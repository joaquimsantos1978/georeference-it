<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Reverses 2026_07_13_144151: checked the current full-world GBIF download's
// occurrence.txt header and associatedMedia isn't present at all — GBIF's own
// interpretation pipeline appears to always promote media into the multimedia.txt
// extension instead, so this column and its fallback code path were dead on arrival
// for GBIF downloads specifically (as opposed to a raw single-publisher DwC-A).
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE gbif_staging DROP COLUMN associated_media, ALGORITHM=INSTANT');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE gbif_staging ADD COLUMN associated_media TEXT NULL AFTER synced_at, ALGORITHM=INSTANT');
    }
};
