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

    // Highest step (2-7) that has actually finished for ACTIVE_KEY_CACHE's current
    // key. A resume — watchdog auto-resume or a manual retry with the same --key —
    // reads this and skips straight past whatever already succeeded, instead of
    // redoing the hours-long download+import every time a downstream step (e.g.
    // auto-suggest) is what actually crashed. Only meaningful together with the key
    // it was recorded under, which is why it's reset whenever ACTIVE_KEY_CACHE
    // changes rather than living forever on its own.
    const PROGRESS_CACHE = 'gbif:monthly-refresh:completed-step';

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
        Log::channel('single')->info('[gbif:monthly-refresh] Starting monthly refresh');
        $this->info("Starting monthly GBIF refresh at {$start}...");

        // Step 1: request (or reuse) a download key
        $key = $this->option('key');

        if (!$key && !$this->option('skip-request')) {
            $this->markStep('Step 1/7: Requesting GBIF download');
            $this->info('Step 1/7: Requesting GBIF download...');
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

        // A key different from whatever this pipeline was last working on (a freshly
        // requested key, or a human deliberately pointing at a different one) means
        // there's nothing to resume — any completed-step progress recorded under the
        // old key is meaningless here, and a stale watchdog retry count from an old
        // crash loop shouldn't silence auto-resume for this run too.
        if ($key !== Cache::get(self::ACTIVE_KEY_CACHE)) {
            Cache::forget(self::PROGRESS_CACHE);
            Cache::forget('gbif:monthly-refresh:watchdog:retries');
            Cache::forget('gbif:monthly-refresh:watchdog:last_attempt');
            Cache::forget('gbif:monthly-refresh:watchdog:exhausted-notified');
        }
        Cache::forever(self::ACTIVE_KEY_CACHE, $key);

        $completedStep = Cache::get(self::PROGRESS_CACHE, 0);
        if ($completedStep > 0) {
            $this->info("Resuming with this key: steps up to {$completedStep} already completed, continuing from step " . ($completedStep + 1) . '/7.');
        }

        // Step 2: poll, download, and import (gbif:import-download already polls internally
        // for up to 8 hours, downloads the DWCA, stages it, and upserts in batches)
        // --prune-deleted is safe here since the monthly refresh always requests a full,
        // unfiltered world download (never --country-scoped).
        $exit = $this->runStep(
            $completedStep, 2,
            'Step 2/7: Importing (download key: ' . $key . ')',
            'gbif:import-download', ['key' => $key, '--prune-deleted' => true],
            'Step 2/7: Importing (polling until GBIF finishes preparing the download — may take hours)...'
        );
        if ($exit !== self::SUCCESS) {
            return $this->abortWith('gbif:import-download failed — aborting refresh before downstream steps.');
        }

        // Step 3: an occurrence whose published locality GBIF corrected this cycle may have
        // moved to a different locality_group — clean up whatever suggestion it left behind
        // on its old (now possibly empty) group, and attach it to its new group's existing
        // suggestion if there is one. Must run before auto-suggest below, which skips any
        // group that already has a suggestion and would otherwise never revisit these.
        $this->runStep($completedStep, 3, 'Step 3/7: Reconciling suggestions after re-grouping', 'gbif:reconcile-suggestions');

        // Step 4: regenerate system auto-suggestions for newly-eligible groups
        $this->runStep($completedStep, 4, 'Step 4/7: Creating system auto-suggestions', 'gbif:auto-suggest');

        // Step 5: re-run consistency checks (new/changed coordinates may reveal conflicts)
        $this->runStep($completedStep, 5, 'Step 5/7: Checking consistency', 'gbif:check-consistency');

        // Step 6: backfill locality_groups.ungeoreferenced_count from the fresh occurrences data
        $this->runStep($completedStep, 6, 'Step 6/7: Backfilling ungeoreferenced counts', 'gbif:backfill-ungeoreferenced');

        // Step 7: refresh dataset metadata/stats shown on the Datasets page
        $this->runStep($completedStep, 7, 'Step 7/7: Syncing dataset stats', 'gbif:sync-datasets');

        $duration = $start->diffForHumans(now(), true);
        $this->info("Monthly GBIF refresh complete. Took {$duration}.");
        Log::channel('single')->info("[gbif:monthly-refresh] Completed successfully in {$duration}");

        Cache::forget(self::STATUS_KEY);
        Cache::forget(self::ACTIVE_KEY_CACHE);
        Cache::forget(self::PROGRESS_CACHE);
        $this->sendReport(true, $duration);

        return self::SUCCESS;
    }

    // Runs one top-level pipeline step, unless $completedStep already covers it (a
    // resume with the same download key) — in which case it's a no-op that reports
    // success, so callers don't need their own branching for the skip case. Only
    // records completion when the step actually succeeds; steps 3-7 don't check their
    // own exit code (matching the pre-existing behavior of always proceeding to the
    // next step regardless), so a failing step 4 simply never advances $completedStep
    // past 3 and a resume will retry it.
    private function runStep(int &$completedStep, int $stepNumber, string $label, string $command, array $params = [], ?string $consoleMessage = null): int
    {
        if ($completedStep >= $stepNumber) {
            $this->info("Step {$stepNumber}/7 already completed for this download key — skipping.");
            return self::SUCCESS;
        }

        $this->markStep($label);
        $this->info($consoleMessage ?? "{$label}...");
        $exit = Artisan::call($command, $params);
        $this->line(Artisan::output());

        if ($exit === self::SUCCESS) {
            $completedStep = $stepNumber;
            Cache::forever(self::PROGRESS_CACHE, $stepNumber);
        }

        return $exit;
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
