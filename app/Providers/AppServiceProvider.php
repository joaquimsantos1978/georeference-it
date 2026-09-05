<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('orcid', \SocialiteProviders\Orcid\Provider::class);
        });

        // Cap how long any single query from a web request can run (MariaDB, seconds).
        // Without this, a slow /georef/next or API query during a GBIF monthly refresh
        // keeps executing server-side long after nginx has given up on the request —
        // orphaned multi-minute queries then pile up and starve both the site and the
        // import itself (this is what turned a transient slow window into an outage).
        // CLI is exempt on purpose: the import, queue workers and artisan commands all
        // legitimately run long queries.
        if (!$this->app->runningInConsole()) {
            try {
                DB::statement('SET SESSION max_statement_time = 20');
            } catch (\Throwable $e) {
                Log::warning('Could not set max_statement_time: ' . $e->getMessage());
            }
        }
    }
}