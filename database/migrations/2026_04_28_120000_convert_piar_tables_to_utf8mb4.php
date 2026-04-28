<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $tables = ['PIAR_MAT', 'PIAR_DIAG', 'PIAR_CARACT_DIR', 'PIAR_CARACT_MAT'];
        foreach ($tables as $t) {
            DB::statement("ALTER TABLE `{$t}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        }
    }

    public function down(): void
    {
        $tables = ['PIAR_MAT', 'PIAR_DIAG', 'PIAR_CARACT_DIR', 'PIAR_CARACT_MAT'];
        foreach ($tables as $t) {
            DB::statement("ALTER TABLE `{$t}` CONVERT TO CHARACTER SET latin1 COLLATE latin1_swedish_ci");
        }
    }
};
