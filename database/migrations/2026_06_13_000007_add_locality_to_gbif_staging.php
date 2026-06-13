<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // locality (DwC interpreted field) used as fallback when verbatimLocality is empty
        DB::statement("ALTER TABLE gbif_staging ADD COLUMN locality TEXT NULL AFTER verbatim_locality");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE gbif_staging DROP COLUMN locality");
    }
};
