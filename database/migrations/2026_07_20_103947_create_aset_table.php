<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aset', function (Blueprint $table) {
            $table->id();
            $table->string('no_asset')->nullable();
            $table->unsignedInteger('uker_kode');
            $table->enum('perangkat', ['Laptop', 'PC', 'Printer', 'UPS', 'Genset']);
            $table->string('holder_pn')->nullable();
            $table->string('status_fisik')->nullable();
            $table->string('status_verifikasi')->nullable();
            $table->timestamps();

            $table->foreign('uker_kode')->references('kode')->on('ukers')->cascadeOnDelete();
            $table->foreign('holder_pn')->references('pn')->on('pekerja')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aset');
    }
};
