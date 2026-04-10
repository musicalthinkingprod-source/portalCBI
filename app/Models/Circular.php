<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Circular extends Model
{
    protected $table = 'circulares';

    protected $fillable = [
        'numero', 'fecha', 'asunto', 'dirigido_a', 'emitido_por', 'contenido', 'estado',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    /**
     * Genera el siguiente número de circular para el año dado.
     * Formato: CIR-001-2026
     */
    public static function siguienteNumero(int $año): string
    {
        $ultimo = static::whereYear('created_at', $año)
            ->orderByDesc('id')
            ->value('numero');

        $secuencia = 1;
        if ($ultimo) {
            // Extrae la parte numérica: CIR-005-2026 → 5
            preg_match('/CIR-(\d+)-\d{4}/', $ultimo, $m);
            $secuencia = isset($m[1]) ? (int) $m[1] + 1 : 1;
        }

        return sprintf('CIR-%03d-%d', $secuencia, $año);
    }
}
