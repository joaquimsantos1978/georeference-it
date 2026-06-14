<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DatasetApiController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->input('q', '');
        $country = $request->input('country', '');
        $institutionCode = $request->input('institution_code', '');

        $query = DB::table('datasets')
            ->select([
                'datasets.key as dataset_key',
                'datasets.title',
                'datasets.publisher_name',
                'datasets.institution_code',
                'datasets.collection_code',
                'datasets.total',
                'datasets.georeferenced',
                'datasets.validated',
                'datasets.ungeoreferenced',
            ])
            ->where('datasets.total', '>', 0)
            ->orderByDesc('datasets.total');

        if ($q) {
            $query->where(function ($sq) use ($q) {
                $sq->where('datasets.institution_code', 'like', "%{$q}%")
                   ->orWhere('datasets.collection_code', 'like', "%{$q}%")
                   ->orWhere('datasets.key', 'like', "%{$q}%")
                   ->orWhere('datasets.title', 'like', "%{$q}%")
                   ->orWhere('datasets.publisher_name', 'like', "%{$q}%");
            });
        }

        if ($country) {
            $keysInCountry = DB::table('occurrences')
                ->where('country_code', $country)
                ->whereNotNull('dataset_key')
                ->distinct()
                ->pluck('dataset_key');
            $query->whereIn('datasets.key', $keysInCountry);
        }

        if ($institutionCode) {
            $query->where('datasets.institution_code', $institutionCode);
        }

        $perPage = min((int) $request->input('per_page', 100), 500);
        $datasets = $query->paginate($perPage);

        return response()->json([
            'meta' => [
                'total'        => $datasets->total(),
                'per_page'     => $datasets->perPage(),
                'current_page' => $datasets->currentPage(),
                'last_page'    => $datasets->lastPage(),
            ],
            'data' => $datasets->items(),
        ]);
    }
}
