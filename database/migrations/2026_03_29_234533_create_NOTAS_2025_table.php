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
        Schema::create('NOTAS_2025', function (Blueprint $table) {
            $table->integer('CODIGO_ALUM');
            $table->integer('PERIODO');
            $table->integer('CODIGO_MAT');
            $table->float('NOTA', null, 0);
            $table->string('TIPODENOTA', 1);
            $table->string('CODIGO_DOC', 6);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('NOTAS_2025');
    }
};
