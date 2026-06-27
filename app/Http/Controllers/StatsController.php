<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function index()
    {
        [$global, $byCountry] = Cache::remember('stats.georef', now()->addWeek(), fn() => $this->compute());
        return view('stats', compact('global', 'byCountry'));
    }

    public function compute(): array
    {
        // Global totals direct from occurrences (locality_groups counters don't distinguish gbif_georeferenced)
        $global = DB::table('occurrences')
            ->selectRaw("
                COUNT(*)                                             AS total_occ,
                SUM(georef_status = 'ungeoreferenced')               AS ungeoref_occ,
                SUM(georef_status = 'has_suggestion')                AS pending_occ,
                SUM(georef_status = 'gbif_georeferenced')            AS gbif_occ,
                SUM(georef_status = 'validated')                     AS validated_occ,
                SUM(georef_status = 'gbif_reviewed')                 AS gbif_reviewed_occ
            ")
            ->first();

        // pending_groups still from locality_groups (faster)
        $pendingGroups = DB::table('locality_groups')
            ->whereRaw('ungeoreferenced_count > 0 OR pending_count > 0')
            ->count();
        $global->pending_groups = $pendingGroups;

        // Per-country breakdown from locality_groups (occurrences table join would be too slow)
        $byCountry = DB::table('locality_groups')
            ->selectRaw('
                country_code,
                SUM(occurrence_count)                                         AS total_occ,
                SUM(ungeoreferenced_count)                                    AS ungeoref_occ,
                SUM(pending_count)                                            AS pending_occ,
                SUM(validated_count)                                          AS validated_occ,
                SUM(occurrence_count - ungeoreferenced_count - pending_count) AS georef_occ,
                COUNT(*)                                                      AS total_groups,
                SUM(ungeoreferenced_count > 0 OR pending_count > 0)          AS pending_groups
            ')
            ->where('occurrence_count', '>', 0)
            ->groupBy('country_code')
            ->orderByDesc('ungeoref_occ')
            ->get();

        return [$global, $byCountry];
    }
}
