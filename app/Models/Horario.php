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
        $rows = DB::table('HORARIOS as h')
            ->join('ASIGNACION_PCM as a', function ($join) use ($codigoDoc) {
                $join->on('a.CODIGO_MAT', '=', 'h.CODIGO_MAT')
                     ->on('a.CURSO', '=', 'h.CURSO')
                     ->where('a.CODIGO_DOC', $codigoDoc);
            })
            ->leftJoin('CODIGOSMAT as cm', 'cm.CODIGO_MAT', '=', 'h.CODIGO_MAT')
            ->select('h.DIA', 'h.HORA', 'h.CURSO', 'h.CODIGO_MAT', 'cm.NOMBRE_MAT')
            ->orderBy('h.HORA')
            ->orderBy('h.DIA')
            ->get();

        $grid = [];
        foreach ($rows as $row) {
            // Si hay varias asignaciones en el mismo bloque, agrupar
            $key = $row->HORA . '_' . $row->DIA;
            if (!isset($grid[$row->HORA][$row->DIA])) {
                $grid[$row->HORA][$row->DIA] = [];
            }
            $grid[$row->HORA][$row->DIA][] = [
                'curso'   => $row->CURSO,
                'materia' => $row->NOMBRE_MAT ?? '—',
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
            ->orderBy('CURSO')
            ->pluck('CURSO')
            ->toArray();
    }

    /**
     * Lista de docentes con asignaciones en HORARIOS.
     */
    public static function docentes(): array
    {
        return DB::table('CODIGOS_DOC as cd')
            ->join('ASIGNACION_PCM as a', 'a.CODIGO_DOC', '=', 'cd.CODIGO_DOC')
            ->join('HORARIOS as h', function ($join) {
                $join->on('h.CODIGO_MAT', '=', 'a.CODIGO_MAT')
                     ->on('h.CURSO', '=', 'a.CURSO');
            })
            ->where('cd.ESTADO', 'ACTIVO')
            ->select('cd.CODIGO_DOC', 'cd.NOMBRE_DOC')
            ->distinct()
            ->orderBy('cd.NOMBRE_DOC')
            ->get()
            ->toArray();
    }
}
