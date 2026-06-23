<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Precio meta del "uniforme completo" por talla (chaqueta + pantalón de sudadera
 * + camiseta + pantaloneta, sin bata). Cuando el recibo tiene las 4 prendas de
 * una misma talla, el sistema aplica un descuento para dejar el conjunto en este
 * precio (corresponde a la "Sudadera Completa" de la resolución de costos).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inv_uniforme_completo', function (Blueprint $table) {
            $table->id();
            $table->string('talla', 4)->unique();   // 4,6,8,10,12,14,16,S,M,L,XL
            $table->decimal('precio', 12, 2);        // precio meta del conjunto de 4
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_uniforme_completo');
    }
};
