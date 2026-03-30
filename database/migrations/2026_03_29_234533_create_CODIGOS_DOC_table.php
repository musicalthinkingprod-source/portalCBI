<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('CODIGOS_DOC', function (Blueprint $table) {
            $table->integer('ID_DOCENTE');
            $table->string('CODIGO_DOC', 10);
            $table->string('NOMBRE_DOC', 150);
            $table->enum('TIPO', ['DOCENTE', 'ADMINISTRATIVO'])->default('DOCENTE');
            $table->string('OBSERVACIONES')->nullable();
            $table->timestamp('FECHA_CREACION')->nullable()->useCurrent();
            $table->string('ESTADO', 20);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('CODIGOS_DOC');
    }
};
