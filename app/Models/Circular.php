<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Circular extends Model
{
    protected $table = 'circulares';

    protected $fillable = [
        'numero', 'fecha', 'asunto', 'dirigido_a', 'emitido_por', 'cargo', 'contenido', 'estado', 'link', 'grados',
    ];

    protected $casts = [
        'fecha'  => 'date',
        'grados' => 'array',
    ];

    /**
     * Genera el siguiente número de circular para el año dado.
     * Formato: CIR-001-2026
     */
    public static function siguienteNumero(int $año): string
    {
        $sufijo = substr((string) $año, -2); // 2026 → "26"

        $ultimo = static::whereYear('created_at', $año)
            ->orderByDesc('id')
            ->value('numero');

        $secuencia = 1;
        if ($ultimo) {
            // Extrae la parte numérica: CBI-CE-26005 → 5
            preg_match('/CBI-CE-\d{2}(\d+)/', $ultimo, $m);
            $secuencia = isset($m[1]) ? (int) $m[1] + 1 : 1;
        }

        return sprintf('CBI-CE-%s%03d', $sufijo, $secuencia);
    }
}
