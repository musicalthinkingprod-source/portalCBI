<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('PRINUSERS', 'CODIGO_EMP')) {
            Schema::table('PRINUSERS', function (Blueprint $table) {
                $table->string('CODIGO_EMP', 10)->nullable()->after('PROFILE');
            });
        }

        // USER => CODIGO_EMP
        $mapeo = [
            'danicaradmin'           => 'ING001',
            'angelaveg'              => 'DIR001',
            'tesoreria'              => 'ADM001',
            'ClaudiaDiaz'            => 'ADM003',
            'academic_coordination'  => 'COR001',
            'marthaconvivencia'      => 'COR002',
            'angie_gonzalez'         => 'DOC001',
            'yuly_martinez'          => 'DOC002',
            'yulieth_leon'           => 'DOC003',
            'lisseth_martinez'       => 'DOC005',
            'luz_martinez'           => 'DOC007',
            'guillermo_pinto'        => 'DOC009',
            'luisa_torres'           => 'DOC010',
            'roxana_echeverry'       => 'DOC011',
            'ricardo_tapias'         => 'DOC012',
            'maria_saucedo'          => 'DOC015',
            'marco_gonzalez'         => 'DOC019',
            'estefania_perez'        => 'DOC021',
            'edward_contreras'       => 'DOC023',
            'lina_mendoza'           => 'DOC024',
            'jose_penagos'           => 'DOC025',
            'jimmy_martinez'         => 'DOC028',
            'lady_granados'          => 'DOC045',
            'alexander_gonzalez'     => 'DOC046',
            'david_amezquita'        => 'DOC047',
            'fabian_calderon'        => 'DOC048',
            'juan_figueroa'          => 'DOC049',
            'kelly_chacon'           => 'DOC050',
            'karol_pinzon'           => 'DOC053',
            'lina_ducuara'           => 'DOC054',
            'catherine_guzman'       => 'DOC055',
            'heiner_toloza'          => 'DOC056',
        ];

        foreach ($mapeo as $user => $codigoEmp) {
            DB::table('PRINUSERS')
                ->where('USER', $user)
                ->update(['CODIGO_EMP' => $codigoEmp]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('PRINUSERS', 'CODIGO_EMP')) {
            Schema::table('PRINUSERS', function (Blueprint $table) {
                $table->dropColumn('CODIGO_EMP');
            });
        }
    }
};
