<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('copias_seguridad', function (Blueprint $table) {
            $table->id();
            $table->string('usuario', 25);          // USER de PRINUSERS
            $table->string('profile', 10);
            $table->date('fecha');
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['fecha']);
            $table->index(['usuario', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('copias_seguridad');
    }
};
