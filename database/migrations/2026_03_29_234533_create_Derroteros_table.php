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
        Schema::create('Derroteros', function (Blueprint $table) {
            $table->integer('CODIGO_ALUM');
            $table->string('MATERIA', 70);
            $table->string('HORARIO', 200);
            $table->string('DOCENTE', 70);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Derroteros');
    }
};
