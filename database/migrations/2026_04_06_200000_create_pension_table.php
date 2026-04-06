<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de asignación de pensión por estudiante.
     * Registra qué código de valor de pensión (tarifa) tiene asignado cada alumno,
     * junto con el concepto contable y el centro de costos correspondiente.
     */
    public function up(): void
    {
        Schema::create('pension', function (Blueprint $table) {
            $table->id();

            // Código del estudiante — referencia a ESTUDIANTES.CODIGO
            $table->integer('codigo_alumno');

            // Código que identifica la tarifa/valor de pensión asignada al alumno
            // (referenciará la tabla pension_valores una vez creada)
            $table->string('codigo_valor_pension', 20);

            // Código del concepto contable (ej. 'PENSION', 'MATRICULA', etc.)
            $table->string('codigo_concepto', 20);

            // Centro de costos al que pertenece el cobro
            $table->string('centro_costos', 50);

            // Año lectivo al que aplica la asignación
            $table->year('anio')->default(date('Y'));

            $table->timestamps();

            // Un alumno solo puede tener una asignación de pensión por año
            $table->unique(['codigo_alumno', 'anio'], 'uq_pension_alumno_anio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pension');
    }
};
