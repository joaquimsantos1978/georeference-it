<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GbifPruneCorrupted extends Command
{
    protected $signature = 'gbif:prune-corrupted {--commit : Actually soft-delete — omit for a dry-run report only}';

    protected $description = 'Soft-delete occurrences whose verbatim_locality/country_code was column-shifted by '
        . 'the LOAD DATA backslash-escape bug (fixed at import time in 9367978, but rows already written with '
        . 'the corrupted data stay that way forever) — these are permanently excluded from GeorefController::next() '
        . '(georef:corrupted_group_ids) and, as of a70dba0, from Stats/Impact counts, so they can never actually be '
        . 'worked on; this removes them instead of just hiding them';

    public function handle(): int
    {
        $commit = $this->option('commit');

        // Same detection signature as RefreshImpactCounts::compute() (source of the
        // georef:corrupted_group_ids cache) and GeorefController::next()'s exclusion —
        // recomputed fresh here rather than read from cache so a prune run is never
        // stale by up to 10 minutes. Scoped through locality_groups first (a few tens of
        // millions of indexed rows) rather than running these two REGEXPs directly
        // against occurrences (200M+ rows, no usable index for a functional REGEXP) —
        // the exact anti-pattern that made a single /georef/next request take 10-35s
        // before 5e716a0 cached this same list.
        $corruptedGroupIds = DB::table('locality_groups')
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNotNull('verbatim_locality')
                  ->whereRaw("verbatim_locality REGEXP '[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}'");
            })
            ->orWhere(function ($q) {
                $q->whereNotNull('country_code')
                  ->whereRaw("country_code REGEXP BINARY '[a-z]'");
            })
            ->pluck('id');

        $this->info("Corrupted locality_groups: {$corruptedGroupIds->count()}");

        if ($corruptedGroupIds->isEmpty()) {
            $this->info('Nothing to prune.');
            return self::SUCCESS;
        }

        // Per-occurrence check (not "delete every occurrence in a corrupted group") —
        // locality_group_id is indexed, so scoping to these groups first keeps this
        // cheap, but the actual corruption is a property of the occurrence row itself.
        // In practice each corrupted group has held exactly one occurrence in every
        // sample seen so far, but checking per-row rather than per-group is what
        // guarantees a group that happens to also hold an unrelated, uncorrupted
        // occurrence never loses it by association.
        $affected = DB::table('occurrences')
            ->whereIn('locality_group_id', $corruptedGroupIds)
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNotNull('verbatim_locality')
                  ->whereRaw("verbatim_locality REGEXP '[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}'");
            })
            ->orWhere(function ($q) {
                $q->whereNotNull('country_code')
                  ->whereRaw("country_code REGEXP BINARY '[a-z]'");
            });

        $affectedCount = (clone $affected)->count();
        $this->info("Corrupted occurrences (within those groups): {$affectedCount}");

        if ($affectedCount === 0) {
            $this->info('Nothing to prune.');
            return self::SUCCESS;
        }

        if (!$commit) {
            $this->warn('Dry run only — pass --commit to actually soft-delete these rows and recalculate counters.');
            return self::SUCCESS;
        }

        $groupIdsToRecalc = (clone $affected)->distinct()->pluck('locality_group_id');

        $deleted = $affected->update(['deleted_at' => now()]);
        $this->info("Soft-deleted {$deleted} occurrences.");

        // Same counter recompute + empty-group soft-delete as GbifImportDownload's
        // Step 2/2 (--prune-deleted), just scoped to the handful of groups touched
        // above instead of a full-table batch pass — there's no need to re-walk every
        // locality_group in the database to fix counters on the few hundred/thousand
        // this actually changed.
        $ids = $groupIdsToRecalc->implode(',');

        DB::statement("
            UPDATE locality_groups lg
            JOIN (
                SELECT locality_group_id,
                    COUNT(*) AS total,
                    SUM(georef_status IN ('has_suggestion', 'conflicted')) AS pending,
                    SUM(georef_status = 'has_suggestion') AS has_suggestion,
                    SUM(georef_status = 'conflicted') AS conflicted,
                    SUM(georef_status = 'validated') AS validated,
                    SUM(georef_status = 'ungeoreferenced') AS ungeoreferenced,
                    SUM(georef_status = 'gbif_georeferenced') AS gbif_georeferenced,
                    SUM(georef_status = 'gbif_reviewed') AS gbif_reviewed
                FROM occurrences
                WHERE locality_group_id IN ({$ids})
                  AND deleted_at IS NULL
                GROUP BY locality_group_id
            ) c ON c.locality_group_id = lg.id
            SET
                lg.occurrence_count         = c.total,
                lg.pending_count            = c.pending,
                lg.has_suggestion_count     = c.has_suggestion,
                lg.conflicted_count         = c.conflicted,
                lg.validated_count          = c.validated,
                lg.ungeoreferenced_count    = c.ungeoreferenced,
                lg.gbif_georeferenced_count = c.gbif_georeferenced,
                lg.gbif_reviewed_count      = c.gbif_reviewed,
                lg.updated_at               = NOW()
            WHERE lg.id IN ({$ids})
        ");

        // Groups left with zero remaining (non-deleted) occurrences — every corrupted
        // group observed so far held exactly one occurrence, so this is the common case.
        DB::statement("
            UPDATE locality_groups lg
            LEFT JOIN occurrences o ON o.locality_group_id = lg.id AND o.deleted_at IS NULL
            SET lg.occurrence_count         = 0,
                lg.pending_count            = 0,
                lg.has_suggestion_count     = 0,
                lg.conflicted_count         = 0,
                lg.validated_count          = 0,
                lg.ungeoreferenced_count    = 0,
                lg.gbif_georeferenced_count = 0,
                lg.gbif_reviewed_count      = 0,
                lg.updated_at               = NOW()
            WHERE o.id IS NULL
              AND lg.id IN ({$ids})
        ");

        $emptyGroups = DB::table('locality_groups')
            ->whereIn('id', $corruptedGroupIds)
            ->where('occurrence_count', 0)
            ->whereNull('deleted_at')
            ->update(['deleted_at' => now()]);

        $this->info("Recalculated counters for {$groupIdsToRecalc->count()} locality_groups; soft-deleted {$emptyGroups} that are now empty.");
        $this->info('Done. Run `php artisan impact:refresh-counts` to refresh Stats/Impact/Explore with the cleaned-up numbers.');

        return self::SUCCESS;
    }
}
