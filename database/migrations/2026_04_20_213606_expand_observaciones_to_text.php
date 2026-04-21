<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE OBSERVACIONES_2024 MODIFY OBSERVACION TEXT NOT NULL");
        DB::statement("ALTER TABLE OBSERVACIONES_2025 MODIFY OBSERVACION TEXT NOT NULL");
        DB::statement("ALTER TABLE OBSERVACIONES_2026 MODIFY OBSERVACION TEXT NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE OBSERVACIONES_2024 MODIFY OBSERVACION VARCHAR(512) NOT NULL");
        DB::statement("ALTER TABLE OBSERVACIONES_2025 MODIFY OBSERVACION VARCHAR(512) NOT NULL");
        DB::statement("ALTER TABLE OBSERVACIONES_2026 MODIFY OBSERVACION VARCHAR(512) NOT NULL");
    }
};
