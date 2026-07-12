<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // ALGORITHM=INSTANT: appending a new value to the end of an ENUM is a metadata-only
    // change in MariaDB 10.3+/MySQL 8+ — no table rewrite, safe to run on a 225M+ row table
    // even under load (unlike most ALTER TABLEs on this table, which take hours).
    public function up(): void
    {
        DB::statement("
            ALTER TABLE occurrences
            MODIFY georef_status ENUM(
                'ungeoreferenced',
                'has_suggestion',
                'validated',
                'gbif_georeferenced',
                'gbif_reviewed',
                'conflicted',
                'not_georeferenceable'
            ) NOT NULL DEFAULT 'ungeoreferenced',
            ALGORITHM=INSTANT
        ");
    }

    public function down(): void
    {
        DB::statement("
            UPDATE occurrences SET georef_status = 'ungeoreferenced' WHERE georef_status = 'not_georeferenceable'
        ");
        DB::statement("
            ALTER TABLE occurrences
            MODIFY georef_status ENUM(
                'ungeoreferenced',
                'has_suggestion',
                'validated',
                'gbif_georeferenced',
                'gbif_reviewed',
                'conflicted'
            ) NOT NULL DEFAULT 'ungeoreferenced',
            ALGORITHM=INSTANT
        ");
    }
};
