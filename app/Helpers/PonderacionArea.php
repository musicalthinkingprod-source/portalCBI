<?php

namespace App\Helpers;

/**
 * Ponderación de asignaturas dentro de cada área académica.
 *
 * La tabla PESOS define qué fracción del promedio del área aporta cada
 * asignatura (CODIGO_MAT), según el grado del estudiante.
 *
 * Reglas:
 *  - peso > 0  → la asignatura contribuye con ese factor al promedio del área
 *  - peso = 0  → la asignatura NO aplica para ese grado (se excluye del cálculo)
 *  - Código MAT ausente en la tabla → peso = 1.0 (contribución equitativa, sin cambio)
 *
 * Los pesos de las asignaturas que SÍ aplican dentro de cada área+grado
 * deben sumar 1.0.
 *
 * Grados reconocidos: 'PE' | '1' | '2' | '3' | '4' | '5' | '6' | '7' | '8' | '9' | '10' | '11'
 */
class PonderacionArea
{
    // ─── Tabla de pesos: [codigo_mat][grado] => peso ─────────────────────────
    // Asignatura                 PE    1°    2°    3°    4°    5°    6°    7°    8°    9°    10°   11°
    const PESOS = [
        // ══ ÁREA 1: MATEMÁTICAS ══════════════════════════════════════════════
        1  => ['PE'=>1.00,'1'=>0,   '2'=>0,   '3'=>0,   '4'=>0,   '5'=>0,   '6'=>0,   '7'=>0,   '8'=>0,   '9'=>0,   '10'=>0,   '11'=>0   ], // Pre-Matemáticas
        2  => ['PE'=>0,   '1'=>1.00,'2'=>1.00,'3'=>1.00,'4'=>1.00,'5'=>1.00,'6'=>1.00,'7'=>1.00,'8'=>0,   '9'=>0,   '10'=>0,   '11'=>0   ], // Matemáticas
        3  => ['PE'=>0,   '1'=>0,   '2'=>0,   '3'=>0,   '4'=>0,   '5'=>0,   '6'=>0,   '7'=>0,   '8'=>1.00,'9'=>1.00,'10'=>0,   '11'=>0   ], // Álgebra
        4  => ['PE'=>0,   '1'=>0,   '2'=>0,   '3'=>0,   '4'=>0,   '5'=>0,   '6'=>0,   '7'=>0,   '8'=>0,   '9'=>0,   '10'=>1.00,'11'=>0   ], // Trigonometría
        5  => ['PE'=>0,   '1'=>0,   '2'=>0,   '3'=>0,   '4'=>0,   '5'=>0,   '6'=>0,   '7'=>0,   '8'=>0,   '9'=>0,   '10'=>0,   '11'=>1.00], // Cálculo

        // ══ ÁREA 2: HUMANIDADES, LENGUAS E IDIOMAS ═══════════════════════════
        //   PE      : Lecto Escritura PE 60% + Inglés PE 40%
        //   1°–4°   : L. Castellana 30% + A. Literario 20% + Inglés 30% + E. Literature 20%
        //   5°–6°   : L. Castellana 25% + A. Literario 15% + Inglés 25% + E. Literature 15% + Francés 20%
        //   7°–9°   : L. Castellana 20% + A. Literario 20% + Inglés 25% + E. Literature 15% + Francés 20%
        //   10°–11° : L. Castellana 15% + A. Literario 20% + Inglés 25% + E. Literature 15% + Francés 25%
        //   English Acquisition [11] pertenece al área pero NO pondera (0%)
        6  => ['PE'=>0,   '1'=>0,   '2'=>0,   '3'=>0,   '4'=>0,   '5'=>0,   '6'=>0,   '7'=>0,   '8'=>0,   '9'=>0,   '10'=>0,   '11'=>0   ], // Lecto Escritura
        7  => ['PE'=>0,   '1'=>0.30,'2'=>0.30,'3'=>0.30,'4'=>0.30,'5'=>0.25,'6'=>0.25,'7'=>0.20,'8'=>0.20,'9'=>0.20,'10'=>0.15,'11'=>0.15], // Lengua Castellana
        8  => ['PE'=>0,   '1'=>0.20,'2'=>0.20,'3'=>0.20,'4'=>0.20,'5'=>0.15,'6'=>0.15,'7'=>0.20,'8'=>0.20,'9'=>0.20,'10'=>0.20,'11'=>0.20], // Análisis Literario
        9  => ['PE'=>0,   '1'=>0,   '2'=>0,   '3'=>0,   '4'=>0,   '5'=>0,   '6'=>0,   '7'=>0,   '8'=>0,   '9'=>0,   '10'=>0,   '11'=>0   ], // Plan Lector
        10 => ['PE'=>0,   '1'=>0.30,'2'=>0.30,'3'=>0.30,'4'=>0.30,'5'=>0.25,'6'=>0.25,'7'=>0.25,'8'=>0.25,'9'=>0.25,'10'=>0.25,'11'=>0.25], // Inglés
        11 => ['PE'=>0,   '1'=>0,   '2'=>0,   '3'=>0,   '4'=>0,   '5'=>0,   '6'=>0,   '7'=>0,   '8'=>0,   '9'=>0,   '10'=>0,   '11'=>0   ], // English Acquisition
        12 => ['PE'=>0,   '1'=>0.20,'2'=>0.20,'3'=>0.20,'4'=>0.20,'5'=>0.15,'6'=>0.15,'7'=>0.15,'8'=>0.15,'9'=>0.15,'10'=>0.15,'11'=>0.15], // English Literature
        13 => ['PE'=>0,   '1'=>0,   '2'=>0,   '3'=>0,   '4'=>0,   '5'=>0.20,'6'=>0.20,'7'=>0.20,'8'=>0.20,'9'=>0.20,'10'=>0.25,'11'=>0.25], // Francés
        106=> ['PE'=>0.60,'1'=>0,   '2'=>0,   '3'=>0,   '4'=>0,   '5'=>0,   '6'=>0,   '7'=>0,   '8'=>0,   '9'=>0,   '10'=>0,   '11'=>0   ], // Lecto Escritura PE
        107=> ['PE'=>0,   '1'=>0,   '2'=>0,   '3'=>0,   '4'=>0,   '5'=>0,   '6'=>0,   '7'=>0,   '8'=>0,   '9'=>0,   '10'=>0,   '11'=>0   ], // Lengua Castellana PE
        110=> ['PE'=>0.40,'1'=>0,   '2'=>0,   '3'=>0,   '4'=>0,   '5'=>0,   '6'=>0,   '7'=>0,   '8'=>0,   '9'=>0,   '10'=>0,   '11'=>0   ], // Inglés PE

        // ══ ÁREA 3: CIENCIAS NATURALES ═══════════════════════════════════════
        //   PE      : Ciencias Naturales PE 100%
        //   1°–6°   : Ciencias Naturales 100%
        //   7°–9°   : Biología 33% + Física 33% + Química 33% (equitativo, 3 materias)
        //   10°–11° : Física 50% + Química 50% (equitativo, 2 materias)
        14 => ['PE'=>0,   '1'=>1.00,'2'=>1.00,'3'=>1.00,'4'=>1.00,'5'=>1.00,'6'=>1.00,'7'=>0,     '8'=>0,     '9'=>0,     '10'=>0,   '11'=>0   ], // Ciencias Naturales
        15 => ['PE'=>0,   '1'=>0,   '2'=>0,   '3'=>0,   '4'=>0,   '5'=>0,   '6'=>0,   '7'=>0.3333,'8'=>0.3333,'9'=>0.3333,'10'=>0,   '11'=>0   ], // Biología
        16 => ['PE'=>0,   '1'=>0,   '2'=>0,   '3'=>0,   '4'=>0,   '5'=>0,   '6'=>0,   '7'=>0.3333,'8'=>0.3333,'9'=>0.3333,'10'=>0.50,'11'=>0.50], // Física
        17 => ['PE'=>0,   '1'=>0,   '2'=>0,   '3'=>0,   '4'=>0,   '5'=>0,   '6'=>0,   '7'=>0.3333,'8'=>0.3333,'9'=>0.3333,'10'=>0.50,'11'=>0.50], // Química
        114=> ['PE'=>1.00,'1'=>0,   '2'=>0,   '3'=>0,   '4'=>0,   '5'=>0,   '6'=>0,   '7'=>0,     '8'=>0,     '9'=>0,     '10'=>0,   '11'=>0   ], // Ciencias Naturales PE

        // ══ ÁREA 4: CIENCIAS SOCIALES ════════════════════════════════════════
        //   PE/1°–4°  : Sociales 60% + Urbanidad 20% + Cátedra de Paz 20%
        //   5°–9°     : Sociales 80% + Cátedra de Paz 20%
        //   10°–11°   : Ciencias Políticas 40% + Filosofía 40% + Cátedra de Paz 20%
        18 => ['PE'=>0.60,'1'=>0.60,'2'=>0.60,'3'=>0.60,'4'=>0.60,'5'=>0.80,'6'=>0.80,'7'=>0.80,'8'=>0.80,'9'=>0.80,'10'=>0,   '11'=>0   ], // Sociales y Democracia
        19 => ['PE'=>0,   '1'=>0,   '2'=>0,   '3'=>0,   '4'=>0,   '5'=>0,   '6'=>0,   '7'=>0,   '8'=>0,   '9'=>0,   '10'=>0.40,'11'=>0.40], // Ciencias Políticas y Económicas
        20 => ['PE'=>0,   '1'=>0,   '2'=>0,   '3'=>0,   '4'=>0,   '5'=>0,   '6'=>0,   '7'=>0,   '8'=>0,   '9'=>0,   '10'=>0.40,'11'=>0.40], // Filosofía
        24 => ['PE'=>0.20,'1'=>0.20,'2'=>0.20,'3'=>0.20,'4'=>0.20,'5'=>0,   '6'=>0,   '7'=>0,   '8'=>0,   '9'=>0,   '10'=>0,   '11'=>0   ], // Urbanidad y Cívica
        35 => ['PE'=>0.20,'1'=>0.20,'2'=>0.20,'3'=>0.20,'4'=>0.20,'5'=>0.20,'6'=>0.20,'7'=>0.20,'8'=>0.20,'9'=>0.20,'10'=>0.20,'11'=>0.20], // Cátedra de Paz

        // ══ ÁREA 5: EDUCACIÓN RELIGIOSA ══════════════════════════════════════
        21 => ['PE'=>0,   '1'=>1.00,'2'=>1.00,'3'=>1.00,'4'=>1.00,'5'=>1.00,'6'=>1.00,'7'=>1.00,'8'=>1.00,'9'=>1.00,'10'=>1.00,'11'=>1.00], // Religión

        // ══ ÁREA 6: ÉTICA Y VALORES ══════════════════════════════════════════
        22 => ['PE'=>0,   '1'=>1.00,'2'=>1.00,'3'=>1.00,'4'=>1.00,'5'=>1.00,'6'=>1.00,'7'=>0.50,'8'=>0.50,'9'=>0.50,'10'=>0.50,'11'=>0.50], // Valores
        23 => ['PE'=>0,   '1'=>0,   '2'=>0,   '3'=>0,   '4'=>0,   '5'=>0,   '6'=>0,   '7'=>0.50,'8'=>0.50,'9'=>0.50,'10'=>0.50,'11'=>0.50], // Ética Profesional
        31 => ['PE'=>0,   '1'=>0,   '2'=>0,   '3'=>0,   '4'=>0,   '5'=>0,   '6'=>0,   '7'=>0,   '8'=>0,   '9'=>0,   '10'=>0,   '11'=>0   ], // Proyecto

        // ══ ÁREA 7: EDUCACIÓN ARTÍSTICA ══════════════════════════════════════
        //   1°–6°   : Artes 50% + Música 50% (todos ven ambas)
        //   7°–11°  : Artes 100% O Música 100% (el estudiante escoge una)
        25 => ['PE'=>0,   '1'=>0.50,'2'=>0.50,'3'=>0.50,'4'=>0.50,'5'=>0.50,'6'=>0.50,'7'=>1.00,'8'=>1.00,'9'=>1.00,'10'=>1.00,'11'=>1.00], // Artes
        26 => ['PE'=>0,   '1'=>0.50,'2'=>0.50,'3'=>0.50,'4'=>0.50,'5'=>0.50,'6'=>0.50,'7'=>1.00,'8'=>1.00,'9'=>1.00,'10'=>1.00,'11'=>1.00], // Música

        // ══ ÁREA 8: EDUCACIÓN FÍSICA ═════════════════════════════════════════
        //   1°–11° : Educación Física 100% (Aprestamiento no pondera)
        27 => ['PE'=>0,   '1'=>0,   '2'=>0,   '3'=>0,   '4'=>0,   '5'=>0,   '6'=>0,   '7'=>0,   '8'=>0,   '9'=>0,   '10'=>0,   '11'=>0   ], // Aprestamiento
        28 => ['PE'=>0,   '1'=>1.00,'2'=>1.00,'3'=>1.00,'4'=>1.00,'5'=>1.00,'6'=>1.00,'7'=>1.00,'8'=>1.00,'9'=>1.00,'10'=>1.00,'11'=>1.00], // Educación Física

        // ══ ÁREA 9: INFORMÁTICA Y TECNOLOGÍA ═════════════════════════════════
        29 => ['PE'=>0,   '1'=>1.00,'2'=>1.00,'3'=>1.00,'4'=>1.00,'5'=>1.00,'6'=>1.00,'7'=>1.00,'8'=>1.00,'9'=>1.00,'10'=>1.00,'11'=>1.00], // Tecnología e Informática

        // ══ ÁREA 10: PROYECTO DE VIDA ════════════════════════════════════════
        //   4°–11° : Gestión Empresarial 100%
        30 => ['PE'=>0,   '1'=>0,   '2'=>0,   '3'=>0,   '4'=>1.00,'5'=>1.00,'6'=>1.00,'7'=>1.00,'8'=>1.00,'9'=>1.00,'10'=>1.00,'11'=>1.00], // Gestión Empresarial
    ];

    /**
     * Determina el grado del estudiante a partir del código de curso.
     * Retorna 'PE' para preescolar o '1'..'11' para los demás grados.
     */
    public static function nivel(?string $curso): string
    {
        if (!$curso) return '1';

        $curso = strtoupper(trim($curso));

        // Preescolar
        if (in_array($curso, ['J', 'PJ', 'T', 'PRE', 'TRANS'])) return 'PE';

        // Extraer número inicial (ej: "10A" → 10, "1B" → 1)
        $num = (int) preg_replace('/\D/', '', $curso);

        if ($num <= 0)  return 'PE';
        if ($num > 11)  return '11';
        return (string) $num;
    }

    /**
     * Retorna el peso de una asignatura para un grado dado.
     *  - Si la asignatura está en la tabla y tiene peso 0 → NO aplica (excluir).
     *  - Si la asignatura NO está en la tabla             → peso 1.0 (equitativo).
     *  - Si el grado no está en la entrada de la materia  → peso 1.0.
     */
    public static function peso(int $codigoMat, string $nivel): float
    {
        if (!isset(self::PESOS[$codigoMat])) return 1.0;
        return (float) (self::PESOS[$codigoMat][$nivel] ?? 1.0);
    }
}
