<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AsistenciaPersonalController extends Controller
{
    // ── Etiquetas y colores ────────────────────────────────────────────────────

    public static array $estadoLabel = [
        'presente'    => 'Presente',
        'retardo'     => 'Retardo',
        'ausente'     => 'Ausente',
        'permiso'     => 'Permiso',
        'incapacidad' => 'Incapacidad',
    ];

    public static array $estadoColor = [
        'presente'    => 'bg-green-100 text-green-800',
        'retardo'     => 'bg-yellow-100 text-yellow-800',
        'ausente'     => 'bg-red-100 text-red-800',
        'permiso'     => 'bg-blue-100 text-blue-800',
        'incapacidad' => 'bg-purple-100 text-purple-800',
    ];

    public static array $tipoPermisoLabel = [
        'permiso'     => 'Permiso',
        'incapacidad' => 'Incapacidad',
        'calamidad'   => 'Calamidad doméstica',
        'comision'    => 'Comisión',
    ];

    // ── Panel principal: estado del día ───────────────────────────────────────

    public function index(Request $request)
    {
        $fecha = $request->input('fecha', today()->toDateString());

        $diaAcademico = DB::table('calendario_academico')
            ->where('fecha', $fecha)
            ->first();

        // Todos los docentes activos con su registro de asistencia para esa fecha
        $docentes = DB::table('CODIGOS_DOC as d')
            ->leftJoin('asistencia_docentes as a', function ($j) use ($fecha) {
                $j->on('a.codigo_emp', '=', 'd.CODIGO_EMP')->where('a.fecha', $fecha);
            })
            ->where('d.ESTADO', 'ACTIVO')
            ->orderBy('d.NOMBRE_DOC')
            ->select(
                'd.CODIGO_EMP', 'd.NOMBRE_DOC', 'd.TIPO',
                'a.estado', 'a.hora_llegada', 'a.observacion'
            )
            ->get();

        $resumen = [
            'presente'    => $docentes->where('estado', 'presente')->count(),
            'retardo'     => $docentes->where('estado', 'retardo')->count(),
            'ausente'     => $docentes->where('estado', 'ausente')->count(),
            'permiso'     => $docentes->where('estado', 'permiso')->count(),
            'incapacidad' => $docentes->where('estado', 'incapacidad')->count(),
            'sin_registro'=> $docentes->whereNull('estado')->count(),
        ];

        return view('asistencia-personal.index', compact(
            'fecha', 'docentes', 'resumen', 'diaAcademico'
        ));
    }

    // ── Registro de asistencia (SecA) ─────────────────────────────────────────

    public function registro(Request $request)
    {
        $fecha = $request->input('fecha', today()->toDateString());

        // Docentes con su estado y si tienen permiso aprobado para esta fecha
        $docentes = DB::table('CODIGOS_DOC as d')
            ->leftJoin('asistencia_docentes as a', function ($j) use ($fecha) {
                $j->on('a.codigo_emp', '=', 'd.CODIGO_EMP')->where('a.fecha', $fecha);
            })
            ->leftJoin('permisos_docentes as p', function ($j) use ($fecha) {
                $j->on('p.codigo_emp', '=', 'd.CODIGO_EMP')
                  ->where('p.estado', 'aprobado')
                  ->where('p.fecha_inicio', '<=', $fecha)
                  ->where('p.fecha_fin',    '>=', $fecha);
            })
            ->where('d.ESTADO', 'ACTIVO')
            ->orderBy('d.NOMBRE_DOC')
            ->select(
                'd.CODIGO_EMP', 'd.NOMBRE_DOC', 'd.TIPO',
                'a.id as asistencia_id', 'a.estado', 'a.hora_llegada', 'a.observacion',
                'p.tipo as tipo_permiso'
            )
            ->get();

        return view('asistencia-personal.registro', compact('fecha', 'docentes'));
    }

    public function guardarRegistro(Request $request)
    {
        $request->validate([
            'fecha'           => 'required|date',
            'asistencias'     => 'required|array',
            'asistencias.*.estado' => 'required|in:presente,retardo,ausente,permiso,incapacidad',
        ]);

        $fecha         = $request->fecha;
        $registradoPor = auth()->user()->USER;

        foreach ($request->asistencias as $codigoDoc => $datos) {
            $estado      = $datos['estado'];
            $horaLlegada = $estado === 'retardo' ? ($datos['hora_llegada'] ?? null) : null;
            $observacion = trim($datos['observacion'] ?? '') ?: null;

            DB::table('asistencia_docentes')->updateOrInsert(
                ['fecha' => $fecha, 'codigo_emp' => $codigoDoc],
                [
                    'estado'         => $estado,
                    'hora_llegada'   => $horaLlegada,
                    'observacion'    => $observacion,
                    'registrado_por' => $registradoPor,
                    'updated_at'     => now(),
                    'created_at'     => now(),
                ]
            );
        }

        return back()->with('success', 'Asistencia guardada correctamente.');
    }

    // ── Permisos (SuperAd) ────────────────────────────────────────────────────

    public function permisos(Request $request)
    {
        $docentes = DB::table('CODIGOS_DOC')
            ->where('ESTADO', 'ACTIVO')
            ->orderBy('NOMBRE_DOC')
            ->get();

        $permisos = DB::table('permisos_docentes as p')
            ->leftJoin('CODIGOS_DOC as d', 'd.CODIGO_EMP', '=', 'p.codigo_emp')
            ->orderByDesc('p.fecha_inicio')
            ->select('p.*', 'd.NOMBRE_DOC')
            ->get();

        return view('asistencia-personal.permisos', compact('docentes', 'permisos'));
    }

    public function crearPermiso(Request $request)
    {
        $request->validate([
            'codigo_emp'  => 'required|string',
            'fecha_inicio'=> 'required|date',
            'fecha_fin'   => 'required|date|after_or_equal:fecha_inicio',
            'tipo'        => 'required|in:permiso,incapacidad,calamidad,comision',
            'motivo'      => 'required|string|max:500',
        ]);

        DB::table('permisos_docentes')->insert([
            'codigo_emp'   => $request->codigo_emp,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin'    => $request->fecha_fin,
            'tipo'         => $request->tipo,
            'motivo'       => trim($request->motivo),
            'estado'       => 'aprobado',
            'aprobado_por' => auth()->user()->USER,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        return back()->with('success_permiso', 'Permiso registrado correctamente.');
    }

    public function eliminarPermiso(int $id)
    {
        DB::table('permisos_docentes')->where('id', $id)->delete();
        return back()->with('success_permiso', 'Permiso eliminado.');
    }

    // ── Reemplazos (SuperAd): docentes ausentes y disponibles por hora ────────

    public function reemplazos(Request $request)
    {
        $fecha = $request->input('fecha', today()->toDateString());

        $diaAcademico = DB::table('calendario_academico')
            ->where('fecha', $fecha)
            ->value('dia_ciclo');

        // Inicio del ciclo actual: fecha del último "día 1" hasta $fecha
        $inicioCiclo = DB::table('calendario_academico')
            ->where('fecha', '<=', $fecha)
            ->where('dia_ciclo', 1)
            ->orderByDesc('fecha')
            ->value('fecha') ?? $fecha;

        // Conteo de reemplazos por docente en este ciclo
        $reemplazosPorDocente = DB::table('reemplazos_asignados')
            ->where('fecha', '>=', $inicioCiclo)
            ->where('fecha', '<=', $fecha)
            ->select('codigo_emp_reemplazo', DB::raw('COUNT(*) as total'))
            ->groupBy('codigo_emp_reemplazo')
            ->pluck('total', 'codigo_emp_reemplazo')
            ->toArray();

        // Docentes ausentes/con permiso hoy
        $ausentes = DB::table('asistencia_docentes as a')
            ->join('CODIGOS_DOC as d', 'd.CODIGO_EMP', '=', 'a.codigo_emp')
            ->where('a.fecha', $fecha)
            ->whereIn('a.estado', ['ausente', 'permiso', 'incapacidad'])
            ->select('a.codigo_emp', 'd.NOMBRE_DOC', 'a.estado', 'a.observacion')
            ->get();

        // Docentes presentes hoy
        $presentes = DB::table('asistencia_docentes as a')
            ->where('a.fecha', $fecha)
            ->whereIn('a.estado', ['presente', 'retardo'])
            ->pluck('a.codigo_emp')
            ->toArray();

        // Info de docentes presentes (nombre + conteo)
        $infoPresentes = DB::table('CODIGOS_DOC')
            ->whereIn('CODIGO_EMP', $presentes)
            ->orderBy('NOMBRE_DOC')
            ->get(['CODIGO_EMP', 'NOMBRE_DOC'])
            ->keyBy('CODIGO_EMP');

        // Reemplazos ya asignados para esta fecha
        $yaAsignados = DB::table('reemplazos_asignados')
            ->where('fecha', $fecha)
            ->get()
            ->groupBy(fn($r) => $r->codigo_emp_ausente . '_' . $r->hora . '_' . $r->curso);

        // Para cada ausente: su horario en el día académico
        $horarioAusentes = [];
        if ($diaAcademico && $ausentes->isNotEmpty()) {
            foreach ($ausentes as $doc) {
                $clases = DB::table('HORARIOS as h')
                    ->join('ASIGNACION_PCM as a', function ($j) use ($doc) {
                        $j->on('a.CODIGO_MAT', '=', 'h.CODIGO_MAT')
                          ->on('a.CURSO', '=', 'h.CURSO')
                          ->where('a.CODIGO_EMP', $doc->codigo_emp);
                    })
                    ->leftJoin('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'h.CODIGO_MAT')
                    ->where('h.DIA', $diaAcademico)
                    ->select('h.HORA', 'h.CURSO', 'h.CODIGO_MAT', 'm.NOMBRE_MAT')
                    ->orderBy('h.HORA')
                    ->get();
                $horarioAusentes[$doc->codigo_emp] = $clases;
            }
        }

        // Disponibles por hora, con prioridad por curso
        // Estructura: [hora][curso] = collect de docentes ordenados (primero los del curso)
        $disponiblesPorHoraCurso = [];
        if ($diaAcademico && !empty($presentes)) {
            // Docentes del curso en ASIGNACION_PCM (todos los cursos)
            $docentesPorCurso = DB::table('ASIGNACION_PCM')
                ->whereIn('CODIGO_EMP', $presentes)
                ->select('CODIGO_EMP', 'CURSO')
                ->get()
                ->groupBy('CURSO')
                ->map(fn($rows) => $rows->pluck('CODIGO_EMP')->toArray());

            for ($hora = 1; $hora <= 8; $hora++) {
                $ocupados = DB::table('HORARIOS as h')
                    ->join('ASIGNACION_PCM as a', function ($j) use ($presentes) {
                        $j->on('a.CODIGO_MAT', '=', 'h.CODIGO_MAT')
                          ->on('a.CURSO', '=', 'h.CURSO')
                          ->whereIn('a.CODIGO_EMP', $presentes);
                    })
                    ->where('h.DIA', $diaAcademico)
                    ->where('h.HORA', $hora)
                    ->pluck('a.CODIGO_EMP')
                    ->toArray();

                $libres = array_values(array_diff($presentes, $ocupados));

                // Para cada curso ausente en esta hora, construir lista priorizada
                foreach ($ausentes as $doc) {
                    $clases = $horarioAusentes[$doc->codigo_emp] ?? collect();
                    foreach ($clases->where('HORA', $hora) as $clase) {
                        $curso = $clase->CURSO;
                        $docsCurso = $docentesPorCurso->get($curso) ?? [];

                        $priorizados = collect($libres)
                            ->map(function ($cod) use ($docsCurso, $reemplazosPorDocente, $infoPresentes) {
                                return [
                                    'codigo'       => $cod,
                                    'nombre'       => $infoPresentes[$cod]->NOMBRE_DOC ?? $cod,
                                    'del_curso'    => in_array($cod, $docsCurso),
                                    'reemplazos'   => $reemplazosPorDocente[$cod] ?? 0,
                                ];
                            })
                            ->sortBy([
                                fn($a, $b) => $b['del_curso'] <=> $a['del_curso'], // del curso primero
                                fn($a, $b) => $a['reemplazos'] <=> $b['reemplazos'], // menos reemplazos primero
                                fn($a, $b) => $a['nombre'] <=> $b['nombre'],
                            ])
                            ->values();

                        $disponiblesPorHoraCurso[$hora][$curso] = $priorizados;
                    }
                }
            }
        }

        $horas = \App\Models\Horario::$horas;

        return view('asistencia-personal.reemplazos', compact(
            'fecha', 'diaAcademico', 'ausentes', 'horarioAusentes',
            'disponiblesPorHoraCurso', 'yaAsignados', 'horas',
            'reemplazosPorDocente', 'inicioCiclo'
        ));
    }

    public function asignarReemplazo(Request $request)
    {
        $request->validate([
            'fecha'               => 'required|date',
            'codigo_emp_ausente'  => 'required|string',
            'codigo_emp_reemplazo'=> 'required|string',
            'hora'                => 'required|integer|min:1|max:8',
            'curso'               => 'required|string',
        ]);

        DB::table('reemplazos_asignados')->updateOrInsert(
            [
                'fecha'              => $request->fecha,
                'codigo_emp_ausente' => $request->codigo_emp_ausente,
                'hora'               => $request->hora,
                'curso'              => $request->curso,
            ],
            [
                'codigo_emp_reemplazo' => $request->codigo_emp_reemplazo,
                'asignado_por'         => auth()->user()->USER,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]
        );

        // Notificar al docente de reemplazo
        $horas    = \App\Models\Horario::$horas;
        $horaLbl  = $horas[$request->hora] ?? $request->hora . 'ª hora';
        $ausente  = DB::table('CODIGOS_DOC')->where('CODIGO_EMP', $request->codigo_emp_ausente)->value('NOMBRE_DOC') ?? $request->codigo_emp_ausente;

        DB::table('notificaciones')->insert([
            'codigo_emp' => $request->codigo_emp_reemplazo,
            'tipo'       => 'reemplazo',
            'titulo'     => 'Se te asignó un reemplazo',
            'mensaje'    => "Debes cubrir la {$horaLbl} del curso {$request->curso} (ausente: {$ausente}) el " . \Carbon\Carbon::parse($request->fecha)->locale('es')->isoFormat('D [de] MMMM'),
            'leida'      => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success_reemplazo', 'Reemplazo asignado correctamente.');
    }

    public function quitarReemplazo(int $id)
    {
        DB::table('reemplazos_asignados')->where('id', $id)->delete();
        return back()->with('success_reemplazo', 'Reemplazo eliminado.');
    }
}
