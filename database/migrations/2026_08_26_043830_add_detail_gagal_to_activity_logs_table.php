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
        // Sebelum ini, daftar baris yang gagal pas upload massal cuma
        // ditampilin sekali doang lewat session flash lalu ilang -- gak ada
        // jejaknya lagi begitu halaman di-reload/ditutup. Disimpen di sini
        // biar bisa ditengok lagi kapan aja dari Log History.
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->json('detail_gagal')->nullable()->after('keterangan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn('detail_gagal');
        });
    }
};
