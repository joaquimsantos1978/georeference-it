<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class GbifSyncDatasets extends Command
{
    protected $signature   = 'gbif:sync-datasets
                                {--missing : Only fetch metadata for datasets not yet in the datasets table}
                                {--stats-only : Skip GBIF API calls, only recompute occurrence stats}';
    protected $description = 'Fetch dataset metadata (title, publisher) from GBIF API and compute occurrence stats';

    public function handle(): int
    {
        if (!$this->option('stats-only')) {
            $this->syncMetadata();
        }

        $this->computeStats();

        return self::SUCCESS;
    }

    private function syncMetadata(): void
    {
        $keys = DB::table('occurrences')
            ->select('dataset_key')
            ->whereNotNull('dataset_key')
            ->distinct()
            ->pluck('dataset_key');

        if ($this->option('missing')) {
            $existing = DB::table('datasets')->whereNotNull('title')->pluck('key')->flip();
            $keys = $keys->filter(fn($k) => !$existing->has($k));
        }

        $total = $keys->count();
        $this->info("Syncing metadata for {$total} datasets from GBIF API...");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $done = 0;
        foreach ($keys as $key) {
            try {
                $resp = Http::timeout(10)->get("https://api.gbif.org/v1/dataset/{$key}");
                if ($resp->successful()) {
                    $d = $resp->json();
                    DB::table('datasets')->upsert([
                        'key'            => $key,
                        'title'          => mb_substr($d['title'] ?? '', 0, 255),
                        'publisher_name' => mb_substr($d['publishingOrganizationTitle'] ?? '', 0, 255),
                        'publisher_key'  => $d['publishingOrganizationKey'] ?? null,
                        'license'        => $d['license'] ?? null,
                        'type'           => $d['type'] ?? null,
                        'synced_at'      => now(),
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ], ['key'], ['title', 'publisher_name', 'publisher_key', 'license', 'type', 'synced_at', 'updated_at']);
                    $done++;
                }
            } catch (\Throwable $e) {
                $this->newLine();
                $this->warn("Failed {$key}: " . $e->getMessage());
            }

            $bar->advance();
            usleep(50000); // 50ms — stay under GBIF rate limit
        }

        $bar->finish();
        $this->newLine();
        $this->info("Metadata: {$done}/{$total} synced.");
    }

    private function computeStats(): void
    {
        $this->info('Computing occurrence stats per dataset...');

        // Single aggregation query over occurrences — result written to datasets table
        DB::statement("
            INSERT INTO datasets (
                `key`, institution_code, collection_code,
                total, georeferenced, validated, ungeoreferenced,
                stats_updated_at, created_at, updated_at
            )
            SELECT
                dataset_key,
                LEFT(MAX(institution_code), 100),
                LEFT(MAX(collection_code), 100),
                COUNT(*),
                SUM(georef_status != 'ungeoreferenced'),
                SUM(georef_status = 'validated'),
                SUM(georef_status = 'ungeoreferenced'),
                NOW(), NOW(), NOW()
            FROM occurrences
            WHERE dataset_key IS NOT NULL
            GROUP BY dataset_key
            ON DUPLICATE KEY UPDATE
                institution_code  = VALUES(institution_code),
                collection_code   = VALUES(collection_code),
                total             = VALUES(total),
                georeferenced     = VALUES(georeferenced),
                validated         = VALUES(validated),
                ungeoreferenced   = VALUES(ungeoreferenced),
                stats_updated_at  = NOW(),
                updated_at        = NOW()
        ");

        $this->info('Stats updated for ' . DB::table('datasets')->count() . ' datasets.');
    }
}
