<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de listado de transporte.
     * Registra la información logística de recogida/entrega de cada alumno.
     */
    public function up(): void
    {
        Schema::create('listado_transporte', function (Blueprint $table) {
            $table->id();

            // Código del estudiante — referencia a ESTUDIANTES.CODIGO
            $table->integer('codigo');

            $table->string('barrio', 60)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('quien_recibe', 80)->nullable();
            $table->string('clase_ruta', 30)->nullable();
            $table->string('ruta', 30)->nullable();
            $table->string('direccion', 100)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listado_transporte');
    }
};
