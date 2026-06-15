<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GbifBackfillUngeoreferencedCount extends Command
{
    protected $signature = 'gbif:backfill-ungeoreferenced
                            {--country= : Limit to a specific country code}
                            {--chunk=50000 : Occurrences rows per UPDATE chunk}';

    protected $description = 'Backfill ungeoreferenced_count on locality_groups from occurrences table';

    public function handle(): int
    {
        $country = $this->option('country') ? strtoupper($this->option('country')) : null;
        $chunk   = (int) $this->option('chunk');

        if ($country) {
            $this->info("Backfilling ungeoreferenced_count for country: {$country}");
            $this->backfillCountry($country, $chunk);
        } else {
            // Process country by country to keep each UPDATE small
            $countries = DB::table('locality_groups')
                ->select('country_code')
                ->whereNotNull('country_code')
                ->distinct()
                ->orderBy('country_code')
                ->pluck('country_code');

            $this->info("Backfilling " . $countries->count() . " countries...");

            foreach ($countries as $cc) {
                $this->line("  {$cc}...");
                $this->backfillCountry($cc, $chunk);
            }

            // Also handle groups with null country_code
            $this->line("  (null country_code)...");
            $this->backfillCountry(null, $chunk);
        }

        $this->info('Done.');
        return self::SUCCESS;
    }

    private function backfillCountry(?string $country, int $chunk): void
    {
        // Get all locality_group IDs for this country in batches
        $lastId = 0;

        while (true) {
            $groupIds = DB::table('locality_groups')
                ->select('id')
                ->where('id', '>', $lastId)
                ->when($country !== null, fn($q) => $q->where('country_code', $country))
                ->when($country === null, fn($q) => $q->whereNull('country_code'))
                ->orderBy('id')
                ->limit($chunk)
                ->pluck('id');

            if ($groupIds->isEmpty()) break;

            $lastId = $groupIds->last();

            // Aggregate counts for this batch
            $counts = DB::table('occurrences')
                ->select('locality_group_id', DB::raw('COUNT(*) as cnt'))
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
        }
    }
}
