<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('centro_costos', function (Blueprint $table) {
            $table->integer('codigo_centro')->primary();
            $table->string('nombre', 100);
        });

        Schema::create('conceptos', function (Blueprint $table) {
            $table->string('codigo_concepto', 10)->primary();
            $table->string('concepto', 100);
            $table->string('centro_costo', 10)->nullable();
        });

        Schema::create('costo_pension', function (Blueprint $table) {
            $table->integer('codigo_pension')->primary();
            $table->decimal('valor', 12, 2);
        });

        Schema::create('costo_transporte', function (Blueprint $table) {
            $table->integer('codigo_transporte')->primary();
            $table->decimal('costo', 12, 2);
        });

        Schema::create('pension', function (Blueprint $table) {
            $table->id();
            $table->integer('codigo_alumno');
            $table->integer('codigo_pension');
            $table->string('codigo_concepto', 10)->nullable();
            $table->string('centro_costo', 10)->nullable();
        });

        Schema::create('transporte', function (Blueprint $table) {
            $table->id();
            $table->integer('codigo_alumno');
            $table->integer('codigo_transporte');
            $table->string('codigo_concepto', 10)->nullable();
            $table->string('centro_costo', 10)->nullable();
        });

        Schema::create('nivelacion', function (Blueprint $table) {
            $table->id();
            $table->integer('codigo_alumno');
            $table->integer('codigo_nivelacion')->nullable();
            $table->string('codigo_concepto', 10)->nullable();
            $table->string('centro_costo', 10)->nullable();
        });

        Schema::create('listado_transporte', function (Blueprint $table) {
            $table->id();
            $table->integer('codigo_alumno');
            $table->string('direccion', 200)->nullable();
            $table->string('barrio', 100)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('acudiente', 150)->nullable();
            $table->string('clase_ruta', 30)->nullable();
            $table->string('ruta', 10)->nullable();
        });

        Schema::create('observaciones_contables', function (Blueprint $table) {
            $table->id();
            $table->integer('codigo_alumno');
            $table->text('observacion')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observaciones_contables');
        Schema::dropIfExists('listado_transporte');
        Schema::dropIfExists('nivelacion');
        Schema::dropIfExists('transporte');
        Schema::dropIfExists('pension');
        Schema::dropIfExists('costo_transporte');
        Schema::dropIfExists('costo_pension');
        Schema::dropIfExists('conceptos');
        Schema::dropIfExists('centro_costos');
    }
};
