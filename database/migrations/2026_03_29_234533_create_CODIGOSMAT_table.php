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
        Schema::create('CODIGOSMAT', function (Blueprint $table) {
            $table->integer('CODIGO_MAT')->nullable();
            $table->string('NOMBRE_MAT', 50)->nullable();
            $table->integer('AREA_MAT')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('CODIGOSMAT');
    }
};
