<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Replaces ~2000 individual Cache::forever('impact_count_{status}_{country}:*', ...)
// keys with one small table, fully replaced (TRUNCATE + bulk INSERT) on every
// RefreshImpactCounts run. Fixes two real problems the cache-key approach had:
// stale entries for countries/statuses that stop matching any data linger forever
// (Cache::forever has no expiry and nothing ever deletes an individual key), and
// writing hundreds of separate cache rows in a PHP loop is slower than one insert.
// 'status'/'country_code' use the literal string 'all' as the "no filter" sentinel
// (matching the existing cache-key convention) rather than NULL, so simple equality
// WHERE clauses work without IS NULL special-casing.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('impact_counts', function (Blueprint $table) {
            $table->string('status', 30);
            $table->string('country_code', 3);
            $table->unsignedInteger('count')->default(0);
            $table->timestamp('computed_at')->nullable();
            $table->primary(['status', 'country_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impact_counts');
    }
};
