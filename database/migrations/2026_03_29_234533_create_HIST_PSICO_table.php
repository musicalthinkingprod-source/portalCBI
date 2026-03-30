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
        Schema::create('HIST_PSICO', function (Blueprint $table) {
            $table->integer('CODIGO');
            $table->date('FECHA');
            $table->string('DESCRIPCION', 2056);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('HIST_PSICO');
    }
};
