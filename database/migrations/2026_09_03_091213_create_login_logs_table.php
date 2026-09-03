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
        // Jejak audit tiap PERCOBAAN login (bukan cuma yang berhasil) --
        // kebutuhan umum auditor internal: "buktikan siapa aja yang pernah
        // akses sistem ini, kapan, dan dari mana". user_id nullable karena
        // percobaan gagal (PN gak ketemu / password salah) belum tentu bisa
        // diresolve ke akun manapun; pn_dicoba tetap disimpan biar percobaan
        // dengan PN acak/asal tebak tetap kelihatan jejaknya. nullOnDelete
        // (bukan cascade) -- riwayat login tetap ada walau akunnya dihapus
        // belakangan.
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('pn_dicoba')->nullable();
            $table->string('status');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_logs');
    }
};
