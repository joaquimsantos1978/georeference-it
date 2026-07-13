<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RefreshImpactCounts extends Command
{
    protected $signature = 'impact:refresh-counts';

    protected $description = 'Recompute Impact/Explore/Activity page counts in the background — the only thing '
        . 'that ever runs these queries, so a page request never gets stuck behind a live recompute';

    private const VALID_STATUSES = ['has_suggestion', 'validated', 'gbif_reviewed'];

    public function handle(): int
    {
        // Single scan of locality_groups serves both explore_countries (just the list of
        // codes) and stats.georef's byCountry aggregates further below — these used to be
        // two separate full scans with identical WHERE filters (deleted_at IS NULL,
        // occurrence_count > 0, country_code NOT NULL) against a table that's grown to
        // tens of millions of rows (EXPLAIN showed ~48M rows examined for a plain DISTINCT
        // alone, since none of the available indexes cover occurrence_count/deleted_at
        // together with country_code). Computing the GROUP BY once and deriving the
        // country list from its keys halves that cost. No REGEXP validity filter on
        // country_code here on purpose either: GbifImportDownload already nulls out
        // anything that doesn't match ^[A-Z]{2}$ at staging time, before locality_groups/
        // occurrences are ever populated from it, so whereNotNull() alone is sufficient —
        // REGEXP can't use an index and was pure wasted scan cost for a condition already
        // guaranteed.
        $byCountry = DB::table('locality_groups')
            ->selectRaw('
                country_code,
                SUM(occurrence_count)                                         AS total_occ,
                SUM(ungeoreferenced_count)                                    AS ungeoref_occ,
                SUM(pending_count)                                            AS pending_occ,
                SUM(validated_count)                                          AS validated_occ,
                SUM(GREATEST(0, CAST(occurrence_count AS SIGNED) - CAST(ungeoreferenced_count AS SIGNED) - CAST(pending_count AS SIGNED))) AS georef_occ,
                COUNT(*)                                                      AS total_groups,
                SUM(ungeoreferenced_count > 0 OR pending_count > 0)          AS pending_groups
            ')
            ->whereNull('deleted_at')
            ->where('occurrence_count', '>', 0)
            ->whereNotNull('country_code')
            ->groupBy('country_code')
            // No ORDER BY here on purpose: forced MariaDB to materialize the whole
            // grouped result into a temp table before a filesort could run (EXPLAIN:
            // "Using temporary; Using filesort") — pure overhead, since the stats page
            // re-sorts this table entirely client-side anyway (stats.blade.php's
            // sortTable(), driven by a dropdown), so the initial order is never used.
            ->get();

        $countries = $byCountry->pluck('country_code')->sort()->values();

        Cache::forever('explore_countries:data', $countries);
        Cache::forever('explore_countries:computed_at', now()->timestamp);
        $this->info('Refreshed explore_countries (' . $countries->count() . ' countries)');

        // Previously one COUNT(*) per (status, country) combination — ~980 separate
        // full aggregate queries against a 225M+ row table, observed taking 10-15+
        // minutes and starving live traffic of I/O. GROUP BY ... WITH ROLLUP computes
        // every subtotal a status/country filter combination could need in two passes
        // instead: this one for pair-level (country, status) and per-country-all-statuses
        // (WITH ROLLUP's subtotal row, status = NULL); the second below for per-status-
        // all-countries and the grand total.
        //
        // country_code IS NOT NULL is deliberate, not just a filter: occurrences can
        // genuinely have a NULL country_code (unresolved locality), and ROLLUP's
        // subtotal rows also show NULL for the rolled-up column — with a nullable real
        // column there'd be no way to tell "real data with no country" apart from
        // "rollup's grand-total marker" without GROUPING(). Excluding real NULLs up
        // front sidesteps that ambiguity entirely; those rows are still correctly
        // included in the second query's country-agnostic totals below.
        $statusList = "'" . implode("','", self::VALID_STATUSES) . "'";

        $byCountryAndStatus = DB::select("
            SELECT country_code, georef_status, COUNT(*) as cnt
            FROM occurrences
            WHERE deleted_at IS NULL AND georef_status IN ({$statusList}) AND country_code IS NOT NULL
            GROUP BY country_code, georef_status WITH ROLLUP
        ");

        // Built up here and written to impact_counts in one TRUNCATE + bulk INSERT below,
        // rather than one Cache::forever() call per (status, country) combination — that
        // used to mean ~2000 individual cache writes per refresh, and left permanently
        // stale entries behind for any country/status combo that stops matching (nothing
        // ever deleted an individual key). A full-table replace can't accumulate garbage.
        $rows = [];
        $now  = now();

        foreach ($byCountryAndStatus as $row) {
            // The single remaining NULL-country row is ROLLUP's own grand-total-of-known-
            // countries marker (real NULLs were excluded above) — not the true grand total,
            // since it excludes unresolved-locality occurrences. The second query below
            // provides the real grand total unambiguously; skip this row entirely.
            if ($row->country_code === null) {
                continue;
            }
            $rows[] = [
                'status'       => $row->georef_status ?: 'all',
                'country_code' => $row->country_code,
                'count'        => (int) $row->cnt,
                'computed_at'  => $now,
            ];
        }

        // Grouped by status only — naturally includes occurrences with a NULL country_code
        // in every total, so this is the correct source for "all countries" subtotals
        // (status set, country = "all") and the true grand total (both "all").
        $byStatusOnly = DB::select("
            SELECT georef_status, COUNT(*) as cnt
            FROM occurrences
            WHERE deleted_at IS NULL AND georef_status IN ({$statusList})
            GROUP BY georef_status WITH ROLLUP
        ");
        foreach ($byStatusOnly as $row) {
            $rows[] = [
                'status'       => $row->georef_status ?: 'all',
                'country_code' => 'all',
                'count'        => (int) $row->cnt,
                'computed_at'  => $now,
            ];
        }

        // Any country with zero matching occurrences never appears in the GROUP BY
        // results above — fill those combinations in as 0 rather than leaving them
        // absent (a missing row reads the same as 0 via COALESCE at read time, but
        // being explicit here keeps every (status, country) combination present).
        $seenCountries = collect($byCountryAndStatus)->pluck('country_code')->filter()->unique();
        $statusOptionsForZeroFill = collect(self::VALID_STATUSES)->prepend(null);
        foreach ($countries->diff($seenCountries) as $missingCountry) {
            foreach ($statusOptionsForZeroFill as $status) {
                $rows[] = [
                    'status'       => $status ?: 'all',
                    'country_code' => $missingCountry,
                    'count'        => 0,
                    'computed_at'  => $now,
                ];
            }
        }

        DB::transaction(function () use ($rows) {
            DB::table('impact_counts')->truncate();
            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('impact_counts')->insert($chunk);
            }
        });

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

        // Moved here from StatsController's own lock-protected inline compute: that
        // approach still let whichever request won the lock pay the multi-minute cost
        // itself, and repeatedly observed multiple copies of the exact same expensive
        // query running at once in production despite the lock (root cause unconfirmed —
        // possibly abandoned queries from OOM-killed PHP-FPM workers never actually
        // released it). Same fix as Impact/Explore: never compute this on a web request.
        $global = DB::table('occurrences')
            ->whereNull('deleted_at')
            ->selectRaw("
                COUNT(*)                                             AS total_occ,
                SUM(georef_status = 'ungeoreferenced')               AS ungeoref_occ,
                SUM(georef_status = 'has_suggestion')                AS pending_occ,
                SUM(georef_status = 'gbif_georeferenced')            AS gbif_occ,
                SUM(georef_status = 'validated')                     AS validated_occ,
                SUM(georef_status = 'gbif_reviewed')                 AS gbif_reviewed_occ
            ")
            ->first();

        $global->pending_groups = DB::table('locality_groups')
            ->whereNull('deleted_at')
            ->whereRaw('ungeoreferenced_count > 0 OR pending_count > 0')
            ->count();

        // $byCountry was already computed once at the top of handle() (reused for
        // explore_countries too) — no need to run the same GROUP BY a second time.
        Cache::forever('stats.georef.data', [$global, $byCountry]);
        Cache::forever('stats.georef.computed_at', now()->timestamp);
        $this->info('Refreshed stats.georef');

        return self::SUCCESS;
    }
}
