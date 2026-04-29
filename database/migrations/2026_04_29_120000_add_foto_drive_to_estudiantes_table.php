<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ESTUDIANTES', function (Blueprint $table) {
            $table->string('FOTO_DRIVE', 255)->nullable()->after('OBSERV_FINAL');
        });
    }

    public function down(): void
    {
        Schema::table('ESTUDIANTES', function (Blueprint $table) {
            $table->dropColumn('FOTO_DRIVE');
        });
    }
};
