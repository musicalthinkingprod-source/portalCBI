<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ESTUDIANTES_CURSOS_ANUAL', function (Blueprint $table) {
            $table->integer('CODIGO_ALUM');
            $table->string('CURSO_2024', 4);
            $table->string('CURSO_2025', 4);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ESTUDIANTES_CURSOS_ANUAL');
    }
};
