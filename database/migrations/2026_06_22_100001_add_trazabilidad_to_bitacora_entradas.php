<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bitacora_entradas', function (Blueprint $table) {
            // Contexto relacional: período académico (1..4) al que pertenece la anotación.
            // Obligatorio para registros de aula (docente); nulo en alcance institucional.
            $table->unsignedTinyInteger('periodo')->nullable()->after('anio');

            // Severidad de la anotación: 'normal' | 'alta'.
            $table->string('prioridad', 10)->default('normal')->after('observacion');

            // Acuse de recibo del acudiente. Mientras esté nulo, la anotación es editable;
            // una vez con valor (la familia confirmó la lectura) el contenido es inmutable.
            $table->timestamp('acknowledged_at')->nullable()->after('prioridad');
        });
    }

    public function down(): void
    {
        Schema::table('bitacora_entradas', function (Blueprint $table) {
            $table->dropColumn(['periodo', 'prioridad', 'acknowledged_at']);
        });
    }
};
