<?php

namespace App\Jobs;

use App\Services\GbifService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class FetchGbifByLocality implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(
        public readonly string $locality,
        public readonly ?string $countryCode = null
    ) {}

    public function handle(GbifService $gbif): void
    {
        
        $cacheKey = 'gbif_fetch_locality_' . md5($this->locality);

        if (Cache::has($cacheKey)) {
            return;
        }

        try {
            // Step 1: get all matching locality strings from GBIF autocomplete
            $strings = \Illuminate\Support\Facades\Http::timeout(10)
                ->get('https://api.gbif.org/v1/occurrence/search/locality', [
                    'q' => $this->locality,
                    'limit' => 100,
                ])->json();

            if (empty($strings)) return;

            // Step 2: fetch occurrences for each locality string
            foreach ($strings as $localityString) {
                $params = [
                    'locality'      => $localityString,
                    'hasCoordinate' => 'false',
                    'basisOfRecord' => 'PRESERVED_SPECIMEN',
                    'limit'         => 300,
                ];

                if ($this->countryCode) {
                    $params['country'] = $this->countryCode;
                }

                $response = \Illuminate\Support\Facades\Http::timeout(30)
                    ->get('https://api.gbif.org/v1/occurrence/search', $params);

                if ($response->successful()) {
                    $results = $response->json()['results'] ?? [];
                    if (!empty($results)) {
                        $gbif->importResults($results);
                    }
                }

                sleep(1); // be polite to GBIF API
                Cache::put($cacheKey, true, now()->addHours(24));
            }
        } catch (\Exception $e) {
            Cache::forget($cacheKey);
            throw $e;
        }
    }
}
