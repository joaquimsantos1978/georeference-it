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

        // Process using cursor over locality_groups, skipping those with suggestions
        // Filter by pending_count=0 (fast index scan) then check occurrences only for candidates
        $processed = 0;
        $created   = 0;
        $lastId    = 0;

        $this->info('Starting auto-suggest (processing in batches)...');

        while (true) {
            // Fetch a batch of groups with no suggestions yet (pending_count=0 as fast pre-filter)
            $groups = LocalityGroup::where('id', '>', $lastId)
                ->where('pending_count', 0)
                ->where('validated_count', 0)
                ->when($country, fn($q) => $q->where('country_code', $country))
                ->orderBy('id')
                ->limit($batch)
                ->get();

            if ($groups->isEmpty()) break;

            $lastId = $groups->last()->id;

            // Get group IDs that have gbif_georeferenced occurrences (one JOIN per batch, not per row)
            $eligibleIds = DB::table('occurrences')
                ->select('locality_group_id')
                ->whereIn('locality_group_id', $groups->pluck('id'))
                ->where('georef_status', 'gbif_georeferenced')
                ->distinct()
                ->pluck('locality_group_id')
                ->flip();

            foreach ($groups as $group) {
                if (!isset($eligibleIds[$group->id])) continue;

                $before = $group->suggestions()->count();
                $gbif->createAutoSuggestions($group);
                $after  = $group->fresh()->suggestions()->count();

                if ($after > $before) $created++;
                $processed++;

                if ($processed % 500 === 0) {
                    $this->line("  {$processed} processed, {$created} with new suggestions... (last id: {$lastId})");
                }

                if ($limit > 0 && $processed >= $limit) {
                    $this->info("Done. Processed {$processed} groups, created suggestions for {$created}.");
                    return self::SUCCESS;
                }
            }
        }

        $this->info("Done. Processed {$processed} groups, created suggestions for {$created}.");
        return self::SUCCESS;
    }
}
