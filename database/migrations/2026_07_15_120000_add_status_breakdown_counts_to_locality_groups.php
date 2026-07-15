<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// pending_count already covers has_suggestion + conflicted combined (kept as-is, existing
// indexes idx_lg_pending_occ / idx_lg_country_pending_occ / idx_lg_activity depend on it).
// These four columns give the remaining breakdown needed to answer any occurrences.georef_status
// question (all 6 enum values) straight from locality_groups instead of scanning occurrences —
// has_suggestion_count + conflicted_count = pending_count, and together with occurrence_count,
// ungeoreferenced_count and validated_count the six columns always sum to occurrence_count.
//
// Raw SQL, no positional ->after() (column order has no functional meaning — Eloquent
// addresses columns by name) and an explicit algorithm/lock rather than letting MariaDB
// pick (Schema::table()'s default): ALGORITHM=INSTANT and LOCK=NONE both confirmed NOT
// supported on this table (locality_groups has a FULLTEXT index on locality_string;
// InnoDB requires at least a shared lock to maintain a FULLTEXT index's auxiliary tables
// during any rebuild, even for an unrelated plain column add — verified against a local
// copy of this schema, error 1846 "Fulltext index creation requires a lock. Try
// LOCK=SHARED"). LOCK=SHARED still allows reads (Explore/Stats/etc. keep working) but
// blocks writes to locality_groups (submit/validate/etc.) for the duration — unavoidable
// on this table's current structure, not something this migration can route around.
// ALGORITHM=INPLACE kept explicit anyway so this fails fast instead of silently falling
// back to the slower, more-locking ALGORITHM=COPY if a server can't grant even that much.
return new class extends Migration
{
    public function up(): void
    {
        // All 4 columns in a single ALTER TABLE on purpose — with a FULLTEXT index present,
        // InnoDB rebuilds the table for this regardless of algorithm, and that rebuild is
        // the expensive part; 4 separate ALTER statements would pay for that rebuild 4
        // times over instead of once.
        DB::statement('
            ALTER TABLE locality_groups
                ADD COLUMN has_suggestion_count INT UNSIGNED NOT NULL DEFAULT 0,
                ADD COLUMN conflicted_count INT UNSIGNED NOT NULL DEFAULT 0,
                ADD COLUMN gbif_georeferenced_count INT UNSIGNED NOT NULL DEFAULT 0,
                ADD COLUMN gbif_reviewed_count INT UNSIGNED NOT NULL DEFAULT 0,
                ALGORITHM=INPLACE, LOCK=SHARED
        ');
    }

    public function down(): void
    {
        Schema::table('locality_groups', function (Blueprint $table) {
            $table->dropColumn(['has_suggestion_count', 'conflicted_count', 'gbif_georeferenced_count', 'gbif_reviewed_count']);
        });
    }
};
