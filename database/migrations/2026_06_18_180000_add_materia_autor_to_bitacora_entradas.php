<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bitacora_entradas', function (Blueprint $table) {
            $table->integer('codigo_mat')->nullable()->after('categoria_id');        // materia (registros de aula)
            $table->string('registrado_nombre', 120)->nullable()->after('registrado_por'); // nombre de quien registra
        });
    }

    public function down(): void
    {
        Schema::table('bitacora_entradas', function (Blueprint $table) {
            $table->dropColumn(['codigo_mat', 'registrado_nombre']);
        });
    }
};
