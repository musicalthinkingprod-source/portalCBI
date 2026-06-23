<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bitacora_categorias', function (Blueprint $table) {
            // Prioridad por defecto de la categoría: 'normal' | 'alta'.
            // Al registrar, precarga esta prioridad (el autor puede cambiarla).
            $table->string('prioridad', 10)->default('normal')->after('unica');
        });

        // Las observaciones de Consejo Académico y de Convivencia/Disciplina nacen como alta.
        DB::table('bitacora_categorias')
            ->whereIn('nombre', ['Consejo Académico', 'Convivencia / Disciplina'])
            ->update(['prioridad' => 'alta']);
    }

    public function down(): void
    {
        Schema::table('bitacora_categorias', function (Blueprint $table) {
            $table->dropColumn('prioridad');
        });
    }
};
