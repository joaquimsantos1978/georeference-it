<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Occurrence;
use App\Models\GeorefSuggestion;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_occurrences' => Occurrence::count(),
            'ungeoreferenced' => Occurrence::where('georef_status', 'ungeoreferenced')->count(),
            'has_suggestion' => Occurrence::where('georef_status', 'has_suggestion')->count(),
            'validated' => Occurrence::where('georef_status', 'validated')->count(),
            'total_users' => User::count(),
            'total_suggestions' => GeorefSuggestion::count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}