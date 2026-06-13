<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GbifResetConsistency extends Command
{
    protected $signature = 'gbif:reset-consistency
                            {--status=inconsistent : Which consistency_status groups to reset (inconsistent|all)}';

    protected $description = 'Delete system consistency-check suggestions and reset group status to unchecked';

    public function handle(): int
    {
        $status = $this->option('status');

        $this->info('Deleting system GBIF_CONSISTENCY_CHECK suggestions...');

        $query = DB::table('georef_suggestions')
            ->whereNull('user_id')
            ->where('georeference_sources', 'GBIF_CONSISTENCY_CHECK');

        if ($status !== 'all') {
            $query->whereIn('locality_group_id', function ($sub) use ($status) {
                $sub->select('id')->from('locality_groups')->where('consistency_status', $status);
            });
        }

        $ids = $query->pluck('id');
        $this->line("  Found {$ids->count()} suggestions to delete");

        DB::table('georef_suggestion_exclusions')->whereIn('suggestion_id', $ids)->delete();
        DB::table('georef_suggestions')->whereIn('id', $ids)->delete();

        $this->info('Resetting consistency_status to unchecked...');
        $n = DB::table('locality_groups')
            ->where('consistency_status', $status === 'all' ? '!=' : '=', $status === 'all' ? 'unchecked' : $status)
            ->update(['consistency_status' => 'unchecked']);
        $this->line("  Reset {$n} groups");

        $this->info('Recalculating pending_count...');
        DB::statement("
            UPDATE locality_groups lg
            JOIN (
                SELECT locality_group_id,
                    SUM(georef_status IN ('has_suggestion','conflicted')) AS p
                FROM occurrences
                WHERE locality_group_id IS NOT NULL
                GROUP BY locality_group_id
            ) c ON c.locality_group_id = lg.id
            SET lg.pending_count = c.p
        ");

        $this->info('Done.');
        return self::SUCCESS;
    }
}
