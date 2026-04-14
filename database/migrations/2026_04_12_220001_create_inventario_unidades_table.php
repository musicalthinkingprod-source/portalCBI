<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_unidades', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_codigo', 30);
            $table->string('codigo', 30)->unique();
            $table->string('serial', 80)->nullable();
            $table->enum('estado', ['bueno', 'dañado', 'baja'])->default('bueno');
            $table->string('asignado_a', 100)->nullable();
            $table->boolean('libre')->default(true);
            $table->text('observaciones')->nullable();
            $table->text('especificaciones')->nullable();
            $table->timestamps();

            $table->foreign('tipo_codigo')
                  ->references('codigo')
                  ->on('inventario_tipos')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');

            $table->index('tipo_codigo');
            $table->index('libre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_unidades');
    }
};
