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
        Schema::create('nomina_empleados', function (Blueprint $table) {
            $table->id();
            $table->string('cedula', 20)->unique();
            $table->string('ciudad_identificacion', 50)->nullable();
            $table->string('nombre', 150);
            $table->string('correo', 100)->nullable();
            $table->string('direccion', 200)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->date('fecha_ingreso')->nullable();
            $table->date('fecha_fin_contrato')->nullable();
            $table->string('cargo', 100)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->unsignedTinyInteger('numero_hijos')->default(0);
            $table->string('eps', 80)->nullable();
            $table->string('fondo_pension', 80)->nullable();
            $table->string('fondo_arl', 80)->nullable();
            $table->string('caja_compensacion', 80)->nullable();
            $table->decimal('sueldo_basico', 12, 2)->nullable();
            $table->string('tipo_sangre', 5)->nullable();
            $table->string('contacto_emergencia', 150)->nullable();
            $table->string('tel_emergencia', 20)->nullable();
            $table->string('codigo_doc', 25)->nullable()->comment('Enlace con perfil docente (asistencia_docentes.codigo_doc)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nomina_empleados');
    }
};
