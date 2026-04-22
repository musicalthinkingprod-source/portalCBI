<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #1a1a1a; }

        /* Estilos para el contenido CKEditor 5 en PDF */
        strong { font-weight: bold; }
        em     { font-style: italic; }
        u      { text-decoration: underline; }
        s      { text-decoration: line-through; }

        ol, ul { padding-left: 28px; margin: 8px 0; }
        li     { margin-bottom: 4px; }

        h1 { font-size: 22px; margin: 14px 0 8px; }
        h2 { font-size: 19px; margin: 12px 0 6px; }
        h3 { font-size: 16px; margin: 10px 0 6px; }
        h4 { font-size: 14px; margin: 10px 0 6px; }

        p { margin: 0 0 8px; }

        /* Tamaños de fuente de CKEditor */
        .text-tiny  { font-size: 0.7em; }
        .text-small { font-size: 0.85em; }
        .text-big   { font-size: 1.4em; }
        .text-huge  { font-size: 1.8em; }

        /* Tablas insertadas con CKEditor */
        figure.table { display: block; margin: 10px 0; }
        figure.table table { border-collapse: collapse; width: 100%; }
        figure.table table td,
        figure.table table th { border: 1px solid #999; padding: 6px 10px; vertical-align: top; }
        figure.table table th { background: #f3f4f6; font-weight: 700; }

        /* Imágenes */
        figure.image { display: block; margin: 10px 0; text-align: center; }
        figure.image img { max-width: 100%; height: auto; }
        figure.image figcaption { font-size: 11px; color: #555; margin-top: 4px; }
        figure.image-style-side { float: right; margin: 0 0 10px 14px; max-width: 45%; }
        figure.image-style-align-left { float: left; margin: 0 14px 10px 0; }
        figure.image-style-align-right { float: right; margin: 0 0 10px 14px; }
        figure.image-style-align-center { margin: 10px auto; }
        img { max-width: 100%; height: auto; }

        /* Blockquote */
        blockquote { border-left: 4px solid #cbd5e1; padding-left: 14px; margin: 10px 0; color: #475569; font-style: italic; }

        /* Código */
        pre { background: #f3f4f6; padding: 10px; border-radius: 4px; font-family: Consolas, monospace; font-size: 12px; overflow: hidden; }
        code { background: #f3f4f6; padding: 1px 4px; border-radius: 3px; font-family: Consolas, monospace; font-size: 12px; }

        /* Línea horizontal / salto de página */
        hr { border: none; border-top: 1px solid #cbd5e1; margin: 14px 0; }
        .page-break { page-break-after: always; }

        /* Media embed (YouTube, etc.) — en PDF no hay iframe interactivo, muestra enlace */
        figure.media { display: block; margin: 10px 0; padding: 10px; background: #f9fafb; border: 1px dashed #cbd5e1; }

        /* Alineación (CKEditor usa style inline o class) */
        .text-center { text-align: center; }
        .text-right  { text-align: right; }
        .text-justify { text-align: justify; }
    </style>
</head>
<body>
    @include('circulares._plantilla', ['circular' => $circular])
</body>
</html>
