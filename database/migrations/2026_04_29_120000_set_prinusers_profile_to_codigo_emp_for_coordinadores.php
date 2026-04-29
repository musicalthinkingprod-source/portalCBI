<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PROFILE sigue siendo el codigo de identidad (DOC###, COR###, etc.).
        // Para Willy y Martha el PROFILE pasa de DOC029/DOC057 (set por la
        // migracion anterior a 'Coord') al codigo COR correspondiente.
        DB::table('PRINUSERS')->where('USER', 'academic_coordination')->update(['PROFILE' => 'COR001']);
        DB::table('PRINUSERS')->where('USER', 'marthaconvivencia')->update(['PROFILE' => 'COR002']);
    }

    public function down(): void
    {
        DB::table('PRINUSERS')->where('USER', 'academic_coordination')->update(['PROFILE' => 'Coord']);
        DB::table('PRINUSERS')->where('USER', 'marthaconvivencia')->update(['PROFILE' => 'Coord']);
    }
};
