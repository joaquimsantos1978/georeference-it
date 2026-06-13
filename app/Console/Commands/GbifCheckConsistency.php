<?php

namespace App\Console\Commands;

use App\Models\LocalityGroup;
use App\Services\GbifService;
use Illuminate\Console\Command;

class GbifCheckConsistency extends Command
{
    protected $signature = 'gbif:check-consistency
                            {--country= : Limit to a specific country code (e.g. PT)}
                            {--limit=0 : Stop after N groups (0 = all)}
                            {--recheck : Re-check groups already marked consistent or inconsistent}';

    protected $description = 'Check consistency of existing georeferenced occurrences within each locality group';

    public function handle(GbifService $gbif): int
    {
        $query = LocalityGroup::query()
            ->whereHas('occurrences', fn($q) => $q->where('georef_status', 'gbif_georeferenced'));

        if (!$this->option('recheck')) {
            $query->where('consistency_status', 'unchecked');
        }

        if ($country = $this->option('country')) {
            $query->where('country_code', strtoupper($country));
        }

        $total = $query->count();

        if ($total === 0) {
            $this->info('No groups to check.');
            return self::SUCCESS;
        }

        $limit = (int) $this->option('limit');
        $this->info("Groups to check: {$total}" . ($limit ? " (first {$limit})" : ''));

        $processed   = 0;
        $consistent  = 0;
        $inconsistent = 0;

        $query->chunkById(200, function ($groups) use ($gbif, $limit, &$processed, &$consistent, &$inconsistent) {
            foreach ($groups as $group) {
                $result = $gbif->checkConsistency($group);

                match ($result) {
                    'consistent'   => $consistent++,
                    'inconsistent' => $inconsistent++,
                    default        => null,
                };

                $processed++;

                if ($processed % 500 === 0) {
                    $this->line("  {$processed} checked — consistent: {$consistent}, inconsistent: {$inconsistent}");
                }

                if ($limit > 0 && $processed >= $limit) {
                    return false;
                }
            }
        });

        $this->info("Done. {$processed} groups checked.");
        $this->line("  Consistent:   {$consistent}");
        $this->line("  Inconsistent: {$inconsistent} (competing suggestions created for community review)");

        return self::SUCCESS;
    }
}
