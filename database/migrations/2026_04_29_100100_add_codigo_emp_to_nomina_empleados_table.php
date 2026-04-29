<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nomina_empleados', function (Blueprint $table) {
            $table->string('codigo_emp', 10)->nullable()->unique()->after('id');
            $table->unsignedBigInteger('tipo_empleado_id')->nullable()->after('codigo_emp');
            $table->foreign('tipo_empleado_id')
                  ->references('id')->on('nomina_cat_tipos_empleado')
                  ->nullOnDelete();
        });

        $tipos = DB::table('nomina_cat_tipos_empleado')
            ->pluck('id', 'codigo')
            ->toArray();

        // cedula => [codigo_emp, tipo_codigo]
        $asignaciones = [
            '51796808'   => ['DIR001', 'DIR'],
            '79996161'   => ['COR001', 'COR'],
            '52151011'   => ['COR002', 'COR'],
            '60446292'   => ['ADM001', 'ADM'],
            '1013656262' => ['ADM002', 'ADM'],
            '52967202'   => ['ADM003', 'ADM'],
            '52187005'   => ['SSG001', 'SSG'],
            '52213492'   => ['SSG002', 'SSG'],
            '1000726784' => ['DOC001', 'DOC'],
            '1030556941' => ['DOC002', 'DOC'],
            '53047275'   => ['DOC003', 'DOC'],
            '1098670657' => ['DOC005', 'DOC'],
            '1019074293' => ['DOC007', 'DOC'],
            '1016017913' => ['DOC009', 'DOC'],
            '1000352266' => ['DOC010', 'DOC'],
            '1007650446' => ['DOC011', 'DOC'],
            '80857884'   => ['DOC012', 'DOC'],
            '1019129874' => ['DOC015', 'DOC'],
            '1015448079' => ['DOC019', 'DOC'],
            '1000517216' => ['DOC021', 'DOC'],
            '79601843'   => ['DOC023', 'DOC'],
            '52131843'   => ['DOC024', 'DOC'],
            '81716196'   => ['DOC025', 'DOC'],
            '79943520'   => ['DOC028', 'DOC'],
            '53090919'   => ['DOC045', 'DOC'],
            '79750213'   => ['DOC046', 'DOC'],
            '1072716112' => ['DOC047', 'DOC'],
            '80108612'   => ['DOC048', 'DOC'],
            '1005209715' => ['DOC049', 'DOC'],
            '1007106756' => ['DOC050', 'DOC'],
            '1000252794' => ['DOC053', 'DOC'],
            '1023902024' => ['DOC054', 'DOC'],
            '52899880'   => ['DOC055', 'DOC'],
            '1014251823' => ['DOC058', 'DOC'],
            '53006323'   => ['DOC059', 'DOC'],
            '1007289039' => ['DOC060', 'DOC'],
            '1007090028' => ['DOC061', 'DOC'],
            '1022955744' => ['DOC062', 'DOC'],
        ];

        foreach ($asignaciones as $cedula => [$codigoEmp, $tipoCodigo]) {
            DB::table('nomina_empleados')
                ->where('cedula', $cedula)
                ->update([
                    'codigo_emp' => $codigoEmp,
                    'tipo_empleado_id' => $tipos[$tipoCodigo] ?? null,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('nomina_empleados', function (Blueprint $table) {
            $table->dropForeign(['tipo_empleado_id']);
            $table->dropUnique(['codigo_emp']);
            $table->dropColumn(['codigo_emp', 'tipo_empleado_id']);
        });
    }
};
