<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE locality_groups
            ADD COLUMN consistency_status ENUM('unchecked','consistent','inconsistent','resolved')
            NOT NULL DEFAULT 'unchecked'
            AFTER validated_count
        ");
        DB::statement("ALTER TABLE locality_groups
            ADD INDEX idx_consistency_status (consistency_status)
        ");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE locality_groups DROP INDEX idx_consistency_status");
        DB::statement("ALTER TABLE locality_groups DROP COLUMN consistency_status");
    }
};
