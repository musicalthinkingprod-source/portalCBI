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
                'num'      => $num,
                'label'    => $label,
                'fecha'    => $prox?->locale('es')->isoFormat('ddd D MMM'),
                'fechaISO' => $prox?->toDateString(),
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

        // ── Datos para asignar reemplazo (mismos criterios que HorariosController@porDocente) ──

        // Inicio del ciclo actual para contar reemplazos
        $inicioCiclo = DB::table('calendario_academico')
            ->where('fecha', '<=', $hoy->toDateString())
            ->where('dia_ciclo', 1)
            ->orderByDesc('fecha')
            ->value('fecha') ?? $hoy->toDateString();

        // Cuántos reemplazos lleva cada docente en el ciclo actual
        $reemplazosCiclo = DB::table('reemplazos_asignados')
            ->where('fecha', '>=', $inicioCiclo)
            ->select('codigo_emp_reemplazo', DB::raw('COUNT(*) as total'))
            ->groupBy('codigo_emp_reemplazo')
            ->pluck('total', 'codigo_emp_reemplazo')
            ->toArray();

        // Docentes que dictan en cada curso (para la ⭐)
        $docentesPorCurso = DB::table('ASIGNACION_PCM')
            ->select('CODIGO_EMP', 'CURSO')
            ->get()
            ->groupBy('CURSO')
            ->map(fn ($rows) => $rows->pluck('CODIGO_EMP')->unique()->values()->toArray())
            ->toArray();

        return view('reuniones.index', [
            'docentes'         => $docentes,
            'ocupacion'        => $ocupacion,
            'ocupacionDet'     => $ocupacionDet,
            'dias'             => $dias,
            'horas'            => $horas,
            'reemplazosCiclo'  => $reemplazosCiclo,
            'docentesPorCurso' => $docentesPorCurso,
        ]);
    }
}
