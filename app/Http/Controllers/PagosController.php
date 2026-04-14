<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PagosController extends Controller
{
    public function index(Request $request)
    {
        $sortable  = ['codigo_alumno', 'fecha', 'concepto', 'mes', 'valor'];
        $sortCol   = in_array($request->sort, $sortable) ? $request->sort : 'fecha';
        $sortDir   = $request->direction === 'asc' ? 'asc' : 'desc';

        $query = DB::table('registro_pagos')->orderBy($sortCol, $sortDir);

        if ($request->filled('codigo_alumno')) {
            $query->where('codigo_alumno', $request->codigo_alumno);
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->fecha_hasta);
        }
        if ($request->filled('concepto')) {
            $query->where('concepto', 'like', '%' . $request->concepto . '%');
        }
        if ($request->filled('mes')) {
            $query->where('mes', 'like', '%' . $request->mes . '%');
        }
        if ($request->filled('orden')) {
            $query->where('orden', 'like', '%' . $request->orden . '%');
        }

        $pagos = $query->paginate(40)->withQueryString();

        return view('pagos.index', compact('pagos', 'sortCol', 'sortDir'));
    }

    public function create()
    {
        return view('pagos.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'codigo_alumno' => 'required|integer',
            'fecha'         => 'required|date',
            'valor'         => 'required|numeric',
            'concepto'      => 'required|string|max:100',
            'mes'           => 'required|string|max:20',
            'orden'         => 'nullable|string|max:100',
        ]);

        DB::table('registro_pagos')->insert([
            'codigo_alumno' => $request->codigo_alumno,
            'fecha'         => $request->fecha,
            'valor'         => $request->valor,
            'concepto'      => $request->concepto,
            'mes'           => $request->mes,
            'orden'         => $request->orden,
        ]);

        return redirect()->route('pagos.index')->with('success', 'Pago registrado correctamente.');
    }

    public function edit($id)
    {
        $pago = DB::table('registro_pagos')->where('id', $id)->first();
        abort_if(!$pago, 404);
        return view('pagos.edit', compact('pago'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'codigo_alumno' => 'required|integer',
            'fecha'         => 'required|date',
            'valor'         => 'required|numeric',
            'concepto'      => 'required|string|max:100',
            'mes'           => 'required|string|max:20',
            'orden'         => 'nullable|string|max:100',
        ]);

        DB::table('registro_pagos')->where('id', $id)->update([
            'codigo_alumno' => $request->codigo_alumno,
            'fecha'         => $request->fecha,
            'valor'         => $request->valor,
            'concepto'      => $request->concepto,
            'mes'           => $request->mes,
            'orden'         => $request->orden,
        ]);

        return redirect()->route('pagos.index')->with('success', 'Pago actualizado correctamente.');
    }

    public function destroy($id)
    {
        DB::table('registro_pagos')->where('id', $id)->delete();
        return redirect()->route('pagos.index')->with('success', 'Pago eliminado correctamente.');
    }

    public function exportarExcel(Request $request)
    {
        $sortable = ['codigo_alumno', 'fecha', 'concepto', 'mes', 'valor'];
        $sortCol  = in_array($request->sort, $sortable) ? $request->sort : 'fecha';
        $sortDir  = $request->direction === 'asc' ? 'asc' : 'desc';

        $query = DB::table('registro_pagos')->orderBy($sortCol, $sortDir);

        if ($request->filled('codigo_alumno')) $query->where('codigo_alumno', $request->codigo_alumno);
        if ($request->filled('fecha_desde'))   $query->whereDate('fecha', '>=', $request->fecha_desde);
        if ($request->filled('fecha_hasta'))   $query->whereDate('fecha', '<=', $request->fecha_hasta);
        if ($request->filled('concepto'))      $query->where('concepto', 'like', '%' . $request->concepto . '%');
        if ($request->filled('mes'))           $query->where('mes', 'like', '%' . $request->mes . '%');
        if ($request->filled('orden'))         $query->where('orden', 'like', '%' . $request->orden . '%');

        $tmp    = tempnam(sys_get_temp_dir(), 'pag') . '.xlsx';
        $writer = new \OpenSpout\Writer\XLSX\Writer();
        $writer->openToFile($tmp);

        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(
            ['CODIGO ALUMNO', 'FECHA', 'CONCEPTO', 'MES', 'ORDEN', 'VALOR']
        ));

        $query->chunk(500, function ($filas) use ($writer) {
            foreach ($filas as $p) {
                $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues([
                    (int) $p->codigo_alumno,
                    $p->fecha,
                    $p->concepto,
                    $p->mes,
                    $p->orden ?? '',
                    (float) $p->valor,
                ]));
            }
        });

        $writer->close();

        $nombre = 'pagos_' . date('Ymd_His') . '.xlsx';
        return response()->download($tmp, $nombre, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
