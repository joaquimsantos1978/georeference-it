<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Widen columns that can exceed VARCHAR(255) in real GBIF data
        DB::statement("ALTER TABLE occurrences
            MODIFY COLUMN recorded_by        TEXT          NULL,
            MODIFY COLUMN verbatim_locality  TEXT          NULL,
            MODIFY COLUMN scientific_name    VARCHAR(500)  NULL,
            MODIFY COLUMN gbif_coordinate_uncertainty_m FLOAT NULL
        ");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE occurrences
            MODIFY COLUMN recorded_by        VARCHAR(255)  NULL,
            MODIFY COLUMN verbatim_locality  VARCHAR(255)  NULL,
            MODIFY COLUMN scientific_name    VARCHAR(255)  NULL,
            MODIFY COLUMN gbif_coordinate_uncertainty_m INT NULL
        ");
    }
};
