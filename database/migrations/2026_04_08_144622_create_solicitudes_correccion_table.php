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
        Schema::create('solicitudes_correccion', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_doc', 20);
            $table->integer('codigo_alum');
            $table->integer('codigo_mat');
            $table->tinyInteger('periodo');
            $table->smallInteger('anio');
            $table->decimal('nota_actual', 4, 1);
            $table->decimal('nota_propuesta', 4, 1);
            $table->text('motivo');
            $table->enum('estado', ['PENDIENTE', 'APROBADA', 'RECHAZADA'])->default('PENDIENTE');
            $table->string('revisado_por', 20)->nullable();
            $table->text('observacion')->nullable();
            $table->timestamp('revisado_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_correccion');
    }
};
