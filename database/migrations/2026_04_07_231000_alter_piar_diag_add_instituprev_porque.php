<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('PIAR_DIAG', function (Blueprint $table) {
            $table->string('INSTITUPREV_PORQUE', 300)->nullable()->after('INTITUPREV_WHICH');
        });
    }

    public function down(): void
    {
        Schema::table('PIAR_DIAG', function (Blueprint $table) {
            $table->dropColumn('INSTITUPREV_PORQUE');
        });
    }
};
