<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class GbifRefreshHeartbeat extends Command
{
    protected $signature = 'gbif:refresh-heartbeat';

    protected $description = 'Email a progress snapshot while gbif:monthly-refresh is running; no-op otherwise';

    public function handle(): int
    {
        $status = Cache::get(GbifMonthlyRefresh::STATUS_KEY);

        if (!$status || empty($status['running'])) {
            return self::SUCCESS; // no refresh in progress — nothing to report
        }

        $email = config('gbif.notification_email');

        // A crash (uncaught exception, e.g. the DB connection dropping mid-query when
        // MariaDB itself gets OOM-killed) skips GbifMonthlyRefresh's own cleanup code
        // entirely, leaving this status stuck at running=true forever with a PID that no
        // longer exists — otherwise we'd keep emailing "still running" indefinitely for a
        // process that's actually dead.
        if (!empty($status['pid']) && !@file_exists("/proc/{$status['pid']}")) {
            Cache::forget(GbifMonthlyRefresh::STATUS_KEY);
            if ($email) {
                try {
                    Mail::raw(
                        "GBIF monthly refresh (PID {$status['pid']}) is no longer running but never reported completion — it likely crashed. Last known step: " . ($status['step'] ?? 'unknown'),
                        fn($m) => $m->to($email)->subject('GBIF monthly refresh — crashed')
                    );
                } catch (\Throwable $e) {
                    $this->error('Failed to send crash notice email: ' . $e->getMessage());
                }
            }
            return self::SUCCESS;
        }

        if (!$email) {
            return self::SUCCESS;
        }

        $elapsed  = $status['started_at']->diffForHumans(now(), true);
        $step     = $status['step'] ?? 'unknown';
        $stagingCount = (int) DB::table('gbif_staging')->count();

        $freeGb = round(disk_free_space('/') / 1024 / 1024 / 1024, 1);
        $memInfo = @file_get_contents('/proc/meminfo');
        $memAvailableGb = null;
        if ($memInfo && preg_match('/MemAvailable:\s+(\d+) kB/', $memInfo, $m)) {
            $memAvailableGb = round(((int) $m[1]) / 1024 / 1024, 1);
        }

        $body = "GBIF monthly refresh is still running ({$elapsed} elapsed).\n\n"
            . "Current step: {$step}\n"
            . "gbif_staging rows: " . number_format($stagingCount) . "\n"
            . "Disk free: {$freeGb} GB\n"
            . ($memAvailableGb !== null ? "Memory available: {$memAvailableGb} GB\n" : '');

        try {
            Mail::raw($body, fn($m) => $m->to($email)->subject('GBIF monthly refresh — still running'));
        } catch (\Throwable $e) {
            $this->error('Failed to send heartbeat email: ' . $e->getMessage());
        }

        return self::SUCCESS;
    }
}
