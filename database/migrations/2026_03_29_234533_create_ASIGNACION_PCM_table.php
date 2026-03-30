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
        Schema::create('ASIGNACION_PCM', function (Blueprint $table) {
            $table->string('CODIGO_DOC', 6)->nullable();
            $table->integer('CODIGO_MAT')->nullable();
            $table->string('CURSO', 5)->nullable();
            $table->string('IHS', 1)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ASIGNACION_PCM');
    }
};
