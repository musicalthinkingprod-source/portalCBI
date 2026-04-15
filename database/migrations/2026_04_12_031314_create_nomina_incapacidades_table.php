<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nomina_incapacidades', function (Blueprint $table) {
            $table->id();
            $table->string('cedula', 20)->index();
            $table->string('nombre_empleado', 150);
            $table->smallInteger('anio')->unsigned();
            $table->string('mes', 20)->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->decimal('sueldo_basico_momento', 12, 2)->nullable();
            $table->decimal('dias_incapacidad', 5, 1);
            $table->decimal('valor_nomina', 12, 2)->nullable();
            $table->string('eps', 80)->nullable();
            $table->enum('estado_eps', ['radicada', 'no_se_radica', 'pendiente'])->default('pendiente');
            $table->date('fecha_radicacion_eps')->nullable();
            $table->string('numero_radicacion', 50)->nullable();
            $table->decimal('valor_pagado_eps', 12, 2)->nullable();
            $table->date('fecha_pago_eps')->nullable();
            $table->decimal('valor_pendiente', 12, 2)->nullable();
            $table->string('observacion', 300)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nomina_incapacidades');
    }
};
