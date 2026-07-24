<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sudah dieksekusi manual sebagian sebelumnya.
        // Lanjutannya ada di migration fix_kode_aset_fk_and_remaining_columns.
    }

    public function down(): void
    {
        //
    }
};