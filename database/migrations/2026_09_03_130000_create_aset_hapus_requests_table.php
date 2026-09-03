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
        // Alur sama persis kayak aset_edit_requests -- user biasa gak bisa
        // langsung hapus aset di cabangnya sendiri, harus ajukan dulu &
        // nunggu admin approve. Tabel terpisah (bukan nambah kolom "jenis"
        // di aset_edit_requests) biar tetap 1 tabel = 1 konsep, sama pola
        // kayak AsetKondisiLog vs AsetMutasiLog yang juga dipisah walau
        // mirip.
        Schema::create('aset_hapus_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aset_id')->constrained('aset')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->text('alasan')->nullable();
            $table->enum('status', ['Menunggu', 'Disetujui', 'Ditolak'])->default('Menunggu');
            $table->text('catatan_admin')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            // Sekali disetujui, cuma bisa dipakai 1x hapus -- abis itu (kalau
            // gak jadi dipakai) balik terkunci, harus ajukan baru lagi.
            $table->boolean('sudah_dipakai')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aset_hapus_requests');
    }
};
