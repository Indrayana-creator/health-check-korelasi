<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aset', function (Blueprint $table) {
            // hapus foreign key + kolom lama yang gak dipakai lagi
            $table->dropForeign(['holder_pn']);
            $table->dropColumn(['holder_pn', 'status_fisik', 'status_verifikasi']);
        });

        // ganti daftar enum perangkat (harus lewat raw SQL karena MySQL enum;
        // di SQLite gak ada enforcement enum jadi kolom string biasa dari
        // create_aset_table sudah cukup, gak perlu di-modify)
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE aset MODIFY perangkat ENUM(
                'Laptop', 'PC All in One', 'Switch', 'Router', 'CCTV', 'Genset', 'UPS', 'Printer'
            ) NOT NULL");
        }

        Schema::table('aset', function (Blueprint $table) {
            $table->string('merek')->nullable()->after('perangkat');
            $table->string('tipe_model')->nullable()->after('merek');
            $table->string('sn')->nullable()->after('tipe_model');
            $table->string('umur')->nullable()->after('sn');
            $table->string('kondisi')->nullable()->after('umur');

            // Field khusus laptop/PC (wajib diisi kalau perangkat = Laptop/PC All in One)
            $table->string('pemegang_nama')->nullable()->after('kondisi');
            $table->string('jabatan')->nullable()->after('pemegang_nama');
            $table->string('pemegang_pn')->nullable()->after('jabatan');
            $table->string('ip_address')->nullable()->after('pemegang_pn');
            $table->string('status_hardening')->nullable()->after('ip_address');
            $table->string('status_bitlocker')->nullable()->after('status_hardening');
            $table->string('status_antivirus')->nullable()->after('status_bitlocker');

            $table->text('keterangan')->nullable()->after('status_antivirus');
        });
    }

    public function down(): void
    {
        Schema::table('aset', function (Blueprint $table) {
            $table->dropColumn([
                'merek', 'tipe_model', 'sn', 'umur', 'kondisi',
                'pemegang_nama', 'jabatan', 'pemegang_pn', 'ip_address',
                'status_hardening', 'status_bitlocker', 'status_antivirus', 'keterangan',
            ]);
            $table->string('holder_pn')->nullable();
            $table->string('status_fisik')->nullable();
            $table->string('status_verifikasi')->nullable();
        });

        DB::statement("ALTER TABLE aset MODIFY perangkat ENUM(
            'Laptop', 'PC', 'Printer', 'UPS', 'Genset'
        ) NOT NULL");
    }
};