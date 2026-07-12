<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Deliberately no foreign key constraint: adding one to a 225M+ row table requires
    // building an index and isn't ALGORITHM=INSTANT-eligible, unlike a plain nullable
    // column appended at the end — which is metadata-only on MariaDB 10.4+. A soft
    // reference to users.id is enough here (this is just an audit trail, not enforced
    // referential integrity); see the many other lessons this week about ALTER TABLE
    // cost on this table.
    public function up(): void
    {
        DB::statement("
            ALTER TABLE occurrences
            ADD COLUMN not_georeferenceable_by BIGINT UNSIGNED NULL AFTER georef_status,
            ADD COLUMN not_georeferenceable_at TIMESTAMP NULL AFTER not_georeferenceable_by,
            ALGORITHM=INSTANT
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE occurrences
            DROP COLUMN not_georeferenceable_by,
            DROP COLUMN not_georeferenceable_at,
            ALGORITHM=INSTANT
        ");
    }
};
