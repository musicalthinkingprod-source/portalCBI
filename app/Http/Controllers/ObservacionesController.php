<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ObservacionesController extends Controller
{
    public function index(Request $request)
    {
        $profile    = auth()->user()->PROFILE;
        $esSuperior = in_array($profile, ['SuperAd', 'Admin']);
        $sinBloqueo = $esSuperior || app()->environment('local');

        // Cualquier perfil puede ser director de grupo si tiene DIR_GRUPO en CODIGOS_DOC
        $docInfo     = DB::table('CODIGOS_DOC')->where('CODIGO_EMP', $profile)->first();
        $isDirector  = $docInfo && !empty($docInfo->DIR_GRUPO);
        $cursoDir    = null;

        if ($isDirector) {
            $cursoDir = $docInfo->DIR_GRUPO;
        } else {
            $cursoDir = $request->input('curso');
        }

        $periodo = max(1, min(4, (int) $request->input('periodo', 1)));

        // Períodos abiertos según FECHAS (SuperAd/Admin y local siempre pueden editar)
        $periodosAbiertos = $sinBloqueo ? [1, 2, 3, 4] : array_values(array_filter(
            [1, 2, 3, 4],
            fn($p) => FechasController::estaActivo('O' . $p)
        ));
        $periodoAbierto = in_array($periodo, $periodosAbiertos);

        $estudiantes   = collect();
        $observaciones = collect();

        if ($cursoDir) {
            $estudiantes = DB::table('ESTUDIANTES')
                ->where('CURSO', $cursoDir)
                ->whereRaw("TRIM(UPPER(ESTADO)) = 'MATRICULADO'")
                ->orderBy('APELLIDO1')->orderBy('APELLIDO2')->orderBy('NOMBRE1')
                ->get();

            $codigos = $estudiantes->pluck('CODIGO')->toArray();

            $observaciones = DB::table('OBSERVACIONES_' . date('Y'))
                ->where('PERIODO', $periodo)
                ->whereIn('CODIGO_ALUM', $codigos)
                ->pluck('OBSERVACION', 'CODIGO_ALUM');
        }

        // Selector de curso solo para SuperAd/Admin sin DIR_GRUPO propio
        $cursos = (!$isDirector) ? DB::table('ASIGNACION_PCM')
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
            })->values() : collect();

        return view('observaciones.index', compact(
            'estudiantes', 'observaciones', 'periodo', 'cursoDir',
            'isDirector', 'cursos', 'periodosAbiertos', 'periodoAbierto'
        ));
    }

    public function store(Request $request)
    {
        $profile    = auth()->user()->PROFILE;
        $esSuperior = in_array($profile, ['SuperAd', 'Admin']);
        $sinBloqueo = $esSuperior || app()->environment('local');
        $periodo    = max(1, min(4, (int) $request->input('periodo')));
        $curso      = $request->input('curso');

        if (!$sinBloqueo && !FechasController::estaActivo('O' . $periodo)) {
            return back()->with('error', 'El período ' . $periodo . ' está cerrado y no permite guardar observaciones.');
        }

        $docInfo    = DB::table('CODIGOS_DOC')->where('CODIGO_EMP', $profile)->first();
        $isDirector = $docInfo && !empty($docInfo->DIR_GRUPO);

        if ($isDirector) {
            $curso = $docInfo->DIR_GRUPO;
        }

        if (!$curso) {
            return back()->with('error', 'Debes seleccionar un curso.');
        }

        $codigosValidos = DB::table('ESTUDIANTES')
            ->where('CURSO', $curso)
            ->whereRaw("TRIM(UPPER(ESTADO)) = 'MATRICULADO'")
            ->pluck('CODIGO')
            ->map(fn($c) => (int) $c)
            ->toArray();

        $tabla = 'OBSERVACIONES_' . date('Y');

        foreach ($request->input('obs', []) as $codigoAlum => $texto) {
            if (!in_array((int) $codigoAlum, $codigosValidos)) continue;

            $texto = trim((string) $texto);

            if ($texto === '') {
                DB::table($tabla)
                    ->where('CODIGO_ALUM', (int) $codigoAlum)
                    ->where('PERIODO', $periodo)
                    ->delete();
            } else {
                DB::table($tabla)->updateOrInsert(
                    ['CODIGO_ALUM' => (int) $codigoAlum, 'PERIODO' => $periodo],
                    ['OBSERVACION' => mb_substr($texto, 0, 4096)]
                );
            }
        }

        return redirect()->route('observaciones.index', ['periodo' => $periodo, 'curso' => $curso])
            ->with('ok', 'Observaciones del período ' . $periodo . ' guardadas correctamente.');
    }
}
