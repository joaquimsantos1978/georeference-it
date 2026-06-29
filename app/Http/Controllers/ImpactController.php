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

        $beforeTs = $request->get('before') ?: null;
        $beforeId = $request->integer('before_id') ?: null;

        $occurrences = DB::table('occurrences as o')
            ->select(
                'o.id', 'o.gbif_occurrence_key', 'o.scientific_name',
                'o.georef_status', 'o.country_code', 'o.locality_group_id',
                'o.verbatim_locality', 'o.municipality', 'o.county',
                'o.state_province', 'o.recorded_by', 'o.event_date',
                'o.institution_code', 'o.collection_code', 'o.catalog_number',
                'o.gbif_decimal_latitude', 'o.gbif_decimal_longitude',
                'o.updated_at'
            )
            ->whereIn('o.georef_status', $validStatuses)
            ->when($status && in_array($status, $validStatuses), fn($q) => $q->where('o.georef_status', $status))
            ->when($country, fn($q) => $q->where('o.country_code', $country))
            ->when($beforeTs && $beforeId, fn($q) => $q->where(
                fn($q2) => $q2->where('o.updated_at', '<', $beforeTs)
                    ->orWhere(fn($q3) => $q3->where('o.updated_at', '=', $beforeTs)->where('o.id', '<', $beforeId))
            ))
            ->orderByDesc('o.updated_at')
            ->orderByDesc('o.id')
            ->limit(51)
            ->get();

        $hasMore     = $occurrences->count() > 50;
        $occurrences = $occurrences->take(50);
        $nextTs      = $hasMore ? $occurrences->last()->updated_at : null;
        $nextId      = $hasMore ? $occurrences->last()->id : null;

        $totalCount = DB::table('occurrences')
            ->whereIn('georef_status', $validStatuses)
            ->when($status && in_array($status, $validStatuses), fn($q) => $q->where('georef_status', $status))
            ->when($country, fn($q) => $q->where('country_code', $country))
            ->count();

        return view('impact', compact('occurrences', 'totalCount', 'status', 'country', 'nextTs', 'nextId'));
    }
}
