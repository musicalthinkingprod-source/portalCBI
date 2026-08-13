<?php

namespace App\Http\Controllers;

use App\Models\AdmisionEntrevista;
use App\Models\AdmisionEvaluacion;
use App\Support\EntrevistaAdmision;
use App\Support\ExamenesAdmision;
use Illuminate\Http\Request;

class AdmisionEntrevistaController extends Controller
{
    /** Listado de entrevistas con filtros. */
    public function index(Request $request)
    {
        $grados = ExamenesAdmision::grados();

        $q = AdmisionEntrevista::query()->with('evaluacion')->latest();

        if ($g = $request->input('grado')) {
            $q->where('grado_key', $g);
        }
        if ($estado = $request->input('estado')) {
            $q->where('estado', $estado);
        }
        if ($buscar = trim((string) $request->input('buscar', ''))) {
            $q->where(function ($w) use ($buscar) {
                $w->where('aspirante_nombre', 'like', "%$buscar%")
                  ->orWhere('aspirante_documento', 'like', "%$buscar%")
                  ->orWhere('acudiente', 'like', "%$buscar%");
            });
        }

        $entrevistas = $q->paginate(20)->withQueryString();

        return view('admision.entrevistas.index', compact('grados', 'entrevistas'));
    }

    /** Formulario nuevo. Admite ?evaluacion=ID para prellenar desde el examen. */
    public function create(Request $request)
    {
        $entrevista = new AdmisionEntrevista([
            'fecha_entrevista' => now()->toDateString(),
            'orientador'       => optional($request->user())->USER,
        ]);

        if ($id = $request->input('evaluacion')) {
            $ev = AdmisionEvaluacion::find($id);
            if ($ev) {
                $entrevista->fill([
                    'evaluacion_id'       => $ev->id,
                    'grado_key'           => $ev->grado_key,
                    'grado_nombre'        => $ev->grado_nombre,
                    'aspirante_nombre'    => $ev->aspirante_nombre,
                    'aspirante_documento' => $ev->aspirante_documento,
                    'acudiente'           => $ev->acudiente,
                    'acudiente_telefono'  => $ev->telefono,
                    'telefono'            => $ev->telefono,
                ]);
            }
        }

        return view('admision.entrevistas.form', $this->datosFormulario($entrevista));
    }

    /** Guarda una entrevista nueva. */
    public function store(Request $request)
    {
        $datos = $this->datosDesde($request);
        $datos['registrado_por'] = optional($request->user())->USER;

        $entrevista = AdmisionEntrevista::create($datos);

        return redirect()->route('admision.entrevistas.show', $entrevista)
            ->with('success', 'Entrevista registrada correctamente.');
    }

    /** Formulario de edición. */
    public function edit(AdmisionEntrevista $entrevista)
    {
        return view('admision.entrevistas.form', $this->datosFormulario($entrevista));
    }

    /** Actualiza una entrevista existente. */
    public function update(Request $request, AdmisionEntrevista $entrevista)
    {
        $entrevista->update($this->datosDesde($request));

        return redirect()->route('admision.entrevistas.show', $entrevista)
            ->with('success', 'Entrevista actualizada.');
    }

    /** Vista de la entrevista completa dentro del portal. */
    public function show(AdmisionEntrevista $entrevista)
    {
        $entrevista->load('evaluacion');

        return view('admision.entrevistas.ver', [
            'entrevista' => $entrevista,
            'secciones'  => EntrevistaAdmision::secciones(),
            'concepto'   => EntrevistaAdmision::concepto(),
        ]);
    }

    /** Versión imprimible del formato completo de entrevista. */
    public function imprimir(AdmisionEntrevista $entrevista)
    {
        $entrevista->load('evaluacion');

        return view('admision.entrevistas.reporte', [
            'entrevista' => $entrevista,
            'secciones'  => EntrevistaAdmision::secciones(),
            'concepto'   => EntrevistaAdmision::concepto(),
        ]);
    }

    /** Balance consolidado para la entrevista de rectoría (examen + entrevista). */
    public function balance(AdmisionEntrevista $entrevista)
    {
        $entrevista->load('evaluacion');

        $grado = $entrevista->evaluacion
            ? ExamenesAdmision::grado($entrevista->evaluacion->grado_key)
            : null;

        return view('admision.entrevistas.balance', [
            'entrevista' => $entrevista,
            'grado'      => $grado,
            'secciones'  => EntrevistaAdmision::secciones(),
        ]);
    }

    /** Registra la decisión final de rectoría sobre el aspirante. */
    public function rectoria(Request $request, AdmisionEntrevista $entrevista)
    {
        $v = $request->validate([
            'rectoria_decision'      => 'required|in:' . implode(',', array_keys(EntrevistaAdmision::DECISION_RECTORIA)),
            'rectoria_observaciones' => 'nullable|string|max:3000',
            'rectoria_fecha'         => 'nullable|date',
        ]);

        $entrevista->update([
            'rectoria_decision'      => $v['rectoria_decision'],
            'rectoria_observaciones' => $v['rectoria_observaciones'] ?? null,
            'rectoria_fecha'         => $v['rectoria_fecha'] ?? now()->toDateString(),
            'rectoria_por'           => optional($request->user())->USER,
        ]);

        return back()->with('success', 'Decisión de rectoría registrada.');
    }

    /** Elimina una entrevista (solo SuperAd). */
    public function destroy(AdmisionEntrevista $entrevista)
    {
        $nombre = $entrevista->aspirante_nombre;
        $entrevista->delete();

        return redirect()->route('admision.entrevistas.index')
            ->with('success', "Entrevista de \"{$nombre}\" eliminada.");
    }

    // ─── Apoyo ───────────────────────────────────────────────────────────────

    /** Datos comunes que necesita el formulario (crear y editar). */
    protected function datosFormulario(AdmisionEntrevista $entrevista): array
    {
        // Exámenes que aún no tienen entrevista, más el ya vinculado a esta.
        $vinculadas = AdmisionEntrevista::whereNotNull('evaluacion_id')
            ->when($entrevista->exists, fn ($q) => $q->where('id', '<>', $entrevista->id))
            ->pluck('evaluacion_id');

        $evaluaciones = AdmisionEvaluacion::whereNotIn('id', $vinculadas)
            ->latest()
            ->limit(200)
            ->get(['id', 'aspirante_nombre', 'aspirante_documento', 'grado_nombre', 'grado_key', 'fecha_examen']);

        return [
            'entrevista'   => $entrevista,
            'grados'       => ExamenesAdmision::grados(),
            'secciones'    => EntrevistaAdmision::secciones(),
            'concepto'     => EntrevistaAdmision::concepto(),
            'evaluaciones' => $evaluaciones,
        ];
    }

    /** Valida y arma el arreglo de atributos a guardar. */
    protected function datosDesde(Request $request): array
    {
        $v = $request->validate([
            'evaluacion_id'           => 'nullable|integer|exists:admision_evaluaciones,id',
            'fecha_entrevista'        => 'nullable|date',
            'grado_key'               => 'nullable|string|max:20',
            'aspirante_nombre'        => 'required|string|max:150',
            'documento_tipo'          => 'nullable|string|max:10',
            'aspirante_documento'     => 'nullable|string|max:30',
            'fecha_nacimiento'        => 'nullable|date',
            'lugar_nacimiento'        => 'nullable|string|max:120',
            'edad'                    => 'nullable|integer|min:0|max:99',
            'direccion'               => 'nullable|string|max:180',
            'telefono'                => 'nullable|string|max:40',
            'acudiente'               => 'nullable|string|max:150',
            'acudiente_parentesco'    => 'nullable|string|max:60',
            'acudiente_ocupacion'     => 'nullable|string|max:120',
            'acudiente_telefono'      => 'nullable|string|max:40',
            'acudiente_correo'        => 'nullable|string|max:120',
            'institucion_procedencia' => 'nullable|string|max:180',
            'tiempo_permanencia'      => 'nullable|string|max:80',
            'motivo_retiro'           => 'nullable|string|max:2000',
            'ha_reprobado'            => 'nullable|string|max:2000',
            'cambios_colegio'         => 'nullable|string|max:2000',
            'nucleo_familiar'         => 'nullable|string|max:2000',
            'responsable_academico'   => 'nullable|string|max:150',
            'red_apoyo'               => 'nullable|string|max:180',
            'viabilidad'              => 'nullable|in:' . implode(',', array_keys(EntrevistaAdmision::VIABILIDAD)),
            'compromisos'             => 'nullable|string|max:3000',
            'orientador'              => 'nullable|string|max:120',
            'coordinador'             => 'nullable|string|max:120',
            'estado'                  => 'nullable|in:borrador,finalizada',
            'familia'                 => 'array',
            'respuestas'              => 'array',
            'alertas'                 => 'array',
        ]);

        $datos = collect($v)->except(['familia', 'respuestas', 'alertas'])->all();

        $datos['grado_nombre'] = $this->nombreGrado($v['grado_key'] ?? null);
        $datos['estado']       = $v['estado'] ?? 'borrador';
        $datos['familia']      = EntrevistaAdmision::normalizarFamilia($request->input('familia', []));
        $datos['respuestas']   = EntrevistaAdmision::normalizarRespuestas($request->input('respuestas', []));
        $datos['alertas']      = EntrevistaAdmision::normalizarAlertas($request->input('alertas', []));

        // Si no escribieron la edad, se calcula desde la fecha de nacimiento.
        if (empty($datos['edad']) && !empty($datos['fecha_nacimiento'])) {
            $datos['edad'] = \Carbon\Carbon::parse($datos['fecha_nacimiento'])->age;
        }

        return $datos;
    }

    /** Nombre legible del grado a partir de su llave. */
    protected function nombreGrado(?string $key): ?string
    {
        if (!$key) {
            return null;
        }
        $grado = ExamenesAdmision::grado(strtoupper($key));

        return $grado['nombre'] ?? null;
    }
}
