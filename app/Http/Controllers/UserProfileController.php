<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserProfileController extends Controller
{
    public function show(int $id)
    {
        $user = User::with('userLevel')->findOrFail($id);

        if (!$user->public_name) {
            abort(404);
        }

        $authId = (int) auth()->id();

        $activities = DB::table('activity_log as al')
            ->select(
                'al.id', 'al.type', 'al.source', 'al.locality_group_id', 'al.occ_count',
                'al.lat', 'al.lng', 'al.uncertainty_m', 'al.remarks',
                'al.country_code', 'al.location_label', 'al.created_at',
                'al.suggestion_user_id',
                DB::raw("IF(su.public_name = 1, su.name, NULL) as suggestion_author_name"),
                DB::raw("IF(su.public_name = 1, su.id, NULL) as suggestion_author_id")
            )
            ->leftJoin('users as su', 'su.id', '=', 'al.suggestion_user_id')
            ->where('al.user_id', $id)
            ->orderByDesc('al.created_at')
            ->simplePaginate(40)
            ->withQueryString();

        $stats = [
            'georefs'        => $user->suggestions_count ?? DB::table('georef_suggestions')->where('user_id', $id)->count(),
            'validated'      => $user->total_validated ?? 0,
            'reviews'        => DB::table('georef_validations as gv')
                ->join('georef_suggestions as gs', 'gs.id', '=', 'gv.suggestion_id')
                ->where('gv.user_id', $id)
                ->where(fn($q) => $q->where('gs.user_id', '!=', $id)->orWhereNull('gs.user_id'))
                ->count(),
        ];

        return view('users.profile', compact('user', 'activities', 'stats'));
    }
}
