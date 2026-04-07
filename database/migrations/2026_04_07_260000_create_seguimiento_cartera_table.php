<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seguimiento_cartera', function (Blueprint $table) {
            $table->id();
            $table->integer('codigo_alumno')->index();
            $table->string('tipo', 30)->default('Nota'); // Llamada, Acuerdo, Email, Nota, Otro
            $table->text('nota');
            $table->string('usuario', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seguimiento_cartera');
    }
};
