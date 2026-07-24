<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aset', function (Blueprint $table) {
            $table->dropColumn('umur');
            $table->unsignedSmallInteger('tahun_perolehan')->nullable()->after('sn');
        });
    }

    public function down(): void
    {
        Schema::table('aset', function (Blueprint $table) {
            $table->dropColumn('tahun_perolehan');
            $table->string('umur')->nullable()->after('sn');
        });
    }
};
