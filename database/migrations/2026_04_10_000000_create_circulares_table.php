<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('circulares', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->unique();   // CIR-001-2026
            $table->date('fecha');
            $table->string('asunto', 255);
            $table->string('dirigido_a', 255);
            $table->string('emitido_por', 255);
            $table->longText('contenido');             // HTML del editor
            $table->enum('estado', ['borrador', 'publicada'])->default('borrador');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('circulares');
    }
};
