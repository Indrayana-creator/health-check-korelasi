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
        Schema::table('users', function (Blueprint $table) {
            // Cuma berarti kalau role = admin. Bedain "admin biasa" (bisa
            // kelola data, approve edit, tapi GAK bisa hapus apa pun secara
            // permanen) dari "Admin Master" (satu-satunya yang boleh hapus
            // aset/health check/user/data master, & approve Permintaan
            // Hapus). Default false -- admin lama yang sudah ada TIDAK
            // otomatis jadi Admin Master pas migration ini jalan.
            $table->boolean('is_admin_master')->default(false)->after('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin_master');
        });
    }
};
