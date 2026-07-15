<?php

namespace App\Http\Controllers;

use App\Models\LocalityGroup;
use Illuminate\Http\Request;

class ExploreController extends Controller
{
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

        $perPage  = 50;
        $page     = $request->integer('page', 1) ?: 1;
        $cacheKey = 'explore_count_' . md5(json_encode($request->only(['q', 'country', 'dataset_key', 'status'])));
        $total    = $this->countWithStaleWhileRevalidate($cacheKey, fn() => (clone $query)->count());

        if ($request->filled('q')) {
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

        // Live query, not cached/precomputed — a plain filter-free DISTINCT on
        // country_code uses a loose index scan (EXPLAIN: "Using index for group-by",
        // ~46K rows) regardless of how large locality_groups gets, so there's nothing
        // here worth precomputing in the background. The validity filter (exactly two
        // uppercase letters) runs in PHP against the small distinct-values result, not
        // as a SQL REGEXP — pre-validation-era data left plenty of garbage values
        // (tabs, digits, whole province names in Chinese) still sitting in the column,
        // and REGEXP-in-SQL against millions of rows is exactly the full-scan cost this
        // query exists to avoid.
        $countries = \Illuminate\Support\Facades\DB::table('locality_groups')
            ->whereNotNull('country_code')
            ->where('country_code', '!=', '')
            ->distinct()
            ->pluck('country_code')
            ->filter(fn($code) => preg_match('/^[A-Z]{2}$/', $code))
            ->sort()
            ->values();

        $currentDataset = null;
        if ($request->filled('dataset_key')) {
            $currentDataset = \Illuminate\Support\Facades\DB::table('datasets')
                ->where('key', $request->dataset_key)
                ->first(['title', 'institution_code', 'collection_code']);
        }

        return view('explore', compact('groups', 'countries', 'currentDataset'));
    }

    // COUNT(*) with a locality_groups filter was observed taking minutes on this table
    // (tens of millions of rows, no index covers the combination of filters well). A
    // plain Cache::remember() with a 1h TTL meant the request that landed right after
    // expiry paid that cost inline and 504'd — this used to still have that failure
    // mode for the *first* request after staleness (the lock winner computed inline).
    // Now that request only ever recomputes live if NO value has ever been cached for
    // this exact filter combination (a one-time cost per combination, ever). Once
    // something is cached, every later request — including the one that notices it's
    // stale — returns it immediately and kicks the recompute off via afterResponse(),
    // which runs after the response is already on its way to the browser (uses
    // fastcgi_finish_request() under PHP-FPM) — no queue worker needed, and no request
    // is ever the one left waiting on a multi-minute COUNT(*).
    private function countWithStaleWhileRevalidate(string $cacheKey, \Closure $compute): int
    {
        $dataKey = $cacheKey . ':data';
        $cached  = \Illuminate\Support\Facades\Cache::get($dataKey);

        if ($cached === null) {
            $lock = \Illuminate\Support\Facades\Cache::lock($cacheKey . ':lock', 300);
            if ($lock->get()) {
                try {
                    $cached = $compute();
                    \Illuminate\Support\Facades\Cache::forever($dataKey, $cached);
                    \Illuminate\Support\Facades\Cache::forever($cacheKey . ':computed_at', now()->timestamp);
                } finally {
                    $lock->release();
                }
            } else {
                try {
                    $lock->block(10);
                    $lock->release();
                } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
                    // Still running after 10s — fall through to the safe default below.
                }
                $cached = \Illuminate\Support\Facades\Cache::get($dataKey);
            }

            return $cached ?? 0;
        }

        $isStale = \Illuminate\Support\Facades\Cache::get($cacheKey . ':computed_at', 0) < now()->subHour()->timestamp;
        if ($isStale) {
            $lock = \Illuminate\Support\Facades\Cache::lock($cacheKey . ':lock', 300);
            if ($lock->get()) {
                dispatch(function () use ($compute, $dataKey, $cacheKey, $lock) {
                    try {
                        \Illuminate\Support\Facades\Cache::forever($dataKey, $compute());
                        \Illuminate\Support\Facades\Cache::forever($cacheKey . ':computed_at', now()->timestamp);
                    } finally {
                        $lock->release();
                    }
                })->afterResponse();
            }
            // Lock already held (another request's afterResponse() job is mid-flight,
            // or hasn't run yet) — nothing to do here either way, this request still
            // just returns the stale value below like normal.
        }

        return $cached;
    }
}
