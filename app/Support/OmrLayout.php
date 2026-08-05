<?php

namespace App\Support;

/**
 * Geometría (en milímetros) de la hoja de respuestas OMR. Es la ÚNICA fuente de
 * verdad tanto para la hoja imprimible como para el lector en el navegador:
 * ambos usan exactamente las mismas coordenadas de fiduciales y burbujas, de
 * modo que lo que se imprime coincide con lo que se mide al escanear.
 *
 * Sistema de coordenadas: origen arriba-izquierda, X→derecha, Y→abajo, tamaño
 * carta (215.9 × 279.4 mm). El lector detecta los 4 fiduciales y mapea cada
 * burbuja por interpolación bilineal dentro del rectángulo que forman.
 */
class OmrLayout
{
    const PAGE_W = 215.9;
    const PAGE_H = 279.4;

    const FID_SIZE   = 9.0;   // lado del cuadrado fiducial
    const BUBBLE_R   = 2.5;   // radio de la burbuja

    // Centros de los 4 fiduciales (mm)
    const FID = [
        'tl' => [14.0, 14.0],
        'tr' => [201.9, 14.0],
        'bl' => [14.0, 265.4],
        'br' => [201.9, 265.4],
    ];

    // Rejilla: 5 columnas (materias) × 10 filas (preguntas)
    const COLS      = 5;
    const ROWS      = 10;
    const COL_LEFT  = 17.0;   // x del borde izquierdo de la 1ª columna (centra la rejilla)
    const COL_WIDTH = 35.0;   // ancho de cada columna (≈ ancho del grupo nº+A-B-C-D)
    const BUB_X0    = 11.0;   // offset de la 1ª burbuja (separada del número)
    const BUB_DX    = 6.8;    // separación entre burbujas A-B-C-D (buen aire entre círculos)
    const ROW_Y0    = 76.0;   // y del centro de la 1ª fila (debajo de las instrucciones)
    const ROW_DY    = 15.0;   // separación vertical entre filas
    const HEADER_DY = 8.0;    // cuánto por encima de la 1ª fila va el título de la materia

    const OPCIONES = ['A', 'B', 'C', 'D'];

    /**
     * Construye el layout completo. $materias = nombres de las 5 materias en
     * orden (para los títulos de columna). Devuelve un arreglo apto para JSON.
     */
    public static function build(array $materias = []): array
    {
        $bubbles = [];
        $columnas = [];

        for ($c = 0; $c < self::COLS; $c++) {
            $colLeft = self::COL_LEFT + $c * self::COL_WIDTH;
            $columnas[] = [
                'materia' => $materias[$c] ?? ('Materia ' . ($c + 1)),
                'x'       => $colLeft,
                'ancho'   => self::COL_WIDTH,
                'header_y' => self::ROW_Y0 - self::HEADER_DY,
                'num_x'   => $colLeft + 2.0,
            ];

            for ($r = 0; $r < self::ROWS; $r++) {
                $n = $c * self::ROWS + $r + 1;
                $y = self::ROW_Y0 + $r * self::ROW_DY;
                foreach (self::OPCIONES as $j => $op) {
                    $bubbles[] = [
                        'n'   => $n,
                        'opt' => $op,
                        'x'   => $colLeft + self::BUB_X0 + $j * self::BUB_DX,
                        'y'   => $y,
                    ];
                }
            }
        }

        return [
            'page'    => ['w' => self::PAGE_W, 'h' => self::PAGE_H],
            'fid'     => self::FID,
            'fidSize' => self::FID_SIZE,
            'bubbleR' => self::BUBBLE_R,
            'cols'    => $columnas,
            'rows'    => self::ROWS,
            'opciones' => self::OPCIONES,
            'rowDy'   => self::ROW_DY,
            'bubbles' => $bubbles,
        ];
    }
}
