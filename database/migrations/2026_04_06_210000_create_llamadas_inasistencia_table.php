<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registro de llamadas a padres por inasistencia.
     * Cada fila corresponde a la gestión realizada por secretaría
     * para un estudiante ausente en una fecha específica.
     */
    public function up(): void
    {
        Schema::create('llamadas_inasistencia', function (Blueprint $table) {
            $table->id();

            // Código del estudiante — referencia a ESTUDIANTES.CODIGO
            $table->integer('codigo');

            // Fecha de la inasistencia (coincide con ASISTENCIA.FECHA)
            $table->date('fecha_inasistencia');

            // Motivo informado por el padre/acudiente al ser contactado
            $table->string('motivo', 300);

            // Persona que atendió la llamada (padre, madre, acudiente, etc.)
            $table->string('quien_atendio', 100)->nullable();

            // Observaciones adicionales de la secretaria
            $table->text('observacion')->nullable();

            // Usuario que registró la llamada
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Un registro por estudiante por fecha
            $table->unique(['codigo', 'fecha_inasistencia'], 'uq_llamada_codigo_fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('llamadas_inasistencia');
    }
};
