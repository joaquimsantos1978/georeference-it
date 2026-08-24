<?php

use App\Console\Commands\AwardBadges;
use App\Console\Commands\SendWeeklySummary;
use App\Console\Commands\GbifMonthlyRefresh;
use App\Console\Commands\GbifRefreshHeartbeat;
use App\Console\Commands\GbifWatchdog;
use App\Console\Commands\GbifSyncDatasets;
use App\Console\Commands\ProjectsRefreshCandidates;
use App\Console\Commands\RefreshImpactCounts;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);

        $middleware->throttleApi('300,1');  // 300 requests/minute per IP
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command(SendWeeklySummary::class)->weeklyOn(0, '8:00'); // Sunday 8am

        // First Saturday of every month, 00:00 UTC. Cron has no native "nth weekday of
        // month" field, so this runs the check daily at midnight and gates the actual
        // work with ->when() — cheap to evaluate, only fires when both conditions hold.
        $gbifRefresh = $schedule->command(GbifMonthlyRefresh::class)
            ->dailyAt('00:00')
            ->timezone('UTC')
            ->when(fn () => now()->isSaturday() && now()->day <= 7)
            // Manual escape hatch for skipping the next trigger — e.g. this month's run
            // was manually resumed after a crash and finished only days before the next
            // one would otherwise fire. Set via `Cache::put('gbif:monthly-refresh:paused',
            // true, now()->addDays(N))`; TTL'd on purpose so a forgotten flag can't
            // silently disable the whole pipeline forever.
            ->skip(fn () => \Illuminate\Support\Facades\Cache::get('gbif:monthly-refresh:paused', false))
            ->withoutOverlapping(1440) // minutes; import can take hours, never double-run
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/gbif-monthly-refresh.log'));

        if (config('gbif.notification_email')) {
            $gbifRefresh->emailOutputOnFailure(config('gbif.notification_email'));
        }

        // Progress snapshot by email every 2 hours — a no-op unless gbif:monthly-refresh
        // is actually running (checked via the cache flag it sets), so this is safe to
        // leave scheduled year-round.
        $schedule->command(GbifRefreshHeartbeat::class)->everyTwoHours();

        // Detects a crashed gbif:monthly-refresh (PID gone, no completion ever reported —
        // e.g. MariaDB itself getting OOM-killed mid-query) and auto-resumes it with the
        // same download key, up to a few retries — a no-op unless a crash is actually
        // detected, so safe to leave scheduled year-round like the heartbeat above.
        $schedule->command(GbifWatchdog::class)->everyFiveMinutes();

        // Keeps Stats/Impact/Explore/Activity's counts fresh without ever computing them
        // inline on a page request (see ImpactController for why that caused 504s).
        // Now derives everything from locality_groups' per-status counters instead of
        // scanning `occurrences` directly (see RefreshImpactCounts) — that took the
        // typical run from 2-3+ hours down to low minutes, so it can run far more often
        // than the old hourly cadence and still finish well before the next tick.
        // Skipped while a GBIF import is running: locality_groups is being written to
        // in batches by the import at the same time, and competing for I/O with those
        // batches every few minutes would be a bad trade during a days-long import for
        // numbers that are only cosmetic (Stats/Impact/Explore/Activity, not correctness-
        // critical). Self-resuming: no manual re-enable needed once the import finishes.
        $schedule->command(RefreshImpactCounts::class)
            ->everyTenMinutes()
            ->withoutOverlapping()
            ->skip(fn () => !empty(\Illuminate\Support\Facades\Cache::get(GbifMonthlyRefresh::STATUS_KEY)['running'] ?? false))
            // Manual escape hatch for a run started outside the scheduler (withoutOverlapping()
            // only guards scheduler-launched instances against each other, not a manual `php
            // artisan impact:refresh-counts`) — set via `Cache::put('impact:refresh-counts:paused',
            // true, now()->addHours(N))`. TTL'd on purpose so a forgotten flag can't silently
            // disable this forever; clear early with `Cache::forget(...)` once done.
            ->skip(fn () => \Illuminate\Support\Facades\Cache::get('impact:refresh-counts:paused', false));

        // Datasets page stats — a separate pipeline from the one above on purpose:
        // dataset_key isn't a property of a locality_group (one group can hold
        // occurrences from several datasets sharing the same locality text), so this
        // still has to aggregate `occurrences` directly rather than locality_groups.
        // That makes it inherently slower than RefreshImpactCounts, so it stays daily
        // rather than every-10-minutes — previously this only ran when someone
        // remembered to invoke `gbif:sync-datasets --stats-only` by hand, so the
        // Datasets page could silently go stale for weeks. Same GBIF-import guard as
        // above, same reasoning (competes for I/O with the import's own batches).
        // TEMPORARILY DISABLED (2026-08-24) — this run has twice left long-running/orphaned
        // `INSERT INTO datasets` connections behind that starve unrelated writes (georef
        // submissions started failing with "Lock wait timeout exceeded" both times), including
        // the 03:00 run today, which also exited with code 1. Re-enable once the root cause
        // is fixed — see conversation/investigation notes.
        // $schedule->command(GbifSyncDatasets::class, ['--stats-only' => true])
        //     ->dailyAt('03:00')
        //     ->withoutOverlapping(240)
        //     ->skip(fn () => !empty(\Illuminate\Support\Facades\Cache::get(GbifMonthlyRefresh::STATUS_KEY)['running'] ?? false));

        // Only scans users active in the last 24h (see AwardBadges), so this stays cheap
        // even hourly.
        $schedule->command(AwardBadges::class)->hourly()->withoutOverlapping();

        // Rebuilds project_candidate_groups (see ProjectsRefreshCandidates) — moves the
        // cost of evaluating a criteria-mode project's (possibly unindexed) criteria out of
        // the live /georef/next request path. Same competes-for-I/O-with-the-import
        // reasoning as GbifSyncDatasets above; withoutOverlapping guards against a slow
        // project (e.g. a LIKE fallback scan) still running when the next tick fires.
        $schedule->command(ProjectsRefreshCandidates::class)
            ->everyFifteenMinutes()
            ->withoutOverlapping(30)
            ->skip(fn () => !empty(\Illuminate\Support\Facades\Cache::get(GbifMonthlyRefresh::STATUS_KEY)['running'] ?? false))
            ->appendOutputTo(storage_path('logs/projects-refresh-candidates.log'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
