<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Facturación de ventas y módulo de devoluciones / cambios.
 *
 * Flujo: la venta nace "por facturar". El Admin la factura.
 *  - Sin facturar  → se puede cancelar (reembolso total) o cualquier cambio (con diferencia).
 *  - Facturada     → solo cambio par (otra prenda del mismo precio, sin dinero); nunca devolución.
 *
 * La prenda devuelta reingresa al stock (el cálculo de stock suma 'entra' y resta 'sale').
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inv_ventas', function (Blueprint $table) {
            $table->boolean('facturada')->default(false)->after('estado');
            $table->timestamp('facturada_at')->nullable()->after('facturada');
            $table->string('facturada_por', 20)->nullable()->after('facturada_at'); // PRINUSERS.USER
        });

        // Encabezado de devolución / cambio (siempre contra una venta).
        Schema::create('inv_devoluciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('inv_ventas')->restrictOnDelete();
            $table->string('tipo', 12);                 // cancelacion | cambio
            $table->date('fecha');
            $table->decimal('diferencia', 12, 2)->default(0); // + cobra al cliente · - se le devuelve
            $table->string('motivo', 255)->nullable();
            $table->string('usuario', 20)->nullable();  // PRINUSERS.USER que lo registró
            $table->timestamps();
        });

        // Detalle: prendas que reingresan ('entra') y prendas entregadas en el cambio ('sale').
        Schema::create('inv_devolucion_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('devolucion_id')->constrained('inv_devoluciones')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('inv_productos')->restrictOnDelete();
            $table->string('sentido', 6);               // entra | sale
            $table->unsignedInteger('cantidad');
            $table->decimal('precio', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_devolucion_items');
        Schema::dropIfExists('inv_devoluciones');
        Schema::table('inv_ventas', function (Blueprint $table) {
            $table->dropColumn(['facturada', 'facturada_at', 'facturada_por']);
        });
    }
};
