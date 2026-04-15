<?php

namespace App\Helpers;

/**
 * Ponderación de asignaturas dentro de cada área académica.
 *
 * La tabla PESOS define qué fracción del promedio del área aporta cada
 * asignatura (CODIGO_MAT), según el nivel del curso del estudiante.
 *
 * Reglas:
 *  - peso > 0  → la asignatura contribuye con ese factor al promedio del área
 *  - peso = 0  → la asignatura NO aplica para ese nivel (se excluye del cálculo)
 *  - Código MAT ausente en la tabla → peso = 1.0 (contribución equitativa, sin cambio)
 *
 * Los pesos de las asignaturas que SÍ aplican dentro de cada área+nivel
 * deben sumar 1.0.
 *
 * Niveles reconocidos: 'PE' | '1-4' | '5-6' | '7-8' | '9' | '10-11'
 */
class PonderacionArea
{
    // ─── Tabla de pesos: [codigo_mat][nivel] => peso ─────────────────────────
    // Asignatura              PE      1°-4°   5°-6°   7°-8°   9°      10°-11°
    const PESOS = [
        // ══ ÁREA 1: MATEMÁTICAS ══════════════════════════════════════════════
        1  => ['PE'=>1.00, '1-4'=>0,    '5-6'=>0,    '7-8'=>0,    '9'=>0,    '10-11'=>0   ], // Pre-Matemáticas
        2  => ['PE'=>0,    '1-4'=>1.00, '5-6'=>1.00, '7-8'=>0.50, '9'=>0,    '10-11'=>0   ], // Matemáticas
        3  => ['PE'=>0,    '1-4'=>0,    '5-6'=>0,    '7-8'=>0.50, '9'=>0.50, '10-11'=>0.50], // Álgebra
        4  => ['PE'=>0,    '1-4'=>0,    '5-6'=>0,    '7-8'=>0,    '9'=>0.25, '10-11'=>0.25], // Trigonometría
        5  => ['PE'=>0,    '1-4'=>0,    '5-6'=>0,    '7-8'=>0,    '9'=>0.25, '10-11'=>0.25], // Cálculo

        // ══ ÁREA 2: HUMANIDADES ══════════════════════════════════════════════
        6  => ['PE'=>0,    '1-4'=>0.60, '5-6'=>0,    '7-8'=>0,    '9'=>0,    '10-11'=>0   ], // Lecto Escritura
        7  => ['PE'=>0,    '1-4'=>0.25, '5-6'=>0.50, '7-8'=>0.50, '9'=>0.50, '10-11'=>0.50], // Lengua Castellana
        8  => ['PE'=>0,    '1-4'=>0,    '5-6'=>0,    '7-8'=>0,    '9'=>0,    '10-11'=>0   ], // Análisis Literario
        9  => ['PE'=>0,    '1-4'=>0.15, '5-6'=>0,    '7-8'=>0,    '9'=>0,    '10-11'=>0   ], // Plan Lector
        10 => ['PE'=>0,    '1-4'=>0,    '5-6'=>0.50, '7-8'=>0.50, '9'=>0.25, '10-11'=>0.25], // Inglés
        11 => ['PE'=>0,    '1-4'=>0,    '5-6'=>0,    '7-8'=>0,    '9'=>0,    '10-11'=>0   ], // English Acquisition
        12 => ['PE'=>0,    '1-4'=>0,    '5-6'=>0,    '7-8'=>0,    '9'=>0.25, '10-11'=>0.25], // English Literature
        13 => ['PE'=>0,    '1-4'=>0,    '5-6'=>0,    '7-8'=>0,    '9'=>0,    '10-11'=>0   ], // Francés

        // ══ ÁREA 3: CIENCIAS NATURALES ═══════════════════════════════════════
        14 => ['PE'=>0,    '1-4'=>1.00, '5-6'=>1.00, '7-8'=>0.50, '9'=>0,    '10-11'=>0   ], // Ciencias Naturales
        15 => ['PE'=>0,    '1-4'=>0,    '5-6'=>0,    '7-8'=>0.50, '9'=>0.50, '10-11'=>0.50], // Biología
        16 => ['PE'=>0,    '1-4'=>0,    '5-6'=>0,    '7-8'=>0,    '9'=>0.25, '10-11'=>0.25], // Física
        17 => ['PE'=>0,    '1-4'=>0,    '5-6'=>0,    '7-8'=>0,    '9'=>0.25, '10-11'=>0.25], // Química

        // ══ ÁREA 4: CIENCIAS SOCIALES ════════════════════════════════════════
        //   PE/PJ–4°  : Sociales 60% + Urbanidad 20% + Cátedra de Paz 20%
        //   5°–9°     : Sociales 80% + Cátedra de Paz 20%
        //   10°–11°   : Ciencias Políticas 40% + Filosofía 40% + Cátedra de Paz 20%
        18 => ['PE'=>0.60, '1-4'=>0.60, '5-6'=>0.80, '7-8'=>0.80, '9'=>0.80, '10-11'=>0   ], // Sociales y Democracia
        19 => ['PE'=>0,    '1-4'=>0,    '5-6'=>0,    '7-8'=>0,    '9'=>0,    '10-11'=>0.40], // Ciencias Políticas y Económicas
        20 => ['PE'=>0,    '1-4'=>0,    '5-6'=>0,    '7-8'=>0,    '9'=>0,    '10-11'=>0.40], // Filosofía
        24 => ['PE'=>0.20, '1-4'=>0.20, '5-6'=>0,    '7-8'=>0,    '9'=>0,    '10-11'=>0   ], // Urbanidad y Cívica
        35 => ['PE'=>0.20, '1-4'=>0.20, '5-6'=>0.20, '7-8'=>0.20, '9'=>0.20, '10-11'=>0.20], // Cátedra de Paz

        // ══ ÁREA 5: EDUCACIÓN RELIGIOSA ══════════════════════════════════════
        21 => ['PE'=>0,    '1-4'=>1.00, '5-6'=>1.00, '7-8'=>1.00, '9'=>1.00, '10-11'=>1.00], // Religión

        // ══ ÁREA 6: ÉTICA Y VALORES ══════════════════════════════════════════
        22 => ['PE'=>0,    '1-4'=>1.00, '5-6'=>1.00, '7-8'=>0.50, '9'=>0.50, '10-11'=>0.50], // Valores
        23 => ['PE'=>0,    '1-4'=>0,    '5-6'=>0,    '7-8'=>0.50, '9'=>0.50, '10-11'=>0.50], // Ética Profesional
        31 => ['PE'=>0,    '1-4'=>0,    '5-6'=>0,    '7-8'=>0,    '9'=>0,    '10-11'=>0   ], // Proyecto

        // ══ ÁREA 7: EDUCACIÓN ARTÍSTICA ══════════════════════════════════════
        25 => ['PE'=>0,    '1-4'=>0.50, '5-6'=>0.50, '7-8'=>0.50, '9'=>0.50, '10-11'=>0.50], // Artes
        26 => ['PE'=>0,    '1-4'=>0.50, '5-6'=>0.50, '7-8'=>0.50, '9'=>0.50, '10-11'=>0.50], // Música

        // ══ ÁREA 8: EDUCACIÓN FÍSICA ═════════════════════════════════════════
        27 => ['PE'=>0,    '1-4'=>1.00, '5-6'=>0,    '7-8'=>0,    '9'=>0,    '10-11'=>0   ], // Aprestamiento
        28 => ['PE'=>0,    '1-4'=>0,    '5-6'=>1.00, '7-8'=>1.00, '9'=>1.00, '10-11'=>1.00], // Educación Física

        // ══ ÁREA 9: INFORMÁTICA Y TECNOLOGÍA ═════════════════════════════════
        29 => ['PE'=>0,    '1-4'=>1.00, '5-6'=>1.00, '7-8'=>1.00, '9'=>1.00, '10-11'=>1.00], // Tecnología e Informática

        // ══ ÁREA 10: PROYECTO DE VIDA ════════════════════════════════════════
        30 => ['PE'=>0,    '1-4'=>0,    '5-6'=>0,    '7-8'=>0,    '9'=>1.00, '10-11'=>1.00], // Gestión Empresarial
    ];

    /**
     * Determina el nivel de ponderación según el código de curso del estudiante.
     */
    public static function nivel(?string $curso): string
    {
        if (!$curso) return '1-4';

        $curso = strtoupper(trim($curso));

        // Preescolar
        if (in_array($curso, ['J', 'PJ', 'T', 'PRE', 'TRANS'])) return 'PE';

        // Extraer número inicial (ej: "10A" → 10, "1B" → 1)
        $num = (int) preg_replace('/\D/', '', $curso);

        if ($num <= 0)  return 'PE';
        if ($num <= 4)  return '1-4';
        if ($num <= 6)  return '5-6';
        if ($num <= 8)  return '7-8';
        if ($num <= 9)  return '9';
        return '10-11';
    }

    /**
     * Retorna el peso de una asignatura para un nivel dado.
     *  - Si la asignatura está en la tabla y tiene peso 0 → NO aplica (excluir).
     *  - Si la asignatura NO está en la tabla             → peso 1.0 (equitativo).
     *  - Si el nivel no está en la entrada de la materia  → peso 1.0.
     */
    public static function peso(int $codigoMat, string $nivel): float
    {
        if (!isset(self::PESOS[$codigoMat])) return 1.0;
        return (float) (self::PESOS[$codigoMat][$nivel] ?? 1.0);
    }
}
