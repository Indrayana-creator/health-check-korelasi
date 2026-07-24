<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kode_aset', function (Blueprint $table) {
            $table->string('kode', 10)->primary();
            $table->string('kategori', 100);
            $table->string('nama', 150);
            $table->timestamps();
        });

        Schema::table('aset', function (Blueprint $table) {
            $table->dropColumn('perangkat');
            $table->string('kode_aset_kode', 10)->after('uker_kode');
        });
    }

    public function down(): void
    {
        Schema::table('aset', function (Blueprint $table) {
            $table->dropColumn('kode_aset_kode');
            $table->enum('perangkat', [
                'Laptop', 'PC All in One', 'Switch', 'Router', 'CCTV', 'Genset', 'UPS', 'Printer',
            ])->after('uker_kode');
        });

        Schema::dropIfExists('kode_aset');
    }
};