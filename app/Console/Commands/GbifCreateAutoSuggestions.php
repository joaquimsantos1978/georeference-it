<?php

namespace App\Console\Commands;

use App\Models\LocalityGroup;
use App\Services\GbifService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GbifCreateAutoSuggestions extends Command
{
    protected $signature = 'gbif:auto-suggest
                            {--country= : Limit to a specific country code (e.g. PT)}
                            {--limit=0 : Stop after N groups (0 = all)}
                            {--batch=500 : Groups per batch}';

    protected $description = 'Create system auto-suggestions for locality groups that have georeferenced siblings but no suggestion yet';

    public function handle(GbifService $gbif): int
    {
        $country = $this->option('country') ? strtoupper($this->option('country')) : null;
        $limit   = (int) $this->option('limit');
        $batch   = (int) $this->option('batch');

        // Get eligible group IDs via JOIN on occurrences table (avoids correlated subqueries on 43M groups)
        // Logic: groups that have at least one gbif_georeferenced occurrence AND no existing suggestion
        $idQuery = DB::table('occurrences as o')
            ->select('o.locality_group_id')
            ->leftJoin('georef_suggestions as gs', 'gs.locality_group_id', '=', 'o.locality_group_id')
            ->where('o.georef_status', 'gbif_georeferenced')
            ->whereNotNull('o.locality_group_id')
            ->whereNull('gs.id')
            ->when($country, fn($q) => $q->join('locality_groups as lg', 'lg.id', '=', 'o.locality_group_id')
                ->where('lg.country_code', $country))
            ->distinct();

        $total = (clone $idQuery)->count('o.locality_group_id');

        if ($total === 0) {
            $this->info('No eligible groups found.');
            return self::SUCCESS;
        }

        $this->info("Eligible groups: {$total}" . ($limit ? " (processing first {$limit})" : ''));

        $processed = 0;
        $created   = 0;
        $offset    = 0;

        while (true) {
            $ids = (clone $idQuery)->offset($offset)->limit($batch)->pluck('o.locality_group_id');

            if ($ids->isEmpty()) break;

            $groups = LocalityGroup::whereIn('id', $ids)->get();

            foreach ($groups as $group) {
                $before = $group->suggestions()->count();
                $gbif->createAutoSuggestions($group);
                $after  = $group->fresh()->suggestions()->count();

                if ($after > $before) $created++;
                $processed++;

                if ($processed % 500 === 0) {
                    $this->line("  {$processed} processed, {$created} with new suggestions...");
                }

                if ($limit > 0 && $processed >= $limit) {
                    $this->info("Done. Processed {$processed} groups, created suggestions for {$created}.");
                    return self::SUCCESS;
                }
            }

            $offset += $batch;
        }

        $this->info("Done. Processed {$processed} groups, created suggestions for {$created}.");
        return self::SUCCESS;
    }
}
