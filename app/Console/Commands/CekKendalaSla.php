<?php

namespace App\Console\Commands;

use App\Http\Controllers\MonitoringController;
use App\Models\HealthCheckItem;
use App\Models\Uker;
use App\Models\User;
use App\Notifications\KendalaMendesakCabang;
use App\Notifications\KendalaSlaTerlambat;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

#[Signature('kendala:cek-sla')]
#[Description('Cek item checklist "Not OK" yang lewat ambang SLA (belum ditindaklanjuti) dan notifikasi admin + cabang terkait')]
class CekKendalaSla extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $itemMendesak = HealthCheckItem::where('status', 'Not OK')
            ->where('status_tindak_lanjut', '!=', 'Selesai Diperbaiki')
            ->with('form')
            ->get()
            ->filter(fn ($item) => MonitoringController::itemMendesak($item));

        if ($itemMendesak->isEmpty()) {
            $this->info('Gak ada item checklist yang lewat ambang SLA.');

            return self::SUCCESS;
        }

        $adminDinotif = $this->notifikasiAdmin($itemMendesak);
        $totalCabangDinotif = $this->notifikasiCabang($itemMendesak);

        $this->info("Ditemukan {$itemMendesak->count()} item mendesak. ".
            ($adminDinotif ? 'Admin dinotifikasi. ' : 'Admin sudah dinotifikasi hari ini (skip). ').
            "{$totalCabangDinotif} cabang dinotifikasi.");

        return self::SUCCESS;
    }

    // Notifikasi ke SEMUA admin, total lintas cabang -- perilaku yang sudah
    // ada dari awal, gak diubah. Return true kalau beneran ngirim (buat pesan
    // ringkasan di atas), false kalau di-skip (gak ada admin / udah dinotif).
    protected function notifikasiAdmin(Collection $itemMendesak): bool
    {
        $admin = User::where('role', 'admin')->where('is_active', true)->get();
        if ($admin->isEmpty()) {
            return false;
        }

        // Dedup sederhana -- kalau admin pertama udah dapet notifikasi tipe
        // ini hari ini, anggap semua admin udah dinotifikasi juga (dikirim
        // bareng tiap kali command ini jalan), biar gak spam tiap kali
        // scheduler jalan lagi di hari yang sama.
        $sudahDinotifHariIni = $admin->first()->notifications()
            ->where('type', KendalaSlaTerlambat::class)
            ->where('created_at', '>=', now()->startOfDay())
            ->exists();

        if ($sudahDinotifHariIni) {
            return false;
        }

        $admin->each->notify(new KendalaSlaTerlambat($itemMendesak->count()));

        return true;
    }

    // Notifikasi ke user tiap cabang (role "user") yang punya item mendesak
    // di uker sendiri + seluruh turunannya (subtree) -- reuse
    // Uker::descendantKodes() yang sama dipakai buat scoping Monitoring
    // Kendala, biar konsisten sama apa yang mereka lihat kalau buka
    // halamannya sendiri. Return jumlah cabang (user) yang beneran dinotif.
    protected function notifikasiCabang(Collection $itemMendesak): int
    {
        $users = User::where('role', 'user')->where('is_active', true)->whereNotNull('uker_kode')->get();
        $totalDinotif = 0;

        foreach ($users as $user) {
            $ukerBolehDiakses = Uker::descendantKodes($user->uker_kode);
            $jumlahUntukUser = $itemMendesak->filter(
                fn ($item) => in_array($item->form?->uker_kode, $ukerBolehDiakses)
            )->count();

            if ($jumlahUntukUser === 0) {
                continue;
            }

            $sudahDinotifHariIni = $user->notifications()
                ->where('type', KendalaMendesakCabang::class)
                ->where('created_at', '>=', now()->startOfDay())
                ->exists();

            if ($sudahDinotifHariIni) {
                continue;
            }

            $user->notify(new KendalaMendesakCabang($jumlahUntukUser));
            $totalDinotif++;
        }

        return $totalDinotif;
    }
}
