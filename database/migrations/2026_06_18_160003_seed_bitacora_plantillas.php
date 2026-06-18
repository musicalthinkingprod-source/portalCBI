<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Solo sembrar si la tabla está vacía (no duplicar en re-ejecuciones).
        if (DB::table('bitacora_plantillas')->count() > 0) {
            return;
        }

        // Plantillas genéricas globales (categoria_id = null → disponibles en cualquier categoría).
        $textos = [
            'El estudiante presentó un excelente desempeño académico y comportamental durante este período. ¡Felicitaciones!',
            'El estudiante mostró un buen desempeño general durante este período.',
            'El estudiante presentó un desempeño medio; se sugiere reforzar hábitos de estudio y compromiso desde casa.',
            'El estudiante requiere mayor compromiso académico y acompañamiento permanente del acudiente.',
            'Se cita al acudiente para tratar asuntos relacionados con el proceso del estudiante.',
        ];

        DB::table('bitacora_plantillas')->insert(array_map(
            fn($texto) => ['categoria_id' => null, 'texto' => $texto, 'activo' => 1],
            $textos
        ));
    }

    public function down(): void
    {
        // No se eliminan datos sembrados al revertir.
    }
};
