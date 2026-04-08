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
        Schema::create('ciclos_academicos', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('anio');
            $table->tinyInteger('numero');          // 1, 2, 3 …
            $table->string('nombre', 60);           // "Ciclo 1", "Semana 1-2", etc.
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->timestamps();
            $table->unique(['anio', 'numero']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ciclos_academicos');
    }
};
