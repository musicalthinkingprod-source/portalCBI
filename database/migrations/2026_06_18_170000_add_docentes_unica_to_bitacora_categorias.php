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
            $table->boolean('docentes')->default(false)->after('color'); // ¿la pueden usar los docentes?
            $table->boolean('unica')->default(false)->after('docentes');  // un solo registro por estudiante+fecha
        });

        // Consejo Académico no se puede repetir
        DB::table('bitacora_categorias')->where('nombre', 'Consejo Académico')->update(['unica' => 1]);

        // Categoría exclusiva para docentes (si no existe ya)
        if (!DB::table('bitacora_categorias')->where('nombre', 'Observación de aula (docente)')->exists()) {
            DB::table('bitacora_categorias')->insert([
                'nombre'   => 'Observación de aula (docente)',
                'ambito'   => 'academico',
                'color'    => 'gray',
                'activo'   => 1,
                'docentes' => 1,
                'unica'    => 0,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('bitacora_categorias', function (Blueprint $table) {
            $table->dropColumn(['docentes', 'unica']);
        });
    }
};
