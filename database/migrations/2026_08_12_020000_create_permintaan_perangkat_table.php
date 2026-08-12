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
        Schema::create('permintaan_perangkat', function (Blueprint $table) {
            $table->id();
            // Diisi manual bebas oleh user (bukan auto-generate) -- nomor
            // nota dinas resmi dari surat pengajuan cabang.
            $table->string('no_nota_dinas');
            $table->date('tanggal_request');
            $table->string('fungsi_requester');
            $table->unsignedInteger('jumlah');
            $table->text('keterangan');
            $table->enum('status', ['Pending IT', 'Pending ESO', 'Pending LGA', 'Done Terkirim'])->default('Pending IT');
            $table->text('catatan_admin')->nullable();
            $table->unsignedInteger('uker_kode');
            $table->foreign('uker_kode')->references('kode')->on('ukers')->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permintaan_perangkat');
    }
};
