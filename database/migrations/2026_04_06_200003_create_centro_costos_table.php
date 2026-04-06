<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla maestra de centros de costos.
     */
    public function up(): void
    {
        Schema::create('centro_costos', function (Blueprint $table) {
            $table->string('codigo_centro_costos', 20)->primary();
            $table->string('nombre_centro_costos', 100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('centro_costos');
    }
};
