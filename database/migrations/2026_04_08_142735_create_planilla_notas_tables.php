<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Columnas (actividades) de cada planilla
        Schema::create('planilla_columnas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_doc', 20);
            $table->integer('codigo_mat');
            $table->string('curso', 20);
            $table->tinyInteger('periodo');
            $table->smallInteger('anio');
            $table->char('categoria', 1);       // P = Procedimental · C = Cognitivo · A = Actitudinal
            $table->string('nombre_actividad', 100);
            $table->tinyInteger('orden')->default(0);
            $table->timestamps();
        });

        // Notas por estudiante por columna
        Schema::create('planilla_notas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('columna_id')->constrained('planilla_columnas')->onDelete('cascade');
            $table->integer('codigo_alumno');
            $table->decimal('nota', 4, 1)->nullable();
            $table->timestamps();
            $table->unique(['columna_id', 'codigo_alumno']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planilla_notas');
        Schema::dropIfExists('planilla_columnas');
    }
};
