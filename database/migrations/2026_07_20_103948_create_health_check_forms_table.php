<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_check_forms', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('uker_kode');
            $table->string('pic_pn')->nullable();
            $table->date('tanggal_pemeriksaan')->nullable();
            $table->string('periode')->nullable();
            $table->timestamps();

            $table->foreign('uker_kode')->references('kode')->on('ukers')->cascadeOnDelete();
            $table->foreign('pic_pn')->references('pn')->on('pekerja')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_check_forms');
    }
};
