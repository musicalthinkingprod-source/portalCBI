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
        Schema::table('circulares', function (Blueprint $table) {
            // null = todos los grados; array JSON = grados específicos
            $table->json('grados')->nullable()->after('link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('circulares', function (Blueprint $table) {
            $table->dropColumn('grados');
        });
    }
};
