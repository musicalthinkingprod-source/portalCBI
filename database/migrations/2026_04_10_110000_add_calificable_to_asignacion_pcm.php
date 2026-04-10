<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ASIGNACION_PCM', function (Blueprint $table) {
            $table->tinyInteger('calificable')->default(1)->after('IHS')
                  ->comment('1 = lleva nota, 0 = solo aparece en horario');
        });
    }

    public function down(): void
    {
        Schema::table('ASIGNACION_PCM', function (Blueprint $table) {
            $table->dropColumn('calificable');
        });
    }
};
