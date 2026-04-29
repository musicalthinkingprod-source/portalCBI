<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Renombra la columna codigo_doc -> codigo_emp (y CODIGO_DOC -> CODIGO_EMP)
     * en todas las tablas que la usan. La columna ya contiene tanto DOC*** como
     * COR*** (y a futuro DIR/ADM/SSG/COC), por lo que el nombre legacy "doc" miente.
     */
    public function up(): void
    {
        $tablasMin = [
            'asistencia_docentes',
            'notificaciones',
            'permisos_docentes',
            'planilla_columnas',
            'planilla_columnas_historial',
            'solicitudes_correccion',
        ];

        foreach ($tablasMin as $t) {
            if (Schema::hasColumn($t, 'codigo_doc')) {
                Schema::table($t, function (Blueprint $table) {
                    $table->renameColumn('codigo_doc', 'codigo_emp');
                });
            }
        }

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

        foreach ($tablasMay as $t) {
            if (Schema::hasColumn($t, 'CODIGO_DOC')) {
                Schema::table($t, function (Blueprint $table) {
                    $table->renameColumn('CODIGO_DOC', 'CODIGO_EMP');
                });
            }
        }
    }

    public function down(): void
    {
        $tablasMin = [
            'asistencia_docentes',
            'notificaciones',
            'permisos_docentes',
            'planilla_columnas',
            'planilla_columnas_historial',
            'solicitudes_correccion',
        ];

        foreach ($tablasMin as $t) {
            if (Schema::hasColumn($t, 'codigo_emp')) {
                Schema::table($t, function (Blueprint $table) {
                    $table->renameColumn('codigo_emp', 'codigo_doc');
                });
            }
        }

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

        foreach ($tablasMay as $t) {
            if (Schema::hasColumn($t, 'CODIGO_EMP')) {
                Schema::table($t, function (Blueprint $table) {
                    $table->renameColumn('CODIGO_EMP', 'CODIGO_DOC');
                });
            }
        }
    }
};
