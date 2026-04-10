<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Renombrar tabla si aún existe con el nombre antiguo
        if (Schema::hasTable('calendario_vigilancias') && ! Schema::hasTable('calendario_academico')) {
            Schema::rename('calendario_vigilancias', 'calendario_academico');
        }

        // Agregar columna evento si no existe
        if (Schema::hasTable('calendario_academico') && ! Schema::hasColumn('calendario_academico', 'evento')) {
            Schema::table('calendario_academico', function (Blueprint $table) {
                $table->string('evento', 200)->nullable()->after('anio');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('calendario_academico')) {
            Schema::table('calendario_academico', function (Blueprint $table) {
                $table->dropColumn('evento');
            });

            if (! Schema::hasTable('calendario_vigilancias')) {
                Schema::rename('calendario_academico', 'calendario_vigilancias');
            }
        }
    }
};
