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
        Schema::create('HORARIOS', function (Blueprint $table) {
            $table->string('CURSO', 8);
            $table->integer('DIA');
            $table->integer('HORA');
            $table->integer('CODIGO_MAT');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('HORARIOS');
    }
};
