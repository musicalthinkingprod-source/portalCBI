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
            ->where('a.calificable', 1)
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

            $estudiantes = $this->estudiantesPara((int) $matSelec, $cursoSelec);

            if ($columnas->isNotEmpty()) {
                $notas = DB::table('planilla_notas')
                    ->whereIn('columna_id', $columnas->pluck('id'))
                    ->get();

                foreach ($notas as $n) {
                    $notasMap[$n->columna_id][$n->codigo_alumno] = $n->nota;
                }
            }
        }

        // Ventana de entrega activa según FECHAS (N1–N4); SuperAd/Admin siempre pueden
        $codigoVentana = 'N' . $periodo;
        $entregaActiva = $esSuperior || FechasController::estaActivo($codigoVentana);
        $fechaEntrega  = DB::table('FECHAS')->where('CODIGO_FECHA', $codigoVentana)->first();

        return view('notas.v2', compact(
            'materias', 'cursosDisponibles', 'matSelec', 'cursoSelec', 'periodo',
            'mapaMateriasCursos', 'materiaNombre', 'columnas', 'estudiantes', 'notasMap',
            'entregaActiva', 'fechaEntrega', 'esSuperior'
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
            'peso'            => 'nullable|numeric|min:0.001|max:5',
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

        // Validar consistencia de modo si se especifica peso
        if ($request->filled('peso')) {
            $newPeso     = (float) $request->peso;

            if ($newPeso > 1 && floor($newPeso) != $newPeso) {
                return back()->withInput()
                    ->with('error', 'Si el peso es mayor a 1 debe ser un número entero (sin decimales).');
            }
            $existentes  = DB::table('planilla_columnas')
                ->where('codigo_mat', $request->codigo_mat)
                ->where('curso', $request->curso)
                ->where('periodo', $request->periodo)
                ->where('anio', $anio)
                ->where('categoria', $request->categoria)
                ->whereNotNull('peso')
                ->pluck('peso')
                ->map(fn($p) => (float)$p);

            if ($existentes->isNotEmpty()) {
                $tieneDecimal = $existentes->contains(fn($p) => $p < 1);
                $tieneEntero  = $existentes->contains(fn($p) => $p > 1);

                if ($tieneDecimal && $newPeso >= 1) {
                    return back()->withInput()
                        ->with('error', 'Esta categoría usa porcentajes decimales (0–1). El peso debe ser menor a 1.');
                }
                if ($tieneDecimal) {
                    $suma = $existentes->sum() + $newPeso;
                    if ($suma > 1.0001) {
                        return back()->withInput()
                            ->with('error', "Los porcentajes de esta categoría ya suman {$existentes->sum()} y agregar {$newPeso} superaría 1.");
                    }
                }
                if ($tieneEntero && (floor($newPeso) != $newPeso || $newPeso < 1)) {
                    return back()->withInput()
                        ->with('error', 'Esta categoría usa pesos por número de notas (enteros ≥ 1). No se permiten decimales.');
                }
            }
        }

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
        $request->validate(['peso' => 'nullable|numeric|min:0.001|max:5']);

        if ($request->filled('peso')) {
            $col        = DB::table('planilla_columnas')->where('id', $id)->first();
            $newPeso    = (float) $request->peso;

            if ($newPeso > 1 && floor($newPeso) != $newPeso) {
                return back()->with('error', 'Si el peso es mayor a 1 debe ser un número entero (sin decimales).');
            }
            $existentes = DB::table('planilla_columnas')
                ->where('codigo_mat', $col->codigo_mat)
                ->where('curso', $col->curso)
                ->where('periodo', $col->periodo)
                ->where('anio', $col->anio)
                ->where('categoria', $col->categoria)
                ->where('id', '!=', $id)
                ->whereNotNull('peso')
                ->pluck('peso')
                ->map(fn($p) => (float)$p);

            $tieneDecimal = $existentes->contains(fn($p) => $p < 1);
            $tieneEntero  = $existentes->contains(fn($p) => $p > 1);

            if ($tieneDecimal && $newPeso >= 1) {
                return back()->with('error', 'Esta categoría usa porcentajes decimales. El peso debe ser menor a 1.');
            }
            if ($tieneDecimal) {
                $suma = $existentes->sum() + $newPeso;
                if ($suma > 1.0001) {
                    return back()->with('error', "Los porcentajes de las demás actividades suman {$existentes->sum()} y este valor lo superaría.");
                }
            }
            if ($tieneEntero && (floor($newPeso) != $newPeso || $newPeso < 1)) {
                return back()->with('error', 'Esta categoría usa pesos por número de notas (enteros ≥ 1). No se permiten decimales.');
            }
        }

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

    public function actualizarNombre(Request $request, $id)
    {
        $request->validate([
            'nombre_actividad' => 'required|string|max:100',
        ]);

        $col = DB::table('planilla_columnas')->where('id', $id)->first();
        if (!$col) {
            return back()->with('error', 'Actividad no encontrada.');
        }

        $nuevo  = trim($request->nombre_actividad);
        $previo = $col->nombre_actividad;

        if ($nuevo === $previo) {
            return back();
        }

        DB::transaction(function () use ($id, $col, $nuevo, $previo) {
            DB::table('planilla_columnas_historial')->insert([
                'columna_id'       => $id,
                'nombre_anterior'  => $previo,
                'nombre_nuevo'     => $nuevo,
                'codigo_doc'       => auth()->user()->PROFILE,
                'created_at'       => now(),
            ]);

            DB::table('planilla_columnas')
                ->where('id', $id)
                ->update([
                    'nombre_actividad' => $nuevo,
                    'updated_at'       => now(),
                ]);
        });

        return back()->with('success', 'Nombre de actividad actualizado.');
    }

    public function entregar(Request $request)
    {
        $request->validate([
            'codigo_mat' => 'required|integer',
            'curso'      => 'required|string',
            'periodo'    => 'required|integer|between:1,4',
        ]);

        $profile      = auth()->user()->PROFILE;
        $esSuperior   = in_array($profile, ['SuperAd', 'Admin']);
        $codigoMat    = (int) $request->codigo_mat;
        $curso        = $request->curso;
        $periodo      = (int) $request->periodo;
        $anio         = (int) date('Y');

        // Verificar ventana de entrega
        if (!$esSuperior && !FechasController::estaActivo('N' . $periodo)) {
            return back()->with('error_entrega', 'La ventana de entrega de notas para el período ' . $periodo . ' no está abierta en este momento.');
        }

        $columnas = DB::table('planilla_columnas')
            ->where('codigo_mat', $codigoMat)
            ->where('curso', $curso)
            ->where('periodo', $periodo)
            ->where('anio', $anio)
            ->get();

        if ($columnas->isEmpty()) {
            return back()->with('error_entrega', 'No hay actividades registradas en esta planilla.');
        }

        $estudiantes = $this->estudiantesPara($codigoMat, $curso)->pluck('CODIGO');

        $columnaIds = $columnas->pluck('id');

        $notasMap = DB::table('planilla_notas')
            ->whereIn('columna_id', $columnaIds)
            ->whereNotNull('nota')
            ->get()
            ->groupBy('columna_id')
            ->map(fn($rows) => $rows->pluck('nota', 'codigo_alumno'));

        // Validar que no haya celdas vacías
        $faltantes = [];
        foreach ($columnas as $col) {
            foreach ($estudiantes as $codAlum) {
                $nota = $notasMap[$col->id][$codAlum] ?? null;
                if ($nota === null) {
                    $faltantes[] = "Actividad «{$col->nombre_actividad}» — alumno {$codAlum}";
                }
            }
        }

        if (!empty($faltantes)) {
            $detalle = implode('; ', array_slice($faltantes, 0, 10));
            $extra   = count($faltantes) > 10 ? ' (y ' . (count($faltantes) - 10) . ' más)' : '';
            return back()->with('error_entrega', 'Hay notas sin registrar. Completa la planilla antes de entregar. ' . $detalle . $extra);
        }

        // Calcular nota final ponderada por estudiante
        $columnasPorCat = $columnas->groupBy('categoria');

        DB::transaction(function () use ($estudiantes, $columnasPorCat, $notasMap, $codigoMat, $periodo, $profile) {
            foreach ($estudiantes as $codAlum) {
                $sumaTotal = 0;
                $pesoTotal = 0;

                foreach (self::PESOS as $cat => $pesoCat) {
                    $cols = $columnasPorCat[$cat] ?? collect();
                    if ($cols->isEmpty()) continue;

                    $sumPeso = 0;
                    $sumVal  = 0;
                    foreach ($cols as $col) {
                        $nota = $notasMap[$col->id][$codAlum] ?? null;
                        if ($nota === null) continue;
                        $peso     = (float) ($col->peso ?? 1);
                        $sumPeso += $peso;
                        $sumVal  += (float) $nota * $peso;
                    }
                    if ($sumPeso === 0) continue;

                    $promCat    = $sumVal / $sumPeso;
                    $sumaTotal += $promCat * $pesoCat;
                    $pesoTotal += $pesoCat;
                }

                if ($pesoTotal === 0) continue;

                $notaFinal = round($sumaTotal / $pesoTotal, 1);

                DB::table('NOTAS_2026')->updateOrInsert(
                    [
                        'CODIGO_ALUM' => $codAlum,
                        'PERIODO'     => $periodo,
                        'CODIGO_MAT'  => $codigoMat,
                        'TIPODENOTA'  => 'N',
                    ],
                    [
                        'NOTA'       => $notaFinal,
                        'CODIGO_DOC' => $profile,
                    ]
                );
            }
        });

        return back()->with('success', "Notas entregadas correctamente a NOTAS_2026 ({$estudiantes->count()} estudiantes).");
    }

    public function guardar(Request $request)
    {
        $notas = $request->input('notas', []); // [columna_id][codigo_alumno] => nota

        foreach ($notas as $columnaId => $alumnos) {
            foreach ($alumnos as $codAlum => $nota) {
                $nota    = trim((string) $nota);
                $notaVal = $nota !== '' ? (float) str_replace(',', '.', $nota) : null;

                if ($notaVal === null) {
                    // Celda vacía: si ya existía un registro lo borra, si no existía no hace nada
                    DB::table('planilla_notas')
                        ->where('columna_id', $columnaId)
                        ->where('codigo_alumno', $codAlum)
                        ->delete();
                    continue;
                }

                DB::table('planilla_notas')->updateOrInsert(
                    ['columna_id' => $columnaId, 'codigo_alumno' => $codAlum],
                    ['nota' => $notaVal, 'updated_at' => now(), 'created_at' => now()]
                );
            }
        }

        return redirect()->back()->with('success', 'Planilla guardada correctamente.');
    }
}
