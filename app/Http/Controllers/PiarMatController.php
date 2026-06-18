<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ControlFechasController;
use App\Http\Controllers\NotificacionesController;

class PiarMatController extends Controller
{
    private function esDocente(): bool
    {
        $profile = auth()->user()->PROFILE;
        return str_starts_with($profile, 'DOC') || str_starts_with($profile, 'COR');
    }

    private function codigoDoc(): string
    {
        return auth()->user()->PROFILE;
    }

    // ── Utilidades para notificaciones PIAR (Anexo 2) ────────────────────────
    private function etiquetaEstudiante(string $codigo): string
    {
        $e = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)
            ->select('NOMBRE1','APELLIDO1','APELLIDO2')->first();
        if (!$e) return $codigo;
        $nombre = trim(($e->NOMBRE1 ?? '') . ' ' . ($e->APELLIDO1 ?? '') . ' ' . ($e->APELLIDO2 ?? ''));
        return "{$nombre} ({$codigo})";
    }

    private function nombreMateria(int $codigoMat): string
    {
        return DB::table('CODIGOSMAT')->where('CODIGO_MAT', $codigoMat)->value('NOMBRE_MAT') ?? "Materia {$codigoMat}";
    }

    private function docentesAsignados(string $codigo, int $codigoMat): array
    {
        $curso = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->value('CURSO');
        if (!$curso) return [];
        $cursos = $this->cursosAplicables($codigo, $curso);
        return DB::table('ASIGNACION_PCM')
            ->whereIn('CURSO', $cursos)
            ->where('CODIGO_MAT', $codigoMat)
            ->pluck('CODIGO_EMP')
            ->unique()
            ->filter()
            ->values()
            ->all();
    }

    // ── Lista de estudiantes con PIAR para este docente ──────────────────────
    public function index()
    {
        $codigoDoc = $this->codigoDoc();
        $esDocente = $this->esDocente();

        // Estudiantes con PIAR + materias asignadas al docente
        // El join con ASIGNACION_PCM empareja por curso base del estudiante O por grupos
        // de LISTADOS_ESPECIALES (Artes/Música 7°+ con -1/-2, Proyectos).
        $query = DB::table('ESTUDIANTES as e')
            ->join('PIAR_DIAG as pd', 'pd.CODIGO_ALUM', '=', 'e.CODIGO')
            ->leftJoin('LISTADOS_ESPECIALES as le', 'le.CODIGO_ALUM', '=', 'e.CODIGO')
            ->join(DB::raw('(SELECT DISTINCT CODIGO_EMP, CODIGO_MAT, CURSO FROM ASIGNACION_PCM) as a'), function ($j) {
                $j->whereRaw('(a.CURSO = e.CURSO OR a.CURSO = le.GRUPO)');
            })
            ->join('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'a.CODIGO_MAT')
            ->leftJoin('PIAR_MAT as pm', function ($j) {
                $j->on('pm.CODIGO_ALUM', '=', 'e.CODIGO')
                  ->on('pm.CODIGO_MAT',  '=', 'a.CODIGO_MAT');
            })
            ->select(
                'e.CODIGO', 'e.NOMBRE1', 'e.NOMBRE2', 'e.APELLIDO1', 'e.APELLIDO2',
                'e.GRADO', 'e.CURSO',
                'a.CODIGO_MAT', 'm.NOMBRE_MAT',
                'pd.DIAGNOSTICO',
                DB::raw('CASE WHEN pm.CODIGO_ALUM IS NOT NULL THEN 1 ELSE 0 END as DILIGENCIADO')
            )
            ->where('e.ESTADO', 'MATRICULADO')
            ->groupBy(
                'e.CODIGO', 'e.NOMBRE1', 'e.NOMBRE2', 'e.APELLIDO1', 'e.APELLIDO2',
                'e.GRADO', 'e.CURSO', 'a.CODIGO_MAT', 'm.NOMBRE_MAT', 'pd.DIAGNOSTICO'
            );

        $matsExcluidas = [24, 31, 35, 124, 135, 153]; // Urbanidad y Cívica, Proyectos, Cátedra de Paz, Urbanidad y Cívica PE, Cátedra de Paz PE, Pensamiento Lógico
        $query->whereNotIn('a.CODIGO_MAT', $matsExcluidas);

        if ($esDocente) {
            $query->where('a.CODIGO_EMP', $codigoDoc);
        }

        $filas = $query->orderBy('m.NOMBRE_MAT')->orderBy('e.APELLIDO1')->orderBy('e.NOMBRE1')->get();

        // Agrupar por materia
        $porMateria = $filas->groupBy('NOMBRE_MAT');

        return view('piar.anexo2.index', compact('porMateria', 'esDocente'));
    }

    // ── Formulario para llenar el Anexo 2 de una materia-estudiante ──────────
    public function form(string $codigo, int $codigoMat)
    {
        $codigoDoc = $this->codigoDoc();
        $esDocente = $this->esDocente();

        // Validar que el docente tenga acceso a esta combinación
        if ($esDocente) {
            $estudiante = DB::table('ESTUDIANTES as e')
                ->leftJoin('LISTADOS_ESPECIALES as le', 'le.CODIGO_ALUM', '=', 'e.CODIGO')
                ->join('ASIGNACION_PCM as a', function ($j) use ($codigoDoc, $codigoMat) {
                    $j->where('a.CODIGO_EMP', $codigoDoc)
                      ->where('a.CODIGO_MAT', $codigoMat)
                      ->whereRaw('(a.CURSO = e.CURSO OR a.CURSO = le.GRUPO)');
                })
                ->where('e.CODIGO', $codigo)
                ->select('e.*')
                ->first();
        } else {
            $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();
        }

        if (!$estudiante) abort(403);

        $materia  = DB::table('CODIGOSMAT')->where('CODIGO_MAT', $codigoMat)->first();
        $docente  = DB::table('CODIGOS_DOC')->where('CODIGO_EMP', $codigoDoc)->first();
        $piarDiag = DB::table('PIAR_DIAG')->where('CODIGO_ALUM', $codigo)->first();
        $piarMat  = DB::table('PIAR_MAT')
                        ->where('CODIGO_ALUM', $codigo)
                        ->where('CODIGO_MAT', $codigoMat)
                        ->first();

        // Datos del estudiante
        $nombreCompleto = trim("{$estudiante->NOMBRE1} {$estudiante->NOMBRE2}");
        $apellidos      = trim("{$estudiante->APELLIDO1} {$estudiante->APELLIDO2}");
        $numId = $estudiante->TAR_ID ?? $estudiante->REG_CIVIL ?? '';
        $edad  = $estudiante->EDAD ?? '';
        $grado = $estudiante->GRADO ?? '';

        $fechaNac = '';
        if ($estudiante->FECH_NACIMIENTO ?? null) {
            try { $fechaNac = \Carbon\Carbon::parse($estudiante->FECH_NACIMIENTO)->locale('es')->isoFormat('D [de] MMMM [de] YYYY'); }
            catch (\Exception $e) { $fechaNac = $estudiante->FECH_NACIMIENTO; }
        }

        $v = fn($campo, $default = '') => ($piarMat && $piarMat->$campo !== null && $piarMat->$campo !== '')
            ? $piarMat->$campo : $default;

        $estadoEtapa   = ControlFechasController::estadoEtapa('ajustes');
        $periodoActivo = ControlFechasController::periodoActivo();

        return view('piar.anexo2.form', compact(
            'estudiante', 'materia', 'docente', 'piarDiag', 'piarMat',
            'nombreCompleto', 'apellidos', 'numId', 'edad', 'grado', 'fechaNac',
            'v', 'codigoMat', 'estadoEtapa', 'periodoActivo'
        ));
    }

    // ── Vista de impresión ───────────────────────────────────────────────────
    public function imprimir(Request $request, string $codigo, int $codigoMat)
    {
        $periodoImp = (int) $request->query('periodo', 0);
        if (!in_array($periodoImp, [1, 2, 3, 4])) $periodoImp = 0; // 0 = todos los períodos
        // Reutiliza la misma lógica de form() pero retorna la vista de impresión
        $codigoDoc = $this->codigoDoc();
        $esDocente = $this->esDocente();

        if ($esDocente) {
            $estudiante = DB::table('ESTUDIANTES as e')
                ->leftJoin('LISTADOS_ESPECIALES as le', 'le.CODIGO_ALUM', '=', 'e.CODIGO')
                ->join('ASIGNACION_PCM as a', function ($j) use ($codigoDoc, $codigoMat) {
                    $j->where('a.CODIGO_EMP', $codigoDoc)
                      ->where('a.CODIGO_MAT', $codigoMat)
                      ->whereRaw('(a.CURSO = e.CURSO OR a.CURSO = le.GRUPO)');
                })
                ->where('e.CODIGO', $codigo)
                ->select('e.*')
                ->first();
        } else {
            $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();
        }

        if (!$estudiante) abort(403);

        $materia  = DB::table('CODIGOSMAT')->where('CODIGO_MAT', $codigoMat)->first();
        $docente  = DB::table('CODIGOS_DOC')->where('CODIGO_EMP', $codigoDoc)->first();
        $piarDiag = DB::table('PIAR_DIAG')->where('CODIGO_ALUM', $codigo)->first();
        $piarMat  = DB::table('PIAR_MAT')
                        ->where('CODIGO_ALUM', $codigo)
                        ->where('CODIGO_MAT', $codigoMat)
                        ->first();

        $nombreCompleto = trim("{$estudiante->NOMBRE1} {$estudiante->NOMBRE2}");
        $apellidos      = trim("{$estudiante->APELLIDO1} {$estudiante->APELLIDO2}");
        $numId = $estudiante->TAR_ID ?? $estudiante->REG_CIVIL ?? '';
        $edad  = $estudiante->EDAD ?? '';
        $grado = $estudiante->GRADO ?? '';

        $fechaNac = '';
        if ($estudiante->FECH_NACIMIENTO ?? null) {
            try { $fechaNac = \Carbon\Carbon::parse($estudiante->FECH_NACIMIENTO)->locale('es')->isoFormat('D [de] MMMM [de] YYYY'); }
            catch (\Exception $e) { $fechaNac = $estudiante->FECH_NACIMIENTO; }
        }

        return view('piar.anexo2.imprimir', compact(
            'estudiante', 'materia', 'docente', 'piarDiag', 'piarMat',
            'nombreCompleto', 'apellidos', 'numId', 'edad', 'grado', 'fechaNac',
            'codigoMat', 'periodoImp'
        ));
    }

    // ── Guardar ──────────────────────────────────────────────────────────────
    public function guardar(Request $request, string $codigo, int $codigoMat)
    {
        $esDocente   = $this->esDocente();
        $estadoEtapa = ControlFechasController::estadoEtapa('ajustes');

        $existingEstado = DB::table('PIAR_MAT')
            ->where('CODIGO_ALUM', $codigo)->where('CODIGO_MAT', $codigoMat)
            ->value('ESTADO') ?? 'pendiente';

        // Orientador envía observaciones → estado con_observaciones
        if (!$esDocente && $request->input('accion') === 'observar') {
            if ($estadoEtapa === 'finalizado') {
                return back()->withErrors(['etapa' => 'La etapa está finalizada.']);
            }
            DB::table('PIAR_MAT')->updateOrInsert(
                ['CODIGO_ALUM' => $codigo, 'CODIGO_MAT' => $codigoMat],
                ['OBSERVACIONES' => $request->OBSERVACIONES, 'ESTADO' => 'con_observaciones', 'updated_at' => now()]
            );

            $materia    = $this->nombreMateria($codigoMat);
            $etiqueta   = $this->etiquetaEstudiante($codigo);
            $url        = route('piar.anexo2.form', [$codigo, $codigoMat]);
            $mensaje    = "{$materia} — {$etiqueta}: hay observaciones pendientes en los ajustes razonables.";
            foreach ($this->docentesAsignados($codigo, $codigoMat) as $doc) {
                NotificacionesController::crear($doc, 'piar_observ', 'Observaciones en Anexo 2', $mensaje, $url);
            }
            return back()->with('saved', 'Observaciones enviadas al docente.');
        }

        // Docente solo puede guardar si etapa abierta O su registro tiene observaciones pendientes
        $tieneObservaciones = $existingEstado === 'con_observaciones';
        if ($esDocente && $estadoEtapa !== 'abierto' && !$tieneObservaciones) {
            $msg = match($estadoEtapa) {
                'cerrado'    => 'La etapa de ajustes razonables está cerrada. No se permiten cambios.',
                'revision'   => 'La etapa está en revisión. El orientador está revisando tu trabajo.',
                'finalizado' => 'La etapa está finalizada. No se permiten más cambios.',
                default      => 'No se pueden guardar cambios en este momento.',
            };
            return back()->withErrors(['etapa' => $msg]);
        }
        if (!$esDocente && $estadoEtapa === 'finalizado') {
            return back()->withErrors(['etapa' => 'La etapa está finalizada. No se permiten más cambios.']);
        }

        $entregar = $request->input('accion') === 'entregar';

        // Si estaba aprobado o con_observaciones y se edita, vuelve a revisión
        $nuevoEstado = $entregar ? 'revision' : (in_array($existingEstado, ['aprobado', 'con_observaciones']) ? 'revision' : ($existingEstado ?? 'pendiente'));

        $datos = [
            'BARRERAS'      => $request->BARRERAS,
            'LOGRO1'        => $request->LOGRO1,
            'DIDACT1'       => $request->DIDACT1,
            'EVAL1'         => $request->EVAL1,
            'LOGRO2'        => $request->LOGRO2,
            'DIDACT2'       => $request->DIDACT2,
            'EVAL2'         => $request->EVAL2,
            'LOGRO3'        => $request->LOGRO3,
            'DIDACT3'       => $request->DIDACT3,
            'EVAL3'         => $request->EVAL3,
            'LOGRO4'        => $request->LOGRO4,
            'DIDACT4'       => $request->DIDACT4,
            'EVAL4'         => $request->EVAL4,
            // El Plan Casero (ESTRAG_CASERA / FREC_CASERA) NO se toca aquí: vive en la
            // misma fila de PIAR_MAT pero lo administra guardarPlanCasero(). El form del
            // Anexo 2 no envía esos campos, así que incluirlos los sobreescribía con NULL
            // y borraba el plan casero al guardar los ajustes razonables.
            'ESTADO'        => $nuevoEstado,
        ];
        if (!$this->esDocente() && $request->has('OBSERVACIONES')) {
            $datos['OBSERVACIONES'] = $request->OBSERVACIONES;
        }

        DB::table('PIAR_MAT')->updateOrInsert(
            ['CODIGO_ALUM' => $codigo, 'CODIGO_MAT' => $codigoMat],
            $datos
        );

        if ($entregar) {
            $materia  = $this->nombreMateria($codigoMat);
            $etiqueta = $this->etiquetaEstudiante($codigo);
            $nomDoc   = DB::table('CODIGOS_DOC')->where('CODIGO_EMP', $this->codigoDoc())->value('NOMBRE_DOC') ?? $this->codigoDoc();
            $url      = route('piar.anexo2.form', [$codigo, $codigoMat]) . '#observaciones';
            $mensaje  = "{$nomDoc} entregó los ajustes razonables de {$etiqueta} en {$materia}.";
            NotificacionesController::crearParaRevisoresPiar('piar_entreg', 'Anexo 2 entregado para revisión', $mensaje, $url);
        }

        $msg = $entregar ? 'Ajustes marcados como entregados para revisión.' : 'PIAR Anexo 2 guardado correctamente.';
        return redirect()->route('piar.anexo2.form', [$codigo, $codigoMat])->with('saved', $msg);
    }

    // ── Plan Casero ──────────────────────────────────────────────────────────
    public function formPlanCasero(Request $request, string $codigo, int $codigoMat)
    {
        $codigoDoc = $this->codigoDoc();
        $esDocente = $this->esDocente();

        if ($esDocente) {
            $estudiante = DB::table('ESTUDIANTES as e')
                ->leftJoin('LISTADOS_ESPECIALES as le', 'le.CODIGO_ALUM', '=', 'e.CODIGO')
                ->join('ASIGNACION_PCM as a', function ($j) use ($codigoDoc, $codigoMat) {
                    $j->where('a.CODIGO_EMP', $codigoDoc)
                      ->where('a.CODIGO_MAT', $codigoMat)
                      ->whereRaw('(a.CURSO = e.CURSO OR a.CURSO = le.GRUPO)');
                })
                ->where('e.CODIGO', $codigo)
                ->select('e.*')
                ->first();
        } else {
            $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();
        }

        if (!$estudiante) abort(403);

        $materia  = DB::table('CODIGOSMAT')->where('CODIGO_MAT', $codigoMat)->first();
        $docente  = DB::table('CODIGOS_DOC')->where('CODIGO_EMP', $codigoDoc)->first();
        $piarDiag = DB::table('PIAR_DIAG')->where('CODIGO_ALUM', $codigo)->first();
        $piarMat  = DB::table('PIAR_MAT')
                        ->where('CODIGO_ALUM', $codigo)
                        ->where('CODIGO_MAT', $codigoMat)
                        ->first();
        $caract   = DB::table('PIAR_CARACT_MAT')
                        ->where('CODIGO_ALUM', $codigo)
                        ->where('CODIGO_MAT', $codigoMat)
                        ->first();

        // Plan casero por período: registros indexados por PERIODO (1-4)
        $planesPorPeriodo = DB::table('PIAR_PLAN_CASERO')
            ->where('CODIGO_ALUM', $codigo)
            ->where('CODIGO_MAT', $codigoMat)
            ->get()
            ->keyBy('PERIODO');

        $periodoActivo = ControlFechasController::periodoActivo();
        $periodoVista  = (int) $request->query('periodo', $periodoActivo);
        if (!in_array($periodoVista, [1, 2, 3, 4])) $periodoVista = $periodoActivo;

        $nombreCompleto = trim("{$estudiante->NOMBRE1} {$estudiante->NOMBRE2}");
        $apellidos      = trim("{$estudiante->APELLIDO1} {$estudiante->APELLIDO2}");
        $estadoEtapa    = ControlFechasController::estadoEtapa('plan_casero');

        return view('piar.plan-casero.form', compact(
            'estudiante', 'materia', 'docente', 'piarDiag', 'piarMat', 'caract',
            'planesPorPeriodo', 'periodoActivo', 'periodoVista',
            'nombreCompleto', 'apellidos', 'codigoMat', 'estadoEtapa'
        ));
    }

    public function guardarPlanCasero(Request $request, string $codigo, int $codigoMat)
    {
        $esDocente   = $this->esDocente();
        $estadoEtapa = ControlFechasController::estadoEtapa('plan_casero');
        $periodoActivo = ControlFechasController::periodoActivo();

        // Período objetivo del guardado. El docente solo puede tocar el período activo;
        // el orientador puede registrar observaciones sobre el período que esté viendo.
        $periodo = (int) $request->input('PERIODO', $periodoActivo);
        if (!in_array($periodo, [1, 2, 3, 4])) $periodo = $periodoActivo;
        if ($esDocente) $periodo = $periodoActivo;

        $clave = ['CODIGO_ALUM' => $codigo, 'CODIGO_MAT' => $codigoMat, 'PERIODO' => $periodo];

        $existingEstado = DB::table('PIAR_PLAN_CASERO')
            ->where($clave)
            ->value('ESTADO') ?? 'pendiente';

        $volverUrl = route('piar.plan_casero.form', [$codigo, $codigoMat]) . '?periodo=' . $periodo;

        // Orientador envía observaciones → con_observaciones
        if (!$esDocente && $request->input('accion') === 'observar') {
            if ($estadoEtapa === 'finalizado') {
                return back()->withErrors(['etapa' => 'La etapa está finalizada.']);
            }
            DB::table('PIAR_PLAN_CASERO')->updateOrInsert(
                $clave,
                [
                    'OBSERVACIONES' => $request->OBSERVACIONES_CASERO,
                    'ESTADO'        => 'con_observaciones',
                    'updated_at'    => now(),
                ]
            );

            $materia  = $this->nombreMateria($codigoMat);
            $etiqueta = $this->etiquetaEstudiante($codigo);
            $url      = $volverUrl . '#observaciones';
            $mensaje  = "{$materia} — {$etiqueta}: hay observaciones pendientes en el Plan Casero (período {$periodo}).";
            foreach ($this->docentesAsignados($codigo, $codigoMat) as $doc) {
                NotificacionesController::crear($doc, 'piar_casero_observ', 'Observaciones en Plan Casero', $mensaje, $url);
            }
            return back()->with('saved', 'Observaciones enviadas al docente.');
        }

        $tieneObservaciones = $existingEstado === 'con_observaciones';
        if ($esDocente && $estadoEtapa !== 'abierto' && !$tieneObservaciones) {
            $msg = match($estadoEtapa) {
                'cerrado'    => 'La etapa de Plan Casero está cerrada. No se permiten cambios.',
                'revision'   => 'La etapa está en revisión. El orientador está revisando tu trabajo.',
                'finalizado' => 'La etapa está finalizada. No se permiten más cambios.',
                default      => 'No se pueden guardar cambios en este momento.',
            };
            return back()->withErrors(['etapa' => $msg]);
        }
        if (!$esDocente && $estadoEtapa === 'finalizado') {
            return back()->withErrors(['etapa' => 'La etapa está finalizada. No se permiten más cambios.']);
        }

        $entregar    = $request->input('accion') === 'entregar';
        $nuevoEstado = $entregar
            ? 'revision'
            : (in_array($existingEstado, ['aprobado', 'con_observaciones']) ? 'revision' : ($existingEstado ?? 'pendiente'));

        $datos = [
            'ESTRAG'     => $request->ESTRAG_CASERA,
            'FREC'       => $request->FREC_CASERA,
            'ESTADO'     => $nuevoEstado,
            'updated_at' => now(),
        ];
        if (!$esDocente && $request->has('OBSERVACIONES_CASERO')) {
            $datos['OBSERVACIONES'] = $request->OBSERVACIONES_CASERO;
        }

        DB::table('PIAR_PLAN_CASERO')->updateOrInsert($clave, $datos);

        if ($entregar) {
            $materia  = $this->nombreMateria($codigoMat);
            $etiqueta = $this->etiquetaEstudiante($codigo);
            $nomDoc   = DB::table('CODIGOS_DOC')->where('CODIGO_EMP', $this->codigoDoc())->value('NOMBRE_DOC') ?? $this->codigoDoc();
            $url      = $volverUrl . '#observaciones';
            $mensaje  = "{$nomDoc} entregó el Plan Casero de {$etiqueta} en {$materia} (período {$periodo}).";
            NotificacionesController::crearParaRevisoresPiar('piar_casero_entreg', 'Plan Casero entregado para revisión', $mensaje, $url);
        }

        $msg = $entregar ? 'Plan Casero marcado como entregado para revisión.' : 'Plan Casero guardado correctamente.';
        return redirect($volverUrl)->with('saved', $msg);
    }

    // ── Etiquetas de período ──────────────────────────────────────────────────
    public static function etiquetasPeriodo(): array
    {
        return [1 => 'Primer período', 2 => 'Segundo período', 3 => 'Tercer período', 4 => 'Cuarto período'];
    }

    // ── Vista de impresión Plan Casero (Anexo 3) ─────────────────────────────
    // Un acta por período. Si llega ?periodo=N imprime solo ese; si no, imprime
    // un acta por cada período que tenga al menos una estrategia diligenciada.
    public function imprimirPlanCasero(Request $request, string $codigo)
    {
        $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();
        if (!$estudiante) abort(404);

        $piarDiag = DB::table('PIAR_DIAG')->where('CODIGO_ALUM', $codigo)->first();
        $padres   = DB::table('INFO_PADRES')->where('CODIGO', $codigo)->first();

        $matsExcluidas = [24, 31, 35, 124, 135, 153];
        $cursosEst     = $this->cursosAplicables($codigo, $estudiante->CURSO);
        $etiquetas     = self::etiquetasPeriodo();

        // Períodos a imprimir
        $periodoParam = (int) $request->query('periodo', 0);
        $periodos = in_array($periodoParam, [1, 2, 3, 4])
            ? [$periodoParam]
            : DB::table('PIAR_PLAN_CASERO')
                ->where('CODIGO_ALUM', $codigo)
                ->whereNotNull('ESTRAG')->where('ESTRAG', '!=', '')
                ->whereNotIn('CODIGO_MAT', $matsExcluidas)
                ->distinct()->orderBy('PERIODO')->pluck('PERIODO')->all();

        // Construye un "acta" por período (planes de ese período + docentes que elaboran)
        $actas = collect();
        foreach ($periodos as $periodo) {
            $planes = DB::table('PIAR_PLAN_CASERO as pm')
                ->join('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'pm.CODIGO_MAT')
                ->leftJoin(DB::raw('(SELECT CODIGO_MAT, CURSO, MIN(CODIGO_EMP) AS CODIGO_EMP FROM ASIGNACION_PCM GROUP BY CODIGO_MAT, CURSO) as a'), function ($j) use ($cursosEst) {
                    $j->on('a.CODIGO_MAT', '=', 'pm.CODIGO_MAT')->whereIn('a.CURSO', $cursosEst);
                })
                ->leftJoin('CODIGOS_DOC as d', 'd.CODIGO_EMP', '=', 'a.CODIGO_EMP')
                ->where('pm.CODIGO_ALUM', $codigo)
                ->where('pm.PERIODO', $periodo)
                ->whereNotIn('pm.CODIGO_MAT', $matsExcluidas)
                ->whereNotNull('pm.ESTRAG')
                ->where('pm.ESTRAG', '!=', '')
                ->select('pm.ESTRAG as ESTRAG_CASERA', 'pm.FREC as FREC_CASERA', 'm.NOMBRE_MAT', 'd.NOMBRE_DOC')
                ->orderBy('m.NOMBRE_MAT')
                ->get();

            $docentesElaboran = collect();
            foreach ($planes as $pl) {
                if ($pl->NOMBRE_DOC && !$docentesElaboran->contains('NOMBRE_DOC', $pl->NOMBRE_DOC)) {
                    $docentesElaboran->push((object)['NOMBRE_DOC' => $pl->NOMBRE_DOC, 'MATERIA' => $pl->NOMBRE_MAT]);
                }
            }

            $actas->push((object)[
                'periodo'          => $periodo,
                'label'            => $etiquetas[$periodo] ?? "Período {$periodo}",
                'planes'           => $planes,
                'docentesElaboran' => $docentesElaboran,
            ]);
        }

        $orientadora = $piarDiag->PERSONA_DIL ?? 'Jennifer Andrea Martínez Londoño';

        $nombreCompleto = trim("{$estudiante->NOMBRE1} {$estudiante->NOMBRE2}");
        $apellidos      = trim("{$estudiante->APELLIDO1} {$estudiante->APELLIDO2}");
        $tipoDoc = 'TI';
        $numId   = $estudiante->TAR_ID ?? '';
        if (!$numId && ($estudiante->REG_CIVIL ?? '')) { $tipoDoc = 'RC'; $numId = $estudiante->REG_CIVIL; }
        $edad  = $estudiante->EDAD  ?? '';
        $grado = $estudiante->GRADO ?? '';
        $curso = $estudiante->CURSO ?? '';
        $sede  = $estudiante->SEDE  ? 'Sede ' . $estudiante->SEDE : '';

        $nombreMadre = $padres->MADRE ?? '';
        $nombrePadre = $padres->PADRE ?? '';

        return view('piar.plan-casero.imprimir', compact(
            'estudiante', 'piarDiag', 'actas', 'orientadora',
            'nombreCompleto', 'apellidos', 'tipoDoc', 'numId',
            'edad', 'grado', 'curso', 'sede',
            'nombreMadre', 'nombrePadre'
        ));
    }

    // ── Aprobar Plan Casero (Ori / SuperAd) ──────────────────────────────────
    public function aprobarPlanCasero(string $codigo, int $codigoMat, int $periodo)
    {
        if (!in_array($periodo, [1, 2, 3, 4])) abort(404);

        DB::table('PIAR_PLAN_CASERO')
            ->where('CODIGO_ALUM', $codigo)->where('CODIGO_MAT', $codigoMat)->where('PERIODO', $periodo)
            ->update([
                'ESTADO'           => 'aprobado',
                'APROBADO_POR'     => auth()->user()->name ?? auth()->user()->PROFILE,
                'FECHA_APROBACION' => today()->toDateString(),
            ]);

        $materia  = $this->nombreMateria($codigoMat);
        $etiqueta = $this->etiquetaEstudiante($codigo);
        $url      = route('piar.plan_casero.form', [$codigo, $codigoMat]) . '?periodo=' . $periodo . '#observaciones';
        $mensaje  = "{$materia} — {$etiqueta}: el Plan Casero del período {$periodo} fue aprobado.";
        foreach ($this->docentesAsignados($codigo, $codigoMat) as $doc) {
            NotificacionesController::crear($doc, 'piar_casero_aprob', 'Plan Casero aprobado', $mensaje, $url);
        }

        return back()->with('aprobado', 'Plan Casero aprobado.');
    }

    // ── Aprobar (Ori / SuperAd) ───────────────────────────────────────────────
    public function aprobar(string $codigo, int $codigoMat)
    {
        DB::table('PIAR_MAT')
            ->where('CODIGO_ALUM', $codigo)->where('CODIGO_MAT', $codigoMat)
            ->update([
                'ESTADO'           => 'aprobado',
                'APROBADO_POR'     => auth()->user()->name ?? auth()->user()->PROFILE,
                'FECHA_APROBACION' => today()->toDateString(),
            ]);

        $materia  = $this->nombreMateria($codigoMat);
        $etiqueta = $this->etiquetaEstudiante($codigo);
        $url      = route('piar.anexo2.form', [$codigo, $codigoMat]) . '#observaciones';
        $mensaje  = "{$materia} — {$etiqueta}: los ajustes razonables fueron aprobados.";
        foreach ($this->docentesAsignados($codigo, $codigoMat) as $doc) {
            NotificacionesController::crear($doc, 'piar_aprob', 'Anexo 2 aprobado', $mensaje, $url);
        }

        return back()->with('aprobado', 'Ajustes aprobados.');
    }
}
