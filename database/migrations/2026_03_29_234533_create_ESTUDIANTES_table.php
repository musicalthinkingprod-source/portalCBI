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
        Schema::create('ESTUDIANTES', function (Blueprint $table) {
            $table->integer('CODIGO');
            $table->string('NOMBRE', 42)->nullable();
            $table->string('NOMBRE1', 50);
            $table->string('NOMBRE2', 50);
            $table->string('APELLIDO1', 50);
            $table->string('APELLIDO2', 50);
            $table->date('FECH_MATRICULA');
            $table->string('GRADO', 6)->nullable();
            $table->string('LUG_NACIMIENTO', 25)->nullable();
            $table->date('FECH_NACIMIENTO')->nullable();
            $table->integer('EDAD')->nullable();
            $table->string('LUG_EXPED', 20)->nullable();
            $table->string('DIRECCION', 51)->nullable();
            $table->integer('ESTRATO')->nullable();
            $table->string('BARRIO', 44)->nullable();
            $table->string('EPS', 30)->nullable();
            $table->string('RH', 3)->nullable();
            $table->string('ALERG', 50)->nullable();
            $table->string('ENFER', 50)->nullable();
            $table->string('ACUDIENTE', 20)->nullable();
            $table->string('REG_CIVIL', 15)->nullable();
            $table->string('TAR_ID', 11)->nullable();
            $table->string('GAFAS', 58)->nullable();
            $table->string('ESTADO', 20)->nullable();
            $table->string('CURSO', 3)->nullable();
            $table->string('SEDE', 1)->nullable();
            $table->string('ENTRADA', 10)->nullable();
            $table->string('SALIDA', 10)->nullable();
            $table->boolean('RET_CART')->default(false);
            $table->boolean('RET_ACAD')->default(false);
            $table->boolean('RET_CONV')->default(false);
            $table->boolean('RET_RECT')->default(false);
            $table->string('OBSERV_FINAL', 200);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ESTUDIANTES');
    }
};
