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
            $table->string('DIAGNOSTICO', 100)->nullable()->change();
            $table->string('LUGAR_DIL', 100)->nullable()->change();
            $table->string('PERSONA_DIL', 100)->nullable()->change();
            $table->string('MUNICIPIO', 50)->nullable()->change();
            $table->string('EMAIL', 50)->nullable()->change();
            $table->string('PROTEC_WHICH', 50)->nullable()->change();
            $table->string('ASPIRA', 50)->nullable()->change();
            $table->string('REGIS', 100)->nullable()->change();
            $table->string('ETNIC_WHICH', 50)->nullable()->change();
            $table->string('CONFARM_REG', 50)->nullable()->change();
            $table->string('EPS', 50)->nullable()->change();
            $table->string('EMERG', 100)->nullable()->change();
            $table->string('FREC_PROTEG', 50)->nullable()->change();
            $table->boolean('DIAGMED')->nullable()->change();
            $table->string('DIAGMED_WHICH', 200)->nullable()->change();
            $table->boolean('TERAP')->nullable()->change();
            $table->string('TERAP_WHICH1', 100)->nullable()->change();
            $table->string('TERAP_FREC1', 50)->nullable()->change();
            $table->string('TERAP_WHICH2', 100)->nullable()->change();
            $table->string('TERAP_FREC2', 50)->nullable()->change();
            $table->string('TERAP_WHICH3', 100)->nullable()->change();
            $table->string('TERAP_FREC3', 50)->nullable()->change();
            $table->boolean('ENFERPAR')->nullable()->change();
            $table->string('ENFERPAR_WHICH', 200)->nullable()->change();
            $table->boolean('MEDIC')->nullable()->change();
            $table->string('MEDIC_FREC', 100)->nullable()->change();
            $table->boolean('MOVILID')->nullable()->change();
            $table->string('MOVILID_WHICH', 200)->nullable()->change();
            $table->string('OCUP_MADRE', 100)->nullable()->change();
            $table->string('OCUP_PADRE', 100)->nullable()->change();
            $table->string('EDUC_MADRE', 100)->nullable()->change();
            $table->string('EDUC_PADRE', 100)->nullable()->change();
            $table->string('NOMB_CUID', 100)->nullable()->change();
            $table->string('PAREN_CUID', 100)->nullable()->change();
            $table->string('EDUC_CUID', 100)->nullable()->change();
            $table->string('EMAIL_CUID', 100)->nullable()->change();
            $table->integer('HERMANOS')->nullable()->change();
            $table->integer('LUGAR')->nullable()->change();
            $table->string('PERS_VIVE', 300)->nullable()->change();
            $table->string('CRIANZA', 300)->nullable()->change();
            $table->boolean('HOG_PROTEC')->nullable()->change();
            $table->string('HOG_PROTEC_WHICH', 100)->nullable()->change();
            $table->boolean('HOG_SUB')->nullable()->change();
            $table->string('HOG_SUB_WHICH', 100)->nullable()->change();
            $table->boolean('INSTITUPREV')->nullable()->change();
            $table->string('INTITUPREV_WHICH', 100)->nullable()->change();
            $table->string('ULTGRADO', 50)->nullable()->change();
            $table->boolean('APRUEBA')->nullable()->change();
            $table->string('OBSERV', 300)->nullable()->change();
            $table->boolean('INFOPIAR')->nullable()->change();
            $table->string('INFOPIAR_WHICH', 300)->nullable()->change();
            $table->boolean('COMPLEM')->nullable()->change();
            $table->string('COMPLEM_WHICH', 300)->nullable()->change();
            $table->string('TRANSPOR', 100)->nullable()->change();
            $table->string('DISTANCIA', 50)->nullable()->change();
        });
    }

    public function down(): void
    {
        //
    }
};
