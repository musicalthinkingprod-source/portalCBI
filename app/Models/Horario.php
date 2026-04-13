<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Horario extends Model
{
    protected $table = 'HORARIOS';
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = ['CURSO', 'DIA', 'HORA', 'CODIGO_MAT'];

    // Ciclo de 6 días hábiles rotativos
    public static array $dias = [
        1 => 'Día 1',
        2 => 'Día 2',
        3 => 'Día 3',
        4 => 'Día 4',
        5 => 'Día 5',
        6 => 'Día 6',
    ];

    // Horas de clase (se pueden ajustar con horarios reales)
    public static array $horas = [
        1 => '1ª hora',
        2 => '2ª hora',
        3 => '3ª hora',
        4 => '4ª hora',
        5 => '5ª hora',
        6 => '6ª hora',
        7 => '7ª hora',
        8 => '8ª hora',
    ];

    /**
     * Grilla de horario para un curso.
     * Retorna array[hora][dia] = ['materia' => ..., 'docente' => ...]
     */
    public static function gridPorCurso(string $curso): array
    {
        $rows = DB::table('HORARIOS as h')
            ->leftJoin('CODIGOSMAT as cm', 'cm.CODIGO_MAT', '=', 'h.CODIGO_MAT')
            ->leftJoin('ASIGNACION_PCM as a', function ($join) use ($curso) {
                $join->on('a.CODIGO_MAT', '=', 'h.CODIGO_MAT')
                     ->where('a.CURSO', $curso);
            })
            ->leftJoin('CODIGOS_DOC as cd', 'cd.CODIGO_DOC', '=', 'a.CODIGO_DOC')
            ->where('h.CURSO', $curso)
            ->select('h.DIA', 'h.HORA', 'h.CODIGO_MAT', 'cm.NOMBRE_MAT', 'cd.NOMBRE_DOC', 'cd.CODIGO_DOC')
            ->orderBy('h.HORA')
            ->orderBy('h.DIA')
            ->get();

        $grid = [];
        foreach ($rows as $row) {
            $grid[$row->HORA][$row->DIA] = [
                'codigo_mat' => $row->CODIGO_MAT,
                'materia'    => $row->NOMBRE_MAT ?? '—',
                'docente'    => $row->NOMBRE_DOC ?? null,
                'codigo_doc' => $row->CODIGO_DOC ?? null,
            ];
        }
        return $grid;
    }

    /**
     * Grilla de horario para un docente.
     * Retorna array[hora][dia] = ['materia' => ..., 'curso' => ...]
     */
    public static function gridPorDocente(string $codigoDoc): array
    {
        // Materias regulares.
        // Excluye Proyecto (CODIGO_MAT=31) que se trata por separado.
        // Usa SUBSTRING_INDEX para manejar subgrupos como '7A-1' → se comparan
        // contra el curso base '7A' en HORARIOS, y se muestra el subgrupo real.
        $rows = DB::table('HORARIOS as h')
            ->join('ASIGNACION_PCM as a', function ($join) use ($codigoDoc) {
                $join->on('a.CODIGO_MAT', '=', 'h.CODIGO_MAT')
                     ->on(DB::raw("SUBSTRING_INDEX(a.CURSO, '-', 1)"), '=', 'h.CURSO')
                     ->where('a.CODIGO_DOC', $codigoDoc);
            })
            ->leftJoin('CODIGOSMAT as cm', 'cm.CODIGO_MAT', '=', 'h.CODIGO_MAT')
            ->whereNotIn('h.CODIGO_MAT', [31, 200])
            ->select('h.DIA', 'h.HORA', 'a.CURSO', 'h.CODIGO_MAT', 'cm.NOMBRE_MAT')
            ->orderBy('h.HORA')
            ->orderBy('h.DIA')
            ->get();

        $grid = [];
        foreach ($rows as $row) {
            if (!isset($grid[$row->HORA][$row->DIA])) {
                $grid[$row->HORA][$row->DIA] = [];
            }
            $grid[$row->HORA][$row->DIA][] = [
                'curso'   => $row->CURSO,
                'materia' => $row->NOMBRE_MAT ?? '—',
            ];
        }

        // Artes (25) y Música (26) en bachillerato: en HORARIOS aparecen como
        // CODIGO_MAT=70 ('Expresión Artística'), no como 25 ni 26. Se buscan
        // los slots de CODIGO_MAT=70 para los cursos asignados al docente.
        $asigArtesMusica = DB::table('ASIGNACION_PCM')
            ->whereIn('CODIGO_MAT', [25, 26])
            ->where('CODIGO_DOC', $codigoDoc)
            ->get(['CODIGO_MAT', 'CURSO']);

        // Pre-cargar nombres para evitar N+1
        $nombresAM = DB::table('CODIGOSMAT')
            ->whereIn('CODIGO_MAT', [25, 26])
            ->pluck('NOMBRE_MAT', 'CODIGO_MAT');

        // Pre-cargar todos los slots de CODIGO_MAT=70 para los cursos base del docente
        $cursosBaseAM = $asigArtesMusica->map(fn($a) => explode('-', $a->CURSO)[0])->unique()->values()->toArray();
        $slotsAM70 = !empty($cursosBaseAM)
            ? DB::table('HORARIOS')->where('CODIGO_MAT', 70)
                ->whereIn('CURSO', $cursosBaseAM)
                ->get(['CURSO', 'DIA', 'HORA'])
                ->groupBy('CURSO')
            : collect();

        foreach ($asigArtesMusica as $asig) {
            $cursoBase = explode('-', $asig->CURSO)[0]; // '7A-1' → '7A'
            $nombreMat = $nombresAM[$asig->CODIGO_MAT] ?? '—';

            $slots = $slotsAM70->get($cursoBase, collect());

            foreach ($slots as $slot) {
                if (!isset($grid[$slot->HORA][$slot->DIA])) {
                    $grid[$slot->HORA][$slot->DIA] = [];
                }
                // Evitar duplicado si ya existe la misma combinación
                $yaExiste = collect($grid[$slot->HORA][$slot->DIA])
                    ->contains(fn($c) => $c['curso'] === $asig->CURSO && $c['materia'] === $nombreMat);
                if (!$yaExiste) {
                    $grid[$slot->HORA][$slot->DIA][] = [
                        'curso'   => $asig->CURSO,
                        'materia' => $nombreMat,
                    ];
                }
            }
        }

        // Proyecto (CODIGO_MAT=31): la asignación usa el nombre del grupo (GP1, GP2…)
        // como CURSO en ASIGNACION_PCM, así que se trata por separado.
        $grupoProyecto = DB::table('ASIGNACION_PCM')
            ->where('CODIGO_DOC', $codigoDoc)
            ->where('CODIGO_MAT', 31)
            ->value('CURSO'); // p.ej. 'GP1'

        if ($grupoProyecto) {
            $slots = DB::table('HORARIOS')
                ->where('CODIGO_MAT', 31)
                ->select('DIA', 'HORA')
                ->distinct()
                ->orderBy('HORA')
                ->orderBy('DIA')
                ->get();

            foreach ($slots as $slot) {
                if (!isset($grid[$slot->HORA][$slot->DIA])) {
                    $grid[$slot->HORA][$slot->DIA] = [];
                }
                $grid[$slot->HORA][$slot->DIA][] = [
                    'curso'   => $grupoProyecto,
                    'materia' => 'Proyecto',
                ];
            }
        }

        // Atención a Padres (CODIGO_MAT=200, no calificable).
        // Los slots se almacenan en HORARIOS con CURSO=CODIGO_DOC para que
        // cada docente tenga sus propios bloques sin interferir con otros.
        $slotsAP = DB::table('HORARIOS')
            ->where('CODIGO_MAT', 200)
            ->where('CURSO', $codigoDoc)
            ->orderBy('HORA')
            ->orderBy('DIA')
            ->get(['DIA', 'HORA']);

        foreach ($slotsAP as $slot) {
            if (!isset($grid[$slot->HORA][$slot->DIA])) {
                $grid[$slot->HORA][$slot->DIA] = [];
            }
            $grid[$slot->HORA][$slot->DIA][] = [
                'curso'   => 'Padres',
                'materia' => 'Atención a Padres',
            ];
        }

        return $grid;
    }

    /**
     * Retorna las fechas del calendario para cada día del ciclo en un año dado.
     * Resultado: array[dia_ciclo] = [fecha1, fecha2, ...]  (Carbon instances)
     */
    public static function fechasPorCiclo(?int $anio = null): array
    {
        $anio ??= now()->year;

        $rows = DB::table('calendario_academico')
            ->where('anio', $anio)
            ->orderBy('fecha')
            ->get(['fecha', 'dia_ciclo']);

        $map = [];
        foreach ($rows as $row) {
            $map[$row->dia_ciclo][] = \Carbon\Carbon::parse($row->fecha);
        }
        return $map;
    }

    /**
     * Retorna el día del ciclo correspondiente a hoy (o null si no está en el calendario).
     */
    public static function diaCicloHoy(): ?int
    {
        $row = DB::table('calendario_academico')->where('fecha', today()->toDateString())->first();
        return $row?->dia_ciclo;
    }

    /**
     * Lista de cursos disponibles en HORARIOS.
     */
    public static function cursos(): array
    {
        return DB::table('HORARIOS')
            ->select('CURSO')
            ->distinct()
            ->whereRaw("CURSO NOT REGEXP '^DOC|^GP|^VOC'")
            ->orderBy('CURSO')
            ->pluck('CURSO')
            ->toArray();
    }

    /**
     * Lista de docentes con al menos una asignación (ASIGNACION_PCM).
     * No se filtra por ESTADO ni por coincidencia en HORARIOS, para que
     * aparezcan también inactivos y docentes con listados especiales.
     */
    public static function docentes(): array
    {
        return DB::table('CODIGOS_DOC as cd')
            ->join('ASIGNACION_PCM as a', 'a.CODIGO_DOC', '=', 'cd.CODIGO_DOC')
            ->select('cd.CODIGO_DOC', 'cd.NOMBRE_DOC', 'cd.ESTADO')
            ->distinct()
            ->orderBy('cd.NOMBRE_DOC')
            ->get()
            ->toArray();
    }
}
