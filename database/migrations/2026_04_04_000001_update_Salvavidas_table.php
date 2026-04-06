<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Vaciar la tabla antes de alterar (datos viejos sin ANIO no son útiles)
        DB::table('Salvavidas')->truncate();

        Schema::table('Salvavidas', function (Blueprint $table) {
            $table->id()->first();
            $table->smallInteger('ANIO')->after('PERIODO');
            $table->string('CODIGO_DOC')->after('ANIO');
        });
    }

    public function down(): void
    {
        Schema::table('Salvavidas', function (Blueprint $table) {
            $table->dropColumn(['id', 'ANIO', 'CODIGO_DOC']);
        });
    }
};
