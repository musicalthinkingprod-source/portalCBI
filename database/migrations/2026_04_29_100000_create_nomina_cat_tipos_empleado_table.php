<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nomina_cat_tipos_empleado', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 3)->unique();
            $table->string('nombre', 60);
            $table->string('prefijo_codigo', 3)->unique();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        DB::table('nomina_cat_tipos_empleado')->insert([
            ['codigo' => 'ING', 'nombre' => 'Ingenieria',          'prefijo_codigo' => 'ING', 'orden' => 1, 'activo' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'DIR', 'nombre' => 'Direccion',           'prefijo_codigo' => 'DIR', 'orden' => 2, 'activo' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'COR', 'nombre' => 'Coordinacion',        'prefijo_codigo' => 'COR', 'orden' => 3, 'activo' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'DOC', 'nombre' => 'Docente',             'prefijo_codigo' => 'DOC', 'orden' => 4, 'activo' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'ADM', 'nombre' => 'Administrativo',      'prefijo_codigo' => 'ADM', 'orden' => 5, 'activo' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'SSG', 'nombre' => 'Servicios Generales', 'prefijo_codigo' => 'SSG', 'orden' => 6, 'activo' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'COC', 'nombre' => 'Cocina',              'prefijo_codigo' => 'COC', 'orden' => 7, 'activo' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('nomina_cat_tipos_empleado');
    }
};
