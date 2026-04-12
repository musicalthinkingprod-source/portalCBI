<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("UPDATE PIAR_CARACT_MAT SET OBSERVACIONES = NULL");
        DB::statement("UPDATE PIAR_CARACT_DIR SET OBSERVACIONES = NULL");
        DB::statement("UPDATE PIAR_MAT SET OBSERVACIONES = NULL");
    }

    public function down(): void {}
};
