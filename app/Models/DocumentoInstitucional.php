<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentoInstitucional extends Model
{
    protected $table = 'documentos_institucionales';

    protected $fillable = [
        'categoria',
        'titulo',
        'descripcion',
        'url',
        'orden',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'orden'  => 'integer',
    ];
}
