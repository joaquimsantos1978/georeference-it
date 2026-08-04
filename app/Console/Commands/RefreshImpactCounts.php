<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RefreshImpactCounts extends Command
{
    protected $signature = 'impact:refresh-counts';

    protected $description = 'Recompute Impact/Explore/Activity/Stats page counts in the background — the only '
        . 'thing that ever runs these queries, so a page request never gets stuck behind a live recompute';

    private const VALID_STATUSES = ['has_suggestion', 'validated', 'gbif_reviewed'];

    public function handle(): int
    {
        // withoutOverlapping() in bootstrap/app.php's schedule only guards
        // scheduler-launched instances against each other — it has no visibility into
        // a manually-started `php artisan impact:refresh-counts`, and the pause flag
        // used to sidestep that is easy to let expire on a long run. A real lock here
        // protects every invocation path the same way. Ceiling is generous (4h) since
        // it's just a safety net against a truly stuck/crashed process holding it
        // forever, not an expected duration — the lock is released in the finally
        // block on every normal exit.
        $lock = Cache::lock('impact:refresh-counts:running', 14400);
        if (!$lock->get()) {
            $this->warn('Another impact:refresh-counts is already running — skipping this invocation.');
            return self::SUCCESS;
        }

        try {
            return $this->compute();
        } finally {
            $lock->release();
        }
    }

    private function compute(): int
    {
        // Corrupted-locality exclusion list for GeorefController::next(), computed here
        // rather than live — REGEXP against verbatim_locality/country_code has no index
        // to use (functional, not sargable), so evaluating it per-request forced every
        // candidate row to be fetched in full instead of served from an index: a single
        // country-scoped /georef/next request was observed taking 10-35s purely from
        // this (confirmed via EXPLAIN/timing — down to ~0.4-0.6s once replaced with a
        // plain whereNotIn against this cached ID list).
        // whereNull('deleted_at') matters here — without it this also matches groups
        // already soft-deleted for any unrelated reason whose frozen verbatim_locality/
        // country_code happens to fit the pattern, which is how this list drifted to
        // 10,000+ stale entries (a group already excluded via deleted_at everywhere else
        // gains nothing from also being on this list, but the giant whereNotIn() built
        // from it below gets slower for every one that's on it for no reason).
        $corruptedGroupIds = DB::table('locality_groups')
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereNotNull('verbatim_locality')
                       ->whereRaw("verbatim_locality REGEXP '[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}'");
                })->orWhere(function ($q2) {
                    $q2->whereNotNull('country_code')
                       ->whereRaw("country_code REGEXP BINARY '[a-z]'");
                });
            })
            ->pluck('id');
        Cache::forever('georef:corrupted_group_ids', $corruptedGroupIds->all());
        $this->info('Refreshed corrupted-group exclusion list: ' . $corruptedGroupIds->count() . ' groups');

        // Everything below — Stats' per-country table, Impact's per-(status,country)
        // counts, and Stats' global totals — used to be computed by scanning
        // `occurrences` directly (225M+ rows, three separate full passes: a
        // GROUP BY ... WITH ROLLUP keyed on TRIM(country_code) for the per-country/status
        // breakdown, another WITH ROLLUP for the per-status grand totals, and a plain
        // COUNT/SUM for the global row). TRIM() makes the grouping key non-sargable, so
        // none of the three could use an index — each was a genuine full-table scan, and
        // three of them back-to-back is what actually produced the 2-3+ hour runtimes
        // observed in production.
        //
        // `locality_groups` already carries a live per-group breakdown across all 6
        // georef_status values (has_suggestion_count, conflicted_count, validated_count,
        // ungeoreferenced_count, gbif_georeferenced_count, gbif_reviewed_count —
        // maintained synchronously by LocalityGroup::recalculateCounters() after every
        // user action, and by the batched counter-update step at the end of a GBIF
        // import). Every number below is now a SUM over that table instead: tens of
        // millions of narrow rows with a plain (non-functional) country_code index,
        // instead of hundreds of millions of wide rows with no usable index at all.
        $countryList = DB::table('locality_groups')
            ->selectRaw('TRIM(country_code) AS country_code')
            ->whereNotNull('country_code')
            ->where('country_code', '!=', '')
            ->distinct()
            ->pluck('country_code');

        // A group in $corruptedGroupIds is permanently excluded from every candidate
        // query in GeorefController::next() (see $excludeCorrupted there) — its
        // ungeoreferenced/pending occurrences can never actually be served to a user.
        // Counting them here anyway is exactly how "2 remaining in Curaçao" and then
        // "no occurrences found" for that same country happened: Stats/Impact showed
        // phantom work next() would never hand out. Exclude the same ids here so the
        // numbers only ever promise what's reachable.
        $excludeCorruptedGroups = fn ($q) => $q->whereNotIn('id', $corruptedGroupIds);

        $now  = now();
        $rows = [];

        $byCountry = collect();
        $countryStart = microtime(true);
        foreach ($countryList as $i => $code) {
            $t0  = microtime(true);
            $agg = DB::table('locality_groups')
                ->selectRaw('
                    SUM(occurrence_count)                                         AS total_occ,
                    SUM(ungeoreferenced_count)                                    AS ungeoref_occ,
                    SUM(pending_count)                                            AS pending_occ,
                    SUM(has_suggestion_count)                                     AS has_suggestion_occ,
                    SUM(validated_count)                                          AS validated_occ,
                    SUM(gbif_reviewed_count)                                      AS gbif_reviewed_occ,
                    SUM(GREATEST(0, CAST(occurrence_count AS SIGNED) - CAST(ungeoreferenced_count AS SIGNED) - CAST(pending_count AS SIGNED))) AS georef_occ,
                    COUNT(*)                                                      AS total_groups,
                    SUM(ungeoreferenced_count > 0 OR pending_count > 0)          AS pending_groups
                ')
                ->where('country_code', $code)
                ->whereNull('deleted_at')
                ->where('occurrence_count', '>', 0)
                ->tap($excludeCorruptedGroups)
                ->first();
            $elapsed = microtime(true) - $t0;

            // Visibility for next time this is slow: which country, and how long each
            // one actually took — without this, a stuck run just looks like silence
            // until it either finishes or someone starts poking at SHOW PROCESSLIST.
            if ($elapsed > 2.0) {
                $this->info(sprintf('  %s took %.1fs (%d/%d)', $code, $elapsed, $i + 1, $countryList->count()));
            }

            // Countries with no group matching the filters (occurrence_count > 0,
            // not deleted) contribute nothing — same as before, when they simply
            // never appeared in the old query's GROUP BY output.
            if ((int) $agg->total_groups > 0) {
                $agg->country_code = $code;
                $byCountry->push($agg);

                // total_groups here is exactly ExploreController's "country=X, no other
                // filters" count (occurrence_count > 0, deleted_at IS NULL, country_code
                // = X) — precomputing it means that filter combination never has to hit
                // countWithStaleWhileRevalidate()'s live COUNT(*) fallback, observed
                // taking minutes on this table when it does. Only country-only is
                // covered; every other filter combination (status, search, dataset_key)
                // still computes lazily on first hit — too many combinations to
                // precompute them all, but "just a country" is the single most common one.
                $exploreCountryKey = 'explore_count_' . md5(json_encode(['country' => $code]));
                Cache::forever($exploreCountryKey . ':data', (int) $agg->total_groups);
                Cache::forever($exploreCountryKey . ':computed_at', now()->timestamp);

                // impact_counts rows for this country — read straight off the same
                // aggregate the per-country loop already computed, no second query.
                // "all" is the sum of the 3 valid statuses for this country — what
                // ImpactController reads when a country filter is set but no status
                // filter is (default view for that country).
                $rows["has_suggestion|{$code}"] = ['status' => 'has_suggestion', 'country_code' => $code, 'count' => (int) $agg->has_suggestion_occ, 'computed_at' => $now];
                $rows["validated|{$code}"]      = ['status' => 'validated',      'country_code' => $code, 'count' => (int) $agg->validated_occ,      'computed_at' => $now];
                $rows["gbif_reviewed|{$code}"]  = ['status' => 'gbif_reviewed',  'country_code' => $code, 'count' => (int) $agg->gbif_reviewed_occ,  'computed_at' => $now];
                $rows["all|{$code}"]            = ['status' => 'all', 'country_code' => $code, 'count' => (int) ($agg->has_suggestion_occ + $agg->validated_occ + $agg->gbif_reviewed_occ), 'computed_at' => $now];
            }
        }
        $this->info(sprintf('Per-country loop: %d countries in %.1fs', $countryList->count(), microtime(true) - $countryStart));

        // Global totals — unfiltered by country on purpose, so groups with a NULL or
        // blank country_code (unresolved locality) are still included, matching what
        // the old direct-occurrences query counted. Still just a SUM over
        // locality_groups (tens of millions of narrow rows), not occurrences.
        $global = DB::table('locality_groups')
            ->whereNull('deleted_at')
            ->where('occurrence_count', '>', 0)
            ->tap($excludeCorruptedGroups)
            ->selectRaw("
                SUM(occurrence_count)         AS total_occ,
                SUM(ungeoreferenced_count)    AS ungeoref_occ,
                SUM(has_suggestion_count)     AS pending_occ,
                SUM(gbif_georeferenced_count) AS gbif_occ,
                SUM(validated_count)          AS validated_occ,
                SUM(gbif_reviewed_count)      AS gbif_reviewed_occ
            ")
            ->first();

        $global->pending_groups = DB::table('locality_groups')
            ->whereNull('deleted_at')
            // Parens are load-bearing: whereRaw() is AND-ed in as-is, so without them the
            // OR silently escapes the deleted_at/exclusion filters — this used to compile
            // to "deleted_at IS NULL AND ungeoreferenced_count > 0 OR (pending_count > 0
            // AND id NOT IN (...))", letting corrupted groups slip back in through the
            // ungeoreferenced_count side.
            ->whereRaw('(ungeoreferenced_count > 0 OR pending_count > 0)')
            ->tap($excludeCorruptedGroups)
            ->count();

        // "All countries" subtotals for impact_counts, straight from $global above —
        // no extra query, and correctly includes NULL/blank-country groups the
        // per-country loop above never touches. "all|all" is the true grand total
        // across every status and country — what ImpactController reads by default
        // with no filters applied at all.
        $rows['has_suggestion|all'] = ['status' => 'has_suggestion', 'country_code' => 'all', 'count' => (int) $global->pending_occ,    'computed_at' => $now];
        $rows['validated|all']      = ['status' => 'validated',      'country_code' => 'all', 'count' => (int) $global->validated_occ,  'computed_at' => $now];
        $rows['gbif_reviewed|all']  = ['status' => 'gbif_reviewed',  'country_code' => 'all', 'count' => (int) $global->gbif_reviewed_occ, 'computed_at' => $now];
        $rows['all|all']            = ['status' => 'all', 'country_code' => 'all', 'count' => (int) ($global->pending_occ + $global->validated_occ + $global->gbif_reviewed_occ), 'computed_at' => $now];

        $rows = array_values($rows);

        // Not wrapped in DB::transaction(): TRUNCATE is DDL and auto-commits in MySQL,
        // which silently closes any surrounding transaction — Laravel's transaction
        // wrapper then throws "There is no active transaction" when it tries to commit
        // at the end, even though the truncate + every insert already succeeded. This
        // table is fully rebuilt from scratch every run regardless, so there's nothing
        // an explicit transaction here was actually protecting.
        DB::table('impact_counts')->truncate();
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('impact_counts')->insert($chunk);
        }

        $this->info('Refreshed impact_counts: ' . count($rows) . ' rows');

        // Pre-computes the Explore page's default (no filters applied) total count — by far
        // the most common case, hit by anyone just landing on the page — so that view never
        // has to fall back to computing it live (see ExploreController::countWithStaleWhileRevalidate).
        // Filtered/searched views still compute lazily on demand; there are too many possible
        // filter combinations to precompute them all here.
        $exploreDefaultCount = DB::table('locality_groups')->where('occurrence_count', '>', 0)->count();
        $exploreDefaultKey   = 'explore_count_' . md5(json_encode([]));
        Cache::forever($exploreDefaultKey . ':data', $exploreDefaultCount);
        Cache::forever($exploreDefaultKey . ':computed_at', now()->timestamp);
        $this->info('Refreshed explore default count (' . $exploreDefaultCount . ')');

        Cache::forever('stats.georef.data', [$global, $byCountry]);
        Cache::forever('stats.georef.computed_at', now()->timestamp);
        $this->info('Refreshed stats.georef');

        return self::SUCCESS;
    }
}
