<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. FECHAS: renombrar PERIODO → CODIGO_FECHA y hacerlo primary key
        if (Schema::hasColumn('FECHAS', 'PERIODO') && !Schema::hasColumn('FECHAS', 'CODIGO_FECHA')) {
            DB::statement('ALTER TABLE FECHAS CHANGE `PERIODO` `CODIGO_FECHA` VARCHAR(10) NOT NULL');
        }

        // 2. NOTAS_ENGLISH_ACQ: agregar columnas que faltan
        if (!Schema::hasColumn('NOTAS_ENGLISH_ACQ', 'id')) {
            DB::statement('ALTER TABLE NOTAS_ENGLISH_ACQ ADD COLUMN `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY FIRST');
        }
        Schema::table('NOTAS_ENGLISH_ACQ', function (Blueprint $table) {
            if (!Schema::hasColumn('NOTAS_ENGLISH_ACQ', 'ANIO')) {
                $table->smallInteger('ANIO')->after('CODIGO_DOC');
            }
            if (!Schema::hasColumn('NOTAS_ENGLISH_ACQ', 'PERIODO')) {
                $table->tinyInteger('PERIODO')->unsigned()->after('ANIO');
            }
            if (!Schema::hasColumn('NOTAS_ENGLISH_ACQ', 'FECHA')) {
                $table->timestamp('FECHA')->useCurrent()->after('PERIODO');
            }
        });
    }

    public function down(): void
    {
        Schema::table('NOTAS_ENGLISH_ACQ', function (Blueprint $table) {
            $table->dropColumn(['FECHA', 'PERIODO', 'ANIO']);
        });
        if (Schema::hasColumn('NOTAS_ENGLISH_ACQ', 'id')) {
            DB::statement('ALTER TABLE NOTAS_ENGLISH_ACQ DROP PRIMARY KEY, DROP COLUMN `id`');
        }

        if (Schema::hasColumn('FECHAS', 'CODIGO_FECHA') && !Schema::hasColumn('FECHAS', 'PERIODO')) {
            DB::statement('ALTER TABLE FECHAS DROP PRIMARY KEY');
            DB::statement('ALTER TABLE FECHAS CHANGE `CODIGO_FECHA` `PERIODO` INT NOT NULL');
        }
    }
};
