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
        Schema::create('OBSERVACIONES_2024', function (Blueprint $table) {
            $table->integer('CODIGO_ALUM');
            $table->integer('PERIODO');
            $table->string('OBSERVACION', 512);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('OBSERVACIONES_2024');
    }
};
