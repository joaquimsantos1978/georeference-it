<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateGroupCounters extends Command
{
    protected $signature   = 'georef:recalculate-counters {--chunk=5000} {--fast : Aggregate into temp tables first (much faster on large datasets)}';
    protected $description = 'Bulk-recalculate occurrence_count, ungeoreferenced_count, pending_count, validated_count on all locality_groups';

    public function handle(): int
    {
        if ($this->option('fast')) {
            return $this->handleFast();
        }
        return $this->handleChunked((int) $this->option('chunk'));
    }

    private function handleFast(): int
    {
        $this->info('Step 1/3: Aggregating occurrences into temp table...');
        DB::statement('DROP TEMPORARY TABLE IF EXISTS tmp_occ_counts');
        DB::statement('
            CREATE TEMPORARY TABLE tmp_occ_counts AS
            SELECT
                locality_group_id,
                COUNT(*)                               AS total,
                SUM(georef_status = \'ungeoreferenced\') AS ungeoreferenced,
                SUM(georef_status = \'validated\')       AS validated
            FROM occurrences
            GROUP BY locality_group_id
        ');

        $this->info('Step 2/3: Aggregating suggestions into temp table...');
        DB::statement('DROP TEMPORARY TABLE IF EXISTS tmp_sug_counts');
        DB::statement('
            CREATE TEMPORARY TABLE tmp_sug_counts AS
            SELECT locality_group_id, COUNT(*) AS pending
            FROM georef_suggestions
            WHERE status = \'pending\'
            GROUP BY locality_group_id
        ');

        $this->info('Step 3/3: Updating locality_groups...');
        DB::statement('
            UPDATE locality_groups lg
            JOIN tmp_occ_counts occ ON occ.locality_group_id = lg.id
            LEFT JOIN tmp_sug_counts sug ON sug.locality_group_id = lg.id
            SET
                lg.occurrence_count      = occ.total,
                lg.ungeoreferenced_count = occ.ungeoreferenced,
                lg.validated_count       = occ.validated,
                lg.pending_count         = COALESCE(sug.pending, 0),
                lg.updated_at            = NOW()
        ');

        DB::statement('DROP TEMPORARY TABLE IF EXISTS tmp_occ_counts');
        DB::statement('DROP TEMPORARY TABLE IF EXISTS tmp_sug_counts');

        $this->info('Done. Run php artisan georef:warm-stats to refresh the cache.');
        return 0;
    }

    private function handleChunked(int $chunk): int
    {
        $total = DB::table('locality_groups')->count();
        $bar   = $this->output->createProgressBar($total);
        $bar->start();

        $minId = 0;
        do {
            $ids = DB::table('locality_groups')
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
                        SUM(georef_status = 'validated')            AS validated
                    FROM occurrences
                    WHERE locality_group_id IN (" . $ids->implode(',') . ")
                    GROUP BY locality_group_id
                ) occ ON occ.locality_group_id = lg.id
                LEFT JOIN (
                    SELECT locality_group_id, COUNT(*) AS pending
                    FROM georef_suggestions
                    WHERE locality_group_id IN (" . $ids->implode(',') . ")
                      AND status = 'pending'
                    GROUP BY locality_group_id
                ) sug ON sug.locality_group_id = lg.id
                SET
                    lg.occurrence_count      = occ.total,
                    lg.ungeoreferenced_count = occ.ungeoreferenced,
                    lg.validated_count       = occ.validated,
                    lg.pending_count         = COALESCE(sug.pending, 0),
                    lg.updated_at            = NOW()
                WHERE lg.id IN (" . $ids->implode(',') . ")
            ");

            $bar->advance($ids->count());
            $minId = $ids->last();
        } while ($ids->count() === $chunk);

        $bar->finish();
        $this->newLine();
        $this->info('Done. Run php artisan georef:warm-stats to refresh the cache.');
        return 0;
    }
}
