<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GbifReconcileSuggestions extends Command
{
    protected $signature = 'gbif:reconcile-suggestions';

    protected $description = 'Clean up suggestions orphaned by a locality group re-hash, and attach '
        . 'newly-arrived occurrences to whatever suggestion their (possibly new) group already has';

    // Every monthly refresh re-associates each occurrence with locality_group_id = VALUES(...)
    // (see GbifImportDownload::processStaging()) — if GBIF corrects an occurrence's published
    // locality text (e.g. "Portugal" -> "Sintra, Portugal"), it moves to a different group_hash.
    // Two things go stale when that happens, and nothing else in the pipeline fixes either:
    //   1. The suggestion it left behind still points at the old group. If that group is now
    //      empty, counters_update above already soft-deleted it, but the orphaned suggestion
    //      (and any votes/exclusions on it) just sit there forever.
    //   2. The occurrence itself lands in its new group as 'ungeoreferenced' (the ON DUPLICATE
    //      KEY UPDATE only carries over 'validated'/'has_suggestion'/'conflicted' when the group
    //      is unchanged). If that new group already has a live suggestion from some other
    //      occurrence, gbif:auto-suggest will never touch it — it explicitly skips any group
    //      that already has a suggestion — so this occurrence would be stuck showing
    //      'ungeoreferenced' next to siblings that already have one.
    private function getCheckpoint(): ?int
    {
        return Cache::get('gbif:progress:reconcile_suggestions');
    }

    private function setCheckpoint(int $throughId): void
    {
        Cache::forever('gbif:progress:reconcile_suggestions', $throughId);
    }

    public function handle(): int
    {
        $bounds = DB::selectOne('SELECT MIN(id) AS min_id, MAX(id) AS max_id FROM locality_groups');

        if (!$bounds || $bounds->min_id === null) {
            $this->info('No locality groups — nothing to reconcile.');
            return self::SUCCESS;
        }

        $batchSize    = 200000;
        $minId        = (int) $bounds->min_id;
        $maxId        = (int) $bounds->max_id;
        $totalBatches = (int) ceil((($maxId - $minId) + 1) / $batchSize);
        $resumeFrom   = $this->getCheckpoint();
        $startId      = $resumeFrom !== null ? $resumeFrom + 1 : $minId;
        $batchNum     = $resumeFrom !== null ? (int) floor(($resumeFrom - $minId + 1) / $batchSize) : 0;
        $orphansDeleted = 0;
        $reconciled     = 0;

        if ($resumeFrom !== null) {
            $this->info("Resuming from locality_group id {$startId} (batch " . ($batchNum + 1) . "/{$totalBatches})");
        }

        $this->info('Reconciling suggestions against this cycle\'s locality group re-hashing...');

        for ($from = $startId; $from <= $maxId; $from += $batchSize) {
            $to = min($from + $batchSize - 1, $maxId);
            $batchNum++;

            // 1. Orphaned suggestions: the group they point at has already been soft-deleted
            // (counters_update marks a group deleted_at once its occurrence_count hits 0).
            // georef_validations and georef_suggestion_exclusions both cascadeOnDelete() on
            // suggestion_id, so deleting the suggestion row is enough to clean up both.
            $orphansDeleted += DB::affectingStatement("
                DELETE gs FROM georef_suggestions gs
                JOIN locality_groups lg ON lg.id = gs.locality_group_id
                WHERE lg.deleted_at IS NOT NULL
                  AND lg.id BETWEEN {$from} AND {$to}
            ");

            // 2. Occurrences that landed 'ungeoreferenced' in a group whose suggestion is
            // already validated — same outcome validateSuggestion() gives every occurrence
            // in the group at the moment it's validated, applied retroactively to whoever
            // joined afterward.
            $reconciled += DB::affectingStatement("
                UPDATE occurrences o
                JOIN locality_groups lg ON lg.id = o.locality_group_id
                SET o.georef_status = 'validated', o.updated_at = NOW()
                WHERE o.georef_status = 'ungeoreferenced'
                  AND o.deleted_at IS NULL
                  AND o.locality_group_id BETWEEN {$from} AND {$to}
                  AND EXISTS (
                      SELECT 1 FROM georef_suggestions gs
                      WHERE gs.locality_group_id = lg.id AND gs.status = 'validated'
                  )
            ");

            // 3. Whatever's left 'ungeoreferenced' whose group has any other live suggestion
            // (pending) — matches what createAutoSuggestions() does for a group it processes,
            // just for an occurrence that joined after that already happened.
            $reconciled += DB::affectingStatement("
                UPDATE occurrences o
                SET o.georef_status = 'has_suggestion', o.updated_at = NOW()
                WHERE o.georef_status = 'ungeoreferenced'
                  AND o.deleted_at IS NULL
                  AND o.locality_group_id BETWEEN {$from} AND {$to}
                  AND EXISTS (
                      SELECT 1 FROM georef_suggestions gs
                      WHERE gs.locality_group_id = o.locality_group_id AND gs.status != 'rejected'
                  )
            ");

            // Steps 2/3 above change georef_status for some occurrences in this batch, so
            // this cycle's counters_update run (which already happened) is now stale for
            // exactly these groups — recompute the same way it does, scoped to this batch.
            DB::statement("
                UPDATE locality_groups lg
                JOIN (
                    SELECT locality_group_id,
                        COUNT(*) AS total,
                        SUM(georef_status IN ('has_suggestion', 'conflicted')) AS pending,
                        SUM(georef_status = 'has_suggestion') AS has_suggestion,
                        SUM(georef_status = 'conflicted') AS conflicted,
                        SUM(georef_status = 'validated') AS validated,
                        SUM(georef_status = 'ungeoreferenced') AS ungeoreferenced,
                        SUM(georef_status = 'gbif_georeferenced') AS gbif_georeferenced,
                        SUM(georef_status = 'gbif_reviewed') AS gbif_reviewed
                    FROM occurrences
                    WHERE locality_group_id BETWEEN {$from} AND {$to}
                      AND deleted_at IS NULL
                    GROUP BY locality_group_id
                ) c ON c.locality_group_id = lg.id
                SET
                    lg.occurrence_count         = c.total,
                    lg.pending_count            = c.pending,
                    lg.has_suggestion_count     = c.has_suggestion,
                    lg.conflicted_count         = c.conflicted,
                    lg.validated_count          = c.validated,
                    lg.ungeoreferenced_count    = c.ungeoreferenced,
                    lg.gbif_georeferenced_count = c.gbif_georeferenced,
                    lg.gbif_reviewed_count      = c.gbif_reviewed,
                    lg.updated_at               = NOW()
                WHERE lg.id BETWEEN {$from} AND {$to}
            ");

            $this->setCheckpoint($to);

            if ($batchNum % 10 === 0 || $batchNum === $totalBatches) {
                $this->line("  Batch {$batchNum}/{$totalBatches} done (locality_group id {$from}-{$to}) — orphans deleted: {$orphansDeleted}, occurrences reconciled: {$reconciled}");
            }

            usleep(200000);
        }

        $this->info("Done. Orphaned suggestions deleted: {$orphansDeleted}. Occurrences reconciled: {$reconciled}.");
        return self::SUCCESS;
    }
}
