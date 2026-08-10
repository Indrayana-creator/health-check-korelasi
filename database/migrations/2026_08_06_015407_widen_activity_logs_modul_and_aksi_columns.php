<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Kolom modul & aksi tadinya enum super sempit (cuma buat upload/delete
     * massal). Sekarang activity log juga dipakai buat nyatet aksi satuan
     * (tambah/update/hapus/approve/reject) di modul aset, health_check, user,
     * & uker -- diganti jadi varchar biasa biar gak perlu migration lagi tiap
     * ada jenis aksi/modul baru ke depannya.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite gak punya ALTER COLUMN TYPE / DROP CONSTRAINT -- enum() Laravel
            // di sqlite diemulasi pakai CHECK constraint, jadi satu-satunya cara
            // ngelonggarinnya adalah bikin ulang tabelnya (tanpa doctrine/dbal,
            // yang emang gak ke-install di project ini).
            DB::statement('CREATE TABLE activity_logs_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                user_id INTEGER NOT NULL,
                modul VARCHAR(50) NOT NULL,
                aksi VARCHAR(50) NOT NULL,
                jumlah_baris INTEGER NOT NULL DEFAULT 0,
                keterangan VARCHAR(255),
                created_at DATETIME,
                updated_at DATETIME,
                FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
            )');
            DB::statement('INSERT INTO activity_logs_new SELECT id, user_id, modul, aksi, jumlah_baris, keterangan, created_at, updated_at FROM activity_logs');
            DB::statement('DROP TABLE activity_logs');
            DB::statement('ALTER TABLE activity_logs_new RENAME TO activity_logs');

            return;
        }

        DB::statement("ALTER TABLE activity_logs MODIFY modul VARCHAR(50) NOT NULL");
        DB::statement("ALTER TABLE activity_logs MODIFY aksi VARCHAR(50) NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // Rollback yang presisi butuh recreate tabel lagi dgn CHECK constraint;
            // di luar scope migration ini (widening kolom gak realistis di-rollback
            // dgn aman kalau udah ada baris data pakai nilai aksi/modul baru).
            return;
        }

        DB::statement("ALTER TABLE activity_logs MODIFY modul ENUM('aset', 'health_check', 'pekerja_uker') NOT NULL");
        DB::statement("ALTER TABLE activity_logs MODIFY aksi ENUM('upload_massal', 'delete_massal') NOT NULL");
    }
};
