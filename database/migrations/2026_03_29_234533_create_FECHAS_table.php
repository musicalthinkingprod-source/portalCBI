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
        Schema::create('FECHAS', function (Blueprint $table) {
            $table->string('CODIGO_FECHA', 10)->primary();
            $table->dateTime('INICIO');
            $table->dateTime('FIN');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('FECHAS');
    }
};
