<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// pending_count already covers has_suggestion + conflicted combined (kept as-is, existing
// indexes idx_lg_pending_occ / idx_lg_country_pending_occ / idx_lg_activity depend on it).
// These four columns give the remaining breakdown needed to answer any occurrences.georef_status
// question (all 6 enum values) straight from locality_groups instead of scanning occurrences —
// has_suggestion_count + conflicted_count = pending_count, and together with occurrence_count,
// ungeoreferenced_count and validated_count the six columns always sum to occurrence_count.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locality_groups', function (Blueprint $table) {
            $table->unsignedInteger('has_suggestion_count')->default(0)->after('pending_count');
            $table->unsignedInteger('conflicted_count')->default(0)->after('has_suggestion_count');
            $table->unsignedInteger('gbif_georeferenced_count')->default(0)->after('conflicted_count');
            $table->unsignedInteger('gbif_reviewed_count')->default(0)->after('gbif_georeferenced_count');
        });
    }

    public function down(): void
    {
        Schema::table('locality_groups', function (Blueprint $table) {
            $table->dropColumn(['has_suggestion_count', 'conflicted_count', 'gbif_georeferenced_count', 'gbif_reviewed_count']);
        });
    }
};
