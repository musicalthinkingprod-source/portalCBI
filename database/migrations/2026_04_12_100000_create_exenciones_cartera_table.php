<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exenciones_cartera', function (Blueprint $table) {
            $table->id();
            $table->integer('codigo_alumno');
            $table->string('motivo', 200)->nullable();
            $table->date('vence_en')->nullable(); // null = sin vencimiento
            $table->string('creado_por', 25);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exenciones_cartera');
    }
};
