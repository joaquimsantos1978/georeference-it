<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillActivityLog extends Command
{
    protected $signature   = 'activity:backfill {--truncate : Truncate before backfill}';
    protected $description = 'Populate activity_log from existing georef_suggestions and georef_validations';

    public function handle(): int
    {
        if ($this->option('truncate')) {
            DB::table('activity_log')->truncate();
            $this->info('Truncated activity_log.');
        }

        $this->info('Backfilling georef events...');
        DB::statement("
            INSERT IGNORE INTO activity_log (type, user_id, locality_group_id, occ_count, lat, lng, uncertainty_m, remarks, country_code, location_label, created_at)
            SELECT
                'georef',
                gs.user_id,
                gs.locality_group_id,
                COUNT(*),
                MIN(gs.decimal_latitude),
                MIN(gs.decimal_longitude),
                MIN(gs.coordinate_uncertainty_m),
                MIN(gs.georeference_remarks),
                lg.country_code,
                TRIM(CONCAT_WS(', ',
                    NULLIF(lg.verbatim_locality, ''),
                    NULLIF(lg.municipality, ''),
                    NULLIF(lg.county, '')
                )),
                MIN(gs.created_at)
            FROM georef_suggestions gs
            JOIN locality_groups lg ON lg.id = gs.locality_group_id
            WHERE gs.locality_group_id IS NOT NULL
            GROUP BY gs.locality_group_id, gs.user_id, lg.country_code, lg.verbatim_locality, lg.municipality, lg.county
        ");
        $this->info('  Done.');

        $this->info('Backfilling validation events...');
        DB::statement("
            INSERT IGNORE INTO activity_log (type, user_id, locality_group_id, occ_count, country_code, location_label, created_at)
            SELECT
                CASE gv.vote WHEN 'agree' THEN 'validation_agree' WHEN 'disagree' THEN 'validation_disagree' ELSE 'validation_abstain' END,
                gv.user_id,
                gs.locality_group_id,
                1,
                lg.country_code,
                TRIM(CONCAT_WS(', ',
                    NULLIF(lg.verbatim_locality, ''),
                    NULLIF(lg.municipality, ''),
                    NULLIF(lg.county, '')
                )),
                gv.created_at
            FROM georef_validations gv
            JOIN georef_suggestions gs ON gs.id = gv.suggestion_id
            JOIN locality_groups lg ON lg.id = gs.locality_group_id
            WHERE gs.locality_group_id IS NOT NULL
              AND gv.user_id != gs.user_id
        ");
        $this->info('  Done.');

        $total = DB::table('activity_log')->count();
        $this->info("activity_log now has {$total} rows.");
        return 0;
    }
}
