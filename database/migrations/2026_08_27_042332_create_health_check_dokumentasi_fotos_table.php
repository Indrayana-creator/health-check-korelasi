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
        // Revisi Pak Indra -- Dokumentasi Visual (Kategori E) sebelumnya cuma
        // 1 kolom scalar per kategori (foto_ruang_server_url dkk di
        // health_check_forms), jadi cuma bisa 1 foto per kategori & gak bisa
        // dihapus tanpa nimpa pakai foto baru. Ditarik jadi tabel sendiri
        // (one-to-many) biar 1 kategori bisa punya BANYAK foto, dan tiap foto
        // bisa dihapus satu-satu.
        Schema::create('health_check_dokumentasi_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_check_form_id')->constrained('health_check_forms')->cascadeOnDelete();
            $table->string('field');
            $table->string('path');
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        // Kolom lama gak pernah kepakai sama sekali di data asli (semua form
        // yang ada masih null di ketiganya), jadi aman dihapus langsung tanpa
        // migrasi data.
        Schema::table('health_check_forms', function (Blueprint $table) {
            $table->dropColumn(['foto_ruang_server_url', 'foto_storage_cctv_url', 'foto_panel_ups_url']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('health_check_forms', function (Blueprint $table) {
            $table->string('foto_ruang_server_url')->nullable()->after('catatan_tindak_lanjut');
            $table->string('foto_storage_cctv_url')->nullable()->after('foto_ruang_server_url');
            $table->string('foto_panel_ups_url')->nullable()->after('foto_storage_cctv_url');
        });

        Schema::dropIfExists('health_check_dokumentasi_fotos');
    }
};
