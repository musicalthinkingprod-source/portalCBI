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
        Schema::create('HIST_DEF', function (Blueprint $table) {
            $table->integer('ANO');
            $table->integer('CODIGO_ALUM');
            $table->integer('CODIGO_MAT');
            $table->float('DEF', null, 0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('HIST_DEF');
    }
};
