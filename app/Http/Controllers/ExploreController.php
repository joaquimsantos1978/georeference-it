<?php

namespace App\Http\Controllers;

use App\Models\LocalityGroup;
use App\Support\CountsWithStaleWhileRevalidate;
use Illuminate\Http\Request;

class ExploreController extends Controller
{
    use CountsWithStaleWhileRevalidate;

    public function index(Request $request)
    {
        // Block bot crawlers hitting absurd page numbers (causes full-table OFFSET scan)
        if ((int) $request->get('page', 1) > 5000) {
            abort(404);
        }

        // NOTE: a filter for blank-locality rows (verbatim_locality/municipality/county/
        // state_province all empty) was tried here and reverted — the OR across four
        // unindexed columns forced a slow scan over tens of millions of rows, which piled
        // up alongside the concurrent GBIF import and starved it of I/O. Revisit with a
        // proper index (or a generated/stored column) once the import isn't running.
        $query = LocalityGroup::query()->where('occurrence_count', '>', 0);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->whereRaw(
                'MATCH(locality_string) AGAINST(? IN BOOLEAN MODE)',
                [$q]
            );
        }

        if ($request->filled('country')) {
            $query->where('country_code', strtoupper($request->country));
        }

        if ($request->filled('dataset_key')) {
            $query->joinSub(
                \Illuminate\Support\Facades\DB::table('occurrences')
                    ->select('locality_group_id')
                    ->where('dataset_key', $request->dataset_key)
                    ->whereNotNull('locality_group_id')
                    ->whereNull('deleted_at')
                    ->distinct(),
                'ds_occ',
                'locality_groups.id', '=', 'ds_occ.locality_group_id'
            );
        }

        if ($request->filled('status')) {
            match ($request->status) {
                'ungeoreferenced' => $query->where('ungeoreferenced_count', '>', 0),
                'has_suggestion'  => $query->where('pending_count', '>', 0),
                'validated'       => $query->where('validated_count', '>', 0),
                'georeferenced'   => $query->whereRaw('occurrence_count > ungeoreferenced_count'),
                'inconsistent'    => $query->where('consistency_status', 'inconsistent'),
                default           => null,
            };
        }

        // "By me" — scoped to the current user's own georef actions (activity_log.type =
        // 'georef'), bounded by activity_log_user_id_created_at_index regardless of table
        // size, since it's always just one user's own history, never the full table.
        $mine = $request->boolean('mine') && auth()->check();
        if ($mine) {
            $query->whereIn('id', \Illuminate\Support\Facades\DB::table('activity_log')
                ->where('user_id', auth()->id())
                ->where('type', 'georef')
                ->select('locality_group_id')
                ->distinct()
            );
        }

        $perPage    = 50;
        $page       = $request->integer('page', 1) ?: 1;
        $sort       = $request->get('sort');
        $cacheKeyParams = $request->only(['q', 'country', 'dataset_key', 'status']);
        if ($mine) {
            $cacheKeyParams['mine'] = auth()->id(); // per-user, not shared across the general cache
        }
        $cacheKey = 'explore_count_' . md5(json_encode($cacheKeyParams));
        $total    = $this->countWithStaleWhileRevalidate($cacheKey, fn() => (clone $query)->count());

        // 'recent' wins over relevance when both a search term and the sort are given —
        // an explicit sort choice is a stronger signal than the default post-search order.
        if ($sort === 'recent') {
            $rows = $query->orderByDesc('updated_at')->forPage($page, $perPage)->get();
        } elseif ($request->filled('q')) {
            $q    = $request->q;
            $rows = $query
                ->orderByRaw(
                    'MATCH(locality_string) AGAINST(? IN BOOLEAN MODE) DESC',
                    [$q]
                )
                ->forPage($page, $perPage)
                ->get();
        } else {
            $rows = $query
                ->orderByDesc('occurrence_count')
                ->forPage($page, $perPage)
                ->get();
        }

        $groups = new \Illuminate\Pagination\LengthAwarePaginator(
            $rows, $total, $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $countries = LocalityGroup::activeCountryCodes();

        $currentDataset = null;
        if ($request->filled('dataset_key')) {
            $currentDataset = \Illuminate\Support\Facades\DB::table('datasets')
                ->where('key', $request->dataset_key)
                ->first(['title', 'institution_code', 'collection_code']);
        }

        return view('explore', compact('groups', 'countries', 'currentDataset'));
    }

}
