<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hilo de conversación colgado de una anotación de la bitácora.
        // Cada anotación (de un estudiante) tiene su propio hilo: staff y acudiente
        // responden ahí en lugar de crear más anotaciones sueltas.
        Schema::create('bitacora_comentarios', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('entrada_id')->index();
            $table->string('autor_rol', 12);              // 'staff' | 'acudiente'
            $table->string('autor_id', 30)->nullable();   // USER del staff o código del alumno
            $table->string('autor_nombre', 120)->nullable();
            $table->text('mensaje');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora_comentarios');
    }
};
