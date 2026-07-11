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
        $countries = DB::table('locality_groups')
            ->select('country_code')
            ->whereNull('deleted_at')
            ->whereNotNull('country_code')
            ->where('occurrence_count', '>', 0)
            ->whereRaw("country_code REGEXP '^[A-Z]{2}$'")
            ->distinct()
            ->orderBy('country_code')
            ->pluck('country_code');

        Cache::forever('explore_countries:data', $countries);
        Cache::forever('explore_countries:computed_at', now()->timestamp);
        $this->info('Refreshed explore_countries (' . $countries->count() . ' countries)');

        $countryOptions = $countries->prepend(null); // null = "all countries"
        $statusOptions  = collect(self::VALID_STATUSES)->prepend(null); // null = "all statuses"

        foreach ($statusOptions as $status) {
            foreach ($countryOptions as $country) {
                $count = DB::table('occurrences')
                    ->whereNull('deleted_at')
                    ->whereIn('georef_status', self::VALID_STATUSES)
                    ->when($status, fn($q) => $q->where('georef_status', $status))
                    ->when($country, fn($q) => $q->where('country_code', $country))
                    ->count();

                $key = 'impact_count_' . ($status ?: 'all') . '_' . ($country ?: 'all');
                Cache::forever($key . ':data', $count);
                Cache::forever($key . ':computed_at', now()->timestamp);
            }
        }

        $this->info('Refreshed impact_count_* for ' . $statusOptions->count() . ' statuses x ' . $countryOptions->count() . ' countries');

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
            ->whereRaw("country_code REGEXP '^[A-Z]{2}$'")
            ->groupBy('country_code')
            ->orderByDesc('ungeoref_occ')
            ->get();

        Cache::forever('stats.georef.data', [$global, $byCountry]);
        Cache::forever('stats.georef.computed_at', now()->timestamp);
        $this->info('Refreshed stats.georef');

        return self::SUCCESS;
    }
}
