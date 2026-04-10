{{--
    Plantilla institucional de circular.
    Se usa desde show.blade.php (dentro del layout) y desde pdf.blade.php (standalone).
    La variable $circular debe estar disponible en el contexto.
--}}

<div style="font-family: Arial, sans-serif; color: #1a1a1a; max-width: 720px; margin: 0 auto; padding: 40px 48px;">

    {{-- Encabezado institucional --}}
    <table style="width:100%; border-bottom: 3px solid #1e3a8a; margin-bottom: 24px;">
        <tr>
            <td style="width:80px; vertical-align:middle;">
                <img src="{{ public_path('images/escudoCBI.png') }}" alt="Logo CBI"
                     style="height:70px; width:auto;"
                     onerror="this.style.display='none'">
            </td>
            <td style="vertical-align:middle; padding-left: 16px;">
                <div style="font-size:18px; font-weight:700; color:#1e3a8a; letter-spacing:0.5px;">
                    COLEGIO BELLO INTEGRAL
                </div>
                <div style="font-size:11px; color:#6b7280; margin-top:2px;">
                    Resolución de Aprobación — NIT: 900.000.000-0
                </div>
            </td>
            <td style="text-align:right; vertical-align:middle;">
                <div style="font-size:11px; color:#6b7280;">Circular</div>
                <div style="font-size:22px; font-weight:700; color:#1e3a8a; font-family:monospace;">
                    {{ $circular->numero }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Ficha de datos --}}
    <table style="width:100%; font-size:13px; margin-bottom:28px; border-collapse:collapse;">
        <tr>
            <td style="width:110px; font-weight:600; color:#374151; padding:4px 0;">FECHA:</td>
            <td style="color:#111827;">{{ $circular->fecha->translatedFormat('j \d\e F \d\e Y') }}</td>
        </tr>
        <tr>
            <td style="font-weight:600; color:#374151; padding:4px 0;">DIRIGIDO A:</td>
            <td style="color:#111827;">{{ $circular->dirigido_a }}</td>
        </tr>
        <tr>
            <td style="font-weight:600; color:#374151; padding:4px 0;">DE:</td>
            <td style="color:#111827;">{{ $circular->emitido_por }}</td>
        </tr>
        <tr>
            <td style="font-weight:600; color:#374151; padding:4px 0; vertical-align:top;">ASUNTO:</td>
            <td style="color:#111827; font-weight:600;">{{ $circular->asunto }}</td>
        </tr>
    </table>

    <hr style="border:none; border-top:1px solid #e5e7eb; margin-bottom:24px;">

    {{-- Cuerpo --}}
    <div style="font-size:13.5px; line-height:1.8; text-align:justify;">
        {!! $circular->contenido !!}
    </div>

    {{-- Pie --}}
    <div style="margin-top:48px; font-size:12px; color:#6b7280; text-align:center; border-top:1px solid #e5e7eb; padding-top:16px;">
        {{ $circular->emitido_por }} &nbsp;·&nbsp; {{ $circular->fecha->format('Y') }}
    </div>

</div>
