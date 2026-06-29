<?php

namespace App\Http\Controllers;

use App\Mail\CommentNotification;
use App\Models\GeorefSuggestion;
use App\Models\GeorefValidation;
use App\Models\GeorefSuggestionExclusion;
use App\Models\LocalityGroup;
use App\Models\LocalityGroupComment;
use App\Models\Occurrence;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class GeorefController extends Controller
{
    public function index()
    {
        return view('georef.index');
    }

    private const OCC_COLUMNS = [
        'id', 'gbif_occurrence_key', 'catalog_number', 'institution_code',
        'collection_code', 'scientific_name', 'georef_status', 'media',
        'gbif_decimal_latitude', 'gbif_decimal_longitude',
        'recorded_by', 'event_date', 'dataset_key', 'basis_of_record',
    ];

    private function groupData(LocalityGroup $group, int $ungeorefOffset = 0): array
    {
        // Single query: fetch up to 5000 for clustering, full cols for first 500 (map markers).
        // The (locality_group_id, gbif_decimal_latitude) index makes this fast.
        $allGeorefOccurrences = Occurrence::where('locality_group_id', $group->id)
            ->whereNotNull('gbif_decimal_latitude')
            ->limit(5000)
            ->get(array_merge(['id', 'gbif_decimal_latitude', 'gbif_decimal_longitude'], self::OCC_COLUMNS));

        $allGeorefIds      = $allGeorefOccurrences->pluck('id')->all();
        $georefOccurrences = $allGeorefOccurrences->take(500);

        $ungeorefStatuses = ['ungeoreferenced', 'has_suggestion'];
        $ungeorefTotal = Occurrence::where('locality_group_id', $group->id)
            ->whereIn('georef_status', $ungeorefStatuses)
            ->count();
        $ungeorefOccurrences = Occurrence::where('locality_group_id', $group->id)
            ->whereIn('georef_status', $ungeorefStatuses)
            ->offset($ungeorefOffset)
            ->limit(100)
            ->get(self::OCC_COLUMNS);

        $suggestions = GeorefSuggestion::where('locality_group_id', $group->id)
            ->where('status', 'pending')
            ->limit(50)
            ->with(['user', 'exclusions'])
            ->get();

        // For system suggestions (no user_id), assign occurrences by proximity to each
        // suggestion's centroid — whichever suggestion is closest claims the occurrence.
        $systemSuggestions = $suggestions->whereNull('user_id')->values();
        $systemClusterIds = [];
        if ($systemSuggestions->count() > 1) {
            foreach ($allGeorefOccurrences as $occ) {
                $minDist = PHP_FLOAT_MAX;
                $nearest = null;
                $oLat = (float)$occ->gbif_decimal_latitude;
                $oLng = (float)$occ->gbif_decimal_longitude;
                foreach ($systemSuggestions as $s) {
                    $dlat = $oLat - (float)$s->decimal_latitude;
                    $dlng = $oLng - (float)$s->decimal_longitude;
                    $dist = $dlat * $dlat + $dlng * $dlng;
                    if ($dist < $minDist) { $minDist = $dist; $nearest = $s->id; }
                }
                $systemClusterIds[$nearest][] = $occ->id;
            }
        }

        $userId = auth()->id();
        $myVotes = $userId
            ? GeorefValidation::whereIn('suggestion_id', $suggestions->pluck('id'))
                ->where('user_id', $userId)
                ->pluck('vote', 'suggestion_id')
            : collect();

        $mapped = $suggestions->map(function ($s) use ($allGeorefIds, $systemClusterIds, $userId, $myVotes) {
            if (is_null($s->user_id)) {
                $clusterIds = $systemClusterIds[$s->id] ?? $allGeorefIds;
            } else {
                $excludedIds = $s->exclusions->pluck('occurrence_id')->all();
                $clusterIds = array_values(array_diff($allGeorefIds, $excludedIds));
            }

            return [
                'id'                       => $s->id,
                'decimal_latitude'         => $s->decimal_latitude,
                'decimal_longitude'        => $s->decimal_longitude,
                'coordinate_uncertainty_m' => $s->coordinate_uncertainty_m,
                'total_points'             => $s->total_points,
                'submitted_by'             => $s->submitted_by,
                'georeference_remarks'     => $s->georeference_remarks,
                'cluster_occurrence_ids'   => $clusterIds,
                'cluster_count'            => count($clusterIds),
                'is_own'                   => $userId && $s->user_id === $userId,
                'is_system'                => is_null($s->user_id) && $s->georeference_sources === 'GBIF_CONSISTENCY_CHECK',
                'my_vote'                  => $myVotes->get($s->id),
            ];
        });
        $suggestions = $mapped;

        $comments = LocalityGroupComment::where('locality_group_id', $group->id)
            ->with('user')->latest()->take(20)->get()
            ->map(fn($c) => [
                'user_name'  => $c->user->name,
                'body'       => $c->body,
                'created_at' => $c->created_at->diffForHumans(),
            ]);

        // Similar groups: same normalized_locality + county + country_code (excluding this group)
        $similarGroups = [];
        if ($group->normalized_locality) {
            $siblings = LocalityGroup::where('id', '!=', $group->id)
                ->where('normalized_locality', $group->normalized_locality)
                ->where('county', $group->county)
                ->where('country_code', $group->country_code)
                ->get(['id', 'verbatim_locality', 'municipality', 'county', 'state_province', 'country_code', 'occurrence_count', 'ungeoreferenced_count', 'pending_count', 'validated_count']);

            foreach ($siblings as $sib) {
                $sibSuggestions = GeorefSuggestion::where('locality_group_id', $sib->id)
                    ->where('status', 'pending')
                    ->limit(5)
                    ->with('user')
                    ->get()
                    ->map(fn($s) => [
                        'id'                       => $s->id,
                        'decimal_latitude'         => $s->decimal_latitude,
                        'decimal_longitude'        => $s->decimal_longitude,
                        'coordinate_uncertainty_m' => $s->coordinate_uncertainty_m,
                        'submitted_by'             => $s->submitted_by,
                        'georeference_remarks'     => $s->georeference_remarks,
                        'total_points'             => $s->total_points,
                        'is_system'                => is_null($s->user_id) && $s->georeference_sources === 'GBIF_CONSISTENCY_CHECK',
                    ]);

                $similarGroups[] = [
                    'id'                    => $sib->id,
                    'verbatim_locality'     => $sib->verbatim_locality,
                    'municipality'          => $sib->municipality,
                    'county'                => $sib->county,
                    'state_province'        => $sib->state_province,
                    'country_code'          => $sib->country_code,
                    'occurrence_count'      => $sib->occurrence_count,
                    'ungeoreferenced_count' => $sib->ungeoreferenced_count,
                    'pending_count'         => $sib->pending_count,
                    'validated_count'       => $sib->validated_count,
                    'suggestions'           => $sibSuggestions,
                ];
            }
        }

        return [
            'group'               => $group,
            'occurrences'         => $ungeorefOccurrences,
            'ungeoref_total'      => $ungeorefTotal,
            'georef_occurrences'  => $georefOccurrences,
            'suggestions'         => $suggestions,
            'comments'            => $comments,
            'similar_groups'      => $similarGroups,
        ];
    }

public function next(Request $request)
{
    session()->save(); // release session lock before heavy DB work

    $focus         = trim($request->get('focus', ''));
    $country       = strtoupper(trim($request->get('country', ''))) ?: null;
    $preferredTask = auth()->check() ? auth()->user()->preferred_task : 'georef';

    // Session-based exclusion list per focus term (persists across skip calls)
    $focusKey  = 'georef_seen_' . md5($focus ?: '__no_focus__');
    $seenIds   = session($focusKey, []);

    // Exclude the group the user just acted on (may have been loaded directly, not via /next)
    if ($excludeId = (int) $request->get('exclude')) {
        if (!in_array($excludeId, $seenIds)) {
            $seenIds[] = $excludeId;
            session([$focusKey => $seenIds]);
        }
    }

    // Priority: sibling of just-completed group (same normalized_locality + county + country)
    // Skip when focus is active — the user explicitly changed context
    if ($excludeId && $focus === '') {
        $sibling = LocalityGroup::where('id', '!=', $excludeId)
            ->whereNotIn('id', $seenIds)
            ->where(function ($q) use ($excludeId) {
                $ref = LocalityGroup::select('normalized_locality', 'county', 'country_code')
                    ->where('id', $excludeId)->first();
                if ($ref && $ref->normalized_locality) {
                    $q->where('normalized_locality', $ref->normalized_locality)
                      ->where('county', $ref->county)
                      ->where('country_code', $ref->country_code);
                } else {
                    $q->whereRaw('0'); // no siblings if not normalized yet
                }
            })
            ->where(function ($q) {
                $q->where('ungeoreferenced_count', '>', 0)->orWhere('pending_count', '>', 0);
            })
            ->when(auth()->check(), fn($q) => $q->whereDoesntHave('suggestions', fn($s) =>
                $s->where('user_id', auth()->id())->where('status', 'pending')
            ))
            ->first();

        if ($sibling) {
            $seenIds[] = $sibling->id;
            session([
                $focusKey              => $seenIds,
                'georef_last_province' => $sibling->state_province,
                'georef_last_county'   => $sibling->county,
            ]);
            return response()->json($this->groupData($sibling));
        }
    }

    // Build reusable locality-scope constraints in order of specificity:
    // 1. focus text match, 2. last served state_province (geographic coherence), 3. country, 4. any
    $lastProvince = session('georef_last_province');
    $lastCounty   = session('georef_last_county');

    $scopes = [];
    if ($focus !== '') {
        // Focus is an explicit user intent — don't restrict by country so "Braga" finds PT groups even if country=AT
        $scopes[] = fn($q) => $q->whereRaw(
            'MATCH(locality_string) AGAINST(? IN BOOLEAN MODE)',
            [$focus]
        );
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
    } else {
        $scopes[] = fn($q) => $q; // fallback: any (skip when country filter is active)
    }

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
            // Try ungeoreferenced first, then pending — avoids OR which can't use composite indexes
            $focusMatch = fn($q) => $q->whereRaw(
                'MATCH(locality_string) AGAINST(? IN BOOLEAN MODE)',
                [$focus]
            )->when($country, fn($q2) => $q2->where('country_code', $country));

            // No ORDER BY — fulltext index is used directly; we random() the results anyway
            $candidates = LocalityGroup::where('ungeoreferenced_count', '>', 0)
                ->where('occurrence_count', '<', 10000)
                ->tap($focusMatch)
                ->when($seenIds, fn($q) => $q->whereNotIn('id', $seenIds))
                ->limit(50)
                ->get();

            if ($candidates->isEmpty()) {
                $candidates = LocalityGroup::where('pending_count', '>', 0)
                    ->where('occurrence_count', '<', 10000)
                    ->tap($focusMatch)
                    ->when($seenIds, fn($q) => $q->whereNotIn('id', $seenIds))
                    ->limit(50)
                    ->get();
            }

            $group = $candidates->isNotEmpty() ? $candidates->random() : null;

            if (!$group) {
                // Focus exhausted — fall through to country/province scopes with a flag
                session()->forget($focusKey); // reset so user can revisit later
                continue; // keep iterating remaining scopes
            }
        } else {
            if ($wantsGeoref) {
                $georefCandidates = LocalityGroup::where('ungeoreferenced_count', '>', 0)
                    ->where('occurrence_count', '<', 10000)
                    ->tap($scope)
                    ->when($seenIds, fn($q) => $q->whereNotIn('id', $seenIds))
                    ->orderByDesc('occurrence_count')
                    ->limit(50)
                    ->get();
                $group = $georefCandidates->isNotEmpty() ? $georefCandidates->random() : null;
            }

            if (!$group && $wantsValidate) {
                $validateCandidates = LocalityGroup::where('pending_count', '>', 0)
                    ->tap($scope)
                    ->when($seenIds, fn($q) => $q->whereNotIn('id', $seenIds))
                    ->when(auth()->check(), fn($q) => $q->whereDoesntHave('suggestions', fn($s) =>
                        $s->where('user_id', auth()->id())->where('status', 'pending')
                    ))
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
        session()->save(); // release session lock before heavy DB work
        $group = LocalityGroup::findOrFail($groupId);
        return response()->json($this->groupData($group));
    }

    public function groupUngeorefOccurrences(Request $request, int $groupId)
    {
        session()->save();
        $group = LocalityGroup::findOrFail($groupId);
        $offset = max(0, (int) $request->get('offset', 0));
        $occurrences = Occurrence::where('locality_group_id', $group->id)
            ->whereIn('georef_status', ['ungeoreferenced', 'has_suggestion'])
            ->offset($offset)
            ->limit(100)
            ->get(self::OCC_COLUMNS);
        return response()->json(['occurrences' => $occurrences]);
    }

    public function suggestionGeorefOccurrences(Request $request, GeorefSuggestion $suggestion)
    {
        session()->save();
        // Cluster IDs are computed client-side from groupData() and passed here directly.
        $ids = array_filter(array_map('intval', explode(',', $request->get('ids', ''))));
        $occurrences = $ids
            ? Occurrence::whereIn('id', $ids)->get(self::OCC_COLUMNS)
            : collect();
        return response()->json(['occurrences' => $occurrences]);
    }

    public function occurrencesByIds(Request $request)
    {
        session()->save();
        $ids = array_filter(array_map('intval', explode(',', $request->get('ids', ''))));
        $occurrences = $ids
            ? Occurrence::whereIn('id', $ids)->get(self::OCC_COLUMNS)
            : collect();
        return response()->json(['occurrences' => $occurrences]);
    }

    public function submit(Request $request)
    {
        $validated = $request->validate([
            'locality_group_id'           => 'required|exists:locality_groups,id',
            'decimal_latitude'            => 'required|numeric|between:-90,90',
            'decimal_longitude'           => 'required|numeric|between:-180,180',
            'coordinate_uncertainty_m'    => 'nullable|integer|min:1',
            'georeference_remarks'        => 'nullable|string|max:1000',
            'anon_name'                   => 'nullable|string|max:255',
            'excluded_occurrence_ids'     => 'nullable|array',
            'excluded_occurrence_ids.*'   => 'integer|exists:occurrences,id',
            'correct_suggestion_ids'      => 'nullable|array',
            'correct_suggestion_ids.*'    => 'integer|exists:georef_suggestions,id',
            'correct_occurrence_ids'      => 'nullable|array',
            'correct_occurrence_ids.*'    => 'integer|exists:occurrences,id',
            'similar_group_ids'           => 'nullable|array',
            'similar_group_ids.*'         => 'integer|exists:locality_groups,id',
        ]);

        $group = LocalityGroup::findOrFail($validated['locality_group_id']);

        // Replace user's previous pending suggestion for this group
        if (auth()->check()) {
            GeorefSuggestion::where('locality_group_id', $group->id)
                ->where('user_id', auth()->id())
                ->where('status', 'pending')
                ->delete();
        }

        $suggestion = GeorefSuggestion::create([
            'locality_group_id'        => $group->id,
            'locality_group_hash'      => $group->group_hash,
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
            ->whereIn('georef_status', ['ungeoreferenced', 'has_suggestion'])
            ->update(['georef_status' => 'has_suggestion']);

        // Include georef occurrences from suggestions the user chose to correct
        if (!empty($validated['correct_suggestion_ids'])) {
            $correctSuggestions = GeorefSuggestion::whereIn('id', $validated['correct_suggestion_ids'])
                ->where('locality_group_id', $group->id)
                ->with('exclusions')
                ->get();
            foreach ($correctSuggestions as $cs) {
                $excludedIds = $cs->exclusions->pluck('occurrence_id')->all();
                $group->occurrences()
                    ->whereNotNull('gbif_decimal_latitude')
                    ->whereNotIn('id', $excludedIds)
                    ->whereIn('georef_status', ['gbif_georeferenced', 'gbif_reviewed'])
                    ->update(['georef_status' => 'has_suggestion']);
            }
        }

        // Mark specific GBIF-georeferenced occurrences as needing correction (from GBIF cards)
        if (!empty($validated['correct_occurrence_ids'])) {
            $group->occurrences()
                ->whereIn('id', $validated['correct_occurrence_ids'])
                ->whereIn('georef_status', ['gbif_georeferenced', 'gbif_reviewed'])
                ->update(['georef_status' => 'has_suggestion']);
        }

        $group->recalculateCounters();

        // Create suggestions for checked similar groups (same coords/uncertainty/remarks)
        if (!empty($validated['similar_group_ids'])) {
            $similarGroups = LocalityGroup::whereIn('id', $validated['similar_group_ids'])
                ->where('id', '!=', $group->id)
                ->get();

            foreach ($similarGroups as $simGroup) {
                // Replace existing pending suggestion from same user
                if (auth()->check()) {
                    GeorefSuggestion::where('locality_group_id', $simGroup->id)
                        ->where('user_id', auth()->id())
                        ->where('status', 'pending')
                        ->delete();
                }

                $simSuggestion = GeorefSuggestion::create([
                    'locality_group_id'        => $simGroup->id,
                    'locality_group_hash'      => $simGroup->group_hash,
                    'occurrence_id'            => $simGroup->occurrences()->first()?->id,
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

                if (auth()->check()) {
                    $this->applyVote($simSuggestion, auth()->user(), 'agree', false);
                }

                $simGroup->occurrences()
                    ->whereIn('georef_status', ['ungeoreferenced', 'has_suggestion'])
                    ->update(['georef_status' => 'has_suggestion']);

                $simGroup->recalculateCounters();
            }

        }

        if (auth()->check()) {
            $this->applyVote($suggestion, auth()->user(), 'agree');
        }

        // Log the georef event
        $locationLabel = trim(implode(', ', array_filter([
            $group->verbatim_locality, $group->municipality, $group->county,
        ])));
        $activitySource = auth()->check() ? 'user' : 'anonymous';
        DB::table('activity_log')->insert([
            'type'             => 'georef',
            'source'           => $activitySource,
            'user_id'          => auth()->id(),
            'locality_group_id'=> $group->id,
            'occ_count'        => $group->occurrences()->whereNotIn('id', $validated['excluded_occurrence_ids'] ?? [])->count(),
            'lat'              => $validated['decimal_latitude'],
            'lng'              => $validated['decimal_longitude'],
            'uncertainty_m'    => $validated['coordinate_uncertainty_m'] ?? null,
            'remarks'          => $validated['georeference_remarks'] ?? null,
            'country_code'     => $group->country_code,
            'location_label'   => $locationLabel ?: null,
            'created_at'       => now(),
        ]);

        // Log similar group georef events
        if (!empty($validated['similar_group_ids'])) {
            foreach (LocalityGroup::whereIn('id', $validated['similar_group_ids'])->get() as $simGroup) {
                $simLabel = trim(implode(', ', array_filter([$simGroup->verbatim_locality, $simGroup->municipality, $simGroup->county])));
                DB::table('activity_log')->insert([
                    'type'             => 'georef',
                    'source'           => $activitySource,
                    'user_id'          => auth()->id(),
                    'locality_group_id'=> $simGroup->id,
                    'occ_count'        => $simGroup->occurrence_count ?: 1,
                    'lat'              => $validated['decimal_latitude'],
                    'lng'              => $validated['decimal_longitude'],
                    'uncertainty_m'    => $validated['coordinate_uncertainty_m'] ?? null,
                    'remarks'          => $validated['georeference_remarks'] ?? null,
                    'country_code'     => $simGroup->country_code,
                    'location_label'   => $simLabel ?: null,
                    'created_at'       => now(),
                ]);
            }
        }

        return response()->json(['success' => true, 'suggestion_id' => $suggestion->id]);
    }

    public function validate(Request $request, GeorefSuggestion $suggestion)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Login required'], 401);
        }

        $validated = $request->validate([
            'vote'                    => 'required|in:agree,disagree,abstain',
            'excluded_occurrence_ids' => 'nullable|array',
            'excluded_occurrence_ids.*' => 'integer',
        ]);

        if ($suggestion->validations()->where('user_id', auth()->id())->exists()) {
            return response()->json(['success' => false, 'message' => 'Already voted']);
        }

        if ($suggestion->user_id && $suggestion->user_id === auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Cannot validate your own suggestion']);
        }

        $this->applyVote($suggestion, auth()->user(), $validated['vote']);

        if ($validated['vote'] === 'agree' && !empty($validated['excluded_occurrence_ids'])) {
            $existing = $suggestion->exclusions()->pluck('occurrence_id')->all();
            foreach ($validated['excluded_occurrence_ids'] as $occId) {
                if (!in_array($occId, $existing)) {
                    $suggestion->exclusions()->create(['occurrence_id' => $occId]);
                }
            }
        }

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

        $validated = $request->validate([
            'excluded_occurrence_ids'   => 'nullable|array',
            'excluded_occurrence_ids.*' => 'integer',
        ]);

        $this->applyVote($suggestion, $user, 'agree');

        if (!empty($validated['excluded_occurrence_ids'])) {
            $existing = $suggestion->exclusions()->pluck('occurrence_id')->all();
            foreach ($validated['excluded_occurrence_ids'] as $occId) {
                if (!in_array($occId, $existing)) {
                    $suggestion->exclusions()->create(['occurrence_id' => $occId]);
                }
            }
        }

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

        $group = LocalityGroup::findOrFail($validated['locality_group_id']);

        $newComment = LocalityGroupComment::create([
            'locality_group_id' => $validated['locality_group_id'],
            'user_id'           => auth()->id(),
            'body'              => $validated['body'],
        ]);
        $newComment->setRelation('user', auth()->user());

        $this->notifyGroupContributors($group, $newComment);

        $comments = LocalityGroupComment::where('locality_group_id', $validated['locality_group_id'])
            ->with('user')->latest()->take(20)->get()
            ->map(fn($c) => [
                'user_name'  => $c->user->name,
                'body'       => $c->body,
                'created_at' => $c->created_at->diffForHumans(),
            ]);

        return response()->json(['success' => true, 'comments' => $comments]);
    }

    private function notifyGroupContributors(LocalityGroup $group, LocalityGroupComment $comment): void
    {
        $currentUserId = auth()->id();

        $suggesterIds = GeorefSuggestion::where('locality_group_id', $group->id)
            ->pluck('user_id');

        $validatorIds = GeorefValidation::whereIn(
            'suggestion_id',
            GeorefSuggestion::where('locality_group_id', $group->id)->pluck('id')
        )->pluck('user_id');

        $commenterIds = LocalityGroupComment::where('locality_group_id', $group->id)
            ->pluck('user_id');

        $recipientIds = $suggesterIds
            ->merge($validatorIds)
            ->merge($commenterIds)
            ->unique()
            ->filter(fn($id) => $id && $id !== $currentUserId);

        User::whereIn('id', $recipientIds)
            ->where('email_notifications', true)
            ->get()
            ->each(fn($user) => Mail::to($user->email)->queue(new CommentNotification($comment, $group)));
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

    // Detect country name at end of query (e.g. "Pombal, Portugal") and convert to ISO2
    $countryCode = null;
    $searchQ = $q;
    $countryMap = $this->countryNameToIso2();
    foreach ($countryMap as $name => $iso2) {
        if (preg_match('/,?\s*' . preg_quote($name, '/') . '\s*$/i', $q)) {
            $countryCode = $iso2;
            $searchQ = trim(preg_replace('/,?\s*' . preg_quote($name, '/') . '\s*$/i', '', $q));
            break;
        }
    }

    $query = LocalityGroup::where('occurrence_count', '>', 0)
        ->where(function ($sub) {
            $sub->where('ungeoreferenced_count', '>', 0)
                ->orWhere('pending_count', '>', 0);
        });

    if ($countryCode) {
        $query->where('country_code', $countryCode);
    }

    if (strlen($searchQ) >= 2) {
        $query->whereRaw('MATCH(locality_string) AGAINST(? IN BOOLEAN MODE)', [$searchQ])
              ->orderByRaw('MATCH(locality_string) AGAINST(? IN BOOLEAN MODE) DESC', [$searchQ]);
    } else {
        $query->orderBy('occurrence_count', 'desc');
    }

    $results = $query->limit(8)
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

private function countryNameToIso2(): array
{
    return [
        'Afghanistan' => 'AF', 'Albania' => 'AL', 'Algeria' => 'DZ', 'Angola' => 'AO',
        'Argentina' => 'AR', 'Australia' => 'AU', 'Austria' => 'AT', 'Belgium' => 'BE',
        'Bolivia' => 'BO', 'Brazil' => 'BR', 'Brasil' => 'BR', 'Bulgaria' => 'BG',
        'Canada' => 'CA', 'Chile' => 'CL', 'China' => 'CN', 'Colombia' => 'CO',
        'Costa Rica' => 'CR', 'Croatia' => 'HR', 'Cuba' => 'CU', 'Czech Republic' => 'CZ',
        'Czechia' => 'CZ', 'Denmark' => 'DK', 'Ecuador' => 'EC', 'Egypt' => 'EG',
        'Ethiopia' => 'ET', 'Finland' => 'FI', 'France' => 'FR', 'Germany' => 'DE',
        'Ghana' => 'GH', 'Greece' => 'GR', 'Guatemala' => 'GT', 'Honduras' => 'HN',
        'Hungary' => 'HU', 'India' => 'IN', 'Indonesia' => 'ID', 'Iran' => 'IR',
        'Iraq' => 'IQ', 'Ireland' => 'IE', 'Israel' => 'IL', 'Italy' => 'IT',
        'Japan' => 'JP', 'Kenya' => 'KE', 'Madagascar' => 'MG', 'Malaysia' => 'MY',
        'Mexico' => 'MX', 'Morocco' => 'MA', 'Mozambique' => 'MZ', 'Netherlands' => 'NL',
        'New Zealand' => 'NZ', 'Nicaragua' => 'NI', 'Nigeria' => 'NG', 'Norway' => 'NO',
        'Pakistan' => 'PK', 'Panama' => 'PA', 'Paraguay' => 'PY', 'Peru' => 'PE',
        'Philippines' => 'PH', 'Poland' => 'PL', 'Portugal' => 'PT', 'Romania' => 'RO',
        'Russia' => 'RU', 'Saudi Arabia' => 'SA', 'Senegal' => 'SN', 'Slovakia' => 'SK',
        'Slovenia' => 'SI', 'South Africa' => 'ZA', 'Spain' => 'ES', 'Sweden' => 'SE',
        'Switzerland' => 'CH', 'Taiwan' => 'TW', 'Tanzania' => 'TZ', 'Thailand' => 'TH',
        'Turkey' => 'TR', 'Uganda' => 'UG', 'Ukraine' => 'UA', 'United Kingdom' => 'GB',
        'United States' => 'US', 'USA' => 'US', 'Uruguay' => 'UY', 'Venezuela' => 'VE',
        'Vietnam' => 'VN', 'Zimbabwe' => 'ZW',
    ];
}

    public function iiifProxy(Request $request): \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
    {
        $url = $request->get('url');
        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            return response()->json(['error' => 'Invalid URL'], 400);
        }
        if (!str_starts_with($url, 'https://')) {
            return response()->json(['error' => 'HTTPS only'], 400);
        }
        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_HTTPHEADER     => ['Accept: application/json, application/ld+json'],
                CURLOPT_USERAGENT      => 'georeference.it/1.0',
            ]);
            $body   = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if (!$body || $status >= 400) {
                return response()->json(['error' => 'Upstream error'], 502);
            }
            return response($body, 200)->header('Content-Type', 'application/json')
                                       ->header('Access-Control-Allow-Origin', '*');
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Proxy error'], 502);
        }
    }

    private function applyVote(GeorefSuggestion $suggestion, $user, string $vote, bool $logActivity = true): void
    {
        $weight = $user->getVoteWeight();

        GeorefValidation::create([
            'suggestion_id'  => $suggestion->id,
            'user_id'        => $user->id,
            'vote'           => $vote,
            'points_awarded' => $vote === 'agree' ? $weight : -$weight,
        ]);

        // Log validation — skip auto-agree on own submission (suggestion owner == voter)
        if ($logActivity && $suggestion->user_id !== $user->id) {
            $group = $suggestion->localityGroup;
            $locationLabel = $group ? trim(implode(', ', array_filter([$group->verbatim_locality, $group->municipality, $group->county]))) : null;
            DB::table('activity_log')->insert([
                'type'             => 'validation_' . $vote,
                'source'           => 'user',
                'user_id'          => $user->id,
                'locality_group_id'=> $suggestion->locality_group_id,
                'occ_count'        => 1,
                'lat'              => $suggestion->decimal_latitude,
                'lng'              => $suggestion->decimal_longitude,
                'uncertainty_m'       => $suggestion->coordinate_uncertainty_m,
                'suggestion_user_id'  => $suggestion->user_id,
                'suggestion_source'   => $suggestion->user_id !== null ? 'user'
                    : ($suggestion->georeference_sources === 'GBIF_CONSISTENCY_CHECK' ? 'system' : 'anonymous'),
                'country_code'        => $group?->country_code,
                'location_label'     => $locationLabel ?: null,
                'created_at'         => now(),
            ]);
        }

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

        $suggestion->localityGroup->recalculateCounters();
    }

    private function validateSuggestion(GeorefSuggestion $suggestion): void
    {
        $suggestion->update(['status' => 'validated']);

        $excludedIds = $suggestion->exclusions()->pluck('occurrence_id')->toArray();

        $suggestion->localityGroup->occurrences()
            ->whereNotIn('id', $excludedIds)
            ->update(['georef_status' => 'validated']);

        // For consistency-check suggestions, mark the excluded (losing-cluster)
        // occurrences as validated too — the correct coordinates are now known
        // for this locality; discrepancies with GBIF are the publisher's concern.
        if ($suggestion->georeference_sources === 'GBIF_CONSISTENCY_CHECK' && !empty($excludedIds)) {
            $suggestion->localityGroup->occurrences()
                ->whereIn('id', $excludedIds)
                ->update(['georef_status' => 'validated']);

            $suggestion->localityGroup->update(['consistency_status' => 'resolved']);
        }

        $suggestion->localityGroup->recalculateCounters();

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

public function dismissSystemSuggestions(Request $request, \App\Models\LocalityGroup $group): \Illuminate\Http\JsonResponse
{
    // Only dismiss if ALL pending suggestions are system-generated (user_id IS NULL)
    $pendingHuman = $group->suggestions()
        ->where('status', 'pending')
        ->whereNotNull('user_id')
        ->count();

    if ($pendingHuman > 0) {
        return response()->json(['error' => 'Human suggestions exist — cannot dismiss.'], 422);
    }

    // Reject all pending system suggestions
    $group->suggestions()
        ->where('status', 'pending')
        ->whereNull('user_id')
        ->update(['status' => 'rejected']);

    // Mark occurrences as gbif_reviewed (coordinates confirmed as-is)
    $group->occurrences()
        ->where('georef_status', 'gbif_georeferenced')
        ->update(['georef_status' => 'gbif_reviewed']);

    // Update group counters
    $group->pending_count = 0;
    $group->save();

    return response()->json(['success' => true]);
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

    if ($group) {
        $remainingPending = $group->suggestions()->where('status', 'pending')->count();
        // If no pending suggestions remain, revert has_suggestion occurrences to ungeoreferenced
        if ($remainingPending === 0) {
            $group->occurrences()
                ->where('georef_status', 'has_suggestion')
                ->update(['georef_status' => 'ungeoreferenced']);
        }
        $group->pending_count   = $remainingPending;
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