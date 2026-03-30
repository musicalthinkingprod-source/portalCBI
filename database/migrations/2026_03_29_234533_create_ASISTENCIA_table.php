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
        Schema::create('ASISTENCIA', function (Blueprint $table) {
            $table->integer('CODIGO');
            $table->date('FECHA');
            $table->string('ASISTENCIA', 2);
            $table->boolean('CARNET');
            $table->boolean('UNIFORME');
            $table->boolean('RETARDO');
            $table->boolean('PRESENTACION');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ASISTENCIA');
    }
};
