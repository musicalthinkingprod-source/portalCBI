<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventario_unidades', function (Blueprint $table) {
            $table->renameColumn('asignado_a', 'ubicacion');
        });
    }

    public function down(): void
    {
        Schema::table('inventario_unidades', function (Blueprint $table) {
            $table->renameColumn('ubicacion', 'asignado_a');
        });
    }
};
