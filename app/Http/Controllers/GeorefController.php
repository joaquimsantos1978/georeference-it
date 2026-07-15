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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class GeorefController extends Controller
{
    public function index()
    {
        return view('georef.index');
    }

    // Kept identical to Api\OccurrenceController::GEOREFERENCE_PROTOCOL so the API's
    // georeferenceProtocol field is consistent regardless of who/what submitted it.
    private const GEOREFERENCE_PROTOCOL = 'Georeferencing Quick Reference Guide (Zermoglio et al. 2020, https://doi.org/10.35035/e09p-h128)';

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

        $notGeoreferenceableCount = Occurrence::where('locality_group_id', $group->id)
            ->where('georef_status', 'not_georeferenceable')
            ->count();

        $comments = LocalityGroupComment::where('locality_group_id', $group->id)
            ->with('user')->latest()->take(20)->get()
            ->map(fn($c) => [
                'user_name'  => $c->user->public_name ? $c->user->name : 'Hidden contributor',
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

        // Similar locations with an existing coordinate (validated or pending) go first —
        // those are what a user checks against; scrolling past a long tail of fully
        // ungeoreferenced siblings to find them was the actual complaint.
        usort($similarGroups, fn($a, $b) =>
            (($b['validated_count'] > 0 || $b['pending_count'] > 0) ? 1 : 0)
            <=> (($a['validated_count'] > 0 || $a['pending_count'] > 0) ? 1 : 0)
        );

        return [
            'group'               => $group,
            'occurrences'         => $ungeorefOccurrences,
            'ungeoref_total'      => $ungeorefTotal,
            'georef_occurrences'  => $georefOccurrences,
            'not_georeferenceable_count' => $notGeoreferenceableCount,
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
            ->where('occurrence_count', '>', 0)
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
    // 1. focus text match, 2. last served county, 3. last served province, 4. country, 5. any
    $lastProvince = session('georef_last_province');
    $lastCounty   = session('georef_last_county');

    // If no session location but we have the excluded group, seed from it
    if ($focus === '' && !$lastCounty && !$lastProvince && $excludeId) {
        $ref = LocalityGroup::select('state_province', 'county', 'country_code')
            ->find($excludeId);
        if ($ref) {
            $lastCounty   = $ref->county;
            $lastProvince = $ref->state_province;
            if (!$country) $country = $ref->country_code ?: null;
        }
    }

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

    // Hides groups whose verbatim_locality is obviously corrupted by the original import's
    // parsing bug (a stray quote desynced column boundaries, so several unrelated DWC
    // fields — collector name, event date, catalog number, scientific name — got
    // concatenated into one locality string), or whose country_code got truncated by a
    // separate LOAD DATA escaping bug (both fixed at import time; existing rows need a
    // full reprocessing pass — see RefreshImpactCounts::compute() for the exact
    // detection signatures). That detection needs a REGEXP with no index to use, which
    // made every candidate-selection query below pay for a full row fetch per candidate
    // instead of an index-only scan — a single country-scoped request was observed
    // taking 10-35s from this alone. RefreshImpactCounts recomputes the id list hourly
    // and caches it forever; excluding by id here is a plain indexed lookup instead.
    $corruptedIds = Cache::get('georef:corrupted_group_ids', []);
    $excludeCorrupted = fn($q) => $q->whereNotIn('id', $corruptedIds);

    $group  = null;
    $userId = auth()->check() ? auth()->id() : null;

    // A group with pending_count > 0 already has someone else's suggestion sitting on
    // it — resolving that requires voting, which an anonymous visitor can't do, so it
    // reads as "why is there already an answer here?" on what's supposed to be their
    // first plain georef task. A group with a "similar locality" sibling (same
    // normalized_locality + county + country_code) invites a comparison an unregistered
    // first-timer has no context for yet. Neither disqualifies a group outright — try
    // every scope (country → wider fallbacks) with both restrictions first, and only
    // relax them (second pass, identical scopes) if that comes up completely empty,
    // rather than dropping a restriction while there's still unexplored geography.
    $strictPasses = $userId === null ? [true, false] : [false];

    // Same signature as groupData()'s "similar groups" query, but existence-only and
    // capped to the (at most 50) candidates already fetched — the composite index on
    // (normalized_locality, county, country_code) makes each check a fast index lookup.
    $hasSimilarSibling = fn(LocalityGroup $g) => $g->normalized_locality && LocalityGroup::where('id', '!=', $g->id)
        ->where('normalized_locality', $g->normalized_locality)
        ->where('county', $g->county)
        ->where('country_code', $g->country_code)
        ->exists();

    foreach ($strictPasses as $strict) {
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
                ->where('occurrence_count', '>', 0)
                ->where('occurrence_count', '<', 10000)
                ->tap($focusMatch)
                ->tap($excludeCorrupted)
                ->when($seenIds, fn($q) => $q->whereNotIn('id', $seenIds))
                ->limit(50)
                ->get();

            if ($candidates->isEmpty()) {
                $candidates = LocalityGroup::where('pending_count', '>', 0)
                    ->where('occurrence_count', '>', 0)
                    ->where('occurrence_count', '<', 10000)
                    ->tap($focusMatch)
                    ->tap($excludeCorrupted)
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
                    ->where('occurrence_count', '>', 0)
                    ->where('occurrence_count', '<', 10000)
                    ->tap($scope)
                    ->tap($excludeCorrupted)
                    ->when($seenIds, fn($q) => $q->whereNotIn('id', $seenIds))
                    ->when($strict, fn($q) => $q->where('pending_count', 0))
                    ->orderByDesc('occurrence_count')
                    ->limit(50)
                    ->get();
                if ($strict) {
                    $georefCandidates = $georefCandidates->reject($hasSimilarSibling);
                }
                $group = $georefCandidates->isNotEmpty() ? $georefCandidates->random() : null;
            }

            if (!$group && $wantsValidate) {
                $validateCandidates = LocalityGroup::where('pending_count', '>', 0)
                    ->where('occurrence_count', '>', 0)
                    ->tap($scope)
                    ->tap($excludeCorrupted)
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
        session([
            'georef_last_province' => $group->state_province,
            'georef_last_county'   => $group->county,
        ]);
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

    // For localities with no usable info at all ("in the woods", no place name or even
    // country) — applies to the whole group (not a single occurrence), keeping every
    // ungeoreferenced/has_suggestion occurrence in it from being served up as work again
    // to this or any other user, without requiring a real (impossible) coordinate.
    public function markGroupNotGeoreferenceable(Request $request, int $groupId): \Illuminate\Http\JsonResponse
    {
        session()->save();

        $group = LocalityGroup::findOrFail($groupId);

        Occurrence::where('locality_group_id', $group->id)
            ->whereIn('georef_status', ['ungeoreferenced', 'has_suggestion'])
            ->update([
                'georef_status' => 'not_georeferenceable',
                'not_georeferenceable_by' => auth()->id(),
                'not_georeferenceable_at' => now(),
            ]);

        $group->recalculateCounters();

        return response()->json(['success' => true]);
    }

    // Reversible by design — anyone (including the original marker) can undo the mark
    // if they disagree, per the "keep it simple but reversible" decision (no multi-user
    // consensus required). Mirrors markGroupNotGeoreferenceable: acts on the whole group.
    public function unmarkGroupNotGeoreferenceable(Request $request, int $groupId): \Illuminate\Http\JsonResponse
    {
        session()->save();

        $group = LocalityGroup::findOrFail($groupId);

        Occurrence::where('locality_group_id', $group->id)
            ->where('georef_status', 'not_georeferenceable')
            ->update([
                'georef_status' => 'ungeoreferenced',
                'not_georeferenceable_by' => null,
                'not_georeferenceable_at' => null,
            ]);

        $group->recalculateCounters();

        return response()->json(['success' => true]);
    }

    // Used by the "see list" link on already-georeferenced matches, which point to a
    // group that's typically fully georeferenced already — groupUngeorefOccurrences()
    // would return nothing there since there's nothing left ungeoreferenced to show.
    public function groupAllOccurrences(Request $request, int $groupId)
    {
        session()->save();
        $group = LocalityGroup::findOrFail($groupId);
        $offset = max(0, (int) $request->get('offset', 0));
        $occurrences = Occurrence::where('locality_group_id', $group->id)
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

    public function suggestionVotes(GeorefSuggestion $suggestion): \Illuminate\Http\JsonResponse
    {
        $votes = $suggestion->validations()->with('user')->get()->map(fn($v) => [
            'vote'   => $v->vote,
            'name'   => $v->user ? ($v->user->public_name ? $v->user->name : 'Hidden contributor') : 'Anonymous',
            'points' => $v->points_awarded,
        ]);

        return response()->json([
            'agree'    => $votes->where('vote', 'agree')->values(),
            'disagree' => $votes->where('vote', 'disagree')->values(),
        ]);
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

        // Replace the submitter's previous pending suggestion for this group — for authenticated
        // users, match by user_id; for anonymous users (no account), match by session, otherwise
        // the same visitor could pile up unlimited pending suggestions on one group.
        if (auth()->check()) {
            GeorefSuggestion::where('locality_group_id', $group->id)
                ->where('user_id', auth()->id())
                ->where('status', 'pending')
                ->delete();
        } else {
            GeorefSuggestion::where('locality_group_id', $group->id)
                ->whereNull('user_id')
                ->where('session_id', session()->getId())
                ->where('status', 'pending')
                ->delete();
        }

        // If another pending suggestion already has the same coordinates, vote Agree on it instead
        $existing = GeorefSuggestion::where('locality_group_id', $group->id)
            ->where('status', 'pending')
            ->whereRaw('ABS(decimal_latitude  - ?) < 0.0001', [$validated['decimal_latitude']])
            ->whereRaw('ABS(decimal_longitude - ?) < 0.0001', [$validated['decimal_longitude']])
            ->first();

        if ($existing) {
            if (auth()->check() && !$existing->validations()->where('user_id', auth()->id())->exists()) {
                $this->applyVote($existing, auth()->user(), 'agree', true);
            }
            $group->recalculateCounters();
            return response()->json(['success' => true, 'suggestion_id' => $existing->id]);
        }

        $suggestion = GeorefSuggestion::create([
            'locality_group_id'        => $group->id,
            'locality_group_hash'      => $group->group_hash,
            'occurrence_id'            => $group->occurrences()->first()->id,
            'user_id'                  => auth()->id(),
            'anon_name'                => $validated['anon_name'] ?? null,
            'session_id'               => auth()->check() ? null : session()->getId(),
            'decimal_latitude'         => $validated['decimal_latitude'],
            'decimal_longitude'        => $validated['decimal_longitude'],
            'geodetic_datum'           => 'epsg:4326',
            'coordinate_uncertainty_m' => $validated['coordinate_uncertainty_m'] ?? null,
            'georeference_remarks'     => $validated['georeference_remarks'] ?? null,
            'georeference_protocol'    => self::GEOREFERENCE_PROTOCOL,
            'georeference_sources'     => 'georeference.it',
            'status'                   => 'pending',
            'total_points'             => 0,
            'georeferenced_date'       => now(),
        ]);

        // Create the creator's agree validation first so exclusions can reference it
        $creatorValidation = auth()->check()
            ? $this->applyVote($suggestion, auth()->user(), 'agree')
            : null;

        if (!empty($validated['excluded_occurrence_ids'])) {
            $weight = $creatorValidation ? auth()->user()->getVoteWeight() : 1;
            foreach ($validated['excluded_occurrence_ids'] as $occurrenceId) {
                $suggestion->exclusions()->create([
                    'occurrence_id' => $occurrenceId,
                    'validation_id' => $creatorValidation?->id,
                    'weight'        => $weight,
                ]);
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
                // If the group already has a pending suggestion with the same coordinates, vote Agree on it
                $existing = GeorefSuggestion::where('locality_group_id', $simGroup->id)
                    ->where('status', 'pending')
                    ->whereRaw('ABS(decimal_latitude  - ?) < 0.0001', [$validated['decimal_latitude']])
                    ->whereRaw('ABS(decimal_longitude - ?) < 0.0001', [$validated['decimal_longitude']])
                    ->first();

                if ($existing) {
                    if (auth()->check() && !$existing->validations()->where('user_id', auth()->id())->exists()) {
                        $this->applyVote($existing, auth()->user(), 'agree', true);
                    }
                    $simGroup->recalculateCounters();
                    continue;
                }

                // Replace the submitter's previous pending suggestion in this similar group first,
                // same as the main group above — otherwise repeated submissions pile up unbounded.
                if (auth()->check()) {
                    GeorefSuggestion::where('locality_group_id', $simGroup->id)
                        ->where('user_id', auth()->id())
                        ->where('status', 'pending')
                        ->delete();
                } else {
                    GeorefSuggestion::where('locality_group_id', $simGroup->id)
                        ->whereNull('user_id')
                        ->where('session_id', session()->getId())
                        ->where('status', 'pending')
                        ->delete();
                }

                // No existing suggestion — create one
                $simSuggestion = GeorefSuggestion::create([
                    'locality_group_id'        => $simGroup->id,
                    'locality_group_hash'      => $simGroup->group_hash,
                    'occurrence_id'            => $simGroup->occurrences()->first()?->id,
                    'user_id'                  => auth()->id(),
                    'anon_name'                => $validated['anon_name'] ?? null,
                    'session_id'               => auth()->check() ? null : session()->getId(),
                    'decimal_latitude'         => $validated['decimal_latitude'],
                    'decimal_longitude'        => $validated['decimal_longitude'],
                    'geodetic_datum'           => 'epsg:4326',
                    'coordinate_uncertainty_m' => $validated['coordinate_uncertainty_m'] ?? null,
                    'georeference_remarks'     => $validated['georeference_remarks'] ?? null,
                    'georeference_protocol'    => self::GEOREFERENCE_PROTOCOL,
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

    // Propagate an agreed suggestion's coordinates to similar groups only (no suggestion created for the main group)
    public function propagateSimilar(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Login required'], 401);
        }

        $validated = $request->validate([
            'decimal_latitude'         => 'required|numeric|between:-90,90',
            'decimal_longitude'        => 'required|numeric|between:-180,180',
            'coordinate_uncertainty_m' => 'nullable|integer|min:1',
            'georeference_remarks'     => 'nullable|string|max:1000',
            'similar_group_ids'        => 'required|array|min:1',
            'similar_group_ids.*'      => 'integer|exists:locality_groups,id',
        ]);

        $similarGroups = LocalityGroup::whereIn('id', $validated['similar_group_ids'])->get();

        foreach ($similarGroups as $simGroup) {
            $existing = GeorefSuggestion::where('locality_group_id', $simGroup->id)
                ->where('status', 'pending')
                ->whereRaw('ABS(decimal_latitude  - ?) < 0.0001', [$validated['decimal_latitude']])
                ->whereRaw('ABS(decimal_longitude - ?) < 0.0001', [$validated['decimal_longitude']])
                ->first();

            if ($existing) {
                if (!$existing->validations()->where('user_id', auth()->id())->exists()) {
                    $this->applyVote($existing, auth()->user(), 'agree', true);
                }
                // Disagree on all other pending suggestions in this group
                GeorefSuggestion::where('locality_group_id', $simGroup->id)
                    ->where('status', 'pending')
                    ->where('id', '!=', $existing->id)
                    ->get()
                    ->each(function ($other) {
                        if (!$other->validations()->where('user_id', auth()->id())->exists()) {
                            $this->applyVote($other, auth()->user(), 'disagree', true);
                        }
                    });
                $simGroup->recalculateCounters();
                continue;
            }

            GeorefSuggestion::where('locality_group_id', $simGroup->id)
                ->where('user_id', auth()->id())
                ->where('status', 'pending')
                ->delete();

            $simSuggestion = GeorefSuggestion::create([
                'locality_group_id'        => $simGroup->id,
                'locality_group_hash'      => $simGroup->group_hash,
                'occurrence_id'            => $simGroup->occurrences()->first()?->id,
                'user_id'                  => auth()->id(),
                'decimal_latitude'         => $validated['decimal_latitude'],
                'decimal_longitude'        => $validated['decimal_longitude'],
                'geodetic_datum'           => 'epsg:4326',
                'coordinate_uncertainty_m' => $validated['coordinate_uncertainty_m'] ?? null,
                'georeference_remarks'     => $validated['georeference_remarks'] ?? null,
                'georeference_protocol'    => self::GEOREFERENCE_PROTOCOL,
                'georeference_sources'     => 'georeference.it',
                'status'                   => 'pending',
                'total_points'             => 0,
                'georeferenced_date'       => now(),
            ]);

            $this->applyVote($simSuggestion, auth()->user(), 'agree', false);

            // Disagree on all other existing pending suggestions in this group
            GeorefSuggestion::where('locality_group_id', $simGroup->id)
                ->where('status', 'pending')
                ->where('id', '!=', $simSuggestion->id)
                ->get()
                ->each(function ($other) {
                    if (!$other->validations()->where('user_id', auth()->id())->exists()) {
                        $this->applyVote($other, auth()->user(), 'disagree', true);
                    }
                });

            $simGroup->occurrences()
                ->whereIn('georef_status', ['ungeoreferenced', 'has_suggestion'])
                ->update(['georef_status' => 'has_suggestion']);

            $simGroup->recalculateCounters();
        }

        return response()->json(['success' => true]);

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

        $user = auth()->user();
        $validation = $this->applyVote($suggestion, $user, $validated['vote']);

        if ($validated['vote'] === 'agree' && !empty($validated['excluded_occurrence_ids'])) {
            $existing = $suggestion->exclusions()->pluck('occurrence_id')->all();
            $weight = $user->getVoteWeight();
            foreach ($validated['excluded_occurrence_ids'] as $occId) {
                if (!in_array($occId, $existing)) {
                    $suggestion->exclusions()->create([
                        'occurrence_id' => $occId,
                        'validation_id' => $validation->id,
                        'weight'        => $weight,
                    ]);
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

        $validation = $this->applyVote($suggestion, $user, 'agree');

        if (!empty($validated['excluded_occurrence_ids'])) {
            $existing = $suggestion->exclusions()->pluck('occurrence_id')->all();
            $weight = $user->getVoteWeight();
            foreach ($validated['excluded_occurrence_ids'] as $occId) {
                if (!in_array($occId, $existing)) {
                    $suggestion->exclusions()->create([
                        'occurrence_id' => $occId,
                        'validation_id' => $validation->id,
                        'weight'        => $weight,
                    ]);
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
                'user_name'  => $c->user->public_name ? $c->user->name : 'Hidden contributor',
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

// Suggestions for the "Focus area" input — administrative areas only (municipality,
// county, state/province), not individual locality descriptions. The old
// searchLocality() endpoint ran a fulltext MATCH over locality_groups on every
// keystroke, which got slow/blocked during heavy writes to that same table (e.g. the
// monthly GBIF import). This filters an in-memory list built once (cached a day),
// so typing never touches the database.
public function searchFocusAreas(Request $request): \Illuminate\Http\JsonResponse
{
    $q = trim(mb_strtolower($request->get('q', '')));
    if (strlen($q) < 2) {
        return response()->json([]);
    }

    $areas = $this->rememberWithLock('georef:focus-areas', now()->addDay(), function () {
        $rows = LocalityGroup::where('occurrence_count', '>', 0)
            ->whereNull('deleted_at')
            ->where(function ($sub) {
                $sub->where('ungeoreferenced_count', '>', 0)
                    ->orWhere('pending_count', '>', 0);
            })
            ->selectRaw('municipality, county, state_province, country_code, SUM(occurrence_count) as occ')
            ->whereRaw("(municipality != '' OR county != '' OR state_province != '')")
            ->groupBy('municipality', 'county', 'state_province', 'country_code')
            ->get();

        $areas = [];
        foreach ($rows as $r) {
            foreach (['municipality', 'county', 'state_province'] as $field) {
                $name = trim((string) $r->$field);
                if ($name === '') continue;
                $key = mb_strtolower($name) . '|' . $r->country_code;
                $areas[$key]['label']   = implode(', ', array_filter([$name, $r->country_code]));
                $areas[$key]['name']    = $name;
                $areas[$key]['country'] = $r->country_code;
                $areas[$key]['occ']     = ($areas[$key]['occ'] ?? 0) + $r->occ;
            }
        }

        return array_values($areas);
    }, []);

    $matches = array_filter($areas, fn($a) => str_contains(mb_strtolower($a['name']), $q));
    usort($matches, fn($a, $b) => $b['occ'] <=> $a['occ']);

    return response()->json(array_slice(array_values($matches), 0, 8));
}

// Cache::remember() alone recomputes inline whenever the TTL expires — fine for a cheap
// query, but locality_groups grew to tens of millions of rows, and this GROUP BY was
// observed taking 280+ seconds. Two concurrent focus-input keystrokes hitting the same
// expired cache both ran it in full, in parallel — the same stampede pattern fixed for
// Impact/Stats earlier, just discovered here later once the table grew.
private function rememberWithLock(string $key, \Illuminate\Support\Carbon $ttl, \Closure $compute, $emptyFallback)
{
    $dataKey = $key . ':data';
    $cached  = Cache::get($dataKey);

    if ($cached === null) {
        $lock = Cache::lock($key . ':lock', 600);
        if ($lock->get()) {
            try {
                $cached = $compute();
                Cache::put($dataKey, $cached, $ttl);
            } finally {
                $lock->release();
            }
        } else {
            try {
                $lock->block(10);
                $lock->release();
            } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
                // Still running after 10s — fall through to the safe default below.
            }
            $cached = Cache::get($dataKey);
        }
    }

    return $cached ?? $emptyFallback;
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

// Search already-georeferenced locality groups by free text, for use alongside Nominatim
// results when finding coordinates for a not-yet-georeferenced locality. Full-text only —
// can't compare against the current group's centroid because it has no coordinates yet.
public function searchGeoreferencedLocalities(Request $request): \Illuminate\Http\JsonResponse
{
    $q = trim($request->get('q', ''));
    $excludeGroupId = $request->integer('exclude_group_id') ?: null;
    $offset = max(0, $request->integer('offset', 0));
    $perPage = 8;

    if (strlen($q) < 3) {
        return response()->json(['results' => [], 'has_more' => false]);
    }

    // Strip all punctuation, not just MySQL's boolean-mode operator characters. A
    // leftover ":" or "," glued to a word (e.g. "PT:") makes a "+PT:*" mandatory term
    // that can never match anything — the index never stores punctuation — which
    // silently zeroes out the AND search and falls back to a much looser OR match.
    $ftQuery = trim(preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $q));
    $ftQuery = trim(preg_replace('/\s+/', ' ', $ftQuery));
    if ($ftQuery === '') {
        return response()->json(['results' => [], 'has_more' => false]);
    }

    // Pool size grows with the requested offset so "load more" can keep going deeper
    // into the relevance ranking instead of being capped at a fixed pool.
    $poolSize = max(40, $offset + $perPage + 10);

    // Short-lived cache (not the 1h it had before). Removing it entirely turned out to
    // cost 300+ seconds per query in production — the whereHas EXISTS filters combined
    // with fulltext relevance ordering are expensive over the full corpus. A 60s window
    // is short enough that a locality georeferenced moments ago still shows up almost
    // immediately, while absorbing repeat hits on the same term.
    $cacheKey = 'georef:search-geo-loc:' . md5(mb_strtolower($ftQuery) . ':' . $poolSize);
    $candidates = \Illuminate\Support\Facades\Cache::remember($cacheKey, 60, function () use ($ftQuery, $poolSize) {
        $fetchGroups = function (string $booleanQuery) use ($poolSize) {
            return LocalityGroup::query()
                ->where(function ($sub) {
                    $sub->whereHas('suggestions', fn($s) => $s->where('status', '!=', 'rejected'))
                        ->orWhereHas('occurrences', fn($o) => $o->where('georef_status', 'gbif_georeferenced'));
                })
                ->whereRaw('MATCH(locality_string) AGAINST(? IN BOOLEAN MODE)', [$booleanQuery])
                ->orderByRaw('MATCH(locality_string) AGAINST(? IN BOOLEAN MODE) DESC', [$booleanQuery])
                ->limit($poolSize)
                ->get(['id', 'verbatim_locality', 'municipality', 'county', 'state_province', 'country_code', 'occurrence_count']);
        };

        // Require every significant word to match (AND), so a query with several
        // distinctive words isn't drowned out by a single common one (e.g. "Coimbra").
        // Only fall back to a looser OR search when there's a single token — with 2+
        // tokens, OR degrades into "any word matches", which surfaced the same generic,
        // unrelated top-relevance rows for every failed search instead of no results.
        $tokens = array_filter(preg_split('/\s+/', $ftQuery), fn($t) => mb_strlen($t) >= 3);
        $andQuery = implode(' ', array_map(fn($t) => '+' . $t . '*', $tokens));

        $groups = $andQuery !== '' ? $fetchGroups($andQuery) : collect();
        if ($groups->isEmpty() && count($tokens) <= 1) {
            $groups = $fetchGroups($ftQuery);
        }

        if ($groups->isEmpty()) {
            return [];
        }

        // Pick one representative suggestion per group — no averaging across suggestions,
        // since a group can genuinely hold several distinct real-world coordinates (that's
        // exactly what "inconsistent" groups are). Prefer validated, then highest vote total.
        $suggestionsByGroup = GeorefSuggestion::whereIn('locality_group_id', $groups->pluck('id'))
            ->where('status', '!=', 'rejected')
            ->get(['locality_group_id', 'decimal_latitude', 'decimal_longitude', 'coordinate_uncertainty_m', 'status', 'total_points', 'georeference_remarks'])
            ->groupBy('locality_group_id');

        $bestSuggestion = $suggestionsByGroup->map(function ($rows) {
            return $rows->sortByDesc(fn($r) => [$r->status === 'validated' ? 1 : 0, $r->total_points])->first();
        });
        $suggestionCounts = $suggestionsByGroup->map(fn($rows) => [
            'suggestion_count' => $rows->count(),
            'validated_count'  => $rows->where('status', 'validated')->count(),
        ]);

        // Fallback for groups with no suggestion at all (fully GBIF-georeferenced groups
        // never get an auto-suggestion — see GbifService::createAutoSuggestions). Group the
        // raw occurrence coordinates by *exact* match and take the largest cluster, same
        // logic the "already georeferenced" occurrence list itself uses for display.
        $gbifOccByGroup = Occurrence::whereIn('locality_group_id', $groups->pluck('id'))
            ->where('georef_status', 'gbif_georeferenced')
            ->whereNotNull('gbif_decimal_latitude')->whereNotNull('gbif_decimal_longitude')
            ->get(['locality_group_id', 'gbif_decimal_latitude', 'gbif_decimal_longitude', 'gbif_coordinate_uncertainty_m'])
            ->groupBy('locality_group_id');

        $bestGbifCluster = $gbifOccByGroup->map(function ($rows) {
            return $rows->groupBy(fn($o) => round($o->gbif_decimal_latitude, 6) . ',' . round($o->gbif_decimal_longitude, 6))
                ->sortByDesc(fn($cluster) => $cluster->count())
                ->first();
        });

        return $groups->map(function ($g) use ($bestSuggestion, $suggestionCounts, $bestGbifCluster) {
            $displayName = implode(', ', array_filter([
                $g->verbatim_locality, $g->municipality, $g->county, $g->state_province, $g->country_code,
            ]));

            $s = $bestSuggestion->get($g->id);
            if ($s) {
                $counts = $suggestionCounts->get($g->id);
                return [
                    'source'           => 'occurrence',
                    'locality_group_id' => $g->id,
                    'display_name'     => $displayName,
                    'lat'              => (float) $s->decimal_latitude,
                    'lon'              => (float) $s->decimal_longitude,
                    'uncertainty_m'    => $s->coordinate_uncertainty_m ? round($s->coordinate_uncertainty_m) : null,
                    'occurrence_count' => $g->occurrence_count,
                    'suggestion_count' => $counts['suggestion_count'],
                    'validated_count'  => $counts['validated_count'],
                    'remarks'          => $s->georeference_remarks,
                ];
            }

            $cluster = $bestGbifCluster->get($g->id);
            if ($cluster) {
                return [
                    'source'           => 'gbif',
                    'locality_group_id' => $g->id,
                    'display_name'     => $displayName,
                    'lat'              => (float) $cluster->first()->gbif_decimal_latitude,
                    'lon'              => (float) $cluster->first()->gbif_decimal_longitude,
                    'uncertainty_m'    => $cluster->max('gbif_coordinate_uncertainty_m') ? round($cluster->max('gbif_coordinate_uncertainty_m')) : null,
                    'occurrence_count' => $g->occurrence_count,
                    'suggestion_count' => 0,
                    'validated_count'  => 0,
                ];
            }

            return null;
        })->filter()->values()->all();
    });

    $filtered = collect($candidates)
        ->reject(fn($r) => $excludeGroupId && $r['locality_group_id'] === $excludeGroupId)
        ->values();

    $page = $filtered->slice($offset, $perPage)->values();

    return response()->json([
        'results'  => $page,
        'has_more' => $offset + $perPage < $filtered->count(),
    ]);
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

    private function applyVote(GeorefSuggestion $suggestion, $user, string $vote, bool $logActivity = true): GeorefValidation
    {
        $weight = $user->getVoteWeight();

        $validation = GeorefValidation::create([
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

        return $validation;
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

        // Weighted majority exclusion: exclude occurrence if exclude_weight > total_agree_weight / 2
        $totalAgreeWeight = $suggestion->total_points; // already desnormalized
        $excludedIds = [];

        if ($totalAgreeWeight > 0) {
            $excludeWeights = $suggestion->exclusions()
                ->selectRaw('occurrence_id, SUM(weight) as exclude_weight')
                ->groupBy('occurrence_id')
                ->pluck('exclude_weight', 'occurrence_id')
                ->toArray();

            foreach ($excludeWeights as $occId => $excludeWeight) {
                if ($excludeWeight * 2 > $totalAgreeWeight) {
                    $excludedIds[] = $occId;
                }
            }
        }

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

    // Remove the orphaned activity feed entries this suggestion created (creation + any
    // agree/disagree votes referencing it) — there's no FK, so match on the fields
    // logged at creation time.
    DB::table('activity_log')
        ->where('locality_group_id', $suggestion->locality_group_id)
        ->where(function ($q) use ($suggestion) {
            $q->where('user_id', $suggestion->user_id)
              ->orWhere('suggestion_user_id', $suggestion->user_id);
        })
        ->where('lat', $suggestion->decimal_latitude)
        ->where('lng', $suggestion->decimal_longitude)
        ->delete();

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