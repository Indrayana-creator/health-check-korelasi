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
            $table->foreign('kode_aset_kode')->references('kode')->on('kode_aset');
            $table->string('kapasitas_memori')->nullable()->after('sn');
            $table->string('status_dlp')->nullable()->after('status_bitlocker');
        });

        // Raw MySQL-only DDL: ubah kondisi jadi ENUM & perketat panjang kolom.
        // Di SQLite kolom-kolom ini sudah string nullable dari migration
        // sebelumnya, jadi gak ada yang perlu diubah.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE aset MODIFY kondisi ENUM(
                'NORMAL', 'NON DATABASE', 'PH/DISMANTEL', 'RUSAK', 'BACKUP',
                'SERVICE CENTER', 'TIDAK DIGUNAKAN', 'TIDAK LAYAK'
            ) NULL");

            DB::statement('ALTER TABLE aset MODIFY pemegang_nama VARCHAR(150) NULL');
            DB::statement('ALTER TABLE aset MODIFY jabatan VARCHAR(150) NULL');
            DB::statement('ALTER TABLE aset MODIFY pemegang_pn VARCHAR(50) NULL');
            DB::statement('ALTER TABLE aset MODIFY ip_address VARCHAR(50) NULL');
            DB::statement('ALTER TABLE aset MODIFY status_hardening VARCHAR(50) NULL');
            DB::statement('ALTER TABLE aset MODIFY status_bitlocker VARCHAR(50) NULL');
        }
    }

    public function down(): void
    {
        Schema::table('aset', function (Blueprint $table) {
            $table->dropForeign(['kode_aset_kode']);
            $table->dropColumn(['kapasitas_memori', 'status_dlp']);
        });
    }
};
