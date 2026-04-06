<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RutasController extends Controller
{
    public function index(Request $request)
    {
        $rutaFiltro = $request->ruta;

        $query = DB::table('listado_transporte as lt')
            ->join('ESTUDIANTES as e', 'e.CODIGO', '=', 'lt.codigo')
            ->select(
                'lt.id',
                'lt.codigo',
                'lt.ruta',
                'lt.clase_ruta',
                'lt.barrio',
                'lt.direccion',
                'lt.telefono',
                'lt.quien_recibe',
                DB::raw("TRIM(CONCAT(
                    COALESCE(e.NOMBRE1,''),' ',
                    COALESCE(e.NOMBRE2,''),' ',
                    COALESCE(e.APELLIDO1,''),' ',
                    COALESCE(e.APELLIDO2,'')
                )) as nombre_completo"),
                'e.CURSO'
            )
            ->when($rutaFiltro, fn($q) => $q->where('lt.ruta', $rutaFiltro))
            ->orderBy('lt.ruta')
            ->orderBy('e.APELLIDO1')
            ->orderBy('e.NOMBRE1');

        $listado = $query->get()
            ->map(function ($row) {
                $row->nombre_completo = preg_replace('/\s+/', ' ', trim($row->nombre_completo));
                return $row;
            })
            ->groupBy('ruta');

        $rutas = DB::table('listado_transporte')
            ->distinct()
            ->whereNotNull('ruta')
            ->where('ruta', '!=', '')
            ->orderBy('ruta')
            ->pluck('ruta');

        return view('rutas.index', compact('listado', 'rutas', 'rutaFiltro'));
    }
}
