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
use Illuminate\Support\Facades\DB;

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
        $kodeUker = $jumlahAsetPerUker->keys();

        // Semua query di-batch di sini (bukan per-uker di dalam loop) --
        // kalau aset perlu perhatian nyebar di ratusan uker (RO ini punya
        // 365+ unit kerja), query-per-uker bakal jadi ratusan query tiap
        // command ini jalan.
        $ukers = Uker::whereIn('kode', $kodeUker)->get()->keyBy('kode');
        // Cuma uker yang beneran punya user login yang diingatkan -- node
        // organisasi murni (KANWIL/AREA tanpa akun user) otomatis kelewat.
        $usersPerUker = User::whereIn('uker_kode', $kodeUker)->where('is_active', true)->get()->groupBy('uker_kode');

        // Dedup per USER (bukan per uker lewat 1 user acuan) -- kalau ada user
        // baru ditambahkan ke uker yang minggu ini udah sempat diingatkan, dia
        // tetap harus kebagian, bukan ikut ke-skip gara-gara user LAIN di uker
        // yang sama udah pernah dapet.
        $semuaUserId = $usersPerUker->flatten()->pluck('id');
        $sudahDireminderIds = DB::table('notifications')
            ->where('notifiable_type', User::class)
            ->whereIn('notifiable_id', $semuaUserId)
            ->where('type', ReminderAsetPerluPerhatian::class)
            ->where('created_at', '>=', $awalMinggu)
            ->pluck('notifiable_id');

        $totalDiingatkan = 0;
        foreach ($jumlahAsetPerUker as $ukerKode => $jumlahAset) {
            $uker = $ukers->get($ukerKode);
            if (! $uker) {
                continue;
            }

            $users = $usersPerUker->get($ukerKode, collect())->whereNotIn('id', $sudahDireminderIds);
            if ($users->isEmpty()) {
                continue;
            }

            $users->each->notify(new ReminderAsetPerluPerhatian($uker, $jumlahAset));
            $totalDiingatkan++;
        }

        $this->info("Reminder terkirim ke {$totalDiingatkan} uker yang punya aset perlu perhatian.");

        return self::SUCCESS;
    }
}
