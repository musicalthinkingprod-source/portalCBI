<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Materias con listado especial y la lógica de qué grupo usar:
     *   - Materia 31 (Proyecto)   : GRUPO = CURSO tal cual (GP1, GP2…)
     *   - Materia 25 (Música)     : GRUPO = CURSO . '-2'  (grado >= 6)
     *   - Materia 26 (Artes)      : GRUPO = CURSO . '-1'  (grado >= 6)
     *   - Resto / grado < 6       : ESTUDIANTES.CURSO normal
     */
    protected function grupoListado(int $codigoMat, string $curso): ?string
    {
        if ($codigoMat === 31) {
            return $curso; // GP1, GP2…
        }
        $grado = (int) preg_replace('/[^0-9]/', '', $curso);
        if ($grado >= 6) {
            if ($codigoMat === 25) return $curso . '-2'; // Música
            if ($codigoMat === 26) return $curso . '-1'; // Artes
        }
        return null; // usar ESTUDIANTES normalmente
    }

    /**
     * Devuelve la colección de estudiantes MATRICULADOS para una materia/curso,
     * usando LISTADOS_ESPECIALES cuando corresponda.
     */
    protected function estudiantesPara(int $codigoMat, string $curso): Collection
    {
        $grupo = $this->grupoListado($codigoMat, $curso);

        if ($grupo !== null) {
            return DB::table('LISTADOS_ESPECIALES as le')
                ->join('ESTUDIANTES as e', 'le.CODIGO_ALUM', '=', 'e.CODIGO')
                ->where('le.GRUPO', $grupo)
                ->where('e.ESTADO', 'MATRICULADO')
                ->select('e.*')
                ->orderBy('e.APELLIDO1')->orderBy('e.APELLIDO2')
                ->orderBy('e.NOMBRE1')->orderBy('e.NOMBRE2')
                ->get();
        }

        return DB::table('ESTUDIANTES')
            ->where('CURSO', $curso)
            ->where('ESTADO', 'MATRICULADO')
            ->orderBy('APELLIDO1')->orderBy('APELLIDO2')
            ->orderBy('NOMBRE1')->orderBy('NOMBRE2')
            ->get();
    }
}
