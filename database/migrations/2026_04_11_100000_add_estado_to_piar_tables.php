<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PIAR_CARACT_MAT
        Schema::table('PIAR_CARACT_MAT', function (Blueprint $table) {
            $table->string('ESTADO', 20)->default('pendiente')->after('updated_at');
            $table->string('APROBADO_POR', 50)->nullable()->after('ESTADO');
            $table->date('FECHA_APROBACION')->nullable()->after('APROBADO_POR');
        });

        // PIAR_CARACT_DIR
        Schema::table('PIAR_CARACT_DIR', function (Blueprint $table) {
            $table->string('ESTADO', 20)->default('pendiente')->after('updated_at');
            $table->string('APROBADO_POR', 50)->nullable()->after('ESTADO');
            $table->date('FECHA_APROBACION')->nullable()->after('APROBADO_POR');
        });

        // PIAR_MAT
        Schema::table('PIAR_MAT', function (Blueprint $table) {
            $table->string('ESTADO', 20)->default('pendiente')->after('FREC_CASERA');
            $table->string('APROBADO_POR', 50)->nullable()->after('ESTADO');
            $table->date('FECHA_APROBACION')->nullable()->after('APROBADO_POR');
        });
    }

    public function down(): void
    {
        Schema::table('PIAR_CARACT_MAT', function (Blueprint $table) {
            $table->dropColumn(['ESTADO', 'APROBADO_POR', 'FECHA_APROBACION']);
        });
        Schema::table('PIAR_CARACT_DIR', function (Blueprint $table) {
            $table->dropColumn(['ESTADO', 'APROBADO_POR', 'FECHA_APROBACION']);
        });
        Schema::table('PIAR_MAT', function (Blueprint $table) {
            $table->dropColumn(['ESTADO', 'APROBADO_POR', 'FECHA_APROBACION']);
        });
    }
};
