<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ListadoEstudiantesController extends Controller
{
    public function index()
    {
        $sedes  = DB::table('ESTUDIANTES')
            ->whereRaw("TRIM(UPPER(ESTADO)) = 'MATRICULADO'")
            ->whereNotNull('SEDE')->where('SEDE', '<>', '')
            ->distinct()->orderBy('SEDE')->pluck('SEDE');

        $cursos = DB::table('ESTUDIANTES')
            ->whereRaw("TRIM(UPPER(ESTADO)) = 'MATRICULADO'")
            ->whereNotNull('CURSO')->where('CURSO', '<>', '')
            ->distinct()->orderBy('CURSO')->pluck('CURSO');

        return view('listado-estudiantes.index', compact('sedes', 'cursos'));
    }

    public function exportar(Request $request)
    {
        $query = DB::table('ESTUDIANTES')
            ->whereRaw("TRIM(UPPER(ESTADO)) = 'MATRICULADO'")
            ->select(
                'CODIGO',
                DB::raw("TRIM(CONCAT(COALESCE(NOMBRE1,''),' ',COALESCE(NOMBRE2,''),' ',COALESCE(APELLIDO1,''),' ',COALESCE(APELLIDO2,''))) as NOMBRE_COMPLETO"),
                'CURSO',
                'SEDE'
            );

        if ($request->filled('sede'))  $query->where('SEDE', $request->sede);
        if ($request->filled('curso')) $query->where('CURSO', $request->curso);

        $query->orderBy('CURSO')->orderBy('APELLIDO1')->orderBy('NOMBRE1');

        $tmp    = storage_path('app') . DIRECTORY_SEPARATOR . 'est_' . uniqid() . '.xlsx';
        $writer = new \App\Helpers\SimpleXlsx();
        $writer->addRow(['CODIGO', 'NOMBRE', 'CURSO', 'SEDE']);

        foreach ($query->get() as $e) {
            $writer->addRow([
                (int) $e->CODIGO,
                trim(preg_replace('/\s+/', ' ', $e->NOMBRE_COMPLETO)),
                $e->CURSO,
                $e->SEDE,
            ]);
        }

        $writer->save($tmp);

        $nombre = 'listado_estudiantes_' . date('Ymd') . '.xlsx';
        return response()->download($tmp, $nombre, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
