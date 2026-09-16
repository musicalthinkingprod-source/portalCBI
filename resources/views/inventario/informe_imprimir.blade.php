<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Informe de inventario de uniformes — {{ now()->format('d/m/Y') }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    @page { size: letter portrait; margin: 1.1cm; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #1a1a1a; background: #f1f5f9; padding: 18px; }
    .page { max-width: 880px; margin: 0 auto; background: #fff; padding: 22px 26px; box-shadow: 0 1px 10px rgba(0,0,0,.08); }

    /* ── Encabezado ── */
    .header { display: flex; align-items: center; gap: 14px; border-bottom: 2.5px solid #1e3a8a; padding-bottom: 9px; margin-bottom: 14px; }
    .header img { height: 52px; }
    .header .colegio { font-size: 12px; color: #444; letter-spacing: .5px; font-weight: bold; }
    .header .titulo { font-size: 17px; color: #1e3a8a; font-weight: bold; }
    .header .sub { font-size: 10px; color: #777; }
    .header .meta { margin-left: auto; text-align: right; font-size: 9.5px; color: #666; line-height: 1.5; }

    h2.sec { font-size: 11px; background: #1e3a8a; color: #fff; padding: 4px 9px; margin: 16px 0 8px; border-radius: 3px; text-transform: uppercase; letter-spacing: .04em; }
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
    table.t th { background: #eef2ff; color: #1e3a8a; font-size: 8.5px; text-transform: uppercase; letter-spacing: .03em; padding: 5px 7px; text-align: left; border-bottom: 1.2px solid #c7d2fe; }
    table.t td { padding: 4px 7px; border-bottom: 1px solid #eef1f5; font-size: 10.5px; }
    table.t tr { page-break-inside: avoid; }
    .num { text-align: right; font-variant-numeric: tabular-nums; }
    .cod { font-family: 'Courier New', monospace; color: #475569; }
    .agotado td { background: #fef2f2; }
    .bajo td { background: #fffbeb; }
    tr.total td { background: #eef2ff; font-weight: bold; border-top: 1.5px solid #1e3a8a; border-bottom: none; }

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
    .no-print button, .no-print a { background: #1e3a8a; color: #fff; border: none; padding: 8px 20px; border-radius: 6px; font-size: 13px; cursor: pointer; text-decoration: none; display: inline-block; }
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
    <a href="{{ route('inventario.solicitudes.create', ['minimo' => $minimo, 'objetivo' => $objetivo]) }}" class="ok">🛒 Solicitar compra</a>
    <a href="{{ route('inventario.dashboard', ['minimo' => $minimo, 'objetivo' => $objetivo]) }}" class="sec">← Volver al informe</a>
    <span class="hint">Mínimo: {{ $minimo }} · Objetivo por referencia: {{ $objetivo }}</span>
</div>

<div class="page">

    {{-- ══ Encabezado ══ --}}
    <div class="header">
        <img src="{{ asset('images/escudoCBI.png') }}" alt="CBI">
        <div>
            <div class="colegio">COLEGIO BILINGÜE INTEGRAL</div>
            <div class="titulo">Informe de Inventario de Uniformes</div>
            <div class="sub">Existencias, referencias por reponer y costo estimado de reposición</div>
        </div>
        <div class="meta">
            <div><b>Emitido:</b> {{ now()->format('d/m/Y g:i a') }}</div>
            <div><b>Elaboró:</b> {{ auth()->user()->USER ?? '—' }}</div>
            <div><b>Criterio:</b> stock mínimo {{ $minimo }} · objetivo {{ $objetivo }}</div>
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
            <div class="big">${{ number_format($resumen['valor_venta'], 0) }}</div>
            <div class="lbl">Valor a precio venta</div>
        </div>
        <div class="card">
            <div class="big">${{ number_format($resumen['valor_costo'], 0) }}</div>
            <div class="lbl">Valor al costo</div>
        </div>
    </div>

    {{-- ══ Por reponer ══ --}}
    <h2 class="sec">
        Referencias por reponer
        <span>{{ $porComprar->count() }} referencia(s) · costo estimado ${{ number_format($resumen['costo_reposicion'], 0) }}</span>
    </h2>
    <table class="t">
        <thead>
            <tr>
                <th style="width:70px">Código</th>
                <th>Producto</th>
                <th class="num" style="width:55px">Stock</th>
                <th style="width:75px">Estado</th>
                <th class="num" style="width:80px">Sugerido</th>
                <th class="num" style="width:90px">Último costo</th>
                <th class="num" style="width:100px">Costo estimado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($porComprar as $p)
            <tr class="{{ $p->stock <= 0 ? 'agotado' : ($p->stock <= $minimo ? 'bajo' : '') }}">
                <td class="cod">{{ $p->codigo }}</td>
                <td>{{ $p->nombre }}</td>
                <td class="num"><b>{{ $p->stock }}</b></td>
                <td>
                    @if($p->stock <= 0)      <span class="pill p-ago">Agotado</span>
                    @elseif($p->stock <= $minimo) <span class="pill p-baj">Bajo</span>
                    @else                    <span class="pill p-ok">Normal</span>
                    @endif
                </td>
                <td class="num"><b>{{ $p->sugerido }}</b></td>
                <td class="num">{{ $p->ultimo_costo !== null ? '$' . number_format($p->ultimo_costo, 0) : '—' }}</td>
                <td class="num">{{ $p->ultimo_costo !== null ? '$' . number_format($p->sugerido * $p->ultimo_costo, 0) : '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:14px">No hay referencias por reponer.</td></tr>
            @endforelse
        </tbody>
        @if($porComprar->count())
        <tfoot>
            <tr class="total">
                <td colspan="4" style="text-align:right">Totales</td>
                <td class="num">{{ number_format($porComprar->sum('sugerido')) }}</td>
                <td></td>
                <td class="num">${{ number_format($resumen['costo_reposicion'], 0) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
    <p class="nota">
        La <b>cantidad sugerida</b> es la diferencia entre el objetivo de {{ $objetivo }} unidades por referencia y las existencias actuales.
        El <b>costo estimado</b> usa el último precio de compra registrado; las referencias sin compras previas aparecen sin costo.
    </p>

    {{-- ══ Inventario completo ══ --}}
    @unless($soloReposicion)
    <h2 class="sec">
        Inventario completo
        <span>{{ $productos->count() }} referencia(s)</span>
    </h2>
    <table class="t">
        <thead>
            <tr>
                <th style="width:70px">Código</th>
                <th>Producto</th>
                <th class="num" style="width:55px">Stock</th>
                <th class="num" style="width:95px">Precio venta</th>
                <th class="num" style="width:100px">Valor en stock</th>
                <th class="num" style="width:90px">Último costo</th>
                <th class="num" style="width:100px">Valor al costo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($productos as $p)
            <tr class="{{ $p->stock <= 0 ? 'agotado' : ($p->stock <= $minimo ? 'bajo' : '') }}">
                <td class="cod">{{ $p->codigo }}</td>
                <td>{{ $p->nombre }}</td>
                <td class="num"><b>{{ $p->stock }}</b></td>
                <td class="num">${{ number_format($p->precio_venta, 0) }}</td>
                <td class="num">${{ number_format($p->valor_venta, 0) }}</td>
                <td class="num">{{ $p->ultimo_costo !== null ? '$' . number_format($p->ultimo_costo, 0) : '—' }}</td>
                <td class="num">${{ number_format($p->valor_costo, 0) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total">
                <td colspan="2" style="text-align:right">Totales</td>
                <td class="num">{{ number_format($resumen['unidades']) }}</td>
                <td></td>
                <td class="num">${{ number_format($resumen['valor_venta'], 0) }}</td>
                <td></td>
                <td class="num">${{ number_format($resumen['valor_costo'], 0) }}</td>
            </tr>
        </tfoot>
    </table>
    @endunless

    {{-- ══ Autorización ══ --}}
    <div class="autoriza">
        <b style="font-size:10.5px;color:#1e3a8a;text-transform:uppercase;letter-spacing:.04em">Concepto de la administración</b>
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
        Colegio Bilingüe Integral · Inventario de uniformes (INVCBI) · Documento generado por el Portal CBI el {{ now()->format('d/m/Y \a \l\a\s g:i a') }}
    </div>

</div>
</body>
</html>
