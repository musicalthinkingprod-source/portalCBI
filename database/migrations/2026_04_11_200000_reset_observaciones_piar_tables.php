<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['PIAR_CARACT_MAT', 'PIAR_CARACT_DIR', 'PIAR_MAT'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropColumn('OBSERVACIONES');
            });
            Schema::table($tabla, function (Blueprint $table) {
                $table->text('OBSERVACIONES')->nullable()->after('ESTADO');
            });
        }
    }

    public function down(): void {}
};
