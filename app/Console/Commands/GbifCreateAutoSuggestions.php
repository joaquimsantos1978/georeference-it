<?php

namespace App\Console\Commands;

use App\Models\LocalityGroup;
use App\Services\GbifService;
use Illuminate\Console\Command;

class GbifCreateAutoSuggestions extends Command
{
    protected $signature = 'gbif:auto-suggest
                            {--country= : Limit to a specific country code (e.g. PT)}
                            {--limit=0 : Stop after N groups (0 = all)}';

    protected $description = 'Create system auto-suggestions for locality groups that have georeferenced siblings but no suggestion yet';

    public function handle(GbifService $gbif): int
    {
        $query = LocalityGroup::query()
            ->whereDoesntHave('suggestions')
            ->whereHas('occurrences', fn($q) => $q->where('georef_status', 'ungeoreferenced'))
            ->whereHas('occurrences', fn($q) => $q->where('georef_status', 'gbif_georeferenced'));

        if ($country = $this->option('country')) {
            $query->where('country_code', strtoupper($country));
        }

        $total = $query->count();

        if ($total === 0) {
            $this->info('No eligible groups found.');
            return self::SUCCESS;
        }

        $limit = (int) $this->option('limit');
        $this->info("Eligible groups: {$total}" . ($limit ? " (processing first {$limit})" : ''));

        $processed  = 0;
        $created    = 0;

        $query->chunkById(200, function ($groups) use ($gbif, $limit, &$processed, &$created) {
            foreach ($groups as $group) {
                $before = $group->suggestions()->count();
                $gbif->createAutoSuggestions($group);
                $after  = $group->fresh()->suggestions()->count();

                if ($after > $before) {
                    $created++;
                }

                $processed++;

                if ($processed % 100 === 0) {
                    $this->line("  {$processed} processed, {$created} with new suggestions...");
                }

                if ($limit > 0 && $processed >= $limit) {
                    return false; // stop chunk iteration
                }
            }
        });

        $this->info("Done. Processed {$processed} groups, created suggestions for {$created}.");
        return self::SUCCESS;
    }
}
