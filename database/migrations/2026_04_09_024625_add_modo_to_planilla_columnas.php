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
        Schema::table('planilla_columnas', function (Blueprint $table) {
            $table->string('modo', 10)->default('peso')->after('anio');
        });
    }

    public function down(): void
    {
        Schema::table('planilla_columnas', function (Blueprint $table) {
            $table->dropColumn('modo');
        });
    }
};
