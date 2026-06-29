<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ImpactController extends Controller
{
    public function index(Request $request)
    {
        $status  = $request->get('status');
        $country = strtoupper(trim($request->get('country', ''))) ?: null;

        $validStatuses = ['has_suggestion', 'conflicted', 'validated', 'gbif_reviewed'];

        // Best suggestion coords per group (accepted > pending), computed once
        $bestCoords = DB::raw('(
            SELECT locality_group_id, decimal_latitude, decimal_longitude
            FROM (
                SELECT locality_group_id, decimal_latitude, decimal_longitude,
                    ROW_NUMBER() OVER (
                        PARTITION BY locality_group_id
                        ORDER BY FIELD(status, "accepted", "pending")
                    ) as rn
                FROM georef_suggestions
                WHERE status IN ("accepted", "pending")
                  AND decimal_latitude IS NOT NULL
            ) ranked
            WHERE rn = 1
        ) gs');

        // Last activity per group, computed once
        $lastActivity = DB::raw('(
            SELECT locality_group_id, MAX(created_at) as last_activity
            FROM activity_log
            GROUP BY locality_group_id
        ) al');

        $query = DB::table('occurrences as o')
            ->select(
                'o.id', 'o.gbif_occurrence_key', 'o.scientific_name',
                'o.georef_status', 'o.country_code', 'o.locality_group_id',
                'o.verbatim_locality', 'o.municipality', 'o.county',
                'gs.decimal_latitude', 'gs.decimal_longitude',
                'al.last_activity'
            )
            ->join($lastActivity, 'al.locality_group_id', '=', 'o.locality_group_id')
            ->leftJoin($bestCoords, 'gs.locality_group_id', '=', 'o.locality_group_id')
            ->whereIn('o.georef_status', $validStatuses)
            ->when($status && in_array($status, $validStatuses), fn($q) => $q->where('o.georef_status', $status))
            ->when($country, fn($q) => $q->where('o.country_code', $country))
            ->orderByDesc('al.last_activity');

        $occurrences = $query->simplePaginate(50)->withQueryString();

        $totalCount = DB::table('occurrences')
            ->whereIn('georef_status', $validStatuses)
            ->when($status && in_array($status, $validStatuses), fn($q) => $q->where('georef_status', $status))
            ->when($country, fn($q) => $q->where('country_code', $country))
            ->count();

        return view('impact', compact('occurrences', 'totalCount', 'status', 'country'));
    }
}
