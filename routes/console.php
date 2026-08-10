<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Catatan: schedule di sini cuma jalan otomatis kalau ada cron OS yang
// mancing `php artisan schedule:run` tiap menit (belum ada di server
// dev/demo ini) -- lihat komentar CekKendalaSla & KirimReminderPengisianHealthCheck.
Schedule::command('kendala:cek-sla')->dailyAt('08:00');
Schedule::command('healthcheck:reminder-pengisian')->dailyAt('08:00');
