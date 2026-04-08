<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Deshabilitar strict mode para esta sesión (permite fechas 0000-00-00)
        DB::statement("SET SESSION sql_mode = ''");

        // ── 1. PIAR_CARACT_MAT ───────────────────────────────────────────────
        // Ya sin duplicados — sólo agregar PK compuesta
        Schema::table('PIAR_CARACT_MAT', function (Blueprint $table) {
            $table->primary(['CODIGO_ALUM', 'CODIGO_MAT']);
        });

        // ── 2. PIAR_MAT ──────────────────────────────────────────────────────
        // Consolidar duplicados: guardar el registro con más contenido por (CODIGO_ALUM, CODIGO_MAT)
        DB::statement("
            CREATE TEMPORARY TABLE _tmp_piarmat
            SELECT CODIGO_ALUM,
                   CODIGO_MAT,
                   MAX(COALESCE(BARRERAS,''))       AS BARRERAS,
                   MAX(COALESCE(LOGRO1,''))          AS LOGRO1,
                   MAX(COALESCE(DIDACT1,''))         AS DIDACT1,
                   MAX(COALESCE(EVAL1,''))           AS EVAL1,
                   MAX(COALESCE(LOGRO2,''))          AS LOGRO2,
                   MAX(COALESCE(DIDACT2,''))         AS DIDACT2,
                   MAX(COALESCE(EVAL2,''))           AS EVAL2,
                   MAX(COALESCE(LOGRO3,''))          AS LOGRO3,
                   MAX(COALESCE(DIDACT3,''))         AS DIDACT3,
                   MAX(COALESCE(EVAL3,''))           AS EVAL3,
                   MAX(COALESCE(LOGRO4,''))          AS LOGRO4,
                   MAX(COALESCE(DIDACT4,''))         AS DIDACT4,
                   MAX(COALESCE(EVAL4,''))           AS EVAL4,
                   MAX(COALESCE(ESTRAG_CASERA,''))  AS ESTRAG_CASERA,
                   MAX(COALESCE(FREC_CASERA,''))    AS FREC_CASERA
            FROM PIAR_MAT
            GROUP BY CODIGO_ALUM, CODIGO_MAT
        ");
        DB::statement("DELETE FROM PIAR_MAT");
        DB::statement("INSERT INTO PIAR_MAT SELECT * FROM _tmp_piarmat");
        DB::statement("DROP TEMPORARY TABLE _tmp_piarmat");

        // Agregar clave primaria compuesta
        Schema::table('PIAR_MAT', function (Blueprint $table) {
            $table->primary(['CODIGO_ALUM', 'CODIGO_MAT']);
        });
    }

    public function down(): void
    {
        Schema::table('PIAR_CARACT_MAT', function (Blueprint $table) {
            $table->dropPrimary();
        });
        Schema::table('PIAR_MAT', function (Blueprint $table) {
            $table->dropPrimary();
        });
    }
};
