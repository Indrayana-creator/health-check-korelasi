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
        // Riwayat tiap kali kolom kondisi aset berubah (lewat form Edit Aset)
        // -- INSERT per perubahan, dipakai buat modal "Lihat Riwayat" di
        // halaman Data Aset. Kondisi gak pernah dicatat historinya sebelum
        // ini, cuma nilai TERAKHIR yang tersimpan di kolom aset.kondisi.
        Schema::create('aset_kondisi_logs', function (Blueprint $table) {
            $table->id();
            // constrained() tanpa argumen nebak nama tabel dari nama kolom
            // (aset_id -> "asets", pluralisasi Inggris) -- SALAH, tabelnya
            // "aset" (tunggal, lihat Aset::$table), jadi harus eksplisit.
            $table->foreignId('aset_id')->constrained('aset')->cascadeOnDelete();
            $table->string('kondisi_lama')->nullable();
            $table->string('kondisi_baru');
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aset_kondisi_logs');
    }
};
