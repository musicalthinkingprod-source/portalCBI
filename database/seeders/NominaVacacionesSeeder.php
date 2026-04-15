<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NominaVacacionesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('nomina_vacaciones')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        DB::statement("INSERT INTO nomina_vacaciones
            (cedula,anio,periodo,fecha_inicio,fecha_fin,dias_tomados,dias_corresponden,created_at,updated_at)
        VALUES
            ('51796808',2026,'ENERO',NULL,NULL,1,15,NOW(),NOW()),
            ('51796808',2026,'ABRIL',NULL,NULL,1,15,NOW(),NOW()),
            ('79996161',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('52151011',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('1022955744',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('53090919',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('79943520',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('1014251823',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('79750213',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('1000726784',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('1072716112',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('79601843',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('1000517216',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('80108612',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('1016017913',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('80857884',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('53047275',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('81716196',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('1005209715',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('52131843',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('1098670657',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('1000352266',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('1019074293',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('1015448079',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('1019129874',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('53006323',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('1030556941',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('1007650446',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('1007289039',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('60446292',2026,'ENERO',NULL,NULL,1,NULL,NOW(),NOW()),
            ('60446292',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('1013656262',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('52967202',2026,'ENERO',NULL,NULL,1,NULL,NOW(),NOW()),
            ('52967202',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW()),
            ('52213492',2026,'ABRIL',NULL,NULL,1,NULL,NOW(),NOW())
        ");
    }
}
