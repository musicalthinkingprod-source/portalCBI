<?php

namespace App\Http\Controllers;

use App\Models\Horario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeroterosController extends Controller
{
    // Materias que no aplican para recuperación/derroteros
    const SIN_RECUPERACION = [11, 30, 31, 131]; // English Acquisition, Gestión Empresarial, Proyecto, Proyecto PE

    // Franjas horarias: fuente única en el modelo Horario (mismo bloque 1..8 que el horario regular).
    private static function franjas(): array
    {
        return Horario::$horasRangos;
    }

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

            if ($curso) $queryPendientes->where('e.CURSO', $curso);
            // Nota: NO filtramos por $codigoMat aquí. La regla de "máximo 4
            // materias recuperables" debe ver todas las materias perdidas del
            // estudiante; si filtramos antes, la regla nunca dispara para los
            // docentes y muestra elegibilidad incorrecta.
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

                if ($curso) $queryResueltos->where('e.CURSO', $curso);
                // Mismo motivo que arriba: no filtramos por $codigoMat aquí.
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
                    ->where(function ($q) {
                        // Pérdida original: sigue < 7 o fue recuperada (TIPODENOTA='R')
                        $q->where('NOTA', '<', 7)
                          ->orWhere('TIPODENOTA', 'R');
                    })
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

        // ── 4b. Docentes por (curso base, materia) ─────────────────────────────
        $asigDoc = DB::table('ASIGNACION_PCM')
            ->whereIn('CODIGO_MAT', $codigosMat)
            ->where('calificable', 1)
            ->get()
            ->groupBy(fn($a) => (explode('-', (string) $a->CURSO)[0] ?? '') . '_' . $a->CODIGO_MAT);
        $docCodigos = $asigDoc->flatten()->pluck('CODIGO_DOC')->unique()->filter()->toArray();
        $docNombres = $docCodigos
            ? DB::table('CODIGOS_DOC')->whereIn('CODIGO_DOC', $docCodigos)->pluck('NOMBRE_DOC', 'CODIGO_DOC')
            : collect();

        // ── 5. Mapear reglas ───────────────────────────────────────────────────
        $fallos = $fallos->map(function ($f) use ($fallasPrevias, $resoluciones, $asigDoc, $docNombres) {
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
            $f->asistencia        = $res->ASISTENCIA        ?? null;
            $f->nota_recuperacion = $res->NOTA_RECUPERACION ?? null;
            $f->nota_original     = $res->NOTA_ORIGINAL     ?? $f->NOTA;
            $f->horario           = $res->HORARIO           ?? null;
            $f->franja            = $res->FRANJA            ?? null;
            $f->derrotero_id      = $res->id                ?? null;
            $f->nota_intermedia   = round(($f->nota_original + 7) / 2, 1);

            $base    = explode('-', (string) $f->CURSO)[0] ?? '';
            $aRow    = isset($asigDoc[$base . '_' . $f->CODIGO_MAT]) ? $asigDoc[$base . '_' . $f->CODIGO_MAT]->first() : null;
            $f->docente_cod = $aRow->CODIGO_DOC ?? null;
            $f->docente_nom = $f->docente_cod ? ($docNombres[$f->docente_cod] ?? $f->docente_cod) : null;

            // Normalización: la resolución vieja 'NO_ASISTIO' equivale ahora a
            // ASISTENCIA = 'NO_PRESENTO' sin nota. Para datos legacy, no la
            // mostramos como resolución; se exhibe a través de asistencia.
            if ($f->resolucion === 'NO_ASISTIO') {
                if ($f->asistencia === null) $f->asistencia = 'NO_PRESENTO';
                $f->resolucion = 'PENDIENTE';
            }

            return $f;
        });

        // ── 6. Regla máximo 4 materias ─────────────────────────────────────────
        $resultado = $fallos->groupBy('CODIGO_ALUM')->map(function ($materias) {
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

        // ── 7. Filtrar por materia (después de aplicar reglas globales) ────────
        if ($codigoMat) {
            $resultado = $resultado->map(function ($materias) use ($codigoMat) {
                return $materias->filter(fn($m) => (int) $m->CODIGO_MAT === (int) $codigoMat)->values();
            })->filter(fn($materias) => $materias->isNotEmpty());
        }

        return $resultado;
    }

    // ─── Visualizador general ────────────────────────────────────────────────

    public function index(Request $request)
    {
        $anio        = (int) $request->input('anio', date('Y'));
        $periodo     = (int) $request->input('periodo', 1);
        $cursoFiltro = $request->input('curso');
        $busqueda    = $request->input('busqueda');
        $ordenSelec  = $request->input('orden', 'apellido');

        $derroteros = $this->calcularDerroteros($periodo, $anio, $cursoFiltro, $busqueda, null, true);

        $derroteros = match ($ordenSelec) {
            'codigo'   => $derroteros->sortKeys(),
            'perdidas' => $derroteros->sortByDesc(fn($ms) => $ms->count()),
            default    => $derroteros->sortBy(fn($ms) => strtolower(
                ($ms->first()->APELLIDO1 ?? '') . ' ' .
                ($ms->first()->APELLIDO2 ?? '') . ' ' .
                ($ms->first()->NOMBRE1   ?? '')
            )),
        };

        $cursos = DB::table('ESTUDIANTES')
            ->where('ESTADO', 'MATRICULADO')
            ->distinct()->orderBy('CURSO')->pluck('CURSO');

        return view('derroteros.index', compact(
            'derroteros', 'anio', 'periodo', 'cursoFiltro', 'busqueda', 'cursos', 'ordenSelec'
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
            $queryAsig->where('a.CODIGO_DOC', $profile);
        }

        $asignaciones = $queryAsig->orderBy('m.NOMBRE_MAT')->orderBy('a.CURSO')->get();
        $materias     = $asignaciones->unique('CODIGO_MAT')->values();

        $matSelec     = $request->input('materia') ? (int) $request->input('materia') : null;
        $cursoSelec   = $request->input('curso');
        $periodoSelec = (int) $request->input('periodo', 1);
        $ordenSelec   = $request->input('orden', 'apellido');
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

        // Ventana de sustentación de recuperaciones (del calendario académico).
        // SuperAd/Admin se saltan el bloqueo.
        $recupFecha   = FechasController::fechaRecuperacion($periodoSelec, $anio);
        $recupAbierto = $esSuperior || FechasController::recuperacionAbierta($periodoSelec, $anio);

        // Vista logística: todas las recuperaciones del docente con franja
        // asignada, agrupadas por bloque para saber de un vistazo qué
        // estudiantes debían presentarse en cada franja del día.
        $programadasPorBloque = $this->cargarProgramadasPorBloque($profile, $esSuperior, $periodoSelec, $anio);
        $franjasMap           = self::franjas();

        // Derroteros de la materia/curso seleccionada (agrupados por alumno)
        $derroteros = collect();
        if ($matSelec && $cursoSelec) {
            $derroteros = $this->calcularDerroteros($periodoSelec, $anio, $cursoSelec, null, $matSelec);

            $derroteros = match ($ordenSelec) {
                'codigo'  => $derroteros->sortKeys(),
                // Franjas asignadas primero (1..8), sin asignar al final.
                'horario' => $derroteros->sortBy(function ($ms) {
                    $f = $ms->first()->franja ?? null;
                    return $f === null ? 999 : (int) $f;
                }),
                default  => $derroteros->sortBy(fn($ms) => strtolower(
                    ($ms->first()->APELLIDO1 ?? '') . ' ' .
                    ($ms->first()->APELLIDO2 ?? '') . ' ' .
                    ($ms->first()->NOMBRE1   ?? '')
                )),
            };
        }

        return view('derroteros.docente', compact(
            'materias', 'cursosDisponibles', 'matSelec', 'cursoSelec',
            'periodoSelec', 'anio', 'mapaMateriasCursos', 'materiaNombre',
            'derroteros', 'ordenSelec', 'recupAbierto', 'recupFecha', 'esSuperior',
            'programadasPorBloque', 'franjasMap'
        ));
    }

    /**
     * Lista de recuperaciones con FRANJA asignada para el período/año,
     * filtrada por las asignaciones del docente (SuperAd/Admin ven todas).
     * Reutiliza calcularDerroteros() para arrastrar elegibilidad, fallas
     * previas y nota intermedia sugerida (necesarias para resolver desde
     * la vista por bloque). Agrupada por FRANJA y ordenada por apellido.
     */
    private function cargarProgramadasPorBloque(string $profile, bool $esSuperior, int $periodo, int $anio): \Illuminate\Support\Collection
    {
        $grupos = $this->calcularDerroteros($periodo, $anio, null, null, null, true);

        $items = collect();
        foreach ($grupos as $materias) {
            foreach ($materias as $m) {
                if ($m->franja === null || $m->franja === '') continue;
                $items->push($m);
            }
        }

        if ($items->isEmpty()) {
            return collect();
        }

        if (!$esSuperior) {
            $codigosMat = $items->pluck('CODIGO_MAT')->unique()->toArray();
            $asigDoc    = DB::table('ASIGNACION_PCM')
                ->where('CODIGO_DOC', $profile)
                ->where('calificable', 1)
                ->whereIn('CODIGO_MAT', $codigosMat)
                ->get(['CODIGO_MAT', 'CURSO']);

            $matCursoBase = $asigDoc->map(function ($a) {
                $base = explode('-', (string) $a->CURSO)[0];
                return $a->CODIGO_MAT . '_' . $base;
            })->unique()->values()->toArray();

            $items = $items->filter(function ($r) use ($matCursoBase) {
                $base = explode('-', (string) $r->CURSO)[0];
                return in_array($r->CODIGO_MAT . '_' . $base, $matCursoBase, true);
            })->values();
        }

        return $items
            ->sortBy(fn($r) => strtolower(
                ($r->APELLIDO1 ?? '') . ' ' . ($r->APELLIDO2 ?? '') . ' ' . ($r->NOMBRE1 ?? '')
            ))
            ->groupBy(fn($r) => (int) $r->franja)
            ->sortKeys();
    }

    public function resolver(Request $request)
    {
        $profile       = auth()->user()->PROFILE;
        $esSuperior    = in_array($profile, ['SuperAd', 'Admin']);
        $codigoAlum    = (int) $request->input('CODIGO_ALUM');
        $codigoMat     = (int) $request->input('CODIGO_MAT');
        $periodo       = (int) $request->input('periodo');
        $anio          = (int) date('Y');
        $accion        = $request->input('accion'); // 'asistencia' | 'nota'
        $asistencia    = $request->input('asistencia');   // PRESENTO | NO_PRESENTO
        $resolucion    = $request->input('resolucion');   // RECUPERO | NO_RECUPERO | INTERMEDIO
        $notaIngresada = $request->input('nota_recuperacion');

        if (!$esSuperior && !FechasController::recuperacionAbierta($periodo, $anio)) {
            $fecha = FechasController::fechaRecuperacion($periodo, $anio);
            $msg   = $fecha
                ? "Solo puedes resolver recuperaciones el " . \Carbon\Carbon::parse($fecha)->isoFormat('dddd D [de] MMMM') . " entre 6:30 a. m. y 4:30 p. m."
                : "No hay fecha de sustentación configurada en el calendario para el período {$periodo}.";
            return back()->withErrors(['resolucion' => $msg]);
        }

        $notaOriginal = DB::table($this->tablaNotas($anio))
            ->where('CODIGO_ALUM', $codigoAlum)
            ->where('CODIGO_MAT', $codigoMat)
            ->where('PERIODO', $periodo)
            ->value('NOTA');

        $existe = DB::table('Derroteros')
            ->where('CODIGO_ALUM', $codigoAlum)
            ->where('CODIGO_MAT', $codigoMat)
            ->where('PERIODO', $periodo)
            ->where('ANIO', $anio)
            ->exists();

        // Acción 1: registrar/cambiar asistencia (sin tocar la nota)
        if ($accion === 'asistencia') {
            if (!in_array($asistencia, ['PRESENTO', 'NO_PRESENTO'])) {
                return back()->withErrors(['resolucion' => 'Asistencia inválida.']);
            }

            $datos = [
                'ASISTENCIA'    => $asistencia,
                'NOTA_ORIGINAL' => $notaOriginal,
                'CODIGO_DOC'    => $profile,
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
                    'RESOLUCION'  => 'PENDIENTE',
                ]));
            }

            return back()->with('success', $asistencia === 'PRESENTO'
                ? 'Se registró que el estudiante presentó la recuperación.'
                : 'Se registró que el estudiante no presentó la recuperación.');
        }

        // Acción 2: registrar nota (independiente de la asistencia)
        if (!in_array($resolucion, ['RECUPERO', 'NO_RECUPERO', 'INTERMEDIO'])) {
            return back()->withErrors(['resolucion' => 'Resolución inválida.']);
        }

        $notaFinal = match($resolucion) {
            'RECUPERO'    => 7.0,
            'NO_RECUPERO' => $notaOriginal,
            'INTERMEDIO'  => (float) $notaIngresada,
        };

        if ($resolucion === 'INTERMEDIO') {
            if ($notaFinal <= $notaOriginal || $notaFinal > 7) {
                return back()->withErrors(['resolucion' => "La nota intermedia debe ser mayor a {$notaOriginal} y no mayor a 7."]);
            }
        }

        $datos = [
            'RESOLUCION'        => $resolucion,
            'NOTA_RECUPERACION' => $notaFinal,
            'NOTA_ORIGINAL'     => $notaOriginal,
            'CODIGO_DOC'        => $profile,
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

        $tipoNota = $resolucion === 'NO_RECUPERO' ? 'N' : 'R';

        DB::table($this->tablaNotas($anio))
            ->where('CODIGO_ALUM', $codigoAlum)
            ->where('CODIGO_MAT', $codigoMat)
            ->where('PERIODO', $periodo)
            ->update(['NOTA' => $notaFinal, 'TIPODENOTA' => $tipoNota]);

        return back()->with('success', 'Nota de recuperación guardada correctamente.');
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

    // ─── Tablero de franjas (drag & drop) ────────────────────────────────────

    /**
     * Arma la lista de tarjetas (alumno, materia) pendientes de recuperación,
     * cada una con su docente (desde ASIGNACION_PCM) y franja actual si la hay.
     */
    private function cargarTarjetasTablero(int $periodo, int $anio): array
    {
        $grupos = $this->calcularDerroteros($periodo, $anio, null, null, null, false);

        $items = collect();
        foreach ($grupos as $materias) {
            foreach ($materias as $m) {
                if (!$m->elegible) continue;
                if ($m->resolucion !== 'PENDIENTE') continue;
                $items->push($m);
            }
        }

        if ($items->isEmpty()) {
            return ['cards' => collect(), 'docentes' => collect()];
        }

        $codigosAlum = $items->pluck('CODIGO_ALUM')->unique()->toArray();
        $codigosMat  = $items->pluck('CODIGO_MAT')->unique()->toArray();

        // FRANJA e id actuales (si ya se persistió)
        $derrRows = DB::table('Derroteros')
            ->whereIn('CODIGO_ALUM', $codigosAlum)
            ->whereIn('CODIGO_MAT', $codigosMat)
            ->where('PERIODO', $periodo)
            ->where('ANIO', $anio)
            ->get()
            ->keyBy(fn($r) => $r->CODIGO_ALUM . '_' . $r->CODIGO_MAT);

        // Docente por (curso base, materia) desde ASIGNACION_PCM
        $asig = DB::table('ASIGNACION_PCM')
            ->whereIn('CODIGO_MAT', $codigosMat)
            ->where('calificable', 1)
            ->get()
            ->groupBy(fn($a) => (explode('-', (string) $a->CURSO)[0] ?? '') . '_' . $a->CODIGO_MAT);

        $docCodigos = $asig->flatten()->pluck('CODIGO_DOC')->unique()->filter()->toArray();
        $docentesMap = DB::table('CODIGOS_DOC')
            ->whereIn('CODIGO_DOC', $docCodigos)
            ->pluck('NOMBRE_DOC', 'CODIGO_DOC');

        $cards = $items->map(function ($m) use ($derrRows, $asig, $docentesMap) {
            $key     = $m->CODIGO_ALUM . '_' . $m->CODIGO_MAT;
            $d       = $derrRows[$key] ?? null;
            $asigKey = $m->CURSO . '_' . $m->CODIGO_MAT;
            $aRow    = isset($asig[$asigKey]) ? $asig[$asigKey]->first() : null;
            $docCod  = $aRow->CODIGO_DOC ?? null;
            $grado   = preg_match('/^(\d+)/', (string) $m->CURSO, $gm) ? $gm[1] : (string) $m->CURSO;

            return (object) [
                'id'          => $d->id ?? null,
                'codigo_alum' => (int) $m->CODIGO_ALUM,
                'codigo_mat'  => (int) $m->CODIGO_MAT,
                'nombre'      => trim("{$m->APELLIDO1} {$m->APELLIDO2} {$m->NOMBRE1} {$m->NOMBRE2}"),
                'curso'       => $m->CURSO,
                'grado'       => $grado,
                'materia'     => $m->NOMBRE_MAT,
                'franja'      => $d->FRANJA ?? null,
                'docente_cod' => $docCod,
                'docente_nom' => $docCod ? ($docentesMap[$docCod] ?? $docCod) : 'Sin docente asignado',
            ];
        })->values();

        // Filas del tablero: un docente por fila (solo los que tienen tarjetas)
        $docentes = $cards->groupBy('docente_cod')->map(function ($tarjetas, $codDoc) use ($docentesMap) {
            return (object) [
                'codigo' => $codDoc,
                'nombre' => $codDoc ? ($docentesMap[$codDoc] ?? $codDoc) : 'Sin docente',
            ];
        })->sortBy(fn($d) => $d->nombre)->values();

        return ['cards' => $cards, 'docentes' => $docentes];
    }

    public function tablero(Request $request)
    {
        $anio    = (int) $request->input('anio', date('Y'));
        $periodo = (int) $request->input('periodo', 1);

        // La fecha es solo etiqueta: se usa al publicar el texto HORARIO.
        // Si el usuario no la envía, sugerimos la del calendario académico.
        $fechaSel = $request->input('fecha')
            ?: FechasController::fechaRecuperacion($periodo, $anio);

        $data = $this->cargarTarjetasTablero($periodo, $anio);

        return view('derroteros.tablero', [
            'cards'    => $data['cards'],
            'docentes' => $data['docentes'],
            'franjas'  => self::franjas(),
            'anio'     => $anio,
            'periodo'  => $periodo,
            'fechaSel' => $fechaSel,
        ]);
    }

    /**
     * Autoguardado silencioso del tablero. Persiste únicamente FRANJA y
     * deja HORARIO en NULL (el horario visible para padres y docentes se
     * publica solo cuando el usuario presiona "Confirmar asignación").
     */
    public function tableroGuardar(Request $request)
    {
        $anio    = (int) $request->input('anio', date('Y'));
        $periodo = (int) $request->input('periodo', 1);
        $items   = $request->input('items', []);

        if (!is_array($items)) {
            return response()->json(['ok' => false, 'msg' => 'Datos inválidos.'], 422);
        }

        foreach ($items as $it) {
            $codAlum = (int) ($it['codigo_alum'] ?? 0);
            $codMat  = (int) ($it['codigo_mat']  ?? 0);
            $franja  = isset($it['franja']) && $it['franja'] !== null && $it['franja'] !== ''
                ? (int) $it['franja'] : null;

            if (!$codAlum || !$codMat) continue;

            $existe = DB::table('Derroteros')
                ->where('CODIGO_ALUM', $codAlum)
                ->where('CODIGO_MAT', $codMat)
                ->where('PERIODO', $periodo)
                ->where('ANIO', $anio)
                ->exists();

            // HORARIO se invalida (NULL) en cada autoguardado: cualquier
            // cambio obliga a volver a confirmar para volver a publicar.
            if ($existe) {
                DB::table('Derroteros')
                    ->where('CODIGO_ALUM', $codAlum)->where('CODIGO_MAT', $codMat)
                    ->where('PERIODO', $periodo)->where('ANIO', $anio)
                    ->update(['FRANJA' => $franja, 'HORARIO' => null]);
            } else {
                DB::table('Derroteros')->insert([
                    'CODIGO_ALUM' => $codAlum,
                    'CODIGO_MAT'  => $codMat,
                    'PERIODO'     => $periodo,
                    'ANIO'        => $anio,
                    'RESOLUCION'  => 'PENDIENTE',
                    'FRANJA'      => $franja,
                    'HORARIO'     => null,
                ]);
            }
        }

        return response()->json(['ok' => true, 'guardados' => count($items)]);
    }

    /**
     * Publica los horarios: recompone el texto HORARIO desde FRANJA para
     * todas las filas del período/año. Solo después de confirmar el horario
     * queda visible para padres y docentes.
     */
    public function tableroConfirmar(Request $request)
    {
        $anio    = (int) $request->input('anio', date('Y'));
        $periodo = (int) $request->input('periodo', 1);

        // Fecha elegida por el usuario en el tablero; fallback al calendario.
        $fecha = $request->input('fecha')
            ?: FechasController::fechaRecuperacion($periodo, $anio);
        $fechaTxt = $fecha
            ? \Carbon\Carbon::parse($fecha)->locale('es')->isoFormat('dddd D [de] MMMM')
            : null;

        $filas = DB::table('Derroteros')
            ->where('PERIODO', $periodo)
            ->where('ANIO', $anio)
            ->get();

        if ($filas->isEmpty()) {
            return response()->json(['ok' => true, 'publicados' => 0, 'sin_franja' => 0]);
        }

        $codigosMat  = $filas->pluck('CODIGO_MAT')->unique()->filter()->toArray();
        $codigosAlum = $filas->pluck('CODIGO_ALUM')->unique()->filter()->toArray();
        $cursosAlum  = DB::table('ESTUDIANTES')
            ->whereIn('CODIGO', $codigosAlum)
            ->pluck('CURSO', 'CODIGO');
        $asig = DB::table('ASIGNACION_PCM')
            ->whereIn('CODIGO_MAT', $codigosMat)
            ->where('calificable', 1)
            ->get()
            ->groupBy(fn($a) => (explode('-', (string) $a->CURSO)[0] ?? '') . '_' . $a->CODIGO_MAT);
        $docCodigos = $asig->flatten()->pluck('CODIGO_DOC')->unique()->filter()->toArray();
        $docNombres = DB::table('CODIGOS_DOC')
            ->whereIn('CODIGO_DOC', $docCodigos)
            ->pluck('NOMBRE_DOC', 'CODIGO_DOC');

        $publicados = 0;
        $sinFranja  = 0;
        $franjasMap = self::franjas();
        foreach ($filas as $r) {
            $franja = $r->FRANJA !== null ? (int) $r->FRANJA : null;
            if (!$franja || !isset($franjasMap[$franja])) { $sinFranja++; continue; }

            $curso = $cursosAlum[$r->CODIGO_ALUM] ?? '';
            $base  = explode('-', (string) $curso)[0] ?? '';
            $aRow  = isset($asig[$base . '_' . $r->CODIGO_MAT]) ? $asig[$base . '_' . $r->CODIGO_MAT]->first() : null;
            $docTx = $aRow ? ($docNombres[$aRow->CODIGO_DOC] ?? $aRow->CODIGO_DOC) : '';
            $partes = array_filter([
                $fechaTxt,
                $franjasMap[$franja],
                $docTx ? 'Prof. ' . $docTx : null,
            ]);
            $horarioTxt = implode(' · ', $partes);

            DB::table('Derroteros')->where('id', $r->id)->update(['HORARIO' => $horarioTxt]);
            $publicados++;
        }

        return response()->json([
            'ok'          => true,
            'publicados'  => $publicados,
            'sin_franja'  => $sinFranja,
        ]);
    }

    /**
     * Autoasignador greedy por sesión (curso + materia + docente): todos los
     * estudiantes de la misma sesión caen en la misma franja. Prioriza las
     * sesiones con estudiantes que tienen más materias (ellos restringen
     * más el horario). Respeta fijadas y evita choques de estudiante.
     */
    public function tableroAutoAsignar(Request $request)
    {
        $anio    = (int) $request->input('anio', date('Y'));
        $periodo = (int) $request->input('periodo', 1);
        $fijadas = $request->input('fijadas', []);

        $data  = $this->cargarTarjetasTablero($periodo, $anio);
        $cards = $data['cards'];

        // fijadas por (alum,mat)
        $fijMap = [];
        foreach ($fijadas as $f) {
            $k = ($f['codigo_alum'] ?? '') . '_' . ($f['codigo_mat'] ?? '');
            if (!empty($f['franja'])) $fijMap[$k] = (int) $f['franja'];
        }

        // Conteo de materias por alumno (para priorizar sesiones donde caen los "difíciles")
        $conteoPorAlum = $cards->groupBy('codigo_alum')->map->count();

        // Agrupar por sesión: misma (materia, grado, docente).
        // Cursos del mismo grado con el mismo docente quedan juntos para
        // evitar filtraciones del examen entre secciones del mismo grado.
        $sesiones = $cards->groupBy(fn($c) => $c->codigo_mat . '|' . $c->grado . '|' . ($c->docente_cod ?? ''));

        $infoSes = [];
        foreach ($sesiones as $key => $tarjetas) {
            $franjaLock = null;
            foreach ($tarjetas as $c) {
                $kk = $c->codigo_alum . '_' . $c->codigo_mat;
                if (isset($fijMap[$kk]) && $franjaLock === null) {
                    $franjaLock = $fijMap[$kk];
                }
            }
            $first = $tarjetas->first();
            $infoSes[$key] = [
                'tarjetas' => $tarjetas,
                'franja'   => $franjaLock,
                'score'    => $tarjetas->sum(fn($c) => $conteoPorAlum[$c->codigo_alum] ?? 1),
                'docente'  => $first->docente_cod,
                'grado'    => $first->grado,
                'mat'      => (int) $first->codigo_mat,
            ];
        }

        $ocupadoAlum   = [];  // "alum_franja" => true
        $gradosDocFr   = [];  // "doc_franja" => [grado => count]
        $materiasDocFr = [];  // "doc_franja" => [mat => count]
        $resultado     = [];
        $sinCupo       = [];

        $asignarSesion = function(array $info, int $franja, bool $fijada) use (&$ocupadoAlum, &$gradosDocFr, &$materiasDocFr, &$resultado, &$sinCupo) {
            foreach ($info['tarjetas'] as $c) {
                $ak = $c->codigo_alum . '_' . $franja;
                if (isset($ocupadoAlum[$ak])) {
                    $sinCupo[] = [
                        'nombre'  => $c->nombre,
                        'materia' => $c->materia,
                        'razon'   => 'Choque en franja fijada con otra materia del mismo estudiante',
                    ];
                    continue;
                }
                $ocupadoAlum[$ak] = true;
                $gradosDocFr[$info['docente'] . '_' . $franja][$info['grado']]
                    = ($gradosDocFr[$info['docente'] . '_' . $franja][$info['grado']] ?? 0) + 1;
                $materiasDocFr[$info['docente'] . '_' . $franja][$info['mat']]
                    = ($materiasDocFr[$info['docente'] . '_' . $franja][$info['mat']] ?? 0) + 1;
                $resultado[] = [
                    'codigo_alum' => $c->codigo_alum,
                    'codigo_mat'  => $c->codigo_mat,
                    'franja'      => $franja,
                    'fijada'      => $fijada,
                ];
            }
        };

        // Procesar primero las sesiones con franja fijada
        foreach ($infoSes as $key => $info) {
            if ($info['franja'] !== null) {
                $asignarSesion($info, $info['franja'], true);
                $infoSes[$key]['procesada'] = true;
            }
        }

        // Sesiones pendientes, ordenadas por score desc (sesiones con estudiantes difíciles primero)
        $pendientes = collect($infoSes)
            ->filter(fn($s) => empty($s['procesada']))
            ->sortByDesc('score');

        $franjasLista = array_keys(self::franjas());

        foreach ($pendientes as $key => $info) {
            // Franjas donde ningún estudiante de la sesión tenga choque
            $viables = [];
            foreach ($franjasLista as $f) {
                $choque = false;
                foreach ($info['tarjetas'] as $c) {
                    if (isset($ocupadoAlum[$c->codigo_alum . '_' . $f])) { $choque = true; break; }
                }
                if (!$choque) $viables[] = $f;
            }

            if (empty($viables)) {
                foreach ($info['tarjetas'] as $c) {
                    $sinCupo[] = [
                        'nombre'  => $c->nombre,
                        'materia' => $c->materia,
                        'razon'   => 'Choque con otras materias del mismo estudiante',
                    ];
                }
                continue;
            }

            // Elegir mejor franja para la sesión. Con la sesión ya agrupada
            // por (materia, grado, docente), los cursos del mismo grado
            // siempre quedan juntos. El scoring penaliza mezclar grados
            // distintos del mismo docente en la misma franja.
            //   A) misma materia + mismo grado → continúa la sesión  (+200)
            //   C) docente libre (franja limpia)                     (+150)
            //   B) misma materia + grado distinto (último recurso)   (+40)
            //   D) docente con OTRA materia → casi prohibido         (−500)
            $mejor = null;
            $mejorScore = PHP_INT_MIN;
            foreach ($viables as $f) {
                $grados    = $gradosDocFr[$info['docente']   . '_' . $f] ?? [];
                $mats      = $materiasDocFr[$info['docente'] . '_' . $f] ?? [];
                $mismoGr   = $grados[$info['grado']] ?? 0;
                $otroGr    = array_sum($grados) - $mismoGr;
                $mismaMat  = $mats[$info['mat']] ?? 0;
                $otraMat   = array_sum($mats) - $mismaMat;
                $total     = array_sum($grados);

                $score = 0;
                if ($otraMat > 0)                         $score -= 500;  // D
                if ($mismaMat > 0 && $mismoGr > 0)        $score += 200;  // A
                elseif ($total === 0)                     $score += 150;  // C
                elseif ($mismaMat > 0 && $mismoGr === 0)  $score += 40;   // B
                $score -= $otroGr * 5;    // penaliza mezclar grados distintos
                $score -= $total;

                if ($score > $mejorScore) { $mejor = $f; $mejorScore = $score; }
            }

            $asignarSesion($info, $mejor, false);
        }

        return response()->json([
            'ok'        => true,
            'asignadas' => $resultado,
            'sin_cupo'  => $sinCupo,
        ]);
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
                    ->where(function ($q) {
                        // Pérdida original: sigue < 7 o fue recuperada (TIPODENOTA='R')
                        $q->where('NOTA', '<', 7)
                          ->orWhere('TIPODENOTA', 'R');
                    })
                    ->select('CODIGO_MAT', DB::raw('COUNT(*) as veces'))
                    ->groupBy('CODIGO_MAT')
                    ->pluck('veces', 'CODIGO_MAT');
            } catch (\Exception $e) {}
        }

        // Resoluciones (incluye HORARIO)
        $resoluciones = DB::table('Derroteros')
            ->where('CODIGO_ALUM', $codigo)
            ->where('PERIODO', $periodo)
            ->where('ANIO', $anio)
            ->get()
            ->keyBy('CODIGO_MAT');

        // Aplicar elegibilidad
        $fallos = $fallos->map(function ($f) use ($fallasPrevias, $resoluciones) {
            $previas = $fallasPrevias[$f->CODIGO_MAT] ?? 0;

            if ($f->NOTA < 5)       { $f->elegible = false; $f->razon = 'Nota inferior a 5.0'; }
            elseif ($previas >= 2)  { $f->elegible = false; $f->razon = "Perdida {$previas} veces antes"; }
            else                    { $f->elegible = true;  $f->razon = null; }

            $res = $resoluciones[$f->CODIGO_MAT] ?? null;
            $f->resolucion = $res->RESOLUCION ?? 'PENDIENTE';
            $f->horario    = $res->HORARIO    ?? null;
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

        // Banner: horarios aún no subidos (ninguna materia tiene horario asignado)
        $horariosPendientes = $derroteros->isNotEmpty() && $derroteros->every(fn($m) => empty($m->horario));

        $curso = $estudiante->CURSO ?? '';
        $urlsSite = $derroteros->pluck('CODIGO_MAT')->unique()
            ->mapWithKeys(fn($cm) => [$cm => \App\Http\Controllers\PadresController::urlSite((int)$cm, $curso)])
            ->toArray();

        return view('derroteros.padres', compact('derroteros', 'anio', 'periodo', 'bloqueado', 'urlsSite', 'horariosPendientes'));
    }
}
