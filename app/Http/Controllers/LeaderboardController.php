<?php

namespace App\Http\Controllers;

use App\Models\User;

class LeaderboardController extends Controller
{
    public function index()
    {
        $users = User::with('userLevel')
            ->withCount('suggestions')
            ->selectRaw('users.*, (
                SELECT COUNT(*) FROM georef_validations gv
                JOIN georef_suggestions gs ON gs.id = gv.suggestion_id
                WHERE gv.user_id = users.id AND gs.user_id != gv.user_id
            ) as reviews_count')
            ->orderByDesc('total_validated')
            ->orderByDesc('suggestions_count')
            ->take(50)
            ->get();

        return view('leaderboard', compact('users'));
    }
}