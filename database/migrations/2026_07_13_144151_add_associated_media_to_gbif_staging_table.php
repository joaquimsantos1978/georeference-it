<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// gbif_staging can hold 280M+ rows while an import is in progress (it does right now) —
// same ALGORITHM=INSTANT caution as the occurrences migrations this session: a plain
// Schema::table() ALTER would need to rewrite the whole table.
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE gbif_staging ADD COLUMN associated_media TEXT NULL AFTER synced_at, ALGORITHM=INSTANT');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE gbif_staging DROP COLUMN associated_media, ALGORITHM=INSTANT');
    }
};
