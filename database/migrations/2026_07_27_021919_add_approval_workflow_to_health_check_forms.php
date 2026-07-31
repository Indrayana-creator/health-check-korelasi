<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_check_forms', function (Blueprint $table) {
            $table->enum('status_approval', [
                'Draft', 'Menunggu Approval', 'Disetujui', 'Ditolak',
            ])->default('Draft')->after('catatan_tindak_lanjut');
            $table->text('catatan_approval')->nullable()->after('status_approval');
            $table->string('approved_by_pn')->nullable()->after('catatan_approval');
            $table->timestamp('approved_at')->nullable()->after('approved_by_pn');
        });
    }

    public function down(): void
    {
        Schema::table('health_check_forms', function (Blueprint $table) {
            $table->dropColumn(['status_approval', 'catatan_approval', 'approved_by_pn', 'approved_at']);
        });
    }
};
