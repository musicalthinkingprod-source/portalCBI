<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('piar_etapas_control', function (Blueprint $table) {
            $table->id();
            $table->smallInteger('anio');
            $table->tinyInteger('periodo');          // 1, 2, 3 o 4
            $table->string('etapa_key', 30);        // anexo1, caract, ajustes, seguimiento
            $table->string('estado', 20)->default('cerrado'); // cerrado, abierto, revision, finalizado
            $table->timestamps();

            $table->unique(['anio', 'periodo', 'etapa_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('piar_etapas_control');
    }
};
