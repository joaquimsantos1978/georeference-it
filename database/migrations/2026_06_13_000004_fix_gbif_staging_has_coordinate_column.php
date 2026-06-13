<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // DWCA stores hasCoordinate as "true"/"false" strings, not 0/1
        DB::statement("ALTER TABLE gbif_staging MODIFY COLUMN has_coordinate VARCHAR(5) NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE gbif_staging MODIFY COLUMN has_coordinate TINYINT(1) DEFAULT 0");
    }
};
