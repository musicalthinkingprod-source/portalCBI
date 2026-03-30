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
        Schema::create('PIAR_DIAG', function (Blueprint $table) {
            $table->integer('CODIGO_ALUM');
            $table->string('DIAGNOSTICO', 100);
            $table->string('LUGAR_DIL', 100);
            $table->string('PERSONA_DIL', 100);
            $table->string('MUNICIPIO', 50);
            $table->integer('TELEFONO');
            $table->string('EMAIL', 50);
            $table->boolean('PROTEC')->default(false);
            $table->string('PROTEC_WHICH', 50);
            $table->string('ASPIRA', 50);
            $table->string('REGIS', 100);
            $table->boolean('ETNIC')->default(false);
            $table->string('ETNIC_WHICH', 50);
            $table->boolean('CONFARM')->default(false);
            $table->string('CONFARM_REG', 50);
            $table->boolean('SALUD')->default(true);
            $table->string('EPS', 50);
            $table->boolean('CONT')->default(true);
            $table->string('EMERG', 100);
            $table->boolean('PROTEGIDO')->default(false);
            $table->string('FREC_PROTEG', 50);
            $table->boolean('DIAGMED');
            $table->string('DIAGMED_WHICH', 200);
            $table->boolean('TERAP');
            $table->string('TERAP_WHICH1', 100);
            $table->string('TERAP_FREC1', 50);
            $table->string('TERAP_WHICH2', 100);
            $table->string('TERAP_FREC2', 50);
            $table->string('TERAP_WHICH3', 100);
            $table->string('TERAP_FREC3', 50);
            $table->boolean('ENFERPAR');
            $table->string('ENFERPAR_WHICH', 200);
            $table->boolean('MEDIC');
            $table->string('MEDIC_FREC', 100);
            $table->boolean('MOVILID');
            $table->string('MOVILID_WHICH', 200);
            $table->string('OCUP_MADRE', 100);
            $table->string('OCUP_PADRE', 100);
            $table->string('EDUC_MADRE', 100);
            $table->string('EDUC_PADRE', 100);
            $table->string('NOMB_CUID', 100);
            $table->string('PAREN_CUID', 100);
            $table->string('EDUC_CUID', 100);
            $table->integer('TEL_CUID');
            $table->string('EMAIL_CUID', 100);
            $table->integer('HERMANOS');
            $table->integer('LUGAR');
            $table->string('PERS_VIVE', 300);
            $table->string('CRIANZA', 300);
            $table->boolean('HOG_PROTEC');
            $table->string('HOG_PROTEC_WHICH', 100);
            $table->boolean('HOG_SUB');
            $table->string('HOG_SUB_WHICH', 100);
            $table->boolean('INSTITUPREV');
            $table->string('INTITUPREV_WHICH', 100);
            $table->string('ULTGRADO', 50);
            $table->boolean('APRUEBA');
            $table->string('OBSERV', 300);
            $table->boolean('INFOPIAR');
            $table->string('INFOPIAR_WHICH', 300);
            $table->boolean('COMPLEM');
            $table->string('COMPLEM_WHICH', 300);
            $table->string('TRANSPOR', 100);
            $table->string('DISTANCIA', 50);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('PIAR_DIAG');
    }
};
