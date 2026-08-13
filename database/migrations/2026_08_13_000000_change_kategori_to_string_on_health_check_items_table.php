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
        // kategori sebelumnya enum hardcode 5 nilai (A-E) -- begitu nambah
        // kategori baru (F - Genset) di config/health_check_checklist.php,
        // enum jadi bottleneck (INSERT ditolak DB). Diubah ke string bebas
        // biar konsisten sama pola kode_aset.kategori yang juga string, dan
        // ke depannya nambah/ubah kategori checklist cukup lewat config,
        // gak perlu migration schema lagi.
        Schema::table('health_check_items', function (Blueprint $table) {
            $table->string('kategori')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('health_check_items', function (Blueprint $table) {
            $table->enum('kategori', [
                'A - Ruang Server/Jaringan',
                'B - CCTV & Storage',
                'C - Jaringan',
                'D - Power System (UPS)',
                'E - Dokumentasi Visual',
            ])->change();
        });
    }
};
