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

    // Rangos horarios reales de cada bloque (fuente única para horarios y recuperaciones).
    public static array $horasRangos = [
        1 => '7:00 – 7:45',
        2 => '7:45 – 8:30',
        3 => '8:50 – 9:35',
        4 => '9:35 – 10:20',
        5 => '10:20 – 11:05',
        6 => '11:05 – 11:50',
        7 => '12:15 – 1:00',
        8 => '1:00 – 1:45',
    ];

    // Descansos reales, indexados por la hora DESPUÉS de la cual ocurren.
    // Fuente única para todas las vistas de horario.
    public static array $descansos = [
        2 => '8:30 – 8:50',
        6 => '11:50 – 12:15',
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
            ->leftJoin('CODIGOS_DOC as cd', 'cd.CODIGO_EMP', '=', 'a.CODIGO_EMP')
            ->where('h.CURSO', $curso)
            ->select('h.DIA', 'h.HORA', 'h.CODIGO_MAT', 'cm.NOMBRE_MAT', 'cd.NOMBRE_DOC', 'cd.CODIGO_EMP')
            ->orderBy('h.HORA')
            ->orderBy('h.DIA')
            ->get();

        $grid = [];
        foreach ($rows as $row) {
            $grid[$row->HORA][$row->DIA] = [
                'codigo_mat' => $row->CODIGO_MAT,
                'materia'    => $row->NOMBRE_MAT ?? '—',
                'docente'    => $row->NOMBRE_DOC ?? null,
                'codigo_emp' => $row->CODIGO_EMP ?? null,
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
                     ->where('a.CODIGO_EMP', $codigoDoc);
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
            ->where('CODIGO_EMP', $codigoDoc)
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
            ->where('CODIGO_EMP', $codigoDoc)
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
                // Solo añadir Proyecto si el docente no tiene ya otra clase en ese slot.
                // Si tiene clase regular a D1H5 y Proyecto a D1H6, solo se muestra D1H6.
                if (empty($grid[$slot->HORA][$slot->DIA] ?? [])) {
                    $grid[$slot->HORA][$slot->DIA][] = [
                        'curso'   => $grupoProyecto,
                        'materia' => 'Proyecto',
                    ];
                }
            }
        }

        // Atención a Padres (CODIGO_MAT=200, no calificable).
        // Los slots se almacenan en HORARIOS con CURSO=CODIGO_EMP para que
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
     * Docentes ocupados en cada slot del ciclo.
     * Resultado: array[dia_ciclo][hora] = [CODIGO_EMP, ...] (arrays secuenciales).
     *
     * Contempla clases regulares, Artes/Música (25/26 que en HORARIOS figuran como 70),
     * subgrupos (7A-1), bloques DOC* (Atención a Padres) y Proyecto (GP*).
     * Fuente única usada por el modal de reemplazo y la programación de reuniones.
     */
    public static function ocupacionPorSlot(): array
    {
        $ocupados = [];

        // 1. Clases regulares + Artes/Música (25/26→70) + subgrupos + VOC*
        DB::table('HORARIOS as h')
            ->join('ASIGNACION_PCM as a', function ($join) {
                $join->where(function ($q) {
                         $q->whereColumn('a.CODIGO_MAT', 'h.CODIGO_MAT')
                           ->orWhereRaw('(a.CODIGO_MAT IN (25,26) AND h.CODIGO_MAT = 70)');
                     })
                     ->where(function ($q) {
                         $q->whereColumn('a.CURSO', 'h.CURSO')
                           ->orWhereRaw("a.CURSO LIKE CONCAT(h.CURSO, '-%')");
                     });
            })
            ->whereRaw("h.CURSO NOT LIKE 'DOC%'")
            ->select('h.DIA', 'h.HORA', 'a.CODIGO_EMP')
            ->distinct()
            ->get()
            ->each(function ($row) use (&$ocupados) {
                $ocupados[$row->DIA][$row->HORA][] = $row->CODIGO_EMP;
            });

        // 2. Slots DOC-prefixed (Atención a Padres, etc.)
        DB::table('HORARIOS')
            ->whereRaw("CURSO LIKE 'DOC%'")
            ->where('CODIGO_MAT', '!=', 0)
            ->select('DIA', 'HORA', DB::raw('CURSO as CODIGO_EMP'))
            ->get()
            ->each(function ($row) use (&$ocupados) {
                $ocupados[$row->DIA][$row->HORA][] = $row->CODIGO_EMP;
            });

        // 3. Proyecto (GP*): ocupado en cualquier slot de CODIGO_MAT=31
        DB::table('HORARIOS as h')
            ->join('ASIGNACION_PCM as a', 'a.CODIGO_MAT', '=', 'h.CODIGO_MAT')
            ->whereRaw("a.CURSO LIKE 'GP%'")
            ->whereRaw("h.CURSO NOT LIKE 'DOC%'")
            ->whereRaw("h.CURSO NOT LIKE 'GP%'")
            ->select('h.DIA', 'h.HORA', 'a.CODIGO_EMP')
            ->distinct()
            ->get()
            ->each(function ($row) use (&$ocupados) {
                $ocupados[$row->DIA][$row->HORA][] = $row->CODIGO_EMP;
            });

        // Deduplicar. array_values() reindexa para que @json serialice arrays JS reales.
        foreach ($ocupados as $dia => $horas) {
            foreach ($horas as $hora => $docs) {
                $ocupados[$dia][$hora] = array_values(array_unique($docs));
            }
        }

        return $ocupados;
    }

    /**
     * Detalle de qué clase tiene cada docente ocupado en cada slot.
     * Resultado: array[dia_ciclo][hora][CODIGO_EMP] = ['Materia · Curso', ...].
     * Complementa ocupacionPorSlot() para poder mostrar el motivo del conflicto.
     */
    public static function detalleOcupacionPorSlot(): array
    {
        $det = [];
        $push = function ($dia, $hora, $cod, $label) use (&$det) {
            $label = trim($label);
            if ($label === '') return;
            $actual = $det[$dia][$hora][$cod] ?? [];
            if (!in_array($label, $actual, true)) {
                $actual[] = $label;
            }
            $det[$dia][$hora][$cod] = $actual;
        };

        // 1. Clases regulares + Artes/Música (25/26→70) + subgrupos + VOC*
        DB::table('HORARIOS as h')
            ->join('ASIGNACION_PCM as a', function ($join) {
                $join->where(function ($q) {
                         $q->whereColumn('a.CODIGO_MAT', 'h.CODIGO_MAT')
                           ->orWhereRaw('(a.CODIGO_MAT IN (25,26) AND h.CODIGO_MAT = 70)');
                     })
                     ->where(function ($q) {
                         $q->whereColumn('a.CURSO', 'h.CURSO')
                           ->orWhereRaw("a.CURSO LIKE CONCAT(h.CURSO, '-%')");
                     });
            })
            ->leftJoin('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'a.CODIGO_MAT')
            ->whereRaw("h.CURSO NOT LIKE 'DOC%'")
            ->select('h.DIA', 'h.HORA', 'a.CODIGO_EMP', 'a.CURSO', 'm.NOMBRE_MAT')
            ->distinct()
            ->get()
            ->each(function ($r) use ($push) {
                $push($r->DIA, $r->HORA, $r->CODIGO_EMP, ($r->NOMBRE_MAT ?? 'Clase') . ' · ' . $r->CURSO);
            });

        // 2. Slots DOC-prefixed (Atención a Padres, etc.)
        DB::table('HORARIOS as h')
            ->leftJoin('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'h.CODIGO_MAT')
            ->whereRaw("h.CURSO LIKE 'DOC%'")
            ->where('h.CODIGO_MAT', '!=', 0)
            ->select('h.DIA', 'h.HORA', DB::raw('h.CURSO as CODIGO_EMP'), 'm.NOMBRE_MAT')
            ->get()
            ->each(function ($r) use ($push) {
                $push($r->DIA, $r->HORA, $r->CODIGO_EMP, $r->NOMBRE_MAT ?? 'Atención a Padres');
            });

        // 3. Proyecto (GP*)
        DB::table('HORARIOS as h')
            ->join('ASIGNACION_PCM as a', 'a.CODIGO_MAT', '=', 'h.CODIGO_MAT')
            ->leftJoin('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'a.CODIGO_MAT')
            ->whereRaw("a.CURSO LIKE 'GP%'")
            ->whereRaw("h.CURSO NOT LIKE 'DOC%'")
            ->whereRaw("h.CURSO NOT LIKE 'GP%'")
            ->select('h.DIA', 'h.HORA', 'a.CODIGO_EMP', 'a.CURSO', 'm.NOMBRE_MAT')
            ->distinct()
            ->get()
            ->each(function ($r) use ($push) {
                $push($r->DIA, $r->HORA, $r->CODIGO_EMP, ($r->NOMBRE_MAT ?? 'Proyecto') . ' · ' . $r->CURSO);
            });

        return $det;
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
            ->join('ASIGNACION_PCM as a', 'a.CODIGO_EMP', '=', 'cd.CODIGO_EMP')
            ->select('cd.CODIGO_EMP', 'cd.NOMBRE_DOC', 'cd.ESTADO')
            ->distinct()
            ->orderBy('cd.NOMBRE_DOC')
            ->get()
            ->toArray();
    }
}
