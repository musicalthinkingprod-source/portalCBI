<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('CODIGOS_DOC', function (Blueprint $table) {
            $table->string('DIR_GRUPO', 4)->nullable()->after('TIPO');
        });
    }

    public function down(): void
    {
        Schema::table('CODIGOS_DOC', function (Blueprint $table) {
            $table->dropColumn('DIR_GRUPO');
        });
    }
};
