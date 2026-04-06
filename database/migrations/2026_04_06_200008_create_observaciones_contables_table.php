<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de observaciones contables por estudiante.
     */
    public function up(): void
    {
        Schema::create('observaciones_contables', function (Blueprint $table) {
            $table->id();
            $table->integer('codigo_alumno');
            $table->text('observacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observaciones_contables');
    }
};
