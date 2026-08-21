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
        // Riwayat tiap kali status Permintaan Perangkat berubah -- sebelum
        // ini cuma kolom status TERAKHIR yang tersimpan, jejak "kapan pindah
        // dari Pending IT ke Pending ESO" hilang begitu status diupdate lagi.
        // Sama pola persis kayak aset_kondisi_logs & health_check_item
        // statusLogs -- INSERT per perubahan, gak pernah diubah/dihapus.
        Schema::create('permintaan_perangkat_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permintaan_perangkat_id')->constrained('permintaan_perangkat')->cascadeOnDelete();
            $table->string('status_lama')->nullable();
            $table->string('status_baru');
            $table->text('catatan_admin')->nullable();
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permintaan_perangkat_status_logs');
    }
};
