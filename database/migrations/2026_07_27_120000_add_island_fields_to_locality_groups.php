<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// island/island_group already drive group_hash (LocalityGroup::hashFromOccurrence()) and are
// baked into locality_string as a fallback, but were never stored as their own columns —
// occurrences.island/island_group have always been populated correctly, this table just never
// carried them, so the georef panel could never display them for any group.
//
// NOT applied via a plain migrate on production: locality_groups has a FULLTEXT index on
// locality_string, and adding a column here requires at least LOCK=SHARED (blocks writes —
// see 2026_07_15_120000_add_status_breakdown_counts_to_locality_groups.php), which combined
// with the current multi-day GBIF merge's concurrent writes makes this a pt-online-schema-change
// job for the next maintenance window, not a live `php artisan migrate`. This file exists so
// the schema is documented and `migrate` reflects reality once that run has happened — apply the
// ALTER by hand via pt-osc first, then mark this migration as run.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            ALTER TABLE locality_groups
                ADD COLUMN island VARCHAR(255) NULL,
                ADD COLUMN island_group VARCHAR(255) NULL,
                ALGORITHM=INPLACE, LOCK=SHARED
        ');
    }

    public function down(): void
    {
        Schema::table('locality_groups', function (Blueprint $table) {
            $table->dropColumn(['island', 'island_group']);
        });
    }
};
