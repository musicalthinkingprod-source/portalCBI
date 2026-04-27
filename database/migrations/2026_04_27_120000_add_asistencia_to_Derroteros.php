<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Derroteros', function (Blueprint $table) {
            // PRESENTO | NO_PRESENTO | NULL (sin marcar todavía)
            $table->string('ASISTENCIA', 20)->nullable()->after('RESOLUCION');
        });
    }

    public function down(): void
    {
        Schema::table('Derroteros', function (Blueprint $table) {
            $table->dropColumn('ASISTENCIA');
        });
    }
};
