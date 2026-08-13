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
        // Email sebenarnya gak pernah dipakai buat apapun yang fungsional di
        // app ini -- login pakai PN, gak ada email verification/notifikasi
        // yang beneran terkirim (MAIL_MAILER=log). Dijadikan nullable
        // (bukan dihapus kolomnya) biar data lama yang udah kadung ada
        // emailnya tetap aman, cuma gak lagi diwajibkan buat akun baru.
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};
