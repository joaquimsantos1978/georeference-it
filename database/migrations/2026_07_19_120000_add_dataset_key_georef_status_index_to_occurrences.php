<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Adding a secondary index is INPLACE/LOCK=NONE-safe on InnoDB (unlike ADD COLUMN,
    // which hit ALGORITHM=INSTANT restrictions on locality_groups because of its FULLTEXT
    // index — see 2026_07_15_120000). No such restriction applies here: this only builds
    // a new B-tree, so reads/writes against occurrences keep working while it builds.
    // Explicit ALGORITHM/LOCK still spelled out so a MySQL/MariaDB version that would
    // otherwise silently fall back to a full table rebuild fails loudly instead.
    public function up(): void
    {
        DB::statement('
            ALTER TABLE occurrences
            ADD INDEX occurrences_dataset_key_georef_status (dataset_key, georef_status),
            ALGORITHM=INPLACE, LOCK=NONE
        ');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE occurrences DROP INDEX occurrences_dataset_key_georef_status');
    }
};
