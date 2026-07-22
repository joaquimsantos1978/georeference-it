<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    // Landing page — a summary of the platform (stats, projects, recent activity) rather
    // than the georef tool itself. `/georef` (GeorefController::index) is the standalone
    // tool now; this used to be the same route/action as `/`.
    //
    // Every number here is a cache read, never computed on the request — same "pure cache
    // read" rule StatsController/ActivityController already follow, since this page is the
    // one most likely to get hit by anonymous/first-time traffic.
    public function index()
    {
        $global = Cache::get('stats.georef.data')[0] ?? (object) [
            'total_occ' => 0, 'ungeoref_occ' => 0, 'pending_occ' => 0,
            'gbif_occ' => 0, 'validated_occ' => 0, 'gbif_reviewed_occ' => 0,
            'pending_groups' => 0,
        ];

        $projects = Project::visibleTo(auth()->user())
            ->orderByDesc('updated_at')
            ->limit(6)
            ->get();

        $projectStats = [];
        foreach ($projects as $project) {
            $projectStats[$project->id] = Cache::get("project_stats_{$project->id}:data", [
                'total' => 0, 'georeferenced' => 0, 'validated' => 0, 'ungeoreferenced' => 0,
            ]);
        }

        $recentActivity = DB::table('activity_log as al')
            ->leftJoin('users as u', 'u.id', '=', 'al.user_id')
            ->select(
                'al.id', 'al.type', 'al.location_label', 'al.country_code', 'al.created_at',
                DB::raw('IF(u.public_name = 1, u.name, NULL) as user_name')
            )
            ->orderByDesc('al.created_at')
            ->limit(8)
            ->get();

        // Same pre-computed lookup ImpactController uses for its headline count — never
        // counted here, just read.
        $impactTotal = DB::table('impact_counts')
            ->where('status', 'all')
            ->where('country_code', 'all')
            ->value('count') ?? 0;

        // Same ordering LeaderboardController uses, just the top 3 and no join-heavy
        // suggestion/review/specimen subqueries — those exist to break ties and to fill
        // out the full leaderboard table, neither needed for a 3-row teaser.
        $topContributors = User::select('id', 'name', 'public_name', 'avatar', 'total_validated')
            ->orderByDesc('total_validated')
            ->take(3)
            ->get();

        return view('home', compact('global', 'projects', 'projectStats', 'recentActivity', 'impactTotal', 'topContributors'));
    }
}
