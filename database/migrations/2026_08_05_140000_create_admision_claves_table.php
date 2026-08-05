<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Overrides de la clave de respuestas por grado. Si existe una fila para
        // el grado, sus letras reemplazan a las del dataset JSON. Solo SuperAd
        // edita esto. respuestas = { "1": "B", "2": "A", ... }
        Schema::create('admision_claves', function (Blueprint $table) {
            $table->increments('id');
            $table->string('grado_key', 20)->unique();
            $table->json('respuestas');
            $table->string('actualizado_por', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admision_claves');
    }
};
