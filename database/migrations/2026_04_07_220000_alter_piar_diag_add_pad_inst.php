<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('PIAR_DIAG', function (Blueprint $table) {
            $table->string('PAD_MADRE',   100)->nullable()->after('OCUP_MADRE');
            $table->string('PAD_PADRE',   100)->nullable()->after('PAD_MADRE');
            $table->string('INST_NOMBRE', 100)->nullable()->after('DISTANCIA');
            $table->string('INST_SEDE',    50)->nullable()->after('INST_NOMBRE');
        });
    }

    public function down(): void
    {
        Schema::table('PIAR_DIAG', function (Blueprint $table) {
            $table->dropColumn(['PAD_MADRE', 'PAD_PADRE', 'INST_NOMBRE', 'INST_SEDE']);
        });
    }
};
