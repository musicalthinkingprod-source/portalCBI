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

        // Días del ciclo con clases (materias regulares)
        $diasRegulares = DB::table('HORARIOS as h')
            ->join('ASIGNACION_PCM as a', function ($join) use ($codigoDoc) {
                $join->on('a.CODIGO_MAT', '=', 'h.CODIGO_MAT')
                     ->on(DB::raw("SUBSTRING_INDEX(a.CURSO, '-', 1)"), '=', 'h.CURSO')
                     ->where('a.CODIGO_DOC', $codigoDoc);
            })
            ->where('h.CODIGO_MAT', '!=', 31)
            ->distinct()
            ->pluck('h.DIA')
            ->toArray();

        // Días de Artes/Música en bachillerato (CODIGO_MAT 25/26 → HORARIOS usa 70)
        $cursosArtesMusica = DB::table('ASIGNACION_PCM')
            ->whereIn('CODIGO_MAT', [25, 26])
            ->where('CODIGO_DOC', $codigoDoc)
            ->pluck('CURSO')
            ->map(fn($c) => explode('-', $c)[0])
            ->unique()->values()->toArray();

        $diasArtesMusica = !empty($cursosArtesMusica)
            ? DB::table('HORARIOS')->where('CODIGO_MAT', 70)
                ->whereIn('CURSO', $cursosArtesMusica)
                ->distinct()->pluck('DIA')->toArray()
            : [];

        // Días de Proyecto (CODIGO_MAT=31): su asignación usa grupo GP como CURSO
        $tieneProyecto = DB::table('ASIGNACION_PCM')
            ->where('CODIGO_DOC', $codigoDoc)
            ->where('CODIGO_MAT', 31)
            ->exists();

        $diasProyecto = $tieneProyecto
            ? DB::table('HORARIOS')->where('CODIGO_MAT', 31)->distinct()->pluck('DIA')->toArray()
            : [];

        $diasConDatos = [1, 2, 3, 4, 5, 6];

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
        // El join usa LIKE para capturar sub-grupos (ej. 10A-1, 10A-2 → matchean con 10A)
        // Nota: MAT=25 (Artes) y MAT=26 (Música) se almacenan en HORARIOS como MAT=70 (Expresión Artística)
        // Por eso el join acepta también esa equivalencia
        $clasesEnSlot = DB::table('HORARIOS as h')
            ->join('ASIGNACION_PCM as a', function ($join) {
                $join->where(function ($q) {
                         // Coincidencia directa de materia, o Artes/Música → Expresión Artística
                         $q->whereColumn('a.CODIGO_MAT', 'h.CODIGO_MAT')
                           ->orWhereRaw("(a.CODIGO_MAT IN (25,26) AND h.CODIGO_MAT = 70)");
                     })
                     ->where(function ($q) {
                         $q->whereColumn('a.CURSO', 'h.CURSO')
                           ->orWhereRaw("a.CURSO LIKE CONCAT(h.CURSO, '-%')");
                     });
            })
            ->leftJoin('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'h.CODIGO_MAT')
            ->where('h.DIA', $diaCiclo)
            ->whereRaw("h.CURSO NOT LIKE 'DOC%'")   // excluir filas de horario propio del docente
            ->select('h.HORA', 'a.CODIGO_DOC', 'h.CURSO', 'm.NOMBRE_MAT')
            ->get()
            ->groupBy('HORA');

        // Docentes ocupados por grupos GP (Proyecto): join solo por CODIGO_MAT,
        // porque el docente de un grupo GP atiende a estudiantes de varios cursos
        // y está ocupado siempre que esa materia corre en cualquier curso regular
        $gpOcupados = DB::table('HORARIOS as h')
            ->join('ASIGNACION_PCM as a', 'a.CODIGO_MAT', '=', 'h.CODIGO_MAT')
            ->leftJoin('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'h.CODIGO_MAT')
            ->where('h.DIA', $diaCiclo)
            ->whereRaw("a.CURSO LIKE 'GP%'")
            ->whereRaw("h.CURSO NOT LIKE 'DOC%'")
            ->whereRaw("h.CURSO NOT LIKE 'GP%'")
            ->select('h.HORA', 'a.CODIGO_DOC', 'a.CURSO', 'm.NOMBRE_MAT')
            ->distinct()
            ->get()
            ->groupBy('HORA');

        // Docentes ocupados por sus propias filas DOC-prefixed en HORARIOS
        $docOcupadosPropios = DB::table('HORARIOS as h')
            ->leftJoin('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'h.CODIGO_MAT')
            ->where('h.DIA', $diaCiclo)
            ->where('h.CURSO', 'like', 'DOC%')
            ->where('h.CODIGO_MAT', '!=', 0)
            ->select('h.HORA', DB::raw('h.CURSO as CODIGO_DOC'), 'm.NOMBRE_MAT')
            ->get()
            ->groupBy('HORA');

        // Para cada hora: libres y ocupados con detalle
        $porHora = [];
        foreach ($horas as $horaNum => $horaLabel) {
            $clases    = $clasesEnSlot->get($horaNum, collect());
            $gpClases  = $gpOcupados->get($horaNum, collect());
            $propios   = $docOcupadosPropios->get($horaNum, collect());

            $ocupadosCodigos = $clases->pluck('CODIGO_DOC')
                ->merge($gpClases->pluck('CODIGO_DOC'))
                ->merge($propios->pluck('CODIGO_DOC'))
                ->unique()->toArray();

            $ocupados = $clases->groupBy('CODIGO_DOC')->map(fn($rows) => [
                'nombre' => $todosDocentes->firstWhere('CODIGO_DOC', $rows->first()->CODIGO_DOC)?->NOMBRE_DOC ?? $rows->first()->CODIGO_DOC,
                'clases' => $rows->sortBy(fn($r) => str_pad(preg_replace('/[^0-9]/', '', $r->CURSO), 4, '0', STR_PAD_LEFT) . $r->CURSO)
                                 ->map(fn($r) => $r->CURSO . ' – ' . ($r->NOMBRE_MAT ?? '?'))->implode(', '),
            ]);

            // Añadir ocupados por grupos GP
            foreach ($gpClases->groupBy('CODIGO_DOC') as $doc => $rows) {
                if (!$ocupados->has($doc)) {
                    $ocupados->put($doc, [
                        'nombre' => $todosDocentes->firstWhere('CODIGO_DOC', $doc)?->NOMBRE_DOC ?? $doc,
                        'clases' => 'Proyecto (' . $rows->first()->CURSO . ') – ' . ($rows->first()->NOMBRE_MAT ?? '?'),
                    ]);
                }
            }

            // Añadir ocupados por filas DOC-prefixed (Atención a Padres, etc.)
            foreach ($propios as $p) {
                if (!$ocupados->has($p->CODIGO_DOC)) {
                    $ocupados->put($p->CODIGO_DOC, [
                        'nombre' => $todosDocentes->firstWhere('CODIGO_DOC', $p->CODIGO_DOC)?->NOMBRE_DOC ?? $p->CODIGO_DOC,
                        'clases' => $p->NOMBRE_MAT ?? '—',
                    ]);
                }
            }

            $libres = $todosDocentes->filter(
                fn($d) => !in_array($d->CODIGO_DOC, $ocupadosCodigos)
            )->values();

            $ocupadosOrdenados = $ocupados->values()->sortBy(function ($doc) {
                $primerCurso = explode(',', $doc['clases'])[0];
                $numero = preg_replace('/[^0-9]/', '', $primerCurso);
                return str_pad($numero !== '' ? $numero : '0', 4, '0', STR_PAD_LEFT) . $primerCurso;
            })->values();

            $porHora[$horaNum] = ['libres' => $libres, 'ocupados' => $ocupadosOrdenados];
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

            $diasRegulares = DB::table('HORARIOS as h')
                ->join('ASIGNACION_PCM as a', function ($join) use ($docenteActual) {
                    $join->on('a.CODIGO_MAT', '=', 'h.CODIGO_MAT')
                         ->on(DB::raw("SUBSTRING_INDEX(a.CURSO, '-', 1)"), '=', 'h.CURSO')
                         ->where('a.CODIGO_DOC', $docenteActual);
                })
                ->where('h.CODIGO_MAT', '!=', 31)
                ->distinct()
                ->pluck('h.DIA')
                ->toArray();

            $cursosArtesMusica = DB::table('ASIGNACION_PCM')
                ->whereIn('CODIGO_MAT', [25, 26])
                ->where('CODIGO_DOC', $docenteActual)
                ->pluck('CURSO')
                ->map(fn($c) => explode('-', $c)[0])
                ->unique()->values()->toArray();

            $diasArtesMusica = !empty($cursosArtesMusica)
                ? DB::table('HORARIOS')->where('CODIGO_MAT', 70)
                    ->whereIn('CURSO', $cursosArtesMusica)
                    ->distinct()->pluck('DIA')->toArray()
                : [];

            $tieneProyecto = DB::table('ASIGNACION_PCM')
                ->where('CODIGO_DOC', $docenteActual)
                ->where('CODIGO_MAT', 31)
                ->exists();

            $diasProyecto = $tieneProyecto
                ? DB::table('HORARIOS')->where('CODIGO_MAT', 31)->distinct()->pluck('DIA')->toArray()
                : [];

            $diasConDatos = [1, 2, 3, 4, 5, 6];

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

        // 1. Clases regulares + Artes/Música (25/26→70) + subgrupos + VOC*
        DB::table('HORARIOS as h')
            ->join('ASIGNACION_PCM as a', function ($join) {
                $join->where(function ($q) {
                         $q->whereColumn('a.CODIGO_MAT', 'h.CODIGO_MAT')
                           ->orWhereRaw('(a.CODIGO_MAT IN (25,26) AND h.CODIGO_MAT = 70)');
                     })
                     ->where(function ($q) {
                         $q->whereColumn('a.CURSO', 'h.CURSO')
                           ->orWhereRaw("a.CURSO LIKE CONCAT(h.CURSO, '-%')");
                     });
            })
            ->whereRaw("h.CURSO NOT LIKE 'DOC%'")
            ->select('h.DIA', 'h.HORA', 'a.CODIGO_DOC')
            ->distinct()
            ->get()
            ->each(function ($row) use (&$ocupadosPorSlot) {
                $ocupadosPorSlot[$row->DIA][$row->HORA][] = $row->CODIGO_DOC;
            });

        // 2. Slots DOC-prefixed (Atención a Padres, etc.)
        DB::table('HORARIOS')
            ->whereRaw("CURSO LIKE 'DOC%'")
            ->where('CODIGO_MAT', '!=', 0)
            ->select('DIA', 'HORA', DB::raw('CURSO as CODIGO_DOC'))
            ->get()
            ->each(function ($row) use (&$ocupadosPorSlot) {
                $ocupadosPorSlot[$row->DIA][$row->HORA][] = $row->CODIGO_DOC;
            });

        // 3. Proyecto (GP*): ocupado en cualquier slot de CODIGO_MAT=31
        DB::table('HORARIOS as h')
            ->join('ASIGNACION_PCM as a', 'a.CODIGO_MAT', '=', 'h.CODIGO_MAT')
            ->whereRaw("a.CURSO LIKE 'GP%'")
            ->whereRaw("h.CURSO NOT LIKE 'DOC%'")
            ->whereRaw("h.CURSO NOT LIKE 'GP%'")
            ->select('h.DIA', 'h.HORA', 'a.CODIGO_DOC')
            ->distinct()
            ->get()
            ->each(function ($row) use (&$ocupadosPorSlot) {
                $ocupadosPorSlot[$row->DIA][$row->HORA][] = $row->CODIGO_DOC;
            });

        // Deduplicar
        foreach ($ocupadosPorSlot as $dia => $horas) {
            foreach ($horas as $hora => $docs) {
                $ocupadosPorSlot[$dia][$hora] = array_unique($docs);
            }
        }

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
