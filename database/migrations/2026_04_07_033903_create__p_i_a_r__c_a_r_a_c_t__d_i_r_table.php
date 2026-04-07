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
        Schema::create('PIAR_CARACT_DIR', function (Blueprint $table) {
            $table->integer('CODIGO_ALUM');
            $table->string('CODIGO_DOC', 10);
            $table->string('CURSO', 5);
            $table->text('CARACTERIZACION')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PIAR_CARACT_DIR');
    }
};
