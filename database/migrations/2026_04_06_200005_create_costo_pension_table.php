<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla maestra de tarifas de pensión.
     * Define los códigos de valor de pensión y su monto correspondiente.
     */
    public function up(): void
    {
        Schema::create('costo_pension', function (Blueprint $table) {
            $table->string('codigo_valor_pension', 20)->primary();
            $table->decimal('valor', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('costo_pension');
    }
};
