<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Replaces the single explore_countries:data cache key (one big serialized array,
// fully overwritten on every write) with one row per country, upserted individually
// as RefreshImpactCounts' per-country loop reaches each one. The old approach made
// the visible country list temporarily shrink back to "whatever this run has
// processed so far" every time a slow run restarted, since the whole cached list was
// replaced with the in-progress accumulator rather than merged into what was already
// known good from the previous run.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('explore_countries', function (Blueprint $table) {
            $table->string('country_code', 2)->primary();
            $table->timestamp('computed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('explore_countries');
    }
};
