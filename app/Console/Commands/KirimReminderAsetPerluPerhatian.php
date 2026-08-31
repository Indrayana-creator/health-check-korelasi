<?php

namespace App\Console\Commands;

use App\Http\Controllers\AsetController;
use App\Models\Aset;
use App\Models\Uker;
use App\Models\User;
use App\Notifications\ReminderAsetPerluPerhatian;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

#[Signature('aset:reminder-perlu-perhatian {--paksa : Lewati pengecekan hari Senin, kirim kapan aja (buat testing/demo)}')]
#[Description('Kirim reminder mingguan ke tiap uker yang punya aset masuk kategori "Perlu Perhatian" (rusak/tidak layak, lewat umur belum PH, atau belum dicek ulang 180 hari)')]
class KirimReminderAsetPerluPerhatian extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = now();

        if (! $this->option('paksa') && ! $now->isMonday()) {
            $this->info("Sekarang hari {$now->locale('id')->dayName}, bukan Senin. Skip.");

            return self::SUCCESS;
        }

        $batasStale = $now->copy()->subDays(AsetController::AMBANG_HARI_ASET_STALE);
        $awalMinggu = $now->copy()->startOfWeek(Carbon::MONDAY);

        $jumlahAsetPerUker = Aset::perluPerhatian($batasStale)->get(['uker_kode'])->countBy('uker_kode');

        $totalDiingatkan = 0;
        foreach ($jumlahAsetPerUker as $ukerKode => $jumlahAset) {
            // Uker::find() bakal nyari lewat primary key 'id', BUKAN 'kode'
            // (business key) -- harus query eksplisit lewat kolom 'kode'.
            $uker = Uker::where('kode', $ukerKode)->first();
            if (! $uker) {
                continue;
            }

            // Cuma uker yang beneran punya user login yang diingatkan -- node
            // organisasi murni (KANWIL/AREA tanpa akun user) otomatis kelewat.
            $users = User::where('uker_kode', $uker->kode)->where('is_active', true)->get();
            if ($users->isEmpty()) {
                continue;
            }

            // Dedup: kalau user pertama di uker ini udah dapet reminder minggu
            // ini, anggap semua user di uker itu udah diingatkan juga.
            $sudahDireminder = $users->first()->notifications()
                ->where('type', ReminderAsetPerluPerhatian::class)
                ->where('created_at', '>=', $awalMinggu)
                ->exists();
            if ($sudahDireminder) {
                continue;
            }

            $users->each->notify(new ReminderAsetPerluPerhatian($uker, $jumlahAset));
            $totalDiingatkan++;
        }

        $this->info("Reminder terkirim ke {$totalDiingatkan} uker yang punya aset perlu perhatian.");

        return self::SUCCESS;
    }
}
