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
        Schema::create('PIAR_MAT', function (Blueprint $table) {
            $table->integer('CODIGO_ALUM');
            $table->integer('CODIGO_MAT');
            $table->text('BARRERAS');
            $table->text('LOGRO1');
            $table->text('DIDACT1');
            $table->text('EVAL1');
            $table->text('LOGRO2');
            $table->text('DIDACT2');
            $table->text('EVAL2');
            $table->text('LOGRO3');
            $table->text('DIDACT3');
            $table->text('EVAL3');
            $table->text('LOGRO4');
            $table->text('DIDACT4');
            $table->text('EVAL4');
            $table->text('ESTRAG_CASERA');
            $table->string('FREC_CASERA', 100);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('PIAR_MAT');
    }
};
