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
                'validations as validations_count' => fn($q) => $q->whereColumn(
                    'georef_validations.user_id', '!=',
                    \DB::raw('(SELECT user_id FROM georef_suggestions WHERE georef_suggestions.id = georef_validations.suggestion_id)')
                ),
            ])
            ->orderByDesc('total_validated')
            ->orderByDesc('suggestions_count')
            ->take(50)
            ->get();

        return view('leaderboard', compact('users'));
    }
}