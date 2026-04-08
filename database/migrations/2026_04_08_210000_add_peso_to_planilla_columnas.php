<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planilla_columnas', function (Blueprint $table) {
            // Peso relativo para promedio ponderado dentro de la categoría.
            // NULL o 1 = igual peso. 2 = doble peso. Etc.
            $table->decimal('peso', 5, 2)->nullable()->after('orden');
        });
    }

    public function down(): void
    {
        Schema::table('planilla_columnas', function (Blueprint $table) {
            $table->dropColumn('peso');
        });
    }
};
