<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateGroupCounters extends Command
{
    protected $signature   = 'georef:recalculate-counters {--chunk=5000}';
    protected $description = 'Bulk-recalculate occurrence_count, ungeoreferenced_count, pending_count, validated_count on all locality_groups';

    public function handle(): int
    {
        $chunk = (int) $this->option('chunk');
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
