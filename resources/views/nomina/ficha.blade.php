<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha de Empleado — {{ $emp->nombre }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 11px;
            color: #1a1a2e;
            background: #fff;
            padding: 20px;
        }

        /* ── Encabezado ── */
        .header {
            display: flex;
            align-items: center;
            gap: 16px;
            border-bottom: 3px solid #1e40af;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .logo-placeholder {
            width: 70px;
            height: 70px;
            background: #1e40af;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 20px;
            flex-shrink: 0;
        }
        .header-info h1 {
            font-size: 18px;
            font-weight: 700;
            color: #1e40af;
        }
        .header-info p {
            color: #6b7280;
            font-size: 11px;
        }
        .header-badge {
            margin-left: auto;
            text-align: right;
        }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
        }
        .badge-activo   { background: #dcfce7; color: #15803d; }
        .badge-inactivo { background: #fee2e2; color: #b91c1c; }

        /* ── Secciones ── */
        .section {
            margin-bottom: 14px;
        }
        .section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #1e40af;
            background: #eff6ff;
            padding: 4px 8px;
            border-left: 4px solid #1e40af;
            margin-bottom: 6px;
        }

        /* ── Grid de campos ── */
        .grid { display: grid; gap: 4px 12px; }
        .grid-2 { grid-template-columns: 1fr 1fr; }
        .grid-3 { grid-template-columns: 1fr 1fr 1fr; }
        .grid-4 { grid-template-columns: 1fr 1fr 1fr 1fr; }

        .field { }
        .field-label {
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            color: #9ca3af;
            margin-bottom: 1px;
        }
        .field-value {
            font-size: 11px;
            color: #1f2937;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 2px;
            min-height: 14px;
        }

        /* ── Tablas ── */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        th {
            background: #1e40af;
            color: #fff;
            padding: 4px 6px;
            text-align: left;
            font-weight: 600;
        }
        td {
            padding: 3px 6px;
            border-bottom: 1px solid #e5e7eb;
            color: #374151;
        }
        tr:nth-child(even) td { background: #f8fafc; }

        /* ── Inducción ── */
        .induccion-section { margin-bottom: 10px; }
        .induccion-section-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #4b5563;
            margin-bottom: 4px;
            padding-bottom: 2px;
            border-bottom: 1px solid #d1d5db;
        }
        .induccion-item {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            padding: 3px 4px;
            border-bottom: 1px dashed #f3f4f6;
        }
        .check-box {
            width: 12px;
            height: 12px;
            border: 1.5px solid #9ca3af;
            border-radius: 2px;
            flex-shrink: 0;
            margin-top: 1px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .check-box.done { background: #15803d; border-color: #15803d; }
        .check-box.done::after { content: '✓'; color: #fff; font-size: 8px; line-height: 1; }
        .induccion-nombre { flex: 1; }
        .induccion-meta { font-size: 9px; color: #9ca3af; text-align: right; white-space: nowrap; }

        /* ── Nomina resumen ── */
        .nomina-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
        }
        .nomina-card {
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            padding: 6px 8px;
            text-align: center;
        }
        .nomina-card-label { font-size: 9px; color: #6b7280; }
        .nomina-card-value { font-size: 13px; font-weight: 700; color: #1e40af; }

        /* ── Pie ── */
        .footer {
            margin-top: 20px;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 9px;
            color: #9ca3af;
        }

        .firma-box {
            text-align: center;
        }
        .firma-line {
            border-top: 1px solid #374151;
            width: 160px;
            margin: 20px auto 4px;
        }

        @media print {
            body { padding: 10px; }
            @page { margin: 15mm; }
        }
        .no-print { display: none !important; }
    </style>
</head>
<body>

{{-- Botón imprimir (solo pantalla) --}}
<div style="text-align:right;margin-bottom:12px;" class="no-print">
    <button onclick="window.print()"
        style="background:#1e40af;color:#fff;border:none;padding:8px 20px;border-radius:6px;font-size:12px;cursor:pointer;font-weight:600;">
        Imprimir / Guardar PDF
    </button>
    <button onclick="window.close()"
        style="background:#6b7280;color:#fff;border:none;padding:8px 16px;border-radius:6px;font-size:12px;cursor:pointer;margin-left:6px;">
        Cerrar
    </button>
</div>

{{-- ══════════════════════════════════════════════
     ENCABEZADO
═══════════════════════════════════════════════ --}}
<div class="header">
    <div class="logo-placeholder">CBI</div>
    <div class="header-info">
        <h1>Ficha de Personal</h1>
        <p>Colegio Bilingüe Integral — Gestión de Nómina</p>
        <p style="margin-top:2px;color:#374151;font-weight:600;font-size:13px;">{{ $emp->nombre }}</p>
        @if($emp->cargo)
        <p style="color:#6b7280;">{{ ucwords(str_replace('_',' ',$emp->cargo)) }}</p>
        @endif
    </div>
    <div class="header-badge">
        @php
            $activo = !$emp->fecha_fin_contrato || \Carbon\Carbon::parse($emp->fecha_fin_contrato)->gte(\Carbon\Carbon::today());
        @endphp
        <span class="badge {{ $activo ? 'badge-activo' : 'badge-inactivo' }}">
            {{ $activo ? 'Activo' : 'Inactivo' }}
        </span>
        <div style="margin-top:6px;font-size:9px;color:#6b7280;">
            Generado: {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════
     DATOS PERSONALES
═══════════════════════════════════════════════ --}}
<div class="section">
    <div class="section-title">Datos Personales e Identificación</div>
    <div class="grid grid-4" style="margin-bottom:6px;">
        <div class="field">
            <div class="field-label">Cédula</div>
            <div class="field-value">{{ $emp->cedula }}{{ $emp->ciudad_identificacion ? ' de ' . $emp->ciudad_identificacion : '' }}</div>
        </div>
        <div class="field">
            <div class="field-label">Fecha de nacimiento</div>
            <div class="field-value">{{ $emp->fecha_nacimiento ? \Carbon\Carbon::parse($emp->fecha_nacimiento)->format('d/m/Y') : '—' }}</div>
        </div>
        <div class="field">
            <div class="field-label">Tipo de sangre</div>
            <div class="field-value">{{ $emp->tipo_sangre ?: '—' }}</div>
        </div>
        <div class="field">
            <div class="field-label">Nº de hijos</div>
            <div class="field-value">{{ $emp->numero_hijos ?? '—' }}</div>
        </div>
    </div>
    <div class="grid grid-3">
        <div class="field">
            <div class="field-label">Correo electrónico</div>
            <div class="field-value">{{ $emp->correo ?: '—' }}</div>
        </div>
        <div class="field">
            <div class="field-label">Teléfono</div>
            <div class="field-value">{{ $emp->telefono ?: '—' }}</div>
        </div>
        <div class="field">
            <div class="field-label">Dirección</div>
            <div class="field-value">{{ $emp->direccion ?: '—' }}</div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════
     CONTRATO
═══════════════════════════════════════════════ --}}
<div class="section">
    <div class="section-title">Información Contractual</div>
    <div class="grid grid-4">
        <div class="field">
            <div class="field-label">Cargo</div>
            <div class="field-value">{{ $emp->cargo ?: '—' }}</div>
        </div>
        <div class="field">
            <div class="field-label">Tipo de empleado</div>
            <div class="field-value">{{ $emp->tipo_empleado ? ucwords(str_replace('_',' ',$emp->tipo_empleado)) : '—' }}</div>
        </div>
        <div class="field">
            <div class="field-label">Fecha de ingreso</div>
            <div class="field-value">{{ $emp->fecha_ingreso ? \Carbon\Carbon::parse($emp->fecha_ingreso)->format('d/m/Y') : '—' }}</div>
        </div>
        <div class="field">
            <div class="field-label">Fecha fin de contrato</div>
            <div class="field-value">{{ $emp->fecha_fin_contrato ? \Carbon\Carbon::parse($emp->fecha_fin_contrato)->format('d/m/Y') : 'Indefinido' }}</div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════
     SEGURIDAD SOCIAL + NÓMINA
═══════════════════════════════════════════════ --}}
<div class="section">
    <div class="section-title">Seguridad Social y Nómina</div>
    <div class="grid grid-3" style="margin-bottom:8px;">
        <div class="field">
            <div class="field-label">EPS</div>
            <div class="field-value">{{ $emp->eps ?: '—' }}</div>
        </div>
        <div class="field">
            <div class="field-label">Fondo de pensión</div>
            <div class="field-value">{{ $emp->fondo_pension ?: '—' }}</div>
        </div>
        <div class="field">
            <div class="field-label">ARL</div>
            <div class="field-value">{{ $emp->fondo_arl ?: '—' }}</div>
        </div>
        <div class="field">
            <div class="field-label">Caja de compensación</div>
            <div class="field-value">{{ $emp->caja_compensacion ?: '—' }}</div>
        </div>
        <div class="field">
            <div class="field-label">Sueldo básico</div>
            <div class="field-value" style="font-weight:700;color:#1e40af;">
                {{ $emp->sueldo_basico ? '$' . number_format($emp->sueldo_basico, 0, ',', '.') : '—' }}
            </div>
        </div>
        @if($emp->codigo_doc)
        <div class="field">
            <div class="field-label">Código docente</div>
            <div class="field-value">{{ $emp->codigo_doc }}</div>
        </div>
        @endif
    </div>

    @if($emp->sueldo_basico)
    @php
        $sueldo      = (float) $emp->sueldo_basico;
        $smmlv2026   = 1423500;
        $auxTrans    = $sueldo <= 2 * $smmlv2026 ? 200000 : 0;
        $salud       = round($sueldo * 0.04);
        $pension     = round($sueldo * 0.04);
        $totalDeds   = $salud + $pension;
        $neto        = $sueldo + $auxTrans - $totalDeds;
    @endphp
    <div class="nomina-grid">
        <div class="nomina-card">
            <div class="nomina-card-label">Sueldo básico</div>
            <div class="nomina-card-value">${{ number_format($sueldo, 0, ',', '.') }}</div>
        </div>
        <div class="nomina-card">
            <div class="nomina-card-label">Aux. transporte</div>
            <div class="nomina-card-value">${{ number_format($auxTrans, 0, ',', '.') }}</div>
        </div>
        <div class="nomina-card">
            <div class="nomina-card-label">Salud (4%)</div>
            <div class="nomina-card-value" style="color:#b91c1c;">-${{ number_format($salud, 0, ',', '.') }}</div>
        </div>
        <div class="nomina-card">
            <div class="nomina-card-label">Pensión (4%)</div>
            <div class="nomina-card-value" style="color:#b91c1c;">-${{ number_format($pension, 0, ',', '.') }}</div>
        </div>
        <div class="nomina-card" style="grid-column:span 2;border-color:#1e40af;">
            <div class="nomina-card-label">Neto estimado a pagar</div>
            <div class="nomina-card-value" style="font-size:16px;">${{ number_format($neto, 0, ',', '.') }}</div>
        </div>
    </div>
    @endif
</div>

{{-- ══════════════════════════════════════════════
     CONTACTO DE EMERGENCIA
═══════════════════════════════════════════════ --}}
@if($emp->contacto_emergencia || $emp->tel_emergencia)
<div class="section">
    <div class="section-title">Contacto de Emergencia</div>
    <div class="grid grid-2">
        <div class="field">
            <div class="field-label">Nombre</div>
            <div class="field-value">{{ $emp->contacto_emergencia ?: '—' }}</div>
        </div>
        <div class="field">
            <div class="field-label">Teléfono</div>
            <div class="field-value">{{ $emp->tel_emergencia ?: '—' }}</div>
        </div>
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════
     VACACIONES
═══════════════════════════════════════════════ --}}
@if($vacaciones->count())
<div class="section">
    <div class="section-title">Vacaciones</div>
    <table>
        <thead>
            <tr>
                <th>Año</th>
                <th>Período</th>
                <th>Fecha inicio</th>
                <th>Fecha fin</th>
                <th style="text-align:right;">Días tomados</th>
                <th style="text-align:right;">Días corresponden</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vacaciones as $vac)
            <tr>
                <td>{{ $vac->anio }}</td>
                <td>{{ $vac->periodo }}</td>
                <td>{{ $vac->fecha_inicio ? \Carbon\Carbon::parse($vac->fecha_inicio)->format('d/m/Y') : '—' }}</td>
                <td>{{ $vac->fecha_fin ? \Carbon\Carbon::parse($vac->fecha_fin)->format('d/m/Y') : '—' }}</td>
                <td style="text-align:right;">{{ number_format($vac->dias_tomados, 1) }}</td>
                <td style="text-align:right;">{{ $vac->dias_corresponden !== null ? number_format($vac->dias_corresponden, 1) : '—' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align:right;font-weight:700;color:#4b5563;">Total días tomados:</td>
                <td style="text-align:right;font-weight:700;color:#1e40af;">{{ number_format($vacaciones->sum('dias_tomados'), 1) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>
@endif

{{-- ══════════════════════════════════════════════
     INCAPACIDADES
═══════════════════════════════════════════════ --}}
@if($incapacidades->count())
<div class="section">
    <div class="section-title">Incapacidades ({{ $incapacidades->count() }} registros)</div>
    <table>
        <thead>
            <tr>
                <th>Año</th>
                <th>Mes</th>
                <th>Fecha inicio</th>
                <th>Fecha fin</th>
                <th style="text-align:right;">Días</th>
                <th>EPS</th>
                <th>Estado EPS</th>
                <th style="text-align:right;">Valor pagado</th>
                <th style="text-align:right;">Pendiente</th>
            </tr>
        </thead>
        <tbody>
            @foreach($incapacidades as $inc)
            <tr>
                <td>{{ $inc->anio }}</td>
                <td>{{ $inc->mes ?: '—' }}</td>
                <td>{{ $inc->fecha_inicio ? \Carbon\Carbon::parse($inc->fecha_inicio)->format('d/m/Y') : '—' }}</td>
                <td>{{ $inc->fecha_fin ? \Carbon\Carbon::parse($inc->fecha_fin)->format('d/m/Y') : '—' }}</td>
                <td style="text-align:right;">{{ number_format($inc->dias_incapacidad, 1) }}</td>
                <td>{{ $inc->eps ?: '—' }}</td>
                <td>
                    @if($inc->estado_eps === 'radicada')
                        <span style="color:#15803d;font-weight:600;">Radicada</span>
                    @elseif($inc->estado_eps === 'no_se_radica')
                        <span style="color:#6b7280;">No radica</span>
                    @else
                        <span style="color:#d97706;font-weight:600;">Pendiente</span>
                    @endif
                </td>
                <td style="text-align:right;">{{ $inc->valor_pagado_eps ? '$' . number_format($inc->valor_pagado_eps, 0, ',', '.') : '—' }}</td>
                <td style="text-align:right;{{ $inc->valor_pendiente ? 'color:#b91c1c;font-weight:600;' : '' }}">
                    {{ $inc->valor_pendiente ? '$' . number_format($inc->valor_pendiente, 0, ',', '.') : '—' }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align:right;font-weight:700;color:#4b5563;">Totales:</td>
                <td style="text-align:right;font-weight:700;color:#1e40af;">{{ number_format($incapacidades->sum('dias_incapacidad'), 1) }}</td>
                <td colspan="2"></td>
                <td style="text-align:right;font-weight:700;color:#15803d;">
                    ${{ number_format($incapacidades->sum('valor_pagado_eps'), 0, ',', '.') }}
                </td>
                <td style="text-align:right;font-weight:700;color:#b91c1c;">
                    {{ $incapacidades->sum('valor_pendiente') ? '$' . number_format($incapacidades->sum('valor_pendiente'), 0, ',', '.') : '—' }}
                </td>
            </tr>
        </tfoot>
    </table>
</div>
@endif

{{-- ══════════════════════════════════════════════
     INDUCCIÓN
═══════════════════════════════════════════════ --}}
@if($items->count())
@php
    $itemsGrupo   = $items->groupBy('seccion');
    $nombreSeccion = ['legal'=>'Legal','administrativa'=>'Administrativa','academica'=>'Académica','convivencial'=>'Convivencial'];
    $completados  = $induccion->filter(fn($r) => $r->completado)->count();
    $total        = $items->count();
@endphp
<div class="section">
    <div class="section-title">
        Proceso de Inducción
        <span style="float:right;font-weight:400;font-size:10px;">
            {{ $completados }}/{{ $total }} ítems completados
            ({{ $total > 0 ? round($completados / $total * 100) : 0 }}%)
        </span>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
    @foreach($itemsGrupo as $seccion => $secItems)
    <div class="induccion-section">
        <div class="induccion-section-title">{{ $nombreSeccion[$seccion] ?? ucfirst($seccion) }}</div>
        @foreach($secItems as $item)
        @php
            $reg  = $induccion->get($item->id);
            $done = $reg && $reg->completado;
        @endphp
        <div class="induccion-item">
            <div class="check-box {{ $done ? 'done' : '' }}"></div>
            <div class="induccion-nombre">{{ $item->nombre }}</div>
            @if($done && $reg->fecha_completado)
            <div class="induccion-meta">{{ \Carbon\Carbon::parse($reg->fecha_completado)->format('d/m/Y') }}</div>
            @endif
        </div>
        @endforeach
    </div>
    @endforeach
    </div>
</div>
@endif

{{-- ══════════════════════════════════════════════
     PIE DE PÁGINA
═══════════════════════════════════════════════ --}}
<div class="footer">
    <div>
        <div>Colegio Bilingüe Integral — Portal CBI</div>
        <div>Gestión de Nómina y Personal</div>
    </div>

    <div style="display:flex;gap:40px;">
        <div class="firma-box">
            <div class="firma-line"></div>
            <div style="font-size:9px;font-weight:600;">Coordinador(a) de Nómina</div>
        </div>
        <div class="firma-box">
            <div class="firma-line"></div>
            <div style="font-size:9px;font-weight:600;">Empleado(a)</div>
        </div>
    </div>

    <div style="text-align:right;">
        <div>Documento generado el {{ now()->format('d/m/Y') }}</div>
        <div>C.C. {{ $emp->cedula }}</div>
    </div>
</div>

</body>
</html>
