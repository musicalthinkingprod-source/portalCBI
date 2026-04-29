@php
    /**
     * Muestra la foto del estudiante alojada en Google Drive a partir del ID
     * guardado en ESTUDIANTES.FOTO_DRIVE. Si no hay ID muestra un placeholder.
     *
     * Variables esperadas:
     *   $fotoDrive  string|null   ID del archivo en Drive (FOTO_DRIVE).
     *   $width      int|string    Ancho en px (default 200).
     *   $height     int|string    Alto en px (default 260).
     *   $estilo     string|null   "print" para caja punteada con texto "PEGAR FOTO";
     *                             cualquier otro valor renderiza un avatar gris.
     */
    $fotoDrive = $fotoDrive ?? null;
    $width     = $width  ?? 200;
    $height    = $height ?? 260;
    $estilo    = $estilo ?? 'print';
    $url       = $fotoDrive
        ? 'https://drive.google.com/thumbnail?id=' . e($fotoDrive) . '&sz=w' . (int) $width
        : null;
@endphp

@if ($url)
    <img src="{{ $url }}"
         alt="Foto del estudiante"
         referrerpolicy="no-referrer"
         style="width:{{ $width }}px;height:{{ $height }}px;object-fit:cover;border:1px solid #333;display:inline-block;">
@elseif ($estilo === 'print')
    <span style="display:inline-block;width:{{ $width }}px;height:{{ $height }}px;border:2.5px dashed #333;text-align:center;line-height:{{ $height }}px;font-size:13pt;font-family:Arial,sans-serif;color:#555;letter-spacing:2px;">PEGAR FOTO</span>
@else
    <span style="display:inline-flex;align-items:center;justify-content:center;width:{{ $width }}px;height:{{ $height }}px;background:#e5e7eb;color:#6b7280;border-radius:8px;font-size:12px;">Sin foto</span>
@endif
