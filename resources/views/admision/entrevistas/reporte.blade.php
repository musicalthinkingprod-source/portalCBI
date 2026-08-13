<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Entrevista de Admisión — {{ $entrevista->aspirante_nombre }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    @page { size: letter; margin: 1.2cm; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #1a1a1a; background: #fff; padding: 18px; }
    .page { max-width: 800px; margin: 0 auto; }

    .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2.5px solid #1e3a8a; padding-bottom: 8px; margin-bottom: 12px; }
    .header .titulo { font-size: 17px; color: #1e3a8a; font-weight: bold; }
    .header .colegio { font-size: 12px; color: #444; letter-spacing: .5px; }
    .header .sub { font-size: 10px; color: #777; }
    .badge { display: inline-block; font-size: 10px; padding: 3px 10px; border-radius: 999px; font-weight: bold; }

    h2.sec { font-size: 11.5px; background: #1e3a8a; color: #fff; padding: 4px 8px; margin: 14px 0 8px; border-radius: 3px; text-transform: uppercase; letter-spacing: .04em; }
    h2.sec .sem { float: right; font-size: 9px; padding: 1px 8px; border-radius: 999px; font-weight: bold; }

    .datos { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px 14px; margin-bottom: 6px; }
    .datos .f { min-width: 0; }
    .datos .f label { display: block; font-size: 8px; text-transform: uppercase; color: #888; letter-spacing: .04em; }
    .datos .f span { font-weight: 600; border-bottom: 1px solid #e5e7eb; display: block; padding-bottom: 1px; min-height: 13px; word-wrap: break-word; }

    .qa { margin-bottom: 7px; page-break-inside: avoid; }
    .qa .q { font-size: 9.5px; color: #64748b; }
    .qa .a { font-size: 11px; border-left: 2px solid #cbd5e1; padding-left: 7px; margin-top: 1px; min-height: 13px; white-space: pre-wrap; }
    .qa .a.vacio { color: #cbd5e1; }

    table.fam { width: 100%; border-collapse: collapse; margin-top: 6px; }
    table.fam th { background: #eef2ff; color: #1e3a8a; font-size: 8.5px; text-transform: uppercase; padding: 4px 6px; text-align: left; }
    table.fam td { padding: 4px 6px; border-bottom: 1px solid #eef1f5; font-size: 10px; }

    .s-ok { background: #dcfce7; color: #15803d; }
    .s-atencion { background: #fef3c7; color: #b45309; }
    .s-alerta { background: #fee2e2; color: #b91c1c; }
    .s-none { background: #f1f5f9; color: #64748b; }

    .concepto { border: 1.5px solid #1e3a8a; border-radius: 6px; padding: 8px 10px; margin-top: 6px; }
    .concepto .via { font-size: 12px; font-weight: bold; }

    .firmas { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 40px; page-break-inside: avoid; }
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
    $e = $entrevista;
    $semCls = fn($k) => $k ? 's-'.$k : 's-none';
@endphp

<div class="no-print">
    <button onclick="window.print()">🖨️ Imprimir</button>
    <a href="{{ route('admision.entrevistas.show', $e) }}">← Volver a la entrevista</a>
</div>

<div class="page">
    <div class="header">
        <div>
            <div class="colegio">COLEGIO BILINGÜE INTEGRAL</div>
            <div class="titulo">Entrevista de Admisión</div>
            <div class="sub">Orientación Escolar{{ $e->grado_nombre ? ' · Ingreso a '.$e->grado_nombre : '' }}</div>
        </div>
        <div style="text-align:right">
            <span class="badge {{ $e->estado === 'finalizada' ? 's-ok' : 's-none' }}">{{ $e->estado === 'finalizada' ? 'Finalizada' : 'Borrador' }}</span>
            <div class="sub" style="margin-top:6px">Fecha: {{ optional($e->fecha_entrevista)->format('d/m/Y') ?? '—' }}</div>
        </div>
    </div>

    {{-- ── I. Datos personales ─────────────────────────────────────────── --}}
    <h2 class="sec">I. Datos personales y contexto de ingreso</h2>
    <div class="datos">
        <div class="f" style="grid-column: span 2"><label>Nombre completo</label><span>{{ $e->aspirante_nombre }}</span></div>
        <div class="f"><label>Documento</label><span>{{ trim($e->documento_tipo.' '.$e->aspirante_documento) ?: '—' }}</span></div>
        <div class="f"><label>Grado al que aspira</label><span>{{ $e->grado_nombre ?: '—' }}</span></div>

        <div class="f"><label>Fecha de nacimiento</label><span>{{ optional($e->fecha_nacimiento)->format('d/m/Y') ?? '—' }}</span></div>
        <div class="f"><label>Lugar de nacimiento</label><span>{{ $e->lugar_nacimiento ?: '—' }}</span></div>
        <div class="f"><label>Edad</label><span>{{ $e->edad ? $e->edad.' años' : '—' }}</span></div>
        <div class="f"><label>Teléfono</label><span>{{ $e->telefono ?: '—' }}</span></div>

        <div class="f" style="grid-column: span 2"><label>Dirección</label><span>{{ $e->direccion ?: '—' }}</span></div>
        <div class="f"><label>Acudiente</label><span>{{ $e->acudiente ?: '—' }}</span></div>
        <div class="f"><label>Parentesco</label><span>{{ $e->acudiente_parentesco ?: '—' }}</span></div>

        <div class="f"><label>Ocupación acudiente</label><span>{{ $e->acudiente_ocupacion ?: '—' }}</span></div>
        <div class="f"><label>Contacto acudiente</label><span>{{ $e->acudiente_telefono ?: '—' }}</span></div>
        <div class="f" style="grid-column: span 2"><label>Correo</label><span>{{ $e->acudiente_correo ?: '—' }}</span></div>

        <div class="f" style="grid-column: span 3"><label>Institución educativa de procedencia</label><span>{{ $e->institucion_procedencia ?: '—' }}</span></div>
        <div class="f"><label>Tiempo de permanencia</label><span>{{ $e->tiempo_permanencia ?: '—' }}</span></div>
    </div>

    <div class="qa"><div class="q">2. Motivo del cambio de institución. Fue decisión de la familia, del colegio anterior, o de ambas partes.</div>
        <div class="a {{ $e->motivo_retiro ? '' : 'vacio' }}">{{ $e->motivo_retiro ?: '—' }}</div></div>
    <div class="qa"><div class="q">3. Ha repetido algún grado. En caso afirmativo, cuál y por qué motivo.</div>
        <div class="a {{ $e->ha_reprobado ? '' : 'vacio' }}">{{ $e->ha_reprobado ?: '—' }}</div></div>
    <div class="qa"><div class="q">4. Ha tenido más de dos cambios de colegio en su historia escolar. Qué llevó a esos cambios.</div>
        <div class="a {{ $e->cambios_colegio ? '' : 'vacio' }}">{{ $e->cambios_colegio ?: '—' }}</div></div>
    <div class="qa"><div class="q">5. Composición del núcleo familiar con quien convive el estudiante actualmente.</div>
        <div class="a {{ $e->nucleo_familiar ? '' : 'vacio' }}">{{ $e->nucleo_familiar ?: '—' }}</div></div>
    <div class="qa"><div class="q">6. Persona responsable de acompañar los procesos académicos en casa.</div>
        <div class="a {{ $e->responsable_academico ? '' : 'vacio' }}">{{ $e->responsable_academico ?: '—' }}{{ $e->red_apoyo ? ' · Red de apoyo: '.$e->red_apoyo : '' }}</div></div>

    @if($e->familia)
    <table class="fam">
        <thead>
            <tr><th>Familiar</th><th>Parentesco</th><th style="width:45px">Edad</th><th>Ocupación</th><th>Positivo</th><th>Por mejorar</th></tr>
        </thead>
        <tbody>
            @foreach($e->familia as $f)
            <tr>
                <td>{{ $f['nombre'] }}</td>
                <td>{{ $f['parentesco'] ?: '—' }}</td>
                <td>{{ $f['edad'] ?? '—' }}</td>
                <td>{{ $f['ocupacion'] ?: '—' }}</td>
                <td>{{ $f['positivo'] ?: '—' }}</td>
                <td>{{ $f['negativo'] ?: '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- ── II a VII ─────────────────────────────────────────────────────── --}}
    @foreach($secciones as $slug => $sec)
    <h2 class="sec">
        {{ $sec['num'] }}. {{ $sec['titulo'] }}
        <span class="sem {{ $semCls($e->alerta($slug)) }}">{{ $e->alertaTexto($slug) }}</span>
    </h2>
    @foreach($sec['preguntas'] as $i => $p)
    <div class="qa">
        <div class="q">{{ $i + 1 }}. {{ $p['t'] }}</div>
        <div class="a {{ $e->r($slug, $p['k']) ? '' : 'vacio' }}">{{ $e->r($slug, $p['k']) ?: '—' }}</div>
    </div>
    @endforeach
    @endforeach

    {{-- ── VIII. Concepto ───────────────────────────────────────────────── --}}
    <h2 class="sec">VIII. Concepto de los entrevistadores</h2>
    @foreach($concepto as $i => $p)
    <div class="qa">
        <div class="q">{{ $i + 1 }}. {{ $p['t'] }}</div>
        <div class="a {{ $e->r('concepto', $p['k']) ? '' : 'vacio' }}">{{ $e->r('concepto', $p['k']) ?: '—' }}</div>
    </div>
    @endforeach

    <div class="concepto">
        <div class="via">Concepto frente a la viabilidad de ingreso: {{ $e->viabilidadTexto() }}</div>
        @if($e->compromisos)
        <div style="margin-top:5px"><b style="font-size:9.5px;color:#64748b;text-transform:uppercase">Compromisos</b><br>{{ $e->compromisos }}</div>
        @endif
        @if($e->evaluacion)
        <div style="margin-top:5px; font-size:10px; color:#475569">
            Examen de admisión: {{ rtrim(rtrim(number_format($e->evaluacion->porcentaje, 1), '0'), '.') }}%
            ({{ $e->evaluacion->resultados['total']['nivel'] ?? '—' }})
        </div>
        @endif
    </div>

    <div class="firmas">
        <div class="firma">{{ $e->orientador }}&nbsp;<br><span style="color:#888">Orientador(a) escolar</span></div>
        <div class="firma">{{ $e->coordinador }}&nbsp;<br><span style="color:#888">Coordinador(a)</span></div>
    </div>

    <div class="pie">Documento generado por el Portal CBI · {{ now()->format('d/m/Y H:i') }}</div>
</div>
</body>
</html>
