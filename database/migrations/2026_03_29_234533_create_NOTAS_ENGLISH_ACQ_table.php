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
        Schema::create('NOTAS_ENGLISH_ACQ', function (Blueprint $table) {
            $table->integer('CODIGO_ALUM');
            $table->integer('CODIGO_DOC');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('NOTAS_ENGLISH_ACQ');
    }
};
