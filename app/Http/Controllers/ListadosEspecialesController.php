<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListadosEspecialesController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->input('tab', 'proyectos');

        // ── PROYECTOS ─────────────────────────────────────────────────────
        // Grupos de LISTADOS_ESPECIALES (ya asignados) + ASIGNACION_PCM MAT=31 (recién creados)
        $gruposDeLE   = DB::table('LISTADOS_ESPECIALES')
            ->where('GRUPO', 'like', 'GP%')->distinct()->pluck('GRUPO');
        $gruposDeAsig = DB::table('ASIGNACION_PCM')
            ->where('CODIGO_MAT', 31)->distinct()->pluck('CURSO');

        $gruposRaw = $gruposDeLE->merge($gruposDeAsig)
            ->unique()
            ->sort(fn($a, $b) => (int) substr($a, 2) - (int) substr($b, 2))
            ->values();

        // Docentes asignados por grupo
        $docentesPorGrupo = DB::table('ASIGNACION_PCM as a')
            ->leftJoin('CODIGOS_DOC as d', 'a.CODIGO_EMP', '=', 'd.CODIGO_EMP')
            ->where('a.CODIGO_MAT', 31)
            ->select('a.CURSO as grupo', 'a.CODIGO_EMP',
                     DB::raw("COALESCE(d.NOMBRE_DOC, a.CODIGO_EMP) as NOMBRE_DOC"))
            ->get()->keyBy('grupo');

        $gruposProyecto = $gruposRaw->map(fn($g) => (object)[
            'grupo'      => $g,
            'CODIGO_EMP' => $docentesPorGrupo[$g]->CODIGO_EMP ?? null,
            'NOMBRE_DOC' => $docentesPorGrupo[$g]->NOMBRE_DOC ?? null,
        ]);

        // Estudiantes por grupo
        $estudiantesProyecto = [];
        foreach ($gruposRaw as $gp) {
            $estudiantesProyecto[$gp] = DB::table('LISTADOS_ESPECIALES as le')
                ->join('ESTUDIANTES as e', 'le.CODIGO_ALUM', '=', 'e.CODIGO')
                ->where('le.GRUPO', $gp)
                ->where('e.ESTADO', 'MATRICULADO')
                ->select('e.CODIGO', 'e.NOMBRE1', 'e.NOMBRE2',
                         'e.APELLIDO1', 'e.APELLIDO2', 'e.CURSO as CURSO_ALUM')
                ->orderBy('e.APELLIDO1')->orderBy('e.APELLIDO2')
                ->get();
        }

        // Estudiantes sin proyecto
        $codigosEnProyecto = DB::table('LISTADOS_ESPECIALES')
            ->where('GRUPO', 'like', 'GP%')->pluck('CODIGO_ALUM')->toArray();

        $sinProyecto = DB::table('ESTUDIANTES')
            ->where('ESTADO', 'MATRICULADO')
            ->whereNotIn('CODIGO', $codigosEnProyecto)
            ->select('CODIGO', 'NOMBRE1', 'NOMBRE2', 'APELLIDO1', 'APELLIDO2', 'CURSO')
            ->orderBy('APELLIDO1')->orderBy('APELLIDO2')
            ->get();

        // Siguiente número de grupo sugerido
        $ultimoNum = $gruposRaw->map(fn($g) => (int) substr($g, 2))->max() ?? 0;
        $siguienteGrupo = 'GP' . ($ultimoNum + 1);

        // Docentes activos
        $docentesActivos = DB::table('CODIGOS_DOC')
            ->where('ESTADO', 'ACTIVO')->orderBy('NOMBRE_DOC')
            ->get(['CODIGO_EMP', 'NOMBRE_DOC']);

        // ── MÚSICA Y ARTES ─────────────────────────────────────────────────
        $cursoMusica = $request->input('curso_musica', '');

        $todosLosCursos = DB::table('ESTUDIANTES')
            ->where('ESTADO', 'MATRICULADO')->distinct()->pluck('CURSO')
            ->filter(fn($c) => preg_match('/^\d/', $c) && (int) $c >= 7)
            ->sortBy(fn($c) => [(int) $c, preg_replace('/^\d+/', '', $c)])
            ->values();

        $estudiantesArtes  = collect();
        $estudiantesMusica = collect();
        $sinAsignarMusica  = collect();

        if ($cursoMusica) {
            $grupoArtes  = $cursoMusica . '-1';
            $grupoMusica = $cursoMusica . '-2';

            $estudiantesArtes = DB::table('LISTADOS_ESPECIALES as le')
                ->join('ESTUDIANTES as e', 'le.CODIGO_ALUM', '=', 'e.CODIGO')
                ->where('le.GRUPO', $grupoArtes)->where('e.ESTADO', 'MATRICULADO')
                ->select('e.CODIGO', 'e.NOMBRE1', 'e.NOMBRE2', 'e.APELLIDO1', 'e.APELLIDO2')
                ->orderBy('e.APELLIDO1')->orderBy('e.APELLIDO2')->get();

            $estudiantesMusica = DB::table('LISTADOS_ESPECIALES as le')
                ->join('ESTUDIANTES as e', 'le.CODIGO_ALUM', '=', 'e.CODIGO')
                ->where('le.GRUPO', $grupoMusica)->where('e.ESTADO', 'MATRICULADO')
                ->select('e.CODIGO', 'e.NOMBRE1', 'e.NOMBRE2', 'e.APELLIDO1', 'e.APELLIDO2')
                ->orderBy('e.APELLIDO1')->orderBy('e.APELLIDO2')->get();

            $asignados = DB::table('LISTADOS_ESPECIALES')
                ->whereIn('GRUPO', [$grupoArtes, $grupoMusica])->pluck('CODIGO_ALUM');

            $sinAsignarMusica = DB::table('ESTUDIANTES')
                ->where('CURSO', $cursoMusica)->where('ESTADO', 'MATRICULADO')
                ->whereNotIn('CODIGO', $asignados)
                ->select('CODIGO', 'NOMBRE1', 'NOMBRE2', 'APELLIDO1', 'APELLIDO2')
                ->orderBy('APELLIDO1')->orderBy('APELLIDO2')->get();
        }

        return view('listados-especiales.index', compact(
            'tab',
            'gruposProyecto', 'estudiantesProyecto', 'sinProyecto',
            'siguienteGrupo', 'docentesActivos',
            'todosLosCursos', 'cursoMusica',
            'estudiantesArtes', 'estudiantesMusica', 'sinAsignarMusica'
        ));
    }

    /** Crear un nuevo grupo de proyecto. */
    public function crearGrupo(Request $request)
    {
        $grupo     = strtoupper(trim($request->input('grupo')));
        $codigoDoc = trim($request->input('codigo_emp', '')) ?: null;

        if (!preg_match('/^GP\d+$/', $grupo)) {
            return back()->withErrors(['error' => "Nombre de grupo inválido. Use formato GP1, GP2, etc."]);
        }
        if (strlen($grupo) > 5) {
            return back()->withErrors(['error' => "Nombre demasiado largo (máx 5 caracteres)."]);
        }

        $yaExiste = DB::table('ASIGNACION_PCM')
            ->where('CODIGO_MAT', 31)->where('CURSO', $grupo)->exists();

        if ($yaExiste) {
            return back()->withErrors(['error' => "El grupo {$grupo} ya existe."]);
        }

        DB::table('ASIGNACION_PCM')->insert([
            'CODIGO_EMP' => $codigoDoc,
            'CODIGO_MAT' => 31,
            'CURSO'      => $grupo,
            'IHS'        => null,
        ]);

        return redirect()->route('listados.index', ['tab' => 'proyectos'])
            ->with('success', "Grupo {$grupo} creado correctamente.");
    }

    /** Asignar / cambiar docente de un grupo. */
    public function asignarDocente(Request $request)
    {
        $grupo     = strtoupper(trim($request->input('grupo')));
        $codigoDoc = trim($request->input('codigo_emp'));

        if (!$grupo || !$codigoDoc) {
            return back()->withErrors(['error' => 'Datos incompletos.']);
        }

        DB::table('ASIGNACION_PCM')
            ->where('CODIGO_MAT', 31)->where('CURSO', $grupo)->delete();

        DB::table('ASIGNACION_PCM')->insert([
            'CODIGO_EMP' => $codigoDoc,
            'CODIGO_MAT' => 31,
            'CURSO'      => $grupo,
            'IHS'        => null,
        ]);

        return redirect()->route('listados.index', ['tab' => 'proyectos'])
            ->with('success', "Docente asignado al grupo {$grupo} correctamente.");
    }

    /** Asignar un estudiante a un grupo GP*. */
    public function asignarProyecto(Request $request)
    {
        $codigoAlum = (int) $request->input('codigo_alum');
        $grupo      = strtoupper(trim($request->input('grupo')));

        if (!$codigoAlum || !$grupo) {
            return back()->withErrors(['error' => 'Datos incompletos.']);
        }

        // Quitar asignación GP anterior
        DB::table('LISTADOS_ESPECIALES')
            ->where('CODIGO_ALUM', $codigoAlum)->where('GRUPO', 'like', 'GP%')->delete();

        DB::table('LISTADOS_ESPECIALES')->insert([
            'CODIGO_ALUM' => $codigoAlum,
            'GRUPO'       => $grupo,
        ]);

        return redirect()->route('listados.index', ['tab' => 'proyectos'])
            ->with('success', 'Estudiante asignado al proyecto correctamente.');
    }

    /** Asignar a artes (-1) o música (-2). */
    public function asignarMusica(Request $request)
    {
        $codigoAlum = (int) $request->input('codigo_alum');
        $curso      = strtoupper(trim($request->input('curso')));
        $tipo       = $request->input('tipo');

        if (!$codigoAlum || !$curso || !in_array($tipo, ['1', '2'])) {
            return back()->withErrors(['error' => 'Datos incompletos.']);
        }

        $grupo = $curso . '-' . $tipo;
        if (strlen($grupo) > 5) {
            return back()->withErrors(['error' => "Nombre de grupo '{$grupo}' demasiado largo."]);
        }

        DB::table('LISTADOS_ESPECIALES')
            ->where('CODIGO_ALUM', $codigoAlum)
            ->whereIn('GRUPO', [$curso . '-1', $curso . '-2'])->delete();

        DB::table('LISTADOS_ESPECIALES')->insert([
            'CODIGO_ALUM' => $codigoAlum,
            'GRUPO'       => $grupo,
        ]);

        return redirect()->route('listados.index', ['tab' => 'musica', 'curso_musica' => $curso])
            ->with('success', 'Estudiante asignado correctamente.');
    }

    /** Quitar un estudiante de cualquier grupo. */
    public function quitar(Request $request)
    {
        $codigoAlum = (int) $request->input('codigo_alum');
        $grupo      = $request->input('grupo');
        $tab        = $request->input('tab', 'proyectos');
        $curso      = $request->input('curso_musica', '');

        DB::table('LISTADOS_ESPECIALES')
            ->where('CODIGO_ALUM', $codigoAlum)->where('GRUPO', $grupo)->delete();

        $params = ['tab' => $tab];
        if ($curso) $params['curso_musica'] = $curso;

        return redirect()->route('listados.index', $params)
            ->with('success', 'Estudiante removido del listado.');
    }

    /** Eliminar un grupo vacío (solo de ASIGNACION_PCM). */
    public function eliminarGrupo(Request $request)
    {
        $grupo = strtoupper(trim($request->input('grupo')));

        $tieneEstudiantes = DB::table('LISTADOS_ESPECIALES')
            ->where('GRUPO', $grupo)->exists();

        if ($tieneEstudiantes) {
            return back()->withErrors(['error' => "No se puede eliminar {$grupo}: tiene estudiantes asignados."]);
        }

        DB::table('ASIGNACION_PCM')
            ->where('CODIGO_MAT', 31)->where('CURSO', $grupo)->delete();

        return redirect()->route('listados.index', ['tab' => 'proyectos'])
            ->with('success', "Grupo {$grupo} eliminado.");
    }
}
