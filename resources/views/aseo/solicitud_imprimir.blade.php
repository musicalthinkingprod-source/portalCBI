<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Solicitud de compra de aseo N° {{ $sol->numero }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    @page { size: letter portrait; margin: 1.1cm; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #1a1a1a; background: #f1f5f9; padding: 18px; }
    .page { max-width: 880px; margin: 0 auto; background: #fff; padding: 22px 26px; box-shadow: 0 1px 10px rgba(0,0,0,.08); }

    .header { display: flex; align-items: center; gap: 14px; border-bottom: 2.5px solid #0f766e; padding-bottom: 9px; margin-bottom: 14px; }
    .header img { height: 52px; }
    .header .colegio { font-size: 12px; color: #444; letter-spacing: .5px; font-weight: bold; }
    .header .titulo { font-size: 17px; color: #0f766e; font-weight: bold; }
    .header .sub { font-size: 10px; color: #777; }
    .header .meta { margin-left: auto; text-align: right; font-size: 9.5px; color: #666; line-height: 1.5; }
    .header .numero { font-size: 20px; font-weight: bold; color: #0f766e; }

    h2.sec { font-size: 11px; background: #0f766e; color: #fff; padding: 4px 9px; margin: 16px 0 8px; border-radius: 3px; text-transform: uppercase; letter-spacing: .04em; }

    .datos { display: grid; grid-template-columns: repeat(4, 1fr); gap: 7px 16px; }
    .datos .f label { display: block; font-size: 8px; text-transform: uppercase; color: #888; letter-spacing: .04em; }
    .datos .f span { font-weight: 600; border-bottom: 1px solid #e5e7eb; display: block; padding-bottom: 2px; min-height: 14px; word-wrap: break-word; }
    .datos .w2 { grid-column: span 2; }
    .datos .w4 { grid-column: span 4; }

    table.t { width: 100%; border-collapse: collapse; }
    table.t thead { display: table-header-group; }
    table.t th { background: #ecfdf5; color: #0f766e; font-size: 8.5px; text-transform: uppercase; letter-spacing: .03em; padding: 5px 7px; text-align: left; border-bottom: 1.2px solid #a7f3d0; }
    table.t td { padding: 4px 7px; border-bottom: 1px solid #eef1f5; font-size: 10.5px; }
    table.t tr { page-break-inside: avoid; }
    .num { text-align: right; font-variant-numeric: tabular-nums; }
    .cod { font-family: 'Courier New', monospace; color: #475569; }
    .pres { color: #64748b; font-size: 9.5px; }
    tr.total td { background: #ecfdf5; font-weight: bold; border-top: 1.5px solid #0f766e; border-bottom: none; }
    tr.cero td { color: #94a3b8; text-decoration: line-through; }

    .estado { display: inline-block; font-size: 10px; padding: 3px 12px; border-radius: 999px; font-weight: bold; }
    .e-pendiente { background: #fef3c7; color: #b45309; }
    .e-aprobada  { background: #dcfce7; color: #15803d; }
    .e-rechazada { background: #fee2e2; color: #b91c1c; }
    .e-comprada  { background: #dbeafe; color: #1d4ed8; }
    .e-anulada   { background: #e2e8f0; color: #475569; }

    .autoriza { border: 1.5px dashed #94a3b8; border-radius: 7px; padding: 10px 12px; margin-top: 18px; page-break-inside: avoid; }
    .autoriza .opts { display: flex; gap: 22px; margin: 7px 0 4px; font-size: 10.5px; }
    .autoriza .box { width: 12px; height: 12px; border: 1.2px solid #475569; display: inline-block; border-radius: 2px; vertical-align: -1px; margin-right: 5px; }
    .autoriza .box.on { background: #0f766e; border-color: #0f766e; }
    .autoriza .lineas { border-bottom: 1px solid #cbd5e1; height: 16px; margin-top: 8px; }

    .firmas { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 32px; margin-top: 46px; page-break-inside: avoid; }
    .firma { border-top: 1px solid #111; padding-top: 4px; text-align: center; font-size: 9px; color: #334155; }
    .firma b { display: block; font-size: 9.5px; color: #111; min-height: 12px; }

    .pie { margin-top: 12px; font-size: 8.5px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 6px; }

    .no-print { max-width: 880px; margin: 0 auto 14px; display: flex; gap: 9px; align-items: center; }
    .no-print button, .no-print a { background: #0f766e; color: #fff; border: none; padding: 8px 20px; border-radius: 6px; font-size: 13px; cursor: pointer; text-decoration: none; }
    .no-print a.sec { background: #64748b; }

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
    <a href="{{ route('aseo.solicitudes.ver', $sol->id) }}" class="sec">← Volver a la solicitud</a>
</div>

@php
    $totalUnd = $items->sum('cantidad_final');
    $totalVal = $items->sum('subtotal');
    $etiqueta = [
        'pendiente' => 'Pendiente de autorización',
        'aprobada'  => 'Aprobada',
        'rechazada' => 'Rechazada',
        'comprada'  => 'Comprada',
        'anulada'   => 'Anulada',
    ][$sol->estado] ?? $sol->estado;
@endphp

<div class="page">

    <div class="header">
        <img src="{{ asset('images/escudoCBI.png') }}" alt="CBI">
        <div>
            <div class="colegio">COLEGIO BILINGÜE INTEGRAL</div>
            <div class="titulo">Solicitud de Compra de Aseo</div>
            <div class="sub">Inventario de aseo e insumos — INVCBI</div>
        </div>
        <div class="meta">
            <div class="numero">N° {{ $sol->numero }}</div>
            <div><b>Fecha:</b> {{ \Carbon\Carbon::parse($sol->fecha)->format('d/m/Y') }}</div>
            <div><span class="estado e-{{ $sol->estado }}">{{ $etiqueta }}</span></div>
        </div>
    </div>

    <h2 class="sec">Datos de la solicitud</h2>
    <div class="datos">
        <div class="f"><label>Solicitada por</label><span>{{ $sol->solicitado_por ?? '—' }}</span></div>
        <div class="f"><label>Proveedor sugerido</label><span>{{ $sol->proveedor ?? '—' }}</span></div>
        <div class="f"><label>Elementos</label><span>{{ $items->count() }}</span></div>
        <div class="f"><label>Unidades</label><span>{{ number_format($totalUnd) }}</span></div>
        <div class="f w4"><label>Justificación</label><span>{{ $sol->justificacion ?: '—' }}</span></div>
        @if($sol->autorizado_at)
        <div class="f"><label>Autorizada por</label><span>{{ $sol->autorizado_por ?? '—' }}</span></div>
        <div class="f"><label>Fecha de autorización</label><span>{{ \Carbon\Carbon::parse($sol->autorizado_at)->format('d/m/Y g:i a') }}</span></div>
        <div class="f w2"><label>Nota de la administración</label><span>{{ $sol->nota_autorizacion ?: '—' }}</span></div>
        @endif
        @if($sol->estado === 'comprada')
        <div class="f"><label>Documento</label><span>{{ $sol->documento ?: '—' }}</span></div>
        <div class="f"><label>Fecha de compra</label><span>{{ $sol->compra_fecha ? \Carbon\Carbon::parse($sol->compra_fecha)->format('d/m/Y') : '—' }}</span></div>
        <div class="f"><label>Total facturado</label><span>{{ $sol->compra_total !== null ? '$' . number_format($sol->compra_total, 0) : '—' }}</span></div>
        <div class="f"><label>Registrada por</label><span>{{ $sol->comprado_por ?? '—' }}</span></div>
        @endif
    </div>

    <h2 class="sec">Elementos solicitados</h2>
    <table class="t">
        <thead>
            <tr>
                <th style="width:65px">Código</th>
                <th>Elemento</th>
                <th style="width:85px">Presentación</th>
                <th class="num" style="width:55px">Stock</th>
                <th class="num" style="width:65px">Consumo</th>
                <th class="num" style="width:70px">Solicitado</th>
                <th class="num" style="width:75px">Autorizado</th>
                <th class="num" style="width:80px">Costo ref.</th>
                <th class="num" style="width:88px">Estimado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $it)
            <tr class="{{ $it->cantidad_final <= 0 ? 'cero' : '' }}">
                <td class="cod">{{ $it->codigo }}</td>
                <td>{{ $it->descripcion }}</td>
                <td class="pres">{{ $it->presentacion ?: '—' }}</td>
                <td class="num">{{ $it->stock_actual }}</td>
                <td class="num">{{ $it->consumo_ref }}</td>
                <td class="num">{{ $it->cantidad }}</td>
                <td class="num"><b>{{ $sol->estado === 'pendiente' ? '—' : $it->cantidad_final }}</b></td>
                <td class="num">{{ $it->costo_ref !== null ? '$' . number_format($it->costo_ref, 0) : '—' }}</td>
                <td class="num">{{ $it->costo_ref !== null ? '$' . number_format($it->subtotal, 0) : '—' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total">
                <td colspan="5" style="text-align:right">Totales</td>
                <td class="num">{{ number_format($items->sum('cantidad')) }}</td>
                <td class="num">{{ $sol->estado === 'pendiente' ? '—' : number_format($totalUnd) }}</td>
                <td></td>
                <td class="num">${{ number_format($totalVal, 0) }}</td>
            </tr>
        </tfoot>
    </table>
    <p style="font-size:9.5px;color:#64748b;margin-top:6px;line-height:1.45">
        El <b>costo de referencia</b> es el último precio de compra registrado de cada elemento, y el <b>consumo</b> corresponde a lo
        entregado a las dependencias en el período analizado al momento de la solicitud. El valor definitivo se establece con la
        cotización y el documento del proveedor.
    </p>

    <div class="autoriza">
        <b style="font-size:10.5px;color:#0f766e;text-transform:uppercase;letter-spacing:.04em">Autorización de compra</b>
        <div class="opts">
            <span><span class="box {{ $sol->estado === 'aprobada' || $sol->estado === 'comprada' ? 'on' : '' }}"></span> Autoriza</span>
            <span><span class="box"></span> Autoriza parcialmente</span>
            <span><span class="box {{ $sol->estado === 'rechazada' ? 'on' : '' }}"></span> No autoriza</span>
        </div>
        <div style="font-size:9px;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-top:6px">Observaciones</div>
        <div class="lineas"></div>
        <div class="lineas"></div>
    </div>

    <div class="firmas">
        <div class="firma"><b>{{ $sol->solicitado_por ?? '' }}</b>Solicita — Secretaría</div>
        <div class="firma"><b>{{ $sol->autorizado_por ?? '' }}</b>Autoriza — Administración</div>
        <div class="firma"><b>&nbsp;</b>Recibido — Proveedor</div>
    </div>

    <div class="pie">
        Colegio Bilingüe Integral · Inventario de aseo (INVCBI) · Documento generado por el Portal CBI el {{ now()->format('d/m/Y \a \l\a\s g:i a') }}
    </div>

</div>
</body>
</html>
