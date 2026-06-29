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

        // Best suggestion coords per group (accepted beats pending), using window function
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

        // Last activity per group
        $lastActivity = DB::raw('(
            SELECT locality_group_id, MAX(created_at) as last_activity
            FROM activity_log
            GROUP BY locality_group_id
        ) al');

        // Representative georef_status per group: pick the most "advanced" status
        $statusRank = 'MAX(CASE o.georef_status
            WHEN "validated"      THEN 4
            WHEN "gbif_reviewed"  THEN 3
            WHEN "conflicted"     THEN 2
            WHEN "has_suggestion" THEN 1
            ELSE 0 END)';

        $statusFromRank = 'CASE ' . $statusRank . '
            WHEN 4 THEN "validated"
            WHEN 3 THEN "gbif_reviewed"
            WHEN 2 THEN "conflicted"
            WHEN 1 THEN "has_suggestion"
            ELSE NULL END';

        $query = DB::table('locality_groups as lg')
            ->select(
                'lg.id as locality_group_id',
                'lg.verbatim_locality', 'lg.municipality', 'lg.county', 'lg.country_code',
                'lg.occurrence_count',
                DB::raw("({$statusFromRank}) as georef_status"),
                'gs.decimal_latitude', 'gs.decimal_longitude',
                'al.last_activity'
            )
            ->join('occurrences as o', 'o.locality_group_id', '=', 'lg.id')
            ->join($lastActivity, 'al.locality_group_id', '=', 'lg.id')
            ->leftJoin($bestCoords, 'gs.locality_group_id', '=', 'lg.id')
            ->whereIn('o.georef_status', $validStatuses)
            ->when($country, fn($q) => $q->where('lg.country_code', $country))
            ->groupBy(
                'lg.id', 'lg.verbatim_locality', 'lg.municipality', 'lg.county',
                'lg.country_code', 'lg.occurrence_count',
                'gs.decimal_latitude', 'gs.decimal_longitude', 'al.last_activity'
            )
            ->when($status && in_array($status, $validStatuses),
                fn($q) => $q->havingRaw("({$statusFromRank}) = ?", [$status])
            )
            ->orderByDesc('al.last_activity');

        $groups = $query->simplePaginate(50)->withQueryString();

        $totalSpecimens = DB::table('occurrences')
            ->whereIn('georef_status', $validStatuses)
            ->when($country, fn($q) => $q->where('country_code', $country))
            ->count();

        $totalGroups = DB::table('locality_groups as lg')
            ->join('occurrences as o', 'o.locality_group_id', '=', 'lg.id')
            ->join($lastActivity, 'al.locality_group_id', '=', 'lg.id')
            ->whereIn('o.georef_status', $validStatuses)
            ->when($country, fn($q) => $q->where('lg.country_code', $country))
            ->distinct('lg.id')
            ->count('lg.id');

        return view('impact', compact('groups', 'totalSpecimens', 'totalGroups', 'status', 'country'));
    }
}
