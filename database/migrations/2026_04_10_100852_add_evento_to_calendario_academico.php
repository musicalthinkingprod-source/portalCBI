<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('calendario_academico', function (Blueprint $table) {
            $table->string('evento', 200)->nullable()->after('anio');
        });
    }

    public function down(): void
    {
        Schema::table('calendario_academico', function (Blueprint $table) {
            $table->dropColumn('evento');
        });
    }
};
