<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('health_check_items', function (Blueprint $table) {
            // Tindak lanjut per item (beda dari status_tindak_lanjut di level
            // form) -- biar 1 form yang punya beberapa item Not OK bisa
            // dipantau progress perbaikannya satu-satu, dipakai di halaman
            // Monitoring Kendala.
            $table->enum('status_tindak_lanjut', [
                'Belum Ditindaklanjuti', 'Sedang Diproses', 'Selesai Diperbaiki',
            ])->default('Belum Ditindaklanjuti')->after('catatan');
            $table->text('catatan_tindak_lanjut')->nullable()->after('status_tindak_lanjut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('health_check_items', function (Blueprint $table) {
            $table->dropColumn(['status_tindak_lanjut', 'catatan_tindak_lanjut']);
        });
    }
};
