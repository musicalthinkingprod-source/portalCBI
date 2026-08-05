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

        $validated = $this->validarDatos($request);
        $respuestas = $this->normalizarRespuestas($grado, $request->input('respuestas', []));
        $resultados = ExamenesAdmision::calificar($key, $respuestas);

        $evaluacion = AdmisionEvaluacion::create(array_merge(
            $this->datosDesde($validated),
            [
                'grado_key'       => $key,
                'grado_nombre'    => $grado['nombre'],
                'tipo'            => $grado['tipo'],
                'respuestas'      => $respuestas,
                'resultados'      => $resultados,
                'puntaje'         => $grado['tipo'] === 'opcion_multiple'
                                        ? ($resultados['total']['correctas'] ?? 0) : null,
                'total_preguntas' => $resultados['total']['items'] ?? $grado['total'],
                'porcentaje'      => $resultados['total']['porcentaje'] ?? null,
                'evaluado_por'    => optional($request->user())->USER,
            ]
        ));

        return redirect()->route('admision.show', $evaluacion)
            ->with('success', 'Evaluación registrada correctamente.');
    }

    /** Reglas de validación de los datos del aspirante. */
    protected function validarDatos(Request $request): array
    {
        return $request->validate([
            'aspirante_nombre'    => 'required|string|max:150',
            'aspirante_documento' => 'nullable|string|max:30',
            'fecha_examen'        => 'nullable|date',
            'acudiente'           => 'nullable|string|max:150',
            'telefono'            => 'nullable|string|max:40',
            'observaciones'       => 'nullable|string|max:1000',
            'respuestas'          => 'array',
        ]);
    }

    /** Extrae los campos del aspirante desde los datos validados. */
    protected function datosDesde(array $v): array
    {
        return [
            'aspirante_nombre'    => $v['aspirante_nombre'],
            'aspirante_documento' => $v['aspirante_documento'] ?? null,
            'fecha_examen'        => $v['fecha_examen'] ?? null,
            'acudiente'           => $v['acudiente'] ?? null,
            'telefono'            => $v['telefono'] ?? null,
            'observaciones'       => $v['observaciones'] ?? null,
        ];
    }

    /** Deja solo números de pregunta válidos y valores permitidos según el tipo. */
    protected function normalizarRespuestas(array $grado, $input): array
    {
        $permitidos = $grado['tipo'] === 'opcion_multiple' ? ['A', 'B', 'C', 'D'] : ['L', 'P', 'N'];
        $respuestas = [];
        foreach ((array) $input as $n => $val) {
            $n   = (int) $n;
            $val = strtoupper(trim((string) $val));
            if ($n >= 1 && $n <= $grado['total'] && in_array($val, $permitidos, true)) {
                $respuestas[$n] = $val;
            }
        }
        return $respuestas;
    }

    /** Reporte integrado en el sitio (vista principal). */
    public function show(AdmisionEvaluacion $evaluacion)
    {
        $grado = ExamenesAdmision::grado($evaluacion->grado_key);

        return view('admision.ver', compact('evaluacion', 'grado'));
    }

    /** Versión imprimible (una página) del reporte. */
    public function imprimir(AdmisionEvaluacion $evaluacion)
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

    // ─── Solo SuperAd: editar / eliminar evaluaciones ────────────────────────

    /** Formulario de edición de una evaluación existente (corregir respuestas). */
    public function edit(AdmisionEvaluacion $evaluacion)
    {
        $key   = $evaluacion->grado_key;
        $grado = ExamenesAdmision::grado($key);

        $omr = null;
        if ($grado && $grado['tipo'] === 'opcion_multiple') {
            $omr = OmrLayout::build(array_map(fn ($m) => $m['nombre'], $grado['materias']));
        }

        return view('admision.form', compact('grado', 'key', 'omr', 'evaluacion'));
    }

    /** Actualiza una evaluación y recalifica. */
    public function update(Request $request, AdmisionEvaluacion $evaluacion)
    {
        $grado = ExamenesAdmision::grado($evaluacion->grado_key);

        $validated  = $this->validarDatos($request);
        $respuestas = $this->normalizarRespuestas($grado, $request->input('respuestas', []));
        $resultados = ExamenesAdmision::calificar($evaluacion->grado_key, $respuestas);

        $evaluacion->update(array_merge(
            $this->datosDesde($validated),
            [
                'respuestas'      => $respuestas,
                'resultados'      => $resultados,
                'puntaje'         => $grado['tipo'] === 'opcion_multiple'
                                        ? ($resultados['total']['correctas'] ?? 0) : null,
                'total_preguntas' => $resultados['total']['items'] ?? $grado['total'],
                'porcentaje'      => $resultados['total']['porcentaje'] ?? null,
            ]
        ));

        return redirect()->route('admision.show', $evaluacion)
            ->with('success', 'Evaluación actualizada y recalificada.');
    }

    /** Elimina una evaluación. */
    public function destroy(AdmisionEvaluacion $evaluacion)
    {
        $nombre = $evaluacion->aspirante_nombre;
        $evaluacion->delete();

        return redirect()->route('admision.index')
            ->with('success', "Evaluación de \"{$nombre}\" eliminada.");
    }

    // ─── Solo SuperAd: editor de la clave de respuestas ──────────────────────

    /** Lista de grados de opción múltiple para editar su clave. */
    public function claves()
    {
        $grados = array_filter(ExamenesAdmision::grados(), fn ($g) => $g['tipo'] === 'opcion_multiple');

        return view('admision.claves.index', ['grados' => $grados]);
    }

    /** Editor de la clave de un grado. */
    public function claveEdit(string $grado)
    {
        $key   = strtoupper($grado);
        $datos = ExamenesAdmision::grado($key);

        if (!$datos || $datos['tipo'] !== 'opcion_multiple') {
            return redirect()->route('admision.claves')
                ->with('error', 'Solo se puede editar la clave de los grados de opción múltiple.');
        }

        $clave = ExamenesAdmision::claveActual($key);

        return view('admision.claves.edit', ['grado' => $datos, 'key' => $key, 'clave' => $clave]);
    }

    /** Guarda la clave editada y recalifica las evaluaciones de ese grado. */
    public function claveUpdate(Request $request, string $grado)
    {
        $key   = strtoupper($grado);
        $datos = ExamenesAdmision::grado($key);

        if (!$datos || $datos['tipo'] !== 'opcion_multiple') {
            return redirect()->route('admision.claves')->with('error', 'Grado no válido.');
        }

        $entrada = (array) $request->input('clave', []);
        $mapa = [];
        for ($n = 1; $n <= $datos['total']; $n++) {
            $val = strtoupper(trim((string) ($entrada[$n] ?? '')));
            if (in_array($val, ['A', 'B', 'C', 'D'], true)) {
                $mapa[$n] = $val;
            }
        }

        if (count($mapa) !== $datos['total']) {
            return back()->with('error', 'Debes marcar la respuesta correcta de las ' . $datos['total'] . ' preguntas.');
        }

        ExamenesAdmision::guardarClave($key, $mapa, optional($request->user())->USER);

        // Nota: los reportes ya registrados NO se modifican. Cada uno puede
        // recalificarse individualmente desde su reporte con el botón "Recalificar".
        return redirect()->route('admision.claves')
            ->with('success', 'Clave de ' . $datos['nombre'] . ' actualizada. Los reportes existentes no cambian; usa "Recalificar" en cada reporte si quieres aplicarles la nueva clave.');
    }

    /** Recalifica una evaluación con la clave vigente (solo bajo demanda). */
    public function recalificar(AdmisionEvaluacion $evaluacion)
    {
        $resultados = ExamenesAdmision::calificar($evaluacion->grado_key, $evaluacion->respuestas ?? []);

        $evaluacion->update([
            'resultados'  => $resultados,
            'puntaje'     => $evaluacion->tipo === 'opcion_multiple'
                                ? ($resultados['total']['correctas'] ?? 0) : null,
            'porcentaje'  => $resultados['total']['porcentaje'] ?? null,
        ]);

        return redirect()->route('admision.show', $evaluacion)
            ->with('success', 'Reporte recalificado con la clave vigente.');
    }
}
