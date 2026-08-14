<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Support\ProjectCriteriaEvaluator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProjectsRefreshCandidates extends Command
{
    protected $signature = 'projects:refresh-candidates';

    protected $description = 'Rebuild the materialized candidate pool (project_candidate_groups) for every '
        . 'criteria-mode project — moves the cost of evaluating a project\'s (possibly unindexed) criteria '
        . 'out of the live /georef/next request path and into this periodic background pass';

    // Well above the live per-request LIMIT 500 in candidateGroupIdsFromCache() — gives
    // many concurrent sessions enough headroom to cycle through seenIds exclusions between
    // refreshes without running dry, while staying small enough that a DISTINCT-bounded
    // scan of even an unindexed criteria field completes in reasonable time.
    private const CANDIDATE_LIMIT = 5000;

    private const BUCKETS = [
        'georef'   => ['ungeoreferenced'],
        'validate' => ['has_suggestion', 'conflicted'],
    ];

    public function handle(): int
    {
        // id_list mode is already a bounded, indexed whereIn(gbif_occurrence_key) —
        // materializing it would just add staleness for no speed benefit, so it stays on
        // the live path in GeorefController.
        $projects = Project::where('mode', 'criteria')->get();

        $this->info("Refreshing candidate pools for {$projects->count()} criteria-mode projects...");

        foreach ($projects as $project) {
            $t0 = microtime(true);
            $counts = $this->refreshProject($project);
            $elapsed = microtime(true) - $t0;

            $this->line(sprintf(
                '  #%d %s — georef: %d, validate: %d (%.1fs)',
                $project->id,
                $project->title,
                $counts['georef'],
                $counts['validate'],
                $elapsed
            ));
        }

        $this->info('Done.');
        return self::SUCCESS;
    }

    private function refreshProject(Project $project): array
    {
        [$where, $bindings] = ProjectCriteriaEvaluator::toSqlWhere($project->criteria ?? []);

        $counts = [];

        foreach (self::BUCKETS as $bucket => $statuses) {
            $ids = $where !== ''
                ? DB::table('occurrences')
                    ->whereRaw($where, $bindings)
                    ->whereIn('georef_status', $statuses)
                    ->whereNull('deleted_at')
                    ->distinct()
                    ->limit(self::CANDIDATE_LIMIT)
                    ->pluck('locality_group_id')
                : collect();

            // Full replace, not a diff/upsert — same "truncate and rebuild" shape already
            // used for impact_counts. This table only ever holds a bounded snapshot per
            // (project, bucket), so there's nothing an incremental update would save beyond
            // avoiding a brief window with fewer rows than usual for that project.
            DB::table('project_candidate_groups')
                ->where('project_id', $project->id)
                ->where('status_bucket', $bucket)
                ->delete();

            foreach ($ids->chunk(1000) as $chunk) {
                DB::table('project_candidate_groups')->insert(
                    $chunk->map(fn ($groupId) => [
                        'project_id'        => $project->id,
                        'status_bucket'     => $bucket,
                        'locality_group_id' => $groupId,
                        'created_at'        => now(),
                    ])->all()
                );
            }

            $counts[$bucket] = $ids->count();
        }

        return $counts;
    }
}
