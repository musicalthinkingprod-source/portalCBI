<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ObservacionesController extends Controller
{
    public function index(Request $request)
    {
        $profile    = auth()->user()->PROFILE;
        $isDoc      = str_starts_with($profile, 'DOC');
        $esSuperior = in_array($profile, ['SuperAd', 'Admin']);
        $cursoDir   = null;

        if ($isDoc) {
            $doc = DB::table('CODIGOS_DOC')->where('CODIGO_DOC', $profile)->first();
            if (!$doc || !$doc->DIR_GRUPO) {
                return redirect()->route('notas.index')
                    ->with('error', 'No tienes una dirección de grupo asignada.');
            }
            $cursoDir = $doc->DIR_GRUPO;
        } else {
            $cursoDir = $request->input('curso');
        }

        $periodo = max(1, min(4, (int) $request->input('periodo', 1)));

        // Períodos abiertos (mismos códigos N1-N4 que notas)
        $periodosAbiertos = $esSuperior ? [1, 2, 3, 4] : array_filter(
            [1, 2, 3, 4],
            fn($p) => FechasController::estaActivo('N' . $p)
        );
        $periodoAbierto = in_array($periodo, $periodosAbiertos);

        $estudiantes   = collect();
        $observaciones = collect();

        if ($cursoDir) {
            $estudiantes = DB::table('ESTUDIANTES')
                ->where('CURSO', $cursoDir)
                ->where('ESTADO', 'MATRICULADO')
                ->orderBy('APELLIDO1')->orderBy('APELLIDO2')->orderBy('NOMBRE1')
                ->get();

            $codigos = $estudiantes->pluck('CODIGO')->toArray();

            $observaciones = DB::table('OBSERVACIONES_2026')
                ->where('PERIODO', $periodo)
                ->whereIn('CODIGO_ALUM', $codigos)
                ->pluck('OBSERVACION', 'CODIGO_ALUM');
        }

        $cursos = $isDoc ? null : DB::table('ASIGNACION_PCM')
            ->distinct()->pluck('CURSO')
            ->sort(function ($a, $b) {
                $orden = fn($c) => match(true) {
                    $c === 'J'  => [-2, ''],
                    $c === 'T'  => [-1, ''],
                    default     => [(int) $c, ltrim($c, '0123456789')],
                };
                [$na, $la] = $orden($a);
                [$nb, $lb] = $orden($b);
                return $na !== $nb ? $na - $nb : strcmp($la, $lb);
            })->values();

        return view('observaciones.index', compact(
            'estudiantes', 'observaciones', 'periodo', 'cursoDir', 'isDoc', 'cursos',
            'periodosAbiertos', 'periodoAbierto'
        ));
    }

    public function store(Request $request)
    {
        $profile    = auth()->user()->PROFILE;
        $isDoc      = str_starts_with($profile, 'DOC');
        $esSuperior = in_array($profile, ['SuperAd', 'Admin']);
        $periodo    = max(1, min(4, (int) $request->input('periodo')));
        $curso      = $request->input('curso');

        if (!$esSuperior && !FechasController::estaActivo('N' . $periodo)) {
            return back()->with('error', 'El período ' . $periodo . ' está cerrado y no permite guardar observaciones.');
        }

        if ($isDoc) {
            $doc = DB::table('CODIGOS_DOC')->where('CODIGO_DOC', $profile)->first();
            if (!$doc || !$doc->DIR_GRUPO) abort(403);
            $curso = $doc->DIR_GRUPO;
        }

        if (!$curso) {
            return back()->with('error', 'Debes seleccionar un curso.');
        }

        // Only allow codes that belong to this course
        $codigosValidos = DB::table('ESTUDIANTES')
            ->where('CURSO', $curso)
            ->where('ESTADO', 'MATRICULADO')
            ->pluck('CODIGO')
            ->map(fn($c) => (int) $c)
            ->toArray();

        foreach ($request->input('obs', []) as $codigoAlum => $texto) {
            if (!in_array((int) $codigoAlum, $codigosValidos)) continue;

            $texto = trim((string) $texto);

            if ($texto === '') {
                DB::table('OBSERVACIONES_2026')
                    ->where('CODIGO_ALUM', (int) $codigoAlum)
                    ->where('PERIODO', $periodo)
                    ->delete();
            } else {
                DB::table('OBSERVACIONES_2026')->updateOrInsert(
                    ['CODIGO_ALUM' => (int) $codigoAlum, 'PERIODO' => $periodo],
                    ['OBSERVACION' => mb_substr($texto, 0, 512)]
                );
            }
        }

        return redirect()->route('observaciones.index', ['periodo' => $periodo, 'curso' => $curso])
            ->with('ok', 'Observaciones del período ' . $periodo . ' guardadas correctamente.');
    }
}
