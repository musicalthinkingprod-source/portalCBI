<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('PIAR_DIAG', function (Blueprint $table) {
            $table->string('TERAP_WHICH4')->nullable()->after('TERAP_FREC3');
            $table->string('TERAP_FREC4')->nullable()->after('TERAP_WHICH4');
            $table->string('TERAP_WHICH5')->nullable()->after('TERAP_FREC4');
            $table->string('TERAP_FREC5')->nullable()->after('TERAP_WHICH5');
        });
    }

    public function down(): void
    {
        Schema::table('PIAR_DIAG', function (Blueprint $table) {
            $table->dropColumn(['TERAP_WHICH4', 'TERAP_FREC4', 'TERAP_WHICH5', 'TERAP_FREC5']);
        });
    }
};
