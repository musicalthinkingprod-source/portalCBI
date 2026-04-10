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
        Schema::create('reemplazos_asignados', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('codigo_doc_ausente', 25);
            $table->string('codigo_doc_reemplazo', 25);
            $table->tinyInteger('hora');
            $table->string('curso', 10);
            $table->string('asignado_por', 50);
            $table->timestamps();
            // Un solo reemplazo por slot (fecha + ausente + hora + curso)
            $table->unique(['fecha', 'codigo_doc_ausente', 'hora', 'curso'], 'reemplazo_slot_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reemplazos_asignados');
    }
};
