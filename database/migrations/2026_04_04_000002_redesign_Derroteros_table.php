<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('Derroteros')->truncate();

        Schema::table('Derroteros', function (Blueprint $table) {
            $table->dropColumn(['MATERIA', 'HORARIO', 'DOCENTE']);
        });

        Schema::table('Derroteros', function (Blueprint $table) {
            $table->id()->first();
            $table->integer('CODIGO_MAT')->after('CODIGO_ALUM');
            $table->tinyInteger('PERIODO')->after('CODIGO_MAT');
            $table->smallInteger('ANIO')->after('PERIODO');
            $table->string('RESOLUCION', 20)->default('PENDIENTE')->after('ANIO');
            $table->float('NOTA_RECUPERACION')->nullable()->after('RESOLUCION');
            $table->string('HORARIO', 300)->nullable()->after('NOTA_RECUPERACION');
            $table->string('CODIGO_DOC', 25)->nullable()->after('HORARIO');
        });
    }

    public function down(): void
    {
        Schema::table('Derroteros', function (Blueprint $table) {
            $table->dropColumn(['id', 'CODIGO_MAT', 'PERIODO', 'ANIO', 'RESOLUCION', 'NOTA_RECUPERACION', 'HORARIO', 'CODIGO_DOC']);
        });
        Schema::table('Derroteros', function (Blueprint $table) {
            $table->string('MATERIA', 70);
            $table->string('HORARIO', 200);
            $table->string('DOCENTE', 70);
        });
    }
};
