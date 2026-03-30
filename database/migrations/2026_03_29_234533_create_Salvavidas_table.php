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
        Schema::create('Salvavidas', function (Blueprint $table) {
            $table->integer('CODIGO_ALUM');
            $table->integer('CODIGO_MAT');
            $table->integer('PERIODO');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Salvavidas');
    }
};
