<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planilla_columnas_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('columna_id')->constrained('planilla_columnas')->onDelete('cascade');
            $table->string('nombre_anterior', 100);
            $table->string('nombre_nuevo', 100);
            $table->string('codigo_doc', 20)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('columna_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planilla_columnas_historial');
    }
};
