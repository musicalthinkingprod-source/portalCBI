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
        Schema::create('PIAR_CARACT_MAT', function (Blueprint $table) {
            $table->integer('CODIGO_ALUM');
            $table->integer('CODIGO_MAT');
            $table->string('CODIGO_DOC', 10);
            $table->text('CARACTERIZACION')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PIAR_CARACT_MAT');
    }
};
