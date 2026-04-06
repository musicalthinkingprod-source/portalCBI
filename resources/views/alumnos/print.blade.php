<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ficha del Estudiante - {{ $estudiante->APELLIDO1 }} {{ $estudiante->NOMBRE1 }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 11px; color: #111; background: #fff; padding: 20px; }
        .page { max-width: 800px; margin: 0 auto; }

        /* Encabezado */
        .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #1e3a8a; padding-bottom: 10px; margin-bottom: 14px; }
        .header h1 { font-size: 15px; color: #1e3a8a; font-weight: bold; }
        .header p { font-size: 10px; color: #555; }
        .header .codigo { font-size: 13px; font-weight: bold; color: #1e3a8a; }

        /* Secciones */
        .section { margin-bottom: 14px; border: 1px solid #c7d2fe; border-radius: 6px; overflow: hidden; }
        .section-title { background: #1e3a8a; color: #fff; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.05em; padding: 5px 10px; }
        .section-body { padding: 8px 10px; }

        /* Grillas */
        .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 6px 10px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px 10px; }
        .grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px 10px; }
        .field label { display: block; font-size: 9px; color: #6b7280; margin-bottom: 1px; }
        .field p { font-weight: 600; border-bottom: 1px solid #e5e7eb; padding-bottom: 1px; min-height: 14px; }

        /* Tabla historial */
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th { background: #f3f4f6; text-align: left; padding: 4px 6px; font-size: 9px; text-transform: uppercase; color: #6b7280; }
        td { padding: 3px 6px; border-bottom: 1px solid #f3f4f6; }

        /* Pie de página */
        .footer { margin-top: 20px; border-top: 1px solid #c7d2fe; padding-top: 12px; font-size: 9px; color: #374151; line-height: 1.5; }
        .footer p { margin-bottom: 8px; text-align: justify; }
        .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px; }
        .sign-line { border-top: 1px solid #111; padding-top: 4px; text-align: center; font-size: 9px; }

        /* Botón imprimir (se oculta al imprimir) */
        .no-print { text-align: center; margin-bottom: 20px; }
        .no-print button { background: #1e3a8a; color: #fff; border: none; padding: 8px 24px; border-radius: 6px; font-size: 13px; cursor: pointer; }
        .no-print a { margin-left: 10px; color: #1e3a8a; font-size: 13px; text-decoration: underline; }

        @media print {
            .no-print { display: none; }
            body { padding: 10px; }
            .page { max-width: 100%; }
        }
    </style>
</head>
<body>
<div class="page">

    <!-- Botones (solo pantalla) -->
    <div class="no-print">
        <button onclick="window.print()">Imprimir / Guardar PDF</button>
        <a href="{{ route('alumnos.show', $estudiante->CODIGO) }}">← Volver a la ficha</a>
    </div>

    <!-- Encabezado -->
    <div class="header">
        <div style="display:flex;align-items:center;gap:12px">
            <img src="{{ asset('images/escudoCBI.png') }}" alt="Logo" style="width:48px;height:auto;opacity:0.85">
            <div>
                <h1>Colegio Bilingüe Integral</h1>
                <p>Ficha de Matrícula del Estudiante</p>
            </div>
        </div>
        <div style="text-align:right">
            <p class="codigo">Código: {{ $estudiante->CODIGO }}</p>
            <p>{{ now()->format('d/m/Y') }}</p>
        </div>
    </div>

    <!-- Datos personales -->
    <div class="section">
        <div class="section-title">Datos del Estudiante</div>
        <div class="section-body">
            <div class="grid-4" style="margin-bottom:8px">
                <div class="field"><label>Apellido 1</label><p>{{ $estudiante->APELLIDO1 ?? '—' }}</p></div>
                <div class="field"><label>Apellido 2</label><p>{{ $estudiante->APELLIDO2 ?? '—' }}</p></div>
                <div class="field"><label>Nombre 1</label><p>{{ $estudiante->NOMBRE1 ?? '—' }}</p></div>
                <div class="field"><label>Nombre 2</label><p>{{ $estudiante->NOMBRE2 ?? '—' }}</p></div>
            </div>
            <div class="grid-4" style="margin-bottom:8px">
                <div class="field"><label>Grado</label><p>{{ $estudiante->GRADO ?? '—' }}</p></div>
                <div class="field"><label>Curso</label><p>{{ $estudiante->CURSO ?? '—' }}</p></div>
                <div class="field"><label>Sede</label><p>{{ $estudiante->SEDE ?? '—' }}</p></div>
                <div class="field"><label>Estado</label><p>{{ $estudiante->ESTADO ?? '—' }}</p></div>
            </div>
            <div class="grid-4" style="margin-bottom:8px">
                <div class="field"><label>Fecha de nacimiento</label><p>{{ $estudiante->FECH_NACIMIENTO ?? '—' }}</p></div>
                <div class="field"><label>Edad</label><p>{{ $edad !== null ? $edad . ' años' : '—' }}</p></div>
                <div class="field"><label>Lugar de nacimiento</label><p>{{ $estudiante->LUG_NACIMIENTO ?? '—' }}</p></div>
                <div class="field"><label>Lugar de expedición</label><p>{{ $estudiante->LUG_EXPED ?? '—' }}</p></div>
            </div>
            <div class="grid-4" style="margin-bottom:8px">
                <div class="field"><label>Tarjeta de identidad</label><p>{{ $estudiante->TAR_ID ?? '—' }}</p></div>
                <div class="field"><label>Registro civil</label><p>{{ $estudiante->REG_CIVIL ?? '—' }}</p></div>
                <div class="field"><label>Grupo sanguíneo / RH</label><p>{{ $estudiante->RH ?? '—' }}</p></div>
                <div class="field"><label>EPS</label><p>{{ $estudiante->EPS ?? '—' }}</p></div>
            </div>
            <div class="grid-4" style="margin-bottom:8px">
                <div class="field"><label>Alergias</label><p>{{ $estudiante->ALERG ?: 'Ninguna' }}</p></div>
                <div class="field"><label>Enfermedades</label><p>{{ $estudiante->ENFER ?: 'Ninguna' }}</p></div>
                <div class="field"><label>Uso de anteojos</label><p>{{ $estudiante->GAFAS ?? 'No' }}</p></div>
                <div class="field"><label>Estrato</label><p>{{ $estudiante->ESTRATO ?? '—' }}</p></div>
            </div>
            <div class="grid-4">
                <div class="field" style="grid-column: span 2"><label>Dirección</label><p>{{ $estudiante->DIRECCION ?? '—' }}</p></div>
                <div class="field"><label>Barrio</label><p>{{ $estudiante->BARRIO ?? '—' }}</p></div>
                <div class="field"><label>Acudiente</label><p>{{ $estudiante->ACUDIENTE ?? '—' }}</p></div>
            </div>
        </div>
    </div>

    @if($infoPadres)
    <!-- Info padres -->
    <div class="section">
        <div class="section-title">Información de Padres / Acudiente</div>
        <div class="section-body">
            <div class="grid-3">
                <!-- Madre -->
                <div>
                    <p style="font-weight:bold;color:#1e3a8a;border-bottom:1px solid #c7d2fe;padding-bottom:3px;margin-bottom:6px">Madre</p>
                    <div class="field"><label>Nombre</label><p>{{ $infoPadres->MADRE ?? '—' }}</p></div>
                    <div class="field" style="margin-top:4px"><label>Cédula</label><p>{{ $infoPadres->CC_MADRE ?? '—' }}</p></div>
                    <div class="field" style="margin-top:4px"><label>Celular</label><p>{{ $infoPadres->CEL_MADRE ?? '—' }}</p></div>
                    <div class="field" style="margin-top:4px"><label>Email</label><p>{{ $infoPadres->EMAIL_MADRE ?? '—' }}</p></div>
                </div>
                <!-- Padre -->
                <div>
                    <p style="font-weight:bold;color:#1e3a8a;border-bottom:1px solid #c7d2fe;padding-bottom:3px;margin-bottom:6px">Padre</p>
                    <div class="field"><label>Nombre</label><p>{{ $infoPadres->PADRE ?? '—' }}</p></div>
                    <div class="field" style="margin-top:4px"><label>Cédula</label><p>{{ $infoPadres->CC_PADRE ?? '—' }}</p></div>
                    <div class="field" style="margin-top:4px"><label>Celular</label><p>{{ $infoPadres->CEL_PADRE ?? '—' }}</p></div>
                    <div class="field" style="margin-top:4px"><label>Email</label><p>{{ $infoPadres->EMAIL_PADRE ?? '—' }}</p></div>
                </div>
                <!-- Acudiente -->
                <div>
                    <p style="font-weight:bold;color:#1e3a8a;border-bottom:1px solid #c7d2fe;padding-bottom:3px;margin-bottom:6px">Acudiente</p>
                    <div class="field"><label>Nombre</label><p>{{ $infoPadres->ACUD ?? '—' }}</p></div>
                    <div class="field" style="margin-top:4px"><label>Cédula</label><p>{{ $infoPadres->CC_ACUD ?? '—' }}</p></div>
                    <div class="field" style="margin-top:4px"><label>Celular</label><p>{{ $infoPadres->CEL_ACUD ?? '—' }}</p></div>
                    <div class="field" style="margin-top:4px"><label>Email</label><p>{{ $infoPadres->EMAIL_ACUD ?? '—' }}</p></div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($infoAcadem)
    <!-- Historial académico -->
    <div class="section">
        <div class="section-title">Historial Académico</div>
        <div class="section-body">
            <table>
                <thead>
                    <tr>
                        <th>Grado</th>
                        <th>Institución</th>
                        <th>Año</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach([
                        'Pre-Jardín' => ['INS_PJ','ANO_PJ'],
                        'Jardín'     => ['INS_J','ANO_J'],
                        'Transición' => ['INS_T','ANO_T'],
                        'Grado 1'    => ['INS_1','ANO_1'],
                        'Grado 2'    => ['INS_2','ANO_2'],
                        'Grado 3'    => ['INS_3','ANO_3'],
                        'Grado 4'    => ['INS_4','ANO_4'],
                        'Grado 5'    => ['INS_5','ANO_5'],
                        'Grado 6'    => ['INS_6','ANO_6'],
                        'Grado 7'    => ['INS_7','ANO_7'],
                        'Grado 8'    => ['INS_8','ANO_8'],
                        'Grado 9'    => ['INS_9','ANO_9'],
                        'Grado 10'   => ['INS_10','ANO_10'],
                        'Grado 11'   => ['INS_11','ANO_11'],
                    ] as $grado => $cols)
                        @php $ins = $infoAcadem->{$cols[0]} ?? null; $ano = $infoAcadem->{$cols[1]} ?? null; @endphp
                        @if($ins || $ano)
                        <tr>
                            <td style="font-weight:600">{{ $grado }}</td>
                            <td>{{ $ins ?? '—' }}</td>
                            <td>{{ $ano ?? '—' }}</td>
                        </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Pie de página legal -->
    <div class="footer">
        <p>Dando cumplimiento a lo dispuesto en la ley 1581 de 2012, "Por el cual se dictan disposiciones generales para la protección de datos personales" y de conformidad con lo señalado en el Decreto 1377 2013, con la firma de este documento manifiesto que he sido informado por el <strong>COLEGIO BILINGÜE INTEGRAL Y/O MARLENE DEHAQUIZ MORENO</strong> de que; El Colegio actuará directamente o a través de terceros como responsable del tratamiento de datos personales de los cuales somos titulares y los de mi(s) hijo(s) menor(es) que, conjunta o separadamente podrán recolectar, usar y tratar nuestros datos personales conforme la Política de Tratamiento de Datos Personales del Colegio.</p>
        <p>Acepto los planes y programas Establecidos por el Establecimiento en el Manual de Convivencia Incluido en el PEI de la Institución.</p>

        <div class="signatures">
            <div class="sign-line">Firma del padre o acudiente</div>
            <div class="sign-line">Nombre del estudiante</div>
        </div>
        <div class="signatures" style="margin-top:24px">
            <div class="sign-line">Rectoría</div>
            <div class="sign-line">Secretaría Académica</div>
        </div>
    </div>

</div>
</body>
</html>
