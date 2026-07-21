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

    // Stats for the directory (total/georeferenced/validated/ungeoreferenced), computed by
    // applying the exact same scope `next()` uses (id_list -> whereIn gbif_occurrence_key,
    // criteria -> ProjectCriteriaEvaluator), then cached — never a live query on page load.
    // Not built on CountsWithStaleWhileRevalidate (used elsewhere for a single int) because
    // all 4 numbers come from one aggregate query; caching them separately would mean
    // running a potentially-expensive unindexed scan up to 4x for no reason.
    private function projectStats(Project $project): array
    {
        $cacheKey  = "project_stats_{$project->id}";
        $dataKey   = $cacheKey . ':data';
        $cached    = Cache::get($dataKey);
        $staleAfterSeconds = 3600;

        if ($cached === null) {
            $lock = Cache::lock($cacheKey . ':lock', 300);
            if ($lock->get()) {
                try {
                    $cached = $this->computeProjectStats($project);
                    Cache::forever($dataKey, $cached);
                    Cache::forever($cacheKey . ':computed_at', now()->timestamp);
                } finally {
                    $lock->release();
                }
            } else {
                try {
                    $lock->block(10);
                    $lock->release();
                } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
                    // still running — fall through to the safe default below
                }
                $cached = Cache::get($dataKey);
            }

            return $cached ?? ['total' => 0, 'georeferenced' => 0, 'validated' => 0, 'ungeoreferenced' => 0];
        }

        $isStale = Cache::get($cacheKey . ':computed_at', 0) < now()->subSeconds($staleAfterSeconds)->timestamp;
        if ($isStale) {
            $lock = Cache::lock($cacheKey . ':lock', 300);
            if ($lock->get()) {
                dispatch(function () use ($project, $dataKey, $cacheKey, $lock) {
                    try {
                        Cache::forever($dataKey, $this->computeProjectStats($project));
                        Cache::forever($cacheKey . ':computed_at', now()->timestamp);
                    } finally {
                        $lock->release();
                    }
                })->afterResponse();
            }
        }

        return $cached;
    }

    private function computeProjectStats(Project $project): array
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

        $agg = $query->selectRaw("
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

    private function forgetStatsCache(Project $project): void
    {
        $cacheKey = "project_stats_{$project->id}";
        Cache::forget($cacheKey . ':data');
        Cache::forget($cacheKey . ':computed_at');
    }
}
