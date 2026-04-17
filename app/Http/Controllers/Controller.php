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
     *   - Materia 31 (Proyecto)        : GRUPO = CURSO tal cual (GP1, GP2…)
     *   - Materia 25 (Artes) / 26 (Música): si el CURSO ya viene en formato
     *     de listado especial (p.ej. 7A-1, 11B-2), se usa tal cual como GRUPO.
     *     En ASIGNACION_PCM, para grados 7+ el CURSO guarda directamente el
     *     nombre del grupo especial: -1 = Artes, -2 = Música.
     *   - Cualquier otro caso          : ESTUDIANTES.CURSO normal.
     */
    protected function grupoListado(int $codigoMat, string $curso): ?string
    {
        if ($codigoMat === 31) {
            return $curso; // GP1, GP2…
        }
        if (in_array($codigoMat, [25, 26], true) && preg_match('/-[12]$/', $curso)) {
            return $curso; // 7A-1, 11B-2, etc.
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
