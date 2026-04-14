<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_tipos', function (Blueprint $table) {
            $table->string('codigo', 30)->primary();
            $table->string('nombre', 100);
            $table->string('marca', 60)->nullable();
            $table->string('modelo', 60)->nullable();
            $table->decimal('precio', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_tipos');
    }
};
