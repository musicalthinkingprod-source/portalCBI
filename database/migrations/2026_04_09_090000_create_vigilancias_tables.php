<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Posiciones asignadas a cada docente por día de ciclo y descanso
        Schema::create('vigilancias', function (Blueprint $table) {
            $table->id();
            $table->string('CODIGO_DOC', 25);
            $table->tinyInteger('DIA_CICLO');   // 1 al 6
            $table->tinyInteger('DESCANSO');    // 1 o 2
            $table->string('POSICION', 20)->nullable();
            $table->smallInteger('ANIO');
            $table->timestamps();
            $table->unique(['CODIGO_DOC', 'DIA_CICLO', 'DESCANSO', 'ANIO'], 'vig_unique');
        });

        // Calendario: qué día del ciclo (1-6) corresponde a cada fecha
        Schema::create('calendario_academico', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->tinyInteger('dia_ciclo');   // 1 al 6
            $table->smallInteger('anio');
            $table->string('evento', 200)->nullable(); // evento especial del día
            $table->enum('visibilidad', ['todos', 'interno', 'docentes', 'directivas', 'padres'])->default('interno');
            $table->timestamps();
            $table->unique('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vigilancias');
        Schema::dropIfExists('calendario_academico');
    }
};
