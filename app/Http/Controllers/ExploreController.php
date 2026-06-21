<?php

namespace App\Http\Controllers;

use App\Models\LocalityGroup;
use Illuminate\Http\Request;

class ExploreController extends Controller
{
    public function index(Request $request)
    {
        // Block bot crawlers hitting absurd page numbers (causes full-table OFFSET scan)
        if ((int) $request->get('page', 1) > 5000) {
            abort(404);
        }

        $query = LocalityGroup::query()->where('occurrence_count', '>', 0);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->whereRaw(
                'MATCH(locality_string) AGAINST(? IN BOOLEAN MODE)',
                [$q]
            );
        }

        if ($request->filled('country')) {
            $query->where('country_code', strtoupper($request->country));
        }

        if ($request->filled('dataset_key')) {
            $query->whereIn('id', function ($sub) use ($request) {
                $sub->select('locality_group_id')
                    ->from('occurrences')
                    ->where('dataset_key', $request->dataset_key)
                    ->whereNotNull('locality_group_id');
            });
        }

        if ($request->filled('status')) {
            match ($request->status) {
                'ungeoreferenced' => $query->where('ungeoreferenced_count', '>', 0),
                'has_suggestion'  => $query->where('pending_count', '>', 0),
                'validated'       => $query->where('validated_count', '>', 0),
                'georeferenced'   => $query->whereRaw('occurrence_count > ungeoreferenced_count'),
                'inconsistent'    => $query->where('consistency_status', 'inconsistent'),
                default           => null,
            };
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $groups = $query
                ->orderByRaw(
                    'MATCH(locality_string) AGAINST(? IN BOOLEAN MODE) DESC',
                    [$q]
                )
                ->simplePaginate(50)
                ->withQueryString();
        } else {
            $groups = $query
                ->orderByDesc('occurrence_count')
                ->simplePaginate(50)
                ->withQueryString();
        }

        $countries = \Illuminate\Support\Facades\Cache::remember('explore_countries', 86400, function () {
            return \Illuminate\Support\Facades\DB::table('locality_groups')
                ->select('country_code')
                ->whereNotNull('country_code')
                ->where('occurrence_count', '>', 0)
                ->distinct()
                ->orderBy('country_code')
                ->pluck('country_code');
        });

        $datasets = \Illuminate\Support\Facades\Cache::remember('explore_datasets', 3600, function () {
            return \Illuminate\Support\Facades\DB::table('datasets')
                ->where('total', '>', 0)
                ->orderByDesc('total')
                ->get(['key', 'title', 'institution_code', 'collection_code']);
        });

        return view('explore', compact('groups', 'countries', 'datasets'));
    }
}
