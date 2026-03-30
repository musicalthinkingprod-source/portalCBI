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
        Schema::create('INFO_PADRES', function (Blueprint $table) {
            $table->integer('CODIGO');
            $table->string('MADRE', 36)->nullable();
            $table->integer('CC_MADRE')->nullable();
            $table->string('EMP_MADRE', 59)->nullable();
            $table->string('TELEMP_MADRE', 21)->nullable();
            $table->bigInteger('CEL_MADRE')->nullable();
            $table->string('CASA_MADRE', 64)->nullable();
            $table->string('TEL_MADRE', 18)->nullable();
            $table->string('EMAIL_MADRE', 62)->nullable();
            $table->string('EMP_PADRE', 53)->nullable();
            $table->string('TELEMP_PADRE', 21)->nullable();
            $table->string('CASA_PADRE', 64)->nullable();
            $table->string('TEL_PADRE', 17)->nullable();
            $table->string('EMAIL_PADRE', 62)->nullable();
            $table->string('ACUD', 31)->nullable();
            $table->string('DIREMP_MADRE', 85)->nullable();
            $table->string('PADRE', 38)->nullable();
            $table->integer('CC_PADRE')->nullable();
            $table->string('DIREMP_PADRE', 49)->nullable();
            $table->bigInteger('CEL_PADRE')->nullable();
            $table->integer('CC_ACUD')->nullable();
            $table->string('EMP_ACUD', 47)->nullable();
            $table->string('TELEMP_ACUD', 10)->nullable();
            $table->string('DIREMP_ACUD', 22)->nullable();
            $table->string('CASA_ACUD', 29)->nullable();
            $table->string('TEL_ACUD', 10)->nullable();
            $table->bigInteger('CEL_ACUD')->nullable();
            $table->string('EMAIL_ACUD', 29)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('INFO_PADRES');
    }
};
