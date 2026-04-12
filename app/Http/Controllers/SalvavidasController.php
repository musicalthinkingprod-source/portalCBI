<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\FechasController;

class SalvavidasController extends Controller
{
    public function index(Request $request)
    {
        $profile    = auth()->user()->PROFILE;
        $esSuperior = in_array($profile, ['SuperAd', 'Admin']);

        $queryAsig = DB::table('ASIGNACION_PCM as a')
            ->join('CODIGOSMAT as m', 'a.CODIGO_MAT', '=', 'm.CODIGO_MAT')
            ->where('a.calificable', 1)
            ->select('a.CODIGO_DOC', 'a.CODIGO_MAT', 'a.CURSO', 'm.NOMBRE_MAT');

        if (!$esSuperior) {
            $queryAsig->where('a.CODIGO_DOC', $profile);
        }

        $asignaciones = $queryAsig->orderBy('m.NOMBRE_MAT')->orderBy('a.CURSO')->get();
        $materias     = $asignaciones->unique('CODIGO_MAT')->values();

        $matSelec     = $request->input('materia');
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
        foreach ($mapaMateriasCursos as &$cursos) {
            $cursos = array_values(array_unique($cursos));
        }

        $estudiantes  = collect();
        $enSalvavidas = [];

        if ($matSelec && $cursoSelec) {
            $estudiantes = DB::table('ESTUDIANTES')
                ->where('CURSO', $cursoSelec)
                ->where('ESTADO', 'MATRICULADO')
                ->orderBy('APELLIDO1')->orderBy('APELLIDO2')->orderBy('NOMBRE1')
                ->get();

            $enSalvavidas = DB::table('Salvavidas')
                ->where('CODIGO_MAT', $matSelec)
                ->where('PERIODO', $periodoSelec)
                ->where('ANIO', $anio)
                ->whereIn('CODIGO_ALUM', $estudiantes->pluck('CODIGO')->toArray())
                ->pluck('CODIGO_ALUM')
                ->toArray();
        }

        // Períodos abiertos para subir salvavidas ('V1'..'V4' = ventana docentes)
        $periodosAbiertos = [];
        if ($esSuperior) {
            $periodosAbiertos = [1, 2, 3, 4];
        } else {
            foreach ([1, 2, 3, 4] as $p) {
                if (FechasController::estaActivo('V' . $p)) {
                    $periodosAbiertos[] = $p;
                }
            }
        }

        $materiaNombre = $matSelec
            ? ($materias->firstWhere('CODIGO_MAT', $matSelec)->NOMBRE_MAT ?? '')
            : '';

        return view('salvavidas.index', compact(
            'materias', 'cursosDisponibles', 'matSelec', 'cursoSelec',
            'periodoSelec', 'anio', 'estudiantes', 'enSalvavidas',
            'mapaMateriasCursos', 'materiaNombre', 'periodosAbiertos'
        ));
    }

    public function guardar(Request $request)
    {
        $profile      = auth()->user()->PROFILE;
        $esSuperior   = in_array($profile, ['SuperAd', 'Admin']);
        $materia      = $request->input('CODIGO_MAT');
        $curso        = $request->input('curso');
        $periodo      = (int) $request->input('periodo');
        $anio         = (int) date('Y');
        $marcados     = $request->input('salvavidas', []);

        if (!$esSuperior && !FechasController::estaActivo('V' . $periodo)) {
            return back()->withErrors(['fechas' => "El período {$periodo} de salvavidas no está abierto para subir."]);
        }

        // Todos los estudiantes del curso para saber a quién borrar
        $todosLosCodigos = DB::table('ESTUDIANTES')
            ->where('CURSO', $curso)
            ->where('ESTADO', 'MATRICULADO')
            ->pluck('CODIGO')
            ->toArray();

        // Eliminar los que fueron desmarcados
        DB::table('Salvavidas')
            ->where('CODIGO_MAT', $materia)
            ->where('PERIODO', $periodo)
            ->where('ANIO', $anio)
            ->whereIn('CODIGO_ALUM', $todosLosCodigos)
            ->whereNotIn('CODIGO_ALUM', array_map('intval', $marcados))
            ->delete();

        // Insertar los nuevos marcados
        foreach ($marcados as $codigoAlum) {
            $existe = DB::table('Salvavidas')
                ->where('CODIGO_ALUM', $codigoAlum)
                ->where('CODIGO_MAT', $materia)
                ->where('PERIODO', $periodo)
                ->where('ANIO', $anio)
                ->exists();

            if (!$existe) {
                DB::table('Salvavidas')->insert([
                    'CODIGO_ALUM' => $codigoAlum,
                    'CODIGO_MAT'  => $materia,
                    'PERIODO'     => $periodo,
                    'ANIO'        => $anio,
                    'CODIGO_DOC'  => $profile,
                ]);
            }
        }

        return back()->with('success', 'Salvavidas guardados correctamente.');
    }

    public function padres()
    {
        $estudiante = session('padre_estudiante');
        if (!$estudiante) return redirect()->route('padres.portal');

        // Verificar que haya algún período de salvavidas abierto
        $abiertoSalvavidas = collect([1,2,3,4])->contains(fn($p) => FechasController::estaActivo('S'.$p));
        if (!$abiertoSalvavidas) {
            return redirect()->route('padres.portal')->with('aviso', 'La institución aún no ha habilitado la consulta de salvavidas.');
        }

        $anio    = (int) date('Y');
        $codigo  = $estudiante->CODIGO;

        $salvavidas = DB::table('Salvavidas as s')
            ->join('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 's.CODIGO_MAT')
            ->where('s.CODIGO_ALUM', $codigo)
            ->where('s.ANIO', $anio)
            ->select('s.PERIODO', 's.CODIGO_MAT', 'm.NOMBRE_MAT')
            ->orderBy('s.PERIODO')->orderBy('m.NOMBRE_MAT')
            ->get();

        $curso = $estudiante->CURSO ?? '';
        $urlsSite = $salvavidas->pluck('CODIGO_MAT')->unique()
            ->mapWithKeys(fn($cm) => [$cm => \App\Http\Controllers\PadresController::urlSite((int)$cm, $curso)])
            ->toArray();

        return view('salvavidas.padres', compact('salvavidas', 'anio', 'urlsSite'));
    }

    public function reporte(Request $request)
    {
        $anio        = $request->input('anio', date('Y'));
        $periodo     = $request->input('periodo');
        $cursoFiltro = $request->input('curso');
        $busqueda    = $request->input('busqueda');

        $query = DB::table('Salvavidas as s')
            ->join('ESTUDIANTES as e', 'e.CODIGO', '=', 's.CODIGO_ALUM')
            ->join('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 's.CODIGO_MAT')
            ->leftJoin('CODIGOS_DOC as d', 'd.CODIGO_DOC', '=', 's.CODIGO_DOC')
            ->where('s.ANIO', $anio)
            ->select(
                'e.CODIGO', 'e.APELLIDO1', 'e.APELLIDO2', 'e.NOMBRE1', 'e.NOMBRE2', 'e.CURSO',
                'm.NOMBRE_MAT', 's.PERIODO', 'd.NOMBRE_DOC', 's.CODIGO_DOC'
            );

        if ($periodo)     $query->where('s.PERIODO', $periodo);
        if ($cursoFiltro) $query->where('e.CURSO', $cursoFiltro);
        if ($busqueda) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('e.APELLIDO1', 'like', "%$busqueda%")
                  ->orWhere('e.APELLIDO2', 'like', "%$busqueda%")
                  ->orWhere('e.NOMBRE1',   'like', "%$busqueda%");
            });
        }

        $registros = $query->orderBy('e.APELLIDO1')->orderBy('s.PERIODO')->get();

        $cursos = DB::table('ESTUDIANTES')
            ->where('ESTADO', 'MATRICULADO')
            ->distinct()->orderBy('CURSO')->pluck('CURSO');

        return view('salvavidas.reporte', compact(
            'registros', 'anio', 'periodo', 'cursoFiltro', 'busqueda', 'cursos'
        ));
    }
}
