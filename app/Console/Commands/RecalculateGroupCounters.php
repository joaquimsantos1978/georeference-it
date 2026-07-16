<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateGroupCounters extends Command
{
    protected $signature   = 'georef:recalculate-counters {--chunk=5000} {--fast : Aggregate occurrences in one pass via a temp table instead of re-scanning per chunk — less total DB work, but that one aggregation pass is a single long-running query with no progress/resume; on a very large occurrences table plain --chunk may finish sooner in practice and can be killed/resumed safely}';
    protected $description = 'Bulk-recalculate the full per-status occurrence breakdown on all locality_groups';

    public function handle(): int
    {
        if ($this->option('fast')) {
            return $this->handleFast();
        }
        return $this->handleChunked((int) $this->option('chunk'));
    }

    private function handleFast(): int
    {
        // pending_count is derived from occurrences.georef_status here, same as everywhere
        // else that maintains this column (LocalityGroup::recalculateCounters(),
        // GbifImportDownload's batch update) — a previous version of this command counted
        // georef_suggestions rows with status='pending' instead, which isn't the same
        // number (a group's pending_count is meant to be an occurrence count, not a
        // suggestion count) and could disagree with the value every other write path sets.
        $this->info('Step 1/2: Aggregating occurrences into temp table...');
        DB::statement('DROP TEMPORARY TABLE IF EXISTS tmp_occ_counts');
        DB::statement('
            CREATE TEMPORARY TABLE tmp_occ_counts AS
            SELECT
                locality_group_id,
                COUNT(*)                                  AS total,
                SUM(georef_status = \'ungeoreferenced\')    AS ungeoreferenced,
                SUM(georef_status = \'validated\')          AS validated,
                SUM(georef_status = \'has_suggestion\')     AS has_suggestion,
                SUM(georef_status = \'conflicted\')         AS conflicted,
                SUM(georef_status = \'gbif_georeferenced\') AS gbif_georeferenced,
                SUM(georef_status = \'gbif_reviewed\')      AS gbif_reviewed
            FROM occurrences
            WHERE deleted_at IS NULL
            GROUP BY locality_group_id
        ');

        // Indexed so the batched JOIN below can seek by locality_group_id instead of
        // scanning the whole temp table on every batch.
        DB::statement('ALTER TABLE tmp_occ_counts ADD PRIMARY KEY (locality_group_id)');

        // Batched by id range against the (already-computed, local, fast) temp table —
        // NOT a single UPDATE over all of locality_groups. A single UPDATE touching every
        // row of a 90M+ row hot table holds one long-running write lock for its entire
        // duration, blocking every live recalculateCounters() call (submit/validate/etc)
        // until it finishes — observed as exactly this class of problem elsewhere in this
        // codebase (see GbifImportDownload's own batched counter-update step) before this
        // command made the same mistake. The expensive part — the full scan of `occurrences`
        // — still only happens once, in Step 1 above; this just batches the write side.
        $this->info('Step 2/2: Updating locality_groups in batches...');
        $bounds = DB::selectOne('SELECT MIN(locality_group_id) AS min_id, MAX(locality_group_id) AS max_id FROM tmp_occ_counts');

        if ($bounds && $bounds->min_id !== null) {
            $batchSize    = 200000;
            $minId        = (int) $bounds->min_id;
            $maxId        = (int) $bounds->max_id;
            $totalBatches = (int) ceil((($maxId - $minId) + 1) / $batchSize);
            $batchNum     = 0;

            for ($from = $minId; $from <= $maxId; $from += $batchSize) {
                $to = min($from + $batchSize - 1, $maxId);
                $batchNum++;

                DB::statement("
                    UPDATE locality_groups lg
                    JOIN tmp_occ_counts occ ON occ.locality_group_id = lg.id
                    SET
                        lg.occurrence_count         = occ.total,
                        lg.ungeoreferenced_count    = occ.ungeoreferenced,
                        lg.validated_count          = occ.validated,
                        lg.pending_count            = occ.has_suggestion + occ.conflicted,
                        lg.has_suggestion_count     = occ.has_suggestion,
                        lg.conflicted_count         = occ.conflicted,
                        lg.gbif_georeferenced_count = occ.gbif_georeferenced,
                        lg.gbif_reviewed_count      = occ.gbif_reviewed,
                        lg.updated_at               = NOW()
                    WHERE occ.locality_group_id BETWEEN {$from} AND {$to}
                ");

                if ($batchNum % 10 === 0 || $batchNum === $totalBatches) {
                    $this->line("  Batch {$batchNum}/{$totalBatches} done (locality_group id {$from}–{$to})");
                }

                // Brief pause between batches so queued live writes get a chance to run.
                usleep(200000);
            }
        }

        DB::statement('DROP TEMPORARY TABLE IF EXISTS tmp_occ_counts');

        $this->info('Done. Run php artisan impact:refresh-counts to refresh the cache.');
        return 0;
    }

    private function handleChunked(int $chunk): int
    {
        $total = DB::table('locality_groups')->whereNull('deleted_at')->count();
        $bar   = $this->output->createProgressBar($total);
        $bar->start();

        $minId = 0;
        do {
            $ids = DB::table('locality_groups')
                ->whereNull('deleted_at')
                ->where('id', '>', $minId)
                ->orderBy('id')
                ->limit($chunk)
                ->pluck('id');

            if ($ids->isEmpty()) break;

            DB::statement("
                UPDATE locality_groups lg
                JOIN (
                    SELECT
                        locality_group_id,
                        COUNT(*)                                    AS total,
                        SUM(georef_status = 'ungeoreferenced')      AS ungeoreferenced,
                        SUM(georef_status = 'validated')            AS validated,
                        SUM(georef_status = 'has_suggestion')       AS has_suggestion,
                        SUM(georef_status = 'conflicted')           AS conflicted,
                        SUM(georef_status = 'gbif_georeferenced')   AS gbif_georeferenced,
                        SUM(georef_status = 'gbif_reviewed')        AS gbif_reviewed
                    FROM occurrences
                    WHERE locality_group_id IN (" . $ids->implode(',') . ")
                      AND deleted_at IS NULL
                    GROUP BY locality_group_id
                ) occ ON occ.locality_group_id = lg.id
                SET
                    lg.occurrence_count         = occ.total,
                    lg.ungeoreferenced_count    = occ.ungeoreferenced,
                    lg.validated_count          = occ.validated,
                    lg.pending_count            = occ.has_suggestion + occ.conflicted,
                    lg.has_suggestion_count     = occ.has_suggestion,
                    lg.conflicted_count         = occ.conflicted,
                    lg.gbif_georeferenced_count = occ.gbif_georeferenced,
                    lg.gbif_reviewed_count      = occ.gbif_reviewed,
                    lg.updated_at               = NOW()
                WHERE lg.id IN (" . $ids->implode(',') . ")
            ");

            $bar->advance($ids->count());
            $minId = $ids->last();
        } while ($ids->count() === $chunk);

        $bar->finish();
        $this->newLine();
        $this->info('Done. Run php artisan impact:refresh-counts to refresh the cache.');
        return 0;
    }
}
