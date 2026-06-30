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

        $perPage = 50;
        $page    = $request->integer('page', 1) ?: 1;

        $cacheKey = 'impact_count_' . ($status ?: 'all') . '_' . ($country ?: 'all');
        $totalCount = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($validStatuses, $status, $country) {
            return DB::table('occurrences')
                ->whereIn('georef_status', $validStatuses)
                ->when($status && in_array($status, $validStatuses), fn($q) => $q->where('georef_status', $status))
                ->when($country, fn($q) => $q->where('country_code', $country))
                ->count();
        });

        // Deferred join: fetch IDs first (index-only), then fetch full rows
        $statusFilter  = $status && in_array($status, $validStatuses) ? [$status] : $validStatuses;
        $offset        = ($page - 1) * $perPage;

        $ids = DB::table('occurrences')
            ->select('id')
            ->whereIn('georef_status', $statusFilter)
            ->when($country, fn($q) => $q->where('country_code', $country))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($perPage)
            ->pluck('id');

        $rows = DB::table('occurrences as o')
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
            ->whereIn('o.id', $ids)
            ->orderByDesc('o.updated_at')
            ->orderByDesc('o.id')
            ->get();

        $occurrences = new \Illuminate\Pagination\LengthAwarePaginator(
            $rows, $totalCount, $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('impact', compact('occurrences', 'totalCount', 'status', 'country'));
    }
}
