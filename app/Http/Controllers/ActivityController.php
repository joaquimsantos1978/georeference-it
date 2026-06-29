<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $filterUserId  = $request->integer('user') ?: null;
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

        $activities = DB::table('activity_log as al')
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
            ->leftJoin('users as u', 'u.id', '=', 'al.user_id')
            ->leftJoin('users as su', 'su.id', '=', 'al.suggestion_user_id')
            ->when($filterUserId, fn($q) => $q->where('al.user_id', $filterUserId))
            ->when($filterCountry, fn($q) => $q->where('al.country_code', $filterCountry))
            ->orderByDesc('al.created_at')
            ->simplePaginate(40)
            ->withQueryString();

        // Users for filter dropdown — all users with activity
        $dropdownUsers = DB::table('activity_log as al')
            ->join('users as u', 'u.id', '=', 'al.user_id')
            ->select('u.id', 'u.name', 'u.public_name', DB::raw('COUNT(*) as n'))
            ->whereNotNull('al.user_id')
            ->groupBy('u.id', 'u.name', 'u.public_name')
            ->orderByDesc('n')
            ->limit(50)
            ->get();

        return view('activity', compact('activities', 'filterUser', 'filterCountry', 'dropdownUsers'));
    }
}
