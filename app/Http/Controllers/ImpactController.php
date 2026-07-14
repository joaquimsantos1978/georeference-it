<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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

        // Pure lookup on a pre-computed table — never counted here. RefreshImpactCounts
        // (scheduled hourly) is the only thing that ever runs this count query, so a page
        // request can never be the one that gets stuck behind it. A previous version ran
        // the count inline whenever the cache was stale, which still caused occasional
        // 504s: whichever request happened to hit the stale window paid for the live
        // query itself.
        $totalCount = DB::table('impact_counts')
            ->where('status', $status ?: 'all')
            ->where('country_code', $country ?: 'all')
            ->value('count') ?? 0;

        $offset       = ($page - 1) * $perPage;
        $statusFilter = $status && in_array($status, $validStatuses) ? [$status] : $validStatuses;
        $countryWhere = $country ? "AND country_code = " . DB::connection()->getPdo()->quote($country) : '';
        $limit        = $perPage + $offset;

        // UNION per status avoids filesort over 7M rows (each branch uses the index directly)
        $pdo      = DB::connection()->getPdo();
        $branches = array_map(fn($s) =>
            "(SELECT id, updated_at FROM occurrences WHERE georef_status = " . $pdo->quote($s) . " AND deleted_at IS NULL $countryWhere ORDER BY updated_at DESC, id DESC LIMIT $limit)",
            $statusFilter
        );
        $unionSql = implode(" UNION ALL ", $branches) . " ORDER BY updated_at DESC, id DESC LIMIT $perPage OFFSET $offset";

        $ids = collect(DB::select($unionSql))->pluck('id');

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

        // Attach the validated suggestion's vote total, but only when a group's
        // validation is unambiguous (one validated suggestion) — inconsistent groups can
        // have several winning clusters, and picking the "right" one needs the same
        // exclusion-aware clustering used on the georef page, which isn't worth
        // replicating here just to draw a progress bar.
        $groupIds = $rows->pluck('locality_group_id')->filter()->unique();
        $suggestionsByGroup = \App\Models\GeorefSuggestion::whereIn('locality_group_id', $groupIds)
            ->where('status', 'validated')
            ->get(['id', 'locality_group_id', 'total_points'])
            ->groupBy('locality_group_id');

        foreach ($rows as $row) {
            $group = $suggestionsByGroup->get($row->locality_group_id);
            if ($group && $group->count() === 1) {
                $row->suggestion_id = $group->first()->id;
                $row->total_points  = $group->first()->total_points;
            } else {
                $row->suggestion_id = null;
                $row->total_points  = null;
            }
        }

        $threshold = (int) \App\Models\PlatformSetting::get('validation_threshold', 60);

        $occurrences = new \Illuminate\Pagination\LengthAwarePaginator(
            $rows, $totalCount, $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Live query, not cached/precomputed — see ExploreController for why this is
        // cheap regardless of table size (loose index scan on country_code).
        $countries = DB::table('locality_groups')
            ->whereNotNull('country_code')
            ->where('country_code', '!=', '')
            ->distinct()
            ->orderBy('country_code')
            ->pluck('country_code');

        return view('impact', compact('occurrences', 'totalCount', 'status', 'country', 'countries', 'threshold'));
    }
}
