<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GbifRequestDownload extends Command
{
    protected $signature = 'gbif:request-download
                            {--country= : Filter by ISO country code for testing (e.g. PT)}';

    protected $description = 'Request a GBIF DWCA occurrence download and output the download key';

    public function handle(): int
    {
        $user = config('gbif.username');
        $pass = config('gbif.password');

        if (!$user || !$pass) {
            $this->error('GBIF credentials not set. Add GBIF_USERNAME and GBIF_PASSWORD to .env');
            return self::FAILURE;
        }

        // Resolve the GBIF username handle (may differ from login email)
        $handle = $this->resolveGbifHandle($user, $pass);
        if (!$handle) {
            return self::FAILURE;
        }

        $predicate = $this->buildPredicate();

        $this->info("Requesting GBIF download as '{$handle}'...");

        $response = Http::withBasicAuth($user, $pass)
            ->post('https://api.gbif.org/v1/occurrence/download/request', [
                'creator'               => $handle,
                'notificationAddresses' => [config('gbif.notification_email')],
                'sendNotification'      => true,
                'format'                => 'DWCA',
                'predicate'             => $predicate,
            ]);

        if (!$response->successful()) {
            $this->error('GBIF API error ' . $response->status() . ': ' . $response->body());
            return self::FAILURE;
        }

        $key = trim($response->body(), '"');

        $this->info("Download requested. Key: <comment>{$key}</comment>");
        $this->info('You will receive an email when ready. Then run:');
        $this->line("  php artisan gbif:import-download {$key}");

        return self::SUCCESS;
    }

    private function resolveGbifHandle(string $user, string $pass): ?string
    {
        $response = Http::withBasicAuth($user, $pass)
            ->get('https://api.gbif.org/v1/user/login');

        if (!$response->successful()) {
            $this->error('GBIF login failed: ' . $response->status() . ' — check GBIF_USERNAME and GBIF_PASSWORD');
            return null;
        }

        $handle = $response->json('userName');
        if (!$handle) {
            $this->error('Could not resolve GBIF username from login response');
            return null;
        }

        return $handle;
    }

    private function buildPredicate(): array
    {
        $predicates = [
            ['type' => 'equals', 'key' => 'BASIS_OF_RECORD', 'value' => 'PRESERVED_SPECIMEN'],
        ];

        if ($country = $this->option('country')) {
            $predicates[] = ['type' => 'equals', 'key' => 'COUNTRY', 'value' => strtoupper($country)];
        }

        return count($predicates) === 1
            ? $predicates[0]
            : ['type' => 'and', 'predicates' => $predicates];
    }
}
