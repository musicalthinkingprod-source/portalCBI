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
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_doc', 25);   // destinatario
            $table->string('tipo', 30);          // 'reemplazo', 'permiso', etc.
            $table->string('titulo', 150);
            $table->string('mensaje', 400);
            $table->boolean('leida')->default(false);
            $table->timestamps();
            $table->index(['codigo_doc', 'leida']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
