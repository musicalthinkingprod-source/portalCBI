<?php

namespace App\Http\Controllers;

use App\Models\AdmisionEvaluacion;
use App\Support\ExamenesAdmision;
use App\Support\OmrLayout;
use Illuminate\Http\Request;

class AdmisionController extends Controller
{
    /** Selector de grado + histórico de evaluaciones. */
    public function index(Request $request)
    {
        $grados = ExamenesAdmision::grados();

        $q = AdmisionEvaluacion::query()->latest();

        if ($g = $request->input('grado')) {
            $q->where('grado_key', $g);
        }
        if ($buscar = trim((string) $request->input('buscar', ''))) {
            $q->where(function ($w) use ($buscar) {
                $w->where('aspirante_nombre', 'like', "%$buscar%")
                  ->orWhere('aspirante_documento', 'like', "%$buscar%");
            });
        }

        $evaluaciones = $q->paginate(20)->withQueryString();

        return view('admision.index', compact('grados', 'evaluaciones'));
    }

    /** Formulario de captura de respuestas para un grado. */
    public function create(Request $request)
    {
        $key = strtoupper((string) $request->input('grado', ''));

        if (!ExamenesAdmision::existe($key)) {
            return redirect()->route('admision.index')
                ->with('error', 'Selecciona un grado válido para iniciar la evaluación.');
        }

        $grado = ExamenesAdmision::grado($key);

        // Layout OMR solo para grados de opción múltiple (lector en navegador).
        $omr = null;
        if ($grado['tipo'] === 'opcion_multiple') {
            $omr = OmrLayout::build(array_map(fn ($m) => $m['nombre'], $grado['materias']));
        }

        return view('admision.form', compact('grado', 'key', 'omr'));
    }

    /** Guarda la evaluación calificada. */
    public function store(Request $request)
    {
        $key = strtoupper((string) $request->input('grado', ''));

        if (!ExamenesAdmision::existe($key)) {
            return back()->with('error', 'Grado no válido.')->withInput();
        }

        $grado = ExamenesAdmision::grado($key);

        $validated = $request->validate([
            'aspirante_nombre'    => 'required|string|max:150',
            'aspirante_documento' => 'nullable|string|max:30',
            'fecha_examen'        => 'nullable|date',
            'acudiente'           => 'nullable|string|max:150',
            'telefono'            => 'nullable|string|max:40',
            'observaciones'       => 'nullable|string|max:1000',
            'respuestas'          => 'array',
        ]);

        // Normaliza el mapa de respuestas: solo números de pregunta válidos y
        // valores permitidos según el tipo de examen.
        $permitidos = $grado['tipo'] === 'opcion_multiple'
            ? ['A', 'B', 'C', 'D']
            : ['L', 'P', 'N'];

        $respuestas = [];
        foreach (($validated['respuestas'] ?? []) as $n => $val) {
            $n   = (int) $n;
            $val = strtoupper(trim((string) $val));
            if ($n >= 1 && $n <= $grado['total'] && in_array($val, $permitidos, true)) {
                $respuestas[$n] = $val;
            }
        }

        $resultados = ExamenesAdmision::calificar($key, $respuestas);

        $evaluacion = AdmisionEvaluacion::create([
            'grado_key'           => $key,
            'grado_nombre'        => $grado['nombre'],
            'tipo'                => $grado['tipo'],
            'aspirante_nombre'    => $validated['aspirante_nombre'],
            'aspirante_documento' => $validated['aspirante_documento'] ?? null,
            'fecha_examen'        => $validated['fecha_examen'] ?? null,
            'acudiente'           => $validated['acudiente'] ?? null,
            'telefono'            => $validated['telefono'] ?? null,
            'observaciones'       => $validated['observaciones'] ?? null,
            'respuestas'          => $respuestas,
            'resultados'          => $resultados,
            'puntaje'             => $grado['tipo'] === 'opcion_multiple'
                                        ? ($resultados['total']['correctas'] ?? 0) : null,
            'total_preguntas'     => $resultados['total']['items'] ?? $grado['total'],
            'porcentaje'          => $resultados['total']['porcentaje'] ?? null,
            'evaluado_por'        => optional($request->user())->USER,
        ]);

        return redirect()->route('admision.show', $evaluacion)
            ->with('success', 'Evaluación registrada correctamente.');
    }

    /** Reporte imprimible (una página) de una evaluación. */
    public function show(AdmisionEvaluacion $evaluacion)
    {
        $grado = ExamenesAdmision::grado($evaluacion->grado_key);

        return view('admision.reporte', compact('evaluacion', 'grado'));
    }

    /** Hoja de respuestas OMR imprimible para un grado (opción múltiple). */
    public function hoja(Request $request)
    {
        $key   = strtoupper((string) $request->input('grado', ''));
        $grado = ExamenesAdmision::grado($key);

        if (!$grado || $grado['tipo'] !== 'opcion_multiple') {
            return redirect()->route('admision.index')
                ->with('error', 'La hoja OMR solo aplica a los grados de opción múltiple (3º a 11º).');
        }

        $omr = OmrLayout::build(array_map(fn ($m) => $m['nombre'], $grado['materias']));

        return view('admision.hoja', compact('grado', 'key', 'omr'));
    }
}
