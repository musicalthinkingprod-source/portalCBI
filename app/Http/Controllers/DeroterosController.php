<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeroterosController extends Controller
{
    // Materias que no aplican para recuperación/derroteros
    const SIN_RECUPERACION = [11, 30, 31, 131]; // English Acquisition, Gestión Empresarial, Proyecto, Proyecto PE

    private function tablaNotas(int $anio): string
    {
        return 'NOTAS_' . $anio;
    }

    /**
     * Calcula los derroteros de un período/año aplicando todas las reglas de negocio.
     * $incluirResueltos = true  → para el informe general (muestra pendientes Y resueltos)
     * $incluirResueltos = false → para la vista del docente (solo pendientes)
     */
    private function calcularDerroteros(int $periodo, int $anio, ?string $curso = null, ?string $busqueda = null, ?int $codigoMat = null, bool $incluirResueltos = false): \Illuminate\Support\Collection
    {
        $tabla = $this->tablaNotas($anio);

        // ── 1. Fallos activos (NOTA < 7 en la tabla de notas) ─────────────────
        try {
            $queryPendientes = DB::table($tabla . ' as n')
                ->join('ESTUDIANTES as e', 'e.CODIGO', '=', 'n.CODIGO_ALUM')
                ->join('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'n.CODIGO_MAT')
                ->where('e.ESTADO', 'MATRICULADO')
                ->where('n.PERIODO', $periodo)
                ->where('n.NOTA', '<', 7)
                ->whereNotIn('n.CODIGO_MAT', self::SIN_RECUPERACION)
                ->select('n.CODIGO_ALUM', 'n.CODIGO_MAT', 'n.NOTA',
                         'e.APELLIDO1', 'e.APELLIDO2', 'e.NOMBRE1', 'e.NOMBRE2', 'e.CURSO',
                         'm.NOMBRE_MAT');

            if ($curso)     $queryPendientes->where('e.CURSO', $curso);
            if ($codigoMat) $queryPendientes->where('n.CODIGO_MAT', $codigoMat);
            if ($busqueda) {
                $queryPendientes->where(function ($q) use ($busqueda) {
                    $q->where('e.APELLIDO1', 'like', "%$busqueda%")
                      ->orWhere('e.APELLIDO2', 'like', "%$busqueda%")
                      ->orWhere('e.NOMBRE1',   'like', "%$busqueda%");
                });
            }

            $fallos = $queryPendientes->get();
        } catch (\Exception $e) {
            return collect();
        }

        // ── 2. Si es el informe, agregar los ya resueltos que no están en fallos ─
        if ($incluirResueltos) {
            try {
                $queryResueltos = DB::table('Derroteros as d')
                    ->join('ESTUDIANTES as e', 'e.CODIGO', '=', 'd.CODIGO_ALUM')
                    ->join('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'd.CODIGO_MAT')
                    ->where('d.PERIODO', $periodo)
                    ->where('d.ANIO', $anio)
                    ->whereNotIn('d.CODIGO_MAT', self::SIN_RECUPERACION)
                    ->whereNotIn(DB::raw("CONCAT(d.CODIGO_ALUM,'_',d.CODIGO_MAT)"),
                        $fallos->map(fn($f) => $f->CODIGO_ALUM . '_' . $f->CODIGO_MAT)->toArray() ?: ['__none__'])
                    ->select('d.CODIGO_ALUM', 'd.CODIGO_MAT',
                             DB::raw('COALESCE(d.NOTA_ORIGINAL, d.NOTA_RECUPERACION) as NOTA'),
                             'e.APELLIDO1', 'e.APELLIDO2', 'e.NOMBRE1', 'e.NOMBRE2', 'e.CURSO',
                             'm.NOMBRE_MAT');

                if ($curso)     $queryResueltos->where('e.CURSO', $curso);
                if ($codigoMat) $queryResueltos->where('d.CODIGO_MAT', $codigoMat);
                if ($busqueda) {
                    $queryResueltos->where(function ($q) use ($busqueda) {
                        $q->where('e.APELLIDO1', 'like', "%$busqueda%")
                          ->orWhere('e.APELLIDO2', 'like', "%$busqueda%")
                          ->orWhere('e.NOMBRE1',   'like', "%$busqueda%");
                    });
                }

                $fallos = $fallos->concat($queryResueltos->get());
            } catch (\Exception $e) {}
        }

        if ($fallos->isEmpty()) return collect();

        $codigosAlum = $fallos->pluck('CODIGO_ALUM')->unique()->toArray();
        $codigosMat  = $fallos->pluck('CODIGO_MAT')->unique()->toArray();

        // ── 3. Fallas previas ──────────────────────────────────────────────────
        $fallasPrevias = collect();
        if ($periodo > 1) {
            try {
                $fallasPrevias = DB::table($tabla)
                    ->whereIn('CODIGO_ALUM', $codigosAlum)
                    ->whereIn('CODIGO_MAT', $codigosMat)
                    ->where('PERIODO', '<', $periodo)
                    ->where('NOTA', '<', 7)
                    ->select('CODIGO_ALUM', 'CODIGO_MAT', DB::raw('COUNT(*) as veces'))
                    ->groupBy('CODIGO_ALUM', 'CODIGO_MAT')
                    ->get()
                    ->keyBy(fn($r) => $r->CODIGO_ALUM . '_' . $r->CODIGO_MAT);
            } catch (\Exception $e) {}
        }

        // ── 4. Resoluciones ────────────────────────────────────────────────────
        $resoluciones = DB::table('Derroteros')
            ->whereIn('CODIGO_ALUM', $codigosAlum)
            ->whereIn('CODIGO_MAT', $codigosMat)
            ->where('PERIODO', $periodo)
            ->where('ANIO', $anio)
            ->get()
            ->keyBy(fn($r) => $r->CODIGO_ALUM . '_' . $r->CODIGO_MAT);

        // ── 5. Mapear reglas ───────────────────────────────────────────────────
        $fallos = $fallos->map(function ($f) use ($fallasPrevias, $resoluciones) {
            $key     = $f->CODIGO_ALUM . '_' . $f->CODIGO_MAT;
            $previas = $fallasPrevias[$key]->veces ?? 0;
            $res     = $resoluciones[$key] ?? null;

            if ($f->NOTA < 5) {
                $f->elegible          = false;
                $f->razon_no_elegible = 'Nota inferior a 5.0';
            } elseif ($previas >= 2) {
                $f->elegible          = false;
                $f->razon_no_elegible = "Perdida en {$previas} período(s) anterior(es)";
            } else {
                $f->elegible          = true;
                $f->razon_no_elegible = null;
            }

            $f->previas_periodos  = $previas;
            $f->resolucion        = $res->RESOLUCION        ?? 'PENDIENTE';
            $f->nota_recuperacion = $res->NOTA_RECUPERACION ?? null;
            $f->nota_original     = $res->NOTA_ORIGINAL     ?? $f->NOTA;
            $f->horario           = $res->HORARIO           ?? null;
            $f->derrotero_id      = $res->id                ?? null;
            $f->nota_intermedia   = round(($f->nota_original + 7) / 2, 1);

            return $f;
        });

        // ── 6. Regla máximo 4 materias ─────────────────────────────────────────
        return $fallos->groupBy('CODIGO_ALUM')->map(function ($materias) {
            $elegibles   = $materias->filter(fn($m) => $m->elegible)->sortBy('NOTA')->values();
            $noElegibles = $materias->filter(fn($m) => !$m->elegible)->values();

            $elegibles = $elegibles->map(function ($m, $idx) {
                if ($idx >= 4) {
                    $m->elegible          = false;
                    $m->razon_no_elegible = 'Límite de 4 materias recuperables superado';
                }
                return $m;
            });

            return $noElegibles->concat($elegibles)->sortBy('NOMBRE_MAT')->values();
        });
    }

    // ─── Visualizador general ────────────────────────────────────────────────

    public function index(Request $request)
    {
        $anio        = (int) $request->input('anio', date('Y'));
        $periodo     = (int) $request->input('periodo', 1);
        $cursoFiltro = $request->input('curso');
        $busqueda    = $request->input('busqueda');

        $derroteros = $this->calcularDerroteros($periodo, $anio, $cursoFiltro, $busqueda, null, true);

        $cursos = DB::table('ESTUDIANTES')
            ->where('ESTADO', 'MATRICULADO')
            ->distinct()->orderBy('CURSO')->pluck('CURSO');

        return view('derroteros.index', compact(
            'derroteros', 'anio', 'periodo', 'cursoFiltro', 'busqueda', 'cursos'
        ));
    }

    // ─── Resolución por docente ──────────────────────────────────────────────

    public function docente(Request $request)
    {
        $profile    = auth()->user()->PROFILE;
        $esSuperior = in_array($profile, ['SuperAd', 'Admin']);

        $queryAsig = DB::table('ASIGNACION_PCM as a')
            ->join('CODIGOSMAT as m', 'a.CODIGO_MAT', '=', 'm.CODIGO_MAT')
            ->where('a.calificable', 1)
            ->whereNotIn('a.CODIGO_MAT', self::SIN_RECUPERACION)
            ->select('a.CODIGO_MAT', 'a.CURSO', 'm.NOMBRE_MAT');

        if (!$esSuperior) {
            $queryAsig->where('a.CODIGO_EMP', $profile);
        }

        $asignaciones = $queryAsig->orderBy('m.NOMBRE_MAT')->orderBy('a.CURSO')->get();
        $materias     = $asignaciones->unique('CODIGO_MAT')->values();

        $matSelec     = $request->input('materia') ? (int) $request->input('materia') : null;
        $cursoSelec   = $request->input('curso');
        $periodoSelec = (int) $request->input('periodo', 1);
        $anio         = (int) date('Y');

        $cursosDisponibles = $matSelec
            ? $asignaciones->where('CODIGO_MAT', $matSelec)->unique('CURSO')->values()
            : collect();

        $mapaMateriasCursos = [];
        foreach ($asignaciones as $a) {
            $mapaMateriasCursos[$a->CODIGO_MAT][] = $a->CURSO;
        }
        foreach ($mapaMateriasCursos as &$cs) {
            $cs = array_values(array_unique($cs));
        }

        $materiaNombre = $matSelec
            ? ($materias->firstWhere('CODIGO_MAT', $matSelec)->NOMBRE_MAT ?? '')
            : '';

        // Derroteros de la materia/curso seleccionada (agrupados por alumno)
        $derroteros = collect();
        if ($matSelec && $cursoSelec) {
            $derroteros = $this->calcularDerroteros($periodoSelec, $anio, $cursoSelec, null, $matSelec);
        }

        return view('derroteros.docente', compact(
            'materias', 'cursosDisponibles', 'matSelec', 'cursoSelec',
            'periodoSelec', 'anio', 'mapaMateriasCursos', 'materiaNombre', 'derroteros'
        ));
    }

    public function resolver(Request $request)
    {
        $profile      = auth()->user()->PROFILE;
        $codigoAlum   = (int) $request->input('CODIGO_ALUM');
        $codigoMat    = (int) $request->input('CODIGO_MAT');
        $periodo      = (int) $request->input('periodo');
        $anio         = (int) date('Y');
        $resolucion   = $request->input('resolucion'); // RECUPERO | NO_RECUPERO | INTERMEDIO | NO_ASISTIO
        $notaIngresada = $request->input('nota_recuperacion');

        // Obtener nota original
        $notaOriginal = DB::table($this->tablaNotas($anio))
            ->where('CODIGO_ALUM', $codigoAlum)
            ->where('CODIGO_MAT', $codigoMat)
            ->where('PERIODO', $periodo)
            ->value('NOTA');

        // Calcular nota final según resolución
        $notaFinal = match($resolucion) {
            'RECUPERO'    => 7.0,
            'NO_RECUPERO' => $notaOriginal,
            'NO_ASISTIO'  => $notaOriginal,
            'INTERMEDIO'  => (float) $notaIngresada,
            default       => $notaOriginal,
        };

        // Validar nota intermedia
        if ($resolucion === 'INTERMEDIO') {
            if ($notaFinal <= $notaOriginal || $notaFinal > 7) {
                return back()->withErrors(['resolucion' => "La nota intermedia debe ser mayor a {$notaOriginal} y no mayor a 7."]);
            }
        }

        // Guardar / actualizar resolución en Derroteros
        $existe = DB::table('Derroteros')
            ->where('CODIGO_ALUM', $codigoAlum)
            ->where('CODIGO_MAT', $codigoMat)
            ->where('PERIODO', $periodo)
            ->where('ANIO', $anio)
            ->exists();

        $datos = [
            'RESOLUCION'        => $resolucion,
            'NOTA_RECUPERACION' => $notaFinal,
            'NOTA_ORIGINAL'     => $notaOriginal,
            'CODIGO_EMP'        => $profile,
        ];

        if ($existe) {
            DB::table('Derroteros')
                ->where('CODIGO_ALUM', $codigoAlum)->where('CODIGO_MAT', $codigoMat)
                ->where('PERIODO', $periodo)->where('ANIO', $anio)
                ->update($datos);
        } else {
            DB::table('Derroteros')->insert(array_merge($datos, [
                'CODIGO_ALUM' => $codigoAlum,
                'CODIGO_MAT'  => $codigoMat,
                'PERIODO'     => $periodo,
                'ANIO'        => $anio,
            ]));
        }

        // Actualizar nota en NOTAS si aplica
        $tipoNota = in_array($resolucion, ['NO_RECUPERO', 'NO_ASISTIO']) ? 'N' : 'R';

        DB::table($this->tablaNotas($anio))
            ->where('CODIGO_ALUM', $codigoAlum)
            ->where('CODIGO_MAT', $codigoMat)
            ->where('PERIODO', $periodo)
            ->update(['NOTA' => $notaFinal, 'TIPODENOTA' => $tipoNota]);

        return back()->with('success', 'Resolución guardada correctamente.');
    }

    // ─── Horarios ────────────────────────────────────────────────────────────

    public function horarios(Request $request)
    {
        $profile     = auth()->user()->PROFILE;
        $esSuperior  = in_array($profile, ['SuperAd', 'Admin']);
        $anio        = (int) $request->input('anio', date('Y'));
        $periodo     = (int) $request->input('periodo', 1);
        $cursoFiltro = $request->input('curso');

        $query = DB::table('Derroteros as d')
            ->join('ESTUDIANTES as e', 'e.CODIGO', '=', 'd.CODIGO_ALUM')
            ->join('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'd.CODIGO_MAT')
            ->where('d.ANIO', $anio)
            ->where('d.PERIODO', $periodo)
            ->select('d.id', 'd.HORARIO', 'd.RESOLUCION', 'e.APELLIDO1', 'e.APELLIDO2',
                     'e.NOMBRE1', 'e.NOMBRE2', 'e.CURSO', 'm.NOMBRE_MAT', 'd.CODIGO_ALUM', 'd.CODIGO_MAT');

        if ($cursoFiltro) $query->where('e.CURSO', $cursoFiltro);

        $registros = $query->orderBy('e.APELLIDO1')->orderBy('m.NOMBRE_MAT')->get();

        $cursos = DB::table('ESTUDIANTES')
            ->where('ESTADO', 'MATRICULADO')
            ->distinct()->orderBy('CURSO')->pluck('CURSO');

        return view('derroteros.horarios', compact(
            'registros', 'anio', 'periodo', 'cursoFiltro', 'cursos', 'esSuperior'
        ));
    }

    public function guardarHorario(Request $request)
    {
        DB::table('Derroteros')
            ->where('id', $request->input('id'))
            ->update(['HORARIO' => $request->input('horario')]);

        return back()->with('success', 'Horario actualizado.');
    }

    // ─── Padres ──────────────────────────────────────────────────────────────

    public function padres()
    {
        $estudiante = session('padre_estudiante');
        if (!$estudiante) return redirect()->route('padres.portal');

        // Verificar que haya algún período de derroteros abierto
        $abiertoDerrotero = collect([1,2,3,4])->contains(fn($p) => \App\Http\Controllers\FechasController::estaActivo('D'.$p));
        if (!$abiertoDerrotero) {
            return redirect()->route('padres.portal')->with('aviso', 'La institución aún no ha habilitado la consulta de derroteros.');
        }

        $codigo = $estudiante->CODIGO;

        // Verificar deuda (solo bloquea la nota, no el acceso)
        $exento    = \App\Http\Controllers\ExencionCarteraController::tieneExencion($codigo);
        $facturado = DB::table('facturacion')->where('codigo_alumno', $codigo)->sum('valor');
        $pagado    = DB::table('registro_pagos')->where('codigo_alumno', $codigo)->sum('valor');
        $bloqueado = !$exento && ($facturado - $pagado) > 100000;

        $anio    = (int) date('Y');
        $periodo = (int) request()->input('periodo', 1);

        // Calcular derroteros para este estudiante
        $tabla = 'NOTAS_' . $anio;
        $fallos = collect();

        try {
            $fallos = DB::table($tabla . ' as n')
                ->join('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'n.CODIGO_MAT')
                ->where('n.CODIGO_ALUM', $codigo)
                ->where('n.PERIODO', $periodo)
                ->where('n.NOTA', '<', 7)
                ->whereNotIn('n.CODIGO_MAT', self::SIN_RECUPERACION)
                ->select('n.CODIGO_MAT', 'n.NOTA', 'm.NOMBRE_MAT')
                ->get();
        } catch (\Exception $e) {}

        // Fallas previas
        $fallasPrevias = collect();
        if ($periodo > 1 && $fallos->isNotEmpty()) {
            try {
                $fallasPrevias = DB::table($tabla)
                    ->where('CODIGO_ALUM', $codigo)
                    ->whereIn('CODIGO_MAT', $fallos->pluck('CODIGO_MAT')->toArray())
                    ->where('PERIODO', '<', $periodo)
                    ->where('NOTA', '<', 7)
                    ->select('CODIGO_MAT', DB::raw('COUNT(*) as veces'))
                    ->groupBy('CODIGO_MAT')
                    ->pluck('veces', 'CODIGO_MAT');
            } catch (\Exception $e) {}
        }

        // Resoluciones
        $resoluciones = DB::table('Derroteros')
            ->where('CODIGO_ALUM', $codigo)
            ->where('PERIODO', $periodo)
            ->where('ANIO', $anio)
            ->pluck('RESOLUCION', 'CODIGO_MAT');

        // Aplicar elegibilidad
        $fallos = $fallos->map(function ($f) use ($fallasPrevias, $resoluciones) {
            $previas = $fallasPrevias[$f->CODIGO_MAT] ?? 0;

            if ($f->NOTA < 5)       { $f->elegible = false; $f->razon = 'Nota inferior a 5.0'; }
            elseif ($previas >= 2)  { $f->elegible = false; $f->razon = "Perdida {$previas} veces antes"; }
            else                    { $f->elegible = true;  $f->razon = null; }

            $f->resolucion = $resoluciones[$f->CODIGO_MAT] ?? 'PENDIENTE';
            return $f;
        });

        // Regla 4 materias
        $elegibles   = $fallos->filter(fn($f) => $f->elegible)->sortBy('NOTA')->values();
        $noElegibles = $fallos->filter(fn($f) => !$f->elegible)->values();
        $elegibles   = $elegibles->map(function ($f, $idx) {
            if ($idx >= 4) { $f->elegible = false; $f->razon = 'Límite de 4 materias superado'; }
            return $f;
        });

        $derroteros = $noElegibles->concat($elegibles)->sortBy('NOMBRE_MAT')->values();

        $curso = $estudiante->CURSO ?? '';
        $urlsSite = $derroteros->pluck('CODIGO_MAT')->unique()
            ->mapWithKeys(fn($cm) => [$cm => \App\Http\Controllers\PadresController::urlSite((int)$cm, $curso)])
            ->toArray();

        return view('derroteros.padres', compact('derroteros', 'anio', 'periodo', 'bloqueado', 'urlsSite'));
    }
}
