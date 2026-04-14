<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('OBSERVACIONES_2026', function (Blueprint $table) {
            $table->integer('CODIGO_ALUM');
            $table->integer('PERIODO');
            $table->string('OBSERVACION', 512);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('OBSERVACIONES_2026');
    }
};
