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
        Schema::create('LISTADOS_ESPECIALES', function (Blueprint $table) {
            $table->integer('CODIGO_ALUM');
            $table->string('GRUPO', 5);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('LISTADOS_ESPECIALES');
    }
};
