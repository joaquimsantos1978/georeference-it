<?php

namespace App\Http\Controllers;

use App\Models\GeorefSuggestion;
use App\Models\GeorefValidation;
use App\Models\LocalityGroup;
use App\Models\LocalityGroupComment;
use App\Models\Occurrence;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;

class GeorefController extends Controller
{
    public function index()
    {
        return view('georef.index');
    }

    private function groupData(LocalityGroup $group): array
    {
        $occurrences = Occurrence::where('locality_group_id', $group->id)
            ->get([
                'id', 'gbif_occurrence_key', 'catalog_number', 'institution_code',
                'collection_code', 'scientific_name', 'georef_status', 'media',
                'gbif_decimal_latitude', 'gbif_decimal_longitude',
                'recorded_by', 'event_date', 'dataset_key', 'basis_of_record',
            ]);

        $suggestions = GeorefSuggestion::where('locality_group_id', $group->id)
            ->where('status', 'pending')
            ->with('user')
            ->get()
            ->map(fn($s) => [
                'id'                      => $s->id,
                'decimal_latitude'        => $s->decimal_latitude,
                'decimal_longitude'       => $s->decimal_longitude,
                'coordinate_uncertainty_m'=> $s->coordinate_uncertainty_m,
                'total_points'            => $s->total_points,
                'submitted_by'            => $s->submitted_by,
                'georeference_remarks'    => $s->georeference_remarks,
            ]);

        $comments = LocalityGroupComment::where('locality_group_id', $group->id)
            ->with('user')->latest()->take(20)->get()
            ->map(fn($c) => [
                'user_name'  => $c->user->name,
                'body'       => $c->body,
                'created_at' => $c->created_at->diffForHumans(),
            ]);

        return [
            'group'       => $group,
            'occurrences' => $occurrences,
            'suggestions' => $suggestions,
            'comments'    => $comments,
        ];
    }

public function next(Request $request)
{
    $country       = $request->get('country');
    $q             = $request->get('q');
    $preferredTask = auth()->check() ? auth()->user()->preferred_task : 'georef';

    $group = null;

    // Build locality filter closure
    $localityFilter = function ($query) use ($q) {
        $query->where('verbatim_locality', 'like', "%{$q}%")
              ->orWhere('municipality',     'like', "%{$q}%")
              ->orWhere('county',           'like', "%{$q}%")
              ->orWhere('locality_string',  'like', "%{$q}%");
    };

    // Expand search: exact q → county → state_province → country → all
    $filters = $q ? [
        ['q' => $q],
        ['county'         => $this->extractField($q, 'county')],
        ['state_province' => $this->extractField($q, 'state_province')],
        ['country_code'   => $country],
        [],
    ] : [[]];

    foreach ($filters as $filter) {
        if (auth()->check() && in_array($preferredTask, ['validate', 'both'])) {
            $group = LocalityGroup::where('pending_count', '>', 0)
                ->when(!empty($filter['q']), function ($query) use ($localityFilter) {
                    $query->where($localityFilter);
                })
                ->when(!empty($filter['county']), fn($query) =>
                    $query->where('county', 'like', '%'.$filter['county'].'%'))
                ->when(!empty($filter['state_province']), fn($query) =>
                    $query->where('state_province', 'like', '%'.$filter['state_province'].'%'))
                ->when(!empty($filter['country_code']), fn($query) =>
                    $query->where('country_code', $filter['country_code']))
                ->whereHas('suggestions', function ($q) {
                    $q->where('status', 'pending')
                        ->where(function ($q2) {
                            $q2->whereNull('user_id')
                                ->orWhere('user_id', '!=', auth()->id());
                        });
                })
                ->inRandomOrder()
                ->first();
        }

        if (!$group && in_array($preferredTask, ['georef', 'both'])) {
            $group = LocalityGroup::whereHas('occurrences', function ($q) {
                    $q->whereIn('georef_status', ['ungeoreferenced']);
                })
                ->when(!empty($filter['q']), function ($query) use ($localityFilter) {
                    $query->where($localityFilter);
                })
                ->when(!empty($filter['county']), fn($query) =>
                    $query->where('county', 'like', '%'.$filter['county'].'%'))
                ->when(!empty($filter['state_province']), fn($query) =>
                    $query->where('state_province', 'like', '%'.$filter['state_province'].'%'))
                ->when(!empty($filter['country_code']), fn($query) =>
                    $query->where('country_code', $filter['country_code']))
                ->inRandomOrder()
                ->first();
        }

        if ($group) break;
    }

    if (!$group) {
        return response()->json(['group' => null]);
    }

    return response()->json($this->groupData($group));
}

private function extractField(string $q, string $field): ?string
{
    // Future: use geocoding to extract county/state from free text
    // For now return null — expansion uses country_code fallback
    return null;
}

    public function group(Request $request, int $groupId)
    {
        $group = LocalityGroup::findOrFail($groupId);
        return response()->json($this->groupData($group));
    }

    public function submit(Request $request)
    {
        $validated = $request->validate([
            'locality_group_id'        => 'required|exists:locality_groups,id',
            'decimal_latitude'         => 'required|numeric|between:-90,90',
            'decimal_longitude'        => 'required|numeric|between:-180,180',
            'coordinate_uncertainty_m' => 'nullable|integer|min:1',
            'georeference_remarks'     => 'nullable|string|max:1000',
            'anon_name'                => 'nullable|string|max:255',
            'excluded_occurrence_ids'  => 'nullable|array',
            'excluded_occurrence_ids.*'=> 'integer|exists:occurrences,id',
        ]);

        $group = LocalityGroup::findOrFail($validated['locality_group_id']);

        $suggestion = GeorefSuggestion::create([
            'locality_group_id'        => $group->id,
            'occurrence_id'            => $group->occurrences()->first()->id,
            'user_id'                  => auth()->id(),
            'anon_name'                => $validated['anon_name'] ?? null,
            'decimal_latitude'         => $validated['decimal_latitude'],
            'decimal_longitude'        => $validated['decimal_longitude'],
            'geodetic_datum'           => 'epsg:4326',
            'coordinate_uncertainty_m' => $validated['coordinate_uncertainty_m'] ?? null,
            'georeference_remarks'     => $validated['georeference_remarks'] ?? null,
            'georeference_protocol'    => 'Georeferencing Quick Reference Guide (Zermoglio et al. 2020)',
            'georeference_sources'     => 'georeference.it',
            'status'                   => 'pending',
            'total_points'             => 0,
            'georeferenced_date'       => now(),
        ]);

        if (!empty($validated['excluded_occurrence_ids'])) {
            foreach ($validated['excluded_occurrence_ids'] as $occurrenceId) {
                $suggestion->exclusions()->create(['occurrence_id' => $occurrenceId]);
            }
        }

        $group->occurrences()
            ->whereNotIn('id', $validated['excluded_occurrence_ids'] ?? [])
            ->whereIn('georef_status', ['ungeoreferenced'])
            ->update(['georef_status' => 'has_suggestion']);

        $group->increment('pending_count');

        if (auth()->check()) {
            $this->applyVote($suggestion, auth()->user(), 'agree');
        }

        return response()->json(['success' => true, 'suggestion_id' => $suggestion->id]);
    }

    public function validate(Request $request, GeorefSuggestion $suggestion)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Login required'], 401);
        }

        $validated = $request->validate([
            'vote' => 'required|in:agree,disagree,abstain',
        ]);

        if ($suggestion->validations()->where('user_id', auth()->id())->exists()) {
            return response()->json(['success' => false, 'message' => 'Already voted']);
        }

        if ($suggestion->user_id && $suggestion->user_id === auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Cannot validate your own suggestion']);
        }

        $this->applyVote($suggestion, auth()->user(), $validated['vote']);

        return response()->json(['success' => true]);
    }

    public function comment(Request $request)
    {
        $validated = $request->validate([
            'locality_group_id' => 'required|exists:locality_groups,id',
            'body'              => 'required|string|max:1000',
        ]);

        LocalityGroupComment::create([
            'locality_group_id' => $validated['locality_group_id'],
            'user_id'           => auth()->id(),
            'body'              => $validated['body'],
        ]);

        $comments = LocalityGroupComment::where('locality_group_id', $validated['locality_group_id'])
            ->with('user')->latest()->take(20)->get()
            ->map(fn($c) => [
                'user_name'  => $c->user->name,
                'body'       => $c->body,
                'created_at' => $c->created_at->diffForHumans(),
            ]);

        return response()->json(['success' => true, 'comments' => $comments]);
    }

    public function detectLocation(Request $request): \Illuminate\Http\JsonResponse
    {
        $ip = $request->ip();

        if (in_array($ip, ['127.0.0.1', '::1'])) {
            return response()->json([
                'city'         => 'Coimbra',
                'region'       => 'Centro',
                'country'      => 'Portugal',
                'country_code' => 'PT',
                'lat'          => 40.2033,
                'lon'          => -8.4103,
            ]);
        }

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(3)
                ->get("http://ip-api.com/json/{$ip}?fields=status,country,countryCode,region,regionName,city,lat,lon");

            $data = $response->json();

            if (($data['status'] ?? '') === 'success') {
                return response()->json([
                    'city'         => $data['city']       ?? null,
                    'region'       => $data['regionName'] ?? null,
                    'country'      => $data['country']    ?? null,
                    'country_code' => $data['countryCode']?? null,
                    'lat'          => $data['lat']         ?? null,
                    'lon'          => $data['lon']         ?? null,
                ]);
            }
        } catch (\Exception $e) {
            // Fail silently
        }

        return response()->json([]);
    }

    public function searchLocality(Request $request): \Illuminate\Http\JsonResponse
    {
        $q = trim($request->get('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $results = LocalityGroup::where(function ($query) use ($q) {
                $query->where('verbatim_locality', 'like', "%{$q}%")
                      ->orWhere('municipality', 'like', "%{$q}%")
                      ->orWhere('county', 'like', "%{$q}%")
                      ->orWhere('locality_string', 'like', "%{$q}%");
            })
            ->where('occurrence_count', '>', 0)
            ->orderByRaw('(validated_count + pending_count) DESC')
            ->limit(8)
            ->get(['id', 'verbatim_locality', 'municipality', 'county',
                   'state_province', 'country_code', 'occurrence_count',
                   'pending_count', 'validated_count'])
            ->map(fn($g) => [
                'type'             => 'local',
                'id'               => $g->id,
                'label'            => implode(', ', array_filter([
                    $g->verbatim_locality, $g->municipality,
                    $g->county, $g->state_province, $g->country_code,
                ])),
                'occurrence_count' => $g->occurrence_count,
                'pending'          => $g->pending_count,
                'validated'        => $g->validated_count,
            ]);

        return response()->json($results);
    }

    private function applyVote(GeorefSuggestion $suggestion, $user, string $vote): void
    {
        $weight = $user->getVoteWeight();

        GeorefValidation::create([
            'suggestion_id' => $suggestion->id,
            'user_id'       => $user->id,
            'vote'          => $vote,
            'points_awarded'=> $vote === 'agree' ? $weight : 0,
        ]);

        if ($vote === 'agree') {
            $suggestion->increment('total_points', $weight);
            $suggestion->refresh();

            $threshold = (int) PlatformSetting::get('validation_threshold', 60);

            if ($suggestion->total_points >= $threshold) {
                $this->validateSuggestion($suggestion);
            }
        }
    }

    private function validateSuggestion(GeorefSuggestion $suggestion): void
    {
        $suggestion->update(['status' => 'validated']);

        $excludedIds = $suggestion->exclusions()->pluck('occurrence_id')->toArray();

        $suggestion->localityGroup->occurrences()
            ->whereNotIn('id', $excludedIds)
            ->update(['georef_status' => 'validated']);

        $suggestion->localityGroup->decrement('pending_count');
        $suggestion->localityGroup->increment('validated_count');

        if ($suggestion->user_id) {
            $submitter = $suggestion->user;
            $submitter->increment('total_validated');
            $submitter->updateLevel();

            $submitter->refresh();
            if ($submitter->wasChanged('user_level_id')) {
                $submitter->notifications()->create([
                    'type' => 'level_up',
                    'data' => [
                        'message' => __('Congratulations! You reached level: ') . $submitter->userLevel->name,
                        'level'   => $submitter->userLevel->name,
                    ],
                ]);
            }
        }
    }
}