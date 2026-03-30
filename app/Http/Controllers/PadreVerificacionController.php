<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PadreVerificacionController extends Controller
{
    public function verificar(Request $request)
    {
        $request->validate([
            'cedula' => 'required|numeric',
            'codigo' => 'required|numeric',
        ]);

        $cedula = $request->cedula;
        $codigo = $request->codigo;

        $padre = DB::table('INFO_PADRES')
            ->where('CODIGO', $codigo)
            ->where(function ($query) use ($cedula) {
                $query->where('CC_MADRE', $cedula)
                      ->orWhere('CC_PADRE', $cedula)
                      ->orWhere('CC_ACUD', $cedula);
            })
            ->first();

        if (!$padre) {
            return back()->withErrors(['verificacion' => 'No se encontró ningún estudiante con esos datos.']);
        }

        $estudiante = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)->first();

        session([
            'padre_verificado' => true,
            'padre_cedula'     => $cedula,
            'padre_codigo'     => $codigo,
            'padre_estudiante' => $estudiante,
        ]);

        return redirect()->route('padres.portal');
    }
}
