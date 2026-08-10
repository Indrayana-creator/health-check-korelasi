<?php

namespace App\Console\Commands;

use App\Models\HealthCheckForm;
use App\Models\Uker;
use App\Models\User;
use App\Notifications\ReminderPengisianHealthCheck;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('healthcheck:reminder-pengisian {--paksa : Lewati pengecekan window H-3, kirim kapan aja (buat testing/demo)}')]
#[Description('Kirim reminder ke uker yang belum bikin form Health Check bulan ini, mulai H-3 sebelum akhir bulan')]
class KirimReminderPengisianHealthCheck extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = now();
        $sisaHari = $now->daysInMonth - $now->day;

        if (! $this->option('paksa') && $sisaHari > 3) {
            $this->info("Masih {$sisaHari} hari lagi sebelum akhir bulan, belum masuk window H-3. Skip.");

            return self::SUCCESS;
        }

        $awalBulan = $now->copy()->startOfMonth();
        $akhirBulan = $now->copy()->endOfMonth();

        $kodeUkerSudahIsiBulanIni = HealthCheckForm::whereBetween('tanggal_pemeriksaan', [$awalBulan, $akhirBulan])
            ->pluck('uker_kode')
            ->unique();

        $ukerBelumIsi = Uker::whereNotIn('kode', $kodeUkerSudahIsiBulanIni)->get();

        $totalDiingatkan = 0;
        foreach ($ukerBelumIsi as $uker) {
            // Cuma uker yang beneran punya user login yang diingatkan -- node
            // organisasi murni (KANWIL/AREA tanpa akun user) otomatis kelewat.
            $users = User::where('uker_kode', $uker->kode)->where('is_active', true)->get();
            if ($users->isEmpty()) {
                continue;
            }

            // Dedup: kalau user pertama di uker ini udah dapet reminder bulan
            // ini, anggap semua user di uker itu udah diingatkan juga.
            $sudahDireminder = $users->first()->notifications()
                ->where('type', ReminderPengisianHealthCheck::class)
                ->where('created_at', '>=', $awalBulan)
                ->exists();
            if ($sudahDireminder) {
                continue;
            }

            $users->each->notify(new ReminderPengisianHealthCheck($uker));
            $totalDiingatkan++;
        }

        $this->info("Reminder terkirim ke {$totalDiingatkan} uker yang belum mengisi Health Check bulan ini.");

        return self::SUCCESS;
    }
}
