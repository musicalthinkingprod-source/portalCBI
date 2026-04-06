<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de asignación de transporte por estudiante.
     * Registra qué código de transporte tiene asignado cada alumno,
     * junto con el concepto contable y el centro de costos correspondiente.
     */
    public function up(): void
    {
        Schema::create('transporte', function (Blueprint $table) {
            $table->id();

            // Código del estudiante — referencia a ESTUDIANTES.CODIGO
            $table->integer('codigo_alumno');

            // Código que identifica la ruta/servicio de transporte asignado al alumno
            // (referenciará la tabla transporte_valores una vez creada)
            $table->string('codigo_transporte', 20);

            // Código del concepto contable
            $table->string('codigo_concepto', 20);

            // Centro de costos al que pertenece el cobro
            $table->string('centro_costos', 50);

            // Año lectivo al que aplica la asignación
            $table->year('anio')->default(date('Y'));

            $table->timestamps();

            // Un alumno solo puede tener una asignación de transporte por año
            $table->unique(['codigo_alumno', 'anio'], 'uq_transporte_alumno_anio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transporte');
    }
};
