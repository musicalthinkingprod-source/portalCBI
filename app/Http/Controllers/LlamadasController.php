<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LlamadasController extends Controller
{
    // ─── Registro diario ────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $fecha = $request->input('fecha', today()->format('Y-m-d'));

        // Estudiantes ausentes (A o SA) ese día
        $ausentes = DB::table('ASISTENCIA as a')
            ->join('ESTUDIANTES as e', 'e.CODIGO', '=', 'a.CODIGO')
            ->leftJoin('llamadas_inasistencia as ll', function ($join) use ($fecha) {
                $join->on('ll.codigo', '=', 'a.CODIGO')
                     ->where('ll.fecha_inasistencia', $fecha);
            })
            ->leftJoin('listado_transporte as lt', 'lt.codigo', '=', 'a.CODIGO')
            ->where('a.FECHA', $fecha)
            ->whereIn('a.ASISTENCIA', ['A', 'SA'])
            ->select(
                'a.CODIGO',
                'a.ASISTENCIA',
                DB::raw("TRIM(CONCAT(
                    COALESCE(e.NOMBRE1,''),' ',
                    COALESCE(e.NOMBRE2,''),' ',
                    COALESCE(e.APELLIDO1,''),' ',
                    COALESCE(e.APELLIDO2,'')
                )) as nombre_completo"),
                'e.CURSO',
                'll.id as llamada_id',
                'll.motivo',
                'll.quien_atendio',
                'll.observacion',
                'lt.ruta as ruta_transporte'
            )
            ->orderBy('e.APELLIDO1')
            ->orderBy('e.NOMBRE1')
            ->get()
            ->map(function ($row) {
                $row->nombre_completo = preg_replace('/\s+/', ' ', trim($row->nombre_completo));
                return $row;
            });

        $registradas = $ausentes->whereNotNull('llamada_id')->count();
        $pendientes  = $ausentes->whereNull('llamada_id')->count();

        return view('llamadas.index', compact('fecha', 'ausentes', 'registradas', 'pendientes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo'            => 'required|integer',
            'fecha_inasistencia'=> 'required|date',
            'motivo'            => 'required|string|max:300',
            'quien_atendio'     => 'nullable|string|max:100',
            'observacion'       => 'nullable|string|max:1000',
        ]);

        DB::table('llamadas_inasistencia')->updateOrInsert(
            [
                'codigo'            => $request->codigo,
                'fecha_inasistencia'=> $request->fecha_inasistencia,
            ],
            [
                'motivo'         => $request->motivo,
                'quien_atendio'  => $request->quien_atendio,
                'observacion'    => $request->observacion,
                'registrado_por' => auth()->id(),
                'updated_at'     => now(),
                'created_at'     => now(),
            ]
        );

        return redirect()
            ->route('llamadas.index', ['fecha' => $request->fecha_inasistencia])
            ->with('ok', 'Llamada registrada.');
    }

    // ─── Reporte histórico ──────────────────────────────────────────────────

    public function reporte(Request $request)
    {
        $desde  = $request->input('desde', today()->startOfMonth()->format('Y-m-d'));
        $hasta  = $request->input('hasta', today()->format('Y-m-d'));
        $codigo = $request->input('codigo');
        $busqueda = $request->input('busqueda');

        $query = DB::table('llamadas_inasistencia as ll')
            ->join('ESTUDIANTES as e', 'e.CODIGO', '=', 'll.codigo')
            ->leftJoin('PRINUSERS as u', 'u.USER', '=', 'll.registrado_por')
            ->leftJoin('listado_transporte as lt', 'lt.codigo', '=', 'll.codigo')
            ->whereBetween('ll.fecha_inasistencia', [$desde, $hasta])
            ->select(
                'll.*',
                DB::raw("TRIM(CONCAT(
                    COALESCE(e.NOMBRE1,''),' ',
                    COALESCE(e.NOMBRE2,''),' ',
                    COALESCE(e.APELLIDO1,''),' ',
                    COALESCE(e.APELLIDO2,'')
                )) as nombre_completo"),
                'e.CURSO',
                'u.USER as registrado_nombre',
                'lt.ruta as ruta_transporte'
            );

        if ($codigo) {
            $query->where('ll.codigo', $codigo);
        }

        if ($busqueda) {
            $query->where(function ($q) use ($busqueda) {
                $q->where('e.APELLIDO1', 'like', "%$busqueda%")
                  ->orWhere('e.NOMBRE1',  'like', "%$busqueda%")
                  ->orWhere('ll.codigo',  'like', "%$busqueda%");
            });
        }

        $registros = $query
            ->orderBy('ll.fecha_inasistencia', 'desc')
            ->orderBy('e.APELLIDO1')
            ->get()
            ->map(function ($row) {
                $row->nombre_completo = preg_replace('/\s+/', ' ', trim($row->nombre_completo));
                return $row;
            });

        return view('llamadas.reporte', compact('registros', 'desde', 'hasta', 'codigo', 'busqueda'));
    }
}
