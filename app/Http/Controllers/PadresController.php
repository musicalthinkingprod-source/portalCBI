<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\FechasController;

class PadresController extends Controller
{
    private function verificarAcceso(string $tipoCodigo): ?string
    {
        $estudiante = session('padre_estudiante');
        if (!$estudiante) return 'sin_sesion';

        $codigo    = $estudiante->CODIGO;
        $facturado = DB::table('facturacion')->where('codigo_alumno', $codigo)->sum('valor');
        $pagado    = DB::table('registro_pagos')->where('codigo_alumno', $codigo)->sum('valor');
        if (($facturado - $pagado) > 100000) return 'deuda';

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

        $notas = collect();
        try {
            $notas = DB::table('NOTAS_'.$anio.' as n')
                ->join('CODIGOSMAT as m', 'm.CODIGO_MAT', '=', 'n.CODIGO_MAT')
                ->where('n.CODIGO_ALUM', $codigo)
                ->select('n.PERIODO', 'n.NOTA', 'n.TIPODENOTA', 'm.NOMBRE_MAT', 'm.CODIGO_MAT')
                ->orderBy('m.NOMBRE_MAT')->orderBy('n.PERIODO')
                ->get();
        } catch (\Exception $e) {}

        return view('padres.notas', compact('estudiante', 'notas', 'anio'));
    }

    public function boletines()
    {
        $bloqueo = $this->verificarAcceso('B');
        if ($bloqueo === 'sin_sesion') return redirect()->route('padres.portal');
        if ($bloqueo === 'deuda')      return redirect()->route('padres.portal')->with('aviso', 'No puedes consultar los boletines mientras tengas un saldo pendiente.');
        if ($bloqueo === 'fechas')     return redirect()->route('padres.portal')->with('aviso', 'La institución aún no ha publicado los boletines.');

        $estudiante = session('padre_estudiante');
        $datos = \App\Http\Controllers\BoletinController::datos((int) $estudiante->CODIGO);
        if (empty($datos)) abort(404);

        $origen = 'padres';
        return view('boletines.ver', array_merge($datos, compact('origen')));
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
