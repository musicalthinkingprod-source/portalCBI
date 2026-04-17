<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('PIAR_MAT', function (Blueprint $table) {
            $table->string('ESTADO_CASERO', 20)->default('pendiente')->after('FECHA_APROBACION');
            $table->text('OBSERVACIONES_CASERO')->nullable()->after('ESTADO_CASERO');
            $table->string('APROBADO_CASERO_POR', 50)->nullable()->after('OBSERVACIONES_CASERO');
            $table->date('FECHA_APROB_CASERO')->nullable()->after('APROBADO_CASERO_POR');
        });
    }

    public function down(): void
    {
        Schema::table('PIAR_MAT', function (Blueprint $table) {
            $table->dropColumn(['ESTADO_CASERO', 'OBSERVACIONES_CASERO', 'APROBADO_CASERO_POR', 'FECHA_APROB_CASERO']);
        });
    }
};
