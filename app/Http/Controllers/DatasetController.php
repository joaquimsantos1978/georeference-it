<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DatasetController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->input('q', '');
        $country = $request->input('country', '');

        $query = DB::table('datasets')
            ->select([
                'datasets.key as dataset_key',
                'datasets.institution_code',
                'datasets.collection_code',
                'datasets.title as dataset_title',
                'datasets.publisher_name',
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
            // Filter by country requires joining occurrences — only used when country filter active
            $keysInCountry = DB::table('occurrences')
                ->where('country_code', $country)
                ->whereNotNull('dataset_key')
                ->distinct()
                ->pluck('dataset_key');
            $query->whereIn('datasets.key', $keysInCountry);
        }

        if ($request->boolean('csv')) {
            return $this->csvDownload($query->get(), $q, $country);
        }

        $datasets = $query->paginate(50)->withQueryString();

        return view('datasets', compact('datasets', 'q', 'country'));
    }

    private function csvDownload($rows, string $q, string $country)
    {
        $filename = 'georeference-it-datasets' . ($country ? "-{$country}" : '') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['dataset_key', 'title', 'publisher', 'institution_code', 'collection_code', 'total', 'georeferenced', 'validated', 'ungeoreferenced', 'gbif_url']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->dataset_key ?? $row->key,
                    $row->dataset_title ?? $row->title ?? '',
                    $row->publisher_name ?? '',
                    $row->institution_code,
                    $row->collection_code,
                    $row->total,
                    $row->georeferenced,
                    $row->validated,
                    $row->ungeoreferenced,
                    'https://www.gbif.org/dataset/' . ($row->dataset_key ?? $row->key),
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}
