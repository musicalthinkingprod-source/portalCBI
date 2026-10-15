<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Solicitudes de compra de ASEO (INVCBI).
 *
 * Mismo ciclo que las solicitudes de uniformes, pero sobre
 * inv_elementos_aseo e inv_aseo_compras:
 *
 *   Secretaría crea    → estado 'pendiente' → Admin/SuperAd aprueba o rechaza
 *   Admin/SuperAd crea → estado 'aprobada' (no se autoriza a sí misma)
 *   Aprobada → se registra la compra de aseo y queda 'comprada'.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Encabezado de la solicitud (una fila por orden de compra).
        Schema::create('inv_aseo_solicitudes_compra', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('numero')->unique();      // consecutivo del sistema
            $table->date('fecha');
            // pendiente | aprobada | rechazada | comprada | anulada
            $table->string('estado', 12)->default('pendiente')->index();
            $table->string('justificacion', 500)->nullable();
            $table->string('solicitado_por', 20)->nullable()->index();  // → PRINUSERS.USER
            $table->foreignId('proveedor_id')->nullable()->constrained('inv_proveedores')->nullOnDelete();

            // Autorización
            $table->string('autorizado_por', 20)->nullable();
            $table->timestamp('autorizado_at')->nullable();
            $table->string('nota_autorizacion', 500)->nullable();

            // Cierre: compra de aseo efectivamente registrada
            $table->foreignId('aseo_compra_id')->nullable()->constrained('inv_aseo_compras')->nullOnDelete();
            $table->string('comprado_por', 20)->nullable();
            $table->timestamp('comprado_at')->nullable();

            $table->timestamps();
        });

        // Detalle: una fila por elemento pedido.
        Schema::create('inv_aseo_solicitud_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitud_id')->constrained('inv_aseo_solicitudes_compra')->cascadeOnDelete();
            $table->foreignId('elemento_id')->constrained('inv_elementos_aseo')->restrictOnDelete();
            $table->integer('stock_actual')->default(0);              // foto del stock al solicitar
            $table->unsignedInteger('consumo_ref')->default(0);        // consumo del período analizado
            $table->unsignedInteger('cantidad');                       // lo pedido
            $table->unsignedInteger('cantidad_aprobada')->nullable();  // lo que autoriza la jefatura
            $table->decimal('costo_ref', 12, 2)->nullable();           // último costo conocido
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inv_aseo_solicitud_items');
        Schema::dropIfExists('inv_aseo_solicitudes_compra');
    }
};
