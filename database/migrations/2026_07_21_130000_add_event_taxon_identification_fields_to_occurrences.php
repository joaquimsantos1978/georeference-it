<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Adds the Event (year/month/day), Taxon (phylum/class/order/genus/specific_epithet) and
// Identification (type_status) Darwin Core groups — needed as criteria fields for thematic
// projects. All nullable with no default, so this qualifies for ALGORITHM=INSTANT — metadata-
// only, no table rewrite, safe on the full production table (gbif_staging can hold 283M+ rows
// too, per past sessions where a --prune-deleted run left it un-truncated).
//
// Explicit ALGORITHM=INSTANT (raw SQL, not plain Schema::table()) rather than trusting
// Laravel's default to pick whatever MariaDB falls back to — same reasoning as
// 2026_07_12_000002_add_not_georeferenceable_audit_to_occurrences.php: these columns are
// added with AFTER (not at the end of the table), and forcing INSTANT means this either
// completes as metadata-only or fails loudly right away, instead of silently doing a slow
// full-table rewrite on a 225-283M-row table if instant somehow isn't available. That
// earlier migration already proved AFTER-positioned instant ADD COLUMN works on this
// MariaDB version, so this isn't new territory.
//
// Existing rows stay NULL until the next gbif:import-download/monthly-refresh reprocesses
// them (termMap() wiring is a separate change) — no special backfill import needed.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE occurrences
            ADD COLUMN `year` SMALLINT UNSIGNED NULL AFTER event_date,
            ADD COLUMN `month` TINYINT UNSIGNED NULL AFTER `year`,
            ADD COLUMN `day` TINYINT UNSIGNED NULL AFTER `month`,
            ADD COLUMN phylum VARCHAR(255) NULL AFTER kingdom,
            ADD COLUMN `class` VARCHAR(255) NULL AFTER phylum,
            ADD COLUMN `order` VARCHAR(255) NULL AFTER `class`,
            ADD COLUMN genus VARCHAR(255) NULL AFTER family,
            ADD COLUMN specific_epithet VARCHAR(255) NULL AFTER genus,
            ADD COLUMN type_status VARCHAR(255) NULL AFTER specific_epithet,
            ALGORITHM=INSTANT
        ");

        DB::statement("
            ALTER TABLE gbif_staging
            ADD COLUMN `year` SMALLINT UNSIGNED NULL AFTER event_date,
            ADD COLUMN `month` TINYINT UNSIGNED NULL AFTER `year`,
            ADD COLUMN `day` TINYINT UNSIGNED NULL AFTER `month`,
            ADD COLUMN phylum VARCHAR(100) NULL AFTER kingdom,
            ADD COLUMN `class` VARCHAR(100) NULL AFTER phylum,
            ADD COLUMN `order` VARCHAR(100) NULL AFTER `class`,
            ADD COLUMN genus VARCHAR(100) NULL AFTER family,
            ADD COLUMN specific_epithet VARCHAR(100) NULL AFTER genus,
            ADD COLUMN type_status VARCHAR(255) NULL AFTER specific_epithet,
            ALGORITHM=INSTANT
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE occurrences
            DROP COLUMN `year`, DROP COLUMN `month`, DROP COLUMN `day`,
            DROP COLUMN phylum, DROP COLUMN `class`, DROP COLUMN `order`,
            DROP COLUMN genus, DROP COLUMN specific_epithet, DROP COLUMN type_status,
            ALGORITHM=INSTANT
        ");

        DB::statement("
            ALTER TABLE gbif_staging
            DROP COLUMN `year`, DROP COLUMN `month`, DROP COLUMN `day`,
            DROP COLUMN phylum, DROP COLUMN `class`, DROP COLUMN `order`,
            DROP COLUMN genus, DROP COLUMN specific_epithet, DROP COLUMN type_status,
            ALGORITHM=INSTANT
        ");
    }
};
