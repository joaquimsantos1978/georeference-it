<?php

namespace App\Http\Controllers;

use App\Models\User;

class LeaderboardController extends Controller
{
    public function index()
    {
        $users = User::with('userLevel')
            ->withCount('suggestions')
            ->where('total_validated', '>', 0)
            ->orderBy('total_validated', 'desc')
            ->take(50)
            ->get();

        return view('leaderboard', compact('users'));
    }
}