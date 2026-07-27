<?php

namespace App\Http\Controllers;

use App\Models\Horario;
use Illuminate\Support\Facades\DB;

class ReunionesController extends Controller
{
    /**
     * Programación de reuniones (solo SuperAd).
     * Analizador: se eligen los docentes requeridos y se ven las horas/bloques
     * con menos conflictos (o del todo libres). Todo el cálculo es reactivo en
     * el cliente a partir de la ocupación por slot enviada como JSON.
     */
    public function index()
    {
        // Docentes activos para el selector
        $docentes = DB::table('CODIGOS_DOC')
            ->where('ESTADO', 'ACTIVO')
            ->where('TIPO', 'DOCENTE')
            ->orderBy('NOMBRE_DOC')
            ->get(['CODIGO_EMP', 'NOMBRE_DOC'])
            ->map(fn ($d) => ['codigo' => $d->CODIGO_EMP, 'nombre' => $d->NOMBRE_DOC])
            ->values();

        // Ocupación por slot [dia][hora] = [CODIGO_EMP, ...]
        $ocupacion = Horario::ocupacionPorSlot();

        // Detalle: qué clase tiene cada ocupado [dia][hora][CODIGO_EMP] = ['Materia · Curso', ...]
        $ocupacionDet = Horario::detalleOcupacionPorSlot();

        // Días del ciclo con su próxima fecha (para contexto)
        $fechasCiclo = Horario::fechasPorCiclo();
        $hoy         = today();
        $dias = [];
        foreach (Horario::$dias as $num => $label) {
            $prox = collect($fechasCiclo[$num] ?? [])->first(fn ($f) => $f->gte($hoy));
            $dias[] = [
                'num'   => $num,
                'label' => $label,
                'fecha' => $prox?->locale('es')->isoFormat('ddd D MMM'),
            ];
        }

        // Horas con su rango real
        $horas = [];
        foreach (Horario::$horas as $num => $label) {
            $horas[] = [
                'num'   => $num,
                'label' => $label,
                'rango' => Horario::$horasRangos[$num] ?? '',
            ];
        }

        return view('reuniones.index', [
            'docentes'     => $docentes,
            'ocupacion'    => $ocupacion,
            'ocupacionDet' => $ocupacionDet,
            'dias'         => $dias,
            'horas'        => $horas,
        ]);
    }
}
