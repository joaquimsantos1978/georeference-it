<?php

namespace App\Jobs;

use App\Services\GbifService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class SyncGbifByCountry implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300;

    public function __construct(
        public readonly string $countryCode,
        public readonly int $maxPages = 5
    ) {}

    public function handle(GbifService $gbif): void
    {
        $cacheKey = 'gbif_sync_country_' . $this->countryCode;

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, now()->addHours(6));

        try {
            for ($page = 0; $page < $this->maxPages; $page++) {
                $offset = $page * 300;
                $results = $gbif->fetchByCountry($this->countryCode, $offset);

                if (empty($results['results'])) {
                    break;
                }

                $gbif->importResults($results['results']);

                if ($results['endOfRecords'] ?? false) {
                    break;
                }

                sleep(1); // Be polite to GBIF API
            }
        } catch (\Exception $e) {
            Cache::forget($cacheKey);
            throw $e;
        }
    }
}