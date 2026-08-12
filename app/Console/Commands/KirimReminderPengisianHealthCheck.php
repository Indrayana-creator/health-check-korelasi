<?php

namespace App\Console\Commands;

use App\Models\HealthCheckForm;
use App\Models\Uker;
use App\Models\User;
use App\Notifications\ReminderPengisianHealthCheck;
use App\Support\PeriodeMingguan;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('healthcheck:reminder-pengisian {--paksa : Lewati pengecekan hari Kamis, kirim kapan aja (buat testing/demo)}')]
#[Description('Kirim reminder ke uker yang belum bikin form Health Check minggu ini, tiap hari Kamis (H-1 sebelum Jumat)')]
class KirimReminderPengisianHealthCheck extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = now();

        if (! $this->option('paksa') && ! $now->isThursday()) {
            $this->info("Sekarang hari {$now->locale('id')->dayName}, bukan Kamis (H-1 sebelum Jumat). Skip.");

            return self::SUCCESS;
        }

        [$senin, $jumat] = PeriodeMingguan::rentang($now);

        $kodeUkerSudahIsiMingguIni = HealthCheckForm::whereBetween('tanggal_pemeriksaan', [$senin, $jumat])
            ->pluck('uker_kode')
            ->unique();

        $ukerBelumIsi = Uker::whereNotIn('kode', $kodeUkerSudahIsiMingguIni)->get();

        $totalDiingatkan = 0;
        foreach ($ukerBelumIsi as $uker) {
            // Cuma uker yang beneran punya user login yang diingatkan -- node
            // organisasi murni (KANWIL/AREA tanpa akun user) otomatis kelewat.
            $users = User::where('uker_kode', $uker->kode)->where('is_active', true)->get();
            if ($users->isEmpty()) {
                continue;
            }

            // Dedup: kalau user pertama di uker ini udah dapet reminder minggu
            // ini, anggap semua user di uker itu udah diingatkan juga.
            $sudahDireminder = $users->first()->notifications()
                ->where('type', ReminderPengisianHealthCheck::class)
                ->where('created_at', '>=', $senin)
                ->exists();
            if ($sudahDireminder) {
                continue;
            }

            $users->each->notify(new ReminderPengisianHealthCheck($uker));
            $totalDiingatkan++;
        }

        $this->info("Reminder terkirim ke {$totalDiingatkan} uker yang belum mengisi Health Check minggu ini.");

        return self::SUCCESS;
    }
}
