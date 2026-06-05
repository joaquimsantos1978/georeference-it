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

    public function next(Request $request)
    {
        $country = $request->get('country');
        $preferredTask = auth()->check() ? auth()->user()->preferred_task : 'both';

        $group = null;

        // Priority: validate first (if user prefers validate or both)
        if (in_array($preferredTask, ['validate', 'both'])) {
            $group = LocalityGroup::where('pending_count', '>', 0)
                ->when($country, fn($q) => $q->where('country_code', $country))
                ->inRandomOrder()
                ->first();
        }

        // If no group to validate, find one to georeference
        if (!$group && in_array($preferredTask, ['georef', 'both'])) {
            $group = LocalityGroup::whereHas('occurrences', function ($q) {
                $q->whereIn('georef_status', ['ungeoreferenced']);
            })
            ->when($country, fn($q) => $q->where('country_code', $country))
            ->inRandomOrder()
            ->first();
        }

        if (!$group) {
            return response()->json(['group' => null]);
        }

        $occurrences = Occurrence::where('locality_group_id', $group->id)
            ->get(['id', 'gbif_occurrence_key', 'catalog_number', 'institution_code',
                   'collection_code', 'scientific_name', 'georef_status', 'media',
                   'gbif_decimal_latitude', 'gbif_decimal_longitude']);

        $suggestions = GeorefSuggestion::where('locality_group_id', $group->id)
            ->where('status', 'pending')
            ->with('user')
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'decimal_latitude' => $s->decimal_latitude,
                'decimal_longitude' => $s->decimal_longitude,
                'coordinate_uncertainty_m' => $s->coordinate_uncertainty_m,
                'total_points' => $s->total_points,
                'submitted_by' => $s->submitted_by,
                'georeference_remarks' => $s->georeference_remarks,
            ]);

        $comments = LocalityGroupComment::where('locality_group_id', $group->id)
            ->with('user')
            ->latest()
            ->take(20)
            ->get()
            ->map(fn($c) => [
                'user_name' => $c->user->name,
                'body' => $c->body,
                'created_at' => $c->created_at->diffForHumans(),
            ]);

        return response()->json([
            'group' => $group,
            'occurrences' => $occurrences,
            'suggestions' => $suggestions,
            'comments' => $comments,
        ]);
    }

    public function submit(Request $request)
    {
        $validated = $request->validate([
            'locality_group_id' => 'required|exists:locality_groups,id',
            'decimal_latitude' => 'required|numeric|between:-90,90',
            'decimal_longitude' => 'required|numeric|between:-180,180',
            'coordinate_uncertainty_m' => 'nullable|integer|min:1',
            'georeference_remarks' => 'nullable|string|max:1000',
            'anon_name' => 'nullable|string|max:255',
            'excluded_occurrence_ids' => 'nullable|array',
            'excluded_occurrence_ids.*' => 'integer|exists:occurrences,id',
        ]);

        $group = LocalityGroup::findOrFail($validated['locality_group_id']);

        $suggestion = GeorefSuggestion::create([
            'locality_group_id' => $group->id,
            'occurrence_id' => $group->occurrences()->first()->id,
            'user_id' => auth()->id(),
            'anon_name' => $validated['anon_name'] ?? null,
            'decimal_latitude' => $validated['decimal_latitude'],
            'decimal_longitude' => $validated['decimal_longitude'],
            'geodetic_datum' => 'epsg:4326',
            'coordinate_uncertainty_m' => $validated['coordinate_uncertainty_m'] ?? null,
            'georeference_remarks' => $validated['georeference_remarks'] ?? null,
            'georeference_protocol' => 'Georeferencing Quick Reference Guide (Zermoglio et al. 2020)',
            'georeference_sources' => 'georeference.it',
            'status' => 'pending',
            'total_points' => 0,
            'georeferenced_date' => now(),
        ]);

        // Record exclusions
        if (!empty($validated['excluded_occurrence_ids'])) {
            foreach ($validated['excluded_occurrence_ids'] as $occurrenceId) {
                $suggestion->exclusions()->create(['occurrence_id' => $occurrenceId]);
            }
        }

        // Update occurrence statuses
        $group->occurrences()
            ->whereNotIn('id', $validated['excluded_occurrence_ids'] ?? [])
            ->whereIn('georef_status', ['ungeoreferenced'])
            ->update(['georef_status' => 'has_suggestion']);

        $group->increment('pending_count');

        // Auto-validate if user has high vote weight
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

        // Prevent duplicate votes
        if ($suggestion->validations()->where('user_id', auth()->id())->exists()) {
            return response()->json(['success' => false, 'message' => 'Already voted']);
        }

        $this->applyVote($suggestion, auth()->user(), $validated['vote']);

        return response()->json(['success' => true]);
    }

    public function comment(Request $request)
    {
        $validated = $request->validate([
            'locality_group_id' => 'required|exists:locality_groups,id',
            'body' => 'required|string|max:1000',
        ]);

        LocalityGroupComment::create([
            'locality_group_id' => $validated['locality_group_id'],
            'user_id' => auth()->id(),
            'body' => $validated['body'],
        ]);

        $comments = LocalityGroupComment::where('locality_group_id', $validated['locality_group_id'])
            ->with('user')
            ->latest()
            ->take(20)
            ->get()
            ->map(fn($c) => [
                'user_name' => $c->user->name,
                'body' => $c->body,
                'created_at' => $c->created_at->diffForHumans(),
            ]);

        return response()->json(['success' => true, 'comments' => $comments]);
    }

    private function applyVote(GeorefSuggestion $suggestion, $user, string $vote): void
    {
        $weight = $user->getVoteWeight();

        GeorefValidation::create([
            'suggestion_id' => $suggestion->id,
            'user_id' => $user->id,
            'vote' => $vote,
            'points_awarded' => $vote === 'agree' ? $weight : 0,
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

        // Update occurrences in the group (except excluded ones)
        $excludedIds = $suggestion->exclusions()->pluck('occurrence_id')->toArray();

        $suggestion->localityGroup->occurrences()
            ->whereNotIn('id', $excludedIds)
            ->update(['georef_status' => 'validated']);

        $suggestion->localityGroup->decrement('pending_count');
        $suggestion->localityGroup->increment('validated_count');

        // Credit the submitter
        if ($suggestion->user_id) {
            $submitter = $suggestion->user;
            $submitter->increment('total_validated');
            $submitter->updateLevel();

            // Notify level up if level changed
            $submitter->refresh();
            if ($submitter->wasChanged('user_level_id')) {
                $submitter->notifications()->create([
                    'type' => 'level_up',
                    'data' => [
                        'message' => __('Congratulations! You reached level: ') . $submitter->userLevel->name,
                        'level' => $submitter->userLevel->name,
                    ],
                ]);
            }
        }
    }
}