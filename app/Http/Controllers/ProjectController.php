<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Support\ProjectCriteriaEvaluator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProjectController extends Controller
{
    private const MAX_ID_LIST_KEYS = 20000;

    public function index(Request $request): View
    {
        $projects = Project::visibleTo(auth()->user())
            ->when($request->filled('q'), fn($q) => $q->where('title', 'like', '%' . $request->get('q') . '%'))
            ->orderByDesc('updated_at')
            ->paginate(50)
            ->withQueryString();

        $stats = [];
        foreach ($projects as $project) {
            $stats[$project->id] = $this->projectStats($project);
        }

        return view('projects.index', compact('projects', 'stats'));
    }

    public function show(int $project): View
    {
        $project = Project::with('user')->findOrFail($project);
        abort_unless($project->isVisibleTo(auth()->user()), 403);

        return view('projects.show', [
            'project'      => $project,
            'stats'        => $this->projectStats($project),
            'contributors' => $this->projectContributors($project),
        ]);
    }

    public function create(): View
    {
        return view('projects.create', [
            'project'  => new Project(['visibility' => 'private', 'mode' => 'criteria']),
            'fields'   => Project::ALLOWED_CRITERIA_FIELDS,
            'operators' => Project::ALLOWED_OPERATORS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateRequest($request);
        $project = new Project();
        $project->user_id = auth()->id();
        $this->applyValidated($project, $validated, $request);
        $project->save();

        return redirect()->route('projects')->with('status', 'project-created');
    }

    public function edit(int $project): View
    {
        $project = Project::findOrFail($project);
        abort_unless($project->isOwnedBy(auth()->user()), 403);

        return view('projects.create', [
            'project'   => $project,
            'fields'    => Project::ALLOWED_CRITERIA_FIELDS,
            'operators' => Project::ALLOWED_OPERATORS,
        ]);
    }

    public function update(Request $request, int $project): RedirectResponse
    {
        $project = Project::findOrFail($project);
        abort_unless($project->isOwnedBy(auth()->user()), 403);

        $validated = $this->validateRequest($request);
        $criteriaChanged = $project->mode !== $validated['mode']
            || json_encode($project->criteria) !== json_encode($validated['criteria'] ?? null)
            || json_encode($project->submitted_keys) !== json_encode($validated['submitted_keys'] ?? null);

        $this->applyValidated($project, $validated, $request);
        $project->save();

        if ($criteriaChanged) {
            $this->forgetStatsCache($project);
        }

        return redirect()->route('projects')->with('status', 'project-updated');
    }

    public function destroy(int $project): RedirectResponse
    {
        $project = Project::findOrFail($project);
        abort_unless($project->isOwnedBy(auth()->user()), 403);

        if ($project->image) {
            Storage::disk('public')->delete('projects/' . basename($project->image));
        }
        $project->delete();

        return redirect()->route('projects')->with('status', 'project-deleted');
    }

    private function validateRequest(Request $request): array
    {
        // Only the active panel's rules are included at all — the criteria builder and
        // the occurrence-keys textarea are both always present in the DOM (an x-show'd
        // panel is just display:none, its fields still submit), so validating both
        // unconditionally would fail on whichever panel the user isn't using (its stale/
        // empty values get converted to null by Laravel's ConvertEmptyStringsToNull
        // middleware, which then fails a bare `string`/`in:` rule). Branching on the
        // submitted mode sidesteps that instead of fighting it with required_if/nullable.
        $rules = [
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'tags'        => 'nullable|string|max:500',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'visibility'  => 'required|in:public,private',
            'mode'        => 'required|in:criteria,id_list',
        ];

        if ($request->input('mode') === 'id_list') {
            $rules['occurrence_keys_raw'] = 'required|string';
        } else {
            $rules['conditions']            = 'required|array|min:1';
            $rules['conditions.*.field']    = 'required|in:' . implode(',', Project::ALLOWED_CRITERIA_FIELDS);
            $rules['conditions.*.operator'] = 'required|in:' . implode(',', Project::ALLOWED_OPERATORS);
            $rules['conditions.*.value']    = 'required|string|max:255';
        }

        $validated = $request->validate($rules);

        if ($validated['mode'] === 'id_list') {
            $keys = preg_split('/[\s,]+/', trim($request->get('occurrence_keys_raw', '')), -1, PREG_SPLIT_NO_EMPTY);
            $keys = array_values(array_unique($keys));
            if (count($keys) > self::MAX_ID_LIST_KEYS) {
                abort(422, __('Too many occurrence keys — max :max.', ['max' => number_format(self::MAX_ID_LIST_KEYS)]));
            }
            $validated['submitted_keys'] = $keys;
            $validated['criteria'] = null;
        } else {
            $validated['criteria'] = $validated['conditions'];
            $validated['submitted_keys'] = null;
        }

        return $validated;
    }

    private function applyValidated(Project $project, array $validated, Request $request): void
    {
        $project->title       = $validated['title'];
        $project->description = $validated['description'] ?? null;
        $project->tags        = $validated['tags'] ?? null;
        $project->visibility   = $validated['visibility'];
        $project->mode         = $validated['mode'];
        $project->criteria     = $validated['criteria'];
        $project->submitted_keys = $validated['submitted_keys'];

        if ($project->mode === 'id_list') {
            $matched = DB::table('occurrences')
                ->whereIn('gbif_occurrence_key', $validated['submitted_keys'])
                ->whereNull('deleted_at')
                ->pluck('gbif_occurrence_key')
                ->all();
            $project->invalid_keys = array_values(array_diff($validated['submitted_keys'], $matched));
        } else {
            $project->invalid_keys = null;
        }

        if ($request->hasFile('image')) {
            if ($project->image) {
                Storage::disk('public')->delete('projects/' . basename($project->image));
            }
            $project->image = $this->storeImage($request->file('image'), $project);
        }
    }

    // Same square-crop-and-resize pattern as ProfileController::uploadAvatar(), just a
    // bigger canvas (directory thumbnails, not a tiny avatar).
    private function storeImage($file, Project $project): string
    {
        $source  = imagecreatefromstring(file_get_contents($file->getRealPath()));
        [$w, $h] = getimagesize($file->getRealPath());

        $size   = min($w, $h);
        $x      = (int) (($w - $size) / 2);
        $y      = (int) (($h - $size) / 2);
        $canvas = imagecreatetruecolor(400, 400);
        imagecopyresampled($canvas, $source, 0, 0, $x, $y, 400, 400, $size, $size);
        imagedestroy($source);

        $filename = 'projects/' . ($project->id ?: 'new') . '_' . time() . '.jpg';
        ob_start();
        imagejpeg($canvas, null, 85);
        $data = ob_get_clean();
        imagedestroy($canvas);

        Storage::disk('public')->put($filename, $data);

        return '/storage/' . $filename;
    }

    // Same "compute once, cache forever, refresh stale in the background" shape used for
    // both stats and contributors below — extracted so that logic (lock, stale check,
    // afterResponse refresh) isn't duplicated per cached value. Not built on
    // CountsWithStaleWhileRevalidate (used elsewhere for a single int) because both callers
    // here cache an array from one aggregate query; caching each field separately would mean
    // running a potentially-expensive unindexed scan multiple times for no reason.
    private function cachedCompute(string $cacheKeyBase, callable $compute, $default)
    {
        $dataKey = $cacheKeyBase . ':data';
        $cached  = Cache::get($dataKey);
        $staleAfterSeconds = 3600;

        if ($cached === null) {
            // Never compute inline here, even on the very first view — a criteria field
            // with no index (e.g. `family`, before the planned production index lands)
            // turns this into a full-table scan over 280M+ rows, and a brand-new project
            // computing that synchronously in the request that renders /projects is
            // exactly what produced a 504 in practice. Same "never compute on the page
            // request" rule as the stale-refresh branch below (and as Stats/Activity/
            // Impact elsewhere) — return the default immediately and let the background
            // job populate the real numbers whenever it finishes.
            $lock = Cache::lock($cacheKeyBase . ':lock', 300);
            if ($lock->get()) {
                // Not $lock->release() in the closure — dispatch(Closure) always wraps it
                // in a serializable job (CallQueuedClosure) regardless of ->afterResponse(),
                // and this app's cache/queue driver is DB-backed, so a captured Lock object
                // holds a live PDO connection that can't be serialized ("Serialization of
                // 'PDO' is not allowed" — confirmed in the log, silently swallowed since
                // dispatch() failures here aren't awaited). A fresh forceRelease() call only
                // needs the cache key string, which serializes fine.
                dispatch(function () use ($compute, $dataKey, $cacheKeyBase) {
                    try {
                        Cache::forever($dataKey, $compute());
                        Cache::forever($cacheKeyBase . ':computed_at', now()->timestamp);
                    } finally {
                        Cache::lock($cacheKeyBase . ':lock')->forceRelease();
                    }
                })->afterResponse();
            }

            return $default;
        }

        $isStale = Cache::get($cacheKeyBase . ':computed_at', 0) < now()->subSeconds($staleAfterSeconds)->timestamp;
        if ($isStale) {
            $lock = Cache::lock($cacheKeyBase . ':lock', 300);
            if ($lock->get()) {
                dispatch(function () use ($compute, $dataKey, $cacheKeyBase) {
                    try {
                        Cache::forever($dataKey, $compute());
                        Cache::forever($cacheKeyBase . ':computed_at', now()->timestamp);
                    } finally {
                        Cache::lock($cacheKeyBase . ':lock')->forceRelease();
                    }
                })->afterResponse();
            }
        }

        return $cached;
    }

    // Stats (total/georeferenced/validated/ungeoreferenced) for the directory and homepage,
    // computed by applying the exact same scope `next()` uses (id_list -> whereIn
    // gbif_occurrence_key, criteria -> ProjectCriteriaEvaluator).
    private function projectStats(Project $project): array
    {
        return $this->cachedCompute(
            "project_stats_{$project->id}",
            fn() => $this->computeProjectStats($project),
            ['total' => 0, 'georeferenced' => 0, 'validated' => 0, 'ungeoreferenced' => 0]
        );
    }

    private function computeProjectStats(Project $project): array
    {
        $agg = $this->scopedOccurrencesQuery($project)->selectRaw("
            COUNT(*) as total,
            SUM(georef_status != 'ungeoreferenced') as georeferenced,
            SUM(georef_status = 'validated') as validated,
            SUM(georef_status = 'ungeoreferenced') as ungeoreferenced
        ")->first();

        return [
            'total'           => (int) $agg->total,
            'georeferenced'   => (int) $agg->georeferenced,
            'validated'       => (int) $agg->validated,
            'ungeoreferenced' => (int) $agg->ungeoreferenced,
        ];
    }

    // Per-user contribution breakdown for the project's show page — who georeferenced
    // something within this project's scope, how many times, and when they last did.
    // Scoped through locality_groups the project's occurrences actually belong to (a
    // bounded id list, same pattern GeorefController/GbifPruneCorrupted already use for
    // "restrict to this candidate set") rather than joining occurrences directly, since
    // activity_log only carries locality_group_id, not occurrence id.
    private function projectContributors(Project $project): array
    {
        return $this->cachedCompute(
            "project_contributors_{$project->id}",
            fn() => $this->computeProjectContributors($project),
            []
        );
    }

    private function computeProjectContributors(Project $project): array
    {
        $groupIds = $this->scopedOccurrencesQuery($project)
            ->whereNotNull('locality_group_id')
            ->distinct()
            ->pluck('locality_group_id');

        if ($groupIds->isEmpty()) {
            return [];
        }

        $rows = DB::table('activity_log as al')
            ->join('users as u', 'u.id', '=', 'al.user_id')
            ->whereIn('al.locality_group_id', $groupIds)
            ->selectRaw('
                u.id, u.name, u.public_name,
                COUNT(*) as georef_count,
                MAX(al.created_at) as last_contribution
            ')
            ->groupBy('u.id', 'u.name', 'u.public_name')
            ->orderByDesc('georef_count')
            ->limit(50)
            ->get();

        return $rows->map(fn($r) => [
            'id'                => $r->id,
            'name'              => $r->name,
            'public_name'       => (bool) $r->public_name,
            'georef_count'      => (int) $r->georef_count,
            'last_contribution' => $r->last_contribution,
        ])->all();
    }

    private function scopedOccurrencesQuery(Project $project): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('occurrences')->whereNull('deleted_at');

        if ($project->mode === 'id_list') {
            $query->whereIn('gbif_occurrence_key', $project->submitted_keys ?? []);
        } else {
            [$where, $bindings] = ProjectCriteriaEvaluator::toSqlWhere($project->criteria ?? []);
            if ($where !== '') {
                $query->whereRaw($where, $bindings);
            }
        }

        return $query;
    }

    private function forgetStatsCache(Project $project): void
    {
        foreach (["project_stats_{$project->id}", "project_contributors_{$project->id}"] as $cacheKey) {
            Cache::forget($cacheKey . ':data');
            Cache::forget($cacheKey . ':computed_at');
        }
    }
}
