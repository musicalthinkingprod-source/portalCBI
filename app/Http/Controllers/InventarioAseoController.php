<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Módulo de Inventario de ASEO (INVCBI).
 *
 * Acceso: SuperAd, Admin y Secretarías.
 *
 * El aseo se compra a proveedores y se entrega a dependencias internas
 * (Cocina, Servicios Generales…); no se vende. El stock se calcula:
 *   stock = SUM(compras de aseo) - SUM(salidas a dependencias)
 */
class InventarioAseoController extends Controller
{
    /** Mapa [elemento_id => unidades en stock]. */
    private function stockElementos(): array
    {
        $compras = DB::table('inv_aseo_compra_items')
            ->select('elemento_id', DB::raw('SUM(cantidad) AS n'))
            ->groupBy('elemento_id')->pluck('n', 'elemento_id');
        $salidas = DB::table('inv_aseo_salida_items')
            ->select('elemento_id', DB::raw('SUM(cantidad) AS n'))
            ->groupBy('elemento_id')->pluck('n', 'elemento_id');

        $stock = [];
        foreach ($compras as $id => $n) $stock[$id] = (int) $n;
        foreach ($salidas as $id => $n) $stock[$id] = ($stock[$id] ?? 0) - (int) $n;
        return $stock;
    }

    // ─────────────────────────── Dashboard ───────────────────────────

    public function dashboard()
    {
        $stock = $this->stockElementos();
        $elementos = DB::table('inv_elementos_aseo')->where('activo', 1)->orderBy('descripcion')->get()
            ->map(function ($e) use ($stock) { $e->stock = $stock[$e->id] ?? 0; return $e; });

        $resumen = [
            'referencias' => $elementos->count(),
            'unidades'    => $elementos->sum('stock'),
            'agotados'    => $elementos->where('stock', '<=', 0)->count(),
            'bajos'       => $elementos->where('stock', '>', 0)->where('stock', '<=', 2)->count(),
        ];
        $porComprar = $elementos->sortBy('stock')->take(15)->values();

        return view('aseo.dashboard', compact('elementos', 'resumen', 'porComprar'));
    }

    // ─────────────────────── Catálogo · Elementos ────────────────────

    public function elementos()
    {
        $stock = $this->stockElementos();
        $elementos = DB::table('inv_elementos_aseo')->orderBy('descripcion')->get()
            ->map(function ($e) use ($stock) { $e->stock = $stock[$e->id] ?? 0; return $e; });

        return view('aseo.elementos', compact('elementos'));
    }

    public function elementoStore(Request $r)
    {
        $data = $r->validate([
            'codigo'       => 'required|integer|unique:inv_elementos_aseo,codigo',
            'descripcion'  => 'required|string|max:150',
            'presentacion' => 'nullable|string|max:60',
        ]);
        $data['activo'] = 1;
        $data['created_at'] = $data['updated_at'] = now();
        DB::table('inv_elementos_aseo')->insert($data);

        return back()->with('ok', 'Elemento creado.');
    }

    public function elementoUpdate(Request $r, int $id)
    {
        $data = $r->validate([
            'codigo'       => "required|integer|unique:inv_elementos_aseo,codigo,{$id}",
            'descripcion'  => 'required|string|max:150',
            'presentacion' => 'nullable|string|max:60',
            'activo'       => 'nullable|boolean',
        ]);
        $data['activo'] = $r->boolean('activo');
        $data['updated_at'] = now();
        DB::table('inv_elementos_aseo')->where('id', $id)->update($data);

        return back()->with('ok', 'Elemento actualizado.');
    }

    // ───────────────────────── Dependencias ──────────────────────────

    public function dependencias()
    {
        // Cuántas entregas tiene cada dependencia (para no borrar las que ya se usaron).
        $conMovs = DB::table('inv_aseo_salidas')
            ->select('dependencia_id', DB::raw('COUNT(*) AS n'))
            ->groupBy('dependencia_id')->pluck('n', 'dependencia_id');
        $dependencias = DB::table('inv_dependencias')->orderBy('nombre')->get()
            ->map(function ($d) use ($conMovs) { $d->movimientos = (int) ($conMovs[$d->id] ?? 0); return $d; });

        return view('aseo.dependencias', compact('dependencias'));
    }

    public function dependenciaStore(Request $r)
    {
        $data = $r->validate(['nombre' => 'required|string|max:100']);
        $data['activo'] = 1;
        $data['created_at'] = $data['updated_at'] = now();
        DB::table('inv_dependencias')->insert($data);

        return back()->with('ok', 'Dependencia creada.');
    }

    public function dependenciaDestroy(int $id)
    {
        // No se puede borrar una dependencia que ya recibió entregas (integridad).
        if (DB::table('inv_aseo_salidas')->where('dependencia_id', $id)->exists()) {
            return back()->withErrors(['dependencia' => 'No se puede borrar: la dependencia ya tiene entregas registradas.']);
        }
        DB::table('inv_dependencias')->where('id', $id)->delete();

        return back()->with('ok', 'Dependencia eliminada.');
    }

    // ───────────────────────── Proveedores ───────────────────────────
    // Los proveedores son compartidos con el módulo de uniformes (inv_proveedores).

    public function proveedores()
    {
        $proveedores = DB::table('inv_proveedores')->orderBy('nombre')->get();
        return view('aseo.proveedores', compact('proveedores'));
    }

    public function proveedorStore(Request $r)
    {
        $data = $r->validate([
            'nombre'    => 'required|string|max:120',
            'nit'       => 'nullable|string|max:30',
            'direccion' => 'nullable|string|max:120',
            'telefono'  => 'nullable|string|max:30',
        ]);
        $data['activo'] = 1;
        $data['created_at'] = $data['updated_at'] = now();
        DB::table('inv_proveedores')->insert($data);

        return back()->with('ok', 'Proveedor creado.');
    }

    // ─────────────────────────── Compras ─────────────────────────────

    public function compras()
    {
        $compras = DB::table('inv_aseo_compras')
            ->join('inv_proveedores', 'inv_proveedores.id', '=', 'inv_aseo_compras.proveedor_id')
            ->select('inv_aseo_compras.*', 'inv_proveedores.nombre AS proveedor')
            ->orderByDesc('inv_aseo_compras.fecha')->orderByDesc('inv_aseo_compras.id')
            ->limit(100)->get();

        return view('aseo.compras_index', compact('compras'));
    }

    public function compraCreate()
    {
        $proveedores = DB::table('inv_proveedores')->where('activo', 1)->orderBy('nombre')->get();
        $elementos   = DB::table('inv_elementos_aseo')->where('activo', 1)->orderBy('descripcion')->get();

        return view('aseo.compras_create', compact('proveedores', 'elementos'));
    }

    public function compraStore(Request $r)
    {
        $data = $r->validate([
            'proveedor_id'        => 'required|exists:inv_proveedores,id',
            'documento'           => 'nullable|string|max:50',
            'fecha'               => 'required|date',
            'observacion'         => 'nullable|string|max:255',
            'items'               => 'required|array|min:1',
            'items.*.elemento_id' => 'required|exists:inv_elementos_aseo,id',
            'items.*.cantidad'    => 'required|integer|min:1',
            'items.*.precio'      => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($data) {
            $total = 0;
            foreach ($data['items'] as $it) $total += $it['cantidad'] * $it['precio'];

            $compraId = DB::table('inv_aseo_compras')->insertGetId([
                'proveedor_id' => $data['proveedor_id'],
                'documento'    => $data['documento'] ?? null,
                'fecha'        => $data['fecha'],
                'total'        => $total,
                'observacion'  => $data['observacion'] ?? null,
                'created_at'   => now(), 'updated_at' => now(),
            ]);
            foreach ($data['items'] as $it) {
                DB::table('inv_aseo_compra_items')->insert([
                    'aseo_compra_id' => $compraId, 'elemento_id' => $it['elemento_id'],
                    'cantidad' => $it['cantidad'], 'precio_compra' => $it['precio'],
                    'subtotal' => $it['cantidad'] * $it['precio'],
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });

        return redirect()->route('aseo.compras')->with('ok', 'Compra de aseo registrada.');
    }

    // ───────────────────── Salidas a dependencias ────────────────────

    public function salidas()
    {
        $salidas = DB::table('inv_aseo_salidas')
            ->join('inv_dependencias', 'inv_dependencias.id', '=', 'inv_aseo_salidas.dependencia_id')
            ->select('inv_aseo_salidas.*', 'inv_dependencias.nombre AS dependencia')
            ->orderByDesc('inv_aseo_salidas.fecha')->orderByDesc('inv_aseo_salidas.id')
            ->limit(100)->get();

        return view('aseo.salidas_index', compact('salidas'));
    }

    public function salidaCreate()
    {
        $dependencias = DB::table('inv_dependencias')->where('activo', 1)->orderBy('nombre')->get();
        $elementos    = DB::table('inv_elementos_aseo')->where('activo', 1)->orderBy('descripcion')
            ->get(['id', 'codigo', 'descripcion', 'presentacion']);

        return view('aseo.salidas_create', compact('dependencias', 'elementos'));
    }

    public function salidaStore(Request $r)
    {
        $data = $r->validate([
            'dependencia_id'      => 'required|exists:inv_dependencias,id',
            'fecha'               => 'required|date',
            'observacion'         => 'nullable|string|max:255',
            'items'               => 'required|array|min:1',
            'items.*.elemento_id' => 'required|exists:inv_elementos_aseo,id',
            'items.*.cantidad'    => 'required|integer|min:1',
        ]);

        // Validar existencias.
        $stock = $this->stockElementos();
        foreach ($data['items'] as $it) {
            if ($it['cantidad'] > ($stock[$it['elemento_id']] ?? 0)) {
                return back()->withInput()->withErrors(['items' => 'No hay stock suficiente de uno o más elementos.']);
            }
        }

        DB::transaction(function () use ($data) {
            $salidaId = DB::table('inv_aseo_salidas')->insertGetId([
                'dependencia_id' => $data['dependencia_id'],
                'fecha'          => $data['fecha'],
                'observacion'    => $data['observacion'] ?? null,
                'entregado_por'  => Auth::user()->USER ?? null,
                'created_at'     => now(), 'updated_at' => now(),
            ]);
            foreach ($data['items'] as $it) {
                DB::table('inv_aseo_salida_items')->insert([
                    'salida_id' => $salidaId, 'elemento_id' => $it['elemento_id'],
                    'cantidad' => $it['cantidad'], 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });

        return redirect()->route('aseo.salidas')->with('ok', 'Entrega a dependencia registrada.');
    }
}
