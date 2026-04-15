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
        Schema::create('nomina_induccion_empleado', function (Blueprint $table) {
            $table->id();
            $table->string('cedula', 20)->index();
            $table->foreignId('item_id')->constrained('nomina_induccion_items')->onDelete('cascade');
            $table->boolean('completado')->default(false);
            $table->date('fecha_completado')->nullable();
            $table->string('responsable', 100)->nullable();
            $table->string('observacion', 300)->nullable();
            $table->timestamps();

            $table->unique(['cedula', 'item_id']);
            $table->foreign('cedula')->references('cedula')->on('nomina_empleados')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nomina_induccion_empleado');
    }
};
