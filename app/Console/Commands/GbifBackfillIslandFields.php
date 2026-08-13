<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GbifBackfillIslandFields extends Command
{
    protected $signature = 'gbif:backfill-island-fields';

    protected $description = 'One-time backfill of locality_groups.island/island_group from occurrences '
        . '(already correctly populated there) for every group that existed before the island/island_group '
        . 'columns were added to locality_groups — future imports keep these current on their own via '
        . 'GbifImportDownload\'s ON DUPLICATE KEY UPDATE, this just catches everything already in the table';

    private function getCheckpoint(): ?int
    {
        return Cache::get('gbif:progress:backfill_island_fields');
    }

    private function setCheckpoint(int $throughId): void
    {
        Cache::forever('gbif:progress:backfill_island_fields', $throughId);
    }

    public function handle(): int
    {
        $bounds = DB::selectOne('SELECT MIN(id) AS min_id, MAX(id) AS max_id FROM locality_groups');

        if (!$bounds || $bounds->min_id === null) {
            $this->info('No locality groups — nothing to backfill.');
            return self::SUCCESS;
        }

        // Bounded by locality_group_id range (indexed FK on occurrences), not a WHERE IN() list —
        // the same lesson already paid for in gbif:backfill-ungeoreferenced (a 50,000-id IN() list
        // against this same table produced a 5.6-hour stuck batch in production).
        $batchSize    = 200000;
        $minId        = (int) $bounds->min_id;
        $maxId        = (int) $bounds->max_id;
        $totalBatches = (int) ceil((($maxId - $minId) + 1) / $batchSize);
        $resumeFrom   = $this->getCheckpoint();
        $startId      = $resumeFrom !== null ? $resumeFrom + 1 : $minId;
        $batchNum     = $resumeFrom !== null ? (int) floor(($resumeFrom - $minId + 1) / $batchSize) : 0;
        $updated      = 0;

        if ($resumeFrom !== null) {
            $this->info("Resuming from locality_group id {$startId} (batch " . ($batchNum + 1) . "/{$totalBatches})");
        }

        $this->info('Backfilling island/island_group on locality_groups from occurrences...');

        for ($from = $startId; $from <= $maxId; $from += $batchSize) {
            $to = min($from + $batchSize - 1, $maxId);
            $batchNum++;

            // Same MIN(NULLIF(...)) aggregation GbifImportDownload uses when a group is first
            // created, applied retroactively to groups that already existed before these columns
            // did. Only touches groups that actually have a value to set — no point writing NULL
            // over NULL for the (common) case of a group with no island data at all.
            $updated += DB::affectingStatement("
                UPDATE locality_groups lg
                JOIN (
                    SELECT locality_group_id,
                        MIN(NULLIF(island, ''))       AS island,
                        MIN(NULLIF(island_group, '')) AS island_group
                    FROM occurrences
                    WHERE deleted_at IS NULL
                      AND locality_group_id BETWEEN {$from} AND {$to}
                    GROUP BY locality_group_id
                ) o ON o.locality_group_id = lg.id
                SET lg.island = o.island, lg.island_group = o.island_group
                WHERE lg.id BETWEEN {$from} AND {$to}
                  AND lg.deleted_at IS NULL
                  AND (o.island IS NOT NULL OR o.island_group IS NOT NULL)
            ");

            $this->setCheckpoint($to);

            if ($batchNum % 10 === 0 || $batchNum === $totalBatches) {
                $this->line("  Batch {$batchNum}/{$totalBatches} done (locality_group id {$from}-{$to}) — groups updated so far: {$updated}");
            }

            usleep(200000);
        }

        $this->info("Done. {$updated} locality_groups updated with island/island_group.");
        return self::SUCCESS;
    }
}
