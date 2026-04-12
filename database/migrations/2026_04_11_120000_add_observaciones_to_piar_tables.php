<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('PIAR_CARACT_MAT', function (Blueprint $table) {
            $table->text('OBSERVACIONES')->nullable()->after('ESTADO');
        });
        Schema::table('PIAR_CARACT_DIR', function (Blueprint $table) {
            $table->text('OBSERVACIONES')->nullable()->after('ESTADO');
        });
        Schema::table('PIAR_MAT', function (Blueprint $table) {
            $table->text('OBSERVACIONES')->nullable()->after('ESTADO');
        });
    }

    public function down(): void
    {
        Schema::table('PIAR_CARACT_MAT', fn($t) => $t->dropColumn('OBSERVACIONES'));
        Schema::table('PIAR_CARACT_DIR', fn($t) => $t->dropColumn('OBSERVACIONES'));
        Schema::table('PIAR_MAT',        fn($t) => $t->dropColumn('OBSERVACIONES'));
    }
};
