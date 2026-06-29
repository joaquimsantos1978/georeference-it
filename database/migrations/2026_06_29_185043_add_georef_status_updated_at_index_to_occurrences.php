<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE occurrences ADD INDEX idx_georef_status_updated_id (georef_status, updated_at, id), ALGORITHM=INPLACE, LOCK=NONE');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE occurrences DROP INDEX idx_georef_status_updated_id');
    }
};
