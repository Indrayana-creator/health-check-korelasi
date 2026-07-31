<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_check_forms', function (Blueprint $table) {
            $table->enum('status_tindak_lanjut', [
                'Belum Ditindaklanjuti', 'Sedang Diproses', 'Selesai Diperbaiki',
            ])->default('Belum Ditindaklanjuti')->after('periode');
            $table->text('catatan_tindak_lanjut')->nullable()->after('status_tindak_lanjut');
        });
    }

    public function down(): void
    {
        Schema::table('health_check_forms', function (Blueprint $table) {
            $table->dropColumn(['status_tindak_lanjut', 'catatan_tindak_lanjut']);
        });
    }
};
