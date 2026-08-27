<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Kode lacak publik -- dipakai di link "cek status" yang bisa dibuka
        // SIAPA AJA tanpa login (kayak nomor resi kurir), jadi SENGAJA acak
        // & bukan id sequential biasa (id gampang ditebak/di-enumerasi, bisa
        // kebuka permintaan cabang lain tanpa izin).
        Schema::table('permintaan_perangkat', function (Blueprint $table) {
            $table->string('kode_lacak')->nullable()->unique()->after('id');
        });

        // Backfill buat data lama yang udah ada sebelum kolom ini ada, biar
        // SEMUA permintaan (bukan cuma yang baru diajukan) bisa dilacak.
        DB::table('permintaan_perangkat')->whereNull('kode_lacak')->orderBy('id')->get()->each(function ($row) {
            do {
                $kode = 'PP-'.strtoupper(Str::random(8));
            } while (DB::table('permintaan_perangkat')->where('kode_lacak', $kode)->exists());

            DB::table('permintaan_perangkat')->where('id', $row->id)->update(['kode_lacak' => $kode]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permintaan_perangkat', function (Blueprint $table) {
            $table->dropColumn('kode_lacak');
        });
    }
};
