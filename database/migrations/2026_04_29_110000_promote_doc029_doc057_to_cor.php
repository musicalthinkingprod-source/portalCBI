<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $reemplazos = [
            'DOC029' => 'COR001', // Willy Rengifo - Coordinador Academico
            'DOC057' => 'COR002', // Martha Babativa - Coordinadora Convivencia
        ];

        // Tablas con columna codigo_doc en minusculas
        $tablasMin = [
            'asistencia_docentes',
            'permisos_docentes',
            'notificaciones',
            'planilla_columnas',
            'planilla_columnas_historial',
            'solicitudes_correccion',
        ];

        // Tablas con columna CODIGO_DOC en mayusculas
        $tablasMay = [
            'ASIGNACION_PCM',
            'CODIGOS_DOC',
            'Derroteros',
            'NOTAS_2024',
            'NOTAS_2025',
            'NOTAS_2026',
            'NOTAS_ENGLISH_ACQ',
            'PIAR_CARACT_DIR',
            'PIAR_CARACT_MAT',
            'Salvavidas',
            'vigilancias',
        ];

        foreach ($reemplazos as $viejo => $nuevo) {
            foreach ($tablasMin as $t) {
                DB::table($t)->where('codigo_doc', $viejo)->update(['codigo_doc' => $nuevo]);
            }
            foreach ($tablasMay as $t) {
                DB::table($t)->where('CODIGO_DOC', $viejo)->update(['CODIGO_DOC' => $nuevo]);
            }
        }

        // Actualizar PROFILE de Willy y Martha en PRINUSERS
        DB::table('PRINUSERS')->where('USER', 'academic_coordination')->update(['PROFILE' => 'Coord']);
        DB::table('PRINUSERS')->where('USER', 'marthaconvivencia')->update(['PROFILE' => 'Coord']);
    }

    public function down(): void
    {
        $reemplazos = [
            'COR001' => 'DOC029',
            'COR002' => 'DOC057',
        ];

        $tablasMin = [
            'asistencia_docentes',
            'permisos_docentes',
            'notificaciones',
            'planilla_columnas',
            'planilla_columnas_historial',
            'solicitudes_correccion',
        ];

        $tablasMay = [
            'ASIGNACION_PCM',
            'CODIGOS_DOC',
            'Derroteros',
            'NOTAS_2024',
            'NOTAS_2025',
            'NOTAS_2026',
            'NOTAS_ENGLISH_ACQ',
            'PIAR_CARACT_DIR',
            'PIAR_CARACT_MAT',
            'Salvavidas',
            'vigilancias',
        ];

        foreach ($reemplazos as $nuevo => $viejo) {
            foreach ($tablasMin as $t) {
                DB::table($t)->where('codigo_doc', $nuevo)->update(['codigo_doc' => $viejo]);
            }
            foreach ($tablasMay as $t) {
                DB::table($t)->where('CODIGO_DOC', $nuevo)->update(['CODIGO_DOC' => $viejo]);
            }
        }

        DB::table('PRINUSERS')->where('USER', 'academic_coordination')->update(['PROFILE' => 'DOC029']);
        DB::table('PRINUSERS')->where('USER', 'marthaconvivencia')->update(['PROFILE' => 'DOC057']);
    }
};
