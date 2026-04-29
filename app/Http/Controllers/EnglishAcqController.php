<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnglishAcqController extends Controller
{
    private string $tabla = 'NOTAS_ENGLISH_ACQ';

    // Devuelve el período activo (1-4) según calendario_academico, o null si no hay.
    private static function periodoActivoHoy(int $anio): ?int
    {
        $inicios = DB::table('calendario_academico')
            ->where('anio', $anio)
            ->where('dia_ciclo', 1)
            ->orderBy('fecha')
            ->distinct()
            ->pluck('fecha')
            ->values();

        if ($inicios->isEmpty()) return null;

        $hoy = now()->toDateString();

        for ($p = 1; $p <= 4; $p++) {
            $offset = ($p - 1) * 7;
            $inicio = $inicios[$offset] ?? null;
            $finExcl = $inicios[$offset + 7] ?? null; // primer día del período siguiente

            if (!$inicio) continue;

            if ($finExcl) {
                if ($hoy >= $inicio && $hoy < $finExcl) return $p;
            } else {
                if ($hoy >= $inicio) return $p;
            }
        }

        return null;
    }

    public function docente(Request $request)
    {
        $profile    = auth()->user()->PROFILE;
        $esSuperior = in_array($profile, ['SuperAd', 'Admin']) || str_starts_with($profile, 'DOC') || str_starts_with($profile, 'COR');

        $query = DB::table('ASIGNACION_PCM as a')
            ->where('a.calificable', 1)
            ->select('a.CURSO')
            ->distinct();

        if (!$esSuperior) {
            $query->where('a.CODIGO_EMP', $profile);
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

        $esAdmin           = in_array($profile, ['SuperAd', 'Admin']);
        $periodoCalendario = self::periodoActivoHoy($anio);
        $registroActivo    = $esAdmin || ($periodoCalendario === $periodoSelec);

        return view('english-acq.docente', compact(
            'cursos', 'cursoSelec', 'periodoSelec', 'anio', 'estudiantes', 'notasMap',
            'registroActivo', 'periodoCalendario', 'esAdmin'
        ));
    }

    public function registrar(Request $request)
    {
        $profile   = auth()->user()->PROFILE;
        $esAdmin   = in_array($profile, ['SuperAd', 'Admin']);
        $codigoAlum = $request->input('CODIGO_ALUM');
        $periodo    = (int) $request->input('PERIODO');

        $periodoActivo = self::periodoActivoHoy((int) date('Y'));
        if (!$esAdmin && $periodoActivo !== $periodo) {
            return back()->with('error_acq', 'Solo se pueden registrar descuentos en el período activo según el calendario académico (período ' . ($periodoActivo ?? '—') . ').');
        }

        DB::table($this->tabla)->insert([
            'CODIGO_ALUM' => $codigoAlum,
            'CODIGO_EMP'  => $profile,
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

    public function entregar(Request $request)
    {
        $request->validate([
            'periodo' => 'required|integer|between:1,4',
            'anio'    => 'required|integer|min:2024|max:2030',
        ]);

        $profile = auth()->user()->PROFILE;
        if (!in_array($profile, ['SuperAd', 'Admin'])) {
            abort(403);
        }

        $periodo = (int) $request->periodo;
        $anio    = (int) $request->anio;

        // Todos los estudiantes matriculados
        $estudiantes = DB::table('ESTUDIANTES')
            ->where('ESTADO', 'MATRICULADO')
            ->pluck('CODIGO');

        // Conteo de bajadas por alumno para este período/año
        $descuentos = DB::table($this->tabla)
            ->where('PERIODO', $periodo)
            ->where('ANIO', $anio)
            ->select('CODIGO_ALUM', DB::raw('COUNT(*) as total'))
            ->groupBy('CODIGO_ALUM')
            ->pluck('total', 'CODIGO_ALUM');

        $procesados = 0;

        DB::transaction(function () use ($estudiantes, $descuentos, $periodo, $anio, $profile, &$procesados) {
            foreach ($estudiantes as $codigo) {
                $bajadas   = $descuentos[$codigo] ?? 0;
                $nota      = round(max(0, 10 - ($bajadas * 0.25)), 2);

                DB::table('NOTAS_2026')->updateOrInsert(
                    [
                        'CODIGO_ALUM' => $codigo,
                        'PERIODO'     => $periodo,
                        'CODIGO_MAT'  => 11,
                        'TIPODENOTA'  => 'N',
                    ],
                    [
                        'NOTA'       => $nota,
                        'CODIGO_EMP' => $profile,
                    ]
                );
                $procesados++;
            }
        });

        return back()->with('success_acq', "Notas English Acquisition período {$periodo}/{$anio} subidas a NOTAS_2026 ({$procesados} estudiantes).");
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
            ->leftJoin('CODIGOS_DOC as d', 'd.CODIGO_EMP', '=', 'n.CODIGO_EMP')
            ->where('n.ANIO', $anio)
            ->select(
                'n.id', 'n.CODIGO_ALUM', 'n.PERIODO', 'n.FECHA', 'n.CODIGO_EMP',
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
