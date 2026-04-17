<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE PIAR_MAT        MODIFY OBSERVACIONES         MEDIUMTEXT NULL");
        DB::statement("ALTER TABLE PIAR_MAT        MODIFY OBSERVACIONES_CASERO  MEDIUMTEXT NULL");
        DB::statement("ALTER TABLE PIAR_CARACT_MAT MODIFY OBSERVACIONES         MEDIUMTEXT NULL");
        DB::statement("ALTER TABLE PIAR_CARACT_DIR MODIFY OBSERVACIONES         MEDIUMTEXT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE PIAR_MAT        MODIFY OBSERVACIONES         TEXT NULL");
        DB::statement("ALTER TABLE PIAR_MAT        MODIFY OBSERVACIONES_CASERO  TEXT NULL");
        DB::statement("ALTER TABLE PIAR_CARACT_MAT MODIFY OBSERVACIONES         TEXT NULL");
        DB::statement("ALTER TABLE PIAR_CARACT_DIR MODIFY OBSERVACIONES         TEXT NULL");
    }
};
