<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacora_plantillas', function (Blueprint $table) {
            $table->increments('id');
            // null = disponible para cualquier categoría
            $table->unsignedInteger('categoria_id')->nullable();
            $table->text('texto');
            $table->boolean('activo')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora_plantillas');
    }
};
