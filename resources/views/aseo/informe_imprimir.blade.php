<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Informe de inventario de aseo — {{ now()->format('d/m/Y') }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    @page { size: letter portrait; margin: 1.1cm; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #1a1a1a; background: #f1f5f9; padding: 18px; }
    .page { max-width: 880px; margin: 0 auto; background: #fff; padding: 22px 26px; box-shadow: 0 1px 10px rgba(0,0,0,.08); }

    /* ── Encabezado ── */
    .header { display: flex; align-items: center; gap: 14px; border-bottom: 2.5px solid #0f766e; padding-bottom: 9px; margin-bottom: 14px; }
    .header img { height: 52px; }
    .header .colegio { font-size: 12px; color: #444; letter-spacing: .5px; font-weight: bold; }
    .header .titulo { font-size: 17px; color: #0f766e; font-weight: bold; }
    .header .sub { font-size: 10px; color: #777; }
    .header .meta { margin-left: auto; text-align: right; font-size: 9.5px; color: #666; line-height: 1.5; }

    h2.sec { font-size: 11px; background: #0f766e; color: #fff; padding: 4px 9px; margin: 16px 0 8px; border-radius: 3px; text-transform: uppercase; letter-spacing: .04em; }
    h2.sec span { float: right; font-weight: normal; text-transform: none; letter-spacing: 0; }

    /* ── Tarjetas de resumen ── */
    .cards { display: grid; grid-template-columns: repeat(6, 1fr); gap: 7px; }
    .card { border: 1px solid #e2e8f0; border-radius: 6px; padding: 7px 8px; text-align: center; }
    .card .big { font-size: 15px; font-weight: bold; line-height: 1.2; color: #1e293b; }
    .card .lbl { font-size: 7.5px; text-transform: uppercase; color: #94a3b8; letter-spacing: .04em; margin-top: 2px; }
    .card.rojo .big { color: #b91c1c; } .card.rojo { background: #fef2f2; border-color: #fecaca; }
    .card.ambar .big { color: #b45309; } .card.ambar { background: #fffbeb; border-color: #fde68a; }

    /* ── Tablas ── */
    table.t { width: 100%; border-collapse: collapse; }
    table.t thead { display: table-header-group; }
    table.t th { background: #ecfdf5; color: #0f766e; font-size: 8.5px; text-transform: uppercase; letter-spacing: .03em; padding: 5px 7px; text-align: left; border-bottom: 1.2px solid #a7f3d0; }
    table.t td { padding: 4px 7px; border-bottom: 1px solid #eef1f5; font-size: 10.5px; }
    table.t tr { page-break-inside: avoid; }
    .num { text-align: right; font-variant-numeric: tabular-nums; }
    .cod { font-family: 'Courier New', monospace; color: #475569; }
    .pres { color: #64748b; font-size: 9.5px; }
    .agotado td { background: #fef2f2; }
    .bajo td { background: #fffbeb; }
    tr.total td { background: #ecfdf5; font-weight: bold; border-top: 1.5px solid #0f766e; border-bottom: none; }

    .pill { display: inline-block; font-size: 8.5px; padding: 1px 7px; border-radius: 999px; font-weight: bold; }
    .p-ago { background: #fee2e2; color: #b91c1c; }
    .p-baj { background: #fef3c7; color: #b45309; }
    .p-ok  { background: #dcfce7; color: #15803d; }

    .nota { font-size: 9.5px; color: #64748b; margin-top: 6px; line-height: 1.45; }

    /* ── Firmas ── */
    .firmas { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 32px; margin-top: 46px; page-break-inside: avoid; }
    .firma { border-top: 1px solid #111; padding-top: 4px; text-align: center; font-size: 9px; color: #334155; }
    .firma b { display: block; font-size: 9.5px; color: #111; }

    .autoriza { border: 1.5px dashed #94a3b8; border-radius: 7px; padding: 10px 12px; margin-top: 18px; page-break-inside: avoid; }
    .autoriza .opts { display: flex; gap: 22px; margin: 7px 0 4px; font-size: 10.5px; }
    .autoriza .box { width: 12px; height: 12px; border: 1.2px solid #475569; display: inline-block; border-radius: 2px; vertical-align: -1px; margin-right: 5px; }
    .autoriza .lineas { border-bottom: 1px solid #cbd5e1; height: 16px; margin-top: 8px; }

    .pie { margin-top: 12px; font-size: 8.5px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 6px; }

    /* ── Barra de acciones (no se imprime) ── */
    .no-print { max-width: 880px; margin: 0 auto 14px; display: flex; gap: 9px; align-items: center; flex-wrap: wrap; }
    .no-print button, .no-print a { background: #0f766e; color: #fff; border: none; padding: 8px 20px; border-radius: 6px; font-size: 13px; cursor: pointer; text-decoration: none; display: inline-block; }
    .no-print a.sec { background: #64748b; }
    .no-print a.ok  { background: #059669; }
    .no-print .hint { font-size: 11.5px; color: #64748b; margin-left: auto; }

    @media print {
        body { background: #fff; padding: 0; }
        .page { box-shadow: none; max-width: none; padding: 0; }
        .no-print { display: none !important; }
    }
</style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>
    <a href="{{ route('aseo.solicitudes.create', ['minimo' => $minimo, 'objetivo' => $objetivo, 'dias' => $dias]) }}" class="ok">🛒 Solicitar compra</a>
    <a href="{{ route('aseo.dashboard', ['minimo' => $minimo, 'objetivo' => $objetivo, 'dias' => $dias]) }}" class="sec">← Volver al informe</a>
    <span class="hint">Mínimo: {{ $minimo }} · Objetivo: {{ $objetivo }} · Consumo: {{ $dias }} días</span>
</div>

<div class="page">

    {{-- ══ Encabezado ══ --}}
    <div class="header">
        <img src="{{ asset('images/escudoCBI.png') }}" alt="CBI">
        <div>
            <div class="colegio">COLEGIO BILINGÜE INTEGRAL</div>
            <div class="titulo">Informe de Inventario de Aseo</div>
            <div class="sub">Existencias, consumo por dependencia y costo estimado de reposición</div>
        </div>
        <div class="meta">
            <div><b>Emitido:</b> {{ now()->format('d/m/Y g:i a') }}</div>
            <div><b>Elaboró:</b> {{ auth()->user()->USER ?? '—' }}</div>
            <div><b>Criterio:</b> mínimo {{ $minimo }} · objetivo {{ $objetivo }}</div>
            <div><b>Consumo:</b> últimos {{ $dias }} días</div>
        </div>
    </div>

    {{-- ══ Resumen ══ --}}
    <h2 class="sec">Resumen general</h2>
    <div class="cards">
        <div class="card">
            <div class="big">{{ number_format($resumen['referencias']) }}</div>
            <div class="lbl">Referencias</div>
        </div>
        <div class="card">
            <div class="big">{{ number_format($resumen['unidades']) }}</div>
            <div class="lbl">Unidades en stock</div>
        </div>
        <div class="card ambar">
            <div class="big">{{ number_format($resumen['bajos']) }}</div>
            <div class="lbl">Stock bajo (≤{{ $minimo }})</div>
        </div>
        <div class="card rojo">
            <div class="big">{{ number_format($resumen['agotados']) }}</div>
            <div class="lbl">Agotados</div>
        </div>
        <div class="card">
            <div class="big">{{ number_format($resumen['consumo']) }}</div>
            <div class="lbl">Consumido ({{ $dias }} días)</div>
        </div>
        <div class="card">
            <div class="big">${{ number_format($resumen['valor_costo'], 0) }}</div>
            <div class="lbl">Valor al costo</div>
        </div>
    </div>

    {{-- Sin precios de compra todavía: el costo estimado no se puede calcular. --}}
    @if($resumen['sin_costo'] === $resumen['referencias'] && $resumen['referencias'] > 0)
    <p class="nota" style="border-left:3px solid #f59e0b;background:#fffbeb;padding:6px 9px;margin-top:10px;color:#92400e">
        <b>Sin precios de compra registrados.</b> El inventario se cargó sin valores, así que el costo estimado
        aparecerá en blanco hasta que se registre una compra de aseo con sus precios unitarios.
    </p>
    @endif

    {{-- ══ Por reponer ══ --}}
    <h2 class="sec">
        Elementos por reponer
        <span>{{ $porComprar->count() }} referencia(s) · costo estimado ${{ number_format($resumen['costo_reposicion'], 0) }}</span>
    </h2>
    <table class="t">
        <thead>
            <tr>
                <th style="width:65px">Código</th>
                <th>Elemento</th>
                <th style="width:85px">Presentación</th>
                <th class="num" style="width:50px">Stock</th>
                <th style="width:68px">Estado</th>
                <th class="num" style="width:70px">Consumo</th>
                <th class="num" style="width:68px">Sugerido</th>
                <th class="num" style="width:80px">Últ. costo</th>
                <th class="num" style="width:88px">Estimado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($porComprar as $e)
            <tr class="{{ $e->stock <= 0 ? 'agotado' : ($e->stock <= $minimo ? 'bajo' : '') }}">
                <td class="cod">{{ $e->codigo }}</td>
                <td>{{ $e->descripcion }}</td>
                <td class="pres">{{ $e->presentacion ?: '—' }}</td>
                <td class="num"><b>{{ $e->stock }}</b></td>
                <td>
                    @if($e->stock <= 0)           <span class="pill p-ago">Agotado</span>
                    @elseif($e->stock <= $minimo) <span class="pill p-baj">Bajo</span>
                    @else                         <span class="pill p-ok">Normal</span>
                    @endif
                </td>
                <td class="num">{{ $e->consumo }}</td>
                <td class="num"><b>{{ $e->sugerido }}</b></td>
                <td class="num">{{ $e->ultimo_costo !== null ? '$' . number_format($e->ultimo_costo, 0) : '—' }}</td>
                <td class="num">{{ $e->ultimo_costo !== null ? '$' . number_format($e->sugerido * $e->ultimo_costo, 0) : '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="9" style="text-align:center;color:#94a3b8;padding:14px">No hay elementos por reponer.</td></tr>
            @endforelse
        </tbody>
        @if($porComprar->count())
        <tfoot>
            <tr class="total">
                <td colspan="5" style="text-align:right">Totales</td>
                <td class="num">{{ number_format($porComprar->sum('consumo')) }}</td>
                <td class="num">{{ number_format($porComprar->sum('sugerido')) }}</td>
                <td></td>
                <td class="num">${{ number_format($resumen['costo_reposicion'], 0) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
    <p class="nota">
        La <b>cantidad sugerida</b> es la diferencia entre el objetivo de {{ $objetivo }} unidades por elemento y las existencias actuales.
        La columna <b>consumo</b> muestra lo entregado a las dependencias en los últimos {{ $dias }} días, para contrastar la sugerencia con el gasto real.
        El <b>costo estimado</b> usa el último precio de compra registrado.
    </p>

    {{-- ══ Consumo por dependencia ══ --}}
    @if($porDependencia->count())
    <h2 class="sec">
        Consumo por dependencia
        <span>últimos {{ $dias }} días</span>
    </h2>
    <table class="t">
        <thead>
            <tr>
                <th>Dependencia</th>
                <th class="num" style="width:110px">Entregas</th>
                <th class="num" style="width:110px">Unidades</th>
                <th class="num" style="width:110px">Participación</th>
            </tr>
        </thead>
        <tbody>
            @php $totalDep = $porDependencia->sum('unidades'); @endphp
            @foreach($porDependencia as $d)
            <tr>
                <td>{{ $d->nombre }}</td>
                <td class="num">{{ number_format($d->entregas) }}</td>
                <td class="num"><b>{{ number_format($d->unidades) }}</b></td>
                <td class="num">{{ $totalDep > 0 ? number_format($d->unidades * 100 / $totalDep, 1) . '%' : '—' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total">
                <td style="text-align:right">Total</td>
                <td class="num">{{ number_format($porDependencia->sum('entregas')) }}</td>
                <td class="num">{{ number_format($totalDep) }}</td>
                <td class="num">100%</td>
            </tr>
        </tfoot>
    </table>
    @endif

    {{-- ══ Inventario completo ══ --}}
    @unless($soloReposicion)
    <h2 class="sec">
        Inventario completo
        <span>{{ $elementos->count() }} referencia(s)</span>
    </h2>
    <table class="t">
        <thead>
            <tr>
                <th style="width:65px">Código</th>
                <th>Elemento</th>
                <th style="width:95px">Presentación</th>
                <th class="num" style="width:55px">Stock</th>
                <th class="num" style="width:80px">Consumo</th>
                <th class="num" style="width:90px">Últ. costo</th>
                <th class="num" style="width:100px">Valor al costo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($elementos as $e)
            <tr class="{{ $e->stock <= 0 ? 'agotado' : ($e->stock <= $minimo ? 'bajo' : '') }}">
                <td class="cod">{{ $e->codigo }}</td>
                <td>{{ $e->descripcion }}</td>
                <td class="pres">{{ $e->presentacion ?: '—' }}</td>
                <td class="num"><b>{{ $e->stock }}</b></td>
                <td class="num">{{ $e->consumo }}</td>
                <td class="num">{{ $e->ultimo_costo !== null ? '$' . number_format($e->ultimo_costo, 0) : '—' }}</td>
                <td class="num">${{ number_format($e->valor_costo, 0) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total">
                <td colspan="3" style="text-align:right">Totales</td>
                <td class="num">{{ number_format($resumen['unidades']) }}</td>
                <td class="num">{{ number_format($resumen['consumo']) }}</td>
                <td></td>
                <td class="num">${{ number_format($resumen['valor_costo'], 0) }}</td>
            </tr>
        </tfoot>
    </table>
    @endunless

    {{-- ══ Autorización ══ --}}
    <div class="autoriza">
        <b style="font-size:10.5px;color:#0f766e;text-transform:uppercase;letter-spacing:.04em">Concepto de la administración</b>
        <div class="opts">
            <span><span class="box"></span> Autoriza la compra</span>
            <span><span class="box"></span> Autoriza parcialmente</span>
            <span><span class="box"></span> No autoriza</span>
        </div>
        <div style="font-size:9px;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-top:6px">Observaciones</div>
        <div class="lineas"></div>
        <div class="lineas"></div>
    </div>

    <div class="firmas">
        <div class="firma"><b>&nbsp;</b>Elaboró — Secretaría</div>
        <div class="firma"><b>&nbsp;</b>Vo. Bo. — Administración</div>
        <div class="firma"><b>&nbsp;</b>Autoriza — Rectoría</div>
    </div>

    <div class="pie">
        Colegio Bilingüe Integral · Inventario de aseo (INVCBI) · Documento generado por el Portal CBI el {{ now()->format('d/m/Y \a \l\a\s g:i a') }}
    </div>

</div>
</body>
</html>
