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

        // Resolve the filtered user (must have public_name or be the auth user)
        $filterUser = null;
        if ($filterUserId) {
            $filterUser = User::find($filterUserId);
            if ($filterUser && !$filterUser->public_name && auth()->id() !== $filterUser->id) {
                $filterUser = null;
                $filterUserId = null;
            }
        }

        $query = DB::table('georef_suggestions as gs')
            ->select(
                'gs.locality_group_id',
                'gs.user_id',
                DB::raw('MAX(gs.created_at) as submitted_at'),
                DB::raw('COUNT(*) as occ_georeffed'),
                DB::raw('ANY_VALUE(gs.decimal_latitude) as lat'),
                DB::raw('ANY_VALUE(gs.decimal_longitude) as lng'),
                DB::raw('ANY_VALUE(gs.coordinate_uncertainty_m) as uncertainty_m'),
                DB::raw('ANY_VALUE(gs.georeference_remarks) as remarks'),
                DB::raw('ANY_VALUE(gs.status) as status'),
                'lg.verbatim_locality',
                'lg.municipality',
                'lg.county',
                'lg.state_province',
                'lg.country_code',
                'lg.occurrence_count',
                DB::raw('IF(u.public_name OR u.id = ' . (int) auth()->id() . ', u.name, NULL) as user_name'),
                DB::raw('IF(u.public_name OR u.id = ' . (int) auth()->id() . ', u.id, NULL) as public_user_id'),
                DB::raw('IF(u.public_name OR u.id = ' . (int) auth()->id() . ', u.avatar, NULL) as user_avatar')
            )
            ->join('locality_groups as lg', 'lg.id', '=', 'gs.locality_group_id')
            ->leftJoin('users as u', 'u.id', '=', 'gs.user_id')
            ->whereNotNull('gs.locality_group_id')
            ->groupBy('gs.locality_group_id', 'gs.user_id', 'lg.verbatim_locality', 'lg.municipality', 'lg.county', 'lg.state_province', 'lg.country_code', 'lg.occurrence_count')
            ->orderByDesc('submitted_at');

        if ($filterUserId) {
            $query->where('gs.user_id', $filterUserId);
        }

        if ($filterCountry) {
            $query->where('lg.country_code', $filterCountry);
        }

        $activities = $query->paginate(40)->withQueryString();

        // Public users for filter dropdown (top contributors)
        $publicUsers = User::where('public_name', true)
            ->withCount('suggestions')
            ->having('suggestions_count', '>', 0)
            ->orderByDesc('suggestions_count')
            ->limit(50)
            ->get(['id', 'name', 'avatar']);

        return view('activity', compact('activities', 'filterUser', 'filterCountry', 'publicUsers'));
    }
}
