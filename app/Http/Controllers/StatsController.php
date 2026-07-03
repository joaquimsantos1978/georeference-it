<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function index()
    {
        [$global, $byCountry] = $this->getWithStaleWhileRevalidate();
        return view('stats', compact('global', 'byCountry'));
    }

    // Recomputing takes minutes (full scan over ~230M occurrences). A plain Cache::remember()
    // meant every request that arrived after the weekly TTL expired reran that scan in
    // parallel — a stampede that once stacked up 3 concurrent 10+ minute queries and took
    // the whole site down with it (504s on Stats/Impact). Data never actually expires now;
    // only ONE request (whichever wins the lock) recomputes in the background while every
    // other request — including that same one, immediately — gets the last known value.
    private function getWithStaleWhileRevalidate(): array
    {
        $cached = Cache::get('stats.georef.data');

        $isStale = $cached === null
            || Cache::get('stats.georef.computed_at', 0) < now()->subWeek()->timestamp;

        if ($isStale) {
            $lock = Cache::lock('stats.georef.lock', 900);
            if ($lock->get()) {
                try {
                    $cached = $this->compute();
                    Cache::forever('stats.georef.data', $cached);
                    Cache::forever('stats.georef.computed_at', now()->timestamp);
                } finally {
                    $lock->release();
                }
            } elseif ($cached === null) {
                // Someone else is already computing and we have nothing to serve yet
                // (a cold cache right after deploy, hit by several requests at once).
                // Wait briefly — well under a gateway timeout — for them to finish
                // instead of also running the multi-minute scan ourselves.
                try {
                    $lock->block(10);
                    $lock->release();
                } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
                    // Still running after 10s — fall through to the empty-safe default below.
                }
                $cached = Cache::get('stats.georef.data');
            }
        }

        return $cached ?? [
            (object) [
                'total_occ' => 0, 'ungeoref_occ' => 0, 'pending_occ' => 0,
                'gbif_occ' => 0, 'validated_occ' => 0, 'gbif_reviewed_occ' => 0,
                'pending_groups' => 0,
            ],
            collect(),
        ];
    }

    public function compute(): array
    {
        // Global totals direct from occurrences (locality_groups counters don't distinguish gbif_georeferenced)
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

        // pending_groups still from locality_groups (faster)
        $pendingGroups = DB::table('locality_groups')
            ->whereNull('deleted_at')
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

        return [$global, $byCountry];
    }
}
