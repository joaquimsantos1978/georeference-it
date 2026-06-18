<?php

namespace App\Http\Controllers;

use App\Models\User;

class LeaderboardController extends Controller
{
    public function index()
    {
        $users = User::with('userLevel')
            ->withCount([
                'suggestions',
                'validations as validations_count' => fn($q) => $q->whereHas(
                    'suggestion', fn($q2) => $q2->whereColumn('georef_suggestions.user_id', '!=', 'georef_validations.user_id')
                ),
            ])
            ->orderByDesc('total_validated')
            ->orderByDesc('suggestions_count')
            ->take(50)
            ->get();

        return view('leaderboard', compact('users'));
    }
}