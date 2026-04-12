<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('PIAR_DIAG', function (Blueprint $table) {
            $table->string('codigo_ori', 25)->nullable()->after('CODIGO_ALUM');
        });

        // Poblar registros existentes según PERSONA_DIL (mapeo por nombre)
        DB::table('PIAR_DIAG')
            ->where('PERSONA_DIL', 'like', '%Jimmy%')
            ->update(['codigo_ori' => 'jimmy_perez']);

        DB::table('PIAR_DIAG')
            ->where('PERSONA_DIL', 'like', '%Jennifer%')
            ->update(['codigo_ori' => 'Andreamar']);
    }

    public function down(): void
    {
        Schema::table('PIAR_DIAG', function (Blueprint $table) {
            $table->dropColumn('codigo_ori');
        });
    }
};
