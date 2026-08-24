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
            ->whereNull('deleted_at')
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

        // Two separate steps on purpose — NOT `INSERT INTO datasets SELECT ... FROM
        // occurrences GROUP BY ...` in one statement. InnoDB takes shared locks on every row
        // an INSERT...SELECT reads from its source table (unlike a standalone SELECT, which
        // gets a lock-free consistent read) — for a GROUP BY over the full 300M+-row
        // `occurrences` table, that meant holding locks across a huge swath of the table for
        // as long as the scan took, which is exactly what produced "Lock wait timeout
        // exceeded" on unrelated georef submissions (an UPDATE on any row still read-locked
        // by the scan has to wait) both times this ran. A plain SELECT here takes no locks at
        // all; the per-dataset upsert loop below only ever locks one row at a time, briefly.
        $rows = DB::select("
            SELECT
                dataset_key AS `key`,
                MAX(institution_code) AS institution_code,
                MAX(collection_code) AS collection_code,
                COUNT(*) AS total,
                SUM(georef_status != 'ungeoreferenced') AS georeferenced,
                SUM(georef_status = 'validated') AS validated,
                SUM(georef_status = 'ungeoreferenced') AS ungeoreferenced
            FROM occurrences
            WHERE dataset_key IS NOT NULL
              AND deleted_at IS NULL
            GROUP BY dataset_key
        ");

        $now = now();
        foreach ($rows as $row) {
            DB::table('datasets')->upsert([
                'key'              => $row->key,
                'institution_code' => $row->institution_code,
                'collection_code'  => $row->collection_code,
                'total'            => $row->total,
                'georeferenced'    => $row->georeferenced,
                'validated'        => $row->validated,
                'ungeoreferenced'  => $row->ungeoreferenced,
                'stats_updated_at' => $now,
                'created_at'       => $now,
                'updated_at'       => $now,
            ], ['key'], [
                'institution_code', 'collection_code', 'total', 'georeferenced',
                'validated', 'ungeoreferenced', 'stats_updated_at', 'updated_at',
            ]);
        }

        $this->info('Stats updated for ' . count($rows) . ' datasets.');
    }
}
