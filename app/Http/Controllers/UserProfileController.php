<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserProfileController extends Controller
{
    public function show(int $id, Request $request)
    {
        $user = User::with(['userLevel', 'badges'])->findOrFail($id);

        if (!$user->public_name) {
            abort(404);
        }

        $authId = (int) auth()->id();

        $perPage = 40;
        $page    = $request->integer('page', 1) ?: 1;

        $query = DB::table('activity_log as al')
            ->leftJoin('users as su', 'su.id', '=', 'al.suggestion_user_id')
            ->where('al.user_id', $id);

        $total = \Illuminate\Support\Facades\Cache::remember(
            "user_profile_activity_count_{$id}", 3600, fn() => (clone $query)->count()
        );

        $rows = $query
            ->select(
                'al.id', 'al.type', 'al.source', 'al.locality_group_id', 'al.occ_count',
                'al.lat', 'al.lng', 'al.uncertainty_m', 'al.remarks',
                'al.country_code', 'al.location_label', 'al.created_at',
                'al.suggestion_user_id',
                DB::raw("IF(su.public_name = 1, su.name, NULL) as suggestion_author_name"),
                DB::raw("IF(su.public_name = 1, su.id, NULL) as suggestion_author_id")
            )
            ->orderByDesc('al.created_at')
            ->forPage($page, $perPage)
            ->get();

        $activities = new \Illuminate\Pagination\LengthAwarePaginator(
            $rows, $total, $perPage, $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $stats = [
            'georefs'        => $user->suggestions_count ?? DB::table('georef_suggestions')->where('user_id', $id)->count(),
            'validated'      => $user->total_validated ?? 0,
            'reviews'        => DB::table('georef_validations as gv')
                ->join('georef_suggestions as gs', 'gs.id', '=', 'gv.suggestion_id')
                ->where('gv.user_id', $id)
                ->where(fn($q) => $q->where('gs.user_id', '!=', $id)->orWhereNull('gs.user_id'))
                ->count(),
            'specimens'      => DB::table('activity_log')
                ->where('user_id', $id)
                ->where('type', 'georef')
                ->sum('occ_count'),
        ];

        return view('users.profile', compact('user', 'activities', 'stats'));
    }
}
