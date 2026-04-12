<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Crea la materia "Atención a Padres" (CODIGO_MAT=200, no calificable)
 * y asigna los bloques de horario a cada docente activo según el Excel
 * "ATENCIÓN A PADRES DE FAMILIA CBI (1).xlsx".
 *
 * Estrategia HORARIOS:
 *   – CURSO = CODIGO_DOC del docente (ej. 'DOC025').
 *     Esto garantiza que cada docente tenga sus propios slots sin colisionar
 *     con los de otros, y que el JOIN en Horario::gridPorDocente funcione.
 *   – CODIGO_MAT = 200.
 *
 * gridPorDocente fue modificado para excluir 200 de la consulta principal
 * y procesarlo en un bloque especial (muestra 'Padres' como etiqueta de curso).
 *
 * Docentes omitidos (INACTIVO / RETIRADO / sin código en BD):
 *   – DOC047 David Amezquita (INACTIVO)
 *   – DOC006 Alejandra Noguera (RETIRADO)
 *   – Juan Fernando Rozo Avendaño (no existe en BD)
 *   – DOC045 Lady Carolina Granados (sin horario definido en el Excel)
 *   – Secretarias y administración (sin bloques DÍA asignados)
 */
return new class extends Migration
{
    /** Mapeo hora textual → número de hora en HORARIOS */
    private array $horaMap = [
        '7:00'  => 1,  // 7:00 – 7:45
        '7:45'  => 2,  // 7:45 – 8:30
        '8:50'  => 3,  // 8:50 – 9:35
        '9:35'  => 4,  // 9:35 – 10:20
        '10:20' => 5,  // 10:20 – 11:05
        '11:05' => 6,  // 11:05 – 11:50
        '12:15' => 7,  // 12:15 – 1:00
        '1:00'  => 8,  // 1:00 – 1:45
    ];

    /**
     * Horarios por docente: [CODIGO_DOC => [[DIA, HORA], ...]]
     *
     * Mapping de horas según el Excel (franjas de 45 min):
     *   1 = 7:00-7:45  | 2 = 7:45-8:30 | 3 = 8:50-9:35  | 4 = 9:35-10:20
     *   5 = 10:20-11:05| 6 = 11:05-11:50| 7 = 12:15-1:00 | 8 = 1:00-1:45
     */
    private array $horarios = [
        // ── SEDE A ────────────────────────────────────────────────────────
        'DOC025' => [[4,4],[6,3]],   // Jose Penagos
        'DOC021' => [[2,5],[4,2]],   // Estefanía Perez
        'DOC020' => [[5,6],[6,8]],   // Katerín Ortiz
        'DOC050' => [[2,7],[3,7]],   // Kelly Chacón
        'DOC015' => [[1,8]],         // Maria Saucedo
        'DOC054' => [[1,6],[6,1]],   // Lina Ducuara
        'DOC048' => [[1,7],[3,7]],   // Fabián Calderón
        'DOC046' => [[2,8]],         // Alexander González
        'DOC049' => [[2,4],[4,5]],   // Juan Diego Figueroa
        'DOC019' => [[3,1],[5,1],[6,8]], // Marco González
        'DOC012' => [[5,7],[6,7]],   // Hernán Tapias
        'DOC023' => [[2,2],[3,2]],   // Edward Contreras
        'DOC024' => [[1,1],[5,8]],   // Lina Mendoza
        // ── SEDE B ────────────────────────────────────────────────────────
        'DOC001' => [[1,2],[3,1]],   // Angie González Suarez
        'DOC002' => [[4,3],[6,3]],   // Yuly Martínez
        'DOC003' => [[2,6],[5,7]],   // Irma León
        'DOC010' => [[2,8],[6,7]],   // Luisa Torres
        // ── SEDE C ────────────────────────────────────────────────────────
        'DOC005' => [[2,1],[3,3]],   // Lisseth Martínez
        'DOC007' => [[2,8],[4,1]],   // Luz Martínez
        'DOC009' => [[4,7],[5,3]],   // Guillermo Pinto
        'DOC011' => [[2,3],[4,1],[5,4]], // Roxana Echeverry
    ];

    public function up(): void
    {
        // 1. Materia nueva ────────────────────────────────────────────────
        DB::table('CODIGOSMAT')->insert([
            'CODIGO_MAT' => 200,
            'NOMBRE_MAT' => 'Atención a Padres',
            'AREA_MAT'   => null,
        ]);

        // 2. Asignación PCM + bloques de horario por docente ──────────────
        foreach ($this->horarios as $codigoDoc => $slots) {
            // Registro en ASIGNACION_PCM (calificable=0 → no lleva nota)
            DB::table('ASIGNACION_PCM')->insert([
                'CODIGO_DOC' => $codigoDoc,
                'CODIGO_MAT' => 200,
                'CURSO'      => 'PADRE',
                'IHS'        => null,
                'calificable'=> 0,
            ]);

            // Slots en HORARIOS (CURSO = CODIGO_DOC para identificar al docente)
            foreach ($slots as [$dia, $hora]) {
                DB::table('HORARIOS')->insert([
                    'CURSO'      => $codigoDoc,
                    'DIA'        => $dia,
                    'HORA'       => $hora,
                    'CODIGO_MAT' => 200,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Eliminar slots de HORARIOS para CODIGO_MAT=200
        DB::table('HORARIOS')->where('CODIGO_MAT', 200)->delete();

        // Eliminar asignaciones PCM de CODIGO_MAT=200
        DB::table('ASIGNACION_PCM')->where('CODIGO_MAT', 200)->delete();

        // Eliminar la materia
        DB::table('CODIGOSMAT')->where('CODIGO_MAT', 200)->delete();
    }
};
