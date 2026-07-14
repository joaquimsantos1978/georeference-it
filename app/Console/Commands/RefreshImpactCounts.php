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
        // This per-country loop exists only for stats.georef's per-country SUMs
        // (total_occ, ungeoref_occ, etc. below) — the Explore/Impact/Activity country
        // *dropdowns* are a plain live DISTINCT query now (see ExploreController),
        // since that alone stays a cheap loose index scan regardless of table size.
        // A single combined GROUP BY across all countries here was tried and dropped:
        // EXPLAIN showed ~48M rows examined (no index covers occurrence_count/
        // deleted_at together with country_code), vs. ~242K rows for one mid-size
        // country via index_merge(idx_lg_country_code, deleted_at index) when scoped
        // to a single country_code with an equality filter.
        // country_code != '' matters as much as whereNotNull() here: a blank string
        // isn't NULL, so it silently slipped through as a "country" before, then broke
        // the impact_counts insert below (a blank-country row appears both from the
        // occurrences ROLLUP directly AND from the zero-fill pass, since collect()
        // ->filter() with no callback treats '' as falsy and drops it — making the
        // zero-fill logic think it was never "seen" and re-add it, violating the
        // (status, country_code) primary key on the second, duplicate row).
        $countryList = DB::table('locality_groups')
            ->select('country_code')
            ->whereNotNull('country_code')
            ->where('country_code', '!=', '')
            ->distinct()
            ->pluck('country_code');

        $byCountry = collect();
        $countryStart = microtime(true);
        foreach ($countryList as $i => $code) {
            $t0  = microtime(true);
            $agg = DB::table('locality_groups')
                ->selectRaw('
                    SUM(occurrence_count)                                         AS total_occ,
                    SUM(ungeoreferenced_count)                                    AS ungeoref_occ,
                    SUM(pending_count)                                            AS pending_occ,
                    SUM(validated_count)                                          AS validated_occ,
                    SUM(GREATEST(0, CAST(occurrence_count AS SIGNED) - CAST(ungeoreferenced_count AS SIGNED) - CAST(pending_count AS SIGNED))) AS georef_occ,
                    COUNT(*)                                                      AS total_groups,
                    SUM(ungeoreferenced_count > 0 OR pending_count > 0)          AS pending_groups
                ')
                ->where('country_code', $code)
                ->whereNull('deleted_at')
                ->where('occurrence_count', '>', 0)
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
            }
        }
        $this->info(sprintf('Per-country loop: %d countries in %.1fs', $countryList->count(), microtime(true) - $countryStart));

        $countries = $byCountry->pluck('country_code')->sort()->values();

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

        // country_code != '' alongside IS NOT NULL: a blank string isn't NULL, so it
        // used to slip through as its own fake "country" bucket — still correctly
        // counted in the true grand total via the country-agnostic query below, just
        // not broken out as if '' were a real country code.
        $byCountryAndStatus = DB::select("
            SELECT country_code, georef_status, COUNT(*) as cnt
            FROM occurrences
            WHERE deleted_at IS NULL AND georef_status IN ({$statusList}) AND country_code IS NOT NULL AND country_code != ''
            GROUP BY country_code, georef_status WITH ROLLUP
        ");

        // Keyed by "{status}|{country_code}" rather than a flat list — impact_counts'
        // primary key is exactly that pair, and this data has repeatedly turned out to
        // have edge cases a diff()-based "have we seen this one" check missed (blank
        // strings, mixed-case fragments from a since-fixed LOAD DATA bug, and at least
        // one case of two supposedly-identical country codes that still weren't ===
        // equal — trailing whitespace or an encoding quirk, never fully diagnosed).
        // An associative array can't contain a duplicate key by construction, so this
        // is immune to that whole class of bug regardless of what the source data does:
        // last write for a given (status, country_code) simply wins.
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
            $status = $row->georef_status ?: 'all';
            $rows["{$status}|{$row->country_code}"] = [
                'status'       => $status,
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
            $status = $row->georef_status ?: 'all';
            $rows["{$status}|all"] = [
                'status'       => $status,
                'country_code' => 'all',
                'count'        => (int) $row->cnt,
                'computed_at'  => $now,
            ];
        }

        // Any country with zero matching occurrences never appears in the GROUP BY
        // results above — fill those combinations in as 0 rather than leaving them
        // absent (a missing row reads the same as 0 via COALESCE at read time, but
        // being explicit here keeps every (status, country) combination present).
        // Writing straight into $rows keyed by (status, country) means a country that
        // *does* have real data simply gets overwritten back to its real count on the
        // next iteration below rather than colliding — no diff()/seen-tracking needed.
        $statusOptionsForZeroFill = collect(self::VALID_STATUSES)->prepend(null);
        foreach ($countries as $code) {
            foreach ($statusOptionsForZeroFill as $status) {
                $status = $status ?: 'all';
                $key    = "{$status}|{$code}";
                if (!isset($rows[$key])) {
                    $rows[$key] = [
                        'status'       => $status,
                        'country_code' => $code,
                        'count'        => 0,
                        'computed_at'  => $now,
                    ];
                }
            }
        }
        $rows = array_values($rows);

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

        // $byCountry was already computed once at the top of handle() via the
        // per-country loop — no need to run the same aggregation a second time.
        Cache::forever('stats.georef.data', [$global, $byCountry]);
        Cache::forever('stats.georef.computed_at', now()->timestamp);
        $this->info('Refreshed stats.georef');

        return self::SUCCESS;
    }
}
