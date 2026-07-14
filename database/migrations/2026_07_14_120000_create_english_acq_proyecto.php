<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Notas del proyecto (componente 40% de English Acquisition).
        // Se guarda aparte de NOTAS_<año> para que el proyecto NO aparezca
        // como asignatura propia en el boletín; entregar() lo pondera dentro
        // de la materia 11.
        if (!Schema::hasTable('english_acq_proyecto')) {
            Schema::create('english_acq_proyecto', function (Blueprint $table) {
                $table->id();
                $table->integer('CODIGO_ALUM');
                $table->tinyInteger('PERIODO');
                $table->integer('ANIO');
                $table->decimal('NOTA', 4, 2);
                $table->string('CODIGO_EMP', 20)->nullable();
                $table->timestamps();

                $table->unique(['CODIGO_ALUM', 'PERIODO', 'ANIO'], 'ea_proy_alum_periodo_anio');
            });
        }

        // Materia "sombra" para que el docente digite el proyecto desde el
        // menú de Notas normal (aparece en ASIGNACION_PCM → CODIGOSMAT).
        $existe = DB::table('CODIGOSMAT')->where('CODIGO_MAT', 36)->exists();
        if (!$existe) {
            DB::table('CODIGOSMAT')->insert([
                'CODIGO_MAT' => 36,
                'NOMBRE_MAT' => 'English Acquisition - Proyecto',
                'AREA_MAT'   => 2,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('english_acq_proyecto');
        DB::table('CODIGOSMAT')->where('CODIGO_MAT', 36)->delete();
    }
};
