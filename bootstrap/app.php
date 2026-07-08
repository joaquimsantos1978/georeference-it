<?php

use App\Console\Commands\SendWeeklySummary;
use App\Console\Commands\GbifMonthlyRefresh;
use App\Console\Commands\GbifRefreshHeartbeat;
use App\Console\Commands\GbifWatchdog;
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

        $middleware->throttleApi('60,1');  // 60 requests/minute per IP
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command(SendWeeklySummary::class)->weeklyOn(1, '8:00'); // Monday 8am

        // First Saturday of every month, 00:00 UTC. Cron has no native "nth weekday of
        // month" field, so this runs the check daily at midnight and gates the actual
        // work with ->when() — cheap to evaluate, only fires when both conditions hold.
        $gbifRefresh = $schedule->command(GbifMonthlyRefresh::class)
            ->dailyAt('00:00')
            ->timezone('UTC')
            ->when(fn () => now()->isSaturday() && now()->day <= 7)
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

        // Keeps Impact/Explore/Activity's counts fresh without ever computing them
        // inline on a page request (see ImpactController for why that caused 504s).
        // Skipped while a GBIF import is running: its distinct-country_code query was
        // observed taking 10-15 minutes on the grown locality_groups table (a full scan,
        // no index covers the combination of filters well) and competing for I/O with the
        // import's own batches every single hour — a bad trade during a days-long import
        // for numbers that are only cosmetic (Impact/Explore/Activity pages, not correctness-
        // critical). Self-resuming: no manual re-enable needed once the import finishes.
        $schedule->command(RefreshImpactCounts::class)
            ->hourly()
            ->withoutOverlapping()
            ->skip(fn () => !empty(\Illuminate\Support\Facades\Cache::get(GbifMonthlyRefresh::STATUS_KEY)['running'] ?? false));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
