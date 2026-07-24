<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'user'])->default('user')->after('email');
            $table->unsignedInteger('uker_kode')->nullable()->after('role');

            $table->foreign('uker_kode')->references('kode')->on('ukers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['uker_kode']);
            $table->dropColumn(['role', 'uker_kode']);
        });
    }
};
