<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\FechasController;
use App\Http\Controllers\ExencionCarteraController;

class PadresController extends Controller
{
    /** URL base de los sites de Google donde los docentes publican su material. */
    const SITES_BASE = 'https://sites.google.com/cbi.edu.co/';

    /**
     * Genera la URL del Google Site para una materia y curso dado.
     * Usa solo el grado (ej: "7") si todos los cursos del mismo grado tienen el mismo docente,
     * o el curso completo en minúscula (ej: "7a") si hay docentes distintos por sección.
     */
    public static function urlSite(int $codigoMat, string $curso): string
    {
        $grado = rtrim($curso, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz');
        if (!$grado) $grado = $curso;

        $docentes = DB::table('ASIGNACION_PCM')
            ->where('CODIGO_MAT', $codigoMat)
            ->get(['CURSO', 'CODIGO_EMP'])
            ->filter(fn($r) => rtrim($r->CURSO, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz') === $grado)
            ->pluck('CODIGO_EMP')
            ->unique();

        $identificador = $docentes->count() <= 1 ? $grado : strtolower($curso);

        return self::SITES_BASE . $codigoMat . '-' . $identificador;
    }

    public function portal()
    {
        $estudiante = session('padre_estudiante');

        if (!$estudiante) {
            return view('padres.portal', [
                'diaCicloHoy' => null, 'proxCal' => null,
                'periodo' => null, 'ciclo' => null,
                'horarioHoy' => collect(), 'gridCompleto' => [], 'diasConDatos' => [], 'proximaFecha' => [],
                'saldo' => 0, 'bloqueado' => false, 'modulos' => [],
            ]);
        }

        $anio   = (int) date('Y');
        $codigo = $estudiante->CODIGO;
        $curso  = $estudiante->CURSO;
        $hoy    = today();

        // Día del calendario académico para hoy
        $calHoy      = DB::table('calendario_academico')->where('fecha', $hoy->toDateString())->first();
        $diaCicloHoy = $calHoy?->dia_ciclo;

        // Próximo día académico
        $proxCal = DB::table('calendario_academico')
            ->where('fecha', '>', $hoy->toDateString())
            ->orderBy('fecha')
            ->first();

        // Período y ciclo actuales: cada período tiene 7 ciclos (dia_ciclo=1 marca inicio de cada ciclo)
        $todosInicios = DB::table('calendario_academico')
            ->where('anio', $anio)
            ->where('dia_ciclo', 1)
            ->orderBy('fecha')
            ->distinct()
            ->pluck('fecha')
            ->values();

        $periodo = null;
        $ciclo   = null;
        $globalActual = null;

        foreach ($todosInicios as $idx => $fechaInicio) {
            if ($fechaInicio <= $hoy->toDateString()) {
                $globalActual = $idx;
            }
        }

        if ($globalActual !== null) {
            $periodo = (int) floor($globalActual / 7) + 1;
            $ciclo   = ($globalActual % 7) + 1;
        }

        // Horario de hoy para el curso del estudiante
        $horarioHoy = collect();
        if ($diaCicloHoy && $curso) {
            $horarioHoy = DB::table('HORARIOS as h')
                ->leftJoin('CODIGOSMAT as cm', 'cm.CODIGO_MAT', '=', 'h.CODIGO_MAT')
                ->leftJoin('ASIGNACION_PCM as a', function ($join) use ($curso) {
                    $join->on('a.CODIGO_MAT', '=', 'h.CODIGO_MAT')
                         ->where('a.CURSO', $curso);
                })
                ->leftJoin('CODIGOS_DOC as cd', 'cd.CODIGO_EMP', '=', 'a.CODIGO_EMP')
                ->where('h.CURSO', $curso)
                ->where('h.DIA', $diaCicloHoy)
                ->select('h.HORA', 'cm.NOMBRE_MAT', 'cd.NOMBRE_DOC')
                ->orderBy('h.HORA')
                ->get();
        }

        // Horario completo del curso (todas las horas y días del ciclo)
        $gridCompleto  = [];
        $diasConDatos  = [];
        $proximaFecha  = [];

        if ($curso) {
            $filas = DB::table('HORARIOS as h')
                ->leftJoin('CODIGOSMAT as cm', 'cm.CODIGO_MAT', '=', 'h.CODIGO_MAT')
                ->leftJoin('ASIGNACION_PCM as a', function ($join) use ($curso) {
                    $join->on('a.CODIGO_MAT', '=', 'h.CODIGO_MAT')
                         ->where('a.CURSO', $curso);
                })
                ->leftJoin('CODIGOS_DOC as cd', 'cd.CODIGO_EMP', '=', 'a.CODIGO_EMP')
                ->where('h.CURSO', $curso)
                ->select('h.DIA', 'h.HORA', 'cm.NOMBRE_MAT', 'cd.NOMBRE_DOC')
                ->orderBy('h.HORA')->orderBy('h.DIA')
                ->get();

            foreach ($filas as $f) {
                $gridCompleto[$f->HORA][$f->DIA] = ['materia' => $f->NOMBRE_MAT ?? '—', 'docente' => $f->NOMBRE_DOC];
                if (!in_array($f->DIA, $diasConDatos)) $diasConDatos[] = $f->DIA;
            }
            sort($diasConDatos);

            // Próxima fecha de cada día del ciclo (desde hoy inclusive)
            $fechasCiclo = \App\Models\Horario::fechasPorCiclo($anio);
            foreach ($fechasCiclo as $diaCiclo => $fechas) {
                $prox = collect($fechas)->first(fn($f) => $f->gte($hoy));
                $proximaFecha[$diaCiclo] = $prox;
            }
        }

        // Estado financiero
        $facturado = DB::table('facturacion')->where('codigo_alumno', $codigo)->sum('valor');
        $pagado    = DB::table('registro_pagos')->where('codigo_alumno', $codigo)->sum('valor');
        $saldo     = $facturado - $pagado;
        $exento    = ExencionCarteraController::tieneExencion($codigo);
        $bloqueado = !$exento && $saldo > 100000;

        // Módulos con estado activo/inactivo
        $now = now();
        $abierto = fn(string $prefix) => DB::table('FECHAS')
            ->where('CODIGO_FECHA', 'like', $prefix.'%')
            ->where('INICIO', '<=', $now)
            ->where('FIN', '>=', $now)
            ->exists();

        $modulos = [
            ['label' => 'Consultar promedios',   'icon' => '📋', 'route' => 'padres.notas',        'activo' => !$bloqueado && $abierto('N'), 'requiere_pago' => true],
            ['label' => 'Boletines',             'icon' => '📝', 'route' => 'padres.boletines',     'activo' => !$bloqueado && $abierto('B'), 'requiere_pago' => true],
            ['label' => 'Salvavidas',            'icon' => '🏊', 'route' => 'padres.salvavidas',    'activo' => $abierto('S'),               'requiere_pago' => false],
            ['label' => 'Derroteros',            'icon' => '📌', 'route' => 'padres.derroteros',    'activo' => $abierto('D'),               'requiere_pago' => false],
            ['label' => 'English Acquisition',   'icon' => '🇬🇧', 'route' => 'padres.english_acq',  'activo' => true,                        'requiere_pago' => false],
            ['label' => 'Asistencia',            'icon' => '📅', 'route' => 'padres.asistencia',    'activo' => true,                        'requiere_pago' => false],
            ['label' => 'Estado de cuenta',      'icon' => '📊', 'route' => 'padres.estado_cuenta', 'activo' => true,                        'requiere_pago' => false],
            ['label' => 'Atención a padres',      'icon' => '🗓', 'route' => 'padres.atencion_docentes', 'activo' => true,                    'requiere_pago' => false],
            ['label' => 'Calendario académico',  'icon' => '📆', 'route' => 'padres.calendario',    'activo' => true,                        'requiere_pago' => false],
        ];

        return view('padres.portal', compact(
            'estudiante', 'diaCicloHoy', 'proxCal', 'periodo', 'ciclo',
            'horarioHoy', 'gridCompleto', 'diasConDatos', 'proximaFecha',
            'saldo', 'bloqueado', 'modulos'
        ));
    }

    private function verificarAcceso(string $tipoCodigo): ?string
    {
        $estudiante = session('padre_estudiante');
        if (!$estudiante) return 'sin_sesion';

        $codigo    = $estudiante->CODIGO;
        $exento    = ExencionCarteraController::tieneExencion($codigo);
        if (!$exento) {
            $facturado = DB::table('facturacion')->where('codigo_alumno', $codigo)->sum('valor');
            $pagado    = DB::table('registro_pagos')->where('codigo_alumno', $codigo)->sum('valor');
            if (($facturado - $pagado) > 100000) return 'deuda';
        }

        $abierto = collect([1,2,3,4])->contains(fn($p) => FechasController::estaActivo($tipoCodigo.$p));
        if (!$abierto) return 'fechas';

        return null;
    }

    public function notas()
    {
        $bloqueo = $this->verificarAcceso('N');
        if ($bloqueo === 'sin_sesion') return redirect()->route('padres.portal');
        if ($bloqueo === 'deuda')      return redirect()->route('padres.portal')->with('aviso', 'No puedes consultar las notas mientras tengas un saldo pendiente.');
        if ($bloqueo === 'fechas')     return redirect()->route('padres.portal')->with('aviso', 'La institución aún no ha publicado las notas finales.');

        $estudiante = session('padre_estudiante');
        $anio       = (int) date('Y');
        $codigo     = $estudiante->CODIGO;

        // Solo mostrar períodos cuyo boletín ya fue publicado alguna vez (B1, B2... con INICIO <= ahora)
        $periodosVisibles = collect([1,2,3,4])->filter(fn($p) =>
            DB::table('FECHAS')->where('CODIGO_FECHA', 'B'.$p)->where('INICIO', '<=', now())->exists()
        )->values()->toArray();

        $datos = BoletinController::datos($codigo);
        if (empty($datos)) abort(404);

        $origen = 'padres';
        return view('promedios.informe', array_merge($datos, compact('origen', 'periodosVisibles')));
    }

    public function boletines()
    {
        $bloqueo = $this->verificarAcceso('B');
        if ($bloqueo === 'sin_sesion') return redirect()->route('padres.portal');
        if ($bloqueo === 'deuda')      return redirect()->route('padres.portal')->with('aviso', 'No puedes consultar los boletines mientras tengas un saldo pendiente.');
        if ($bloqueo === 'fechas')     return redirect()->route('padres.portal')->with('aviso', 'La institución aún no ha publicado los boletines.');

        // Detectar qué período está activo según la ventana B1-B4
        $now = now();
        $ventanaActiva = DB::table('FECHAS')
            ->where('CODIGO_FECHA', 'like', 'B%')
            ->where('INICIO', '<=', $now)
            ->where('FIN',    '>=', $now)
            ->orderBy('CODIGO_FECHA')
            ->value('CODIGO_FECHA');

        $periodoActivo = $ventanaActiva ? (int) substr($ventanaActiva, 1) : null;

        $estudiante = session('padre_estudiante');
        $datos = \App\Http\Controllers\BoletinController::datos((int) $estudiante->CODIGO, $periodoActivo);
        if (empty($datos)) abort(404);

        $origen = 'padres';
        return view('boletines.ver', array_merge($datos, compact('origen')));
    }

    public function atencionDocentes()
    {
        $estudiante = session('padre_estudiante');
        if (!$estudiante) return redirect()->route('padres.portal');

        $curso = $estudiante->CURSO;
        $anio  = (int) date('Y');
        $hoy   = today();

        // Todos los docentes con slots de atención (CODIGO_MAT=200 en HORARIOS, CURSO=CODIGO_EMP)
        $slots = DB::table('HORARIOS as h')
            ->join('CODIGOS_DOC as d', 'd.CODIGO_EMP', '=', 'h.CURSO')
            ->where('h.CODIGO_MAT', 200)
            ->select('h.CURSO as codigo_emp', 'd.NOMBRE_DOC', 'h.DIA', 'h.HORA')
            ->orderBy('d.NOMBRE_DOC')
            ->orderBy('h.DIA')
            ->orderBy('h.HORA')
            ->get();

        // Materias que cada docente le dicta al curso del estudiante
        $materiasDocente = DB::table('ASIGNACION_PCM as a')
            ->join('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'a.CODIGO_MAT')
            ->where('a.CURSO', $curso)
            ->where('a.CODIGO_MAT', '!=', 200)
            ->get(['a.CODIGO_EMP', 'm.NOMBRE_MAT'])
            ->groupBy('CODIGO_EMP')
            ->map(fn($items) => $items->pluck('NOMBRE_MAT'));

        // Próxima fecha de cada día del ciclo
        $fechasCiclo = \App\Models\Horario::fechasPorCiclo($anio);
        $proximaFecha = [];
        foreach ($fechasCiclo as $diaCiclo => $fechas) {
            $prox = collect($fechas)->first(fn($f) => $f->gte($hoy));
            $proximaFecha[$diaCiclo] = $prox;
        }

        // Agrupar slots por docente, separando los del curso del estudiante
        $docentes = $slots->groupBy('codigo_emp')->map(function ($items, $codigoDoc) use ($materiasDocente) {
            return [
                'codigo_emp'  => $codigoDoc,
                'nombre'      => $items->first()->NOMBRE_DOC,
                'materias'    => $materiasDocente->get($codigoDoc, collect()),
                'es_propio'   => $materiasDocente->has($codigoDoc),
                'slots'       => $items->map(fn($s) => ['dia' => $s->DIA, 'hora' => $s->HORA]),
            ];
        })->sortByDesc('es_propio')->values();

        $horaInicio = [1=>'7:00',2=>'7:45',3=>'8:50',4=>'9:35',5=>'10:20',6=>'11:05',7=>'12:10',8=>'12:55'];
        $horaFin    = [1=>'7:45',2=>'8:30',3=>'9:35',4=>'10:20',5=>'11:05',6=>'11:50',7=>'12:55',8=>'13:40'];

        // Directivos y personal administrativo (atención por cita previa o horario fijo)
        $directivos = [
            ['seccion' => 'Rectoría', 'nombre' => 'Luz Angela Vega Buenahora',
             'cargo' => 'Rectora', 'horario' => 'Cita previa · Solicitud por correo',
             'correo' => 'administration@cbi.edu.co'],
            ['seccion' => 'Coordinación', 'nombre' => 'Willy Eduardo Rengifo Trujillo',
             'cargo' => 'Coordinador Académico', 'horario' => 'Cita previa · Solicitud por correo',
             'correo' => 'academic_coordination@cbi.edu.co'],
            ['seccion' => 'Coordinación', 'nombre' => 'Martha Lucia Babativa Valero',
             'cargo' => 'Coordinadora de Convivencia', 'horario' => 'Cita previa · Solicitud por correo',
             'correo' => 'coordination@cbi.edu.co'],
            ['seccion' => 'Orientación', 'nombre' => 'Jennifer Andrea Martínez Londoño',
             'cargo' => 'Orientadora', 'horario' => 'Cita previa · Solicitud por correo',
             'correo' => null],
            ['seccion' => 'Orientación', 'nombre' => 'Jimmy Lorenzo Pérez Martínez',
             'cargo' => 'Orientador', 'horario' => 'Cita previa · Solicitud por correo',
             'correo' => 'school_counselor@cbi.edu.co'],
            ['seccion' => 'Tesorería', 'nombre' => 'Yasbleydis Leal Pico',
             'cargo' => 'Tesorera',
             'horario' => "Lunes a Jueves: 7:30 am – 12:00 m · 3:00 pm – 4:30 pm\nViernes: 7:30 am – 12:00 m",
             'correo' => 'tesoreria@cbi.edu.co'],
        ];

        return view('padres.atencion-docentes', compact(
            'estudiante', 'docentes', 'proximaFecha', 'horaInicio', 'horaFin', 'directivos'
        ));
    }

    public function estadoCuenta()
    {
        $estudiante = session('padre_estudiante');
        $codigo     = $estudiante->CODIGO;

        $facturacion  = DB::table('facturacion')->where('codigo_alumno', $codigo)->orderBy('fecha')->get();
        $pagos        = DB::table('registro_pagos')->where('codigo_alumno', $codigo)->orderBy('fecha')->get();
        $totalFactura = $facturacion->sum('valor');
        $totalPagado  = $pagos->sum('valor');
        $saldo        = $totalFactura - $totalPagado;

        return view('padres.estado_cuenta', compact('estudiante', 'facturacion', 'pagos', 'totalFactura', 'totalPagado', 'saldo'));
    }
}
