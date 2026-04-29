<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ControlFechasController;
use App\Http\Controllers\NotificacionesController;

class PiarCaractController extends Controller
{
    private function codigoDoc(): string
    {
        return auth()->user()->PROFILE;
    }

    private function esDocente(): bool
    {
        $profile = auth()->user()->PROFILE;
        return str_starts_with($profile, 'DOC') || str_starts_with($profile, 'COR');
    }

    // ── Utilidades para notificaciones PIAR (Caracterización) ───────────────
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

    private function nombreDocente(string $codigoDoc): string
    {
        return DB::table('CODIGOS_DOC')->where('CODIGO_EMP', $codigoDoc)->value('NOMBRE_DOC') ?? $codigoDoc;
    }

    // ── ÍNDICE GENERAL – agrupado por estudiante ────────────────────────────
    // Materias excluidas del PIAR (no aplican caracterización ni ajustes)
    private const MATS_EXCLUIDAS_PIAR = [24, 31, 35, 124, 135, 153]; // Urbanidad y Cívica, Proyectos, Cátedra de Paz, Urbanidad y Cívica PE, Cátedra de Paz PE, Pensamiento Lógico

    public function index()
    {
        $codigoDoc = $this->codigoDoc();
        $esDocente = $this->esDocente();

        // ¿Es director de grupo?
        $dirInfo = DB::table('CODIGOS_DOC')
            ->where('CODIGO_EMP', $codigoDoc)
            ->whereNotNull('DIR_GRUPO')
            ->select('DIR_GRUPO', 'NOMBRE_DOC')
            ->first();
        $esDirector = (bool) $dirInfo;

        $perfilActual = auth()->user()->PROFILE;
        $esOriSuperAd = $perfilActual === 'SuperAd' || $perfilActual === 'Piar' || str_starts_with($perfilActual, 'Ori');

        // Filas: un registro por (estudiante × materia) con estados de caract. y ajustes
        // Empareja ASIGNACION_PCM por curso base del estudiante O por grupos de
        // LISTADOS_ESPECIALES (Artes/Música 7°+ con -1/-2, Proyectos).
        $filasRaw = DB::table('ESTUDIANTES as e')
            ->join('PIAR_DIAG as pd', 'pd.CODIGO_ALUM', '=', 'e.CODIGO')
            ->leftJoin('LISTADOS_ESPECIALES as le', 'le.CODIGO_ALUM', '=', 'e.CODIGO')
            ->join(DB::raw('(SELECT DISTINCT CODIGO_EMP, CODIGO_MAT, CURSO FROM ASIGNACION_PCM) as a'), function ($j) {
                $j->whereRaw('(a.CURSO = e.CURSO OR a.CURSO = le.GRUPO)');
            })
            ->join('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'a.CODIGO_MAT')
            ->leftJoin('PIAR_CARACT_MAT as pc', function ($j) {
                $j->on('pc.CODIGO_ALUM', '=', 'e.CODIGO')
                  ->on('pc.CODIGO_MAT',  '=', 'a.CODIGO_MAT');
            })
            ->leftJoin('PIAR_MAT as pm', function ($j) {
                $j->on('pm.CODIGO_ALUM', '=', 'e.CODIGO')
                  ->on('pm.CODIGO_MAT',  '=', 'a.CODIGO_MAT');
            })
            ->select(
                'e.CODIGO', 'e.NOMBRE1', 'e.NOMBRE2', 'e.APELLIDO1', 'e.APELLIDO2',
                'e.GRADO', 'e.CURSO', 'pd.DIAGNOSTICO',
                'a.CODIGO_MAT', 'm.NOMBRE_MAT',
                // Alerta basada en contenido diligenciado, no solo en existencia del registro
                DB::raw("CASE WHEN pc.CARACTERIZACION IS NOT NULL AND pc.CARACTERIZACION != '' THEN 1 ELSE 0 END as CARACT_MAT_OK"),
                DB::raw("CASE WHEN pm.BARRERAS IS NOT NULL AND pm.BARRERAS != '' THEN 1 ELSE 0 END as AJUSTES_OK"),
                DB::raw("CASE WHEN pm.ESTRAG_CASERA IS NOT NULL AND pm.ESTRAG_CASERA != '' THEN 1 ELSE 0 END as PLAN_CASERO_OK"),
                DB::raw("COALESCE(MAX(pc.ESTADO), 'pendiente') as CARACT_MAT_ESTADO"),
                DB::raw("COALESCE(MAX(pm.ESTADO), 'pendiente') as AJUSTES_ESTADO"),
                DB::raw("COALESCE(MAX(pm.ESTADO_CASERO), 'pendiente') as PLAN_CASERO_ESTADO")
            )
            ->where('e.ESTADO', 'MATRICULADO')
            ->whereNotIn('a.CODIGO_MAT', self::MATS_EXCLUIDAS_PIAR)
            ->when($esDocente, fn($q) => $q->where('a.CODIGO_EMP', $codigoDoc))
            ->groupBy(
                'e.CODIGO', 'e.NOMBRE1', 'e.NOMBRE2', 'e.APELLIDO1', 'e.APELLIDO2',
                'e.GRADO', 'e.CURSO', 'pd.DIAGNOSTICO',
                'a.CODIGO_MAT', 'm.NOMBRE_MAT'
            )
            ->when($esOriSuperAd,
                fn($q) => $q->orderBy('e.CODIGO')->orderBy('m.NOMBRE_MAT'),
                fn($q) => $q->orderBy('e.APELLIDO1')->orderBy('e.NOMBRE1')->orderBy('m.NOMBRE_MAT')
            )
            ->get();

        $filas = $filasRaw->groupBy('CODIGO');

        // Si es director de grupo, agregar los estudiantes de su curso que tienen PIAR
        // pero a quienes no les dicta ninguna materia (no aparecen en la query anterior)
        if ($esDirector) {
            $codigosYaVisibles = $filas->keys()->all();

            $soloDirector = DB::table('ESTUDIANTES as e')
                ->join('PIAR_DIAG as pd', 'pd.CODIGO_ALUM', '=', 'e.CODIGO')
                ->select(
                    'e.CODIGO', 'e.NOMBRE1', 'e.NOMBRE2', 'e.APELLIDO1', 'e.APELLIDO2',
                    'e.GRADO', 'e.CURSO', 'pd.DIAGNOSTICO',
                    DB::raw('NULL as CODIGO_MAT'),
                    DB::raw('NULL as NOMBRE_MAT'),
                    DB::raw('0 as CARACT_MAT_OK'),
                    DB::raw('0 as AJUSTES_OK'),
                    DB::raw('0 as PLAN_CASERO_OK'),
                    DB::raw("'pendiente' as CARACT_MAT_ESTADO"),
                    DB::raw("'pendiente' as AJUSTES_ESTADO"),
                    DB::raw("'pendiente' as PLAN_CASERO_ESTADO")
                )
                ->where('e.ESTADO', 'MATRICULADO')
                ->where('e.CURSO', $dirInfo->DIR_GRUPO)
                ->when(!empty($codigosYaVisibles), fn($q) => $q->whereNotIn('e.CODIGO', $codigosYaVisibles))
                ->orderBy('e.APELLIDO1')->orderBy('e.NOMBRE1')
                ->get();

            foreach ($soloDirector as $est) {
                // Agregar al inicio de $filas (ordenando por apellido después)
                $filas->put($est->CODIGO, collect([$est]));
            }

            // Reordenar: primero los que tienen materias, luego los solo-director, todos por apellido
            $filas = $filas->sortBy(fn($rows) => $rows->first()->APELLIDO1 . $rows->first()->NOMBRE1);
        }

        // Caracterizaciones de director ya guardadas (códigos de alumnos) con su estado
        // Para docentes/directores: solo las propias. Para SuperAd/Ori: cualquier director.
        // Solo se considera diligenciada si el campo CARACTERIZACION tiene contenido real
        $caractDirGuardadas = DB::table('PIAR_CARACT_DIR')
            ->when($esDocente, fn($q) => $q->where('CODIGO_EMP', $codigoDoc))
            ->whereNotNull('CARACTERIZACION')
            ->where('CARACTERIZACION', '!=', '')
            ->select('CODIGO_ALUM', DB::raw("COALESCE(ESTADO, 'pendiente') as ESTADO"))
            ->get()
            ->keyBy('CODIGO_ALUM')
            ->toArray();

        $etapasControl = ControlFechasController::etapasDelAnio((int) date('Y'));
        $puedeAprobar  = in_array(auth()->user()->PROFILE, ['SuperAd', 'Ori', 'Piar']);

        return view('piar.anexo2.index', compact(
            'filas', 'esDocente', 'esDirector', 'dirInfo', 'caractDirGuardadas', 'etapasControl', 'puedeAprobar'
        ));
    }

    // ── FORM: Caracterización por materia ────────────────────────────────────
    public function formMat(string $codigo, int $codigoMat)
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
                ->select('e.*')->first();
        } else {
            $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();
        }

        if (!$estudiante) abort(403);

        $materia  = DB::table('CODIGOSMAT')->where('CODIGO_MAT', $codigoMat)->first();
        $docente  = DB::table('CODIGOS_DOC')->where('CODIGO_EMP', $codigoDoc)->first();
        $piarDiag = DB::table('PIAR_DIAG')->where('CODIGO_ALUM', $codigo)->first();
        $caract   = DB::table('PIAR_CARACT_MAT')
                        ->where('CODIGO_ALUM', $codigo)
                        ->where('CODIGO_MAT', $codigoMat)
                        ->first();

        $nombreCompleto = trim("{$estudiante->NOMBRE1} {$estudiante->NOMBRE2}");
        $apellidos      = trim("{$estudiante->APELLIDO1} {$estudiante->APELLIDO2}");

        $estadoEtapa = ControlFechasController::estadoEtapa('caract');

        return view('piar.caracterizacion.mat', compact(
            'estudiante', 'materia', 'docente', 'piarDiag', 'caract',
            'nombreCompleto', 'apellidos', 'codigoMat', 'estadoEtapa'
        ));
    }

    public function guardarMat(Request $request, string $codigo, int $codigoMat)
    {
        $esDocente   = $this->esDocente();
        $estadoEtapa = ControlFechasController::estadoEtapa('caract');

        $existingEstado = DB::table('PIAR_CARACT_MAT')
            ->where('CODIGO_ALUM', $codigo)->where('CODIGO_MAT', $codigoMat)
            ->value('ESTADO') ?? 'pendiente';

        // CODIGO_EMP real del registro existente (o del docente asignado si no existe aún)
        $codigoDocReal = DB::table('PIAR_CARACT_MAT')
            ->where('CODIGO_ALUM', $codigo)->where('CODIGO_MAT', $codigoMat)
            ->value('CODIGO_EMP');

        if (!$codigoDocReal) {
            $curso  = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->value('CURSO');
            $cursos = $this->cursosAplicables($codigo, $curso);
            $codigoDocReal = DB::table('ASIGNACION_PCM')
                ->where('CODIGO_MAT', $codigoMat)
                ->whereIn('CURSO', $cursos)
                ->value('CODIGO_EMP') ?? $this->codigoDoc();
        }

        // Orientador envía observaciones
        if (!$esDocente && $request->input('accion') === 'observar') {
            if ($estadoEtapa === 'finalizado') return back()->withErrors(['etapa' => 'La etapa está finalizada.']);
            DB::table('PIAR_CARACT_MAT')->updateOrInsert(
                ['CODIGO_ALUM' => $codigo, 'CODIGO_MAT' => $codigoMat],
                ['CODIGO_EMP' => $codigoDocReal, 'OBSERVACIONES' => $request->OBSERVACIONES, 'ESTADO' => 'con_observaciones', 'updated_at' => now()]
            );

            $materia  = $this->nombreMateria($codigoMat);
            $etiqueta = $this->etiquetaEstudiante($codigo);
            $url      = route('piar.caract.mat.form', [$codigo, $codigoMat]) . '#observaciones';
            $mensaje  = "{$materia} — {$etiqueta}: hay observaciones pendientes en la caracterización.";
            NotificacionesController::crear($codigoDocReal, 'piar_caract_observ', 'Observaciones en caracterización', $mensaje, $url);
            return back()->with('saved', 'Observaciones enviadas al docente.');
        }

        $tieneObservaciones = $existingEstado === 'con_observaciones';
        if ($esDocente && $estadoEtapa !== 'abierto' && !$tieneObservaciones) {
            $msg = match($estadoEtapa) {
                'cerrado'    => 'La etapa de caracterización está cerrada. No se permiten cambios.',
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
        $nuevoEstado = $entregar ? 'revision' : (in_array($existingEstado, ['aprobado', 'con_observaciones']) ? 'revision' : ($existingEstado ?? 'pendiente'));

        $datos = [
            'CODIGO_EMP'      => $esDocente ? $this->codigoDoc() : $codigoDocReal,
            'CARACTERIZACION' => $request->CARACTERIZACION,
            'ESTADO'          => $nuevoEstado,
            'updated_at'      => now(),
        ];
        if (!$esDocente && $request->has('OBSERVACIONES')) {
            $datos['OBSERVACIONES'] = $request->OBSERVACIONES;
        }

        DB::table('PIAR_CARACT_MAT')->updateOrInsert(
            ['CODIGO_ALUM' => $codigo, 'CODIGO_MAT' => $codigoMat],
            $datos
        );

        if ($entregar) {
            $materia  = $this->nombreMateria($codigoMat);
            $etiqueta = $this->etiquetaEstudiante($codigo);
            $nomDoc   = $this->nombreDocente($this->codigoDoc());
            $url      = route('piar.caract.mat.form', [$codigo, $codigoMat]) . '#observaciones';
            $mensaje  = "{$nomDoc} entregó la caracterización de {$etiqueta} en {$materia}.";
            NotificacionesController::crearParaRevisoresPiar('piar_caract_entreg', 'Caracterización entregada para revisión', $mensaje, $url);
        }

        $msg = $entregar ? 'Caracterización marcada como entregada para revisión.' : 'Caracterización guardada correctamente.';
        return redirect()->route('piar.caract.mat.form', [$codigo, $codigoMat])->with('saved', $msg);
    }

    public function aprobarMat(string $codigo, int $codigoMat)
    {
        if (ControlFechasController::estadoEtapa('caract') !== 'revision') {
            return back()->withErrors(['etapa' => 'Solo se puede aprobar cuando la etapa de caracterización está en estado "En revisión".']);
        }

        DB::table('PIAR_CARACT_MAT')
            ->where('CODIGO_ALUM', $codigo)->where('CODIGO_MAT', $codigoMat)
            ->update([
                'ESTADO'           => 'aprobado',
                'APROBADO_POR'     => auth()->user()->name ?? auth()->user()->PROFILE,
                'FECHA_APROBACION' => today()->toDateString(),
            ]);

        $codigoDocReal = DB::table('PIAR_CARACT_MAT')
            ->where('CODIGO_ALUM', $codigo)->where('CODIGO_MAT', $codigoMat)
            ->value('CODIGO_EMP');
        if ($codigoDocReal) {
            $materia  = $this->nombreMateria($codigoMat);
            $etiqueta = $this->etiquetaEstudiante($codigo);
            $url      = route('piar.caract.mat.form', [$codigo, $codigoMat]) . '#observaciones';
            $mensaje  = "{$materia} — {$etiqueta}: la caracterización fue aprobada.";
            NotificacionesController::crear($codigoDocReal, 'piar_caract_aprob', 'Caracterización aprobada', $mensaje, $url);
        }

        return back()->with('aprobado', 'Caracterización aprobada.');
    }

    // ── FORM: Caracterización por director de grupo ──────────────────────────
    public function formDir(string $codigo)
    {
        $codigoDoc = $this->codigoDoc();
        $esDocente = $this->esDocente();

        $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();
        if (!$estudiante) abort(404);

        // Verificar acceso: director del curso del estudiante, o SuperAd/Ori
        if ($esDocente) {
            $esDir = DB::table('CODIGOS_DOC')
                ->where('CODIGO_EMP', $codigoDoc)
                ->where('DIR_GRUPO', $estudiante->CURSO)
                ->exists();
            if (!$esDir) abort(403);
        }

        // Para Ori/SuperAd, operar sobre el registro del director real del curso
        $codigoDocDir = $esDocente
            ? $codigoDoc
            : (DB::table('CODIGOS_DOC')->where('DIR_GRUPO', $estudiante->CURSO)->value('CODIGO_EMP') ?? $codigoDoc);

        $docente  = DB::table('CODIGOS_DOC')->where('CODIGO_EMP', $codigoDocDir)->first();
        $piarDiag = DB::table('PIAR_DIAG')->where('CODIGO_ALUM', $codigo)->first();
        $caract   = DB::table('PIAR_CARACT_DIR')
                        ->where('CODIGO_ALUM', $codigo)
                        ->where('CODIGO_EMP', $codigoDocDir)
                        ->first();

        $nombreCompleto = trim("{$estudiante->NOMBRE1} {$estudiante->NOMBRE2}");
        $apellidos      = trim("{$estudiante->APELLIDO1} {$estudiante->APELLIDO2}");

        $estadoEtapa = ControlFechasController::estadoEtapa('caract');

        return view('piar.caracterizacion.dir', compact(
            'estudiante', 'docente', 'piarDiag', 'caract',
            'nombreCompleto', 'apellidos', 'estadoEtapa'
        ));
    }

    public function guardarDir(Request $request, string $codigo)
    {
        $codigoDoc   = $this->codigoDoc();
        $esDocente   = $this->esDocente();
        $estadoEtapa = ControlFechasController::estadoEtapa('caract');

        $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();
        if (!$estudiante) abort(404);

        // Para Ori/SuperAd, operar sobre el registro del director real del curso
        $codigoDocDir = $esDocente
            ? $codigoDoc
            : (DB::table('CODIGOS_DOC')->where('DIR_GRUPO', $estudiante->CURSO)->value('CODIGO_EMP') ?? $codigoDoc);

        $existingEstado = DB::table('PIAR_CARACT_DIR')
            ->where('CODIGO_ALUM', $codigo)->where('CODIGO_EMP', $codigoDocDir)
            ->value('ESTADO') ?? 'pendiente';

        // Orientador envía observaciones
        if (!$esDocente && $request->input('accion') === 'observar') {
            if ($estadoEtapa === 'finalizado') return back()->withErrors(['etapa' => 'La etapa está finalizada.']);
            DB::table('PIAR_CARACT_DIR')->updateOrInsert(
                ['CODIGO_ALUM' => $codigo, 'CODIGO_EMP' => $codigoDocDir],
                ['OBSERVACIONES' => $request->OBSERVACIONES, 'ESTADO' => 'con_observaciones', 'updated_at' => now()]
            );

            $etiqueta = $this->etiquetaEstudiante($codigo);
            $url      = route('piar.caract.dir.form', $codigo) . '#observaciones';
            $mensaje  = "Dirección de grupo — {$etiqueta}: hay observaciones pendientes en la caracterización.";
            NotificacionesController::crear($codigoDocDir, 'piar_caract_dir_observ', 'Observaciones en caracterización (dir)', $mensaje, $url);
            return back()->with('saved', 'Observaciones enviadas al docente.');
        }

        $tieneObservaciones = $existingEstado === 'con_observaciones';
        if ($esDocente && $estadoEtapa !== 'abierto' && !$tieneObservaciones) {
            $msg = match($estadoEtapa) {
                'cerrado'    => 'La etapa de caracterización está cerrada. No se permiten cambios.',
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
        $nuevoEstado = $entregar ? 'revision' : (in_array($existingEstado, ['aprobado', 'con_observaciones']) ? 'revision' : ($existingEstado ?? 'pendiente'));

        $datos = [
            'CURSO'           => $estudiante->CURSO,
            'CARACTERIZACION' => $request->CARACTERIZACION,
            'ESTADO'          => $nuevoEstado,
            'updated_at'      => now(),
        ];
        if (!$esDocente && $request->has('OBSERVACIONES')) {
            $datos['OBSERVACIONES'] = $request->OBSERVACIONES;
        }

        DB::table('PIAR_CARACT_DIR')->updateOrInsert(
            ['CODIGO_ALUM' => $codigo, 'CODIGO_EMP' => $codigoDocDir],
            $datos
        );

        if ($entregar) {
            $etiqueta = $this->etiquetaEstudiante($codigo);
            $nomDoc   = $this->nombreDocente($this->codigoDoc());
            $url      = route('piar.caract.dir.form', $codigo) . '#observaciones';
            $mensaje  = "{$nomDoc} (dir. grupo) entregó la caracterización de {$etiqueta}.";
            NotificacionesController::crearParaRevisoresPiar('piar_caract_dir_entreg', 'Caracterización (dir) entregada para revisión', $mensaje, $url);
        }

        $msg = $entregar ? 'Caracterización marcada como entregada para revisión.' : 'Caracterización guardada correctamente.';
        return redirect()->route('piar.caract.dir.form', $codigo)->with('saved', $msg);
    }

    public function aprobarDir(string $codigo)
    {
        if (ControlFechasController::estadoEtapa('caract') !== 'revision') {
            return back()->withErrors(['etapa' => 'Solo se puede aprobar cuando la etapa de caracterización está en estado "En revisión".']);
        }

        $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();
        if (!$estudiante) abort(404);

        DB::table('PIAR_CARACT_DIR')
            ->where('CODIGO_ALUM', $codigo)
            ->update([
                'ESTADO'           => 'aprobado',
                'APROBADO_POR'     => auth()->user()->name ?? auth()->user()->PROFILE,
                'FECHA_APROBACION' => today()->toDateString(),
            ]);

        $codigoDocDir = DB::table('PIAR_CARACT_DIR')->where('CODIGO_ALUM', $codigo)->value('CODIGO_EMP');
        if ($codigoDocDir) {
            $etiqueta = $this->etiquetaEstudiante($codigo);
            $url      = route('piar.caract.dir.form', $codigo) . '#observaciones';
            $mensaje  = "Dirección de grupo — {$etiqueta}: la caracterización fue aprobada.";
            NotificacionesController::crear($codigoDocDir, 'piar_caract_dir_aprob', 'Caracterización (dir) aprobada', $mensaje, $url);
        }

        return back()->with('aprobado', 'Caracterización de dirección aprobada.');
    }

    // ── IMPRESIÓN COMPLETA ANEXO 2 POR ESTUDIANTE (Ori / SuperAd) ───────────
    public function imprimirAnexo2(string $codigo)
    {
        $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();
        if (!$estudiante) abort(404);
        $piarDiag   = DB::table('PIAR_DIAG')->where('CODIGO_ALUM', $codigo)->first();

        // Caracterización por director de grupo
        $caractDir = DB::table('PIAR_CARACT_DIR as pcd')
            ->leftJoin('CODIGOS_DOC as d', 'd.CODIGO_EMP', '=', 'pcd.CODIGO_EMP')
            ->where('pcd.CODIGO_ALUM', $codigo)
            ->select('pcd.CARACTERIZACION', 'pcd.CURSO', 'd.NOMBRE_DOC')
            ->first();

        // Caracterizaciones por materia
        $caractMats = DB::table('PIAR_CARACT_MAT as pc')
            ->join('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'pc.CODIGO_MAT')
            ->leftJoin('CODIGOS_DOC as d', 'd.CODIGO_EMP', '=', 'pc.CODIGO_EMP')
            ->where('pc.CODIGO_ALUM', $codigo)
            ->whereNotIn('pc.CODIGO_MAT', self::MATS_EXCLUIDAS_PIAR)
            ->select('pc.CARACTERIZACION', 'm.NOMBRE_MAT', 'd.NOMBRE_DOC')
            ->orderBy('m.NOMBRE_MAT')
            ->get();

        // Ajustes por materia (un docente por materia para evitar duplicados)
        $cursosEst = $this->cursosAplicables($codigo, $estudiante->CURSO);

        $ajustes = DB::table('PIAR_MAT as pm')
            ->join('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'pm.CODIGO_MAT')
            ->join(DB::raw('(SELECT CODIGO_MAT, CURSO, MIN(CODIGO_EMP) AS CODIGO_EMP FROM ASIGNACION_PCM GROUP BY CODIGO_MAT, CURSO) as a'), function ($j) use ($cursosEst) {
                $j->on('a.CODIGO_MAT', '=', 'pm.CODIGO_MAT')
                  ->whereIn('a.CURSO', $cursosEst);
            })
            ->join('CODIGOS_DOC as d', 'd.CODIGO_EMP', '=', 'a.CODIGO_EMP')
            ->where('pm.CODIGO_ALUM', $codigo)
            ->whereNotIn('pm.CODIGO_MAT', self::MATS_EXCLUIDAS_PIAR)
            ->select('pm.*', 'm.NOMBRE_MAT', 'd.NOMBRE_DOC')
            ->orderBy('m.NOMBRE_MAT')
            ->get();

        // Lista única de docentes que elaboran con su cargo derivado
        $docentesElaboran = collect();
        if ($caractDir) {
            $docentesElaboran->push((object)[
                'NOMBRE_DOC' => $caractDir->NOMBRE_DOC,
                'CARGO'      => 'Director(a) de grupo ' . ($caractDir->CURSO ?? ''),
            ]);
        }
        foreach ($caractMats as $cm) {
            if (!$docentesElaboran->contains('NOMBRE_DOC', $cm->NOMBRE_DOC)) {
                $docentesElaboran->push((object)[
                    'NOMBRE_DOC' => $cm->NOMBRE_DOC,
                    'CARGO'      => 'Docente de ' . $cm->NOMBRE_MAT,
                ]);
            }
        }
        foreach ($ajustes as $aj) {
            if (!$docentesElaboran->contains('NOMBRE_DOC', $aj->NOMBRE_DOC)) {
                $docentesElaboran->push((object)[
                    'NOMBRE_DOC' => $aj->NOMBRE_DOC,
                    'CARGO'      => 'Docente de ' . $aj->NOMBRE_MAT,
                ]);
            }
        }

        $numId    = $estudiante->TAR_ID ?? $estudiante->REG_CIVIL ?? '';
        $edad     = $estudiante->EDAD ?? '';
        $fechaNac = '';
        if (!empty($estudiante->FECH_NACIMIENTO)) {
            try { $fechaNac = \Carbon\Carbon::parse($estudiante->FECH_NACIMIENTO)->translatedFormat('d \d\e F \d\e Y'); }
            catch (\Exception $e) { $fechaNac = $estudiante->FECH_NACIMIENTO; }
        }
        $grado          = $estudiante->GRADO;
        $nombreCompleto = trim("{$estudiante->NOMBRE1} {$estudiante->NOMBRE2}");
        $apellidos      = trim("{$estudiante->APELLIDO1} {$estudiante->APELLIDO2}");

        return view('piar.anexo2.imprimir_est', compact(
            'estudiante', 'piarDiag', 'caractDir', 'caractMats', 'ajustes',
            'docentesElaboran', 'nombreCompleto', 'apellidos',
            'numId', 'edad', 'fechaNac', 'grado'
        ));
    }
}
