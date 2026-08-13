<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admision_entrevistas', function (Blueprint $table) {
            $table->increments('id');

            // Vínculo opcional con el examen de admisión del mismo aspirante
            $table->unsignedInteger('evaluacion_id')->nullable();

            // ── I. Datos personales y contexto de ingreso ──────────────────
            $table->date('fecha_entrevista')->nullable();
            $table->string('grado_key', 20)->nullable();
            $table->string('grado_nombre', 40)->nullable();

            $table->string('aspirante_nombre', 150);
            $table->string('documento_tipo', 10)->nullable();     // RC, TI, CC, CE
            $table->string('aspirante_documento', 30)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('lugar_nacimiento', 120)->nullable();
            $table->unsignedTinyInteger('edad')->nullable();
            $table->string('direccion', 180)->nullable();
            $table->string('telefono', 40)->nullable();

            $table->string('acudiente', 150)->nullable();
            $table->string('acudiente_parentesco', 60)->nullable();
            $table->string('acudiente_ocupacion', 120)->nullable();
            $table->string('acudiente_telefono', 40)->nullable();
            $table->string('acudiente_correo', 120)->nullable();

            $table->string('institucion_procedencia', 180)->nullable();
            $table->string('tiempo_permanencia', 80)->nullable();
            $table->text('motivo_retiro')->nullable();
            $table->text('ha_reprobado')->nullable();
            $table->text('cambios_colegio')->nullable();
            $table->text('nucleo_familiar')->nullable();
            $table->string('responsable_academico', 150)->nullable();
            $table->string('red_apoyo', 180)->nullable();

            // Cuadro de composición familiar (repetidor)
            // [ {nombre, parentesco, edad, ocupacion, positivo, negativo}, ... ]
            $table->json('familia')->nullable();

            // ── II a VIII: respuestas abiertas ─────────────────────────────
            // { "salud": {"medicos": "...", ...}, "academica": {...}, "concepto": {...} }
            $table->json('respuestas')->nullable();

            // Semáforo por área: { "salud": "ok|atencion|alerta", ... }
            $table->json('alertas')->nullable();

            // ── VIII. Concepto de los entrevistadores ──────────────────────
            $table->enum('viabilidad', ['viable', 'condicionado', 'no_viable'])->nullable();
            $table->text('compromisos')->nullable();

            $table->string('orientador', 120)->nullable();
            $table->string('coordinador', 120)->nullable();

            // ── Concepto final de rectoría ─────────────────────────────────
            $table->enum('rectoria_decision', ['admitido', 'condicionado', 'aplazado', 'no_admitido'])->nullable();
            $table->text('rectoria_observaciones')->nullable();
            $table->date('rectoria_fecha')->nullable();
            $table->string('rectoria_por', 100)->nullable();

            $table->enum('estado', ['borrador', 'finalizada'])->default('borrador');
            $table->string('registrado_por', 100)->nullable();

            $table->timestamps();

            $table->index('evaluacion_id');
            $table->index('grado_key');
            $table->index('aspirante_documento');
            $table->index('estado');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admision_entrevistas');
    }
};
