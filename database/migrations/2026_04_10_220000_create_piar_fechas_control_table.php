<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('piar_fechas_control', function (Blueprint $table) {
            $table->id();
            $table->string('tarea_key', 40);
            $table->string('tarea_label', 120);
            $table->tinyInteger('periodo')->nullable(); // 1, 2, 3 — null si no aplica por período
            $table->smallInteger('anio');
            $table->date('fecha_limite')->nullable();
            $table->timestamps();
            $table->unique(['tarea_key', 'anio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('piar_fechas_control');
    }
};
