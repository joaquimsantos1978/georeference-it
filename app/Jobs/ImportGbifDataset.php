<?php

namespace App\Jobs;

use App\Models\LocalityGroup;
use App\Services\GbifService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

// Pulls every preserved/fossil specimen of one GBIF dataset through the search API, for a
// dataset published after the last monthly import. Dispatched when someone opens a record
// of a dataset we don't hold yet (see GeorefController::findByGbifKey), so that a person
// working through an expedition is not made to wait for the next monthly import record by
// record. Records we already have are never touched (GbifService::storeRecord).
class ImportGbifDataset implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 1800;

    public function __construct(public readonly string $datasetKey) {}

    public static function lockKey(string $datasetKey): string
    {
        return 'gbif_dataset_import:' . $datasetKey;
    }

    public function handle(GbifService $gbif): void
    {
        // One import per dataset at a time, and not again for a day: every visitor opening a
        // record of a new dataset would otherwise queue another copy.
        if (!Cache::add(self::lockKey($this->datasetKey), now()->toIso8601String(), now()->addDay())) {
            return;
        }

        try {
            $first = $gbif->fetchDatasetPage($this->datasetKey, 0);
            $total = (int) ($first['count'] ?? 0);

            if ($total === 0) {
                return;
            }
            if ($total > GbifService::MAX_ON_DEMAND_DATASET_RECORDS) {
                Log::info('GBIF dataset too large for on-demand import; left to the monthly import', [
                    'dataset' => $this->datasetKey, 'records' => $total,
                ]);
                return;
            }

            $gbif->ensureDataset($this->datasetKey);

            $created = 0;
            $newGroupIds = [];
            $page = $first;

            for ($offset = 0; $offset < $total; $offset += GbifService::PAGE_LIMIT) {
                if ($offset > 0) {
                    $page = $gbif->fetchDatasetPage($this->datasetKey, $offset);
                }
                $results = $page['results'] ?? [];
                if (empty($results)) {
                    break;
                }

                // No auto-suggestions per page: a group usually spans several pages, so they are
                // made once at the end, when every sibling is in.
                $batch = $gbif->importBatch($results, false);
                $created += $batch['created'];
                $newGroupIds += array_fill_keys(array_keys($batch['groups']), true);

                usleep(200000); // be polite to the GBIF API
            }

            // All siblings exist now: one suggestion per group, made from the georeferenced ones.
            foreach (array_keys($newGroupIds) as $groupId) {
                if ($group = LocalityGroup::find($groupId)) {
                    $gbif->createAutoSuggestions($group);
                }
            }

            Log::info('GBIF dataset imported on demand', [
                'dataset' => $this->datasetKey, 'records' => $total, 'created' => $created,
            ]);
        } catch (\Throwable $e) {
            // Allow a retry on the next visit rather than waiting out the day.
            Cache::forget(self::lockKey($this->datasetKey));
            throw $e;
        }
    }
}
