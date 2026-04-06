<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BoletinController extends Controller
{
    public static function datos(int $codigo): array
    {
        $anio       = (int) date('Y');
        $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();

        if (!$estudiante) return [];

        // Notas con materia y área
        $notasRaw = collect();
        try {
            $notasRaw = DB::table('NOTAS_' . $anio . ' as n')
                ->join('CODIGOSMAT as m',    'm.CODIGO_MAT',   '=', 'n.CODIGO_MAT')
                ->join('CODIGOSAREA as a',   'a.CODIGO_AREA',  '=', 'm.AREA_MAT')
                ->where('n.CODIGO_ALUM', $codigo)
                ->select('n.PERIODO', 'n.NOTA', 'n.TIPODENOTA',
                         'm.NOMBRE_MAT', 'm.CODIGO_MAT',
                         'a.NOMBRE_AREA', 'a.CODIGO_AREA')
                ->orderBy('a.CODIGO_AREA')
                ->orderBy('m.NOMBRE_MAT')
                ->get();
        } catch (\Exception $e) {}

        // Construir estructura áreas > materias > periodos
        $areas = [];
        foreach ($notasRaw as $n) {
            $ak = $n->CODIGO_AREA;
            $mk = $n->CODIGO_MAT;
            if (!isset($areas[$ak])) {
                $areas[$ak] = ['nombre' => $n->NOMBRE_AREA, 'materias' => []];
            }
            if (!isset($areas[$ak]['materias'][$mk])) {
                $areas[$ak]['materias'][$mk] = ['nombre' => $n->NOMBRE_MAT, 'periodos' => []];
            }
            $areas[$ak]['materias'][$mk]['periodos'][$n->PERIODO] = [
                'nota' => $n->NOTA,
                'tipo' => $n->TIPODENOTA,
            ];
        }

        // Director de grupo del curso del estudiante
        $director = $estudiante->CURSO
            ? DB::table('CODIGOS_DOC')->where('DIR_GRUPO', $estudiante->CURSO)->value('NOMBRE_DOC')
            : null;

        // Observaciones del año
        $observaciones = collect();
        try {
            $observaciones = DB::table('OBSERVACIONES_' . $anio)
                ->where('CODIGO_ALUM', $codigo)
                ->orderBy('PERIODO')
                ->get()
                ->keyBy('PERIODO');
        } catch (\Exception $e) {}

        return compact('estudiante', 'areas', 'observaciones', 'director', 'anio');
    }

    private function accesoDocente(): array|null
    {
        $profile = auth()->user()->PROFILE;
        if (!str_starts_with($profile, 'DOC')) return null; // no es docente

        $docente = DB::table('CODIGOS_DOC')->where('CODIGO_DOC', $profile)->first();
        return $docente ? (array) $docente : [];
    }

    public function buscar(Request $request)
    {
        $profile   = auth()->user()->PROFILE;
        $esDocente = str_starts_with($profile, 'DOC');
        $cursoDir  = null;

        if ($esDocente) {
            $doc = DB::table('CODIGOS_DOC')->where('CODIGO_DOC', $profile)->first();
            if (!$doc || !$doc->DIR_GRUPO) {
                return redirect()->route('notas.index')
                    ->with('error', 'No tienes una dirección de grupo asignada. No puedes ver boletines.');
            }
            $cursoDir = $doc->DIR_GRUPO;
        }

        $q           = trim($request->input('q', ''));
        $estudiantes = collect();

        $query = DB::table('ESTUDIANTES')->where('ESTADO', 'MATRICULADO');

        // Docente: solo su curso
        if ($cursoDir) {
            $query->where('CURSO', $cursoDir);
        }

        // Búsqueda por texto (solo para SuperAd/Admin)
        if (!$esDocente && strlen($q) >= 2) {
            $query->where(function ($q2) use ($q) {
                $q2->where('NOMBRE',   'like', "%{$q}%")
                   ->orWhere('APELLIDO1', 'like', "%{$q}%")
                   ->orWhere('APELLIDO2', 'like', "%{$q}%")
                   ->orWhere('CODIGO',    'like', "%{$q}%");
            });
        }

        // Docente: carga todos sus estudiantes directo sin necesidad de búsqueda
        if ($esDocente || strlen($q) >= 2) {
            $estudiantes = $query->orderBy('APELLIDO1')->orderBy('APELLIDO2')->orderBy('NOMBRE1')->get();
        }

        $puedeImprimir = !$esDocente;

        return view('informes.boletin', compact('estudiantes', 'q', 'esDocente', 'cursoDir', 'puedeImprimir'));
    }

    public function ver(int $codigo)
    {
        $profile   = auth()->user()->PROFILE;
        $esDocente = str_starts_with($profile, 'DOC');

        if ($esDocente) {
            $doc = DB::table('CODIGOS_DOC')->where('CODIGO_DOC', $profile)->first();
            if (!$doc || !$doc->DIR_GRUPO) abort(403, 'No tienes dirección de grupo asignada.');

            $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();
            if (!$estudiante || $estudiante->CURSO !== $doc->DIR_GRUPO) {
                abort(403, 'Este estudiante no pertenece a tu curso.');
            }
        }

        $datos = self::datos($codigo);
        if (empty($datos)) abort(404);

        $origen        = 'interno';
        $puedeImprimir = !$esDocente;

        return view('boletines.ver', array_merge($datos, compact('origen', 'puedeImprimir')));
    }
}
