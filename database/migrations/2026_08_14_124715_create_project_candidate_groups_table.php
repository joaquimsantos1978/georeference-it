<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Materialized candidate pool for criteria-mode projects — see
// ProjectsRefreshCandidates and GeorefController::candidateGroupIdsFromCache(). Replaces a
// live per-request scan of `occurrences` against a project's (possibly unindexed, e.g. a
// LIKE fallback on a field with no FULLTEXT index yet) criteria — that scan is fine to pay
// once per refresh cycle in the background, not once per /georef/next request (observed
// taking 100+ seconds live for a project combining an indexed field with a LIKE fallback
// field, directly blocking a user trying to georeference).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_candidate_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            // 'georef' = locality_group has an occurrence matching this project's criteria
            // with georef_status = 'ungeoreferenced'; 'validate' = has_suggestion/conflicted.
            // Same two buckets GeorefController::candidateGroupIds() already queries live.
            $table->enum('status_bucket', ['georef', 'validate']);
            $table->foreignId('locality_group_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->unique(['project_id', 'status_bucket', 'locality_group_id'], 'uniq_project_bucket_group');
            $table->index(['project_id', 'status_bucket'], 'idx_project_bucket');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_candidate_groups');
    }
};
