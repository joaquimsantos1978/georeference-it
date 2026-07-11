<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;

class StatsController extends Controller
{
    // Pure cache read — computed hourly in the background by RefreshImpactCounts, never on
    // a page request. A lock-protected inline-compute fallback used to live here, but it
    // still let whichever request won the lock pay the multi-minute cost itself, and
    // production repeatedly showed several copies of the identical expensive query running
    // at once despite the lock — same class of failure as Impact/Explore before those were
    // moved to background-only computation (see ImpactController).
    public function index()
    {
        $global = Cache::get('stats.georef.data')[0] ?? (object) [
            'total_occ' => 0, 'ungeoref_occ' => 0, 'pending_occ' => 0,
            'gbif_occ' => 0, 'validated_occ' => 0, 'gbif_reviewed_occ' => 0,
            'pending_groups' => 0,
        ];
        $byCountry = Cache::get('stats.georef.data')[1] ?? collect();

        return view('stats', compact('global', 'byCountry'));
    }
}
