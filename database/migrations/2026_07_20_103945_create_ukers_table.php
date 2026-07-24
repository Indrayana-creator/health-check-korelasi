<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ukers', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('kode')->unique();
            $table->string('nama');
            $table->string('jenis')->nullable();       // Kanwil / Kanca Konsol / Unit
            $table->unsignedInteger('kode_spv')->nullable();  // kode uker supervisi (self-reference)
            $table->string('uker_spv')->nullable();     // nama uker supervisi (buat readability)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ukers');
    }
};
