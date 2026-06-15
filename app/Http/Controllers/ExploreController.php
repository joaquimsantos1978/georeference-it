<?php

namespace App\Http\Controllers;

use App\Models\LocalityGroup;
use Illuminate\Http\Request;

class ExploreController extends Controller
{
    public function index(Request $request)
    {
        $query = LocalityGroup::query()->where('occurrence_count', '>', 0);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->whereRaw(
                'MATCH(verbatim_locality, municipality, county, state_province, locality_string) AGAINST(? IN BOOLEAN MODE)',
                [$q]
            );
        }

        if ($request->filled('country')) {
            $query->where('country_code', strtoupper($request->country));
        }

        if ($request->filled('dataset_key')) {
            $groupIds = \Illuminate\Support\Facades\DB::table('occurrences')
                ->where('dataset_key', $request->dataset_key)
                ->whereNotNull('locality_group_id')
                ->distinct()
                ->pluck('locality_group_id');
            $query->whereIn('id', $groupIds);
        }

        if ($request->filled('status')) {
            match ($request->status) {
                'ungeoreferenced' => $query->whereHas('occurrences', fn($q) => $q->where('georef_status', 'ungeoreferenced')),
                'has_suggestion'  => $query->where('pending_count', '>', 0),
                'validated'       => $query->where('validated_count', '>', 0),
                'georeferenced'   => $query->whereHas('occurrences', fn($q) => $q->whereIn('georef_status', ['gbif_georeferenced', 'validated', 'gbif_reviewed'])),
                'inconsistent'    => $query->where('consistency_status', 'inconsistent'),
                default           => null,
            };
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $groups = $query
                ->orderByRaw(
                    'MATCH(verbatim_locality, municipality, county, state_province, locality_string) AGAINST(? IN BOOLEAN MODE) DESC',
                    [$q]
                )
                ->paginate(50)
                ->withQueryString();
        } else {
            $groups = $query
                ->orderByRaw('(pending_count + validated_count) DESC')
                ->orderByDesc('occurrence_count')
                ->paginate(50)
                ->withQueryString();
        }

        $countries = \Illuminate\Support\Facades\Cache::remember('explore_countries', 3600, function () {
            return LocalityGroup::selectRaw('country_code, COUNT(*) as c')
                ->where('occurrence_count', '>', 0)
                ->whereNotNull('country_code')
                ->groupBy('country_code')
                ->orderBy('country_code')
                ->pluck('country_code');
        });

        return view('explore', compact('groups', 'countries'));
    }
}
