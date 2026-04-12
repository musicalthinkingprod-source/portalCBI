<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('PIAR_DIAG', function (Blueprint $table) {
            $table->date('FECHA_P1')->nullable()->after('INST_SEDE');
            $table->string('PERSONA_P1', 150)->nullable()->after('FECHA_P1');
            $table->date('FECHA_P2')->nullable()->after('PERSONA_P1');
            $table->string('PERSONA_P2', 150)->nullable()->after('FECHA_P2');
            $table->date('FECHA_P3')->nullable()->after('PERSONA_P2');
            $table->string('PERSONA_P3', 150)->nullable()->after('FECHA_P3');
        });
    }

    public function down(): void
    {
        Schema::table('PIAR_DIAG', function (Blueprint $table) {
            $table->dropColumn(['FECHA_P1', 'PERSONA_P1', 'FECHA_P2', 'PERSONA_P2', 'FECHA_P3', 'PERSONA_P3']);
        });
    }
};
