<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Eliminar la columna codigo_doc legacy (numeros como 7666, 8226 que no
        // corresponden a los DOC### del portal y no aparecen en archivos fuente)
        if (Schema::hasColumn('nomina_empleados', 'codigo_doc')) {
            Schema::table('nomina_empleados', function (Blueprint $table) {
                $table->dropColumn('codigo_doc');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('nomina_empleados', 'codigo_doc')) {
            Schema::table('nomina_empleados', function (Blueprint $table) {
                $table->string('codigo_doc', 25)->nullable()->after('tel_emergencia');
            });
        }
    }
};
