<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_check_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_check_form_id')->constrained()->cascadeOnDelete();
            $table->enum('kategori', [
                'A - Ruang Server/Jaringan',
                'B - CCTV & Storage',
                'C - Jaringan',
                'D - Power System (UPS)',
                'E - Dokumentasi Visual',
            ]);
            $table->string('item_pemeriksaan');
            $table->enum('status', ['OK', 'Not OK', 'N/A', 'Belum Diperiksa'])->default('Belum Diperiksa');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_check_items');
    }
};