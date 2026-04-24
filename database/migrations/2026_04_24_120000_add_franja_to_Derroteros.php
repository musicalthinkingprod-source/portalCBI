<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('Derroteros', function (Blueprint $table) {
            $table->tinyInteger('FRANJA')->nullable()->after('HORARIO');
        });
    }

    public function down(): void
    {
        Schema::table('Derroteros', function (Blueprint $table) {
            $table->dropColumn('FRANJA');
        });
    }
};
