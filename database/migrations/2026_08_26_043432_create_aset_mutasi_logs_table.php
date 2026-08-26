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
        // Riwayat tiap kali aset dipindah ke Uker lain -- sebelum ini kolom
        // uker_kode di-overwrite langsung tanpa jejak, jadi gak ketahuan aset
        // ini pernah tercatat di cabang mana aja & kapan pindahnya. uker_kode
        // lama/baru sengaja TANPA foreign key (sama pola kayak status_lama/
        // baru di log lain) -- ini fakta historis, bukan constraint referensial,
        // biar gak keblokir kalau suatu saat data Uker lama itu dihapus.
        Schema::create('aset_mutasi_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aset_id')->constrained('aset')->cascadeOnDelete();
            $table->unsignedInteger('uker_kode_lama')->nullable();
            $table->unsignedInteger('uker_kode_baru');
            $table->foreignId('changed_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aset_mutasi_logs');
    }
};
