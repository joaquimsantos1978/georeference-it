<?php

namespace App\Console\Commands;

use App\Models\LocalityGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GbifBackfillUngeoreferencedCount extends Command
{
    protected $signature = 'gbif:backfill-ungeoreferenced
                            {--country= : Limit to a specific country code}
                            {--chunk=3000 : Locality groups per batch (drives an IN() list against occurrences — kept small on purpose, see below)}';

    protected $description = 'Backfill ungeoreferenced_count on locality_groups from occurrences table';

    // Same substep-reporting pattern as GbifImportDownload::markProgress() — this step has
    // no per-batch feedback of its own, so gbif:refresh-heartbeat's 2-hourly email showed
    // "Step 6/7" frozen for the entire run (observed 8+ hours with no visible movement),
    // indistinguishable from an actual hang.
    private function markProgress(string $step, array $counters = []): void
    {
        $status = Cache::get(GbifMonthlyRefresh::STATUS_KEY, []);
        $status['substep']    = $step;
        $status['counters']   = $counters;
        $status['updated_at'] = now();
        Cache::forever(GbifMonthlyRefresh::STATUS_KEY, $status);
    }

    public function handle(): int
    {
        $country = $this->option('country') ? strtoupper($this->option('country')) : null;
        $chunk   = (int) $this->option('chunk');

        if ($country) {
            $this->info("Backfilling ungeoreferenced_count for country: {$country}");
            $this->markProgress("Backfilling ungeoreferenced counts: {$country}");
            $this->backfillCountry($country, $chunk);
        } else {
            // Process country by country to keep each UPDATE small. A plain
            // ->distinct()->whereNull('deleted_at') here forces a full scan of
            // locality_groups (118M+ rows) — the same "any extra WHERE breaks the loose
            // index scan" cost already documented on LocalityGroup::activeCountryCodes(),
            // which solves it (cached, index-only scan + cheap per-code existence check)
            // — reuse it instead of re-paying that cost here.
            $countries = LocalityGroup::activeCountryCodes();
            $total     = $countries->count() + 1; // +1 for the null-country_code pass below

            $this->info("Backfilling {$total} countries...");

            $groupsUpdated = 0;
            foreach ($countries as $i => $cc) {
                $this->line("  {$cc}...");
                $this->markProgress(
                    "Backfilling ungeoreferenced counts: {$cc} (" . ($i + 1) . "/{$total})",
                    ['locality_groups_updated' => $groupsUpdated]
                );
                $groupsUpdated += $this->backfillCountry($cc, $chunk);
            }

            // Also handle groups with null country_code
            $this->line("  (null country_code)...");
            $this->markProgress(
                "Backfilling ungeoreferenced counts: (null country_code) ({$total}/{$total})",
                ['locality_groups_updated' => $groupsUpdated]
            );
            $groupsUpdated += $this->backfillCountry(null, $chunk);
        }

        $this->info('Done.');
        return self::SUCCESS;
    }

    private function backfillCountry(?string $country, int $chunk): int
    {
        // Get all locality_group IDs for this country in batches
        $lastId        = 0;
        $groupsUpdated = 0;
        $batchNum      = 0;

        while (true) {
            $groupIds = DB::table('locality_groups')
                ->select('id')
                ->whereNull('deleted_at')
                ->where('id', '>', $lastId)
                ->when($country !== null, fn($q) => $q->where('country_code', $country))
                ->when($country === null, fn($q) => $q->whereNull('country_code'))
                ->orderBy('id')
                ->limit($chunk)
                ->pluck('id');

            if ($groupIds->isEmpty()) break;

            $lastId = $groupIds->last();
            $groupsUpdated += $groupIds->count();
            $batchNum++;

            // Aggregate counts for this batch. The default chunk (3000) is deliberately
            // small: this WHERE IN() list is matched against occurrences (225M+ rows), and
            // the previous default of 50000 produced an IN() list large enough to leave a
            // single batch running for 5+ hours in production before anyone noticed it was
            // stuck rather than just slow.
            $counts = DB::table('occurrences')
                ->select('locality_group_id', DB::raw('COUNT(*) as cnt'))
                ->whereNull('deleted_at')
                ->whereIn('locality_group_id', $groupIds)
                ->where('georef_status', 'ungeoreferenced')
                ->groupBy('locality_group_id')
                ->pluck('cnt', 'locality_group_id');

            // Set to 0 for groups with no ungeoreferenced occurrences, and actual count for the rest
            foreach ($groupIds->chunk(5000) as $batch) {
                $updates = [];
                foreach ($batch as $id) {
                    $updates[$id] = $counts->get($id, 0);
                }

                // Build CASE WHEN for the batch update
                $cases = implode(' ', array_map(
                    fn($id, $cnt) => "WHEN {$id} THEN {$cnt}",
                    array_keys($updates),
                    array_values($updates)
                ));
                $ids = implode(',', array_keys($updates));

                DB::statement("UPDATE locality_groups SET ungeoreferenced_count = CASE id {$cases} END WHERE id IN ({$ids})");
            }

            // A single country can itself hold tens of millions of groups (chunk=3000
            // means tens of thousands of batches) — report periodically within it too,
            // not just once per country, so a slow country doesn't look like a hang.
            if ($batchNum % 20 === 0) {
                $countryLabel = $country ?? 'null country_code';
                $this->markProgress(
                    "Backfilling ungeoreferenced counts: {$countryLabel} (batch {$batchNum}, id > {$lastId})",
                    ['locality_groups_updated_this_country' => $groupsUpdated]
                );
            }
        }

        return $groupsUpdated;
    }
}
