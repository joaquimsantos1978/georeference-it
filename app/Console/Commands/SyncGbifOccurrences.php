<?php

namespace App\Console\Commands;

use App\Models\SyncJob;
use App\Services\GbifService;
use Illuminate\Console\Command;

class SyncGbifOccurrences extends Command
{
    protected $signature = 'gbif:sync
                            {--country= : Country code to sync (e.g. PT)}
                            {--dataset= : GBIF dataset key to sync}
                            {--max-pages=5 : Maximum number of pages to fetch}';

    protected $description = 'Fetch occurrences from GBIF and import into the database';

    public function handle(GbifService $gbif): int
    {
        $country = $this->option('country');
        $dataset = $this->option('dataset');
        $maxPages = (int) $this->option('max-pages');

        if (!$country && !$dataset) {
            $this->error('Please provide --country or --dataset option.');
            return Command::FAILURE;
        }

        $syncJob = SyncJob::create([
            'country_code' => $country,
            'dataset_key' => $dataset,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $this->info('Starting GBIF sync...');

        $offset = 0;
        $totalImported = 0;
        $page = 0;

        do {
            $this->line("Fetching page " . ($page + 1) . " (offset: {$offset})...");

            $response = $country
                ? $gbif->fetchByCountry($country, $offset)
                : $gbif->fetchByDataset($dataset, $offset);

            $results = $response['results'] ?? [];
            $endOfRecords = $response['endOfRecords'] ?? true;
            $count = $response['count'] ?? 0;

            if (empty($results)) {
                break;
            }

            $syncJob->update([
                'total_count' => $count,
                'offset' => $offset,
            ]);

            $imported = $gbif->importResults($results);
            $totalImported += $imported;

            $syncJob->update(['fetched_count' => $totalImported]);

            $this->line("  Imported {$imported} records. Total: {$totalImported} / {$count}");

            $offset += count($results);
            $page++;

            // Small delay to be polite to GBIF API
            usleep(500000); // 0.5 seconds

        } while (!$endOfRecords && $page < $maxPages);

        $syncJob->update([
            'status' => 'completed',
            'finished_at' => now(),
            'fetched_count' => $totalImported,
        ]);

        $this->info("Sync completed. Total imported: {$totalImported}");

        return Command::SUCCESS;
    }
}