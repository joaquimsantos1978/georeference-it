<?php

namespace App\Http\Controllers;

use App\Models\GeorefSuggestion;
use App\Models\GeorefValidation;
use App\Models\GeorefSuggestionExclusion;
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
            ->limit(500)
            ->get([
                'id', 'gbif_occurrence_key', 'catalog_number', 'institution_code',
                'collection_code', 'scientific_name', 'georef_status', 'media',
                'gbif_decimal_latitude', 'gbif_decimal_longitude',
                'recorded_by', 'event_date', 'dataset_key', 'basis_of_record',
            ]);

        $allGeorefIds = $occurrences
            ->whereNotNull('gbif_decimal_latitude')
            ->pluck('id')
            ->all();

        $suggestions = GeorefSuggestion::where('locality_group_id', $group->id)
            ->where('status', 'pending')
            ->with(['user', 'exclusions'])
            ->get()
            ->map(function ($s) use ($allGeorefIds) {
                $excludedIds = $s->exclusions->pluck('occurrence_id')->all();
                // Occurrences in this cluster = all georef occurrences minus the excluded ones
                $clusterIds = array_values(array_diff($allGeorefIds, $excludedIds));

                return [
                    'id'                       => $s->id,
                    'decimal_latitude'         => $s->decimal_latitude,
                    'decimal_longitude'        => $s->decimal_longitude,
                    'coordinate_uncertainty_m' => $s->coordinate_uncertainty_m,
                    'total_points'             => $s->total_points,
                    'submitted_by'             => $s->submitted_by,
                    'georeference_remarks'     => $s->georeference_remarks,
                    'cluster_occurrence_ids'   => $clusterIds,
                    'is_own'                   => auth()->check() && $s->user_id === auth()->id(),
                ];
            });

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
    $focus         = trim($request->get('focus', ''));
    $country       = strtoupper(trim($request->get('country', ''))) ?: null;
    $preferredTask = auth()->check() ? auth()->user()->preferred_task : 'georef';

    // Session-based exclusion list per focus term (persists across skip calls)
    $focusKey  = 'georef_seen_' . md5($focus ?: '__no_focus__');
    $seenIds   = session($focusKey, []);

    // Build reusable locality-scope constraints in order of specificity:
    // 1. focus text match, 2. last served state_province (geographic coherence), 3. country, 4. any
    $lastProvince = session('georef_last_province');
    $lastCounty   = session('georef_last_county');

    $scopes = [];
    if ($focus !== '') {
        $scopes[] = fn($q) => $q->whereRaw(
            'MATCH(verbatim_locality, municipality, county, state_province, locality_string) AGAINST(? IN BOOLEAN MODE)',
            [$focus]
        )->when($country, fn($q2) => $q2->where('country_code', $country));
    }
    if ($lastCounty) {
        $scopes[] = fn($q) => $q->where('county', $lastCounty)
            ->when($country, fn($q2) => $q2->where('country_code', $country));
    }
    if ($lastProvince) {
        $scopes[] = fn($q) => $q->where('state_province', $lastProvince)
            ->when($country, fn($q2) => $q2->where('country_code', $country));
    }
    if ($country) {
        $scopes[] = fn($q) => $q->where('country_code', $country);
    }
    $scopes[] = fn($q) => $q; // fallback: any

    $group  = null;
    $userId = auth()->check() ? auth()->id() : null;

    foreach ($scopes as $scopeIdx => $scope) {
        $isFocusScope = ($focus !== '' && $scopeIdx === 0);

        // Within the focus scope, always try both task types regardless of preference
        // (the user explicitly said where they want to work — show any available work there)
        $wantsValidate = $userId && ($isFocusScope || in_array($preferredTask, ['validate', 'both']));
        $wantsGeoref   = $isFocusScope || in_array($preferredTask, ['georef', 'both']);

        // Try georef first (preferred outcome for most users), then validate
        if ($isFocusScope) {
            // Get top 50 unseen matches, pick randomly in PHP (avoids ORDER BY RAND on huge sets)
            // TODO: restore strict work filter (pending/ungeoreferenced/inconsistent) after backfill
            $candidates = LocalityGroup::where('occurrence_count', '>', 0)
                ->where('occurrence_count', '<', 10000)
                ->whereRaw(
                    'MATCH(verbatim_locality, municipality, county, state_province, locality_string) AGAINST(? IN BOOLEAN MODE)',
                    [$focus]
                )
                ->when($seenIds, fn($q) => $q->whereNotIn('id', $seenIds))
                ->orderByDesc('occurrence_count')
                ->limit(50)
                ->get();
            $group = $candidates->isNotEmpty() ? $candidates->random() : null;

            if (!$group) {
                // Focus exhausted — fall through to country/province scopes with a flag
                session()->forget($focusKey); // reset so user can revisit later
                continue; // keep iterating remaining scopes
            }
        } else {
            if ($wantsGeoref) {
                // TODO: switch to ungeoreferenced_count > 0 after backfill completes
                $georefCandidates = LocalityGroup::where('occurrence_count', '>', 0)
                    ->where('occurrence_count', '<', 10000)
                    ->tap($scope)
                    ->when($seenIds, fn($q) => $q->whereNotIn('id', $seenIds))
                    ->orderByDesc('occurrence_count')
                    ->limit(50)
                    ->get();
                $group = $georefCandidates->isNotEmpty() ? $georefCandidates->random() : null;
            }

            if (!$group && $wantsValidate) {
                // pending_count index is fast; skip per-user suggestion filter to avoid whereHas.
                $validateCandidates = LocalityGroup::where('pending_count', '>', 0)
                    ->tap($scope)
                    ->when($seenIds, fn($q) => $q->whereNotIn('id', $seenIds))
                    ->orderByDesc('pending_count')
                    ->limit(50)
                    ->get();
                $group = $validateCandidates->isNotEmpty() ? $validateCandidates->random() : null;
            }
        }

        if ($group) break;
    }

    if (!$group) {
        return response()->json(['group' => null]);
    }

    // Remember this group as seen (per focus term) and store geographic coherence
    $seenIds[] = $group->id;
    session([
        $focusKey              => $seenIds,
        'georef_last_province' => $group->state_province,
        'georef_last_county'   => $group->county,
    ]);

    return response()->json($this->groupData($group));
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

    // Agree with one suggestion and auto-disagree with all competing ones in the same group.
    // Returns advance=true so the frontend moves to the next group.
    public function agreeWith(Request $request, GeorefSuggestion $suggestion)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Login required'], 401);
        }

        $user = auth()->user();

        if ($suggestion->user_id && $suggestion->user_id === $user->id) {
            return response()->json(['success' => false, 'message' => 'Cannot validate your own suggestion']);
        }

        if ($suggestion->validations()->where('user_id', $user->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Already voted']);
        }

        $this->applyVote($suggestion, $user, 'agree');

        // Auto-disagree with all other pending suggestions in the same group
        $competing = GeorefSuggestion::where('locality_group_id', $suggestion->locality_group_id)
            ->where('id', '!=', $suggestion->id)
            ->where('status', 'pending')
            ->whereDoesntHave('validations', fn($q) => $q->where('user_id', $user->id))
            ->get();

        foreach ($competing as $other) {
            $this->applyVote($other, $user, 'disagree');
        }

        return response()->json(['success' => true, 'advance' => true]);
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

    $results = LocalityGroup::whereRaw(
            'MATCH(verbatim_locality, municipality, county, state_province, locality_string) AGAINST(? IN BOOLEAN MODE)',
            [$q]
        )
        ->where('occurrence_count', '>', 0)
        ->orderByRaw(
            'MATCH(verbatim_locality, municipality, county, state_province, locality_string) AGAINST(? IN BOOLEAN MODE) DESC',
            [$q]
        )
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

    // If no local results, fetch from GBIF in background
    if ($results->isEmpty()) {
        \App\Jobs\FetchGbifByLocality::dispatch($q);
    }

    return response()->json($results);
}

    private function applyVote(GeorefSuggestion $suggestion, $user, string $vote): void
    {
        $weight = $user->getVoteWeight();

        GeorefValidation::create([
            'suggestion_id'  => $suggestion->id,
            'user_id'        => $user->id,
            'vote'           => $vote,
            'points_awarded' => $vote === 'agree' ? $weight : -$weight,
        ]);

        if ($vote === 'agree') {
            $suggestion->increment('total_points', $weight);
            $suggestion->refresh();

            $threshold = (int) PlatformSetting::get('validation_threshold', 60);
            if ($suggestion->total_points >= $threshold) {
                $this->validateSuggestion($suggestion);
            }
        } elseif ($vote === 'disagree') {
            $suggestion->decrement('total_points', $weight);
            $suggestion->refresh();

            $conflictThreshold = (int) PlatformSetting::get('conflict_threshold', -20);
            if ($suggestion->total_points <= $conflictThreshold) {
                $this->conflictSuggestion($suggestion);
            }
        }
    }

    private function conflictSuggestion(GeorefSuggestion $suggestion): void
    {
        $suggestion->update(['status' => 'conflicted']);

        $suggestion->localityGroup->occurrences()
            ->where('georef_status', 'has_suggestion')
            ->update(['georef_status' => 'ungeoreferenced']);

        $suggestion->localityGroup->update(['pending_count' => 0]);
    }

    private function validateSuggestion(GeorefSuggestion $suggestion): void
    {
        $suggestion->update(['status' => 'validated']);

        $excludedIds = $suggestion->exclusions()->pluck('occurrence_id')->toArray();

        $suggestion->localityGroup->occurrences()
            ->whereNotIn('id', $excludedIds)
            ->update(['georef_status' => 'validated']);

        // For consistency-check suggestions, the excluded occurrences are from
        // competing clusters — flag them as needing correction by their publisher.
        if ($suggestion->georeference_sources === 'GBIF_CONSISTENCY_CHECK' && !empty($excludedIds)) {
            $suggestion->localityGroup->occurrences()
                ->whereIn('id', $excludedIds)
                ->where('georef_status', 'gbif_georeferenced')
                ->update(['georef_status' => 'gbif_reviewed']);

            // Mark the group as resolved
            $suggestion->localityGroup->update(['consistency_status' => 'resolved']);
        }

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
            } else {
                // Notify at 50% progress toward next level
                $nextLevel = \App\Models\UserLevel::where('min_validated', '>', $submitter->userLevel->min_validated)
                    ->orderBy('min_validated')->first();
                if ($nextLevel) {
                    $range    = $nextLevel->min_validated - $submitter->userLevel->min_validated;
                    $progress = $submitter->total_validated - $submitter->userLevel->min_validated;
                    $remaining = $nextLevel->min_validated - $submitter->total_validated;
                    if ($range > 0 && $progress === intval($range / 2)) {
                        $submitter->notifications()->create([
                            'type' => 'progress',
                            'data' => [
                                'message' => "Halfway to {$nextLevel->name}! {$remaining} more validated georeferences to go.",
                                'level'   => $nextLevel->name,
                            ],
                        ]);
                    }
                }
            }
        }
    }

public function destroySuggestion(Request $request, GeorefSuggestion $suggestion): \Illuminate\Http\JsonResponse
{
    if ($suggestion->user_id !== auth()->id()) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
    }

    $group = $suggestion->localityGroup;
    $suggestion->validations()->delete();
    $suggestion->exclusions()->delete();
    $suggestion->delete();

    // Recount pending suggestions for this group
    if ($group) {
        $group->pending_count   = $group->suggestions()->where('status', 'pending')->count();
        $group->validated_count = $group->suggestions()->where('status', 'validated')->count();
        $group->save();
    }

    return response()->json(['success' => true]);
}

public function revokeValidation(Request $request, GeorefValidation $validation): \Illuminate\Http\JsonResponse
{
    if ($validation->user_id !== auth()->id()) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
    }

    $suggestion = $validation->suggestion;
    $points = $validation->points_awarded ?? 0;
    $vote   = $validation->vote;

    $validation->delete();

    // Reverse the points on the suggestion
    if ($suggestion) {
        $suggestion->total_points -= ($vote === 'agree' ? $points : -$points);
        $suggestion->save();
    }

    return response()->json(['success' => true]);
}

public function findByGbifKey(Request $request, string $key): \Illuminate\Http\JsonResponse
{
    // Accept full GBIF URLs: extract numeric key
    if (preg_match('/(\d{6,})/', $key, $m)) {
        $key = $m[1];
    }

    $occurrence = \App\Models\Occurrence::where('gbif_occurrence_key', $key)
        ->whereNotNull('locality_group_id')
        ->first(['locality_group_id']);

    if (!$occurrence) {
        return response()->json(['error' => 'Occurrence not found. It may not have been imported yet.'], 404);
    }

    $group = LocalityGroup::findOrFail($occurrence->locality_group_id);
    return response()->json($this->groupData($group));
}

public function sync(Request $request): \Illuminate\Http\JsonResponse
{
    $country = $request->get('country', 'PT');
    
    \App\Jobs\SyncGbifByCountry::dispatch($country);
    
    return response()->json([
        'message' => __('Sync started for ') . $country . __('. Results will appear shortly.'),
    ]);
}
}