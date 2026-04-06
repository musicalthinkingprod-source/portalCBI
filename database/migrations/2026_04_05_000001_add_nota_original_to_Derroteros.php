<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Derroteros', function (Blueprint $table) {
            $table->float('NOTA_ORIGINAL')->nullable()->after('ANIO');
        });
    }

    public function down(): void
    {
        Schema::table('Derroteros', function (Blueprint $table) {
            $table->dropColumn('NOTA_ORIGINAL');
        });
    }
};
