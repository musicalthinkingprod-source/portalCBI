<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotasV2Controller extends Controller
{
    // Pesos por categoría
    const PESOS = ['P' => 0.70, 'C' => 0.20, 'A' => 0.10];

    public function index(Request $request)
    {
        $profile    = auth()->user()->PROFILE;
        $esSuperior = in_array($profile, ['SuperAd', 'Admin']);

        $queryAsig = DB::table('ASIGNACION_PCM as a')
            ->join('CODIGOSMAT as m', 'a.CODIGO_MAT', '=', 'm.CODIGO_MAT')
            ->select('a.CODIGO_DOC', 'a.CODIGO_MAT', 'a.CURSO', 'm.NOMBRE_MAT');

        if (!$esSuperior) {
            $queryAsig->where('a.CODIGO_DOC', $profile);
        }

        $asignaciones = $queryAsig->orderBy('m.NOMBRE_MAT')->orderBy('a.CURSO')->get();
        $materias     = $asignaciones->unique('CODIGO_MAT')->values();

        $matSelec   = $request->input('materia');
        $cursoSelec = $request->input('curso');
        $periodo    = (int) $request->input('periodo', 1);

        $cursosDisponibles = $matSelec
            ? $asignaciones->where('CODIGO_MAT', $matSelec)->unique('CURSO')->values()
            : collect();

        $mapaMateriasCursos = [];
        foreach ($asignaciones as $a) {
            $mapaMateriasCursos[$a->CODIGO_MAT][] = $a->CURSO;
        }
        foreach ($mapaMateriasCursos as &$cs) {
            $cs = array_values(array_unique($cs));
        }

        $materiaNombre = $matSelec
            ? ($materias->firstWhere('CODIGO_MAT', $matSelec)->NOMBRE_MAT ?? '')
            : '';

        // Datos de la planilla
        $columnas   = collect();
        $estudiantes = collect();
        $notasMap   = []; // [columna_id][codigo_alumno] => nota

        if ($matSelec && $cursoSelec) {
            $anio = (int) date('Y');

            $columnas = DB::table('planilla_columnas')
                ->where('codigo_mat', $matSelec)
                ->where('curso', $cursoSelec)
                ->where('periodo', $periodo)
                ->where('anio', $anio)
                ->orderByRaw("FIELD(categoria, 'P', 'C', 'A')")
                ->orderBy('orden')
                ->orderBy('id')
                ->get();

            $estudiantes = DB::table('ESTUDIANTES')
                ->where('CURSO', $cursoSelec)
                ->where('ESTADO', 'MATRICULADO')
                ->orderBy('APELLIDO1')->orderBy('APELLIDO2')->orderBy('NOMBRE1')
                ->get();

            if ($columnas->isNotEmpty()) {
                $notas = DB::table('planilla_notas')
                    ->whereIn('columna_id', $columnas->pluck('id'))
                    ->get();

                foreach ($notas as $n) {
                    $notasMap[$n->columna_id][$n->codigo_alumno] = $n->nota;
                }
            }
        }

        return view('notas.v2', compact(
            'materias', 'cursosDisponibles', 'matSelec', 'cursoSelec', 'periodo',
            'mapaMateriasCursos', 'materiaNombre', 'columnas', 'estudiantes', 'notasMap'
        ));
    }

    public function agregarColumna(Request $request)
    {
        $request->validate([
            'codigo_mat'      => 'required|integer',
            'curso'           => 'required|string',
            'periodo'         => 'required|integer|between:1,4',
            'categoria'       => 'required|in:P,C,A',
            'nombre_actividad'=> 'required|string|max:100',
            'peso'            => 'nullable|numeric|min:0.1|max:999',
        ]);

        $profile = auth()->user()->PROFILE;
        $anio    = (int) date('Y');

        $orden = DB::table('planilla_columnas')
            ->where('codigo_mat', $request->codigo_mat)
            ->where('curso', $request->curso)
            ->where('periodo', $request->periodo)
            ->where('anio', $anio)
            ->where('categoria', $request->categoria)
            ->max('orden') ?? 0;

        DB::table('planilla_columnas')->insert([
            'codigo_doc'      => $profile,
            'codigo_mat'      => $request->codigo_mat,
            'curso'           => $request->curso,
            'periodo'         => $request->periodo,
            'anio'            => $anio,
            'categoria'       => $request->categoria,
            'nombre_actividad'=> trim($request->nombre_actividad),
            'orden'           => $orden + 1,
            'peso'            => $request->filled('peso') ? (float) $request->peso : null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return redirect()->back()->with('success', 'Actividad agregada.');
    }

    public function actualizarPeso(Request $request, $id)
    {
        $request->validate(['peso' => 'nullable|numeric|min:0.1|max:999']);

        DB::table('planilla_columnas')
            ->where('id', $id)
            ->update([
                'peso'       => $request->filled('peso') ? (float) $request->peso : null,
                'updated_at' => now(),
            ]);

        return redirect()->back()->with('success', 'Peso actualizado.');
    }

    public function eliminarColumna($id)
    {
        DB::table('planilla_columnas')->where('id', $id)->delete();
        return redirect()->back()->with('success', 'Columna eliminada.');
    }

    public function guardar(Request $request)
    {
        $notas = $request->input('notas', []); // [columna_id][codigo_alumno] => nota

        foreach ($notas as $columnaId => $alumnos) {
            foreach ($alumnos as $codAlum => $nota) {
                $nota = trim((string) $nota);
                $notaVal = $nota !== '' ? (float) str_replace(',', '.', $nota) : null;

                $existe = DB::table('planilla_notas')
                    ->where('columna_id', $columnaId)
                    ->where('codigo_alumno', $codAlum)
                    ->exists();

                if ($existe) {
                    DB::table('planilla_notas')
                        ->where('columna_id', $columnaId)
                        ->where('codigo_alumno', $codAlum)
                        ->update(['nota' => $notaVal, 'updated_at' => now()]);
                } else {
                    DB::table('planilla_notas')->insert([
                        'columna_id'    => $columnaId,
                        'codigo_alumno' => $codAlum,
                        'nota'          => $notaVal,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ]);
                }
            }
        }

        return redirect()->back()->with('success', 'Planilla guardada correctamente.');
    }
}
