<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixStaleSuggestionStatus extends Command
{
    protected $signature = 'gbif:fix-stale-suggestion-status {--dry-run : Report the count without updating anything}';

    protected $description = 'One-off backfill: revert occurrences stuck at georef_status=has_suggestion whose '
        . 'current locality_group has zero active suggestions (caused by a locality_group_id reassignment during '
        . 'a past import that did not correctly reset georef_status — see GbifImportDownload upsert for the fix '
        . 'going forward)';

    public function handle(): int
    {
        $bounds = DB::selectOne('SELECT MIN(id) AS min_id, MAX(id) AS max_id FROM occurrences');
        if (!$bounds || $bounds->min_id === null) {
            $this->info('No occurrences to check.');
            return self::SUCCESS;
        }

        $batchSize    = 200000;
        $minId        = (int) $bounds->min_id;
        $maxId        = (int) $bounds->max_id;
        $totalBatches = (int) ceil((($maxId - $minId) + 1) / $batchSize);
        $batchNum     = 0;
        $totalFixed   = 0;
        $dryRun       = $this->option('dry-run');

        for ($from = $minId; $from <= $maxId; $from += $batchSize) {
            $to = min($from + $batchSize - 1, $maxId);
            $batchNum++;

            if ($dryRun) {
                $count = DB::table('occurrences as o')
                    ->where('o.georef_status', 'has_suggestion')
                    ->whereBetween('o.id', [$from, $to])
                    ->whereNotExists(function ($q) {
                        $q->selectRaw(1)
                            ->from('georef_suggestions as gs')
                            ->whereColumn('gs.locality_group_id', 'o.locality_group_id')
                            ->where('gs.status', '!=', 'rejected');
                    })
                    ->count();
                $totalFixed += $count;
            } else {
                $totalFixed += DB::affectingStatement("
                    UPDATE occurrences o
                    SET o.georef_status = IF(o.gbif_decimal_latitude IS NOT NULL, 'gbif_georeferenced', 'ungeoreferenced')
                    WHERE o.georef_status = 'has_suggestion'
                      AND o.id BETWEEN {$from} AND {$to}
                      AND NOT EXISTS (
                          SELECT 1 FROM georef_suggestions gs
                          WHERE gs.locality_group_id = o.locality_group_id
                            AND gs.status != 'rejected'
                      )
                ");
            }

            if ($batchNum % 10 === 0 || $batchNum === $totalBatches) {
                $this->line("  Batch {$batchNum}/{$totalBatches} done (occurrence id {$from}–{$to}) — " . ($dryRun ? 'would fix' : 'fixed') . " so far: {$totalFixed}");
            }

            usleep(200000);
        }

        $this->info(($dryRun ? 'Would fix' : 'Fixed') . " {$totalFixed} occurrences stuck at has_suggestion with no active suggestion in their current group.");

        return self::SUCCESS;
    }
}
