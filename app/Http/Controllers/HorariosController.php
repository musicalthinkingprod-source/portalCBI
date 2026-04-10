<?php

namespace App\Http\Controllers;

use App\Models\Horario;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HorariosController extends Controller
{
    public function index()
    {
        $cursos   = Horario::cursos();
        $docentes = Horario::docentes();

        return view('horarios.index', compact('cursos', 'docentes'));
    }

    public function porCurso(Request $request)
    {
        $cursos      = Horario::cursos();
        $cursoActual = $request->input('curso', $cursos[0] ?? null);

        $grid     = $cursoActual ? Horario::gridPorCurso($cursoActual) : [];
        $dias     = Horario::$dias;
        $horas    = Horario::$horas;

        // Días del ciclo que tienen datos para este curso
        $diasConDatos = $cursoActual
            ? DB::table('HORARIOS')->where('CURSO', $cursoActual)->distinct()->pluck('DIA')->sort()->values()->toArray()
            : [];

        // Próxima fecha de cada día del ciclo (para mostrar en la cabecera)
        $proximaFecha = [];
        $fechasCiclo  = Horario::fechasPorCiclo();
        $hoy          = today();
        foreach ($fechasCiclo as $diaCiclo => $fechas) {
            $proxima = collect($fechas)->first(fn(Carbon $f) => $f->gte($hoy));
            $proximaFecha[$diaCiclo] = $proxima;
        }

        $diaCicloHoy = Horario::diaCicloHoy();

        return view('horarios.por_curso', compact(
            'cursos', 'cursoActual', 'grid', 'dias', 'horas',
            'diasConDatos', 'proximaFecha', 'diaCicloHoy'
        ));
    }

    public function miHorario()
    {
        $codigoDoc = auth()->user()->PROFILE; // Para docentes, PROFILE === CODIGO_DOC

        // Nombre del docente
        $doc = DB::table('CODIGOS_DOC')->where('CODIGO_DOC', $codigoDoc)->first();
        $nombreDocente = $doc?->NOMBRE_DOC ?? $codigoDoc;

        // Grid de horario propio
        $grid  = Horario::gridPorDocente($codigoDoc);
        $dias  = Horario::$dias;
        $horas = Horario::$horas;

        // Días del ciclo con clases
        $diasConDatos = DB::table('HORARIOS as h')
            ->join('ASIGNACION_PCM as a', function ($join) use ($codigoDoc) {
                $join->on('a.CODIGO_MAT', '=', 'h.CODIGO_MAT')
                     ->on('a.CURSO', '=', 'h.CURSO')
                     ->where('a.CODIGO_DOC', $codigoDoc);
            })
            ->distinct()
            ->pluck('h.DIA')
            ->sort()
            ->values()
            ->toArray();

        // Próxima fecha de cada día del ciclo (encabezado de columna)
        $proximaFecha = [];
        $fechasCiclo  = Horario::fechasPorCiclo();
        $hoy          = today();
        foreach ($fechasCiclo as $diaCiclo => $fechas) {
            $proxima = collect($fechas)->first(fn(Carbon $f) => $f->gte($hoy));
            $proximaFecha[$diaCiclo] = $proxima;
        }

        $diaCicloHoy = Horario::diaCicloHoy();

        // Reemplazos donde este docente cubre a otro (próximos 30 días)
        $reemplazosACubrir = DB::table('reemplazos_asignados as r')
            ->where('r.codigo_doc_reemplazo', $codigoDoc)
            ->where('r.fecha', '>=', today()->toDateString())
            ->where('r.fecha', '<=', today()->addDays(30)->toDateString())
            ->leftJoin('CODIGOS_DOC as cd', 'cd.CODIGO_DOC', '=', 'r.codigo_doc_ausente')
            ->leftJoin('calendario_academico as ca', 'ca.fecha', '=', 'r.fecha')
            ->leftJoin('HORARIOS as h', function ($join) {
                $join->on('h.CURSO', '=', 'r.curso')
                     ->on('h.HORA', '=', 'r.hora')
                     ->on('h.DIA', '=', 'ca.dia_ciclo');
            })
            ->leftJoin('CODIGOSMAT as cm', 'cm.CODIGO_MAT', '=', 'h.CODIGO_MAT')
            ->select(
                'r.fecha', 'r.hora', 'r.curso',
                'cd.NOMBRE_DOC as docente_ausente',
                'cm.NOMBRE_MAT as materia',
                'ca.dia_ciclo'
            )
            ->orderBy('r.fecha')
            ->orderBy('r.hora')
            ->get();

        // Ausencias propias con reemplazo asignado (próximos 30 días)
        $misAusencias = DB::table('reemplazos_asignados as r')
            ->where('r.codigo_doc_ausente', $codigoDoc)
            ->where('r.fecha', '>=', today()->toDateString())
            ->where('r.fecha', '<=', today()->addDays(30)->toDateString())
            ->leftJoin('CODIGOS_DOC as cd', 'cd.CODIGO_DOC', '=', 'r.codigo_doc_reemplazo')
            ->leftJoin('calendario_academico as ca', 'ca.fecha', '=', 'r.fecha')
            ->leftJoin('HORARIOS as h', function ($join) {
                $join->on('h.CURSO', '=', 'r.curso')
                     ->on('h.HORA', '=', 'r.hora')
                     ->on('h.DIA', '=', 'ca.dia_ciclo');
            })
            ->leftJoin('CODIGOSMAT as cm', 'cm.CODIGO_MAT', '=', 'h.CODIGO_MAT')
            ->select(
                'r.fecha', 'r.hora', 'r.curso',
                'cd.NOMBRE_DOC as docente_reemplazo',
                'cm.NOMBRE_MAT as materia',
                'ca.dia_ciclo'
            )
            ->orderBy('r.fecha')
            ->orderBy('r.hora')
            ->get();

        return view('horarios.mi_horario', compact(
            'nombreDocente', 'grid', 'dias', 'horas',
            'diasConDatos', 'proximaFecha', 'diaCicloHoy',
            'reemplazosACubrir', 'misAusencias'
        ));
    }

    public function disponibilidad(Request $request)
    {
        $dias      = Horario::$dias;
        $horas     = Horario::$horas;
        $diaCiclo  = (int) $request->input('dia', Horario::diaCicloHoy() ?? 1);

        // Todos los docentes activos
        $todosDocentes = DB::table('CODIGOS_DOC')
            ->where('ESTADO', 'ACTIVO')
            ->where('TIPO', 'DOCENTE')
            ->orderBy('NOMBRE_DOC')
            ->get(['CODIGO_DOC', 'NOMBRE_DOC']);

        // Qué dicta cada docente por hora en este día del ciclo
        $clasesEnSlot = DB::table('HORARIOS as h')
            ->join('ASIGNACION_PCM as a', function ($join) {
                $join->on('a.CODIGO_MAT', '=', 'h.CODIGO_MAT')
                     ->on('a.CURSO', '=', 'h.CURSO');
            })
            ->leftJoin('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'h.CODIGO_MAT')
            ->where('h.DIA', $diaCiclo)
            ->select('h.HORA', 'a.CODIGO_DOC', 'h.CURSO', 'm.NOMBRE_MAT')
            ->get()
            ->groupBy('HORA');

        // Para cada hora: libres y ocupados con detalle
        $porHora = [];
        foreach ($horas as $horaNum => $horaLabel) {
            $clases = $clasesEnSlot->get($horaNum, collect());
            $ocupadosCodigos = $clases->pluck('CODIGO_DOC')->unique()->toArray();

            $ocupados = $clases->groupBy('CODIGO_DOC')->map(fn($rows) => [
                'nombre' => $todosDocentes->firstWhere('CODIGO_DOC', $rows->first()->CODIGO_DOC)?->NOMBRE_DOC ?? $rows->first()->CODIGO_DOC,
                'clases' => $rows->map(fn($r) => $r->CURSO . ' – ' . ($r->NOMBRE_MAT ?? '?'))->implode(', '),
            ])->values();

            $libres = $todosDocentes->filter(
                fn($d) => !in_array($d->CODIGO_DOC, $ocupadosCodigos)
            )->values();

            $porHora[$horaNum] = compact('libres', 'ocupados');
        }

        // Próxima fecha de este día del ciclo
        $fechasCiclo  = Horario::fechasPorCiclo();
        $hoy          = today();
        $proximaFecha = collect($fechasCiclo[$diaCiclo] ?? [])->first(fn(Carbon $f) => $f->gte($hoy));

        return view('horarios.disponibilidad', compact(
            'dias', 'horas', 'diaCiclo', 'porHora', 'proximaFecha', 'todosDocentes'
        ));
    }

    public function porDocente(Request $request)
    {
        $docentes      = Horario::docentes();
        $docenteActual = $request->input('docente');

        $grid          = $docenteActual ? Horario::gridPorDocente($docenteActual) : [];
        $dias          = Horario::$dias;
        $horas         = Horario::$horas;
        $nombreDocente = null;

        // Todos los docentes activos para el selector de reemplazo
        $docentesActivos = DB::table('CODIGOS_DOC')
            ->where('ESTADO', 'ACTIVO')
            ->where('TIPO', 'DOCENTE')
            ->orderBy('NOMBRE_DOC')
            ->get(['CODIGO_DOC', 'NOMBRE_DOC']);

        // Próxima fecha de cada día del ciclo
        $proximaFecha = [];
        $fechasCiclo  = Horario::fechasPorCiclo();
        $hoy          = today();
        foreach ($fechasCiclo as $diaCiclo => $fechas) {
            $proxima = collect($fechas)->first(fn(Carbon $f) => $f->gte($hoy));
            $proximaFecha[$diaCiclo] = $proxima;
        }

        $diasConDatos     = [];
        $reemplazosGrid   = []; // [dia_ciclo][hora][curso] = {id, nombre_reemplazo}

        if ($docenteActual) {
            $doc = DB::table('CODIGOS_DOC')->where('CODIGO_DOC', $docenteActual)->first();
            $nombreDocente = $doc?->NOMBRE_DOC;

            $diasConDatos = DB::table('HORARIOS as h')
                ->join('ASIGNACION_PCM as a', function ($join) use ($docenteActual) {
                    $join->on('a.CODIGO_MAT', '=', 'h.CODIGO_MAT')
                         ->on('a.CURSO', '=', 'h.CURSO')
                         ->where('a.CODIGO_DOC', $docenteActual);
                })
                ->distinct()
                ->pluck('h.DIA')
                ->sort()
                ->values()
                ->toArray();

            // Cargar reemplazos próximos (60 días) mapeados a día del ciclo
            $reemplazos = DB::table('reemplazos_asignados as r')
                ->join('calendario_academico as ca', 'ca.fecha', '=', 'r.fecha')
                ->leftJoin('CODIGOS_DOC as cd', 'cd.CODIGO_DOC', '=', 'r.codigo_doc_reemplazo')
                ->where('r.codigo_doc_ausente', $docenteActual)
                ->where('r.fecha', '>=', $hoy->toDateString())
                ->where('r.fecha', '<=', $hoy->copy()->addDays(60)->toDateString())
                ->select('r.id', 'r.hora', 'r.curso', 'r.fecha', 'ca.dia_ciclo', 'cd.NOMBRE_DOC as nombre_reemplazo', 'r.codigo_doc_reemplazo')
                ->orderBy('r.fecha')
                ->get();

            foreach ($reemplazos as $rem) {
                $reemplazosGrid[$rem->dia_ciclo][$rem->hora][$rem->curso][] = $rem;
            }
        }

        // ── Datos para el modal de reemplazo ──────────────────────────────────

        // Qué docente ocupa cada slot [dia_ciclo][hora] = [CODIGO_DOC, ...]
        $ocupadosPorSlot = [];
        DB::table('HORARIOS as h')
            ->join('ASIGNACION_PCM as a', function ($join) {
                $join->on('a.CODIGO_MAT', '=', 'h.CODIGO_MAT')
                     ->on('a.CURSO', '=', 'h.CURSO');
            })
            ->select('h.DIA', 'h.HORA', 'a.CODIGO_DOC')
            ->get()
            ->each(function ($row) use (&$ocupadosPorSlot) {
                $ocupadosPorSlot[$row->DIA][$row->HORA][] = $row->CODIGO_DOC;
            });

        // Inicio del ciclo actual para contar reemplazos
        $inicioCiclo = DB::table('calendario_academico')
            ->where('fecha', '<=', $hoy->toDateString())
            ->where('dia_ciclo', 1)
            ->orderByDesc('fecha')
            ->value('fecha') ?? $hoy->toDateString();

        // Cantidad de reemplazos en el ciclo actual por docente
        $reemplazosCiclo = DB::table('reemplazos_asignados')
            ->where('fecha', '>=', $inicioCiclo)
            ->select('codigo_doc_reemplazo', DB::raw('COUNT(*) as total'))
            ->groupBy('codigo_doc_reemplazo')
            ->pluck('total', 'codigo_doc_reemplazo')
            ->toArray();

        // Docentes que dictan en cada curso (para priorizar en el modal)
        $docentesPorCurso = DB::table('ASIGNACION_PCM')
            ->select('CODIGO_DOC', 'CURSO')
            ->get()
            ->groupBy('CURSO')
            ->map(fn($rows) => $rows->pluck('CODIGO_DOC')->unique()->values()->toArray())
            ->toArray();

        return view('horarios.por_docente', compact(
            'docentes', 'docenteActual', 'nombreDocente',
            'grid', 'dias', 'horas', 'diasConDatos',
            'docentesActivos', 'proximaFecha', 'reemplazosGrid',
            'ocupadosPorSlot', 'reemplazosCiclo', 'docentesPorCurso'
        ));
    }
}
