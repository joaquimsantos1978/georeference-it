<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GbifRelinkComments extends Command
{
    protected $signature = 'gbif:relink-comments';

    protected $description = 'Re-point locality_group_comments.locality_group_id at the current locality_groups row '
        . 'with the same group_hash — needed after a from-scratch GBIF re-import reassigns auto-increment ids';

    // Unlike georef_suggestions (which gbif:reconcile-suggestions also cleans up every cycle),
    // locality_group_comments only ever go stale on a full rebuild/restore: the monthly refresh
    // uses INSERT IGNORE and never changes an existing locality_groups.id. So this is a one-shot
    // repair run as part of the restore procedure, not something the monthly pipeline calls.
    //
    // A comment whose group_hash no longer exists in locality_groups (the human-touched locality
    // was dropped entirely from GBIF between exports) keeps its now-dangling locality_group_id and
    // is reported below rather than deleted — losing a contributor's note silently would be worse.
    public function handle(): int
    {
        $missingHash = DB::table('locality_group_comments as c')
            ->whereNotNull('c.locality_group_hash')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('locality_groups as lg')
                    ->whereColumn('lg.group_hash', 'c.locality_group_hash');
            })
            ->count();

        $nullHash = DB::table('locality_group_comments')
            ->whereNull('locality_group_hash')
            ->count();

        $relinked = DB::affectingStatement('
            UPDATE locality_group_comments c
            JOIN locality_groups lg ON lg.group_hash = c.locality_group_hash
            SET c.locality_group_id = lg.id
            WHERE c.locality_group_id <> lg.id
        ');

        $this->info("Re-linked {$relinked} comment(s) to their current locality group.");

        if ($nullHash > 0) {
            $this->warn("{$nullHash} comment(s) have no locality_group_hash — created before the shadow "
                . 'column existed and not backfilled; left untouched.');
        }

        if ($missingHash > 0) {
            $this->warn("{$missingHash} comment(s) reference a group_hash that no longer exists in "
                . 'locality_groups (locality gone from GBIF) — left pointing at their stale id.');
        }

        return self::SUCCESS;
    }
}
