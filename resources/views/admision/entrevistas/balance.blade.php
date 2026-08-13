<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Balance de Admisión — {{ $entrevista->aspirante_nombre }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    @page { size: letter; margin: 1.1cm; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; padding: 18px; }
    .page { max-width: 820px; margin: 0 auto; }

    .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2.5px solid #1e3a8a; padding-bottom: 8px; margin-bottom: 12px; }
    .header .titulo { font-size: 18px; color: #1e3a8a; font-weight: bold; }
    .header .colegio { font-size: 12px; color: #444; letter-spacing: .5px; }
    .header .sub { font-size: 10px; color: #777; }

    h2.sec { font-size: 11px; background: #1e3a8a; color: #fff; padding: 4px 8px; margin: 12px 0 7px; border-radius: 3px; text-transform: uppercase; letter-spacing: .04em; }

    .datos { display: grid; grid-template-columns: repeat(4, 1fr); gap: 5px 14px; }
    .datos .f label { display: block; font-size: 8px; text-transform: uppercase; color: #888; letter-spacing: .04em; }
    .datos .f span { font-weight: 600; border-bottom: 1px solid #e5e7eb; display: block; padding-bottom: 1px; min-height: 13px; word-wrap: break-word; }

    /* Tarjetas de resumen */
    .cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-top: 4px; }
    .card { border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px 10px; text-align: center; }
    .card .big { font-size: 17px; font-weight: bold; line-height: 1.15; }
    .card .lbl { font-size: 8px; text-transform: uppercase; color: #94a3b8; letter-spacing: .04em; margin-top: 2px; }

    /* Examen */
    table.res { width: 100%; border-collapse: collapse; }
    table.res th { background: #eef2ff; color: #1e3a8a; font-size: 8.5px; text-transform: uppercase; padding: 4px 7px; text-align: left; }
    table.res td { padding: 4px 7px; border-bottom: 1px solid #eef1f5; font-size: 10.5px; }
    .bar { height: 8px; border-radius: 4px; background: #eef1f5; overflow: hidden; width: 110px; display: inline-block; vertical-align: middle; }
    .bar > i { display: block; height: 100%; }
    .pct { display: inline-block; width: 40px; text-align: right; font-weight: 700; }
    .total-row td { background: #eef2ff !important; font-weight: bold; border-top: 1.5px solid #1e3a8a; }

    /* Semáforo de áreas */
    .areas { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
    .area { border: 1px solid #e2e8f0; border-left-width: 4px; border-radius: 5px; padding: 5px 8px; }
    .area .t { font-size: 10px; font-weight: 600; }
    .area .n { font-size: 8.5px; text-transform: uppercase; letter-spacing: .03em; }
    .a-ok { border-left-color: #16a34a; } .a-ok .n { color: #15803d; }
    .a-atencion { border-left-color: #f59e0b; background: #fffbeb; } .a-atencion .n { color: #b45309; }
    .a-alerta { border-left-color: #dc2626; background: #fef2f2; } .a-alerta .n { color: #b91c1c; }
    .a-none { border-left-color: #cbd5e1; } .a-none .n { color: #94a3b8; }

    .niv, .pill { display: inline-block; font-size: 9.5px; padding: 2px 9px; border-radius: 999px; font-weight: bold; }
    .n-Superior, .n-Alto, .p-ok { background: #dcfce7; color: #15803d; }
    .n-Básico, .p-atencion { background: #fef3c7; color: #b45309; }
    .n-Bajo { background: #ffedd5; color: #c2410c; }
    .n-Insuficiente, .p-alerta { background: #fee2e2; color: #b91c1c; }
    .p-none { background: #f1f5f9; color: #64748b; }

    .bloque { border: 1px solid #e2e8f0; border-radius: 6px; padding: 7px 9px; margin-bottom: 6px; page-break-inside: avoid; }
    .bloque label { font-size: 8.5px; text-transform: uppercase; color: #64748b; letter-spacing: .04em; display: block; margin-bottom: 2px; }
    .bloque p { font-size: 10.5px; white-space: pre-wrap; }
    .bloque.alerta { border-color: #fecaca; background: #fef2f2; }
    .bloque.fuerte { border-color: #bbf7d0; background: #f0fdf4; }

    .veredicto { border: 2px solid #1e3a8a; border-radius: 7px; padding: 9px 12px; margin-top: 4px; display: flex; justify-content: space-between; align-items: center; gap: 12px; }
    .veredicto .concepto { font-size: 15px; font-weight: bold; color: #1e3a8a; }
    .veredicto .lbl { font-size: 8.5px; text-transform: uppercase; color: #64748b; letter-spacing: .04em; }

    .decision { border: 1.5px dashed #94a3b8; border-radius: 7px; padding: 10px 12px; margin-top: 10px; page-break-inside: avoid; }
    .decision .opts { display: flex; gap: 16px; flex-wrap: wrap; margin: 6px 0 8px; font-size: 10.5px; }
    .decision .opts span { display: inline-flex; align-items: center; gap: 5px; }
    .decision .box { width: 12px; height: 12px; border: 1.2px solid #475569; display: inline-block; border-radius: 2px; }
    .decision .box.on { background: #1e3a8a; border-color: #1e3a8a; }
    .decision .lineas { border-bottom: 1px solid #cbd5e1; height: 15px; margin-top: 3px; }

    .firmas { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 30px; margin-top: 34px; page-break-inside: avoid; }
    .firma { border-top: 1px solid #111; padding-top: 4px; text-align: center; font-size: 9px; }

    .pie { margin-top: 10px; font-size: 8.5px; color: #94a3b8; text-align: center; }

    .no-print { max-width: 820px; margin: 0 auto 14px; }
    .no-print .barra { display: flex; gap: 10px; align-items: center; margin-bottom: 10px; }
    .no-print button { background: #1e3a8a; color: #fff; border: none; padding: 8px 22px; border-radius: 6px; font-size: 13px; cursor: pointer; }
    .no-print a { color: #1e3a8a; font-size: 13px; text-decoration: underline; align-self: center; }
    .form-rect { border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px 14px; background: #f8fafc; }
    .form-rect h3 { font-size: 13px; color: #1e3a8a; margin-bottom: 8px; }
    .form-rect label { font-size: 11px; color: #475569; display: block; margin-bottom: 3px; }
    .form-rect select, .form-rect textarea, .form-rect input { width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 8px; font-size: 12px; font-family: inherit; }
    .form-rect .fila { display: flex; gap: 10px; margin-bottom: 8px; }
    .form-rect .fila > div { flex: 1; }
    .form-rect button { margin-top: 8px; }
    .aviso { background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 8px 12px; border-radius: 6px; font-size: 12px; margin-bottom: 10px; }
    @media print { .no-print { display: none; } body { padding: 0; } }
</style>
</head>
<body>
@php
    $e    = $entrevista;
    $ev   = $e->evaluacion_id ? $e->evaluacion : null;
    $decs = \App\Support\EntrevistaAdmision::DECISION_RECTORIA;

    $fmt      = fn($p) => rtrim(rtrim(number_format((float) $p, 1), '0'), '.');
    $barColor = fn($p) => $p >= 80 ? '#16a34a' : ($p >= 60 ? '#f59e0b' : ($p >= 30 ? '#f97316' : '#dc2626'));

    $res  = $ev->resultados ?? [];
    $tot  = $res['total'] ?? [];
    $esMc = $ev && $ev->tipo === 'opcion_multiple';

    // Conteo del semáforo por áreas
    $conteo = ['ok' => 0, 'atencion' => 0, 'alerta' => 0, 'sin' => 0];
    foreach (array_keys($secciones) as $slug) {
        $n = $e->alerta($slug);
        $conteo[$n ?: 'sin']++;
    }

    $viaCls = fn($v) => match($v) {
        'viable'       => 'p-ok',
        'condicionado' => 'p-atencion',
        'no_viable'    => 'p-alerta',
        default        => 'p-none',
    };

    $puedeDecidir = in_array(optional(auth()->user())->PROFILE, ['SuperAd', 'Admin'], true);
@endphp

<div class="no-print">
    <div class="barra">
        <button onclick="window.print()">🖨️ Imprimir balance</button>
        <a href="{{ route('admision.entrevistas.show', $e) }}">← Volver a la entrevista</a>
        <a href="{{ route('admision.entrevistas.imprimir', $e) }}" target="_blank">Ver entrevista completa</a>
    </div>

    @if(session('success'))
    <div class="aviso">✅ {{ session('success') }}</div>
    @endif

    @if($puedeDecidir)
    <form method="POST" action="{{ route('admision.entrevistas.rectoria', $e) }}" class="form-rect">
        @csrf
        <h3>Concepto final de rectoría</h3>
        <div class="fila">
            <div>
                <label>Decisión</label>
                <select name="rectoria_decision" required>
                    <option value="">— Selecciona —</option>
                    @foreach($decs as $k => $t)
                        <option value="{{ $k }}" @selected($e->rectoria_decision === $k)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label>Fecha</label>
                <input type="date" name="rectoria_fecha" value="{{ optional($e->rectoria_fecha)->format('Y-m-d') ?? date('Y-m-d') }}">
            </div>
        </div>
        <div>
            <label>Observaciones de rectoría</label>
            <textarea name="rectoria_observaciones" rows="3">{{ $e->rectoria_observaciones }}</textarea>
        </div>
        <button type="submit">Guardar decisión</button>
    </form>
    @endif
</div>

<div class="page">
    <div class="header">
        <div>
            <div class="colegio">COLEGIO BILINGÜE INTEGRAL</div>
            <div class="titulo">Balance de Admisión para Rectoría</div>
            <div class="sub">Examen de admisión + entrevista de orientación escolar</div>
        </div>
        <div style="text-align:right">
            <span class="pill {{ $viaCls($e->viabilidad) }}">Orientación: {{ $e->viabilidadTexto() }}</span>
            <div class="sub" style="margin-top:6px">Emitido: {{ now()->format('d/m/Y') }}</div>
        </div>
    </div>

    {{-- ── Identificación ──────────────────────────────────────────────── --}}
    <div class="datos">
        <div class="f" style="grid-column: span 2"><label>Aspirante</label><span>{{ $e->aspirante_nombre }}</span></div>
        <div class="f"><label>Documento</label><span>{{ trim($e->documento_tipo.' '.$e->aspirante_documento) ?: '—' }}</span></div>
        <div class="f"><label>Grado al que aspira</label><span>{{ $e->grado_nombre ?: '—' }}</span></div>

        <div class="f"><label>Edad</label><span>{{ $e->edad ? $e->edad.' años' : '—' }}</span></div>
        <div class="f"><label>Colegio de procedencia</label><span>{{ $e->institucion_procedencia ?: '—' }}</span></div>
        <div class="f"><label>Acudiente</label><span>{{ $e->acudiente ?: '—' }}{{ $e->acudiente_parentesco ? ' ('.$e->acudiente_parentesco.')' : '' }}</span></div>
        <div class="f"><label>Contacto</label><span>{{ $e->acudiente_telefono ?: ($e->telefono ?: '—') }}</span></div>
    </div>

    {{-- ── Resumen ejecutivo ───────────────────────────────────────────── --}}
    <h2 class="sec">Resumen</h2>
    <div class="cards">
        <div class="card">
            <div class="big" style="color: {{ $ev ? $barColor($ev->porcentaje) : '#cbd5e1' }}">
                {{ $ev ? $fmt($ev->porcentaje).'%' : '—' }}
            </div>
            <div class="lbl">Examen de admisión</div>
        </div>
        <div class="card">
            <div class="big"><span class="niv n-{{ $tot['nivel'] ?? '' }}">{{ $tot['nivel'] ?? '—' }}</span></div>
            <div class="lbl">Nivel académico global</div>
        </div>
        <div class="card">
            <div class="big" style="color: {{ $conteo['alerta'] ? '#b91c1c' : ($conteo['atencion'] ? '#b45309' : '#15803d') }}">
                {{ $conteo['alerta'] }} / {{ $conteo['atencion'] }}
            </div>
            <div class="lbl">Áreas en alerta / atención</div>
        </div>
        <div class="card">
            <div class="big"><span class="pill {{ $viaCls($e->viabilidad) }}">{{ $e->viabilidadTexto() }}</span></div>
            <div class="lbl">Concepto de orientación</div>
        </div>
    </div>

    {{-- ── Desempeño en el examen ──────────────────────────────────────── --}}
    <h2 class="sec">1. Desempeño en el examen de admisión</h2>
    @if($ev && !empty($res['materias']))
    <table class="res">
        <thead>
            <tr>
                <th style="width:34%">Materia</th>
                <th style="width:14%">{{ $esMc ? 'Aciertos' : 'L / P / N' }}</th>
                <th>Desempeño</th>
                <th style="width:16%">Nivel</th>
            </tr>
        </thead>
        <tbody>
            @foreach($res['materias'] as $m)
            <tr>
                <td style="font-weight:600">{{ $m['nombre'] }}</td>
                <td>
                    @if($esMc){{ $m['correctas'] }} / {{ $m['items'] }}
                    @else{{ $m['niveles']['L'] }} / {{ $m['niveles']['P'] }} / {{ $m['niveles']['N'] }}@endif
                </td>
                <td>
                    <span class="bar"><i style="width: {{ $m['porcentaje'] }}%; background: {{ $barColor($m['porcentaje']) }}"></i></span>
                    <span class="pct">{{ $fmt($m['porcentaje']) }}%</span>
                </td>
                <td><span class="niv n-{{ $m['nivel'] }}">{{ $m['nivel'] }}</span></td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td>TOTAL</td>
                <td>
                    @if($esMc){{ $tot['correctas'] }} / {{ $tot['items'] }}
                    @else{{ $tot['niveles']['L'] }} / {{ $tot['niveles']['P'] }} / {{ $tot['niveles']['N'] }}@endif
                </td>
                <td>
                    <span class="bar"><i style="width: {{ $tot['porcentaje'] }}%; background: {{ $barColor($tot['porcentaje']) }}"></i></span>
                    <span class="pct">{{ $fmt($tot['porcentaje']) }}%</span>
                </td>
                <td><span class="niv n-{{ $tot['nivel'] }}">{{ $tot['nivel'] }}</span></td>
            </tr>
        </tbody>
    </table>
    @else
    <div class="bloque"><p style="color:#94a3b8">Este aspirante aún no tiene un examen de admisión registrado y vinculado a la entrevista.</p></div>
    @endif

    {{-- ── Semáforo de áreas de la entrevista ──────────────────────────── --}}
    <h2 class="sec">2. Valoración por áreas de la entrevista</h2>
    <div class="areas">
        @foreach($secciones as $slug => $sec)
        @php $n = $e->alerta($slug); @endphp
        <div class="area a-{{ $n ?: 'none' }}">
            <div class="t">{{ $sec['titulo'] }}</div>
            <div class="n">{{ $e->alertaTexto($slug) }}</div>
        </div>
        @endforeach
    </div>

    {{-- ── Puntos clave de la entrevista ───────────────────────────────── --}}
    <h2 class="sec">3. Lectura de la entrevista</h2>

    @if($e->r('concepto', 'fortalezas'))
    <div class="bloque fuerte"><label>Fortalezas observadas</label><p>{{ $e->r('concepto', 'fortalezas') }}</p></div>
    @endif

    @if($e->r('concepto', 'alertas'))
    <div class="bloque alerta"><label>Dificultades o señales de alerta</label><p>{{ $e->r('concepto', 'alertas') }}</p></div>
    @endif

    @if($e->r('concepto', 'conducta_estudiante'))
    <div class="bloque"><label>Disposición del estudiante durante la entrevista</label><p>{{ $e->r('concepto', 'conducta_estudiante') }}</p></div>
    @endif

    @if($e->r('concepto', 'conducta_acudientes'))
    <div class="bloque"><label>Actitud de los acudientes</label><p>{{ $e->r('concepto', 'conducta_acudientes') }}</p></div>
    @endif

    @if($e->r('expectativas', 'eleccion'))
    <div class="bloque"><label>Por qué la familia eligió el colegio</label><p>{{ $e->r('expectativas', 'eleccion') }}</p></div>
    @endif

    @if($e->r('academica', 'dificultades') || $e->r('academica', 'piar'))
    <div class="bloque">
        <label>Antecedentes académicos a tener en cuenta</label>
        <p>{{ trim(($e->r('academica', 'dificultades') ?: '') . ($e->r('academica', 'piar') ? "\n".$e->r('academica', 'piar') : '')) }}</p>
    </div>
    @endif

    @if($e->r('salud', 'apoyos') || $e->r('salud', 'diagnostico'))
    <div class="bloque">
        <label>Apoyos terapéuticos y diagnósticos reportados</label>
        <p>{{ trim(($e->r('salud', 'apoyos') ?: '') . ($e->r('salud', 'diagnostico') ? "\n".$e->r('salud', 'diagnostico') : '')) }}</p>
    </div>
    @endif

    @if($e->r('familiar', 'custodia'))
    <div class="bloque"><label>Custodia y acuerdos legales que el colegio debe conocer</label><p>{{ $e->r('familiar', 'custodia') }}</p></div>
    @endif

    {{-- ── Concepto de orientación ─────────────────────────────────────── --}}
    <h2 class="sec">4. Concepto de orientación escolar</h2>
    <div class="veredicto">
        <div>
            <div class="lbl">Viabilidad de ingreso</div>
            <div class="concepto">{{ $e->viabilidadTexto() }}</div>
        </div>
        <div style="flex:1">
            <div class="lbl">Compromisos y recomendaciones</div>
            <div style="font-size:10.5px; white-space:pre-wrap">{{ trim(($e->compromisos ?: '') . ($e->r('concepto', 'recomendaciones') ? "\n".$e->r('concepto', 'recomendaciones') : '')) ?: '—' }}</div>
        </div>
    </div>

    {{-- ── Decisión de rectoría ────────────────────────────────────────── --}}
    <h2 class="sec">5. Decisión de rectoría</h2>
    <div class="decision">
        <div class="opts">
            @foreach($decs as $k => $t)
            <span><i class="box {{ $e->rectoria_decision === $k ? 'on' : '' }}"></i> {{ $t }}</span>
            @endforeach
        </div>
        <label style="font-size:8.5px; text-transform:uppercase; color:#64748b; letter-spacing:.04em">Observaciones de rectoría</label>
        @if($e->rectoria_observaciones)
            <p style="font-size:10.5px; white-space:pre-wrap; margin-top:2px">{{ $e->rectoria_observaciones }}</p>
        @else
            <div class="lineas"></div><div class="lineas"></div><div class="lineas"></div>
        @endif
        @if($e->rectoria_fecha)
        <p style="font-size:9px; color:#64748b; margin-top:5px">Registrado el {{ $e->rectoria_fecha->format('d/m/Y') }}{{ $e->rectoria_por ? ' por '.$e->rectoria_por : '' }}</p>
        @endif
    </div>

    <div class="firmas">
        <div class="firma">{{ $e->orientador }}&nbsp;<br><span style="color:#888">Orientación escolar</span></div>
        <div class="firma">{{ $e->coordinador }}&nbsp;<br><span style="color:#888">Coordinación</span></div>
        <div class="firma">&nbsp;<br><span style="color:#888">Rectoría</span></div>
    </div>

    <div class="pie">Portal CBI · Balance generado el {{ now()->format('d/m/Y H:i') }} · Documento confidencial de uso interno</div>
</div>
</body>
</html>
