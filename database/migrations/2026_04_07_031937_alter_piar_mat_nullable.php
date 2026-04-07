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
        Schema::table('PIAR_MAT', function (Blueprint $table) {
            $table->text('BARRERAS')->nullable()->change();
            $table->text('LOGRO1')->nullable()->change();
            $table->text('DIDACT1')->nullable()->change();
            $table->text('EVAL1')->nullable()->change();
            $table->text('LOGRO2')->nullable()->change();
            $table->text('DIDACT2')->nullable()->change();
            $table->text('EVAL2')->nullable()->change();
            $table->text('LOGRO3')->nullable()->change();
            $table->text('DIDACT3')->nullable()->change();
            $table->text('EVAL3')->nullable()->change();
            $table->text('LOGRO4')->nullable()->change();
            $table->text('DIDACT4')->nullable()->change();
            $table->text('EVAL4')->nullable()->change();
            $table->text('ESTRAG_CASERA')->nullable()->change();
            $table->string('FREC_CASERA', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        //
    }
};
