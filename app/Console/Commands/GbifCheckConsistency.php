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
                            {--min-id=0 : Start from this locality_group id (inclusive)}
                            {--max-id=0 : Stop at this locality_group id (inclusive, 0 = no limit)}
                            {--recheck : Re-check groups already marked consistent or inconsistent}';

    protected $description = 'Check consistency of existing georeferenced occurrences within each locality group';

    public function handle(GbifService $gbif): int
    {
        // Groups where at least some occurrences are georeferenced by GBIF
        // (occurrence_count > ungeoreferenced_count means not all are ungeoreferenced)
        $query = LocalityGroup::query()
            ->whereRaw('occurrence_count > ungeoreferenced_count')
            ->where('occurrence_count', '>', 0);

        if (!$this->option('recheck')) {
            $query->where('consistency_status', 'unchecked');
        }

        if ($country = $this->option('country')) {
            $query->where('country_code', strtoupper($country));
        }

        if ($minId = (int) $this->option('min-id')) {
            $query->where('id', '>=', $minId);
        }

        if ($maxId = (int) $this->option('max-id')) {
            $query->where('id', '<=', $maxId);
        }

        $limit = (int) $this->option('limit');
        $this->info('Starting consistency check' . ($limit ? " (first {$limit} groups)" : '') . '...');

        $processed    = 0;
        $consistent   = 0;
        $inconsistent = 0;

        // Load once — avoids a DB query per group inside checkConsistency
        $threshold = (int) \App\Models\PlatformSetting::get('inconsistency_distance_m', 5000);

        $pendingConsistentIds = [];

        $flush = function () use (&$pendingConsistentIds) {
            if ($pendingConsistentIds) {
                LocalityGroup::whereIn('id', $pendingConsistentIds)
                    ->update(['consistency_status' => 'consistent']);
                $pendingConsistentIds = [];
            }
        };

        $query->chunkById(500, function ($groups) use ($gbif, $threshold, $limit, $flush, &$processed, &$consistent, &$inconsistent, &$pendingConsistentIds) {
            foreach ($groups as $group) {
                $result = $gbif->checkConsistency($group, $threshold, $pendingConsistentIds);

                match ($result) {
                    'consistent'   => $consistent++,
                    'inconsistent' => $inconsistent++,
                    default        => null,
                };

                $processed++;

                if (count($pendingConsistentIds) >= 500) {
                    $flush();
                }

                if ($processed % 500 === 0) {
                    $this->line("  {$processed} checked — consistent: {$consistent}, inconsistent: {$inconsistent}");
                }

                if ($limit > 0 && $processed >= $limit) {
                    $flush();
                    return false;
                }
            }

            $flush();
        });

        $flush();

        $this->info("Done. {$processed} groups checked.");
        $this->line("  Consistent:   {$consistent}");
        $this->line("  Inconsistent: {$inconsistent} (competing suggestions created for community review)");

        return self::SUCCESS;
    }
}
