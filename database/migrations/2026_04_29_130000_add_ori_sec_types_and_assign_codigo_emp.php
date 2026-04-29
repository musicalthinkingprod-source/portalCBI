<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Nuevos tipos en el catalogo: ORI (Orientacion) y SEC (Secretaria)
        $now = now();
        DB::table('nomina_cat_tipos_empleado')->upsert([
            ['codigo' => 'ORI', 'nombre' => 'Orientacion',  'prefijo_codigo' => 'ORI', 'orden' => 5, 'activo' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'SEC', 'nombre' => 'Secretaria',   'prefijo_codigo' => 'SEC', 'orden' => 8, 'activo' => 1, 'created_at' => $now, 'updated_at' => $now],
        ], ['codigo'], ['nombre', 'prefijo_codigo', 'orden', 'activo', 'updated_at']);

        // 2) Asignar CODIGO_EMP a los 6 usuarios internos pendientes
        $asignaciones = [
            'claudia_duque'   => 'DOC016',
            'katherine_ortiz' => 'DOC020',
            'prueba'          => 'DOC048',
            'jimmy_perez'     => 'ORI001',
            'Andreamar'       => 'ORI002',
            'Paola S'         => 'SEC001',
        ];

        foreach ($asignaciones as $user => $codigo) {
            DB::table('PRINUSERS')->where('USER', $user)->update(['CODIGO_EMP' => $codigo]);
        }
    }

    public function down(): void
    {
        $usuarios = ['claudia_duque', 'katherine_ortiz', 'prueba', 'jimmy_perez', 'Andreamar', 'Paola S'];
        foreach ($usuarios as $user) {
            DB::table('PRINUSERS')->where('USER', $user)->update(['CODIGO_EMP' => null]);
        }

        DB::table('nomina_cat_tipos_empleado')->whereIn('codigo', ['ORI', 'SEC'])->delete();
    }
};
