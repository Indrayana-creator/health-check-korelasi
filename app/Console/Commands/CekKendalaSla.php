<?php

namespace App\Console\Commands;

use App\Http\Controllers\MonitoringController;
use App\Models\HealthCheckItem;
use App\Models\User;
use App\Notifications\KendalaSlaTerlambat;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('kendala:cek-sla')]
#[Description('Cek item checklist "Not OK" yang lewat ambang SLA (belum ditindaklanjuti) dan notifikasi admin')]
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

        $admin = User::where('role', 'admin')->where('is_active', true)->get();
        if ($admin->isEmpty()) {
            $this->warn('Ada item mendesak, tapi gak ada admin aktif buat dinotifikasi.');

            return self::SUCCESS;
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
            $this->info('Sudah dinotifikasi hari ini, skip.');

            return self::SUCCESS;
        }

        $admin->each->notify(new KendalaSlaTerlambat($itemMendesak->count()));

        $this->info("Ditemukan {$itemMendesak->count()} item mendesak, notifikasi terkirim ke {$admin->count()} admin.");

        return self::SUCCESS;
    }
}
