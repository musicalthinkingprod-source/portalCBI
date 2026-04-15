<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendario_eventos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha')->index();
            $table->string('evento', 200);
            $table->enum('visibilidad', ['todos', 'interno', 'docentes', 'directivas', 'padres'])->default('interno');
            $table->timestamps();
        });

        // Migrar eventos existentes desde calendario_academico
        DB::table('calendario_academico')
            ->whereNotNull('evento')
            ->orderBy('fecha')
            ->each(function ($row) {
                DB::table('calendario_eventos')->insert([
                    'fecha'       => $row->fecha,
                    'evento'      => $row->evento,
                    'visibilidad' => $row->visibilidad ?? 'interno',
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendario_eventos');
    }
};
