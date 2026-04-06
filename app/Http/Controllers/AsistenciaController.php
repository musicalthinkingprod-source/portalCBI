<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AsistenciaController extends Controller
{
    // ─── Registro (solo secretarias) ────────────────────────────────────────

    public function registro(Request $request)
    {
        $cursos = DB::table('ESTUDIANTES')
            ->where('ESTADO', 'MATRICULADO')
            ->distinct()->orderBy('CURSO')->pluck('CURSO');

        $cursoSelec = $request->input('curso');
        $fecha      = $request->input('fecha', today()->format('Y-m-d'));

        $esFinde = Carbon::parse($fecha)->isWeekend();

        $estudiantes = collect();
        $existentes  = [];

        if ($cursoSelec && !$esFinde) {
            $estudiantes = DB::table('ESTUDIANTES')
                ->where('CURSO', $cursoSelec)
                ->where('ESTADO', 'MATRICULADO')
                ->orderBy('APELLIDO1')->orderBy('APELLIDO2')->orderBy('NOMBRE1')
                ->get();

            $registros = DB::table('ASISTENCIA')
                ->where('FECHA', $fecha)
                ->whereIn('CODIGO', $estudiantes->pluck('CODIGO')->toArray())
                ->get()
                ->keyBy('CODIGO');

            foreach ($registros as $codigo => $r) {
                $existentes[$codigo] = $r;
            }
        }

        return view('asistencia.registro', compact(
            'cursos', 'cursoSelec', 'fecha', 'estudiantes', 'existentes', 'esFinde'
        ));
    }

    public function guardar(Request $request)
    {
        $fecha      = $request->input('fecha');
        $estudiantes = $request->input('asistencia', []);

        foreach ($estudiantes as $codigo => $datos) {
            DB::table('ASISTENCIA')->updateOrInsert(
                ['CODIGO' => $codigo, 'FECHA' => $fecha],
                [
                    'ASISTENCIA'   => $datos['estado']       ?? 'P',
                    'CARNET'       => isset($datos['carnet'])       ? 1 : 0,
                    'UNIFORME'     => isset($datos['uniforme'])     ? 1 : 0,
                    'RETARDO'      => isset($datos['retardo'])      ? 1 : 0,
                    'PRESENTACION' => isset($datos['presentacion']) ? 1 : 0,
                ]
            );
        }

        return back()->with('success', 'Asistencia guardada correctamente.');
    }

    // ─── Vista padres ───────────────────────────────────────────────────────

    public function padres(Request $request)
    {
        $estudiante = session('padre_estudiante');
        if (!$estudiante) return redirect()->route('padres.portal');

        $codigo = $estudiante->CODIGO;
        $anio   = date('Y');

        $desde = $request->input('desde', Carbon::create($anio, 1, 1)->format('Y-m-d'));
        $hasta = $request->input('hasta', today()->format('Y-m-d'));

        $registros = DB::table('ASISTENCIA')
            ->where('CODIGO', $codigo)
            ->whereBetween('FECHA', [$desde, $hasta])
            ->orderBy('FECHA', 'desc')
            ->get();

        $resumen = [
            'presentes'           => $registros->where('ASISTENCIA', 'P')->count(),
            'ausentes'            => $registros->where('ASISTENCIA', 'A')->count(),
            'excusas'             => $registros->where('ASISTENCIA', 'EX')->count(),
            'salidas_anticipadas' => $registros->where('ASISTENCIA', 'SA')->count(),
            'retardos'            => $registros->where('RETARDO', 1)->count(),
            'falta_carnet'        => $registros->where('CARNET', 1)->count(),
            'falta_uniforme'      => $registros->where('UNIFORME', 1)->count(),
            'falta_presentacion'  => $registros->where('PRESENTACION', 1)->count(),
            'total'               => $registros->count(),
        ];

        return view('asistencia.padres', compact('registros', 'resumen', 'desde', 'hasta', 'anio'));
    }

    // ─── Reporte (todos) ────────────────────────────────────────────────────

    public function reporte(Request $request)
    {
        $vista       = $request->input('vista', 'acumulado'); // acumulado | semana
        $cursoFiltro = $request->input('curso');
        $busqueda    = $request->input('busqueda');
        $codigo      = $request->input('codigo');
        $cursos      = DB::table('ESTUDIANTES')
            ->where('ESTADO', 'MATRICULADO')
            ->distinct()->orderBy('CURSO')->pluck('CURSO');

        // ── Vista acumulada ──────────────────────────────────────────────
        $acumulado  = collect();
        $fechaDesde = $request->input('desde', today()->startOfMonth()->format('Y-m-d'));
        $fechaHasta = $request->input('hasta', today()->format('Y-m-d'));

        if ($vista === 'acumulado') {
            $q = DB::table('ASISTENCIA as a')
                ->join('ESTUDIANTES as e', 'e.CODIGO', '=', 'a.CODIGO')
                ->where('e.ESTADO', 'MATRICULADO')
                ->whereBetween('a.FECHA', [$fechaDesde, $fechaHasta])
                ->select(
                    'a.CODIGO',
                    'e.APELLIDO1', 'e.APELLIDO2', 'e.NOMBRE1', 'e.NOMBRE2', 'e.CURSO',
                    DB::raw("SUM(CASE WHEN a.ASISTENCIA='P'  THEN 1 ELSE 0 END) as presentes"),
                    DB::raw("SUM(CASE WHEN a.ASISTENCIA='A'  THEN 1 ELSE 0 END) as ausentes"),
                    DB::raw("SUM(CASE WHEN a.ASISTENCIA='EX' THEN 1 ELSE 0 END) as excusas"),
                    DB::raw("SUM(CASE WHEN a.ASISTENCIA='SA' THEN 1 ELSE 0 END) as salidas_anticipadas"),
                    DB::raw('SUM(a.RETARDO)                                      as retardos'),
                    DB::raw('SUM(a.CARNET)                                        as falta_carnet'),
                    DB::raw('SUM(a.UNIFORME)                                     as falta_uniforme'),
                    DB::raw('SUM(a.PRESENTACION)                                 as falta_presentacion'),
                    DB::raw('COUNT(*) as total_dias')
                )
                ->groupBy('a.CODIGO', 'e.APELLIDO1', 'e.APELLIDO2', 'e.NOMBRE1', 'e.NOMBRE2', 'e.CURSO');

            if ($cursoFiltro) $q->where('e.CURSO', $cursoFiltro);
            if ($codigo)      $q->where('a.CODIGO', $codigo);
            if ($busqueda) {
                $q->where(function ($q2) use ($busqueda) {
                    $q2->where('e.APELLIDO1', 'like', "%$busqueda%")
                       ->orWhere('e.APELLIDO2', 'like', "%$busqueda%")
                       ->orWhere('e.NOMBRE1',   'like', "%$busqueda%")
                       ->orWhere('e.CODIGO',    'like', "%$busqueda%");
                });
            }

            $acumulado = $q->orderBy('e.APELLIDO1')->orderBy('e.APELLIDO2')->get();
        }

        // ── Vista semanal ────────────────────────────────────────────────
        $dias        = [];
        $mapaAsist   = [];
        $semanaLabel = '';
        $semanaAnterior = '';
        $semanaSiguiente = '';

        if ($vista === 'semana') {
            $semanaInicio = Carbon::parse(
                $request->input('semana', today()->startOfWeek(Carbon::MONDAY)->format('Y-m-d'))
            )->startOfWeek(Carbon::MONDAY);

            $semanaFin = $semanaInicio->copy()->addDays(4); // viernes

            $semanaLabel     = 'Semana del ' . $semanaInicio->format('d/m/Y') . ' al ' . $semanaFin->format('d/m/Y');
            $semanaAnterior  = $semanaInicio->copy()->subWeek()->format('Y-m-d');
            $semanaSiguiente = $semanaInicio->copy()->addWeek()->format('Y-m-d');

            // Días de la semana (lun-vie)
            $d = $semanaInicio->copy();
            while ($d->lte($semanaFin)) {
                $dias[] = $d->copy();
                $d->addDay();
            }

            $q = DB::table('ASISTENCIA as a')
                ->join('ESTUDIANTES as e', 'e.CODIGO', '=', 'a.CODIGO')
                ->where('e.ESTADO', 'MATRICULADO')
                ->whereBetween('a.FECHA', [$semanaInicio->format('Y-m-d'), $semanaFin->format('Y-m-d')])
                ->select('a.*', 'e.APELLIDO1', 'e.APELLIDO2', 'e.NOMBRE1', 'e.NOMBRE2', 'e.CURSO');

            if ($cursoFiltro) $q->where('e.CURSO', $cursoFiltro);
            if ($codigo)      $q->where('a.CODIGO', $codigo);
            if ($busqueda) {
                $q->where(function ($q2) use ($busqueda) {
                    $q2->where('e.APELLIDO1', 'like', "%$busqueda%")
                       ->orWhere('e.NOMBRE1',   'like', "%$busqueda%")
                       ->orWhere('e.CODIGO',    'like', "%$busqueda%");
                });
            }

            $registros = $q->orderBy('e.APELLIDO1')->get();

            // Mapa [CODIGO][FECHA] => registro
            foreach ($registros as $r) {
                $mapaAsist[$r->CODIGO][$r->FECHA] = $r;
            }

            // Obtener lista de estudiantes de esa semana
            $codigosEnSemana = array_keys($mapaAsist);

            // Si hay filtro de curso, obtener todos los estudiantes del curso aunque no tengan registro
            if ($cursoFiltro) {
                $estQ = DB::table('ESTUDIANTES')
                    ->where('CURSO', $cursoFiltro)
                    ->where('ESTADO', 'MATRICULADO')
                    ->orderBy('APELLIDO1')->orderBy('APELLIDO2');
                if ($codigo) $estQ->where('CODIGO', $codigo);
                if ($busqueda) {
                    $estQ->where(function ($q2) use ($busqueda) {
                        $q2->where('APELLIDO1', 'like', "%$busqueda%")
                           ->orWhere('NOMBRE1',   'like', "%$busqueda%")
                           ->orWhere('CODIGO',    'like', "%$busqueda%");
                    });
                }
                $estudiantesSemana = $estQ->get();
            } else {
                $estudiantesSemana = empty($codigosEnSemana) ? collect() :
                    DB::table('ESTUDIANTES')
                        ->whereIn('CODIGO', $codigosEnSemana)
                        ->orderBy('APELLIDO1')->orderBy('APELLIDO2')
                        ->get();
            }
        } else {
            $estudiantesSemana = collect();
            $semanaInicio = today()->startOfWeek(Carbon::MONDAY);
        }

        return view('asistencia.reporte', compact(
            'vista', 'cursos', 'cursoFiltro', 'busqueda', 'codigo',
            'acumulado', 'fechaDesde', 'fechaHasta',
            'dias', 'mapaAsist', 'estudiantesSemana',
            'semanaLabel', 'semanaAnterior', 'semanaSiguiente',
            'semanaInicio'
        ));
    }
}
