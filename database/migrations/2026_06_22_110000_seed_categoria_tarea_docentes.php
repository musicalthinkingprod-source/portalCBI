<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Categoría para que los docentes dejen tareas/asignaciones a un curso completo.
        if (!DB::table('bitacora_categorias')->where('nombre', 'Tarea')->exists()) {
            DB::table('bitacora_categorias')->insert([
                'nombre'    => 'Tarea',
                'ambito'    => 'academico',
                'color'     => 'indigo',
                'docentes'  => 1,
                'unica'     => 0,
                'prioridad' => 'normal',
                'activo'    => 1,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('bitacora_categorias')
            ->where('nombre', 'Tarea')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('bitacora_entradas')
                  ->whereColumn('bitacora_entradas.categoria_id', 'bitacora_categorias.id');
            })
            ->delete();
    }
};
