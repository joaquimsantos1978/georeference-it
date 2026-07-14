<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Reverses 2026_07_14_083631_create_explore_countries_table — turned out to be
// unnecessary machinery. The Explore/Impact/Activity country dropdowns switched to a
// plain live DISTINCT query (see ExploreController) instead, which stays cheap via a
// loose index scan regardless of table size, so nothing reads this table any more.
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('explore_countries');
    }

    public function down(): void
    {
        Schema::create('explore_countries', function (Blueprint $table) {
            $table->string('country_code', 2)->primary();
            $table->timestamp('computed_at')->nullable();
        });
    }
};
