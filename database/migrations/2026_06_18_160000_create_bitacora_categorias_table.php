<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bitacora_categorias', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nombre', 100);
            $table->string('ambito', 20)->default('general'); // academico | convivencia | general
            $table->string('color', 20)->nullable();
            $table->boolean('activo')->default(true);
        });

        // Categorías iniciales para que el módulo arranque con opciones.
        DB::table('bitacora_categorias')->insert([
            ['nombre' => 'Consejo Académico',       'ambito' => 'academico',   'color' => 'blue',   'activo' => 1],
            ['nombre' => 'Académico',               'ambito' => 'academico',   'color' => 'indigo', 'activo' => 1],
            ['nombre' => 'Convivencia / Disciplina','ambito' => 'convivencia', 'color' => 'red',    'activo' => 1],
            ['nombre' => 'Felicitación',            'ambito' => 'general',     'color' => 'green',  'activo' => 1],
            ['nombre' => 'Citación',                'ambito' => 'general',     'color' => 'amber',  'activo' => 1],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora_categorias');
    }
};
