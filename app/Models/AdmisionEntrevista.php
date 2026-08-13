<?php

namespace App\Models;

use App\Support\EntrevistaAdmision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmisionEntrevista extends Model
{
    protected $table = 'admision_entrevistas';

    protected $fillable = [
        'evaluacion_id',
        'fecha_entrevista', 'grado_key', 'grado_nombre',
        'aspirante_nombre', 'documento_tipo', 'aspirante_documento',
        'fecha_nacimiento', 'lugar_nacimiento', 'edad', 'direccion', 'telefono',
        'acudiente', 'acudiente_parentesco', 'acudiente_ocupacion',
        'acudiente_telefono', 'acudiente_correo',
        'institucion_procedencia', 'tiempo_permanencia', 'motivo_retiro',
        'ha_reprobado', 'cambios_colegio', 'nucleo_familiar',
        'responsable_academico', 'red_apoyo', 'familia',
        'respuestas', 'alertas',
        'viabilidad', 'compromisos', 'orientador', 'coordinador',
        'rectoria_decision', 'rectoria_observaciones', 'rectoria_fecha', 'rectoria_por',
        'estado', 'registrado_por',
    ];

    protected $casts = [
        'fecha_entrevista' => 'date',
        'fecha_nacimiento' => 'date',
        'rectoria_fecha'   => 'date',
        'familia'          => 'array',
        'respuestas'       => 'array',
        'alertas'          => 'array',
    ];

    /** Examen de admisión vinculado (puede no existir todavía). */
    public function evaluacion(): BelongsTo
    {
        return $this->belongsTo(AdmisionEvaluacion::class, 'evaluacion_id');
    }

    /** Texto de una respuesta: $e->r('salud', 'medicos'). */
    public function r(string $seccion, string $pregunta): ?string
    {
        return $this->respuestas[$seccion][$pregunta] ?? null;
    }

    /** Semáforo de una sección ('ok' | 'atencion' | 'alerta' | null). */
    public function alerta(string $seccion): ?string
    {
        return $this->alertas[$seccion] ?? null;
    }

    /** Áreas marcadas como alerta o atención, para el balance de rectoría. */
    public function areasCriticas(): array
    {
        $secciones = EntrevistaAdmision::secciones();
        $out = [];
        foreach ((array) $this->alertas as $slug => $nivel) {
            if ($nivel !== 'ok' && isset($secciones[$slug])) {
                $out[] = [
                    'slug'   => $slug,
                    'titulo' => $secciones[$slug]['titulo'],
                    'nivel'  => $nivel,
                ];
            }
        }
        return $out;
    }

    /** Avance de diligenciamiento (respondidas / total / %). */
    public function avance(): array
    {
        return EntrevistaAdmision::avance($this->respuestas);
    }

    /** Etiqueta legible del concepto de viabilidad. */
    public function viabilidadTexto(): string
    {
        return EntrevistaAdmision::VIABILIDAD[(string) $this->viabilidad] ?? '—';
    }

    /** Etiqueta legible de la decisión de rectoría. */
    public function decisionTexto(): string
    {
        return EntrevistaAdmision::DECISION_RECTORIA[(string) $this->rectoria_decision] ?? '—';
    }

    /** Etiqueta legible del semáforo de una sección. */
    public function alertaTexto(string $seccion): string
    {
        return EntrevistaAdmision::SEMAFORO[(string) $this->alerta($seccion)]['texto'] ?? 'Sin valorar';
    }
}
