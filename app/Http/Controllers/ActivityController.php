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

        // Resolve filtered user (must have public_name or be the auth user)
        $filterUser = null;
        if ($filterUserId) {
            $filterUser = User::find($filterUserId);
            if ($filterUser && !$filterUser->public_name && $authId !== $filterUser->id) {
                $filterUser   = null;
                $filterUserId = null;
            }
        }

        $activities = DB::table('activity_log as al')
            ->select(
                'al.id', 'al.type', 'al.locality_group_id', 'al.occ_count',
                'al.lat', 'al.lng', 'al.uncertainty_m', 'al.remarks',
                'al.country_code', 'al.location_label', 'al.created_at',
                DB::raw("IF(u.public_name = 1 OR u.id = {$authId}, u.name, NULL) as user_name"),
                DB::raw("IF(u.public_name = 1 OR u.id = {$authId}, u.id, NULL) as public_user_id"),
                DB::raw("IF(u.public_name = 1 OR u.id = {$authId}, u.avatar, NULL) as user_avatar")
            )
            ->leftJoin('users as u', 'u.id', '=', 'al.user_id')
            ->when($filterUserId, fn($q) => $q->where('al.user_id', $filterUserId))
            ->when($filterCountry, fn($q) => $q->where('al.country_code', $filterCountry))
            ->orderByDesc('al.created_at')
            ->simplePaginate(40)
            ->withQueryString();

        // Public users for filter dropdown
        $publicUsers = User::where('public_name', true)
            ->withCount('suggestions')
            ->having('suggestions_count', '>', 0)
            ->orderByDesc('suggestions_count')
            ->limit(50)
            ->get(['id', 'name', 'avatar']);

        return view('activity', compact('activities', 'filterUser', 'filterCountry', 'publicUsers'));
    }
}
