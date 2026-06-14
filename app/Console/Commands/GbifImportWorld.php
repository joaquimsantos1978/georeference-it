<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GbifImportWorld extends Command
{
    protected $signature = 'gbif:import-world
                            {--country= : Limit to a single country (e.g. PT)}
                            {--key= : Use an existing GBIF download key instead of requesting a new one}
                            {--file= : Use an already-downloaded ZIP file}
                            {--skip-import : Skip download+import, only run post-processing}
                            {--skip-consistency : Skip consistency check}
                            {--skip-suggestions : Skip auto-suggest}';

    protected $description = 'Full pipeline: request GBIF download → import → sync datasets → consistency → auto-suggest';

    public function handle(): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════╗');
        $this->info('║   georeference.it — GBIF full import ║');
        $this->info('╚══════════════════════════════════════╝');
        $this->info('');

        $startedAt = now();

        // ── Step 1: Download + Import ────────────────────────────────────────
        if (!$this->option('skip-import')) {
            $key  = $this->option('key');
            $file = $this->option('file');

            if (!$key && !$file) {
                $key = $this->requestDownload();
                if (!$key) {
                    return self::FAILURE;
                }
            }

            $this->info('[1/4] Importing occurrences...');
            $args = [];
            if ($key)  $args['key']    = $key;
            if ($file) $args['--file'] = $file;
            if (!$key) $args['key']    = 'none'; // won't be used when --file is set

            $importArgs = array_filter([
                'key'    => $key ?? 'none',
                '--file' => $file,
            ]);

            $exitCode = $this->call('gbif:import-download', $importArgs);

            if ($exitCode !== self::SUCCESS) {
                $this->error('Import failed. Aborting pipeline.');
                return self::FAILURE;
            }
        } else {
            $this->info('[1/4] Skipping import (--skip-import).');
        }

        // ── Step 2: Sync dataset stats ───────────────────────────────────────
        $this->info('');
        $this->info('[2/4] Syncing dataset statistics...');
        $this->call('gbif:sync-datasets', ['--stats-only' => true]);

        // ── Step 3: Consistency check ────────────────────────────────────────
        if (!$this->option('skip-consistency')) {
            $this->info('');
            $this->info('[3/4] Checking consistency...');
            $consistencyArgs = ['--recheck' => true];
            if ($country = $this->option('country')) {
                $consistencyArgs['--country'] = $country;
            }
            $this->call('gbif:check-consistency', $consistencyArgs);
        } else {
            $this->info('[3/4] Skipping consistency check (--skip-consistency).');
        }

        // ── Step 4: Auto-suggestions ─────────────────────────────────────────
        if (!$this->option('skip-suggestions')) {
            $this->info('');
            $this->info('[4/4] Creating auto-suggestions...');
            $this->call('gbif:auto-suggest');
        } else {
            $this->info('[4/4] Skipping auto-suggestions (--skip-suggestions).');
        }

        // ── Done ─────────────────────────────────────────────────────────────
        $elapsed = now()->diffInMinutes($startedAt);
        $this->info('');
        $this->info("Pipeline complete in {$elapsed} minutes.");

        return self::SUCCESS;
    }

    private function requestDownload(): ?string
    {
        $user = config('gbif.username');
        $pass = config('gbif.password');

        if (!$user || !$pass) {
            $this->error('GBIF credentials not set. Add GBIF_USERNAME and GBIF_PASSWORD to .env');
            return null;
        }

        // Resolve handle
        $response = Http::withBasicAuth($user, $pass)->get('https://api.gbif.org/v1/user/login');
        if (!$response->successful()) {
            $this->error('GBIF login failed: ' . $response->status());
            return null;
        }
        $handle = $response->json('userName');

        // Build predicate
        $predicates = [
            ['type' => 'equals', 'key' => 'BASIS_OF_RECORD', 'value' => 'PRESERVED_SPECIMEN'],
        ];
        if ($country = $this->option('country')) {
            $predicates[] = ['type' => 'equals', 'key' => 'COUNTRY', 'value' => strtoupper($country)];
        }
        $predicate = count($predicates) === 1 ? $predicates[0] : ['type' => 'and', 'predicates' => $predicates];

        $label = $this->option('country') ? 'country=' . strtoupper($this->option('country')) : 'world';
        $this->info("[1/4] Requesting GBIF download ({$label})...");

        $response = Http::withBasicAuth($user, $pass)
            ->post('https://api.gbif.org/v1/occurrence/download/request', [
                'creator'               => $handle,
                'notificationAddresses' => [config('gbif.notification_email')],
                'sendNotification'      => true,
                'format'                => 'DWCA',
                'predicate'             => $predicate,
            ]);

        if (!$response->successful()) {
            $this->error('GBIF API error: ' . $response->body());
            return null;
        }

        $key = trim($response->body(), '"');
        $this->info("  Download key: {$key}");

        return $key;
    }
}
