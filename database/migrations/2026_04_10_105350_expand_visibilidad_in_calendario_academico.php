<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ampliar el ENUM con las nuevas categorías de audiencia
        DB::statement("ALTER TABLE calendario_academico MODIFY COLUMN visibilidad ENUM('todos','interno','docentes','directivas','padres') NOT NULL DEFAULT 'interno'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE calendario_academico MODIFY COLUMN visibilidad ENUM('interno','todos') NOT NULL DEFAULT 'interno'");
    }
};
