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
        $totalCount = $this->staleWhileRevalidate($cacheKey, 3600, function () use ($validStatuses, $status, $country) {
            return DB::table('occurrences')
                ->whereNull('deleted_at')
                ->whereIn('georef_status', $validStatuses)
                ->when($status && in_array($status, $validStatuses), fn($q) => $q->where('georef_status', $status))
                ->when($country, fn($q) => $q->where('country_code', $country))
                ->count();
        });

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

        $countries = $this->staleWhileRevalidate('explore_countries', 86400, function () {
            return DB::table('locality_groups')
                ->select('country_code')
                ->whereNull('deleted_at')
                ->whereNotNull('country_code')
                ->where('occurrence_count', '>', 0)
                ->whereRaw("country_code REGEXP '^[A-Z]{2}$'")
                ->distinct()
                ->orderBy('country_code')
                ->pluck('country_code');
        }, collect());

        return view('impact', compact('occurrences', 'totalCount', 'status', 'country', 'countries', 'threshold'));
    }

    // Same stale-while-revalidate pattern as StatsController: counting over ~230M
    // occurrences is slow enough that a plain Cache::remember() TTL expiry let every
    // concurrent request rerun the count in parallel — a stampede that caused 504s on
    // this page. Data is kept forever and only refreshed by whichever single request
    // wins the lock; everyone else (including that request, immediately) gets the last
    // known count instead of waiting on or duplicating the query.
    private function staleWhileRevalidate(string $key, int $maxAgeSeconds, \Closure $compute, $emptyFallback = 0)
    {
        $dataKey = $key . ':data';
        $atKey   = $key . ':computed_at';
        $cached  = \Illuminate\Support\Facades\Cache::get($dataKey);

        $isStale = $cached === null
            || \Illuminate\Support\Facades\Cache::get($atKey, 0) < now()->subSeconds($maxAgeSeconds)->timestamp;

        if ($isStale) {
            $lock = \Illuminate\Support\Facades\Cache::lock($key . ':lock', 300);
            if ($lock->get()) {
                try {
                    $cached = $compute();
                    \Illuminate\Support\Facades\Cache::forever($dataKey, $cached);
                    \Illuminate\Support\Facades\Cache::forever($atKey, now()->timestamp);
                } finally {
                    $lock->release();
                }
            } elseif ($cached === null) {
                // Someone else is already computing and we have nothing to serve yet
                // (a cold cache right after deploy, hit by several requests at once).
                // Wait briefly — well under a gateway timeout — instead of also
                // running the same slow query ourselves.
                try {
                    $lock->block(10);
                    $lock->release();
                } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
                    // Still running after 10s — fall through to the safe default below.
                }
                $cached = \Illuminate\Support\Facades\Cache::get($dataKey);
            }
        }

        return $cached ?? $emptyFallback;
    }
}
