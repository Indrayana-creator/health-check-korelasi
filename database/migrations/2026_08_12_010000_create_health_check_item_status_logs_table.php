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
        // Riwayat tiap kali status_tindak_lanjut item diubah lewat modal
        // "Update Tindak Lanjut" -- INSERT per perubahan (bukan overwrite),
        // dipakai buat modal "Lihat Riwayat" di Monitoring Kendala. Sama
        // sekali gak berhubungan sama mulai_diproses_at/SLA yang sudah ada.
        Schema::create('health_check_item_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_check_item_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->text('catatan')->nullable();
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_check_item_status_logs');
    }
};
