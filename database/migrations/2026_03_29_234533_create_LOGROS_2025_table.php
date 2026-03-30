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
        Schema::create('LOGROS_2025', function (Blueprint $table) {
            $table->string('GRADO', 2);
            $table->integer('CODIGO_MAT');
            $table->integer('PERIODO');
            $table->string('LOGRO', 1023);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('LOGROS_2025');
    }
};
