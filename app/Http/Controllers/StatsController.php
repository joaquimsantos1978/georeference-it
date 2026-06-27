<?php

namespace App\Http\Controllers;

use App\Models\LocalityGroup;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function index()
    {
        // Global totals
        $global = DB::table('locality_groups')
            ->selectRaw('
                SUM(occurrence_count)      AS total_occ,
                SUM(ungeoreferenced_count) AS ungeoref_occ,
                SUM(pending_count)         AS pending_occ,
                SUM(validated_count)       AS validated_occ,
                COUNT(*)                   AS total_groups,
                SUM(ungeoreferenced_count > 0 OR pending_count > 0) AS pending_groups
            ')
            ->first();

        // Per-country breakdown (only countries with occurrences)
        $byCountry = DB::table('locality_groups')
            ->selectRaw('
                country_code,
                SUM(occurrence_count)      AS total_occ,
                SUM(ungeoreferenced_count) AS ungeoref_occ,
                SUM(pending_count)         AS pending_occ,
                SUM(validated_count)       AS validated_occ,
                COUNT(*)                   AS total_groups,
                SUM(ungeoreferenced_count > 0 OR pending_count > 0) AS pending_groups
            ')
            ->where('occurrence_count', '>', 0)
            ->groupBy('country_code')
            ->orderByDesc('ungeoref_occ')
            ->get();

        return view('stats', compact('global', 'byCountry'));
    }
}
