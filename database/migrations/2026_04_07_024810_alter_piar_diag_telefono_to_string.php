<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('PIAR_DIAG', function (Blueprint $table) {
            $table->string('TELEFONO', 20)->nullable()->change();
            $table->string('TEL_CUID', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('PIAR_DIAG', function (Blueprint $table) {
            $table->integer('TELEFONO')->nullable()->change();
            $table->integer('TEL_CUID')->nullable()->change();
        });
    }
};
