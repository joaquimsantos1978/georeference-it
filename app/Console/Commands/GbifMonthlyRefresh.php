<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class GbifMonthlyRefresh extends Command
{
    protected $signature = 'gbif:monthly-refresh
                            {--skip-request : Skip requesting a new download (use --key instead)}
                            {--key= : Reuse an existing GBIF download key instead of requesting a new one}';

    protected $description = 'Full monthly GBIF refresh: request download, wait, import, then re-run auto-suggestions, consistency checks, counters and dataset stats';

    public function handle(): int
    {
        $start = now();
        Log::channel('single')->info('[gbif:monthly-refresh] Starting monthly refresh');
        $this->info("Starting monthly GBIF refresh at {$start}...");

        // Step 1: request (or reuse) a download key
        $key = $this->option('key');

        if (!$key && !$this->option('skip-request')) {
            $this->info('Step 1/6: Requesting GBIF download...');
            $exit = Artisan::call('gbif:request-download');
            $output = Artisan::output();
            $this->line($output);

            if ($exit !== self::SUCCESS) {
                return $this->abortWith('gbif:request-download failed — aborting refresh.');
            }

            if (!preg_match('/Key:\s*(\S+)/', $output, $m)) {
                return $this->abortWith('Could not parse download key from gbif:request-download output — aborting refresh.');
            }
            $key = $m[1];
        }

        if (!$key) {
            return $this->abortWith('No download key available (missing --key and --skip-request without --key) — aborting refresh.');
        }

        $this->info("Using download key: {$key}");

        // Step 2: poll, download, and import (gbif:import-download already polls internally
        // for up to 8 hours, downloads the DWCA, stages it, and upserts in batches)
        $this->info('Step 2/6: Importing (polling until GBIF finishes preparing the download — may take hours)...');
        $exit = Artisan::call('gbif:import-download', ['key' => $key]);
        $this->line(Artisan::output());

        if ($exit !== self::SUCCESS) {
            return $this->abortWith('gbif:import-download failed — aborting refresh before downstream steps.');
        }

        // Step 3: regenerate system auto-suggestions for newly-eligible groups
        $this->info('Step 3/6: Creating system auto-suggestions...');
        Artisan::call('gbif:auto-suggest');
        $this->line(Artisan::output());

        // Step 4: re-run consistency checks (new/changed coordinates may reveal conflicts)
        $this->info('Step 4/6: Checking consistency...');
        Artisan::call('gbif:check-consistency');
        $this->line(Artisan::output());

        // Step 5: backfill locality_groups.ungeoreferenced_count from the fresh occurrences data
        $this->info('Step 5/6: Backfilling ungeoreferenced counts...');
        Artisan::call('gbif:backfill-ungeoreferenced');
        $this->line(Artisan::output());

        // Step 6: refresh dataset metadata/stats shown on the Datasets page
        $this->info('Step 6/6: Syncing dataset stats...');
        Artisan::call('gbif:sync-datasets');
        $this->line(Artisan::output());

        $duration = $start->diffForHumans(now(), true);
        $this->info("Monthly GBIF refresh complete. Took {$duration}.");
        Log::channel('single')->info("[gbif:monthly-refresh] Completed successfully in {$duration}");

        return self::SUCCESS;
    }

    private function abortWith(string $message): int
    {
        $this->error($message);
        Log::channel('single')->error("[gbif:monthly-refresh] {$message}");
        return self::FAILURE;
    }
}
