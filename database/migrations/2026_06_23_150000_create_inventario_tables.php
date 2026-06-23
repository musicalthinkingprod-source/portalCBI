<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo de Inventario (uniformes y aseo) — INVCBI.
 *
 * Patrón encabezado + detalle para compras de uniformes, ventas de uniformes
 * y entregas de aseo, de modo que un solo registro agrupe varios artículos.
 *
 * El stock NO se almacena: se calcula por movimientos
 *   uniformes = SUM(compras) - SUM(ventas activas)
 *   aseo      = SUM(compras de aseo) - SUM(salidas)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ───────────────────────── Catálogos ─────────────────────────

        // A quién se le compra (uniformes y aseo). Reemplaza el texto libre.
        Schema::create('inv_proveedores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120);
            $table->string('nit', 30)->nullable();
            $table->string('direccion', 120)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Catálogo de uniformes (el "menú" de venta).
        Schema::create('inv_productos', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('codigo')->unique();   // código del negocio (4101…)
            $table->string('nombre', 150);
            $table->decimal('precio_venta', 12, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Catálogo de elementos de aseo.
        Schema::create('inv_elementos_aseo', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('codigo')->unique();   // código del negocio (2000…)
            $table->string('descripcion', 150);
            $table->string('presentacion', 60)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Dependencias internas que reciben aseo (Cocina, Servicios Generales…).
        Schema::create('inv_dependencias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // ──────────────────── Uniformes · Compras ────────────────────

        // Encabezado de compra a proveedor (una fila por factura).
        Schema::create('inv_compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('inv_proveedores')->restrictOnDelete();
            $table->string('factura', 50)->nullable();     // número de factura del proveedor (manual)
            $table->date('fecha');
            $table->decimal('total', 12, 2)->default(0);
            $table->string('observacion', 255)->nullable();
            $table->timestamps();
        });

        // Detalle de compra (una fila por artículo de la factura).
        Schema::create('inv_compra_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained('inv_compras')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('inv_productos')->restrictOnDelete();
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_compra', 12, 2);        // unitario
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });

        // ───────────────────── Uniformes · Ventas ─────────────────────

        // Encabezado de venta / salida (consecutivo automático en 'numero').
        // Una venta puede ir dirigida a un estudiante (con cobro) o ser una
        // dotación a un docente de Ed. Física (sin cobro, total 0).
        Schema::create('inv_ventas', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('numero')->unique();    // consecutivo del sistema
            $table->string('tipo', 12)->default('venta');   // venta (estudiante) | dotacion (docente)
            $table->integer('estudiante_codigo')->nullable()->index(); // → ESTUDIANTES.CODIGO (ventas)
            $table->foreignId('empleado_id')->nullable()->constrained('nomina_empleados')->nullOnDelete(); // → docente (dotación)
            $table->date('fecha');
            $table->decimal('descuento', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->string('estado', 10)->default('activa'); // activa | anulada
            $table->string('vendedor_user', 20)->nullable()->index(); // → PRINUSERS.USER
            $table->timestamps();
        });

        // Detalle de venta (los artículos de ese recibo).
        Schema::create('inv_venta_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('inv_ventas')->cascadeOnDelete();
            $table->foreignId('producto_id')->constrained('inv_productos')->restrictOnDelete();
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_venta', 12, 2);         // unitario al momento de la venta
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });

        // ────────────────────── Aseo · Compras ──────────────────────

        // Encabezado de compra de aseo.
        Schema::create('inv_aseo_compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_id')->constrained('inv_proveedores')->restrictOnDelete();
            $table->string('documento', 50)->nullable();    // número de documento/factura (manual)
            $table->date('fecha');
            $table->decimal('total', 12, 2)->default(0);
            $table->string('observacion', 255)->nullable();
            $table->timestamps();
        });

        // Detalle de compra de aseo.
        Schema::create('inv_aseo_compra_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aseo_compra_id')->constrained('inv_aseo_compras')->cascadeOnDelete();
            $table->foreignId('elemento_id')->constrained('inv_elementos_aseo')->restrictOnDelete();
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_compra', 12, 2);        // unitario
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });

        // ────────────────────── Aseo · Salidas ──────────────────────

        // Encabezado de entrega a una dependencia (acta).
        Schema::create('inv_aseo_salidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dependencia_id')->constrained('inv_dependencias')->restrictOnDelete();
            $table->date('fecha');
            $table->string('observacion', 255)->nullable();
            $table->string('entregado_por', 20)->nullable()->index(); // → PRINUSERS.USER
            $table->timestamps();
        });

        // Detalle de entrega (los elementos entregados en esa acta).
        Schema::create('inv_aseo_salida_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salida_id')->constrained('inv_aseo_salidas')->cascadeOnDelete();
            $table->foreignId('elemento_id')->constrained('inv_elementos_aseo')->restrictOnDelete();
            $table->unsignedInteger('cantidad');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Orden inverso por las llaves foráneas.
        Schema::dropIfExists('inv_aseo_salida_items');
        Schema::dropIfExists('inv_aseo_salidas');
        Schema::dropIfExists('inv_aseo_compra_items');
        Schema::dropIfExists('inv_aseo_compras');
        Schema::dropIfExists('inv_venta_items');
        Schema::dropIfExists('inv_ventas');
        Schema::dropIfExists('inv_compra_items');
        Schema::dropIfExists('inv_compras');
        Schema::dropIfExists('inv_dependencias');
        Schema::dropIfExists('inv_elementos_aseo');
        Schema::dropIfExists('inv_productos');
        Schema::dropIfExists('inv_proveedores');
    }
};
