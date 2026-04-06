<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla maestra de conceptos contables.
     */
    public function up(): void
    {
        Schema::create('conceptos', function (Blueprint $table) {
            $table->string('codigo_concepto', 20)->primary();
            $table->string('concepto', 100);
            $table->string('centro_costos', 20);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conceptos');
    }
};
