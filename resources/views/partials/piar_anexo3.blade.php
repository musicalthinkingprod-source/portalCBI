{{-- ══════════════════════════════════════════════════════════════════════ --}}
{{-- ANEXO 3 – ACTA DE ACUERDO Y PLAN CASERO                                --}}
{{-- Variables esperadas:                                                   --}}
{{--   $estudiante, $piarDiag, $nombreCompleto, $apellidos, $tipoDoc,        --}}
{{--   $numId, $edad, $grado, $curso, $sede                                  --}}
{{--   $planes (collection con NOMBRE_MAT, NOMBRE_DOC, ESTRAG_CASERA, FREC_CASERA) --}}
{{--   $docentesElaboran (collection con NOMBRE_DOC, MATERIA)                --}}
{{--   $orientadora, $nombreMadre, $nombrePadre                              --}}
{{-- ══════════════════════════════════════════════════════════════════════ --}}

<table border="1" cellspacing="0" cellpadding="4" style="border-collapse:collapse;width:100%;page-break-before:always;break-before:page;">
<tr>
<td colspan="5" style="background-color:#F2F2F2"><p style="text-align:center;margin-top:6pt;margin-bottom:6pt"><span style="color:#0070C0;font-size:12pt"><b>ACTA DE ACUERDO</b></span></p><p style="text-align:center;margin-top:6pt;margin-bottom:6pt"><span style="color:#0070C0;font-size:12pt"><b>Plan Individual de Ajustes Razonables – PIAR – ANEXO 3</b></span></p></td>
</tr>
<tr>
<td colspan="2"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Fecha: </b></span><span style="font-size:12pt">Año {{ date('Y') }}</span></p></td>
<td colspan="3"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Institución educativa y Sede:</b></span></p><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt">Colegio Bilingüe Integral.{{ $sede ? ' ' . $sede . '.' : '' }}</span></p></td>
</tr>
<tr>
<td colspan="2"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Nombre del estudiante: </b></span><span style="font-size:12pt">{{ $nombreCompleto }} {{ $apellidos }}</span></p></td>
<td colspan="2"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Documento de Identificación: </b></span><span style="font-size:12pt">{{ $tipoDoc }} {{ $numId }}</span></p></td>
<td><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Edad: </b>{{ $edad }} años</span></p><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Grado: </b>{{ $grado }}{{ $curso ? ' – ' . $curso : '' }}</span></p></td>
</tr>
<tr>
<td colspan="2" rowspan="{{ max(2, ($docentesElaboran ?? collect())->count() + 1) }}" style="vertical-align:top"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Nombres equipo directivos y de docentes</b></span></p></td>
<td colspan="2"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>DIRECTORA DE LA INSTITUCIÓN.</b></span></p></td>
<td><p style="text-align:center;margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt">Ángela Vega.</span></p></td>
</tr>
<tr>
<td colspan="2"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>ORIENTADORA.</b></span></p></td>
<td><p style="text-align:center;margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt">{{ $orientadora ?? 'Jennifer Andrea Martínez Londoño' }}</span></p></td>
</tr>
@foreach(($docentesElaboran ?? collect()) as $d)
<tr>
<td colspan="2"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>DOCENTE{{ !empty($d->MATERIA) ? ' – ' . strtoupper($d->MATERIA) : '' }}.</b></span></p></td>
<td><p style="text-align:center;margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt">{{ $d->NOMBRE_DOC }}</span></p></td>
</tr>
@endforeach
<tr>
<td colspan="2" rowspan="2" style="vertical-align:top"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Nombres familia del estudiante</b></span></p></td>
<td colspan="2"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt">{{ $nombreMadre ?: '' }}</span></p></td>
<td><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Parentesco: </b>Mamá.</span></p></td>
</tr>
<tr>
<td colspan="2"><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt">{{ $nombrePadre ?: '' }}</span></p></td>
<td><p style="margin-top:6pt;margin-bottom:6pt"><span style="font-size:12pt"><b>Parentesco: </b>Papá.</span></p></td>
</tr>
<tr>
<td colspan="5"><p style="text-align:justify;margin-top:6pt;margin-bottom:6pt;line-height:1.5"><span style="font-size:11pt">Según el Decreto 1421 de 2017 la educación inclusiva es un proceso permanente que reconoce, valora y responde a la diversidad de características, intereses, posibilidades y expectativas de los estudiantes para promover su desarrollo, aprendizaje y participación, en un ambiente de aprendizaje común, sin discriminación o exclusión. La inclusión solo es posible cuando se unen los esfuerzos del colegio, el estudiante y la familia. De ahí la importancia de formalizar con las firmas, la presente Acta Acuerdo.</span></p>
<p style="text-align:justify;margin-top:6pt;margin-bottom:6pt;line-height:1.5"><span style="font-size:11pt"><b>El Establecimiento Educativo</b> ha realizado la valoración y definido los ajustes razonables que facilitarán al estudiante su proceso educativo.</span></p>
<p style="text-align:justify;margin-top:6pt;margin-bottom:6pt;line-height:1.5"><span style="font-size:11pt"><b>La Familia se compromete a</b> cumplir y firmar los compromisos señalados en el PIAR y en las actas de acuerdo, para fortalecer los procesos escolares del estudiante y en particular a:</span></p></td>
</tr>
<tr>
<td colspan="5" style="background-color:#F2F2F2"><p style="text-align:center;margin-top:6pt;margin-bottom:6pt;line-height:1.5"><span style="font-size:11pt"><u><b>Compromisos específicos para implementar en el aula y en casa:</b></u></span></p>
<p style="text-align:justify;margin-top:4pt;margin-bottom:4pt;line-height:1.5"><span style="font-size:10pt">Asistir todos los días al colegio en los horarios establecidos por la institución como parte de la garantía del derecho a la educación.</span></p>
<p style="text-align:justify;margin-top:4pt;margin-bottom:4pt;line-height:1.5"><span style="font-size:10pt">Organizar las rutinas diarias de alimentación, juego, tiempos de descanso y rutinas de sueño con el objetivo de sincronizar de manera armónica toda la jornada del estudiante.</span></p>
<p style="text-align:justify;margin-top:4pt;margin-bottom:4pt;line-height:1.5"><span style="font-size:10pt">Favorecer en el estudiante la adquisición de la autonomía e independencia permitiéndole realizar algunas tareas de manera individual.</span></p>
<p style="text-align:justify;margin-top:4pt;margin-bottom:4pt;line-height:1.5"><span style="font-size:10pt">Instaurar una rutina diaria en la casa (actividades escolares, juegos libres o vocacionales).</span></p>
<p style="text-align:justify;margin-top:4pt;margin-bottom:4pt;line-height:1.5"><span style="font-size:10pt">El entorno familiar debe ser corresponsable, participando del proceso orientado con compromiso y empoderamiento, incluyendo acompañamiento en reuniones escolares y talleres de padres.</span></p>
<p style="text-align:justify;margin-top:4pt;margin-bottom:4pt;line-height:1.5"><span style="font-size:10pt">Unificar normas y límites con todos los miembros de la familia y entre el equipo de docentes.</span></p>
<p style="text-align:justify;margin-top:4pt;margin-bottom:4pt;line-height:1.5"><span style="font-size:10pt">Utilizar órdenes breves y directas en tono positivo o neutro, manteniendo la calma y siendo concretos según la situación.</span></p>
<p style="text-align:justify;margin-top:4pt;margin-bottom:4pt;line-height:1.5"><span style="font-size:10pt">La familia debe apoyar y orientar al estudiante con apoyos moderados y bien administrados, sin realizar las tareas por él o ella.</span></p>
<p style="text-align:justify;margin-top:4pt;margin-bottom:4pt;line-height:1.5"><span style="font-size:10pt">Asistir a las reuniones programadas por la institución educativa según sea la situación o contexto.</span></p>
<p style="text-align:justify;margin-top:4pt;margin-bottom:4pt;line-height:1.5"><span style="font-size:10pt">Continuar con la implementación del Diseño Universal de Aprendizaje (DUA) bajo sus tres principios: múltiples medios de Representación, de Acción y Expresión, y de Implicación.</span></p></td>
</tr>
<tr>
<td colspan="5"><p style="text-align:center;margin-top:8pt;margin-bottom:8pt"><span style="color:#0070C0;font-size:12pt"><u><b>Y en casa apoyará con las siguientes actividades: Plan Casero.</b></u></span></p></td>
</tr>
<tr>
<td style="width:18%;background-color:#F2F2F2"><p style="text-align:center;margin-top:6pt;margin-bottom:6pt"><span style="font-size:11pt"><b>Asignatura</b></span></p></td>
<td colspan="2" style="background-color:#F2F2F2"><p style="text-align:center;margin-top:6pt;margin-bottom:6pt"><span style="font-size:11pt"><b>Descripción de la estrategia.</b></span></p><p style="text-align:center;margin-top:2pt;margin-bottom:6pt"><span style="color:#7F7F7F;font-size:9pt">Descripción y materiales de apoyo didácticos, concretos o virtuales necesarios.</span></p></td>
<td colspan="2" style="background-color:#F2F2F2"><p style="text-align:center;margin-top:6pt;margin-bottom:6pt"><span style="font-size:11pt"><b>Frecuencia</b></span></p><p style="text-align:center;margin-top:2pt;margin-bottom:6pt"><span style="color:#7F7F7F;font-size:9pt">Diaria / Semanal / Permanente.</span></p></td>
</tr>
@forelse(($planes ?? collect()) as $pl)
<tr>
<td><p style="text-align:center;margin-top:4pt;margin-bottom:4pt;line-height:1.3"><span style="font-size:11pt"><b>{{ $pl->NOMBRE_MAT }}</b></span></p>@if(!empty($pl->NOMBRE_DOC))<p style="text-align:center;margin-top:2pt;margin-bottom:4pt;line-height:1.2"><span style="font-size:9pt;color:#555">{{ $pl->NOMBRE_DOC }}</span></p>@endif</td>
<td colspan="2"><p style="text-align:justify;margin-top:4pt;margin-bottom:4pt;line-height:1.3;white-space:pre-wrap"><span style="font-size:11pt">{{ $pl->ESTRAG_CASERA }}</span></p></td>
<td colspan="2"><p style="text-align:justify;margin-top:4pt;margin-bottom:4pt;line-height:1.3;white-space:pre-wrap"><span style="font-size:11pt">{{ $pl->FREC_CASERA }}</span></p></td>
</tr>
@empty
<tr><td colspan="5"><p style="text-align:center;margin-top:8pt;margin-bottom:8pt;line-height:1.5"><span style="font-size:10pt;color:#888"><i>Sin estrategias de Plan Casero diligenciadas.</i></span></p></td></tr>
@endforelse
<tr>
<td colspan="5"><p style="margin-top:6pt;margin-bottom:6pt;line-height:1.5"><span style="font-size:11pt"><b>Firma de los Actores comprometidos:</b></span><span style="color:#7F7F7F;font-size:11pt"> Nombre y Firma.</span></p></td>
</tr>
<tr><td colspan="5" style="height:60pt"><p style="margin-top:6pt;margin-bottom:6pt;line-height:1.5"><span style="color:#8496B0;font-size:11pt">Estudiante</span></p></td></tr>
<tr><td colspan="5" style="height:60pt"><p style="margin-top:6pt;margin-bottom:6pt;line-height:1.5"><span style="color:#8496B0;font-size:11pt">Acudiente / familia</span></p></td></tr>
<tr><td colspan="5" style="height:60pt"><p style="margin-top:6pt;margin-bottom:6pt;line-height:1.5"><span style="color:#8496B0;font-size:11pt">Docente de Apoyo</span></p></td></tr>
<tr><td colspan="5" style="height:60pt"><p style="margin-top:6pt;margin-bottom:6pt;line-height:1.5"><span style="color:#8496B0;font-size:11pt">Docentes de Asignatura / Director(a) de Grupo</span></p></td></tr>
<tr><td colspan="5" style="height:60pt"><p style="margin-top:6pt;margin-bottom:6pt;line-height:1.5"><span style="color:#8496B0;font-size:11pt">Profesional de mediación o apoyo</span></p></td></tr>
<tr><td colspan="5" style="height:60pt"><p style="margin-top:6pt;margin-bottom:6pt;line-height:1.5"><span style="color:#8496B0;font-size:11pt">Directivo docente</span></p></td></tr>
</table>

<p style="margin-top:6pt;margin-bottom:6pt;line-height:1.5">&nbsp;</p>
<p style="margin-top:6pt;margin-bottom:6pt;line-height:1.5"><span style="color:#8496B0;font-size:11pt"><i>Nota: Se adjuntan actas de reuniones, actas de acuerdo y corresponsabilidad entre familia e institución.</i></span></p>
