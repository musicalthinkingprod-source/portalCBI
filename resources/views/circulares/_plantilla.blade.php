{{--
    Plantilla institucional de circular — Formato CBI-FCE001
    Se usa desde show.blade.php (dentro del layout) y desde pdf.blade.php (standalone).
    La variable $circular debe estar disponible en el contexto.
--}}
@php
    // Imágenes embebidas como base64 para que funcionen tanto en navegador (show) como en PDF (DomPDF).
    $logoFile = public_path('images/escudoCBI.png');
    $logoSrc  = file_exists($logoFile)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoFile))
        : null;
@endphp

<div style="font-family: Arial, Helvetica, sans-serif; color: #1a1a1a; max-width: 740px; margin: 0 auto; padding: 40px 48px;">

    {{-- ═══════════════════════════════════════
         ENCABEZADO — tabla oficial CBI-FCE001
         ═══════════════════════════════════════ --}}
    <table style="width:100%; border-collapse:collapse; border:1.5px solid #374151; margin-bottom:0;">
        <tr>
            {{-- Logo (rowspan 3) --}}
            <td rowspan="3"
                style="width:90px; border-right:1.5px solid #374151; text-align:center; vertical-align:middle; padding:10px;">
                @if($logoSrc)
                    <img src="{{ $logoSrc }}" alt="Logo CBI" style="height:72px; width:auto;">
                @endif
            </td>
            {{-- Fila 1 centro: "FORMATO" --}}
            <td style="border-right:1.5px solid #374151; border-bottom:1px solid #374151;
                        text-align:center; vertical-align:middle; padding:6px 12px;">
                <span style="font-size:13px; font-weight:700; letter-spacing:0.5px;">FORMATO</span>
            </td>
            {{-- Fila 1 derecha: Código --}}
            <td style="border-bottom:1px solid #374151; text-align:center; vertical-align:middle;
                        padding:6px 12px; width:160px;">
                <span style="font-size:11px; color:#374151;">Código</span><br>
                <span style="font-size:12px; font-weight:700;">CBI-FCE001</span>
            </td>
        </tr>
        <tr>
            {{-- Fila 2 centro: "CIRCULAR EXTERNA" --}}
            <td style="border-right:1.5px solid #374151; border-bottom:1px solid #374151;
                        text-align:center; vertical-align:middle; padding:6px 12px;">
                <span style="font-size:14px; font-weight:700; letter-spacing:0.3px;">CIRCULAR EXTERNA</span>
            </td>
            {{-- Fila 2 derecha: Versión --}}
            <td style="border-bottom:1px solid #374151; text-align:center; vertical-align:middle;
                        padding:6px 12px;">
                <span style="font-size:11px; color:#374151;">Versión 01</span>
            </td>
        </tr>
        <tr>
            {{-- Fila 3 centro: Número de circular --}}
            <td style="border-right:1.5px solid #374151; text-align:center; vertical-align:middle; padding:6px 12px;">
                <span style="font-size:13px; font-weight:700; color:#1e3a8a; letter-spacing:0.5px;">
                    {{ $circular->numero }}
                </span>
            </td>
            {{-- Fila 3 derecha: Fecha de aprobación (fija) --}}
            <td style="text-align:center; vertical-align:middle; padding:6px 12px;">
                <span style="font-size:10px; color:#374151;">Fecha Aprobación</span><br>
                <span style="font-size:11px; font-weight:600;">2008-ENE-08</span>
            </td>
        </tr>
    </table>

    {{-- Filas de datos: ASUNTO y FECHA --}}
    <table style="width:100%; border-collapse:collapse;
                  border-left:1.5px solid #374151;
                  border-right:1.5px solid #374151;
                  border-bottom:1.5px solid #374151;
                  margin-bottom:28px;">
        <tr>
            <td style="width:110px; font-weight:700; font-size:12px; padding:7px 12px;
                        border-bottom:1px solid #374151; border-right:1px solid #374151;
                        vertical-align:top; text-transform:uppercase; color:#111827;">
                ASUNTO
            </td>
            <td style="font-size:12px; font-weight:700; padding:7px 12px;
                        border-bottom:1px solid #374151; text-transform:uppercase; color:#111827;">
                {{ $circular->asunto }}
            </td>
        </tr>
        <tr>
            <td style="font-weight:700; font-size:12px; padding:7px 12px;
                        border-right:1px solid #374151; vertical-align:middle;
                        text-transform:uppercase; color:#111827;">
                FECHA
            </td>
            <td style="font-size:12px; font-weight:700; padding:7px 12px;
                        text-transform:uppercase; color:#111827;">
                {{ mb_strtoupper($circular->fecha->locale('es')->isoFormat('D [DE] MMMM [DE] YYYY')) }}
            </td>
        </tr>
    </table>

    {{-- ═══════════════════
         CUERPO DE LA CIRCULAR
         ═══════════════════ --}}

    {{-- Saludo / Dirigido a --}}
    @if($circular->dirigido_a)
    <p style="font-size:13.5px; margin-bottom:10px;">
        {{ $circular->dirigido_a }}:
    </p>
    @endif

    <p style="font-size:13.5px; margin-bottom:20px;">
        Reciban un cordial saludo.
    </p>

    {{-- Contenido --}}
    @if($circular->contenido)
    <div style="font-size:13.5px; line-height:1.85; text-align:justify; margin-bottom:24px;">
        {!! $circular->contenido !!}
    </div>
    @elseif($circular->link)
    <div style="text-align:center; padding: 32px 0; margin-bottom:24px;">
        <p style="font-size:13px; color:#6b7280; margin-bottom:16px;">Esta circular está disponible en Google Drive.</p>
        <a href="{{ $circular->link }}" target="_blank" rel="noopener"
           style="display:inline-block; background:#1e3a8a; color:#fff; padding:10px 28px;
                  border-radius:8px; font-size:13px; font-weight:600; text-decoration:none;">
            Ver circular en Drive
        </a>
    </div>
    @endif

    {{-- ═══════════════════
         PIE DE FIRMA
         ═══════════════════ --}}
    <div style="margin-top:52px;">
        {{-- Imagen de firma --}}
        @php
            $firmaPath = public_path('images/firma.png');
            $firmaSrc  = file_exists($firmaPath)
                ? 'data:image/png;base64,' . base64_encode(file_get_contents($firmaPath))
                : null;
        @endphp
        @if($firmaSrc)
            <img src="{{ $firmaSrc }}" alt="Firma"
                 style="height:72px; width:auto; display:block; margin-bottom:4px;">
        @else
            <div style="height:72px; margin-bottom:4px;"></div>
        @endif

        <p style="font-size:13px; font-weight:700; margin:0; line-height:1.4;">
            {{ mb_strtoupper($circular->emitido_por) }}
        </p>
        @if($circular->cargo)
        <p style="font-size:13px; margin:0; color:#374151;">
            {{ $circular->cargo }}
        </p>
        @endif
    </div>

</div>
