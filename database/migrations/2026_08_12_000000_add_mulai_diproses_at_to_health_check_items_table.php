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
            // Dicatat OTOMATIS & SEKALI SAJA begitu status_tindak_lanjut pertama
            // kali jadi "Sedang Diproses" -- jadi titik awal buat SLA khusus
            // status ini (beda dari Mendesak yang pakai tanggal_pemeriksaan),
            // lihat MonitoringController::itemMelewatiSlaDiproses().
            $table->timestamp('mulai_diproses_at')->nullable()->after('catatan_tindak_lanjut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('health_check_items', function (Blueprint $table) {
            $table->dropColumn('mulai_diproses_at');
        });
    }
};
