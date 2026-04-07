<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('PIAR_DIAG', function (Blueprint $table) {
            $table->string('ALU_NOMBRES',     100)->nullable()->after('PERSONA_DIL');
            $table->string('ALU_APELLIDOS',   100)->nullable()->after('ALU_NOMBRES');
            $table->string('ALU_TIPO_DOC',      5)->nullable()->after('ALU_APELLIDOS');
            $table->string('ALU_TIPO_DOC_OTRO', 50)->nullable()->after('ALU_TIPO_DOC');
            $table->string('ALU_NUM_ID',        20)->nullable()->after('ALU_TIPO_DOC_OTRO');
            $table->string('ALU_FECH_NAC',      50)->nullable()->after('ALU_NUM_ID');
            $table->string('ALU_EDAD',          10)->nullable()->after('ALU_FECH_NAC');
            $table->string('ALU_LUG_NAC',       50)->nullable()->after('ALU_EDAD');
            $table->string('ALU_CURSO_INFO',   150)->nullable()->after('ALU_LUG_NAC');
            $table->string('ALU_DEPTO',         50)->nullable()->after('ALU_CURSO_INFO');
            $table->string('ALU_DIRECCION',    150)->nullable()->after('ALU_DEPTO');
            $table->string('ALU_BARRIO',        50)->nullable()->after('ALU_DIRECCION');
            $table->string('ALU_ESTRATO',        5)->nullable()->after('ALU_BARRIO');
            $table->string('ALU_RH',             5)->nullable()->after('ALU_ESTRATO');
            $table->string('ALU_ALERG',        300)->nullable()->after('ALU_RH');
            $table->tinyInteger('ALU_GAFAS')->nullable()->after('ALU_ALERG');
        });
    }

    public function down(): void
    {
        Schema::table('PIAR_DIAG', function (Blueprint $table) {
            $table->dropColumn([
                'ALU_NOMBRES', 'ALU_APELLIDOS', 'ALU_TIPO_DOC', 'ALU_TIPO_DOC_OTRO',
                'ALU_NUM_ID', 'ALU_FECH_NAC', 'ALU_EDAD', 'ALU_LUG_NAC',
                'ALU_CURSO_INFO', 'ALU_DEPTO', 'ALU_DIRECCION', 'ALU_BARRIO',
                'ALU_ESTRATO', 'ALU_RH', 'ALU_ALERG', 'ALU_GAFAS',
            ]);
        });
    }
};
