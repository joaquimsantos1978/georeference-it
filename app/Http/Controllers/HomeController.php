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
            ->limit(6)
            ->get();

        // Same pre-computed lookup ImpactController uses for its headline count — never
        // counted here, just read.
        $impactTotal = DB::table('impact_counts')
            ->where('status', 'all')
            ->where('country_code', 'all')
            ->value('count') ?? 0;

        // Same ordering LeaderboardController uses — including the suggestions_count
        // tiebreaker. Dropping that tiebreaker (as an earlier version of this did) looked
        // fine when total_validated varied, but with several users tied at 0 it fell back
        // to arbitrary row order, which didn't match the first 3 rows of the real
        // /leaderboard page. reviews_count/specimens_count are still skipped — those exist
        // to fill out the full leaderboard table, not to order a 3-row teaser.
        // select() replaces the whole select clause, so it has to come before
        // withCount() — the other way around, withCount()'s suggestions_count subquery
        // gets wiped out before the orderBy below can reference it (exactly what happened
        // when this was written the other way: "Unknown column 'suggestions_count'").
        $topContributors = User::select('id', 'name', 'public_name', 'avatar', 'total_validated')
            ->withCount('suggestions')
            ->orderByDesc('total_validated')
            ->orderByDesc('suggestions_count')
            ->take(3)
            ->get();

        return view('home', compact('global', 'projects', 'projectStats', 'recentActivity', 'impactTotal', 'topContributors'));
    }
}
