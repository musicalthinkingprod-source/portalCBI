<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Hoja de Respuestas OMR — {{ $grado['nombre'] }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    @page { size: letter; margin: 0; }
    html, body { margin: 0; padding: 0; background: #fff; }
    body { font-family: Arial, Helvetica, sans-serif; color: #111; }

    .hoja { position: relative; width: {{ $omr['page']['w'] }}mm; height: {{ $omr['page']['h'] }}mm; overflow: hidden; }

    /* Marcas fiduciales (cuadros negros) y marco de referencia */
    .fid { position: absolute; width: {{ $omr['fidSize'] }}mm; height: {{ $omr['fidSize'] }}mm; background: #000; }
    .marco { position: absolute; border: 0.3mm solid #c8c8c8; }

    .abs { position: absolute; }
    .titulo { top: 16mm; left: 0; width: 100%; text-align: center; }
    .titulo .col { font-size: 12pt; font-weight: bold; color: #1e3a8a; letter-spacing: .5px; }
    .titulo .ti { font-size: 14pt; font-weight: bold; }
    .titulo .sub { font-size: 8.5pt; color: #444; }

    .campos { top: 30mm; left: 16mm; width: 183mm; font-size: 9pt; }
    .campos .row { display: flex; gap: 10mm; margin-bottom: 2.5mm; }
    .campo { flex: 1; }
    .campo .lbl { font-size: 7.5pt; color: #555; }
    .campo .line { border-bottom: 0.4mm solid #333; height: 5mm; }

    .colhead { font-size: 8pt; font-weight: bold; color: #1e3a8a; text-align: center;
               border-bottom: 0.4mm solid #1e3a8a; padding-bottom: 0.6mm; }
    .num { font-size: 8pt; font-weight: bold; text-align: right; width: 6mm; color: #444; }

    .bub {
        width: {{ 2*$omr['bubbleR'] }}mm; height: {{ 2*$omr['bubbleR'] }}mm;
        border: 0.35mm solid #333; border-radius: 50%;
        font-size: 6pt; color: #999; text-align: center; line-height: {{ 2*$omr['bubbleR'] }}mm;
    }

    .instr { top: 44mm; left: 16mm; width: 183mm; font-size: 7.5pt; color: #333;
             background: #f3f3f3; border: 0.3mm solid #ccc; border-radius: 2mm; padding: 1.5mm 3mm; }

    .no-print { position: fixed; top: 8px; right: 8px; }
    .no-print button { background: #1e3a8a; color:#fff; border:none; padding:8px 18px; border-radius:6px; font-size:13px; cursor:pointer; }
    @media print { .no-print { display: none; } }
</style>
</head>
<body>
<div class="no-print"><button onclick="window.print()">🖨️ Imprimir</button></div>

<div class="hoja">
    {{-- Fiduciales --}}
    @foreach($omr['fid'] as $pos => $c)
        <div class="fid" style="left: {{ $c[0] - $omr['fidSize']/2 }}mm; top: {{ $c[1] - $omr['fidSize']/2 }}mm;"></div>
    @endforeach
    {{-- Marco de referencia (une los fiduciales) --}}
    <div class="marco" style="left: {{ $omr['fid']['tl'][0] }}mm; top: {{ $omr['fid']['tl'][1] }}mm;
        width: {{ $omr['fid']['tr'][0] - $omr['fid']['tl'][0] }}mm; height: {{ $omr['fid']['bl'][1] - $omr['fid']['tl'][1] }}mm;"></div>

    {{-- Encabezado --}}
    <div class="abs titulo">
        <div class="col">COLEGIO BILINGÜE INTEGRAL</div>
        <div class="ti">Hoja de Respuestas · Examen de Admisión</div>
        <div class="sub">Ingreso a {{ $grado['nombre'] }} · Selección múltiple con única respuesta</div>
    </div>

    {{-- Campos --}}
    <div class="abs campos">
        <div class="row">
            <div class="campo" style="flex:2"><div class="lbl">Nombre del aspirante</div><div class="line"></div></div>
            <div class="campo"><div class="lbl">Documento</div><div class="line"></div></div>
        </div>
        <div class="row">
            <div class="campo"><div class="lbl">Grado al que aspira</div><div class="line">{{ $grado['nombre'] }}</div></div>
            <div class="campo"><div class="lbl">Fecha</div><div class="line"></div></div>
        </div>
    </div>

    <div class="abs instr">
        <b>Instrucciones:</b> Rellena por completo el círculo de la opción elegida (A, B, C o D) con lápiz oscuro o lapicero negro.
        Marca una sola opción por pregunta. Si te equivocas, borra por completo. No hagas otras marcas sobre la hoja.
    </div>

    {{-- Títulos de columna (materias) --}}
    @foreach($omr['cols'] as $col)
        <div class="abs colhead" style="left: {{ $col['x'] }}mm; top: {{ $col['header_y'] - 4 }}mm; width: {{ $col['ancho'] - 2 }}mm;">
            {{ $col['materia'] }}
        </div>
    @endforeach

    {{-- Números de pregunta --}}
    @php $r = $omr['bubbleR']; @endphp
    @foreach($omr['cols'] as $ci => $col)
        @for($row = 0; $row < $omr['rows']; $row++)
            @php $n = $ci * $omr['rows'] + $row + 1; $y = \App\Support\OmrLayout::ROW_Y0 + $row * $omr['rowDy']; @endphp
            <div class="abs num" style="left: {{ $col['num_x'] - 2 }}mm; top: {{ $y - 2 }}mm;">{{ $n }}</div>
        @endfor
    @endforeach

    {{-- Burbujas --}}
    @foreach($omr['bubbles'] as $b)
        <div class="abs bub" style="left: {{ $b['x'] - $r }}mm; top: {{ $b['y'] - $r }}mm;">{{ $b['opt'] }}</div>
    @endforeach
</div>
</body>
</html>
