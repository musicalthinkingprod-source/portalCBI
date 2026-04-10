<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #1a1a1a; }

        /* Estilos para el contenido Quill en PDF */
        strong { font-weight: bold; }
        em     { font-style: italic; }
        u      { text-decoration: underline; }

        .ql-size-small  { font-size: 11px; }
        .ql-size-large  { font-size: 16px; }

        ol, ul { padding-left: 24px; margin: 8px 0; }
        li     { margin-bottom: 4px; }

        .ql-align-center { text-align: center; }
        .ql-align-right  { text-align: right; }
        .ql-align-justify{ text-align: justify; }
    </style>
</head>
<body>
    @include('circulares._plantilla', ['circular' => $circular])
</body>
</html>
