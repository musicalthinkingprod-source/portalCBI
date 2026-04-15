<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('nomina_empleados', function (Blueprint $table) {
            $table->string('tipo_empleado', 50)->nullable()->after('cargo');
        });
    }

    public function down(): void
    {
        Schema::table('nomina_empleados', function (Blueprint $table) {
            $table->dropColumn('tipo_empleado');
        });
    }
};
