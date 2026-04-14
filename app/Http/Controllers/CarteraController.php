<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CarteraController extends Controller
{
    public function index()
    {
        // Totales de facturación y recaudo (brutos)
        $totalFacturado = DB::table('facturacion')->sum('valor');
        $totalPagado    = DB::table('registro_pagos')->sum('valor');

        // Saldo por estudiante
        $facturaPorAlumno = DB::table('facturacion')
            ->select('codigo_alumno', DB::raw('SUM(valor) as total_facturado'))
            ->groupBy('codigo_alumno');

        $pagoPorAlumno = DB::table('registro_pagos')
            ->select('codigo_alumno', DB::raw('SUM(valor) as total_pagado'))
            ->groupBy('codigo_alumno');

        $saldos = DB::table(DB::raw("({$facturaPorAlumno->toSql()}) as f"))
            ->mergeBindings($facturaPorAlumno)
            ->leftJoinSub($pagoPorAlumno, 'p', 'f.codigo_alumno', '=', 'p.codigo_alumno')
            ->select(
                'f.codigo_alumno',
                'f.total_facturado',
                DB::raw('COALESCE(p.total_pagado, 0) as total_pagado'),
                DB::raw('f.total_facturado - COALESCE(p.total_pagado, 0) as saldo')
            )
            ->get();

        // Cartera real: solo suma los saldos positivos (deudores)
        // Los pagos adelantados no restan la cartera de otros
        $totalCartera = $saldos->where('saldo', '>', 0)->sum('saldo');

        $porcentajeRecaudo = $totalFacturado > 0
            ? round(($totalPagado / $totalFacturado) * 100, 1)
            : 0;

        $alDia    = $saldos->where('saldo', '<=', 0)->count();
        $debiendo = $saldos->where('saldo', '>', 0)->count();

        // Top 10 mayores deudores
        $topDeudores = $saldos->where('saldo', '>', 0)
            ->sortByDesc('saldo')
            ->take(10)
            ->values();

        // Obtener nombres de estudiantes para top deudores
        $codigos    = $topDeudores->pluck('codigo_alumno')->toArray();
        $estudiantes = DB::table('ESTUDIANTES')
            ->whereIn('CODIGO', $codigos)
            ->get()
            ->keyBy('CODIGO');

        // Facturación por mes
        $porMes = DB::table('facturacion')
            ->select('mes', DB::raw('SUM(valor) as total'))
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        // Pagos por mes
        $pagosPorMes = DB::table('registro_pagos')
            ->select('mes', DB::raw('SUM(valor) as total'))
            ->groupBy('mes')
            ->orderBy('mes')
            ->get()
            ->keyBy('mes');

        return view('cartera.index', compact(
            'totalFacturado', 'totalPagado', 'totalCartera', 'porcentajeRecaudo',
            'alDia', 'debiendo', 'topDeudores', 'estudiantes', 'porMes', 'pagosPorMes'
        ));
    }

    public function estudiante(Request $request, $codigo)
    {
        // Datos del estudiante
        $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();
        $infoPadres = DB::table('INFO_PADRES')->where('CODIGO', $codigo)->first();

        // Resumen financiero
        $totalFacturado = DB::table('facturacion')->where('codigo_alumno', $codigo)->sum('valor');
        $totalPagado    = DB::table('registro_pagos')->where('codigo_alumno', $codigo)->sum('valor');
        $saldo          = $totalFacturado - $totalPagado;

        // Detalle de facturas
        $facturas = DB::table('facturacion')
            ->where('codigo_alumno', $codigo)
            ->orderBy('fecha', 'desc')
            ->get();

        // Detalle de pagos
        $pagos = DB::table('registro_pagos')
            ->where('codigo_alumno', $codigo)
            ->orderBy('fecha', 'desc')
            ->get();

        // Seguimientos
        $seguimientos = DB::table('seguimiento_cartera')
            ->where('codigo_alumno', $codigo)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('cartera.estudiante', compact(
            'estudiante', 'infoPadres', 'codigo',
            'totalFacturado', 'totalPagado', 'saldo',
            'facturas', 'pagos', 'seguimientos'
        ));
    }

    public function storeSeguimiento(Request $request, $codigo)
    {
        $request->validate([
            'tipo' => 'required|string|max:30',
            'nota' => 'required|string|max:2000',
        ]);

        DB::table('seguimiento_cartera')->insert([
            'codigo_alumno' => $codigo,
            'tipo'          => $request->tipo,
            'nota'          => $request->nota,
            'usuario'       => auth()->user()->name ?? null,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return redirect()->route('cartera.estudiante', $codigo)
            ->with('success', 'Registro guardado.');
    }

    public function destroySeguimiento(Request $request, $id)
    {
        $seg = DB::table('seguimiento_cartera')->where('id', $id)->first();
        if ($seg) {
            DB::table('seguimiento_cartera')->where('id', $id)->delete();
            return redirect()->route('cartera.estudiante', $seg->codigo_alumno)
                ->with('success', 'Registro eliminado.');
        }
        return back();
    }

    public function updateSeguimiento(Request $request, $id)
    {
        $request->validate([
            'tipo' => 'required|string|max:30',
            'nota' => 'required|string|max:2000',
        ]);

        $seg = DB::table('seguimiento_cartera')->where('id', $id)->first();
        abort_if(!$seg, 404);

        DB::table('seguimiento_cartera')->where('id', $id)->update([
            'tipo'       => $request->tipo,
            'nota'       => $request->nota,
            'updated_at' => now(),
        ]);

        return redirect()->route('cartera.estudiante', $seg->codigo_alumno)
            ->with('success', 'Registro actualizado.');
    }

    public function informeSeguimiento(Request $request)
    {
        $query = DB::table('seguimiento_cartera as s')
            ->leftJoin('ESTUDIANTES as e', 'e.CODIGO', '=', 's.codigo_alumno')
            ->select(
                's.id', 's.codigo_alumno', 's.tipo', 's.nota', 's.usuario',
                's.created_at', 's.updated_at',
                DB::raw("TRIM(CONCAT(COALESCE(e.APELLIDO1,''),' ',COALESCE(e.APELLIDO2,''),' ',COALESCE(e.NOMBRE1,''),' ',COALESCE(e.NOMBRE2,''))) as nombre"),
                'e.CURSO as curso'
            )
            ->orderBy('s.created_at', 'desc');

        if ($request->filled('codigo_alumno')) {
            $query->where('s.codigo_alumno', $request->codigo_alumno);
        }
        if ($request->filled('tipo')) {
            $query->where('s.tipo', $request->tipo);
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate('s.created_at', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('s.created_at', '<=', $request->fecha_hasta);
        }
        if ($request->filled('usuario')) {
            $query->where('s.usuario', 'like', '%' . $request->usuario . '%');
        }

        $registros = $query->paginate(50)->withQueryString();

        // Totales por tipo (sin filtros para mostrar contexto global)
        $totalesPorTipo = DB::table('seguimiento_cartera')
            ->select('tipo', DB::raw('COUNT(*) as total'))
            ->groupBy('tipo')
            ->orderByDesc('total')
            ->get()
            ->keyBy('tipo');

        $totalGeneral = DB::table('seguimiento_cartera')->count();

        return view('cartera.informe-seguimiento', compact(
            'registros', 'totalesPorTipo', 'totalGeneral'
        ));
    }

    public function deudores(Request $request)
    {
        $tab        = $request->input('tab', 'cartera'); // 'cartera' | 'anticipos'
        $fechaDesde = $request->filled('fecha_desde') ? $request->input('fecha_desde') : null;
        $fechaHasta = $request->filled('fecha_hasta') ? $request->input('fecha_hasta') : null;

        $facturaSub = DB::table('facturacion')
            ->select('codigo_alumno', DB::raw('SUM(valor) as total_facturado'))
            ->when($fechaDesde, fn($q) => $q->whereDate('fecha', '>=', $fechaDesde))
            ->when($fechaHasta, fn($q) => $q->whereDate('fecha', '<=', $fechaHasta))
            ->groupBy('codigo_alumno');

        $pagoSub = DB::table('registro_pagos')
            ->select('codigo_alumno', DB::raw('SUM(valor) as total_pagado'))
            ->when($fechaDesde, fn($q) => $q->whereDate('fecha', '>=', $fechaDesde))
            ->when($fechaHasta, fn($q) => $q->whereDate('fecha', '<=', $fechaHasta))
            ->groupBy('codigo_alumno');

        $query = DB::table(DB::raw("({$facturaSub->toSql()}) as f"))
            ->mergeBindings($facturaSub)
            ->leftJoinSub($pagoSub, 'p', 'f.codigo_alumno', '=', 'p.codigo_alumno')
            ->leftJoin('ESTUDIANTES as e', 'e.CODIGO', '=', 'f.codigo_alumno')
            ->leftJoin('INFO_PADRES as ip', 'ip.CODIGO', '=', 'f.codigo_alumno')
            ->select(
                'f.codigo_alumno',
                'f.total_facturado',
                DB::raw('COALESCE(p.total_pagado, 0) as total_pagado'),
                DB::raw('f.total_facturado - COALESCE(p.total_pagado, 0) as saldo'),
                'e.NOMBRE1', 'e.NOMBRE2', 'e.APELLIDO1', 'e.APELLIDO2', 'e.CURSO',
                'ip.MADRE', 'ip.PADRE', 'ip.ACUD',
                'ip.CEL_MADRE', 'ip.CEL_PADRE', 'ip.CEL_ACUD',
                'ip.TEL_MADRE', 'ip.TEL_PADRE', 'ip.TEL_ACUD'
            );

        if ($tab === 'anticipos') {
            // Saldos a favor: pagaron más de lo facturado
            $query->whereRaw('f.total_facturado - COALESCE(p.total_pagado, 0) < 0')
                  ->orderBy('saldo'); // más negativo primero = mayor anticipo
        } else {
            $query->whereRaw('f.total_facturado - COALESCE(p.total_pagado, 0) > 0')
                  ->orderByDesc('saldo');
        }

        $resultados = $query->paginate(25)->withQueryString();

        return view('cartera.deudores', compact('resultados', 'tab', 'fechaDesde', 'fechaHasta'));
    }

    public function carteraPorCC()
    {
        // Todos los vínculos CC → estudiante
        $vinculos = DB::table('titular_facturacion')->get();

        $codigos = $vinculos->pluck('codigo_alum')->unique();

        $estudiantes = DB::table('ESTUDIANTES')
            ->whereIn('CODIGO', $codigos)
            ->get()
            ->keyBy('CODIGO');

        // Info de padres para encontrar nombre y celular del titular por CC
        $infoPadres = DB::table('INFO_PADRES')
            ->whereIn('CODIGO', $codigos)
            ->get()
            ->keyBy('CODIGO');

        $facturasPor = DB::table('facturacion')
            ->whereIn('codigo_alumno', $codigos)
            ->select('codigo_alumno', DB::raw('SUM(valor) as total'))
            ->groupBy('codigo_alumno')
            ->pluck('total', 'codigo_alumno');

        $pagosPor = DB::table('registro_pagos')
            ->whereIn('codigo_alumno', $codigos)
            ->select('codigo_alumno', DB::raw('SUM(valor) as total'))
            ->groupBy('codigo_alumno')
            ->pluck('total', 'codigo_alumno');

        // Agrupar por CC
        $porCC = $vinculos->groupBy('cc_facturación')->map(function ($filaCC) use ($estudiantes, $facturasPor, $pagosPor, $infoPadres) {
            $detalle = $filaCC->map(function ($v) use ($estudiantes, $facturasPor, $pagosPor) {
                $facturado = (float) ($facturasPor[$v->codigo_alum] ?? 0);
                $pagado    = (float) ($pagosPor[$v->codigo_alum]    ?? 0);
                return (object) [
                    'codigo'     => $v->codigo_alum,
                    'estudiante' => $estudiantes[$v->codigo_alum] ?? null,
                    'facturado'  => $facturado,
                    'pagado'     => $pagado,
                    'saldo'      => $facturado - $pagado,
                ];
            });

            // Buscar nombre y celular del titular buscando la CC en INFO_PADRES de cualquier estudiante del grupo
            $cc              = (int) $filaCC->first()->{'cc_facturación'};
            $nombreTitular   = null;
            $celTitular      = null;

            foreach ($filaCC as $v) {
                $p = $infoPadres[$v->codigo_alum] ?? null;
                if (!$p) continue;

                if ((int) $p->CC_MADRE === $cc) {
                    $nombreTitular = $p->MADRE;
                    $celTitular    = $p->CEL_MADRE;
                    break;
                }
                if ((int) $p->CC_PADRE === $cc) {
                    $nombreTitular = $p->PADRE;
                    $celTitular    = $p->CEL_PADRE;
                    break;
                }
                if ((int) $p->CC_ACUD === $cc) {
                    $nombreTitular = $p->ACUD;
                    $celTitular    = $p->CEL_ACUD;
                    break;
                }
            }

            return (object) [
                'cc'             => $cc,
                'nombreTitular'  => $nombreTitular,
                'celTitular'     => $celTitular,
                'detalle'        => $detalle,
                'totalFacturado' => $detalle->sum('facturado'),
                'totalPagado'    => $detalle->sum('pagado'),
                'totalSaldo'     => $detalle->sum('saldo'),
            ];
        })->sortByDesc(fn($g) => $g->totalSaldo)->values();

        $granTotalFacturado = $porCC->sum('totalFacturado');
        $granTotalPagado    = $porCC->sum('totalPagado');
        $granTotalSaldo     = $porCC->sum('totalSaldo');

        return view('cartera.por_cc', compact(
            'porCC', 'granTotalFacturado', 'granTotalPagado', 'granTotalSaldo'
        ));
    }

    // ── Exportar informe de cartera general ──────────────────────────────────

    public function exportarInforme()
    {
        $facturaPorAlumno = DB::table('facturacion')
            ->select('codigo_alumno', DB::raw('SUM(valor) as total_facturado'))
            ->groupBy('codigo_alumno');

        $pagoPorAlumno = DB::table('registro_pagos')
            ->select('codigo_alumno', DB::raw('SUM(valor) as total_pagado'))
            ->groupBy('codigo_alumno');

        $filas = DB::table(DB::raw("({$facturaPorAlumno->toSql()}) as f"))
            ->mergeBindings($facturaPorAlumno)
            ->leftJoinSub($pagoPorAlumno, 'p', 'f.codigo_alumno', '=', 'p.codigo_alumno')
            ->leftJoin('ESTUDIANTES as e', 'e.CODIGO', '=', 'f.codigo_alumno')
            ->select(
                'f.codigo_alumno',
                DB::raw("TRIM(CONCAT(COALESCE(e.NOMBRE1,''),' ',COALESCE(e.NOMBRE2,''),' ',COALESCE(e.APELLIDO1,''),' ',COALESCE(e.APELLIDO2,''))) as nombre"),
                'e.CURSO',
                'f.total_facturado',
                DB::raw('COALESCE(p.total_pagado, 0) as total_pagado'),
                DB::raw('f.total_facturado - COALESCE(p.total_pagado, 0) as saldo')
            )
            ->orderByDesc('saldo')
            ->get();

        $nombre = 'informe_cartera_' . date('Ymd_His') . '.csv';
        $tmp    = tempnam(sys_get_temp_dir(), 'car') . '.csv';
        $fh     = fopen($tmp, 'w');

        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, ['CODIGO', 'NOMBRE', 'CURSO', 'FACTURADO', 'PAGADO', 'SALDO'], ';');

        foreach ($filas as $f) {
            fputcsv($fh, [
                $f->codigo_alumno,
                trim(preg_replace('/\s+/', ' ', $f->nombre)),
                $f->CURSO ?? '',
                number_format((float) $f->total_facturado, 2, ',', '.'),
                number_format((float) $f->total_pagado,    2, ',', '.'),
                number_format((float) $f->saldo,           2, ',', '.'),
            ], ';');
        }

        fclose($fh);

        return response()->download($tmp, $nombre, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ])->deleteFileAfterSend(true);
    }

    // ── Exportar cartera / anticipos (deudores) ──────────────────────────────

    public function exportarDeudores(Request $request)
    {
        @ini_set('memory_limit', '-1');

        $tab        = $request->input('tab', 'cartera');
        $fechaDesde = $request->filled('fecha_desde') ? $request->input('fecha_desde') : null;
        $fechaHasta = $request->filled('fecha_hasta') ? $request->input('fecha_hasta') : null;

        $facturaSub = DB::table('facturacion')
            ->select('codigo_alumno', DB::raw('SUM(valor) as total_facturado'))
            ->when($fechaDesde, fn($q) => $q->whereDate('fecha', '>=', $fechaDesde))
            ->when($fechaHasta, fn($q) => $q->whereDate('fecha', '<=', $fechaHasta))
            ->groupBy('codigo_alumno');

        $pagoSub = DB::table('registro_pagos')
            ->select('codigo_alumno', DB::raw('SUM(valor) as total_pagado'))
            ->when($fechaDesde, fn($q) => $q->whereDate('fecha', '>=', $fechaDesde))
            ->when($fechaHasta, fn($q) => $q->whereDate('fecha', '<=', $fechaHasta))
            ->groupBy('codigo_alumno');

        $query = DB::table(DB::raw("({$facturaSub->toSql()}) as f"))
            ->mergeBindings($facturaSub)
            ->leftJoinSub($pagoSub, 'p', 'f.codigo_alumno', '=', 'p.codigo_alumno')
            ->leftJoin('ESTUDIANTES as e', 'e.CODIGO', '=', 'f.codigo_alumno')
            ->leftJoin('INFO_PADRES as ip', 'ip.CODIGO', '=', 'f.codigo_alumno')
            ->select(
                'f.codigo_alumno',
                'e.NOMBRE1', 'e.NOMBRE2', 'e.APELLIDO1', 'e.APELLIDO2', 'e.CURSO',
                'f.total_facturado',
                DB::raw('COALESCE(p.total_pagado, 0) as total_pagado'),
                DB::raw('f.total_facturado - COALESCE(p.total_pagado, 0) as saldo'),
                'ip.ACUD', 'ip.CEL_ACUD'
            );

        if ($tab === 'anticipos') {
            $query->whereRaw('f.total_facturado - COALESCE(p.total_pagado, 0) < 0')->orderBy('saldo');
            $titulo = 'Anticipos';
            $color  = '1A5C38';
        } else {
            $query->whereRaw('f.total_facturado - COALESCE(p.total_pagado, 0) > 0')->orderByDesc('saldo');
            $titulo = 'Cartera';
            $color  = '7B1A1A';
        }

        $filas = $query->get();

        $nombreArchivo = strtolower($titulo) . '_' . date('Ymd_His') . '.csv';
        $tmp           = tempnam(sys_get_temp_dir(), 'deu') . '.csv';
        $fh            = fopen($tmp, 'w');

        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, ['CODIGO', 'NOMBRE', 'CURSO', 'FACTURADO', 'PAGADO', 'SALDO', 'ACUDIENTE', 'CELULAR'], ';');

        foreach ($filas as $f) {
            $nombre = trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
                $f->NOMBRE1, $f->NOMBRE2, $f->APELLIDO1, $f->APELLIDO2
            ]))));
            fputcsv($fh, [
                $f->codigo_alumno,
                $nombre,
                $f->CURSO ?? '',
                number_format((float) $f->total_facturado, 2, ',', '.'),
                number_format((float) $f->total_pagado,    2, ',', '.'),
                number_format((float) $f->saldo,           2, ',', '.'),
                $f->ACUD ?? '',
                $f->CEL_ACUD ?? '',
            ], ';');
        }

        fclose($fh);

        return response()->download($tmp, $nombreArchivo, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ])->deleteFileAfterSend(true);
    }
}
