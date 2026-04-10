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
            // 'interno' = solo personal interno | 'todos' = padres, estudiantes e internos
            $table->enum('visibilidad', ['interno', 'todos'])->default('interno')->after('evento');
        });
    }

    public function down(): void
    {
        Schema::table('calendario_academico', function (Blueprint $table) {
            $table->dropColumn('visibilidad');
        });
    }
};
