<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class GbifWatchdog extends Command
{
    protected $signature = 'gbif:watchdog';

    protected $description = 'Detect a crashed gbif:monthly-refresh (PID gone but the run never reported completion) and auto-resume it — no-op otherwise';

    private const RETRY_COUNT_KEY   = 'gbif:monthly-refresh:watchdog:retries';
    private const LAST_ATTEMPT_KEY  = 'gbif:monthly-refresh:watchdog:last_attempt';
    private const EXHAUSTED_KEY     = 'gbif:monthly-refresh:watchdog:exhausted-notified';
    private const MAX_RETRIES       = 5;
    private const COOLDOWN_MINUTES  = 15;

    public function handle(): int
    {
        $status = Cache::get(GbifMonthlyRefresh::STATUS_KEY);

        if (!$status || empty($status['running']) || empty($status['pid'])) {
            return self::SUCCESS; // nothing tracked, or a clean success/failure already cleared it
        }

        if (@file_exists("/proc/{$status['pid']}")) {
            return self::SUCCESS; // still alive, nothing to do
        }

        // PID recorded as running but the process is gone and never reached its own
        // cleanup code (both the success path and abortWith() Cache::forget the status —
        // an uncaught exception, e.g. MariaDB itself getting OOM-killed mid-query, skips
        // that entirely and would otherwise leave this stuck at running=true forever).
        $lastAttempt = Cache::get(self::LAST_ATTEMPT_KEY);
        if ($lastAttempt && $lastAttempt->diffInMinutes(now()) < self::COOLDOWN_MINUTES) {
            return self::SUCCESS; // avoid retry-storming a genuine crash loop
        }

        $email   = config('gbif.notification_email');
        $key     = Cache::get(GbifMonthlyRefresh::ACTIVE_KEY_CACHE);
        $retries = Cache::get(self::RETRY_COUNT_KEY, 0);
        $step    = $status['step'] ?? 'unknown';

        if (!$key) {
            // Shouldn't normally happen — GbifMonthlyRefresh sets this at the same time as
            // STATUS_KEY — but fail safe rather than guess a key to relaunch with.
            Log::channel('single')->error('[gbif:watchdog] Crash detected but no active download key on record — cannot auto-resume.');
            return self::SUCCESS;
        }

        if ($retries >= self::MAX_RETRIES) {
            if (!Cache::get(self::EXHAUSTED_KEY)) {
                Cache::forever(self::EXHAUSTED_KEY, true);
                if ($email) {
                    $this->notify($email, 'GBIF monthly refresh — auto-resume exhausted, manual intervention needed',
                        "gbif:monthly-refresh (PID {$status['pid']}) crashed and was auto-resumed {$retries} times, all of which crashed again.\n\n"
                        . "Giving up — manual intervention needed.\nLast known step: {$step}");
                }
            }
            return self::SUCCESS;
        }

        Cache::forever(self::LAST_ATTEMPT_KEY, now());
        Cache::forever(self::RETRY_COUNT_KEY, $retries + 1);

        $logPath = storage_path('logs/gbif-monthly-refresh.log');
        $cmd = sprintf(
            'nohup php %s gbif:monthly-refresh --skip-request --key=%s >> %s 2>&1 &',
            escapeshellarg(base_path('artisan')),
            escapeshellarg($key),
            escapeshellarg($logPath)
        );
        exec($cmd);

        $attempt = $retries + 1;
        Log::channel('single')->warning("[gbif:watchdog] PID {$status['pid']} crashed at step '{$step}' — auto-resuming with key {$key} (attempt {$attempt}/" . self::MAX_RETRIES . ')');

        if ($email) {
            $this->notify($email, 'GBIF monthly refresh — crashed, auto-resuming',
                "gbif:monthly-refresh (PID {$status['pid']}) is no longer running but never reported completion — it crashed.\n\n"
                . "Last known step: {$step}\n\nAuto-resuming now with the same download key (attempt {$attempt}/" . self::MAX_RETRIES . ').');
        }

        return self::SUCCESS;
    }

    private function notify(string $email, string $subject, string $body): void
    {
        try {
            Mail::raw($body, fn($m) => $m->to($email)->subject($subject));
        } catch (\Throwable $e) {
            Log::channel('single')->error('[gbif:watchdog] Failed to send notification email: ' . $e->getMessage());
        }
    }
}
