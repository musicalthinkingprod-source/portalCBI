<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SolicitudCorreccionController extends Controller
{
    private function tablaNotas(int $anio): string
    {
        return 'NOTAS_' . $anio;
    }

    /** Lista de solicitudes — docentes/orientadores ven las suyas; admins ven todas */
    public function index(Request $request)
    {
        $profile      = auth()->user()->PROFILE;
        $esSuperior   = in_array($profile, ['SuperAd', 'Admin']);
        $esOrientador = str_starts_with($profile, 'Ori');

        $q = DB::table('solicitudes_correccion as s')
            ->leftJoin('CODIGOSMAT as m',   'm.CODIGO_MAT', '=', 's.codigo_mat')
            ->leftJoin('ESTUDIANTES as e',   'e.CODIGO',     '=', 's.codigo_alum')
            ->leftJoin('CODIGOS_DOC as d',   'd.CODIGO_DOC', '=', 's.codigo_doc')
            ->select(
                's.*',
                'm.NOMBRE_MAT',
                DB::raw("TRIM(CONCAT(COALESCE(e.APELLIDO1,''),' ',COALESCE(e.APELLIDO2,''),' ',COALESCE(e.NOMBRE1,''))) as nombre_alumno"),
                'd.NOMBRE_DOC'
            )
            ->orderByRaw("FIELD(s.estado,'PENDIENTE','APROBADA','RECHAZADA')")
            ->orderByDesc('s.created_at');

        if (!$esSuperior) {
            $q->where('s.codigo_doc', $profile);
        }

        // Filtro de estado
        if ($request->filled('estado')) {
            $q->where('s.estado', $request->estado);
        }

        $solicitudes  = $q->paginate(30)->withQueryString();
        $pendientes   = DB::table('solicitudes_correccion')->where('estado', 'PENDIENTE')->count();

        return view('correcciones.index', compact('solicitudes', 'esSuperior', 'esOrientador', 'pendientes'));
    }

    /** Formulario para el docente / orientador */
    public function create(Request $request)
    {
        $profile      = auth()->user()->PROFILE;
        $esSuperior   = in_array($profile, ['SuperAd', 'Admin']);
        $esOrientador = str_starts_with($profile, 'Ori');
        $anio         = (int) date('Y');

        if ($esOrientador) {
            // Orientador ve todas las materias calificables
            $materias = DB::table('CODIGOSMAT')
                ->orderBy('NOMBRE_MAT')
                ->get(['CODIGO_MAT', 'NOMBRE_MAT']);

            $mapaMateriasCursos = []; // no aplica filtro por curso para orientadores
        } else {
            // Asignaciones calificables del docente
            $queryAsig = DB::table('ASIGNACION_PCM as a')
                ->join('CODIGOSMAT as m', 'a.CODIGO_MAT', '=', 'm.CODIGO_MAT')
                ->where('a.calificable', 1)
                ->select('a.CODIGO_DOC', 'a.CODIGO_MAT', 'a.CURSO', 'm.NOMBRE_MAT');

            if (!$esSuperior) {
                $queryAsig->where('a.CODIGO_DOC', $profile);
            }

            $asignaciones = $queryAsig->orderBy('m.NOMBRE_MAT')->orderBy('a.CURSO')->get();
            $materias     = $asignaciones->unique('CODIGO_MAT')->values();

            $mapaMateriasCursos = [];
            foreach ($asignaciones as $a) {
                $mapaMateriasCursos[$a->CODIGO_MAT][] = $a->CURSO;
            }
            foreach ($mapaMateriasCursos as &$cs) {
                $cs = array_values(array_unique($cs));
            }
        }

        $matSelec   = $request->input('materia');
        $cursoSelec = $request->input('curso');
        $periodo    = (int) $request->input('periodo', 1);
        $codAlum    = $request->input('codigo_alum');

        $notaActual = null;
        $alumno     = null;

        if ($matSelec && $codAlum && $periodo) {
            try {
                $notaActual = DB::table($this->tablaNotas($anio))
                    ->where('CODIGO_ALUM', $codAlum)
                    ->where('CODIGO_MAT', $matSelec)
                    ->where('PERIODO', $periodo)
                    ->value('NOTA');
            } catch (\Exception $e) {}

            $alumno = DB::table('ESTUDIANTES')->where('CODIGO', $codAlum)->first();
        }

        if ($esOrientador) {
            // Estudiantes con PIAR — sin filtro por curso
            $codigosPiar = DB::table('PIAR_DIAG')->pluck('CODIGO_ALUM');
            $estudiantes = DB::table('ESTUDIANTES')
                ->where('ESTADO', 'MATRICULADO')
                ->whereIn('CODIGO', $codigosPiar)
                ->orderBy('APELLIDO1')->orderBy('NOMBRE1')
                ->get();
        } else {
            $estudiantes = collect();
            if ($matSelec && $cursoSelec) {
                $esListadoEspecial = (bool) preg_match('/^(GP\d+|\d+[A-Z]?-\d)$/', $cursoSelec);

                if ($esListadoEspecial) {
                    $estudiantes = DB::table('LISTADOS_ESPECIALES as le')
                        ->join('ESTUDIANTES as e', 'le.CODIGO_ALUM', '=', 'e.CODIGO')
                        ->where('le.GRUPO', $cursoSelec)
                        ->where('e.ESTADO', 'MATRICULADO')
                        ->select('e.*')
                        ->orderBy('e.APELLIDO1')->orderBy('e.NOMBRE1')
                        ->get();
                } else {
                    $estudiantes = DB::table('ESTUDIANTES')
                        ->where('CURSO', $cursoSelec)
                        ->where('ESTADO', 'MATRICULADO')
                        ->orderBy('APELLIDO1')->orderBy('NOMBRE1')
                        ->get();
                }
            }
        }

        return view('correcciones.create', compact(
            'materias', 'mapaMateriasCursos', 'matSelec', 'cursoSelec', 'periodo',
            'codAlum', 'notaActual', 'alumno', 'estudiantes', 'anio', 'esOrientador'
        ));
    }

    /** Guardar la solicitud */
    public function store(Request $request)
    {
        $request->validate([
            'codigo_mat'    => 'required|integer',
            'codigo_alum'   => 'required|integer',
            'periodo'       => 'required|integer|between:1,4',
            'nota_propuesta'=> 'required|numeric|min:0|max:10',
            'motivo'        => 'required|string|min:10|max:1000',
        ]);

        $profile = auth()->user()->PROFILE;
        $anio    = (int) date('Y');

        // Tomar la nota actual directamente de la BD
        try {
            $notaReal = DB::table($this->tablaNotas($anio))
                ->where('CODIGO_ALUM', $request->codigo_alum)
                ->where('CODIGO_MAT',  $request->codigo_mat)
                ->where('PERIODO',     $request->periodo)
                ->value('NOTA');
        } catch (\Exception $e) {
            $notaReal = null;
        }

        if ($notaReal === null) {
            return back()->withErrors(['nota_propuesta' => 'No se encontró una nota registrada para este estudiante en ese período.'])->withInput();
        }

        if ((float) $notaReal === (float) $request->nota_propuesta) {
            return back()->withErrors(['nota_propuesta' => 'La nota propuesta es igual a la nota actual.'])->withInput();
        }

        // Verificar que no haya una solicitud PENDIENTE igual
        $yaPendiente = DB::table('solicitudes_correccion')
            ->where('codigo_doc',  $profile)
            ->where('codigo_alum', $request->codigo_alum)
            ->where('codigo_mat',  $request->codigo_mat)
            ->where('periodo',     $request->periodo)
            ->where('anio',        $anio)
            ->where('estado',      'PENDIENTE')
            ->exists();

        if ($yaPendiente) {
            return back()->withErrors(['motivo' => 'Ya hay una solicitud pendiente para esta nota.'])->withInput();
        }

        DB::table('solicitudes_correccion')->insert([
            'codigo_doc'     => $profile,
            'codigo_alum'    => $request->codigo_alum,
            'codigo_mat'     => $request->codigo_mat,
            'periodo'        => $request->periodo,
            'anio'           => $anio,
            'nota_actual'    => $notaReal,
            'nota_propuesta' => $request->nota_propuesta,
            'motivo'         => trim($request->motivo),
            'estado'         => 'PENDIENTE',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return redirect()->route('correcciones.index')
            ->with('success', 'Solicitud enviada. Será revisada por un administrador.');
    }

    /** Admin aprueba → aplica la corrección en NOTAS */
    public function aprobar(Request $request, $id)
    {
        $request->validate([
            'observacion' => 'nullable|string|max:500',
        ]);

        $sol = DB::table('solicitudes_correccion')->where('id', $id)->first();
        abort_if(!$sol || $sol->estado !== 'PENDIENTE', 404);

        $revisor = auth()->user()->PROFILE;
        $tabla   = $this->tablaNotas($sol->anio);

        // Aplicar la corrección en la tabla de notas
        DB::table($tabla)
            ->where('CODIGO_ALUM', $sol->codigo_alum)
            ->where('CODIGO_MAT',  $sol->codigo_mat)
            ->where('PERIODO',     $sol->periodo)
            ->update(['NOTA' => $sol->nota_propuesta, 'TIPODENOTA' => 'N']);

        DB::table('solicitudes_correccion')->where('id', $id)->update([
            'estado'       => 'APROBADA',
            'revisado_por' => $revisor,
            'observacion'  => $request->observacion,
            'revisado_at'  => now(),
            'updated_at'   => now(),
        ]);

        return back()->with('success', "Solicitud aprobada. Nota actualizada a {$sol->nota_propuesta}.");
    }

    /** Admin rechaza */
    public function rechazar(Request $request, $id)
    {
        $request->validate([
            'observacion' => 'required|string|min:5|max:500',
        ]);

        $sol = DB::table('solicitudes_correccion')->where('id', $id)->first();
        abort_if(!$sol || $sol->estado !== 'PENDIENTE', 404);

        DB::table('solicitudes_correccion')->where('id', $id)->update([
            'estado'       => 'RECHAZADA',
            'revisado_por' => auth()->user()->PROFILE,
            'observacion'  => $request->observacion,
            'revisado_at'  => now(),
            'updated_at'   => now(),
        ]);

        return back()->with('success', 'Solicitud rechazada.');
    }
}
