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

        $validStatuses = ['has_suggestion', 'validated', 'gbif_reviewed'];

        $occurrences = DB::table('occurrences as o')
            ->select(
                'o.id', 'o.gbif_occurrence_key', 'o.scientific_name',
                'o.georef_status', 'o.country_code', 'o.locality_group_id',
                'o.verbatim_locality', 'o.municipality', 'o.county',
                'o.state_province', 'o.continent', 'o.water_body',
                'o.higher_geography', 'o.island', 'o.island_group',
                'o.location_remarks',
                'o.recorded_by', 'o.event_date',
                'o.institution_code', 'o.collection_code', 'o.catalog_number',
                'o.country',
                'o.gbif_decimal_latitude', 'o.gbif_decimal_longitude',
                'o.updated_at'
            )
            ->whereIn('o.georef_status', $validStatuses)
            ->when($status && in_array($status, $validStatuses), fn($q) => $q->where('o.georef_status', $status))
            ->when($country, fn($q) => $q->where('o.country_code', $country))
            ->orderByDesc('o.updated_at')
            ->orderByDesc('o.id')
            ->paginate(50)
            ->withQueryString();

        $cacheKey = 'impact_count_' . ($status ?: 'all') . '_' . ($country ?: 'all');
        $totalCount = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($validStatuses, $status, $country) {
            return DB::table('occurrences')
                ->whereIn('georef_status', $validStatuses)
                ->when($status && in_array($status, $validStatuses), fn($q) => $q->where('georef_status', $status))
                ->when($country, fn($q) => $q->where('country_code', $country))
                ->count();
        });

        return view('impact', compact('occurrences', 'totalCount', 'status', 'country'));
    }
}
