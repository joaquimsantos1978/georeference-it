<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class GbifMonthlyRefresh extends Command
{
    protected $signature = 'gbif:monthly-refresh
                            {--skip-request : Skip requesting a new download (use --key instead)}
                            {--key= : Reuse an existing GBIF download key instead of requesting a new one}';

    protected $description = 'Full monthly GBIF refresh: request download, wait, import, reconcile suggestions, then re-run auto-suggestions, consistency checks, counters and dataset stats';

    // Cache key the heartbeat command (gbif:refresh-heartbeat) reads to know a refresh is
    // in progress and report on it — see that command for the periodic-email side of this.
    const STATUS_KEY = 'gbif:monthly-refresh:status';

    // Cache key gbif:watchdog reads to know which download key to auto-resume with after
    // a crash. Kept separate from STATUS_KEY (rather than nested inside it) so its
    // lifecycle is exactly "set for the duration of a live run, cleared on any exit path" —
    // simple enough that the watchdog never has to guess whether a value it read is stale.
    const ACTIVE_KEY_CACHE = 'gbif:monthly-refresh:active-key';

    private function markStep(string $step): void
    {
        $status = Cache::get(self::STATUS_KEY, []);
        $status['step'] = $step;
        unset($status['substep']); // stale otherwise — see GbifImportDownload::markProgress()
        $status['updated_at'] = now();
        Cache::forever(self::STATUS_KEY, $status);
    }

    // A TTL-based lock doesn't fit how this actually gets operated: a stuck run is
    // routinely killed by hand and immediately retried, and any fixed TTL is either too
    // short (expires mid-legitimate-run, letting a real duplicate start) or too long
    // (blocks a manual retry for hours after a kill, since SIGTERM/SIGKILL skip our own
    // release-the-lock code). Recording the PID and checking /proc/{pid} instead means a
    // killed process is detected as gone essentially immediately, no timeout to tune.
    private function isProcessAlive(int $pid): bool
    {
        return @file_exists("/proc/{$pid}");
    }

    public function handle(): int
    {
        // ->withoutOverlapping() on the schedule only guards runs the scheduler itself
        // kicks off — a manual `php artisan gbif:monthly-refresh --key=...` (which is
        // exactly how this gets retried after a failure) bypasses it entirely. Nothing
        // stopped two invocations from running concurrently and fighting over the same
        // tables.
        $existing = Cache::get(self::STATUS_KEY);
        if ($existing && !empty($existing['running']) && !empty($existing['pid']) && $this->isProcessAlive($existing['pid'])) {
            $this->error("Another gbif:monthly-refresh (PID {$existing['pid']}) is already running — exiting.");
            Log::channel('single')->warning("[gbif:monthly-refresh] Skipped: PID {$existing['pid']} is still running");
            return self::FAILURE;
        }

        $start = now();
        Cache::forever(self::STATUS_KEY, ['running' => true, 'pid' => getmypid(), 'started_at' => $start, 'step' => 'Starting', 'updated_at' => $start]);
        // A genuinely fresh run (not a watchdog auto-resume) resets the retry bookkeeping —
        // otherwise a crash-loop from months ago could leave gbif:watchdog permanently
        // silenced for this month's run too.
        Cache::forget('gbif:monthly-refresh:watchdog:retries');
        Cache::forget('gbif:monthly-refresh:watchdog:last_attempt');
        Cache::forget('gbif:monthly-refresh:watchdog:exhausted-notified');
        Log::channel('single')->info('[gbif:monthly-refresh] Starting monthly refresh');
        $this->info("Starting monthly GBIF refresh at {$start}...");

        // Step 1: request (or reuse) a download key
        $key = $this->option('key');

        if (!$key && !$this->option('skip-request')) {
            $this->markStep('Step 1/6: Requesting GBIF download');
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
        Cache::forever(self::ACTIVE_KEY_CACHE, $key);

        // Step 2: poll, download, and import (gbif:import-download already polls internally
        // for up to 8 hours, downloads the DWCA, stages it, and upserts in batches)
        $this->markStep('Step 2/7: Importing (download key: ' . $key . ')');
        $this->info('Step 2/7: Importing (polling until GBIF finishes preparing the download — may take hours)...');
        // --prune-deleted is safe here since the monthly refresh always requests a full,
        // unfiltered world download (never --country-scoped).
        $exit = Artisan::call('gbif:import-download', ['key' => $key, '--prune-deleted' => true]);
        $this->line(Artisan::output());

        if ($exit !== self::SUCCESS) {
            return $this->abortWith('gbif:import-download failed — aborting refresh before downstream steps.');
        }

        // Step 3: an occurrence whose published locality GBIF corrected this cycle may have
        // moved to a different locality_group — clean up whatever suggestion it left behind
        // on its old (now possibly empty) group, and attach it to its new group's existing
        // suggestion if there is one. Must run before auto-suggest below, which skips any
        // group that already has a suggestion and would otherwise never revisit these.
        $this->markStep('Step 3/7: Reconciling suggestions after re-grouping');
        $this->info('Step 3/7: Reconciling suggestions after re-grouping...');
        Artisan::call('gbif:reconcile-suggestions');
        $this->line(Artisan::output());

        // Step 4: regenerate system auto-suggestions for newly-eligible groups
        $this->markStep('Step 4/7: Creating system auto-suggestions');
        $this->info('Step 4/7: Creating system auto-suggestions...');
        Artisan::call('gbif:auto-suggest');
        $this->line(Artisan::output());

        // Step 5: re-run consistency checks (new/changed coordinates may reveal conflicts)
        $this->markStep('Step 5/7: Checking consistency');
        $this->info('Step 5/7: Checking consistency...');
        Artisan::call('gbif:check-consistency');
        $this->line(Artisan::output());

        // Step 6: backfill locality_groups.ungeoreferenced_count from the fresh occurrences data
        $this->markStep('Step 6/7: Backfilling ungeoreferenced counts');
        $this->info('Step 6/7: Backfilling ungeoreferenced counts...');
        Artisan::call('gbif:backfill-ungeoreferenced');
        $this->line(Artisan::output());

        // Step 7: refresh dataset metadata/stats shown on the Datasets page
        $this->markStep('Step 7/7: Syncing dataset stats');
        $this->info('Step 7/7: Syncing dataset stats...');
        Artisan::call('gbif:sync-datasets');
        $this->line(Artisan::output());

        $duration = $start->diffForHumans(now(), true);
        $this->info("Monthly GBIF refresh complete. Took {$duration}.");
        Log::channel('single')->info("[gbif:monthly-refresh] Completed successfully in {$duration}");

        Cache::forget(self::STATUS_KEY);
        Cache::forget(self::ACTIVE_KEY_CACHE);
        $this->sendReport(true, $duration);

        return self::SUCCESS;
    }

    private function abortWith(string $message): int
    {
        $this->error($message);
        Log::channel('single')->error("[gbif:monthly-refresh] {$message}");
        Cache::forget(self::STATUS_KEY);
        Cache::forget(self::ACTIVE_KEY_CACHE);
        $this->sendReport(false, null, $message);
        return self::FAILURE;
    }

    private function sendReport(bool $success, ?string $duration, ?string $failureMessage = null): void
    {
        $email = config('gbif.notification_email');
        if (!$email) {
            return;
        }

        $occCount   = number_format(DB::table('occurrences')->count());
        $groupCount = number_format(DB::table('locality_groups')->count());
        $subject    = $success
            ? "GBIF monthly refresh succeeded ({$duration})"
            : 'GBIF monthly refresh FAILED';

        $body = $success
            ? "The monthly GBIF refresh completed successfully in {$duration}.\n\nOccurrences: {$occCount}\nLocality groups: {$groupCount}"
            : "The monthly GBIF refresh failed:\n\n{$failureMessage}\n\nOccurrences: {$occCount}\nLocality groups: {$groupCount}";

        try {
            Mail::raw($body, fn($m) => $m->to($email)->subject($subject));
        } catch (\Throwable $e) {
            Log::channel('single')->error('[gbif:monthly-refresh] Failed to send report email: ' . $e->getMessage());
        }
    }
}
