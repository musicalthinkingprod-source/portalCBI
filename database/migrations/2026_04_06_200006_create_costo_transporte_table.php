<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla maestra de tarifas de transporte.
     * Define los códigos de transporte y su costo correspondiente.
     */
    public function up(): void
    {
        Schema::create('costo_transporte', function (Blueprint $table) {
            $table->string('codigo_transporte', 20)->primary();
            $table->decimal('costo', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('costo_transporte');
    }
};
