<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pekerja', function (Blueprint $table) {
            $table->id();
            $table->string('pn')->unique();          // nomor pekerja
            $table->string('nama');
            $table->string('jabatan')->nullable();
            $table->string('status')->nullable();
            $table->unsignedInteger('uker_kode')->nullable();
            $table->boolean('is_petugas_it')->default(false);
            $table->string('no_hp')->nullable();
            $table->timestamps();

            $table->foreign('uker_kode')->references('kode')->on('ukers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pekerja');
    }
};
