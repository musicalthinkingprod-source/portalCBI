<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacora_entradas', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('codigo_alumno')->index();
            $table->unsignedInteger('categoria_id');
            $table->date('fecha');
            $table->integer('anio')->index();
            $table->text('observacion');
            $table->string('registrado_por', 30)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora_entradas');
    }
};
