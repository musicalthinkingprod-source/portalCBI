<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AdmisionEvaluacion extends Model
{
    protected $table = 'admision_evaluaciones';

    protected $fillable = [
        'grado_key', 'grado_nombre', 'tipo',
        'aspirante_nombre', 'aspirante_documento', 'fecha_examen',
        'acudiente', 'telefono', 'observaciones',
        'respuestas', 'resultados',
        'puntaje', 'total_preguntas', 'porcentaje',
        'evaluado_por',
    ];

    protected $casts = [
        'fecha_examen' => 'date',
        'respuestas'   => 'array',
        'resultados'   => 'array',
        'porcentaje'   => 'float',
    ];

    /** Escala cualitativa por ítem. */
    const NIVELES = ['L' => 'Logrado', 'P' => 'En proceso', 'N' => 'No logrado'];

    /** Entrevista de admisión del mismo aspirante (si ya se aplicó). */
    public function entrevista(): HasOne
    {
        return $this->hasOne(AdmisionEntrevista::class, 'evaluacion_id');
    }
}
