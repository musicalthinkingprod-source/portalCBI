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
        Schema::create('INFO_ACADEM', function (Blueprint $table) {
            $table->integer('CODIGO');
            $table->string('INS_PJ', 55)->nullable();
            $table->string('ANO_PJ', 12)->nullable();
            $table->string('INS_J', 58)->nullable();
            $table->string('ANO_J', 9)->nullable();
            $table->string('INS_T', 74)->nullable();
            $table->string('ANO_T', 8)->nullable();
            $table->string('INS_1', 50)->nullable();
            $table->string('ANO_1', 4)->nullable();
            $table->string('INS_2', 48)->nullable();
            $table->string('ANO_2', 4)->nullable();
            $table->string('INS_3', 43)->nullable();
            $table->string('ANO_3', 9)->nullable();
            $table->string('INS_4', 44)->nullable();
            $table->string('ANO_4', 4)->nullable();
            $table->string('INS_5', 43)->nullable();
            $table->string('ANO_5', 4)->nullable();
            $table->string('INS_6', 43)->nullable();
            $table->string('ANO_6', 4)->nullable();
            $table->string('INS_7', 54)->nullable();
            $table->string('ANO_7', 4)->nullable();
            $table->string('INS_8', 43)->nullable();
            $table->string('ANO_8', 4)->nullable();
            $table->string('INS_9', 30)->nullable();
            $table->string('ANO_9', 4)->nullable();
            $table->string('INS_10', 26)->nullable();
            $table->string('ANO_10', 4)->nullable();
            $table->string('INS_11', 26)->nullable();
            $table->string('ANO_11', 4)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('INFO_ACADEM');
    }
};
