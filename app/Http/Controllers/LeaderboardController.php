<?php

namespace App\Http\Controllers;

use App\Models\User;

class LeaderboardController extends Controller
{
    public function index()
    {
        $users = User::with('userLevel')
            ->withCount(['suggestions', 'validations' => fn($q) => $q->where('vote', 'agree')])
            ->orderByDesc('total_validated')
            ->orderByDesc('suggestions_count')
            ->take(50)
            ->get();

        return view('leaderboard', compact('users'));
    }
}