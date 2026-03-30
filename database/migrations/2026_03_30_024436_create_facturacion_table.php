<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facturacion', function (Blueprint $table) {
            $table->id();
            $table->integer('codigo_alumno');
            $table->string('concepto', 100);
            $table->decimal('valor', 12, 2);
            $table->string('mes', 20);
            $table->integer('orden')->nullable();
            $table->string('codigo_concepto', 20)->nullable();
            $table->string('concepto_otro', 100)->nullable();
            $table->string('centro_costos', 50)->nullable();
            $table->date('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturacion');
    }
};
