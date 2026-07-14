<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Retenciones de boletines/promedios en el portal de padres.
     * Cada fila = una retención activa de un estudiante por un área (tipo).
     * Los booleanos RET_* de ESTUDIANTES se mantienen en sincronía para
     * conservar los filtros de búsqueda existentes.
     */
    public function up(): void
    {
        Schema::create('retenciones_boletin', function (Blueprint $table) {
            $table->id();
            $table->integer('codigo_alumno');
            $table->string('tipo', 4); // CART | ACAD | CONV | RECT
            $table->string('motivo', 200)->nullable();
            $table->string('retenido_por', 25);      // USER que la creó
            $table->string('retenido_perfil', 25)->nullable(); // PROFILE al momento
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['codigo_alumno', 'tipo']);
            $table->index('codigo_alumno');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retenciones_boletin');
    }
};
