<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Docente ICFES (externo) + su asignación.
 *
 * La materia ICFES (CODIGO_MAT=154) existe en HORARIOS para varios cursos de
 * bachillerato pero no tenía docente en ASIGNACION_PCM, así que el sistema no la
 * reconocía como un slot reemplazable. Este placeholder permite programar una
 * suplencia sobre esas clases desde "Horario por docente" igual que con cualquier
 * otro docente. ESTADO='EXTERNO' lo mantiene fuera de la asistencia diaria pero
 * visible en el selector de horarios.
 */
return new class extends Migration
{
    private const CODIGO = 'ICFES';
    private const MAT    = 154;

    public function up(): void
    {
        DB::table('CODIGOS_DOC')->updateOrInsert(
            ['CODIGO_EMP' => self::CODIGO],
            [
                'NOMBRE_DOC'     => 'Docente ICFES (externo)',
                'TIPO'           => 'DOCENTE',
                'ESTADO'         => 'EXTERNO',
                'FECHA_CREACION' => now(),
            ]
        );

        // Cursos reales que tienen la materia ICFES en el horario (no hardcodear).
        $cursos = DB::table('HORARIOS')
            ->where('CODIGO_MAT', self::MAT)
            ->distinct()
            ->pluck('CURSO');

        foreach ($cursos as $curso) {
            DB::table('ASIGNACION_PCM')->updateOrInsert(
                ['CODIGO_EMP' => self::CODIGO, 'CODIGO_MAT' => self::MAT, 'CURSO' => $curso],
                ['calificable' => 0]
            );
        }
    }

    public function down(): void
    {
        DB::table('ASIGNACION_PCM')
            ->where('CODIGO_EMP', self::CODIGO)
            ->where('CODIGO_MAT', self::MAT)
            ->delete();

        DB::table('CODIGOS_DOC')->where('CODIGO_EMP', self::CODIGO)->delete();
    }
};
