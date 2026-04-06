<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla de configuración global para la plantilla de exportación CSV.
     * Contiene los datos fijos de la empresa que se repiten en cada fila del CSV.
     */
    public function up(): void
    {
        Schema::create('plantilla_facturacion', function (Blueprint $table) {
            $table->id();
            $table->string('empresa', 100);
            $table->string('tipo', 10)->default('FV');
            $table->string('prefijo', 10)->nullable();
            $table->string('cedula_facturador', 20);
            $table->string('forma_pago', 50)->default('CONTADO');
            $table->integer('numero_inicio')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plantilla_facturacion');
    }
};
