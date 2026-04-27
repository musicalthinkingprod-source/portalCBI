<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE PIAR_MAT MODIFY ESTRAG_CASERA MEDIUMTEXT NULL");
        DB::statement("ALTER TABLE PIAR_MAT MODIFY FREC_CASERA   VARCHAR(255) NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE PIAR_MAT MODIFY ESTRAG_CASERA TEXT NULL");
        DB::statement("ALTER TABLE PIAR_MAT MODIFY FREC_CASERA   VARCHAR(100) NULL");
    }
};
