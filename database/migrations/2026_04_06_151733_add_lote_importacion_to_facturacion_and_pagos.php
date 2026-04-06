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
        Schema::table('facturacion', function (Blueprint $table) {
            $table->string('lote_importacion', 30)->nullable()->index()->after('fecha');
        });

        Schema::table('registro_pagos', function (Blueprint $table) {
            $table->string('lote_importacion', 30)->nullable()->index()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('facturacion', function (Blueprint $table) {
            $table->dropColumn('lote_importacion');
        });

        Schema::table('registro_pagos', function (Blueprint $table) {
            $table->dropColumn('lote_importacion');
        });
    }
};
