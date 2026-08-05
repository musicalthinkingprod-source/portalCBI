<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admision_evaluaciones', function (Blueprint $table) {
            $table->increments('id');

            // Grado al que aspira ingresar (llave del dataset: TERCERO, ONCE, ...)
            $table->string('grado_key', 20);
            $table->string('grado_nombre', 40);
            $table->enum('tipo', ['opcion_multiple', 'cualitativo']);

            // Datos del aspirante (aún no es estudiante: no tiene código)
            $table->string('aspirante_nombre', 150);
            $table->string('aspirante_documento', 30)->nullable();
            $table->date('fecha_examen')->nullable();
            $table->string('acudiente', 150)->nullable();
            $table->string('telefono', 40)->nullable();
            $table->text('observaciones')->nullable();

            // Respuestas marcadas por materia/pregunta y resultados calculados
            // respuestas:  { "1": "B", "2": "A", ... }  (A-D o L/P/N)
            // resultados:  { "materias": [{nombre, correctas, total, porcentaje, ...}], "total": {...} }
            $table->json('respuestas');
            $table->json('resultados');

            // Puntaje global (correctas) para MC; null en cualitativo
            $table->unsignedSmallInteger('puntaje')->nullable();
            $table->unsignedSmallInteger('total_preguntas')->default(0);
            $table->decimal('porcentaje', 5, 2)->nullable();

            // Trazabilidad de quién evaluó
            $table->string('evaluado_por', 100)->nullable();

            $table->timestamps();

            $table->index('grado_key');
            $table->index('aspirante_documento');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admision_evaluaciones');
    }
};
