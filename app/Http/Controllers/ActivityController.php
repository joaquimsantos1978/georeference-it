<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $rawUser       = $request->get('user');
        $isSystem      = $rawUser === 'system';
        $filterUserId  = (!$isSystem && $rawUser) ? (int) $rawUser : null;
        $filterCountry = strtoupper(trim($request->get('country', ''))) ?: null;
        $authId        = (int) auth()->id();

        // Resolve filtered user — any registered user can be filtered by ID,
        // but $filterUser (used for the header label) is only set when public or self.
        $filterUser = null;
        if ($filterUserId) {
            $u = User::find($filterUserId);
            if (!$u) {
                $filterUserId = null; // non-existent user
            } elseif ($u->public_name || $authId === $u->id) {
                $filterUser = $u; // show name in header
            }
            // hidden users: filterUserId stays, filterUser stays null → shows "Hidden contributor"
        }

        $perPage = 40;
        $page    = $request->integer('page', 1) ?: 1;

        if ($isSystem) {
            // System-generated suggestions aren't logged to activity_log (too high-volume to log
            // individually) — read them directly from georef_suggestions instead.
            $query = DB::table('georef_suggestions as gs')
                ->join('locality_groups as lg', 'lg.id', '=', 'gs.locality_group_id')
                ->whereNull('gs.user_id')
                ->whereIn('gs.georeference_sources', ['GBIF', 'GBIF_CONSISTENCY_CHECK'])
                ->when($filterCountry, fn($q) => $q->where('lg.country_code', $filterCountry));

            $cacheKey = 'activity_count_system_' . ($filterCountry ?: 'all');
            $total = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, fn() => (clone $query)->count());

            $rows = $query
                ->select(
                    'gs.id', DB::raw("'georef' as type"), DB::raw("'system' as source"),
                    'gs.locality_group_id',
                    DB::raw('1 as occ_count'),
                    'gs.decimal_latitude as lat', 'gs.decimal_longitude as lng',
                    'gs.coordinate_uncertainty_m as uncertainty_m',
                    'gs.georeference_remarks as remarks',
                    'lg.country_code',
                    'lg.locality_string as location_label',
                    'gs.created_at',
                    DB::raw('NULL as user_id'),
                    DB::raw('NULL as user_name'), DB::raw('NULL as public_user_id'), DB::raw('NULL as user_avatar'),
                    DB::raw('NULL as suggestion_user_id'), DB::raw('NULL as suggestion_source'),
                    DB::raw('NULL as suggestion_author_name'), DB::raw('NULL as suggestion_author_id')
                )
                ->orderByDesc('gs.created_at')
                ->forPage($page, $perPage)
                ->get();
        } else {
            $query = DB::table('activity_log as al')
                ->leftJoin('users as u', 'u.id', '=', 'al.user_id')
                ->leftJoin('users as su', 'su.id', '=', 'al.suggestion_user_id')
                ->when($filterUserId, fn($q) => $q->where('al.user_id', $filterUserId))
                ->when($filterCountry, fn($q) => $q->where('al.country_code', $filterCountry));

            $cacheKey = 'activity_count_' . ($filterUserId ?: 'all') . '_' . ($filterCountry ?: 'all');
            $total = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, fn() => (clone $query)->count());

            $rows = $query
                ->select(
                    'al.id', 'al.type', 'al.source', 'al.locality_group_id', 'al.occ_count',
                    'al.lat', 'al.lng', 'al.uncertainty_m', 'al.remarks',
                    'al.country_code', 'al.location_label', 'al.created_at', 'al.user_id',
                    DB::raw("IF(u.public_name = 1 OR u.id = {$authId}, u.name, NULL) as user_name"),
                    DB::raw("IF(u.public_name = 1 OR u.id = {$authId}, u.id, NULL) as public_user_id"),
                    DB::raw("IF(u.public_name = 1 OR u.id = {$authId}, u.avatar, NULL) as user_avatar"),
                    'al.suggestion_user_id',
                    'al.suggestion_source',
                    DB::raw("IF(su.public_name = 1, su.name, NULL) as suggestion_author_name"),
                    DB::raw("IF(su.public_name = 1, su.id, NULL) as suggestion_author_id")
                )
                ->orderByDesc('al.created_at')
                ->forPage($page, $perPage)
                ->get();
        }

        $activities = new \Illuminate\Pagination\LengthAwarePaginator(
            $rows, $total, $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Users for filter dropdown — all users with activity
        $dropdownUsers = DB::table('activity_log as al')
            ->join('users as u', 'u.id', '=', 'al.user_id')
            ->select('u.id', 'u.name', 'u.public_name', DB::raw('COUNT(*) as n'))
            ->whereNotNull('al.user_id')
            ->groupBy('u.id', 'u.name', 'u.public_name')
            ->orderByDesc('n')
            ->limit(50)
            ->get();

        $countries = \Illuminate\Support\Facades\Cache::remember('explore_countries', 86400, function () {
            return DB::table('locality_groups')
                ->select('country_code')
                ->whereNotNull('country_code')
                ->where('occurrence_count', '>', 0)
                ->whereRaw("country_code REGEXP '^[A-Z]{2}$'")
                ->distinct()
                ->orderBy('country_code')
                ->pluck('country_code');
        });

        return view('activity', compact('activities', 'filterUser', 'filterCountry', 'dropdownUsers', 'countries', 'isSystem'));
    }
}
