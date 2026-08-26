<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

// Supports Explore's new "most recently updated" sort — locality_groups.updated_at is
// touched by recalculateCounters() on every georef action, so it already doubles as "last
// activity" without a dedicated column. No index existed for it before, so sorting by it
// over the full table would have meant a filesort across tens of millions of rows.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locality_groups', function ($table) {
            $table->index('updated_at', 'idx_lg_updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('locality_groups', function ($table) {
            $table->dropIndex('idx_lg_updated_at');
        });
    }
};
