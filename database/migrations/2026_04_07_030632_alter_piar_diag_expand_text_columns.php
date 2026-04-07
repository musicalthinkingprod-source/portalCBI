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
        Schema::table('PIAR_DIAG', function (Blueprint $table) {
            // Campos que pueden llevar texto largo — se cambian a TEXT
            $table->text('DIAGNOSTICO')->nullable()->change();
            $table->text('LUGAR_DIL')->nullable()->change();
            $table->text('PERSONA_DIL')->nullable()->change();
            $table->text('EMERG')->nullable()->change();
            $table->text('FREC_PROTEG')->nullable()->change();
            $table->text('DIAGMED_WHICH')->nullable()->change();
            $table->text('TERAP_WHICH1')->nullable()->change();
            $table->text('TERAP_FREC1')->nullable()->change();
            $table->text('TERAP_WHICH2')->nullable()->change();
            $table->text('TERAP_FREC2')->nullable()->change();
            $table->text('TERAP_WHICH3')->nullable()->change();
            $table->text('TERAP_FREC3')->nullable()->change();
            $table->text('ENFERPAR_WHICH')->nullable()->change();
            $table->text('MEDIC_FREC')->nullable()->change();
            $table->text('MOVILID_WHICH')->nullable()->change();
            $table->text('OCUP_MADRE')->nullable()->change();
            $table->text('OCUP_PADRE')->nullable()->change();
            $table->text('NOMB_CUID')->nullable()->change();
            $table->text('PERS_VIVE')->nullable()->change();
            $table->text('CRIANZA')->nullable()->change();
            $table->text('HOG_PROTEC_WHICH')->nullable()->change();
            $table->text('HOG_SUB_WHICH')->nullable()->change();
            $table->text('INTITUPREV_WHICH')->nullable()->change();
            $table->text('OBSERV')->nullable()->change();
            $table->text('INFOPIAR_WHICH')->nullable()->change();
            $table->text('COMPLEM_WHICH')->nullable()->change();
            $table->text('TRANSPOR')->nullable()->change();
            $table->text('REGIS')->nullable()->change();
        });
    }

    public function down(): void
    {
        //
    }
};
