<?php

namespace App\Support;

/**
 * Acceso al catálogo de exámenes de admisión (dataset estático extraído de las
 * pruebas oficiales). Cada grado trae sus materias y, para los de opción
 * múltiple, la clave de respuestas correctas.
 */
class ExamenesAdmision
{
    protected static ?array $data = null;
    protected static ?array $overrides = null;

    /** Orden de presentación de los grados (de menor a mayor). */
    const ORDEN = [
        'PREJARDIN', 'JARDIN', 'TRANSICION', 'PRIMERO', 'SEGUNDO',
        'TERCERO', 'CUARTO', 'QUINTO', 'SEXTO', 'SEPTIMO',
        'OCTAVO', 'NOVENO', 'DECIMO', 'ONCE',
    ];

    /** Carga y cachea el JSON en memoria. */
    public static function todos(): array
    {
        if (self::$data === null) {
            $path = resource_path('data/examenes_admision.json');
            self::$data = json_decode(file_get_contents($path), true) ?: [];
        }
        return self::$data;
    }

    /** Lista de grados en orden, con datos resumidos para el selector. */
    public static function grados(): array
    {
        $todos = self::todos();
        $lista = [];
        foreach (self::ORDEN as $key) {
            if (!isset($todos[$key])) {
                continue;
            }
            $g = $todos[$key];
            $lista[] = [
                'key'      => $key,
                'codigo'   => $g['codigo'],
                'nombre'   => $g['nombre'],
                'tipo'     => $g['tipo'],
                'total'    => $g['total'],
                'materias' => array_map(fn ($m) => $m['nombre'], $g['materias']),
            ];
        }
        return $lista;
    }

    /** Devuelve el examen completo de un grado o null. */
    public static function grado(string $key): ?array
    {
        $grado = self::todos()[$key] ?? null;
        if ($grado === null || ($grado['tipo'] ?? '') !== 'opcion_multiple') {
            return $grado;
        }

        // Aplica el override de clave (si SuperAd lo modificó en BD).
        $ov = self::overrides()[$key] ?? null;
        if ($ov) {
            foreach ($grado['materias'] as &$mat) {
                foreach ($mat['preguntas'] as &$q) {
                    if (isset($ov[$q['n']])) {
                        $q['correcta'] = $ov[$q['n']];
                    }
                }
            }
            unset($mat, $q);
        }
        return $grado;
    }

    /** ¿Es un grado válido? */
    public static function existe(string $key): bool
    {
        return isset(self::todos()[$key]);
    }

    /** Overrides de clave por grado desde BD: [ key => [ n => letra ] ]. */
    public static function overrides(): array
    {
        if (self::$overrides === null) {
            self::$overrides = [];
            try {
                $filas = \Illuminate\Support\Facades\DB::table('admision_claves')->get(['grado_key', 'respuestas']);
                foreach ($filas as $f) {
                    $map = json_decode($f->respuestas, true) ?: [];
                    self::$overrides[$f->grado_key] = array_map('strtoupper', $map);
                }
            } catch (\Throwable $e) {
                // La tabla puede no existir todavía (durante migraciones): sin overrides.
                self::$overrides = [];
            }
        }
        return self::$overrides;
    }

    /** Clave vigente del grado: [ n => letra ] (con overrides ya aplicados). */
    public static function claveActual(string $key): array
    {
        $grado = self::grado($key);
        $clave = [];
        if ($grado && ($grado['tipo'] ?? '') === 'opcion_multiple') {
            foreach ($grado['materias'] as $mat) {
                foreach ($mat['preguntas'] as $q) {
                    $clave[$q['n']] = $q['correcta'] ?? null;
                }
            }
        }
        return $clave;
    }

    /** Guarda (upsert) el override de clave de un grado y limpia el caché. */
    public static function guardarClave(string $key, array $mapa, ?string $usuario = null): void
    {
        \Illuminate\Support\Facades\DB::table('admision_claves')->updateOrInsert(
            ['grado_key' => $key],
            [
                'respuestas'      => json_encode($mapa),
                'actualizado_por' => $usuario,
                'updated_at'      => now(),
                'created_at'      => now(),
            ]
        );
        self::$overrides = null; // fuerza recarga
    }

    /**
     * Califica un set de respuestas contra la clave del grado.
     *
     * @param  array  $respuestas  [ numero(int|string) => letra|nivel ]
     * @return array  { materias: [...], total: {...} }
     */
    public static function calificar(string $key, array $respuestas): array
    {
        $grado = self::grado($key);
        $esMc  = ($grado['tipo'] ?? '') === 'opcion_multiple';

        $materias = [];
        $totCorrectas = 0;
        $totItems     = 0;
        $totContadas  = 0; // respondidas (para %)
        $conteoNiveles = ['L' => 0, 'P' => 0, 'N' => 0];

        foreach ($grado['materias'] as $mat) {
            $correctas = 0;
            $items     = 0;
            $contadas  = 0;
            $niveles   = ['L' => 0, 'P' => 0, 'N' => 0];

            foreach ($mat['preguntas'] as $q) {
                $items++;
                $marca = $respuestas[$q['n']] ?? ($respuestas[(string) $q['n']] ?? null);
                if ($marca === null || $marca === '') {
                    continue;
                }
                $contadas++;

                if ($esMc) {
                    if (strtoupper($marca) === strtoupper($q['correcta'] ?? '')) {
                        $correctas++;
                    }
                } else {
                    $nivel = strtoupper($marca);
                    if (isset($niveles[$nivel])) {
                        $niveles[$nivel]++;
                    }
                }
            }

            $fila = [
                'nombre'    => $mat['nombre'],
                'titulo'    => $mat['titulo'] ?? $mat['nombre'],
                'items'     => $items,
                'contadas'  => $contadas,
            ];

            if ($esMc) {
                $fila['correctas']  = $correctas;
                $fila['porcentaje'] = $items ? round($correctas * 100 / $items, 1) : 0;
                $fila['nivel']      = self::nivelDesempeno($fila['porcentaje']);
                $totCorrectas += $correctas;
            } else {
                $fila['niveles']    = $niveles;
                // Puntaje cualitativo: L=1, P=0.5, N=0 → % de logro
                $logro = $niveles['L'] + $niveles['P'] * 0.5;
                $fila['porcentaje'] = $items ? round($logro * 100 / $items, 1) : 0;
                $fila['nivel']      = self::nivelDesempeno($fila['porcentaje']);
                foreach ($conteoNiveles as $k => $_) {
                    $conteoNiveles[$k] += $niveles[$k];
                }
            }

            $totItems    += $items;
            $totContadas += $contadas;
            $materias[]   = $fila;
        }

        $total = [
            'items'    => $totItems,
            'contadas' => $totContadas,
        ];

        if ($esMc) {
            $total['correctas']  = $totCorrectas;
            $total['porcentaje'] = $totItems ? round($totCorrectas * 100 / $totItems, 1) : 0;
        } else {
            $total['niveles']    = $conteoNiveles;
            $logro = $conteoNiveles['L'] + $conteoNiveles['P'] * 0.5;
            $total['porcentaje'] = $totItems ? round($logro * 100 / $totItems, 1) : 0;
        }
        $total['nivel'] = self::nivelDesempeno($total['porcentaje']);

        return ['materias' => $materias, 'total' => $total];
    }

    /** Clasificación de desempeño según porcentaje (escala institucional). */
    public static function nivelDesempeno(float $pct): string
    {
        return match (true) {
            $pct >= 90 => 'Superior',
            $pct >= 70 => 'Alto',
            $pct >= 50 => 'Básico',
            default    => 'Bajo',
        };
    }
}
