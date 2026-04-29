<?php

namespace App\Http\Controllers;

use App\Helpers\PonderacionArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BoletinController extends Controller
{
    public static function datos(int $codigo, ?int $periodo = null): array
    {
        $anio       = (int) date('Y');
        $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();

        if (!$estudiante) return [];

        // Notas con materia y área
        // Extraer grado numérico del curso (ej. "2A" → 2, "10B" → 10)
        $grado = (int) preg_replace('/\D/', '', $estudiante->CURSO ?? '');

        $hayLogros = DB::getSchemaBuilder()->hasTable('LOGROS_' . $anio);

        $notasRaw = collect();
        try {
            $q = DB::table('NOTAS_' . $anio . ' as n')
                ->join('CODIGOSMAT as m',    'm.CODIGO_MAT',   '=', 'n.CODIGO_MAT')
                ->join('CODIGOSAREA as a',   'a.CODIGO_AREA',  '=', 'm.AREA_MAT')
                ->leftJoin('CODIGOS_DOC as d', 'd.CODIGO_EMP', '=', 'n.CODIGO_EMP');

            if ($hayLogros) {
                $q->leftJoin('LOGROS_' . $anio . ' as l', function ($join) use ($grado) {
                    $join->on('l.CODIGO_MAT', '=', 'n.CODIGO_MAT')
                         ->on('l.PERIODO',    '=', 'n.PERIODO')
                         ->where('l.GRADO',   '=', $grado);
                });
            }

            $selectLogro = $hayLogros ? 'l.LOGRO' : DB::raw('NULL as LOGRO');
            $q->where('n.CODIGO_ALUM', $codigo)
              ->select([
                  'n.PERIODO', 'n.NOTA', 'n.TIPODENOTA',
                  'm.NOMBRE_MAT', 'm.CODIGO_MAT',
                  'a.NOMBRE_AREA', 'a.CODIGO_AREA',
                  'd.NOMBRE_DOC',
                  $selectLogro,
              ])
              ->orderBy('a.CODIGO_AREA')
              ->orderBy('m.NOMBRE_MAT');

            if ($periodo !== null) {
                $q->where('n.PERIODO', $periodo);
            }

            $notasRaw = $q->get();
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
                $areas[$ak]['materias'][$mk] = ['nombre' => $n->NOMBRE_MAT, 'docente' => null, 'periodos' => []];
            }
            // Conservar el primer docente disponible a nivel de materia
            if ($n->NOMBRE_DOC && !$areas[$ak]['materias'][$mk]['docente']) {
                $areas[$ak]['materias'][$mk]['docente'] = $n->NOMBRE_DOC;
            }
            $areas[$ak]['materias'][$mk]['periodos'][$n->PERIODO] = [
                'nota'  => $n->NOTA,
                'tipo'  => $n->TIPODENOTA,
                'logro' => $n->LOGRO,
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

        $periodoFiltro = $periodo;
        $nivel         = PonderacionArea::nivel($estudiante->CURSO ?? null);
        return compact('estudiante', 'areas', 'observaciones', 'director', 'anio', 'periodoFiltro', 'nivel');
    }

    private function accesoDocente(): array|null
    {
        $profile = auth()->user()->PROFILE;
        if (!str_starts_with($profile, 'DOC') && !str_starts_with($profile, 'COR')) return null; // no es docente

        $docente = DB::table('CODIGOS_DOC')->where('CODIGO_EMP', $profile)->first();
        return $docente ? (array) $docente : [];
    }

    public function promedios(int $codigo)
    {
        $datos = self::datos($codigo);
        if (empty($datos)) abort(404);

        $periodosVisibles = [1, 2, 3, 4];
        $origen           = 'interno';
        return view('promedios.informe', array_merge($datos, compact('origen', 'periodosVisibles')));
    }

    public function buscar(Request $request)
    {
        $profile      = auth()->user()->PROFILE;
        $user         = auth()->user()->USER;
        $esDocente    = str_starts_with($profile, 'DOC') || str_starts_with($profile, 'COR');
        $esOrientador = str_starts_with($profile, 'Ori');
        $cursoDir     = null;

        if ($esDocente) {
            $doc = DB::table('CODIGOS_DOC')->where('CODIGO_EMP', $profile)->first();
            if (!$doc || !$doc->DIR_GRUPO) {
                return redirect()->route('notas.index')
                    ->with('error', 'No tienes una dirección de grupo asignada. No puedes ver boletines.');
            }
            $cursoDir = $doc->DIR_GRUPO;
        }

        $q           = trim($request->input('q', ''));
        $estudiantes = collect();

        if ($esOrientador) {
            // Todos los estudiantes que tienen PIAR registrado
            $codigosPiar = DB::table('PIAR_DIAG')->pluck('CODIGO_ALUM');

            $estudiantes = DB::table('ESTUDIANTES')
                ->where('ESTADO', 'MATRICULADO')
                ->whereIn('CODIGO', $codigosPiar)
                ->orderBy('APELLIDO1')->orderBy('APELLIDO2')->orderBy('NOMBRE1')
                ->get();
        } else {
            $query = DB::table('ESTUDIANTES')->where('ESTADO', 'MATRICULADO');

            if ($cursoDir) {
                $query->where('CURSO', $cursoDir);
            }

            if (!$esDocente && strlen($q) >= 2) {
                $query->where(function ($q2) use ($q) {
                    $q2->where('NOMBRE1',   'like', "%{$q}%")
                       ->orWhere('APELLIDO1', 'like', "%{$q}%")
                       ->orWhere('APELLIDO2', 'like', "%{$q}%")
                       ->orWhere('CODIGO',    'like', "%{$q}%");
                });
            }

            if ($esDocente || strlen($q) >= 2) {
                $estudiantes = $query->orderBy('APELLIDO1')->orderBy('APELLIDO2')->orderBy('NOMBRE1')->get();
            }
        }

        $puedeImprimir = !$esDocente;

        return view('informes.boletin', compact('estudiantes', 'q', 'esDocente', 'esOrientador', 'cursoDir', 'puedeImprimir'));
    }

    public function ver(int $codigo)
    {
        $profile      = auth()->user()->PROFILE;
        $user         = auth()->user()->USER;
        $esDocente    = str_starts_with($profile, 'DOC') || str_starts_with($profile, 'COR');
        $esOrientador = str_starts_with($profile, 'Ori');

        if ($esDocente) {
            $doc = DB::table('CODIGOS_DOC')->where('CODIGO_EMP', $profile)->first();
            if (!$doc || !$doc->DIR_GRUPO) abort(403, 'No tienes dirección de grupo asignada.');

            $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();
            if (!$estudiante || $estudiante->CURSO !== $doc->DIR_GRUPO) {
                abort(403, 'Este estudiante no pertenece a tu curso.');
            }
        }

        if ($esOrientador) {
            $enPiar = DB::table('PIAR_DIAG')->where('CODIGO_ALUM', $codigo)->exists();
            if (!$enPiar) abort(403, 'Este estudiante no tiene PIAR registrado.');
        }

        $datos = self::datos($codigo);
        if (empty($datos)) abort(404);

        $origen        = 'interno';
        $puedeImprimir = !$esDocente;

        return view('boletines.ver', array_merge($datos, compact('origen', 'puedeImprimir')));
    }
}
