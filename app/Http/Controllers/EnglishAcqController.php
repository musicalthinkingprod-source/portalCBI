<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnglishAcqController extends Controller
{
    private string $tabla = 'NOTAS_ENGLISH_ACQ';

    public function docente(Request $request)
    {
        $profile    = auth()->user()->PROFILE;
        $esSuperior = in_array($profile, ['SuperAd', 'Admin']);

        $query = DB::table('ASIGNACION_PCM as a')
            ->select('a.CURSO')
            ->distinct();

        if (!$esSuperior) {
            $query->where('a.CODIGO_DOC', $profile);
        }

        $cursos       = $query->orderBy('a.CURSO')->pluck('CURSO');
        $cursoSelec   = $request->input('curso');
        $periodoSelec = (int) $request->input('periodo', 1);
        $anio         = (int) date('Y');

        $estudiantes = collect();
        $notasMap    = [];

        if ($cursoSelec) {
            $estudiantes = DB::table('ESTUDIANTES')
                ->where('CURSO', $cursoSelec)
                ->where('ESTADO', 'MATRICULADO')
                ->orderBy('APELLIDO1')->orderBy('APELLIDO2')->orderBy('NOMBRE1')
                ->get();

            $codigos = $estudiantes->pluck('CODIGO')->toArray();

            $conteos = DB::table($this->tabla)
                ->whereIn('CODIGO_ALUM', $codigos)
                ->where('PERIODO', $periodoSelec)
                ->where('ANIO', $anio)
                ->select('CODIGO_ALUM', DB::raw('COUNT(*) as total'))
                ->groupBy('CODIGO_ALUM')
                ->pluck('total', 'CODIGO_ALUM');

            foreach ($estudiantes as $est) {
                $descuentos        = $conteos[$est->CODIGO] ?? 0;
                $notasMap[$est->CODIGO] = max(0, 10 - ($descuentos * 0.25));
            }
        }

        return view('english-acq.docente', compact(
            'cursos', 'cursoSelec', 'periodoSelec', 'anio', 'estudiantes', 'notasMap'
        ));
    }

    public function registrar(Request $request)
    {
        $profile    = auth()->user()->PROFILE;
        $codigoAlum = $request->input('CODIGO_ALUM');
        $periodo    = $request->input('PERIODO');

        DB::table($this->tabla)->insert([
            'CODIGO_ALUM' => $codigoAlum,
            'CODIGO_DOC'  => $profile,
            'PERIODO'     => $periodo,
            'ANIO'        => (int) date('Y'),
            'FECHA'       => now(),
        ]);

        return back()->with('success_acq', 'Punto descontado correctamente.');
    }

    public function eliminar(int $id)
    {
        DB::table($this->tabla)->where('id', $id)->delete();
        return back()->with('success_acq', 'Registro eliminado.');
    }

    public function padres()
    {
        $estudiante = session('padre_estudiante');
        if (!$estudiante) {
            return redirect()->route('padres.portal');
        }

        $anio    = (int) date('Y');
        $codigo  = $estudiante->CODIGO;
        $notas   = [];
        $detalle = [];

        for ($p = 1; $p <= 4; $p++) {
            $registros = DB::table($this->tabla)
                ->where('CODIGO_ALUM', $codigo)
                ->where('PERIODO', $p)
                ->where('ANIO', $anio)
                ->orderBy('FECHA')
                ->get();

            $notas[$p] = max(0, 10 - ($registros->count() * 0.25));

            foreach ($registros as $r) {
                $detalle[] = ['periodo' => $p, 'fecha' => $r->FECHA];
            }
        }

        return view('english-acq.padres', compact('notas', 'detalle', 'anio'));
    }

    public function informe(Request $request)
    {
        $anio        = $request->input('anio', date('Y'));
        $periodo     = $request->input('periodo');
        $cursoFiltro = $request->input('curso');
        $busqueda    = $request->input('busqueda');
        $codigo      = $request->input('codigo');

        // Resumen por estudiante y período
        $resumenQuery = DB::table($this->tabla . ' as n')
            ->join('ESTUDIANTES as e', 'e.CODIGO', '=', 'n.CODIGO_ALUM')
            ->where('n.ANIO', $anio)
            ->select(
                'n.CODIGO_ALUM', 'n.PERIODO',
                'e.APELLIDO1', 'e.APELLIDO2', 'e.NOMBRE1', 'e.NOMBRE2', 'e.CURSO',
                DB::raw('COUNT(*) as descuentos'),
                DB::raw('GREATEST(10 - COUNT(*) * 0.25, 0) as nota')
            )
            ->groupBy('n.CODIGO_ALUM', 'n.PERIODO', 'e.APELLIDO1', 'e.APELLIDO2', 'e.NOMBRE1', 'e.NOMBRE2', 'e.CURSO');

        if ($periodo)     $resumenQuery->where('n.PERIODO', $periodo);
        if ($cursoFiltro) $resumenQuery->where('e.CURSO', $cursoFiltro);
        if ($codigo)      $resumenQuery->where('n.CODIGO_ALUM', $codigo);
        if ($busqueda) {
            $resumenQuery->where(function ($q) use ($busqueda) {
                $q->where('e.APELLIDO1',   'like', "%$busqueda%")
                  ->orWhere('e.APELLIDO2', 'like', "%$busqueda%")
                  ->orWhere('e.NOMBRE1',   'like', "%$busqueda%")
                  ->orWhere('e.CODIGO',    'like', "%$busqueda%");
            });
        }

        $resumen = $resumenQuery->orderBy('e.APELLIDO1')->orderBy('n.PERIODO')->get();

        // Detalle completo con docente (solo admin)
        $detalleQuery = DB::table($this->tabla . ' as n')
            ->join('ESTUDIANTES as e', 'e.CODIGO', '=', 'n.CODIGO_ALUM')
            ->leftJoin('CODIGOS_DOC as d', 'd.CODIGO_DOC', '=', 'n.CODIGO_DOC')
            ->where('n.ANIO', $anio)
            ->select(
                'n.id', 'n.CODIGO_ALUM', 'n.PERIODO', 'n.FECHA', 'n.CODIGO_DOC',
                'e.APELLIDO1', 'e.APELLIDO2', 'e.NOMBRE1', 'e.NOMBRE2', 'e.CURSO',
                'd.NOMBRE_DOC'
            );

        if ($periodo)     $detalleQuery->where('n.PERIODO', $periodo);
        if ($cursoFiltro) $detalleQuery->where('e.CURSO', $cursoFiltro);
        if ($codigo)      $detalleQuery->where('n.CODIGO_ALUM', $codigo);
        if ($busqueda) {
            $detalleQuery->where(function ($q) use ($busqueda) {
                $q->where('e.APELLIDO1',   'like', "%$busqueda%")
                  ->orWhere('e.APELLIDO2', 'like', "%$busqueda%")
                  ->orWhere('e.NOMBRE1',   'like', "%$busqueda%")
                  ->orWhere('e.CODIGO',    'like', "%$busqueda%");
            });
        }

        $detalle = $detalleQuery->orderBy('e.APELLIDO1')->orderBy('n.PERIODO')->orderBy('n.FECHA')->get();

        $cursos = DB::table('ESTUDIANTES')
            ->where('ESTADO', 'MATRICULADO')
            ->distinct()->orderBy('CURSO')->pluck('CURSO');

        return view('english-acq.informe', compact(
            'resumen', 'detalle', 'anio', 'periodo', 'cursoFiltro', 'busqueda', 'codigo', 'cursos'
        ));
    }
}
