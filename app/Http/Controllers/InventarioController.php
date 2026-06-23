<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Módulo de Inventario de uniformes (INVCBI).
 *
 * Acceso: SuperAd, Admin y Secretarías (definido en routes/web.php).
 *
 * El stock NO se almacena: se calcula por movimientos
 *   stock = SUM(compras) - SUM(ventas activas)
 * Las "ventas" incluyen tanto ventas a estudiantes (con cobro) como
 * dotaciones a docentes de Ed. Física (sin cobro, total 0).
 */
class InventarioController extends Controller
{
    // ───────────────────────────── Stock ─────────────────────────────

    /** Mapa [producto_id => unidades en stock] calculado por movimientos. */
    private function stockProductos(): array
    {
        $compras = DB::table('inv_compra_items')
            ->select('producto_id', DB::raw('SUM(cantidad) AS n'))
            ->groupBy('producto_id')->pluck('n', 'producto_id');

        $ventas = DB::table('inv_venta_items')
            ->join('inv_ventas', 'inv_ventas.id', '=', 'inv_venta_items.venta_id')
            ->where('inv_ventas.estado', 'activa')
            ->select('producto_id', DB::raw('SUM(cantidad) AS n'))
            ->groupBy('producto_id')->pluck('n', 'producto_id');

        // Devoluciones: 'entra' reingresa al stock, 'sale' se entrega en el cambio.
        $devEntra = DB::table('inv_devolucion_items')->where('sentido', 'entra')
            ->select('producto_id', DB::raw('SUM(cantidad) AS n'))
            ->groupBy('producto_id')->pluck('n', 'producto_id');
        $devSale = DB::table('inv_devolucion_items')->where('sentido', 'sale')
            ->select('producto_id', DB::raw('SUM(cantidad) AS n'))
            ->groupBy('producto_id')->pluck('n', 'producto_id');

        $stock = [];
        foreach ($compras as $id => $n) $stock[$id] = (int) $n;
        foreach ($ventas as $id => $n) $stock[$id] = ($stock[$id] ?? 0) - (int) $n;
        foreach ($devEntra as $id => $n) $stock[$id] = ($stock[$id] ?? 0) + (int) $n;
        foreach ($devSale as $id => $n) $stock[$id] = ($stock[$id] ?? 0) - (int) $n;

        return $stock;
    }

    // ─────────────────────────── Dashboard ───────────────────────────

    public function dashboard()
    {
        $stock = $this->stockProductos();

        $productos = DB::table('inv_productos')->where('activo', 1)
            ->orderBy('nombre')->get()
            ->map(function ($p) use ($stock) {
                $p->stock = $stock[$p->id] ?? 0;
                return $p;
            });

        $resumen = [
            'referencias'  => $productos->count(),
            'unidades'     => $productos->sum('stock'),
            'agotados'     => $productos->where('stock', '<=', 0)->count(),
            'bajos'        => $productos->where('stock', '>', 0)->where('stock', '<=', 2)->count(),
            'valor_venta'  => $productos->sum(fn ($p) => $p->stock * $p->precio_venta),
        ];

        // Para revisar qué comprar: lo de menor stock primero.
        $porComprar = $productos->sortBy('stock')->take(15)->values();

        return view('inventario.dashboard', compact('productos', 'resumen', 'porComprar'));
    }

    // ─────────────────────── Catálogo · Productos ─────────────────────

    public function productos()
    {
        $stock = $this->stockProductos();
        $productos = DB::table('inv_productos')->orderBy('nombre')->get()
            ->map(function ($p) use ($stock) { $p->stock = $stock[$p->id] ?? 0; return $p; });

        return view('inventario.productos', compact('productos'));
    }

    public function productoStore(Request $r)
    {
        $data = $r->validate([
            'codigo' => 'required|integer|unique:inv_productos,codigo',
            'nombre' => 'required|string|max:150',
        ]);
        // El precio de venta lo fija la primera compra (costo + 30%); arranca en 0.
        $data['precio_venta'] = 0;
        $data['activo'] = 1;
        $data['created_at'] = $data['updated_at'] = now();
        DB::table('inv_productos')->insert($data);

        return back()->with('ok', 'Producto creado. Asigna su precio en «Precios y costos».');
    }

    public function productoUpdate(Request $r, int $id)
    {
        // El precio de venta NO se edita a mano: se calcula desde la compra.
        $data = $r->validate([
            'codigo' => "required|integer|unique:inv_productos,codigo,{$id}",
            'nombre' => 'required|string|max:150',
            'activo' => 'nullable|boolean',
        ]);
        $data['activo'] = $r->boolean('activo');
        $data['updated_at'] = now();
        DB::table('inv_productos')->where('id', $id)->update($data);

        return back()->with('ok', 'Producto actualizado.');
    }

    // ─────────────────── Precios y costos (Admin/SuperAd) ────────────

    /**
     * Lista de precios: el precio de venta se DIGITA (según resolución de costos).
     * Se muestra el último costo de compra para ver la ganancia por prenda.
     */
    public function precios()
    {
        $costo = $this->ultimoCostoProductos();

        $productos = DB::table('inv_productos')->orderBy('nombre')->get()
            ->map(function ($p) use ($costo) {
                $p->ultimo_costo = $costo[$p->id] ?? null;
                $p->ganancia = $p->ultimo_costo !== null ? ($p->precio_venta - $p->ultimo_costo) : null;
                $p->margen = ($p->ultimo_costo) ? round((($p->precio_venta - $p->ultimo_costo) / $p->ultimo_costo) * 100, 1) : null;
                return $p;
            });

        return view('inventario.precios', compact('productos'));
    }

    public function precioUpdate(Request $r, int $id)
    {
        $data = $r->validate([
            'precio_venta' => 'required|numeric|min:0',
        ]);
        DB::table('inv_productos')->where('id', $id)->update([
            'precio_venta' => $data['precio_venta'],
            'updated_at'   => now(),
        ]);

        return back()->with('ok', 'Precio actualizado.');
    }

    // ────────────────────── Catálogo · Proveedores ───────────────────

    public function proveedores()
    {
        $proveedores = DB::table('inv_proveedores')->orderBy('nombre')->get();
        return view('inventario.proveedores', compact('proveedores'));
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

    // ───────────────────────────── Compras ───────────────────────────

    public function compras()
    {
        $compras = DB::table('inv_compras')
            ->join('inv_proveedores', 'inv_proveedores.id', '=', 'inv_compras.proveedor_id')
            ->select('inv_compras.*', 'inv_proveedores.nombre AS proveedor')
            ->orderByDesc('inv_compras.fecha')->orderByDesc('inv_compras.id')
            ->limit(100)->get();

        return view('inventario.compras_index', compact('compras'));
    }

    public function compraCreate()
    {
        $proveedores = DB::table('inv_proveedores')->where('activo', 1)->orderBy('nombre')->get();
        $productos   = DB::table('inv_productos')->where('activo', 1)->orderBy('nombre')->get();

        return view('inventario.compras_create', compact('proveedores', 'productos'));
    }

    public function compraStore(Request $r)
    {
        $data = $r->validate([
            'proveedor_id'        => 'required|exists:inv_proveedores,id',
            'factura'             => 'nullable|string|max:50',
            'fecha'               => 'required|date',
            'observacion'         => 'nullable|string|max:255',
            'items'               => 'required|array|min:1',
            'items.*.producto_id' => 'required|exists:inv_productos,id',
            'items.*.cantidad'    => 'required|integer|min:1',
            'items.*.precio'      => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($data) {
            $total = 0;
            foreach ($data['items'] as $it) $total += $it['cantidad'] * $it['precio'];

            $compraId = DB::table('inv_compras')->insertGetId([
                'proveedor_id' => $data['proveedor_id'],
                'factura'      => $data['factura'] ?? null,
                'fecha'        => $data['fecha'],
                'total'        => $total,
                'observacion'  => $data['observacion'] ?? null,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            foreach ($data['items'] as $it) {
                DB::table('inv_compra_items')->insert([
                    'compra_id'     => $compraId,
                    'producto_id'   => $it['producto_id'],
                    'cantidad'      => $it['cantidad'],
                    'precio_compra' => $it['precio'],
                    'subtotal'      => $it['cantidad'] * $it['precio'],
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        });

        return redirect()->route('inventario.compras')->with('ok', 'Compra registrada.');
    }

    /** Mapa [producto_id => último precio de compra registrado]. */
    private function ultimoCostoProductos(): array
    {
        // Última compra por producto (mayor fecha, desempate por id de compra).
        $rows = DB::table('inv_compra_items')
            ->join('inv_compras', 'inv_compras.id', '=', 'inv_compra_items.compra_id')
            ->orderBy('inv_compra_items.producto_id')
            ->orderByDesc('inv_compras.fecha')->orderByDesc('inv_compras.id')
            ->get(['inv_compra_items.producto_id', 'inv_compra_items.precio_compra']);

        $costo = [];
        foreach ($rows as $r) {
            if (!array_key_exists($r->producto_id, $costo)) {
                $costo[$r->producto_id] = (float) $r->precio_compra;
            }
        }
        return $costo;
    }

    // ────────────────────────── Ventas (POS) ─────────────────────────

    public function ventas()
    {
        $ventas = DB::table('inv_ventas')
            ->leftJoin('ESTUDIANTES', 'ESTUDIANTES.CODIGO', '=', 'inv_ventas.estudiante_codigo')
            ->leftJoin('nomina_empleados', 'nomina_empleados.id', '=', 'inv_ventas.empleado_id')
            ->select(
                'inv_ventas.*',
                DB::raw("TRIM(CONCAT(COALESCE(ESTUDIANTES.NOMBRE1,''),' ',COALESCE(ESTUDIANTES.APELLIDO1,''),' ',COALESCE(ESTUDIANTES.APELLIDO2,''))) AS estudiante"),
                'nomina_empleados.nombre AS docente'
            )
            ->orderByDesc('inv_ventas.id')->limit(100)->get();

        return view('inventario.ventas_index', compact('ventas'));
    }

    public function ventaCreate()
    {
        $productos = DB::table('inv_productos')->where('activo', 1)->orderBy('nombre')
            ->get(['id', 'codigo', 'nombre', 'precio_venta']);

        // Docentes candidatos a dotación (cargo con "fisica"/"física").
        $docentes = DB::table('nomina_empleados')
            ->where(function ($q) {
                $q->where('cargo', 'like', '%fisic%')->orWhere('cargo', 'like', '%físic%');
            })
            ->orderBy('nombre')->get(['id', 'nombre', 'cargo']);

        // Si no hay docentes que coincidan, ofrecer todos para no bloquear.
        if ($docentes->isEmpty()) {
            $docentes = DB::table('nomina_empleados')->orderBy('nombre')->get(['id', 'nombre', 'cargo']);
        }

        // Precios meta del uniforme completo (para mostrar el descuento en el POS).
        $metasCompleto = DB::table('inv_uniforme_completo')->pluck('precio', 'talla');

        return view('inventario.ventas_create', compact('productos', 'docentes', 'metasCompleto'));
    }

    /** Búsqueda de producto por código de barras (escáner) — responde JSON. */
    public function buscarProducto(Request $r)
    {
        $codigo = trim($r->query('codigo', ''));
        if ($codigo === '') return response()->json(null, 404);

        $p = DB::table('inv_productos')->where('activo', 1)
            ->where('codigo', $codigo)
            ->first(['id', 'codigo', 'nombre', 'precio_venta']);

        if (!$p) return response()->json(null, 404);

        $stock = $this->stockProductos();
        $p->stock = $stock[$p->id] ?? 0;

        return response()->json($p);
    }

    /** Búsqueda de estudiante por código — responde JSON (nombre para el POS). */
    public function buscarEstudiante(Request $r)
    {
        $codigo = trim($r->query('codigo', ''));
        $e = DB::table('ESTUDIANTES')->where('CODIGO', $codigo)
            ->first(['CODIGO', 'NOMBRE1', 'NOMBRE2', 'APELLIDO1', 'APELLIDO2', 'GRADO', 'CURSO']);

        if (!$e) return response()->json(null, 404);

        $nombre = trim("{$e->NOMBRE1} {$e->NOMBRE2} {$e->APELLIDO1} {$e->APELLIDO2}");
        return response()->json([
            'codigo' => $e->CODIGO,
            'nombre' => preg_replace('/\s+/', ' ', $nombre),
            'grado'  => trim(($e->GRADO ?? '') . ' ' . ($e->CURSO ?? '')),
        ]);
    }

    public function ventaStore(Request $r)
    {
        $data = $r->validate([
            'tipo'                => 'required|in:venta,dotacion',
            'estudiante_codigo'   => 'required_if:tipo,venta|nullable|integer',
            'empleado_id'         => 'required_if:tipo,dotacion|nullable|exists:nomina_empleados,id',
            'fecha'               => 'required|date',
            'items'               => 'required|array|min:1',
            'items.*.producto_id' => 'required|exists:inv_productos,id',
            'items.*.cantidad'    => 'required|integer|min:1',
        ]);

        $esDotacion = $data['tipo'] === 'dotacion';

        // Validar existencias antes de descontar.
        $stock = $this->stockProductos();
        $faltantes = [];
        foreach ($data['items'] as $it) {
            $disp = $stock[$it['producto_id']] ?? 0;
            if ($it['cantidad'] > $disp) $faltantes[] = $it['producto_id'];
        }
        if ($faltantes) {
            return back()->withInput()->withErrors([
                'items' => 'No hay stock suficiente para uno o más artículos.',
            ]);
        }

        DB::transaction(function () use ($data, $esDotacion) {
            // El precio SIEMPRE sale de la BD (precio fijo), nunca del formulario.
            $precios = DB::table('inv_productos')
                ->whereIn('id', array_column($data['items'], 'producto_id'))
                ->pluck('precio_venta', 'id');

            $subtotal = 0;
            foreach ($data['items'] as $it) {
                if (!$esDotacion) $subtotal += $it['cantidad'] * (float) ($precios[$it['producto_id']] ?? 0);
            }

            // El descuento lo calcula el sistema (no las secretarías).
            // TODO: descuento automático por uniforme completo (pendiente de definir los elementos).
            $descuento = $esDotacion ? 0 : $this->descuentoAutomatico($data['items']);
            $total     = $esDotacion ? 0 : max(0, $subtotal - $descuento);

            $numero = (int) DB::table('inv_ventas')->max('numero') + 1;

            $ventaId = DB::table('inv_ventas')->insertGetId([
                'numero'            => $numero,
                'tipo'              => $data['tipo'],
                'estudiante_codigo' => $esDotacion ? null : $data['estudiante_codigo'],
                'empleado_id'       => $esDotacion ? $data['empleado_id'] : null,
                'fecha'             => $data['fecha'],
                'descuento'         => $descuento,
                'total'             => $total,
                'estado'            => 'activa',
                'vendedor_user'     => Auth::user()->USER ?? null,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            foreach ($data['items'] as $it) {
                $precio = $esDotacion ? 0 : (float) ($precios[$it['producto_id']] ?? 0);
                DB::table('inv_venta_items')->insert([
                    'venta_id'     => $ventaId,
                    'producto_id'  => $it['producto_id'],
                    'cantidad'     => $it['cantidad'],
                    'precio_venta' => $precio,
                    'subtotal'     => $it['cantidad'] * $precio,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        });

        // Las ventas (no las dotaciones) quedan pendientes de facturar → avisar al Admin.
        if (!$esDotacion) {
            $this->notificarAdmins(
                'uniformes_facturar',
                'Uniformes por facturar',
                'Hay una nueva venta de uniformes pendiente de facturar.'
            );
        }

        $msg = $esDotacion ? 'Dotación a docente registrada.' : 'Venta registrada (queda pendiente de facturar).';
        return redirect()->route('inventario.ventas')->with('ok', $msg);
    }

    /**
     * Notifica a los perfiles Admin y SuperAd usando el mismo canal (campana) que
     * la Agenda Estudiantil Virtual. El helper deduplica avisos no leídos por tipo+mensaje.
     */
    private function notificarAdmins(string $tipo, string $titulo, string $mensaje): void
    {
        foreach (['Admin', 'SuperAd'] as $perfil) {
            NotificacionesController::crear($perfil, $tipo, $titulo, $mensaje, route('inventario.facturar'));
        }
    }

    /** Clasifica una prenda: [tipo, talla]. Tipo en {chaqueta,pantalon,camiseta,pantaloneta}. */
    private function clasificarPrenda(string $nombre): array
    {
        $n = mb_strtolower($nombre, 'UTF-8');
        $n = strtr($n, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n']);

        $talla = null;
        if (preg_match('/t\s*-?\s*(xl|\d+|s|m|l)\s*$/', $n, $m)) $talla = strtoupper($m[1]);

        $tipo = null;
        if (str_starts_with($n, 'chaqueta sudadera'))      $tipo = 'chaqueta';
        elseif (str_starts_with($n, 'pantalon sudadera'))  $tipo = 'pantalon';
        elseif (str_starts_with($n, 'camiseta'))           $tipo = 'camiseta';
        elseif (str_starts_with($n, 'pantaloneta'))        $tipo = 'pantaloneta';

        return [$tipo, $talla];
    }

    /**
     * Descuento automático por "uniforme completo": chaqueta + pantalón de sudadera
     * + camiseta + pantaloneta de la MISMA talla (sin bata). Por cada conjunto
     * completo, deja esas 4 prendas en el precio meta de la talla (tabla
     * inv_uniforme_completo). Las secretarías no lo manejan a mano.
     *
     * @param array $items lista de ['producto_id' => int, 'cantidad' => int]
     */
    private function descuentoAutomatico(array $items): float
    {
        $ids = array_column($items, 'producto_id');
        if (!$ids) return 0;

        $prods = DB::table('inv_productos')->whereIn('id', $ids)->get()->keyBy('id');

        $cant = [];
        foreach ($items as $it) $cant[$it['producto_id']] = ($cant[$it['producto_id']] ?? 0) + $it['cantidad'];

        // Agrupar por talla los precios y cantidades de cada tipo de prenda.
        $porTalla = [];
        foreach ($prods as $p) {
            [$tipo, $talla] = $this->clasificarPrenda($p->nombre);
            if (!$tipo || !$talla) continue;
            $porTalla[$talla][$tipo] = ['precio' => (float) $p->precio_venta, 'cant' => $cant[$p->id]];
        }

        $metas = DB::table('inv_uniforme_completo')->pluck('precio', 'talla');
        $req = ['chaqueta', 'pantalon', 'camiseta', 'pantaloneta'];

        $descuento = 0;
        foreach ($porTalla as $talla => $g) {
            if (count(array_intersect($req, array_keys($g))) < 4) continue;   // faltan prendas
            if (!isset($metas[$talla])) continue;

            $conjuntos = min($g['chaqueta']['cant'], $g['pantalon']['cant'], $g['camiseta']['cant'], $g['pantaloneta']['cant']);
            if ($conjuntos < 1) continue;

            $suma = $g['chaqueta']['precio'] + $g['pantalon']['precio'] + $g['camiseta']['precio'] + $g['pantaloneta']['precio'];
            $porConjunto = $suma - (float) $metas[$talla];
            if ($porConjunto > 0) $descuento += $conjuntos * $porConjunto;
        }

        return $descuento;
    }

    public function ventaAnular(int $id)
    {
        DB::table('inv_ventas')->where('id', $id)->update([
            'estado' => 'anulada', 'updated_at' => now(),
        ]);
        return back()->with('ok', 'Documento anulado. El stock fue devuelto.');
    }

    // ──────────────── Facturación (Admin / SuperAd) ────────────────

    /** Cuántas ventas hay pendientes de facturar (para el badge del menú). */
    public static function pendientesFacturar(): int
    {
        return (int) DB::table('inv_ventas')
            ->where('tipo', 'venta')->where('estado', 'activa')->where('facturada', false)
            ->count();
    }

    public function facturarIndex()
    {
        $ventas = DB::table('inv_ventas')
            ->leftJoin('ESTUDIANTES', 'ESTUDIANTES.CODIGO', '=', 'inv_ventas.estudiante_codigo')
            ->where('inv_ventas.tipo', 'venta')
            ->where('inv_ventas.estado', 'activa')
            ->where('inv_ventas.facturada', false)
            ->select('inv_ventas.*', DB::raw("TRIM(CONCAT(COALESCE(ESTUDIANTES.NOMBRE1,''),' ',COALESCE(ESTUDIANTES.APELLIDO1,''),' ',COALESCE(ESTUDIANTES.APELLIDO2,''))) AS estudiante"))
            ->orderBy('inv_ventas.fecha')->orderBy('inv_ventas.numero')
            ->get();

        return view('inventario.facturar', compact('ventas'));
    }

    public function facturar(Request $r)
    {
        $ids = (array) $r->input('ids', []);
        if (!$ids) return back()->with('ok', 'No se seleccionaron ventas.');

        $n = DB::table('inv_ventas')->whereIn('id', $ids)
            ->where('tipo', 'venta')->where('estado', 'activa')->where('facturada', false)
            ->update([
                'facturada' => true, 'facturada_at' => now(),
                'facturada_por' => Auth::user()->USER ?? null, 'updated_at' => now(),
            ]);

        return back()->with('ok', "$n venta(s) facturada(s).");
    }

    // ──────────────── Devoluciones y cambios ────────────────

    public function cambiosIndex()
    {
        $productos = DB::table('inv_productos')->where('activo', 1)->orderBy('nombre')
            ->get(['id', 'codigo', 'nombre', 'precio_venta']);

        return view('inventario.cambios', compact('productos'));
    }

    /** Detalle de una venta por número (JSON) para el formulario de devolución/cambio. */
    public function ventaPorNumero(Request $r)
    {
        $numero = trim($r->query('numero', ''));
        $v = DB::table('inv_ventas')->where('numero', $numero)->where('tipo', 'venta')->first();
        if (!$v) return response()->json(null, 404);

        // Cantidades ya devueltas (para no permitir devolver de más).
        $yaDev = DB::table('inv_devolucion_items')
            ->join('inv_devoluciones', 'inv_devoluciones.id', '=', 'inv_devolucion_items.devolucion_id')
            ->where('inv_devoluciones.venta_id', $v->id)->where('inv_devolucion_items.sentido', 'entra')
            ->select('producto_id', DB::raw('SUM(cantidad) AS n'))->groupBy('producto_id')->pluck('n', 'producto_id');

        $items = DB::table('inv_venta_items')
            ->join('inv_productos', 'inv_productos.id', '=', 'inv_venta_items.producto_id')
            ->where('venta_id', $v->id)
            ->get(['inv_venta_items.producto_id', 'inv_productos.codigo', 'inv_productos.nombre', 'inv_venta_items.cantidad', 'inv_venta_items.precio_venta'])
            ->map(function ($it) use ($yaDev) {
                $it->devuelto = (int) ($yaDev[$it->producto_id] ?? 0);
                $it->disponible = $it->cantidad - $it->devuelto;
                return $it;
            });

        return response()->json([
            'id' => $v->id, 'numero' => $v->numero, 'estado' => $v->estado,
            'facturada' => (bool) $v->facturada, 'fecha' => $v->fecha, 'total' => $v->total,
            'items' => $items,
        ]);
    }

    public function cambioStore(Request $r)
    {
        $data = $r->validate([
            'venta_id'                => 'required|exists:inv_ventas,id',
            'accion'                  => 'required|in:cancelar,cambio',
            'motivo'                  => 'nullable|string|max:255',
            'devueltos'               => 'array',
            'devueltos.*.producto_id' => 'required|exists:inv_productos,id',
            'devueltos.*.cantidad'    => 'required|integer|min:1',
            'nuevos'                  => 'array',
            'nuevos.*.producto_id'    => 'required|exists:inv_productos,id',
            'nuevos.*.cantidad'       => 'required|integer|min:1',
        ]);

        $venta = DB::table('inv_ventas')->where('id', $data['venta_id'])->first();
        if (!$venta || $venta->estado !== 'activa') {
            return back()->withErrors(['venta' => 'La venta no existe o ya está anulada.']);
        }
        $facturada = (bool) $venta->facturada;

        // ── Cancelación (solo si NO está facturada) ──
        if ($data['accion'] === 'cancelar') {
            if ($facturada) {
                return back()->withErrors(['venta' => 'La venta ya fue facturada: no se puede cancelar, solo cambio.']);
            }
            DB::transaction(function () use ($venta, $data) {
                DB::table('inv_ventas')->where('id', $venta->id)->update(['estado' => 'anulada', 'updated_at' => now()]);
                DB::table('inv_devoluciones')->insert([
                    'venta_id' => $venta->id, 'tipo' => 'cancelacion', 'fecha' => now(),
                    'diferencia' => -$venta->total, 'motivo' => $data['motivo'] ?? null,
                    'usuario' => Auth::user()->USER ?? null, 'created_at' => now(), 'updated_at' => now(),
                ]);
            });
            return redirect()->route('inventario.cambios')->with('ok', 'Venta cancelada: se reembolsa el total y las prendas reingresan al stock.');
        }

        // ── Cambio ──
        $devueltos = $data['devueltos'] ?? [];
        $nuevos    = $data['nuevos'] ?? [];
        if (!$devueltos || !$nuevos) {
            return back()->withErrors(['venta' => 'Indica las prendas devueltas y las prendas nuevas del cambio.']);
        }

        // No devolver más de lo que queda por devolver de esa venta.
        $vendidos = DB::table('inv_venta_items')->where('venta_id', $venta->id)->pluck('cantidad', 'producto_id');
        $yaDev = DB::table('inv_devolucion_items')
            ->join('inv_devoluciones', 'inv_devoluciones.id', '=', 'inv_devolucion_items.devolucion_id')
            ->where('inv_devoluciones.venta_id', $venta->id)->where('inv_devolucion_items.sentido', 'entra')
            ->select('producto_id', DB::raw('SUM(cantidad) AS n'))->groupBy('producto_id')->pluck('n', 'producto_id');
        foreach ($devueltos as $d) {
            $disp = ($vendidos[$d['producto_id']] ?? 0) - ($yaDev[$d['producto_id']] ?? 0);
            if ($d['cantidad'] > $disp) {
                return back()->withErrors(['venta' => 'No puedes devolver más de lo vendido (revisa cantidades).']);
            }
        }

        // Precios desde la BD.
        $ids = array_merge(array_column($devueltos, 'producto_id'), array_column($nuevos, 'producto_id'));
        $precios = DB::table('inv_productos')->whereIn('id', $ids)->pluck('precio_venta', 'id');
        $valDev = 0; foreach ($devueltos as $d) $valDev += $d['cantidad'] * (float) ($precios[$d['producto_id']] ?? 0);
        $valNue = 0; foreach ($nuevos as $n) $valNue += $n['cantidad'] * (float) ($precios[$n['producto_id']] ?? 0);
        $diferencia = $valNue - $valDev; // + se cobra al cliente · - se le devuelve

        if ($facturada && abs($diferencia) > 0.001) {
            return back()->withErrors(['venta' => 'La venta está facturada: solo se permite cambio par (mismo valor). Diferencia detectada: $' . number_format($diferencia, 0)]);
        }

        // Stock de las prendas nuevas.
        $stock = $this->stockProductos();
        foreach ($nuevos as $n) {
            if ($n['cantidad'] > ($stock[$n['producto_id']] ?? 0)) {
                return back()->withErrors(['venta' => 'No hay stock suficiente de una de las prendas nuevas.']);
            }
        }

        DB::transaction(function () use ($venta, $data, $devueltos, $nuevos, $precios, $diferencia) {
            $devId = DB::table('inv_devoluciones')->insertGetId([
                'venta_id' => $venta->id, 'tipo' => 'cambio', 'fecha' => now(),
                'diferencia' => $diferencia, 'motivo' => $data['motivo'] ?? null,
                'usuario' => Auth::user()->USER ?? null, 'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($devueltos as $d) {
                DB::table('inv_devolucion_items')->insert([
                    'devolucion_id' => $devId, 'producto_id' => $d['producto_id'], 'sentido' => 'entra',
                    'cantidad' => $d['cantidad'], 'precio' => (float) ($precios[$d['producto_id']] ?? 0),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            foreach ($nuevos as $n) {
                DB::table('inv_devolucion_items')->insert([
                    'devolucion_id' => $devId, 'producto_id' => $n['producto_id'], 'sentido' => 'sale',
                    'cantidad' => $n['cantidad'], 'precio' => (float) ($precios[$n['producto_id']] ?? 0),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        });

        $msg = 'Cambio registrado.';
        if ($diferencia > 0)      $msg .= ' Cobrar diferencia: $' . number_format($diferencia, 0);
        elseif ($diferencia < 0)  $msg .= ' Devolver al cliente: $' . number_format(abs($diferencia), 0);
        return redirect()->route('inventario.cambios')->with('ok', $msg);
    }
}
