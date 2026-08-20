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

    // A criteria field with no index (e.g. family/recorded_by before the production index
    // migration landed) can turn one computeProjectStats() call into a multi-hour
    // full-table scan. A 300s lock TTL — reasonable for a "normal" background refresh —
    // meant the lock expired mid-computation, so the next request to view that same
    // project saw the cache still empty and dispatched ANOTHER copy of the identical scan.
    // Observed in production as 14 near-identical multi-hour queries piled up
    // simultaneously, all holding read metadata locks on `occurrences` and blocking a
    // pt-online-schema-change run from ever acquiring the exclusive lock it needed for
    // CREATE TRIGGER. Long enough to outlast any realistic unindexed scan; once the
    // criteria-field indexes exist this margin costs nothing since the query is fast anyway.
    private const COMPUTE_LOCK_SECONDS = 21600; // 6 hours

    public function index(Request $request): View
    {
        $projects = Project::visibleTo(auth()->user())
            ->when($request->filled('q'), fn($q) => $q->where(function ($q) use ($request) {
                $term = '%' . $request->get('q') . '%';
                $q->where('title', 'like', $term)->orWhere('tags', 'like', $term);
            }))
            ->orderByDesc('updated_at')
            ->paginate(50)
            ->withQueryString();

        $stats     = [];
        $computing = [];
        foreach ($projects as $project) {
            $stats[$project->id]     = $this->projectStats($project);
            $computing[$project->id] = $this->isComputing("project_stats_{$project->id}");
        }

        return view('projects.index', compact('projects', 'stats', 'computing'));
    }

    public function show(int $project): View
    {
        $project = Project::with('user')->findOrFail($project);
        abort_unless($project->isVisibleTo(auth()->user()), 403);

        return view('projects.show', [
            'project'             => $project,
            'stats'               => $this->projectStats($project),
            'statsComputing'      => $this->isComputing("project_stats_{$project->id}"),
            'contributors'        => $this->projectContributors($project),
            'contributorsComputing' => $this->isComputing("project_contributors_{$project->id}"),
        ]);
    }

    // True only for "never computed yet" (background job dispatched but hasn't finished,
    // or genuinely never ran) — distinct from "computed and the real answer is zero",
    // which cachedCompute()'s default return value can't tell apart from on its own.
    private function isComputing(string $cacheKeyBase): bool
    {
        return Cache::get($cacheKeyBase . ':computed_at') === null;
    }

    public function create(): View
    {
        return view('projects.create', array_merge(
            [
                'project'       => new Project(['visibility' => 'private', 'mode' => 'criteria']),
                'fields'        => Project::ALLOWED_CRITERIA_FIELDS,
                'numericFields' => Project::NUMERIC_CRITERIA_FIELDS,
                'textOperators'    => Project::TEXT_OPERATORS,
                'numericOperators' => Project::NUMERIC_OPERATORS,
            ],
            $this->criteriaFieldMeta()
        ));
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

        return view('projects.create', array_merge(
            [
                'project'       => $project,
                'fields'        => Project::ALLOWED_CRITERIA_FIELDS,
                'numericFields' => Project::NUMERIC_CRITERIA_FIELDS,
                'textOperators'    => Project::TEXT_OPERATORS,
                'numericOperators' => Project::NUMERIC_OPERATORS,
            ],
            $this->criteriaFieldMeta()
        ));
    }

    // Shared by create()/edit() — which fields require a FULLTEXT index for contains/
    // not_contains (drives operator filtering) and which fields render as a <select> of the
    // values actually present in `occurrences` right now instead of a free-text <input>
    // (drives value-input switching). See Project::operatorsForField() and
    // ProjectCriteriaEvaluator::dropdownOptions().
    private function criteriaFieldMeta(): array
    {
        return [
            'fulltextFields'  => Project::FULLTEXT_CRITERIA_FIELDS,
            'dropdownFields'  => Project::DROPDOWN_CRITERIA_FIELDS,
            'dropdownOptions' => collect(Project::DROPDOWN_CRITERIA_FIELDS)
                ->mapWithKeys(fn($f) => [$f => ProjectCriteriaEvaluator::dropdownOptions($f)])
                ->all(),
        ];
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
            // ALLOWED_OPERATORS above only checks an operator is valid for *some* field
            // (e.g. 'gt' is allowed in general) — this catches the field-specific mismatch
            // (e.g. 'gt' on 'family', or 'contains' on 'year') the same way
            // ProjectCriteriaEvaluator would reject it later when the project's stats/
            // candidates actually get computed, just with a normal validation error here
            // instead of a background job failing silently.
            foreach ($validated['conditions'] as $condition) {
                if (!in_array($condition['operator'], Project::operatorsForField($condition['field']), true)) {
                    abort(422, __("Operator ':operator' is not valid for field ':field'.", [
                        'operator' => $condition['operator'],
                        'field'    => $condition['field'],
                    ]));
                }
            }

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
            // Impact elsewhere) — return the default immediately and let a background
            // callback populate the real numbers whenever it finishes.
            $lock = Cache::lock($cacheKeyBase . ':lock', self::COMPUTE_LOCK_SECONDS);
            if ($lock->get()) {
                // app()->terminating(), not dispatch(Closure)->afterResponse() — dispatch()
                // always routes through the Bus/Queue system, which serializes the job even
                // on the 'sync' driver (CallQueuedClosure round-trips through serialize()/
                // unserialize() regardless of ->afterResponse() only changing *when* it
                // fires). $compute captures $project/$this, and something reachable from
                // there drags a live PDO handle into that serialization — "Serialization of
                // 'PDO' is not allowed", thrown before $compute() ever runs, silently
                // swallowed since dispatch() failures here aren't awaited. This is why
                // project stats could get stuck on the default forever. terminating()
                // callbacks run at the exact same point in the request lifecycle
                // (Kernel::terminate()) but stay in-process — nothing is ever serialized.
                app()->terminating(function () use ($compute, $dataKey, $cacheKeyBase) {
                    try {
                        Cache::forever($dataKey, $compute());
                        Cache::forever($cacheKeyBase . ':computed_at', now()->timestamp);
                    } finally {
                        Cache::lock($cacheKeyBase . ':lock')->forceRelease();
                    }
                });
            }

            return $default;
        }

        $isStale = Cache::get($cacheKeyBase . ':computed_at', 0) < now()->subSeconds($staleAfterSeconds)->timestamp;
        if ($isStale) {
            $lock = Cache::lock($cacheKeyBase . ':lock', self::COMPUTE_LOCK_SECONDS);
            if ($lock->get()) {
                app()->terminating(function () use ($compute, $dataKey, $cacheKeyBase) {
                    try {
                        Cache::forever($dataKey, $compute());
                        Cache::forever($cacheKeyBase . ':computed_at', now()->timestamp);
                    } finally {
                        Cache::lock($cacheKeyBase . ':lock')->forceRelease();
                    }
                });
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
            [
                'total' => 0, 'georeferenced' => 0, 'validated' => 0, 'ungeoreferenced' => 0,
                'locality_groups' => 0, 'locality_groups_missing' => 0,
                'gbif' => 0, 'pending' => 0,
            ]
        );
    }

    private function computeProjectStats(Project $project): array
    {
        // Specimen counts alone understate/overstate the real effort — georeferencing is
        // done per locality description, not per specimen, so a project spanning 3,000
        // specimens might only be ~200 distinct places to actually look at (same reasoning
        // as the global Stats page's "N locality groups with work remaining").
        //
        // 'gbif'/'pending' mirror the global Stats page's breakdown ('Coordinates from GBIF'
        // combines gbif_georeferenced+gbif_reviewed; 'pending' = has_suggestion, i.e.
        // suggestions submitted but not yet at the validation threshold) so the project
        // progress bar can use the same four-way legend instead of a single georeferenced%.
        $agg = $this->scopedOccurrencesQuery($project)->selectRaw("
            COUNT(*) as total,
            SUM(georef_status != 'ungeoreferenced') as georeferenced,
            SUM(georef_status = 'validated') as validated,
            SUM(georef_status = 'ungeoreferenced') as ungeoreferenced,
            SUM(georef_status IN ('gbif_georeferenced', 'gbif_reviewed')) as gbif,
            SUM(georef_status = 'has_suggestion') as pending,
            COUNT(DISTINCT locality_group_id) as locality_groups,
            COUNT(DISTINCT CASE WHEN georef_status = 'ungeoreferenced' THEN locality_group_id END) as locality_groups_missing
        ")->first();

        return [
            'total'                    => (int) $agg->total,
            'georeferenced'            => (int) $agg->georeferenced,
            'validated'                => (int) $agg->validated,
            'ungeoreferenced'          => (int) $agg->ungeoreferenced,
            'gbif'                     => (int) $agg->gbif,
            'pending'                  => (int) $agg->pending,
            'locality_groups'          => (int) $agg->locality_groups,
            'locality_groups_missing'  => (int) $agg->locality_groups_missing,
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
            // whereIn('col', []) already compiles to an always-false condition on its own —
            // an empty submitted_keys list correctly yields nothing here, no extra guard needed.
            $query->whereIn('gbif_occurrence_key', $project->submitted_keys ?? []);
        } else {
            [$where, $bindings] = ProjectCriteriaEvaluator::toSqlWhere($project->criteria ?? []);
            // Empty criteria must never mean "no restriction" — skipping whereRaw() entirely
            // here would turn an empty/misconfigured criteria project into an unrestricted
            // scan of the full occurrences table (273M+ rows), which is exactly what produced
            // two 1+ hour stuck queries in production. '1 = 0' keeps this always-false instead,
            // matching whereIn('col', [])'s behavior above for the id_list branch.
            $query->whereRaw($where !== '' ? $where : '1 = 0', $where !== '' ? $bindings : []);
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
