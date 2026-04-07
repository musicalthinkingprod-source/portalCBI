<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PiarCaractController extends Controller
{
    private function codigoDoc(): string
    {
        return auth()->user()->PROFILE;
    }

    private function esDocente(): bool
    {
        return str_starts_with(auth()->user()->PROFILE, 'DOC');
    }

    // ── ÍNDICE GENERAL – agrupado por estudiante ────────────────────────────
    public function index()
    {
        $codigoDoc = $this->codigoDoc();
        $esDocente = $this->esDocente();

        // ¿Es director de grupo?
        $dirInfo = DB::table('CODIGOS_DOC')
            ->where('CODIGO_DOC', $codigoDoc)
            ->whereNotNull('DIR_GRUPO')
            ->select('DIR_GRUPO', 'NOMBRE_DOC')
            ->first();
        $esDirector = (bool) $dirInfo;

        // Filas: un registro por (estudiante × materia) con estados de caract. y ajustes
        $filas = DB::table('ESTUDIANTES as e')
            ->join('PIAR_DIAG as pd', 'pd.CODIGO_ALUM', '=', 'e.CODIGO')
            ->join('ASIGNACION_PCM as a', 'a.CURSO', '=', 'e.CURSO')
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
                DB::raw('CASE WHEN pc.CODIGO_ALUM IS NOT NULL THEN 1 ELSE 0 END as CARACT_MAT_OK'),
                DB::raw('CASE WHEN pm.CODIGO_ALUM IS NOT NULL THEN 1 ELSE 0 END as AJUSTES_OK')
            )
            ->where('e.ESTADO', 'MATRICULADO')
            ->when($esDocente, fn($q) => $q->where('a.CODIGO_DOC', $codigoDoc))
            ->orderBy('e.APELLIDO1')->orderBy('e.NOMBRE1')->orderBy('m.NOMBRE_MAT')
            ->get()
            ->groupBy('CODIGO');

        // Caracterizaciones de director ya guardadas (códigos de alumnos)
        $caractDirGuardadas = DB::table('PIAR_CARACT_DIR')
            ->where('CODIGO_DOC', $codigoDoc)
            ->pluck('CODIGO_ALUM')
            ->flip()
            ->toArray();

        return view('piar.anexo2.index', compact(
            'filas', 'esDocente', 'esDirector', 'dirInfo', 'caractDirGuardadas'
        ));
    }

    // ── FORM: Caracterización por materia ────────────────────────────────────
    public function formMat(string $codigo, int $codigoMat)
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
                ->select('e.*')->first();
        } else {
            $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();
        }

        if (!$estudiante) abort(403);

        $materia  = DB::table('CODIGOSMAT')->where('CODIGO_MAT', $codigoMat)->first();
        $docente  = DB::table('CODIGOS_DOC')->where('CODIGO_DOC', $codigoDoc)->first();
        $piarDiag = DB::table('PIAR_DIAG')->where('CODIGO_ALUM', $codigo)->first();
        $caract   = DB::table('PIAR_CARACT_MAT')
                        ->where('CODIGO_ALUM', $codigo)
                        ->where('CODIGO_MAT', $codigoMat)
                        ->first();

        $nombreCompleto = trim("{$estudiante->NOMBRE1} {$estudiante->NOMBRE2}");
        $apellidos      = trim("{$estudiante->APELLIDO1} {$estudiante->APELLIDO2}");

        return view('piar.caracterizacion.mat', compact(
            'estudiante', 'materia', 'docente', 'piarDiag', 'caract',
            'nombreCompleto', 'apellidos', 'codigoMat'
        ));
    }

    public function guardarMat(Request $request, string $codigo, int $codigoMat)
    {
        DB::table('PIAR_CARACT_MAT')->updateOrInsert(
            ['CODIGO_ALUM' => $codigo, 'CODIGO_MAT' => $codigoMat],
            [
                'CODIGO_DOC'      => $this->codigoDoc(),
                'CARACTERIZACION' => $request->CARACTERIZACION,
                'updated_at'      => now(),
            ]
        );

        return back()->with('saved', 'Caracterización guardada correctamente.');
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
                ->where('CODIGO_DOC', $codigoDoc)
                ->where('DIR_GRUPO', $estudiante->CURSO)
                ->exists();
            if (!$esDir) abort(403);
        }

        $docente  = DB::table('CODIGOS_DOC')->where('CODIGO_DOC', $codigoDoc)->first();
        $piarDiag = DB::table('PIAR_DIAG')->where('CODIGO_ALUM', $codigo)->first();
        $caract   = DB::table('PIAR_CARACT_DIR')
                        ->where('CODIGO_ALUM', $codigo)
                        ->where('CODIGO_DOC', $codigoDoc)
                        ->first();

        $nombreCompleto = trim("{$estudiante->NOMBRE1} {$estudiante->NOMBRE2}");
        $apellidos      = trim("{$estudiante->APELLIDO1} {$estudiante->APELLIDO2}");

        return view('piar.caracterizacion.dir', compact(
            'estudiante', 'docente', 'piarDiag', 'caract',
            'nombreCompleto', 'apellidos'
        ));
    }

    public function guardarDir(Request $request, string $codigo)
    {
        $codigoDoc  = $this->codigoDoc();
        $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();
        if (!$estudiante) abort(404);

        DB::table('PIAR_CARACT_DIR')->updateOrInsert(
            ['CODIGO_ALUM' => $codigo, 'CODIGO_DOC' => $codigoDoc],
            [
                'CURSO'           => $estudiante->CURSO,
                'CARACTERIZACION' => $request->CARACTERIZACION,
                'updated_at'      => now(),
            ]
        );

        return back()->with('saved', 'Caracterización guardada correctamente.');
    }

    // ── IMPRESIÓN COMPLETA ANEXO 2 POR ESTUDIANTE (Ori / SuperAd) ───────────
    public function imprimirAnexo2(string $codigo)
    {
        $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();
        if (!$estudiante) abort(404);
        $piarDiag   = DB::table('PIAR_DIAG')->where('CODIGO_ALUM', $codigo)->first();

        // Caracterización por director de grupo
        $caractDir = DB::table('PIAR_CARACT_DIR as pcd')
            ->leftJoin('CODIGOS_DOC as d', 'd.CODIGO_DOC', '=', 'pcd.CODIGO_DOC')
            ->where('pcd.CODIGO_ALUM', $codigo)
            ->select('pcd.CARACTERIZACION', 'pcd.CURSO', 'd.NOMBRE_DOC')
            ->first();

        // Caracterizaciones por materia
        $caractMats = DB::table('PIAR_CARACT_MAT as pc')
            ->join('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'pc.CODIGO_MAT')
            ->leftJoin('CODIGOS_DOC as d', 'd.CODIGO_DOC', '=', 'pc.CODIGO_DOC')
            ->where('pc.CODIGO_ALUM', $codigo)
            ->select('pc.CARACTERIZACION', 'm.NOMBRE_MAT', 'd.NOMBRE_DOC')
            ->orderBy('m.NOMBRE_MAT')
            ->get();

        // Ajustes por materia (docente via ASIGNACION_PCM + curso del estudiante)
        $ajustes = DB::table('PIAR_MAT as pm')
            ->join('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'pm.CODIGO_MAT')
            ->join('ASIGNACION_PCM as a', function ($j) use ($estudiante) {
                $j->on('a.CODIGO_MAT', '=', 'pm.CODIGO_MAT')
                  ->where('a.CURSO', '=', $estudiante->CURSO);
            })
            ->join('CODIGOS_DOC as d', 'd.CODIGO_DOC', '=', 'a.CODIGO_DOC')
            ->where('pm.CODIGO_ALUM', $codigo)
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
