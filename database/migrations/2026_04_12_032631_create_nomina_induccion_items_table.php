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
        Schema::create('nomina_induccion_items', function (Blueprint $table) {
            $table->id();
            $table->enum('seccion', ['legal', 'administrativa', 'academica', 'convivencial']);
            $table->string('nombre', 150);
            $table->string('tipo_empleado', 50)->nullable()->comment('null = todos los empleados');
            $table->unsignedTinyInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nomina_induccion_items');
    }
};
