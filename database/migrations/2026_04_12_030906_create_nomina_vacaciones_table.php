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
        Schema::create('nomina_vacaciones', function (Blueprint $table) {
            $table->id();
            $table->string('cedula', 20)->index();
            $table->smallInteger('anio')->unsigned();
            $table->string('periodo', 80);
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->decimal('dias_tomados', 5, 1)->default(0);
            $table->decimal('dias_corresponden', 5, 1)->nullable();
            $table->timestamps();

            $table->foreign('cedula')->references('cedula')->on('nomina_empleados')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nomina_vacaciones');
    }
};
