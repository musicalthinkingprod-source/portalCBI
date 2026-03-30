<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registro_pagos', function (Blueprint $table) {
            $table->id();
            $table->integer('codigo_alumno');
            $table->date('fecha');
            $table->decimal('valor', 12, 2);
            $table->string('concepto', 100);
            $table->string('mes', 20);
            $table->string('orden', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registro_pagos');
    }
};
