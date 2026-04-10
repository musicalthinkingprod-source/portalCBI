<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop FK constraint and change column to varchar matching PRINUSERS.USER
        DB::statement('ALTER TABLE llamadas_inasistencia DROP FOREIGN KEY llamadas_inasistencia_registrado_por_foreign');
        DB::statement('ALTER TABLE llamadas_inasistencia MODIFY registrado_por VARCHAR(25) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE llamadas_inasistencia MODIFY registrado_por BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE llamadas_inasistencia ADD CONSTRAINT llamadas_inasistencia_registrado_por_foreign FOREIGN KEY (registrado_por) REFERENCES users(id) ON DELETE SET NULL');
    }
};
