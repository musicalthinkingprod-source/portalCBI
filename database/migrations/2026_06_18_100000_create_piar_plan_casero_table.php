<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('PIAR_PLAN_CASERO', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('CODIGO_ALUM');
            $table->integer('CODIGO_MAT');
            $table->tinyInteger('PERIODO');                       // 1, 2, 3 o 4
            $table->longText('ESTRAG')->nullable();               // Estrategias / actividades para el hogar
            $table->string('FREC', 255)->nullable();              // Frecuencia
            $table->string('ESTADO', 20)->default('pendiente');   // pendiente | revision | con_observaciones | aprobado
            $table->longText('OBSERVACIONES')->nullable();
            $table->string('APROBADO_POR', 100)->nullable();
            $table->date('FECHA_APROBACION')->nullable();
            $table->timestamps();

            $table->unique(['CODIGO_ALUM', 'CODIGO_MAT', 'PERIODO'], 'piar_plan_casero_alum_mat_periodo');
            $table->index(['CODIGO_ALUM', 'CODIGO_MAT'], 'piar_plan_casero_alum_mat');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PIAR_PLAN_CASERO');
    }
};
