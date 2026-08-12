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
        Schema::table('health_check_forms', function (Blueprint $table) {
            // Kategori E "Dokumentasi Visual" (form resmi ESO Group BRI) --
            // cuma link/URL foto bukti, TIDAK ikut hitungan compliance % yang
            // tetap murni dari checklist Kategori A-D.
            $table->string('foto_ruang_server_url')->nullable()->after('catatan_tindak_lanjut');
            $table->string('foto_storage_cctv_url')->nullable()->after('foto_ruang_server_url');
            $table->string('foto_panel_ups_url')->nullable()->after('foto_storage_cctv_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('health_check_forms', function (Blueprint $table) {
            $table->dropColumn(['foto_ruang_server_url', 'foto_storage_cctv_url', 'foto_panel_ups_url']);
        });
    }
};
