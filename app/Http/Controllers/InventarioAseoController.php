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

    /**
     * Informe de aseo (pantalla principal del módulo).
     *
     * Acepta ?minimo=, ?objetivo= y ?dias= para ajustar qué se considera
     * «por reponer», cuántas unidades se quieren tener de cada elemento y
     * qué ventana de consumo se analiza.
     */
    public function dashboard(Request $r)
    {
        [$minimo, $objetivo, $dias] = $this->umbrales($r);

        $datos = $this->datosInforme($minimo, $objetivo, $dias);
        $datos['autoriza']   = $this->autorizaCompras();
        $datos['pendientes'] = self::contarSolicitudes(['pendiente', 'aprobada']);

        return view('aseo.dashboard', $datos);
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

    /**
     * Formulario de compra. Con ?solicitud=N (aprobada) las líneas llegan
     * prellenadas con lo que autorizó la administración.
     */
    public function compraCreate(Request $r)
    {
        $proveedores = DB::table('inv_proveedores')->where('activo', 1)->orderBy('nombre')->get();
        $elementos   = DB::table('inv_elementos_aseo')->where('activo', 1)->orderBy('descripcion')->get();

        $solicitud  = null;
        $prellenado = [];

        if ($solId = (int) $r->query('solicitud', 0)) {
            $solicitud = DB::table('inv_aseo_solicitudes_compra')
                ->where('id', $solId)->where('estado', 'aprobada')->first();

            if ($solicitud) {
                $prellenado = DB::table('inv_aseo_solicitud_items AS i')
                    ->join('inv_elementos_aseo AS e', 'e.id', '=', 'i.elemento_id')
                    ->where('i.solicitud_id', $solId)
                    ->whereRaw('COALESCE(i.cantidad_aprobada, i.cantidad) > 0')
                    ->orderBy('e.descripcion')
                    ->get([
                        'i.elemento_id',
                        DB::raw('COALESCE(i.cantidad_aprobada, i.cantidad) AS cantidad'),
                        DB::raw('COALESCE(i.costo_ref, 0) AS precio'),
                    ]);
            }
        }

        return view('aseo.compras_create', compact('proveedores', 'elementos', 'solicitud', 'prellenado'));
    }

    public function compraStore(Request $r)
    {
        $data = $r->validate([
            'proveedor_id'        => 'required|exists:inv_proveedores,id',
            'documento'           => 'nullable|string|max:50',
            'fecha'               => 'required|date',
            'observacion'         => 'nullable|string|max:255',
            'solicitud_id'        => 'nullable|exists:inv_aseo_solicitudes_compra,id',
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

            // Si la compra viene de una solicitud aprobada, se cierra el ciclo.
            if (!empty($data['solicitud_id'])) {
                DB::table('inv_aseo_solicitudes_compra')
                    ->where('id', $data['solicitud_id'])->where('estado', 'aprobada')
                    ->update([
                        'estado'         => 'comprada',
                        'aseo_compra_id' => $compraId,
                        'comprado_por'   => Auth::user()->USER ?? null,
                        'comprado_at'    => now(),
                        'updated_at'     => now(),
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

    // ══════════════ Informe imprimible y solicitudes de compra ══════════════

    /** ¿El usuario actual autoriza compras? (jefatura: Admin / SuperAd). */
    private function autorizaCompras(): bool
    {
        return in_array(Auth::user()->PROFILE ?? '', ['SuperAd', 'Admin'], true);
    }

    /**
     * Mapa [elemento_id => último precio de compra registrado].
     *
     * Se ignoran los precios en 0: el cargue inicial del inventario se hizo
     * sin valores y un «$0» en el informe se leería como un precio real.
     * Mientras no haya una compra con precios, el elemento queda sin costo.
     */
    private function ultimoCostoElementos(): array
    {
        $rows = DB::table('inv_aseo_compra_items')
            ->join('inv_aseo_compras', 'inv_aseo_compras.id', '=', 'inv_aseo_compra_items.aseo_compra_id')
            ->where('inv_aseo_compra_items.precio_compra', '>', 0)
            ->orderBy('inv_aseo_compra_items.elemento_id')
            ->orderByDesc('inv_aseo_compras.fecha')->orderByDesc('inv_aseo_compras.id')
            ->get(['inv_aseo_compra_items.elemento_id', 'inv_aseo_compra_items.precio_compra']);

        $costo = [];
        foreach ($rows as $r) {
            if (!array_key_exists($r->elemento_id, $costo)) {
                $costo[$r->elemento_id] = (float) $r->precio_compra;
            }
        }
        return $costo;
    }

    /**
     * Consumo por elemento: lo entregado a dependencias en los últimos N días.
     *
     * @return array [elemento_id => unidades entregadas]
     */
    private function consumoElementos(int $dias): array
    {
        return DB::table('inv_aseo_salida_items AS i')
            ->join('inv_aseo_salidas AS s', 's.id', '=', 'i.salida_id')
            ->where('s.fecha', '>=', now()->subDays($dias)->toDateString())
            ->select('i.elemento_id', DB::raw('SUM(i.cantidad) AS n'))
            ->groupBy('i.elemento_id')
            ->pluck('n', 'i.elemento_id')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    /**
     * Datos del informe de aseo.
     *
     * @param int $minimo   stock a partir del cual se considera «por reponer»
     * @param int $objetivo unidades que se quieren tener de cada elemento
     * @param int $dias     ventana de consumo a analizar
     */
    private function datosInforme(int $minimo, int $objetivo, int $dias): array
    {
        $stock   = $this->stockElementos();
        $costo   = $this->ultimoCostoElementos();
        $consumo = $this->consumoElementos($dias);

        $elementos = DB::table('inv_elementos_aseo')->where('activo', 1)
            ->orderBy('descripcion')->get()
            ->map(function ($e) use ($stock, $costo, $consumo, $objetivo) {
                $e->stock        = $stock[$e->id] ?? 0;
                $e->ultimo_costo = $costo[$e->id] ?? null;
                $e->consumo      = $consumo[$e->id] ?? 0;
                $e->sugerido     = max(0, $objetivo - $e->stock);
                $e->valor_costo  = $e->stock * (float) ($e->ultimo_costo ?? 0);
                return $e;
            });

        $resumen = [
            'referencias' => $elementos->count(),
            'unidades'    => $elementos->sum('stock'),
            'agotados'    => $elementos->where('stock', '<=', 0)->count(),
            'bajos'       => $elementos->where('stock', '>', 0)->where('stock', '<=', $minimo)->count(),
            'valor_costo' => $elementos->sum('valor_costo'),
            'consumo'     => $elementos->sum('consumo'),
        ];

        // Lo que hay que reponer. Si todo está por encima del mínimo, se
        // muestran igual los 15 elementos con menos existencias.
        $porComprar = $elementos->where('stock', '<=', $minimo)->sortBy('stock')->values();
        if ($porComprar->isEmpty()) {
            $porComprar = $elementos->sortBy('stock')->take(15)->values();
        }

        $resumen['costo_reposicion'] = $porComprar->sum(fn ($e) => $e->sugerido * (float) ($e->ultimo_costo ?? 0));
        // Para avisar en el informe cuando todavía no hay precios de compra.
        $resumen['sin_costo'] = $elementos->whereNull('ultimo_costo')->count();

        // A qué dependencias se fue el aseo en la ventana analizada.
        $porDependencia = DB::table('inv_aseo_salida_items AS i')
            ->join('inv_aseo_salidas AS s', 's.id', '=', 'i.salida_id')
            ->join('inv_dependencias AS d', 'd.id', '=', 's.dependencia_id')
            ->where('s.fecha', '>=', now()->subDays($dias)->toDateString())
            ->groupBy('d.id', 'd.nombre')
            ->orderByDesc(DB::raw('SUM(i.cantidad)'))
            ->get([
                'd.nombre',
                DB::raw('SUM(i.cantidad) AS unidades'),
                DB::raw('COUNT(DISTINCT s.id) AS entregas'),
            ]);

        return compact('elementos', 'resumen', 'porComprar', 'porDependencia', 'minimo', 'objetivo', 'dias');
    }

    /** Lee los umbrales del querystring con valores por defecto sensatos. */
    private function umbrales(Request $r): array
    {
        return [
            max(0, min(99,  (int) $r->query('minimo', 2))),
            max(1, min(999, (int) $r->query('objetivo', 10))),
            max(7, min(730, (int) $r->query('dias', 90))),
        ];
    }

    /** Informe de aseo en formato imprimible (hoja carta, sin menú). */
    public function informeImprimir(Request $r)
    {
        [$minimo, $objetivo, $dias] = $this->umbrales($r);
        $datos = $this->datosInforme($minimo, $objetivo, $dias);
        $datos['soloReposicion'] = $r->boolean('solo_reposicion');

        return view('aseo.informe_imprimir', $datos);
    }

    // ─────────────────── Solicitudes de compra · bandeja ───────────────────

    /**
     * Conteo de solicitudes por estado.
     *
     * Tolera que la migración de solicitudes aún no se haya corrido: el menú
     * lateral llama esto en cada pantalla y no debe tumbar el portal.
     */
    private static function contarSolicitudes(array $estados): int
    {
        try {
            return (int) DB::table('inv_aseo_solicitudes_compra')->whereIn('estado', $estados)->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** Solicitudes de aseo pendientes de autorización (badge del menú). */
    public static function pendientesAutorizar(): int
    {
        return self::contarSolicitudes(['pendiente']);
    }

    public function solicitudes()
    {
        $solicitudes = DB::table('inv_aseo_solicitudes_compra AS s')
            ->leftJoin('inv_proveedores AS pv', 'pv.id', '=', 's.proveedor_id')
            ->leftJoin('inv_aseo_compras AS c', 'c.id', '=', 's.aseo_compra_id')
            ->select('s.*', 'pv.nombre AS proveedor', 'c.documento',
                DB::raw('(SELECT COUNT(*) FROM inv_aseo_solicitud_items i WHERE i.solicitud_id = s.id) AS n_items'),
                DB::raw('(SELECT SUM(COALESCE(i.cantidad_aprobada, i.cantidad)) FROM inv_aseo_solicitud_items i WHERE i.solicitud_id = s.id) AS n_unidades'))
            ->orderByDesc('s.id')->limit(200)->get();

        return view('aseo.solicitudes_index', [
            'solicitudes' => $solicitudes,
            'autoriza'    => $this->autorizaCompras(),
        ]);
    }

    /** Formulario de solicitud, prellenado con lo que está por debajo del mínimo. */
    public function solicitudCreate(Request $r)
    {
        [$minimo, $objetivo, $dias] = $this->umbrales($r);
        $datos = $this->datosInforme($minimo, $objetivo, $dias);

        $datos['proveedores'] = DB::table('inv_proveedores')->where('activo', 1)->orderBy('nombre')->get();
        $datos['autoriza']    = $this->autorizaCompras();

        return view('aseo.solicitud_create', $datos);
    }

    public function solicitudStore(Request $r)
    {
        $data = $r->validate([
            'proveedor_id'        => 'nullable|exists:inv_proveedores,id',
            'justificacion'       => 'nullable|string|max:500',
            'dias'                => 'nullable|integer',
            'items'               => 'required|array|min:1',
            'items.*.elemento_id' => 'required|exists:inv_elementos_aseo,id',
            'items.*.cantidad'    => 'required|integer|min:1',
        ]);

        // Una sola línea por elemento: si viene repetido, se suman cantidades.
        $lineas = [];
        foreach ($data['items'] as $it) {
            $eid = (int) $it['elemento_id'];
            $lineas[$eid] = ($lineas[$eid] ?? 0) + (int) $it['cantidad'];
        }

        $dias     = max(7, min(730, (int) ($data['dias'] ?? 90)));
        $stock    = $this->stockElementos();
        $costo    = $this->ultimoCostoElementos();
        $consumo  = $this->consumoElementos($dias);
        $usuario  = Auth::user()->USER ?? null;
        $autoriza = $this->autorizaCompras();

        $id = DB::transaction(function () use ($data, $lineas, $stock, $costo, $consumo, $usuario, $autoriza) {
            $numero = (int) DB::table('inv_aseo_solicitudes_compra')->max('numero') + 1;

            $solId = DB::table('inv_aseo_solicitudes_compra')->insertGetId([
                'numero'         => $numero,
                'fecha'          => now()->toDateString(),
                // La jefatura no tiene que autorizarse a sí misma.
                'estado'         => $autoriza ? 'aprobada' : 'pendiente',
                'justificacion'  => $data['justificacion'] ?? null,
                'solicitado_por' => $usuario,
                'proveedor_id'   => $data['proveedor_id'] ?? null,
                'autorizado_por' => $autoriza ? $usuario : null,
                'autorizado_at'  => $autoriza ? now() : null,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            foreach ($lineas as $eid => $cant) {
                DB::table('inv_aseo_solicitud_items')->insert([
                    'solicitud_id'      => $solId,
                    'elemento_id'       => $eid,
                    'stock_actual'      => $stock[$eid] ?? 0,
                    'consumo_ref'       => $consumo[$eid] ?? 0,
                    'cantidad'          => $cant,
                    'cantidad_aprobada' => $autoriza ? $cant : null,
                    'costo_ref'         => $costo[$eid] ?? null,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            }

            return $solId;
        });

        $msg = $autoriza
            ? 'Solicitud creada y aprobada. Las secretarías ya pueden registrar la compra.'
            : 'Solicitud enviada a autorización de la administración.';

        return redirect()->route('aseo.solicitudes.ver', $id)->with('ok', $msg);
    }

    /** Encabezado + detalle de una solicitud. */
    private function cargarSolicitud(int $id): array
    {
        $sol = DB::table('inv_aseo_solicitudes_compra AS s')
            ->leftJoin('inv_proveedores AS pv', 'pv.id', '=', 's.proveedor_id')
            ->leftJoin('inv_aseo_compras AS c', 'c.id', '=', 's.aseo_compra_id')
            ->where('s.id', $id)
            ->select('s.*', 'pv.nombre AS proveedor', 'c.documento', 'c.fecha AS compra_fecha', 'c.total AS compra_total')
            ->first();

        abort_if(!$sol, 404);

        $stock = $this->stockElementos();
        $items = DB::table('inv_aseo_solicitud_items AS i')
            ->join('inv_elementos_aseo AS e', 'e.id', '=', 'i.elemento_id')
            ->where('i.solicitud_id', $id)
            ->orderBy('e.descripcion')
            ->select('i.*', 'e.codigo', 'e.descripcion', 'e.presentacion')
            ->get()
            ->map(function ($i) use ($stock) {
                $i->stock_hoy      = $stock[$i->elemento_id] ?? 0;
                $i->cantidad_final = $i->cantidad_aprobada ?? $i->cantidad;
                $i->subtotal       = $i->cantidad_final * (float) ($i->costo_ref ?? 0);
                return $i;
            });

        return [$sol, $items];
    }

    public function solicitudVer(int $id)
    {
        [$sol, $items] = $this->cargarSolicitud($id);

        return view('aseo.solicitud_ver', [
            'sol'      => $sol,
            'items'    => $items,
            'autoriza' => $this->autorizaCompras(),
        ]);
    }

    public function solicitudImprimir(int $id)
    {
        [$sol, $items] = $this->cargarSolicitud($id);
        return view('aseo.solicitud_imprimir', compact('sol', 'items'));
    }

    /** Aprobar (con cantidades ajustables) o rechazar. Solo jefatura. */
    public function solicitudAutorizar(Request $r, int $id)
    {
        abort_unless($this->autorizaCompras(), 403);

        $data = $r->validate([
            'decision'     => 'required|in:aprobar,rechazar',
            'nota'         => 'nullable|string|max:500',
            'cantidades'   => 'nullable|array',
            'cantidades.*' => 'nullable|integer|min:0',
        ]);

        $sol = DB::table('inv_aseo_solicitudes_compra')->where('id', $id)->first();
        abort_if(!$sol, 404);

        if (!in_array($sol->estado, ['pendiente', 'aprobada'], true)) {
            return back()->withErrors(['estado' => 'Esta solicitud ya está cerrada; no se puede modificar.']);
        }

        if ($data['decision'] === 'rechazar') {
            DB::table('inv_aseo_solicitudes_compra')->where('id', $id)->update([
                'estado'            => 'rechazada',
                'autorizado_por'    => Auth::user()->USER ?? null,
                'autorizado_at'     => now(),
                'nota_autorizacion' => $data['nota'] ?? null,
                'updated_at'        => now(),
            ]);
            return redirect()->route('aseo.solicitudes.ver', $id)->with('ok', 'Solicitud rechazada.');
        }

        $cantidades = $data['cantidades'] ?? [];

        DB::transaction(function () use ($id, $cantidades, $data) {
            foreach (DB::table('inv_aseo_solicitud_items')->where('solicitud_id', $id)->get() as $it) {
                // Lo aprobado en 0 simplemente no se compra.
                $cant = array_key_exists($it->id, $cantidades) && $cantidades[$it->id] !== null
                    ? (int) $cantidades[$it->id]
                    : (int) $it->cantidad;
                DB::table('inv_aseo_solicitud_items')->where('id', $it->id)
                    ->update(['cantidad_aprobada' => $cant, 'updated_at' => now()]);
            }

            DB::table('inv_aseo_solicitudes_compra')->where('id', $id)->update([
                'estado'            => 'aprobada',
                'autorizado_por'    => Auth::user()->USER ?? null,
                'autorizado_at'     => now(),
                'nota_autorizacion' => $data['nota'] ?? null,
                'updated_at'        => now(),
            ]);
        });

        return redirect()->route('aseo.solicitudes.ver', $id)
            ->with('ok', 'Solicitud aprobada. Las secretarías ya pueden registrar la compra.');
    }

    /** Anular una solicitud que aún no se ha comprado. */
    public function solicitudAnular(int $id)
    {
        $sol = DB::table('inv_aseo_solicitudes_compra')->where('id', $id)->first();
        abort_if(!$sol, 404);

        if ($sol->estado === 'comprada') {
            return back()->withErrors(['estado' => 'La solicitud ya se compró: no se puede anular.']);
        }
        // Quien no autoriza solo puede anular lo que él mismo pidió y sigue pendiente.
        if (!$this->autorizaCompras() &&
            ($sol->estado !== 'pendiente' || $sol->solicitado_por !== (Auth::user()->USER ?? null))) {
            abort(403);
        }

        DB::table('inv_aseo_solicitudes_compra')->where('id', $id)
            ->update(['estado' => 'anulada', 'updated_at' => now()]);

        return redirect()->route('aseo.solicitudes')->with('ok', 'Solicitud anulada.');
    }
}
