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
        // Riwayat perubahan akun User (role, uker_kode, status aktif, nama)
        // -- sebelumnya cuma kecatat sebagai teks bebas di ActivityLog
        // ("User X diupdate"), gak ada rincian field mana yang berubah dari
        // nilai apa ke apa. Penting buat access-review: kalau ada akun yang
        // rolenya berubah jadi admin, harus jelas kapan & disetujui siapa.
        // Pola sama kayak UkerPerubahanLog -- 1 baris = 1 field yang berubah.
        Schema::create('user_perubahan_logs', function (Blueprint $table) {
            $table->id();
            // nullOnDelete (BUKAN cascade) buat user_id maupun changed_by --
            // riwayat perubahan ini justru paling penting dipertahankan SETELAH
            // akunnya dihapus (bukti "sempat ada perubahan role X ke Y sebelum
            // akunnya dihapus"), jangan ikut lenyap begitu subjek atau
            // pelakunya dihapus dari sistem.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('field');
            $table->string('nilai_lama')->nullable();
            $table->string('nilai_baru')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_perubahan_logs');
    }
};
