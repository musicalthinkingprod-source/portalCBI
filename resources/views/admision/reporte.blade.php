<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Reporte de Admisión — {{ $evaluacion->aspirante_nombre }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    @page { size: letter; margin: 1.2cm; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11.5px; color: #1a1a1a; background: #fff; padding: 18px; }
    .page { max-width: 800px; margin: 0 auto; }

    .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2.5px solid #1e3a8a; padding-bottom: 8px; margin-bottom: 12px; }
    .header .titulo { font-size: 17px; color: #1e3a8a; font-weight: bold; }
    .header .colegio { font-size: 12px; color: #444; letter-spacing: .5px; }
    .header .sub { font-size: 10px; color: #777; }
    .badge { display: inline-block; font-size: 10px; padding: 3px 10px; border-radius: 999px; font-weight: bold; }

    .datos { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px 14px; margin-bottom: 14px; }
    .datos .f label { display: block; font-size: 8.5px; text-transform: uppercase; color: #888; letter-spacing: .04em; }
    .datos .f span { font-weight: 600; border-bottom: 1px solid #e5e7eb; display: block; padding-bottom: 1px; min-height: 14px; }

    table { width: 100%; border-collapse: collapse; }
    .res th { background: #1e3a8a; color: #fff; font-size: 9.5px; text-transform: uppercase; letter-spacing: .03em; padding: 6px 8px; text-align: left; }
    .res td { padding: 6px 8px; border-bottom: 1px solid #eef1f5; font-size: 11.5px; }
    .res tr:nth-child(even) td { background: #f8fafc; }
    .res .materia { font-weight: 600; }
    .bar { height: 9px; border-radius: 5px; background: #eef1f5; overflow: hidden; width: 120px; display: inline-block; vertical-align: middle; }
    .bar > i { display: block; height: 100%; }
    .pct { display: inline-block; width: 42px; text-align: right; font-weight: 700; }

    .niv { display: inline-block; font-size: 10px; padding: 2px 9px; border-radius: 999px; font-weight: bold; }
    .n-Superior, .n-Alto { background: #dcfce7; color: #15803d; }
    .n-Básico { background: #fef3c7; color: #b45309; }
    .n-Bajo { background: #fee2e2; color: #b91c1c; }

    .total-row td { background: #eef2ff !important; font-weight: bold; border-top: 2px solid #1e3a8a; }

    .mapa { margin-top: 14px; }
    .mapa h3 { font-size: 10px; text-transform: uppercase; color: #64748b; letter-spacing: .05em; margin-bottom: 6px; }
    .mapa .grid { display: flex; flex-wrap: wrap; gap: 3px; }
    .cell { width: 20px; height: 20px; border-radius: 4px; font-size: 9px; font-weight: bold; display: flex; align-items: center; justify-content: center; color: #fff; }
    .c-ok { background: #16a34a; }
    .c-bad { background: #dc2626; }
    .c-none { background: #cbd5e1; color: #64748b; }
    .leyenda { font-size: 9px; color: #64748b; margin-top: 5px; }
    .leyenda b { padding: 1px 5px; border-radius: 3px; color:#fff; }

    .obs { margin-top: 12px; font-size: 10.5px; }
    .obs label { font-size: 8.5px; text-transform: uppercase; color: #888; }
    .obs p { border: 1px solid #e5e7eb; border-radius: 6px; padding: 6px 8px; min-height: 34px; }

    .firmas { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 34px; }
    .firma { border-top: 1px solid #111; padding-top: 4px; text-align: center; font-size: 9.5px; }

    .pie { margin-top: 10px; font-size: 8.5px; color: #94a3b8; text-align: center; }

    .no-print { max-width: 800px; margin: 0 auto 14px; display: flex; gap: 10px; }
    .no-print button { background: #1e3a8a; color: #fff; border: none; padding: 8px 22px; border-radius: 6px; font-size: 13px; cursor: pointer; }
    .no-print a { color: #1e3a8a; font-size: 13px; text-decoration: underline; align-self: center; }
    @media print { .no-print { display: none; } body { padding: 0; } }
</style>
</head>
<body>
@php
    $res = $evaluacion->resultados;
    $tot = $res['total'] ?? [];
    $esMc = $evaluacion->tipo === 'opcion_multiple';
    $fmt = fn($p) => rtrim(rtrim(number_format((float)$p, 1), '0'), '.');
    $barColor = fn($p) => $p >= 70 ? '#16a34a' : ($p >= 50 ? '#f59e0b' : '#dc2626');
@endphp

<div class="no-print">
    <button onclick="window.print()">🖨️ Imprimir</button>
    <a href="{{ route('admision.index') }}">← Volver al listado</a>
    <a href="{{ route('admision.create', ['grado' => $evaluacion->grado_key]) }}">+ Nueva evaluación</a>
</div>

<div class="page">
    <div class="header">
        <div>
            <div class="colegio">COLEGIO BILINGÜE INTEGRAL</div>
            <div class="titulo">Reporte de Examen de Admisión</div>
            <div class="sub">Ingreso a {{ $evaluacion->grado_nombre }}</div>
        </div>
        <div style="text-align:right">
            <span class="badge" style="background:#e0e7ff;color:#3730a3">
                {{ $esMc ? 'Opción múltiple' : 'Valoración cualitativa' }}
            </span>
            <div class="sub" style="margin-top:6px">Emitido: {{ now()->format('d/m/Y') }}</div>
        </div>
    </div>

    <div class="datos">
        <div class="f" style="grid-column: span 2"><label>Aspirante</label><span>{{ $evaluacion->aspirante_nombre }}</span></div>
        <div class="f"><label>Documento</label><span>{{ $evaluacion->aspirante_documento ?: '—' }}</span></div>
        <div class="f"><label>Fecha del examen</label><span>{{ optional($evaluacion->fecha_examen)->format('d/m/Y') ?? '—' }}</span></div>
        <div class="f"><label>Acudiente</label><span>{{ $evaluacion->acudiente ?: '—' }}</span></div>
        <div class="f"><label>Teléfono</label><span>{{ $evaluacion->telefono ?: '—' }}</span></div>
        <div class="f"><label>Evaluó</label><span>{{ $evaluacion->evaluado_por ?: '—' }}</span></div>
        <div class="f"><label>Grado</label><span>{{ $evaluacion->grado_nombre }}</span></div>
    </div>

    {{-- ── Resultados por materia ──────────────────────────────────────── --}}
    <table class="res">
        <thead>
            <tr>
                <th style="width:32%">Materia</th>
                @if($esMc)
                    <th style="width:14%">Aciertos</th>
                @else
                    <th style="width:22%">L / P / N</th>
                @endif
                <th>Desempeño</th>
                <th style="width:15%">Nivel</th>
            </tr>
        </thead>
        <tbody>
            @foreach($res['materias'] as $m)
            <tr>
                <td class="materia">{{ $m['nombre'] }}</td>
                @if($esMc)
                    <td>{{ $m['correctas'] }} / {{ $m['items'] }}</td>
                @else
                    <td>{{ $m['niveles']['L'] }} / {{ $m['niveles']['P'] }} / {{ $m['niveles']['N'] }}</td>
                @endif
                <td>
                    <span class="bar"><i style="width: {{ $m['porcentaje'] }}%; background: {{ $barColor($m['porcentaje']) }}"></i></span>
                    <span class="pct">{{ $fmt($m['porcentaje']) }}%</span>
                </td>
                <td><span class="niv n-{{ $m['nivel'] }}">{{ $m['nivel'] }}</span></td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td>TOTAL</td>
                @if($esMc)
                    <td>{{ $tot['correctas'] }} / {{ $tot['items'] }}</td>
                @else
                    <td>{{ $tot['niveles']['L'] }} / {{ $tot['niveles']['P'] }} / {{ $tot['niveles']['N'] }}</td>
                @endif
                <td>
                    <span class="bar"><i style="width: {{ $tot['porcentaje'] }}%; background: {{ $barColor($tot['porcentaje']) }}"></i></span>
                    <span class="pct">{{ $fmt($tot['porcentaje']) }}%</span>
                </td>
                <td><span class="niv n-{{ $tot['nivel'] }}">{{ $tot['nivel'] }}</span></td>
            </tr>
        </tbody>
    </table>

    {{-- ── Mapa de respuestas (solo opción múltiple) ───────────────────── --}}
    @if($esMc)
    <div class="mapa">
        <h3>Detalle de respuestas</h3>
        <div class="grid">
            @foreach($grado['materias'] as $mat)
                @foreach($mat['preguntas'] as $q)
                    @php
                        $marca = $evaluacion->respuestas[$q['n']] ?? ($evaluacion->respuestas[(string)$q['n']] ?? null);
                        $ok = $marca !== null && strtoupper($marca) === strtoupper($q['correcta']);
                        $cls = $marca === null ? 'c-none' : ($ok ? 'c-ok' : 'c-bad');
                    @endphp
                    <div class="cell {{ $cls }}" title="Pregunta {{ $q['n'] }} · Correcta: {{ $q['correcta'] }}{{ $marca ? ' · Marcó: '.$marca : ' · En blanco' }}">{{ $q['n'] }}</div>
                @endforeach
            @endforeach
        </div>
        <div class="leyenda">
            <b class="c-ok">&nbsp;</b> Correcta &nbsp;&nbsp;
            <b class="c-bad">&nbsp;</b> Incorrecta &nbsp;&nbsp;
            <b class="c-none">&nbsp;</b> En blanco
        </div>
    </div>
    @endif

    @if($evaluacion->observaciones)
    <div class="obs">
        <label>Observaciones</label>
        <p>{{ $evaluacion->observaciones }}</p>
    </div>
    @endif

    <div class="firmas">
        <div class="firma">{{ $evaluacion->evaluado_por ?: 'Evaluador(a)' }}<br><span style="color:#888">Aplicó y calificó</span></div>
        <div class="firma">Coordinación Académica<br><span style="color:#888">Revisó</span></div>
    </div>

    <div class="pie">Documento generado por el Portal CBI · {{ now()->format('d/m/Y H:i') }}</div>
</div>
</body>
</html>
