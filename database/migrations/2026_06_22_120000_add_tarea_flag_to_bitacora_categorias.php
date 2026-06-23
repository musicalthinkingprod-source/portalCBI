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
            // Marca las categorías que se usan en la pantalla de "Tareas" del docente
            // (asignación a un curso/grupo completo con un solo texto compartido).
            $table->boolean('tarea')->default(false)->after('unica');
        });

        DB::table('bitacora_categorias')->where('nombre', 'Tarea')->update(['tarea' => 1]);
    }

    public function down(): void
    {
        Schema::table('bitacora_categorias', function (Blueprint $table) {
            $table->dropColumn('tarea');
        });
    }
};
