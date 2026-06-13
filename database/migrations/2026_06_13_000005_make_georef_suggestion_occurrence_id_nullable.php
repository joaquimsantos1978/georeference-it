<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // System-generated suggestions (consistency check, auto-suggest) have no
        // single representative occurrence_id — make the FK nullable.
        DB::statement("ALTER TABLE georef_suggestions MODIFY COLUMN occurrence_id BIGINT UNSIGNED NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE georef_suggestions MODIFY COLUMN occurrence_id BIGINT UNSIGNED NOT NULL");
    }
};
