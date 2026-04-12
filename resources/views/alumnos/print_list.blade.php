<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titulo }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #000; }

        .encabezado {
            text-align: center;
            margin-bottom: 14px;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
        }
        .encabezado h1 { font-size: 14px; font-weight: bold; text-transform: uppercase; }
        .encabezado p  { font-size: 10px; color: #444; margin-top: 3px; }

        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead tr th {
            border: 1px solid #000;
            padding: 4px 6px;
            background: #e8e8e8;
            font-size: 10px;
            text-transform: uppercase;
            text-align: left;
        }
        tbody tr td {
            border: 1px solid #888;
            padding: 5px 6px;
            vertical-align: middle;
        }
        tbody tr:nth-child(even) td { background: #f7f7f7; }

        .td-num   { width: 28px; text-align: center; color: #555; }
        .td-cod   { width: 70px; font-weight: bold; }
        .td-apel  { }
        .td-nom   { }
        .td-extra { width: 120px; min-width: 80px; }

        .pie {
            margin-top: 10px;
            font-size: 9px;
            color: #666;
            text-align: right;
        }

        @media print {
            body { font-size: 10px; }
            .no-print { display: none !important; }
            @page { margin: 1.5cm 1.2cm; }
        }
    </style>
</head>
<body>

<div class="no-print" style="padding:12px; background:#f0f4ff; display:flex; align-items:center; gap:12px; border-bottom:1px solid #ccc;">
    <button onclick="window.print()"
        style="background:#1e3a8a; color:#fff; border:none; padding:7px 18px; border-radius:6px; font-size:12px; cursor:pointer; font-weight:bold;">
        🖨️ Imprimir
    </button>
    <button onclick="window.close()"
        style="background:#e5e7eb; color:#374151; border:none; padding:7px 14px; border-radius:6px; font-size:12px; cursor:pointer;">
        ✕ Cerrar
    </button>
    <span style="font-size:11px; color:#555;">{{ $estudiantes->count() }} estudiante(s)</span>
</div>

<div style="padding: 16px 20px;">

    <div class="encabezado">
        <h1>{{ $titulo }}</h1>
        <p>Institución Educativa · Fecha: {{ \Carbon\Carbon::now()->format('d/m/Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="td-num">#</th>
                <th class="td-cod">Código</th>
                <th class="td-apel">Apellidos</th>
                <th class="td-nom">Nombres</th>
                @if($col1)<th class="td-extra">{{ $col1 }}</th>@endif
                @if($col2)<th class="td-extra">{{ $col2 }}</th>@endif
            </tr>
        </thead>
        <tbody>
            @foreach($estudiantes as $i => $e)
            <tr>
                <td class="td-num">{{ $i + 1 }}</td>
                <td class="td-cod">{{ $e->CODIGO }}</td>
                <td class="td-apel">{{ trim($e->APELLIDO1 . ' ' . $e->APELLIDO2) }}</td>
                <td class="td-nom">{{ trim($e->NOMBRE1 . ' ' . $e->NOMBRE2) }}</td>
                @if($col1)<td class="td-extra">&nbsp;</td>@endif
                @if($col2)<td class="td-extra">&nbsp;</td>@endif
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="pie">
        Generado: {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }} · Total: {{ $estudiantes->count() }} estudiantes
    </div>

</div>
</body>
</html>
