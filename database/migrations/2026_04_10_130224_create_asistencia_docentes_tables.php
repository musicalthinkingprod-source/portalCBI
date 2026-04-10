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
        // Registro diario de asistencia del personal docente
        Schema::create('asistencia_docentes', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('codigo_doc', 25);
            $table->enum('estado', ['presente', 'retardo', 'ausente', 'permiso', 'incapacidad']);
            $table->time('hora_llegada')->nullable();   // para retardos
            $table->string('observacion', 300)->nullable();
            $table->string('registrado_por', 50);
            $table->timestamps();
            $table->unique(['fecha', 'codigo_doc']);
        });

        // Permisos / licencias autorizados por SuperAd
        Schema::create('permisos_docentes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_doc', 25);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->enum('tipo', ['permiso', 'incapacidad', 'calamidad', 'comision']);
            $table->text('motivo');
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
            $table->string('aprobado_por', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permisos_docentes');
        Schema::dropIfExists('asistencia_docentes');
    }
};
