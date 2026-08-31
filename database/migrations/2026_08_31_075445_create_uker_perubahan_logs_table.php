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
        // Riwayat perubahan struktur organisasi -- sebelumnya kalau Uker
        // pindah cabang induk / ganti nama / ganti jenis, kolomnya langsung
        // ke-overwrite tanpa jejak sama sekali. 1 baris = 1 FIELD yang
        // berubah (bukan 1 baris per update()), biar 1 update yang ngubah
        // beberapa field sekaligus (mis. nama & jenis bareng) tetap kecatat
        // rapi per-field, sama pola kayak log lain (insert-only).
        Schema::create('uker_perubahan_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('uker_kode');
            $table->string('field');
            $table->string('nilai_lama')->nullable();
            $table->string('nilai_baru')->nullable();
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uker_perubahan_logs');
    }
};
