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

        $query = DB::table('occurrences as o')
            ->select(
                'o.id', 'o.gbif_occurrence_key', 'o.scientific_name',
                'o.locality_group_id', 'o.georef_status', 'o.country_code',
                'o.decimal_latitude', 'o.decimal_longitude',
                'lg.verbatim_locality', 'lg.municipality', 'lg.county',
                DB::raw('MAX(al.created_at) as last_activity')
            )
            ->join('locality_groups as lg', 'lg.id', '=', 'o.locality_group_id')
            ->leftJoin('activity_log as al', 'al.locality_group_id', '=', 'o.locality_group_id')
            ->whereIn('o.georef_status', $validStatuses)
            ->when($status && in_array($status, $validStatuses), fn($q) => $q->where('o.georef_status', $status))
            ->when($country, fn($q) => $q->where('o.country_code', $country))
            ->groupBy(
                'o.id', 'o.gbif_occurrence_key', 'o.scientific_name',
                'o.locality_group_id', 'o.georef_status', 'o.country_code',
                'o.decimal_latitude', 'o.decimal_longitude',
                'lg.verbatim_locality', 'lg.municipality', 'lg.county'
            )
            ->orderByDesc('last_activity');

        $occurrences = $query->simplePaginate(50)->withQueryString();

        $totalCount = DB::table('occurrences')
            ->whereIn('georef_status', $validStatuses)
            ->when($status && in_array($status, $validStatuses), fn($q) => $q->where('georef_status', $status))
            ->when($country, fn($q) => $q->where('country_code', $country))
            ->count();

        return view('impact', compact('occurrences', 'totalCount', 'status', 'country', 'validStatuses'));
    }
}
