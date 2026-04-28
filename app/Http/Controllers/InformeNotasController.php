<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InformeNotasController extends Controller
{
    private const APROBADO_MIN = 3.0;

    private const AGRUPACIONES = [
        'estudiante' => ['label' => 'Estudiante',  'col' => 'CODIGO'],
        'curso'      => ['label' => 'Curso',       'col' => 'CURSO'],
        'grado'      => ['label' => 'Grado',       'col' => 'GRADO'],
        'sede'       => ['label' => 'Sede',        'col' => 'SEDE'],
        'docente'    => ['label' => 'Docente',     'col' => 'CODIGO_DOC'],
        'materia'    => ['label' => 'Asignatura',  'col' => 'CODIGO_MAT'],
        'area'       => ['label' => 'Área',        'col' => 'AREA_MAT'],
    ];

    private const METRICAS = [
        'promedio'  => 'Promedio',
        'aprobados' => '% Aprobados (≥ 3.0)',
        'min'       => 'Nota mínima',
        'max'       => 'Nota máxima',
        'desv'      => 'Desviación estándar',
        'cantidad'  => '# Notas',
    ];

    public function index(Request $request)
    {
        $anio = (int) date('Y');

        $filtros = [
            'sede'       => $request->input('sede'),
            'jornada'    => $request->input('jornada'),
            'grado'      => $request->input('grado'),
            'curso'      => $request->input('curso'),
            'docente'    => $request->input('docente'),
            'codigo_mat' => $request->input('codigo_mat'),
            'area'       => $request->input('area'),
            'periodo'    => $request->input('periodo', 'acum'),
            'g1'         => $request->input('g1', 'curso'),
            'g2'         => $request->input('g2', ''),
            'metrica'    => $request->input('metrica', 'promedio'),
            'orden'      => $request->input('orden', 'metrica_desc'),
        ];

        $opciones = $this->cargarOpciones($anio);

        $resultados = $this->ejecutar($anio, $filtros);

        return view('informes.notas', [
            'anio'         => $anio,
            'filtros'      => $filtros,
            'opciones'     => $opciones,
            'resultados'   => $resultados,
            'agrupaciones' => self::AGRUPACIONES,
            'metricas'     => self::METRICAS,
        ]);
    }

    private function cargarOpciones(int $anio): array
    {
        $sedes = DB::table('ESTUDIANTES')
            ->where('ESTADO', 'MATRICULADO')
            ->whereNotNull('SEDE')
            ->distinct()
            ->orderBy('SEDE')
            ->pluck('SEDE');

        $grados = DB::table('ESTUDIANTES')
            ->where('ESTADO', 'MATRICULADO')
            ->whereNotNull('GRADO')
            ->distinct()
            ->orderByRaw('CAST(GRADO AS SIGNED), GRADO')
            ->pluck('GRADO');

        $cursos = DB::table('ESTUDIANTES')
            ->where('ESTADO', 'MATRICULADO')
            ->whereNotNull('CURSO')
            ->distinct()
            ->orderBy('CURSO')
            ->pluck('CURSO');

        $materias = DB::table('CODIGOSMAT')
            ->orderBy('NOMBRE_MAT')
            ->get(['CODIGO_MAT', 'NOMBRE_MAT', 'AREA_MAT']);

        $areas = $materias->pluck('AREA_MAT')->filter()->unique()->sort()->values();

        $docentes = DB::table('CODIGOS_DOC')
            ->where('ESTADO', 'ACTIVO')
            ->orderBy('NOMBRE_DOC')
            ->get(['CODIGO_DOC', 'NOMBRE_DOC']);

        return compact('sedes', 'grados', 'cursos', 'materias', 'areas', 'docentes');
    }

    private function ejecutar(int $anio, array $f): array
    {
        $tablaNotas = "NOTAS_{$anio}";
        if (!Schema::hasTable($tablaNotas)) {
            return ['rows' => collect(), 'total' => 0, 'globalProm' => null, 'globalAprob' => null];
        }

        $q = DB::table($tablaNotas . ' as n')
            ->join('ESTUDIANTES as e', 'e.CODIGO', '=', 'n.CODIGO_ALUM')
            ->join('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'n.CODIGO_MAT')
            ->leftJoin('CODIGOS_DOC as d', 'd.CODIGO_DOC', '=', 'n.CODIGO_DOC')
            ->where('n.TIPODENOTA', 'N')
            ->where('e.ESTADO', 'MATRICULADO');

        if ($f['periodo'] !== 'acum') {
            $q->where('n.PERIODO', (int) $f['periodo']);
        }
        if ($f['sede'])        $q->where('e.SEDE', $f['sede']);
        if ($f['grado'])       $q->where('e.GRADO', $f['grado']);
        if ($f['curso'])       $q->where('e.CURSO', $f['curso']);
        if ($f['docente'])     $q->where('n.CODIGO_DOC', $f['docente']);
        if ($f['codigo_mat'])  $q->where('n.CODIGO_MAT', (int) $f['codigo_mat']);
        if ($f['area'])        $q->where('m.AREA_MAT', (int) $f['area']);

        $g1 = self::AGRUPACIONES[$f['g1']] ?? self::AGRUPACIONES['curso'];
        $g2 = !empty($f['g2']) ? (self::AGRUPACIONES[$f['g2']] ?? null) : null;
        if ($g2 && $f['g1'] === $f['g2']) $g2 = null;

        $selectGroup = [];
        $groupBy     = [];
        $orderGroup  = [];

        $this->aplicarColumnaGrupo($f['g1'], $selectGroup, $groupBy, $orderGroup, 'g1');
        if ($g2) $this->aplicarColumnaGrupo($f['g2'], $selectGroup, $groupBy, $orderGroup, 'g2');

        if ($f['periodo'] === 'acum') {
            $sub = clone $q;
            $sub->select(
                'n.CODIGO_ALUM',
                'n.CODIGO_MAT',
                'n.CODIGO_DOC',
                'e.CODIGO',
                'e.APELLIDO1', 'e.APELLIDO2', 'e.NOMBRE1', 'e.NOMBRE2',
                'e.GRADO', 'e.CURSO', 'e.SEDE',
                'm.NOMBRE_MAT', 'm.AREA_MAT',
                'd.NOMBRE_DOC',
                DB::raw('AVG(n.NOTA) as nota_efectiva')
            )->groupBy(
                'n.CODIGO_ALUM','n.CODIGO_MAT','n.CODIGO_DOC',
                'e.CODIGO','e.APELLIDO1','e.APELLIDO2','e.NOMBRE1','e.NOMBRE2',
                'e.GRADO','e.CURSO','e.SEDE',
                'm.NOMBRE_MAT','m.AREA_MAT','d.NOMBRE_DOC'
            );

            $base = DB::query()->fromSub($sub, 'b');
            $rows = $this->agregar($base, 'nota_efectiva', $selectGroup, $groupBy, $orderGroup, $f);
        } else {
            $q->addSelect(
                'n.CODIGO_ALUM',
                'n.CODIGO_MAT',
                'n.CODIGO_DOC',
                'e.CODIGO',
                'e.APELLIDO1', 'e.APELLIDO2', 'e.NOMBRE1', 'e.NOMBRE2',
                'e.GRADO', 'e.CURSO', 'e.SEDE',
                'm.NOMBRE_MAT', 'm.AREA_MAT',
                'd.NOMBRE_DOC',
                DB::raw('n.NOTA as nota_efectiva')
            );
            $base = DB::query()->fromSub($q, 'b');
            $rows = $this->agregar($base, 'nota_efectiva', $selectGroup, $groupBy, $orderGroup, $f);
        }

        $globalProm  = $rows->avg('promedio');
        $globalAprob = $rows->isNotEmpty() ? $rows->avg('aprobados_pct') : null;

        return [
            'rows'        => $rows,
            'total'       => $rows->count(),
            'globalProm'  => $globalProm,
            'globalAprob' => $globalAprob,
        ];
    }

    private function aplicarColumnaGrupo(string $key, array &$select, array &$group, array &$order, string $alias): void
    {
        switch ($key) {
            case 'estudiante':
                $select[] = DB::raw("b.CODIGO as {$alias}_codigo");
                $select[] = DB::raw("CONCAT_WS(' ', b.APELLIDO1, b.APELLIDO2, b.NOMBRE1, b.NOMBRE2) as {$alias}_label");
                $select[] = DB::raw("b.CURSO as {$alias}_extra");
                $group[]  = 'b.CODIGO';
                $group[]  = 'b.APELLIDO1';
                $group[]  = 'b.APELLIDO2';
                $group[]  = 'b.NOMBRE1';
                $group[]  = 'b.NOMBRE2';
                $group[]  = 'b.CURSO';
                $order[]  = 'b.APELLIDO1';
                break;
            case 'curso':
                $select[] = DB::raw("b.CURSO as {$alias}_codigo");
                $select[] = DB::raw("b.CURSO as {$alias}_label");
                $group[]  = 'b.CURSO';
                $order[]  = 'b.CURSO';
                break;
            case 'grado':
                $select[] = DB::raw("b.GRADO as {$alias}_codigo");
                $select[] = DB::raw("b.GRADO as {$alias}_label");
                $group[]  = 'b.GRADO';
                $order[]  = DB::raw('CAST(b.GRADO AS SIGNED)');
                break;
            case 'sede':
                $select[] = DB::raw("b.SEDE as {$alias}_codigo");
                $select[] = DB::raw("b.SEDE as {$alias}_label");
                $group[]  = 'b.SEDE';
                $order[]  = 'b.SEDE';
                break;
            case 'docente':
                $select[] = DB::raw("b.CODIGO_DOC as {$alias}_codigo");
                $select[] = DB::raw("COALESCE(b.NOMBRE_DOC, b.CODIGO_DOC) as {$alias}_label");
                $group[]  = 'b.CODIGO_DOC';
                $group[]  = 'b.NOMBRE_DOC';
                $order[]  = 'b.NOMBRE_DOC';
                break;
            case 'materia':
                $select[] = DB::raw("b.CODIGO_MAT as {$alias}_codigo");
                $select[] = DB::raw("b.NOMBRE_MAT as {$alias}_label");
                $group[]  = 'b.CODIGO_MAT';
                $group[]  = 'b.NOMBRE_MAT';
                $order[]  = 'b.NOMBRE_MAT';
                break;
            case 'area':
                $select[] = DB::raw("b.AREA_MAT as {$alias}_codigo");
                $select[] = DB::raw("b.AREA_MAT as {$alias}_label");
                $group[]  = 'b.AREA_MAT';
                $order[]  = 'b.AREA_MAT';
                break;
        }
    }

    private function agregar($base, string $colNota, array $select, array $group, array $order, array $f)
    {
        $aprobMin = self::APROBADO_MIN;

        $base->select(array_merge($select, [
            DB::raw("ROUND(AVG(b.{$colNota}), 2) as promedio"),
            DB::raw("ROUND(MIN(b.{$colNota}), 2) as nota_min"),
            DB::raw("ROUND(MAX(b.{$colNota}), 2) as nota_max"),
            DB::raw("ROUND(STDDEV_SAMP(b.{$colNota}), 2) as desv"),
            DB::raw("COUNT(*) as cantidad"),
            DB::raw("ROUND(100 * SUM(CASE WHEN b.{$colNota} >= {$aprobMin} THEN 1 ELSE 0 END) / COUNT(*), 1) as aprobados_pct"),
        ]));

        foreach ($group as $g) $base->groupBy($g);

        $colMetrica = match ($f['metrica']) {
            'aprobados' => 'aprobados_pct',
            'min'       => 'nota_min',
            'max'       => 'nota_max',
            'desv'      => 'desv',
            'cantidad'  => 'cantidad',
            default     => 'promedio',
        };

        switch ($f['orden']) {
            case 'metrica_asc':
                $base->orderBy($colMetrica, 'asc');
                break;
            case 'metrica_desc':
                $base->orderBy($colMetrica, 'desc');
                break;
            case 'grupo_asc':
                foreach ($order as $o) $base->orderBy($o, 'asc');
                break;
            case 'grupo_desc':
                foreach ($order as $o) $base->orderBy($o, 'desc');
                break;
            default:
                $base->orderBy($colMetrica, 'desc');
        }

        return $base->get();
    }
}
