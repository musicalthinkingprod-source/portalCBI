<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\ControlFechasController;

class PiarMatController extends Controller
{
    private function esDocente(): bool
    {
        return str_starts_with(auth()->user()->PROFILE, 'DOC');
    }

    private function codigoDoc(): string
    {
        return auth()->user()->PROFILE;
    }

    // ── Lista de estudiantes con PIAR para este docente ──────────────────────
    public function index()
    {
        $codigoDoc = $this->codigoDoc();
        $esDocente = $this->esDocente();

        // Estudiantes con PIAR + materias asignadas al docente
        $query = DB::table('ESTUDIANTES as e')
            ->join('PIAR_DIAG as pd', 'pd.CODIGO_ALUM', '=', 'e.CODIGO')
            ->join(DB::raw('(SELECT DISTINCT CODIGO_DOC, CODIGO_MAT, CURSO FROM ASIGNACION_PCM) as a'), 'a.CURSO', '=', 'e.CURSO')
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

        $matsExcluidas = [24, 35, 124, 135, 153]; // Urbanidad y Cívica, Cátedra de Paz, Urbanidad y Cívica PE, Cátedra de Paz PE, Pensamiento Lógico
        $query->whereNotIn('a.CODIGO_MAT', $matsExcluidas);

        if ($esDocente) {
            $query->where('a.CODIGO_DOC', $codigoDoc);
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
                ->join('ASIGNACION_PCM as a', function ($j) use ($codigoDoc, $codigoMat) {
                    $j->on('a.CURSO', '=', 'e.CURSO')
                      ->where('a.CODIGO_DOC', $codigoDoc)
                      ->where('a.CODIGO_MAT', $codigoMat);
                })
                ->where('e.CODIGO', $codigo)
                ->select('e.*')
                ->first();
        } else {
            $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();
        }

        if (!$estudiante) abort(403);

        $materia  = DB::table('CODIGOSMAT')->where('CODIGO_MAT', $codigoMat)->first();
        $docente  = DB::table('CODIGOS_DOC')->where('CODIGO_DOC', $codigoDoc)->first();
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
            try { $fechaNac = \Carbon\Carbon::parse($estudiante->FECH_NACIMIENTO)->translatedFormat('d \d\e F \d\e Y'); }
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
    public function imprimir(string $codigo, int $codigoMat)
    {
        // Reutiliza la misma lógica de form() pero retorna la vista de impresión
        $codigoDoc = $this->codigoDoc();
        $esDocente = $this->esDocente();

        if ($esDocente) {
            $estudiante = DB::table('ESTUDIANTES as e')
                ->join('ASIGNACION_PCM as a', function ($j) use ($codigoDoc, $codigoMat) {
                    $j->on('a.CURSO', '=', 'e.CURSO')
                      ->where('a.CODIGO_DOC', $codigoDoc)
                      ->where('a.CODIGO_MAT', $codigoMat);
                })
                ->where('e.CODIGO', $codigo)
                ->select('e.*')
                ->first();
        } else {
            $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();
        }

        if (!$estudiante) abort(403);

        $materia  = DB::table('CODIGOSMAT')->where('CODIGO_MAT', $codigoMat)->first();
        $docente  = DB::table('CODIGOS_DOC')->where('CODIGO_DOC', $codigoDoc)->first();
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
            try { $fechaNac = \Carbon\Carbon::parse($estudiante->FECH_NACIMIENTO)->translatedFormat('d \d\e F \d\e Y'); }
            catch (\Exception $e) { $fechaNac = $estudiante->FECH_NACIMIENTO; }
        }

        return view('piar.anexo2.imprimir', compact(
            'estudiante', 'materia', 'docente', 'piarDiag', 'piarMat',
            'nombreCompleto', 'apellidos', 'numId', 'edad', 'grado', 'fechaNac',
            'codigoMat'
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
            'ESTRAG_CASERA' => $request->ESTRAG_CASERA,
            'FREC_CASERA'   => $request->FREC_CASERA,
            'ESTADO'        => $nuevoEstado,
        ];
        if (!$this->esDocente() && $request->has('OBSERVACIONES')) {
            $datos['OBSERVACIONES'] = $request->OBSERVACIONES;
        }

        DB::table('PIAR_MAT')->updateOrInsert(
            ['CODIGO_ALUM' => $codigo, 'CODIGO_MAT' => $codigoMat],
            $datos
        );

        $msg = $entregar ? 'Ajustes marcados como entregados para revisión.' : 'PIAR Anexo 2 guardado correctamente.';
        return redirect()->route('piar.anexo2.form', [$codigo, $codigoMat])->with('saved', $msg);
    }

    // ── Plan Casero ──────────────────────────────────────────────────────────
    public function formPlanCasero(string $codigo, int $codigoMat)
    {
        $codigoDoc = $this->codigoDoc();
        $esDocente = $this->esDocente();

        if ($esDocente) {
            $estudiante = DB::table('ESTUDIANTES as e')
                ->join('ASIGNACION_PCM as a', function ($j) use ($codigoDoc, $codigoMat) {
                    $j->on('a.CURSO', '=', 'e.CURSO')
                      ->where('a.CODIGO_DOC', $codigoDoc)
                      ->where('a.CODIGO_MAT', $codigoMat);
                })
                ->where('e.CODIGO', $codigo)
                ->select('e.*')
                ->first();
        } else {
            $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();
        }

        if (!$estudiante) abort(403);

        $materia  = DB::table('CODIGOSMAT')->where('CODIGO_MAT', $codigoMat)->first();
        $docente  = DB::table('CODIGOS_DOC')->where('CODIGO_DOC', $codigoDoc)->first();
        $piarDiag = DB::table('PIAR_DIAG')->where('CODIGO_ALUM', $codigo)->first();
        $piarMat  = DB::table('PIAR_MAT')
                        ->where('CODIGO_ALUM', $codigo)
                        ->where('CODIGO_MAT', $codigoMat)
                        ->first();
        $caract   = DB::table('PIAR_CARACT_MAT')
                        ->where('CODIGO_ALUM', $codigo)
                        ->where('CODIGO_MAT', $codigoMat)
                        ->first();

        $nombreCompleto = trim("{$estudiante->NOMBRE1} {$estudiante->NOMBRE2}");
        $apellidos      = trim("{$estudiante->APELLIDO1} {$estudiante->APELLIDO2}");
        $estadoEtapa    = ControlFechasController::estadoEtapa('plan_casero');

        return view('piar.plan-casero.form', compact(
            'estudiante', 'materia', 'docente', 'piarDiag', 'piarMat', 'caract',
            'nombreCompleto', 'apellidos', 'codigoMat', 'estadoEtapa'
        ));
    }

    public function guardarPlanCasero(Request $request, string $codigo, int $codigoMat)
    {
        $esDocente   = $this->esDocente();
        $estadoEtapa = ControlFechasController::estadoEtapa('plan_casero');

        if ($esDocente && $estadoEtapa !== 'abierto') {
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

        DB::table('PIAR_MAT')->updateOrInsert(
            ['CODIGO_ALUM' => $codigo, 'CODIGO_MAT' => $codigoMat],
            [
                'ESTRAG_CASERA' => $request->ESTRAG_CASERA,
                'FREC_CASERA'   => $request->FREC_CASERA,
                'updated_at'    => now(),
            ]
        );

        return redirect()->route('piar.plan_casero.form', [$codigo, $codigoMat])->with('saved', 'Plan Casero guardado correctamente.');
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

        return back()->with('aprobado', 'Ajustes aprobados.');
    }
}
