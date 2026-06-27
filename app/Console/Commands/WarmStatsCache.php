<?php

namespace App\Console\Commands;

use App\Http\Controllers\StatsController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class WarmStatsCache extends Command
{
    protected $signature   = 'georef:warm-stats';
    protected $description = 'Compute georeferencing stats and store in cache forever (no expiry)';

    public function handle(): int
    {
        $this->info('Computing stats…');
        $data = (new StatsController)->compute();
        Cache::forever('stats.georef', $data);
        $this->info('Done. Cache key stats.georef stored permanently.');
        return 0;
    }
}
