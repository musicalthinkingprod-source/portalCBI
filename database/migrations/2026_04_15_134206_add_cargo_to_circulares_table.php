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
            $table->string('cargo', 255)->nullable()->after('emitido_por');
        });
    }

    public function down(): void
    {
        Schema::table('circulares', function (Blueprint $table) {
            $table->dropColumn('cargo');
        });
    }
};
