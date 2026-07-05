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

        return self::SUCCESS;
    }
}
