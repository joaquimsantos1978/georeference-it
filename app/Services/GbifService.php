<?php

namespace App\Services;

use App\Models\GeorefSuggestion;
use App\Models\LocalityGroup;
use App\Models\Occurrence;
use App\Models\PlatformSetting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GbifService
{
    const BASE_URL = 'https://api.gbif.org/v1';
    const PAGE_LIMIT = 300;

public function fetchByCountry(string $countryCode, int $offset = 0): array
{
    return $this->fetch([
        'country' => $countryCode,
        'hasCoordinate' => 'false',
        'basisOfRecord' => ['PRESERVED_SPECIMEN', 'FOSSIL_SPECIMEN'],
        'limit' => self::PAGE_LIMIT,
        'offset' => $offset,
    ]);
}

public function fetchByDataset(string $datasetKey, int $offset = 0): array
{
    return $this->fetch([
        'datasetKey' => $datasetKey,
        'hasCoordinate' => 'false',
        'basisOfRecord' => ['PRESERVED_SPECIMEN', 'FOSSIL_SPECIMEN'],
        'limit' => self::PAGE_LIMIT,
        'offset' => $offset,
    ]);
}
    public function fetchReferencesForGroup(LocalityGroup $group): array
    {
        $params = ['hasCoordinate' => 'true', 'limit' => 100];

        if ($group->country_code) $params['country'] = $group->country_code;
        if ($group->verbatim_locality) $params['verbatimLocality'] = $group->verbatim_locality;
        elseif ($group->municipality) $params['municipality'] = $group->municipality;
        elseif ($group->county) $params['county'] = $group->county;

        return $this->fetch($params);
    }

    // GBIF's search API expects repeated "key=a&key=b" query params for an OR across
    // multiple values of the same field, not the "key[0]=a&key[1]=b" bracket notation
    // Guzzle/Laravel's Http::get produces by default for array values — GBIF silently
    // ignores the bracketed form, so basisOfRecord filters with multiple values need to
    // be built manually.
    private function fetch(array $params): array
    {
        try {
            $query = [];
            foreach ($params as $key => $value) {
                foreach ((array) $value as $v) {
                    $query[] = rawurlencode((string) $key) . '=' . rawurlencode((string) $v);
                }
            }

            $response = Http::timeout(30)
                ->get(self::BASE_URL . '/occurrence/search?' . implode('&', $query));

            if (!$response->successful()) {
                Log::error('GBIF API error', ['status' => $response->status(), 'params' => $params]);
                return ['results' => [], 'count' => 0, 'endOfRecords' => true];
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('GBIF API exception', ['message' => $e->getMessage()]);
            return ['results' => [], 'count' => 0, 'endOfRecords' => true];
        }
    }

    // What the monthly DWCA download keeps (see GbifRequestDownload::buildPredicate()). Anything
    // else must not be imported on demand either: the next monthly refresh would not contain it.
    public const ELIGIBLE_BASIS_OF_RECORD = ['PRESERVED_SPECIMEN', 'FOSSIL_SPECIMEN'];

    // Datasets bigger than this are left to the monthly import rather than pulled through the
    // paged search API (GBIF caps offset+limit at 100,000, and 300 records/page is slow).
    public const MAX_ON_DEMAND_DATASET_RECORDS = 20000;

    public function importResults(array $results): int
    {
        return $this->importBatch($results)['imported'];
    }

    /**
     * Stores a page of GBIF API records. Records we already hold (even soft-deleted ones) are
     * left exactly as they are, so a re-run never resets the georeferencing status of a record
     * that has suggestions, nor resurrects one the monthly refresh removed.
     *
     * @return array{imported: int, created: int, groups: array<int, LocalityGroup>}
     *         `groups` are the groups created by this batch.
     */
    public function importBatch(array $results, bool $autoSuggest = true): array
    {
        $imported = 0;
        $created = 0;
        $newGroups = [];

        foreach ($results as $record) {
            try {
                $stored = $this->storeRecord($record);
                if ($stored === null) {
                    continue;
                }
                $imported++;
                if ($stored['created']) {
                    $created++;
                }
                if ($stored['groupIsNew']) {
                    $newGroups[$stored['group']->id] = $stored['group'];
                }
            } catch (\Exception $e) {
                Log::warning('Failed to import GBIF record', [
                    'key' => $record['key'] ?? null,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        // For each new locality group, create auto-suggestions from georeferenced siblings
        // (a caller importing page after page passes false and does it once everything is in)
        if ($autoSuggest) {
            foreach ($newGroups as $group) {
                $this->createAutoSuggestions($group);
            }
        }

        return ['imported' => $imported, 'created' => $created, 'groups' => $newGroups];
    }

    /**
     * One record straight from GBIF's occurrence endpoint.
     *
     * @return array|null the record, or null when GBIF has no such occurrence (404)
     * @throws \RuntimeException when GBIF could not be reached or answered with an error, so
     *         the caller can tell "does not exist" from "try again later"
     */
    public function fetchOccurrence(string $key): ?array
    {
        try {
            $response = Http::timeout(8)->acceptJson()->get(self::BASE_URL . '/occurrence/' . rawurlencode($key));
        } catch (\Throwable $e) {
            throw new \RuntimeException('GBIF unreachable: ' . $e->getMessage(), 0, $e);
        }

        if ($response->status() === 404) {
            return null;
        }
        if (!$response->successful()) {
            throw new \RuntimeException('GBIF answered HTTP ' . $response->status());
        }

        $record = $response->json();
        return is_array($record) && !empty($record['key']) ? $record : null;
    }

    /**
     * Makes sure a GBIF occurrence exists locally, importing it from GBIF's API if it was
     * published after the last monthly import.
     *
     * @return array{occurrence: Occurrence, created: bool, datasetWasKnown: bool}|null
     *         null when there is nothing to georeference: GBIF has no such record, it is not a
     *         preserved/fossil specimen, or we removed it on purpose (soft-deleted).
     * @throws \RuntimeException when GBIF cannot be reached right now
     */
    public function ensureOccurrence(string $key): ?array
    {
        $existing = Occurrence::withTrashed()->where('gbif_occurrence_key', $key)->first();
        if ($existing) {
            return $existing->trashed() ? null : ['occurrence' => $existing, 'created' => false, 'datasetWasKnown' => true];
        }

        $record = $this->fetchOccurrence($key);
        if (!$record || !in_array($record['basisOfRecord'] ?? null, self::ELIGIBLE_BASIS_OF_RECORD, true)
            || ($record['occurrenceStatus'] ?? 'PRESENT') === 'ABSENT') {
            return null;
        }

        $datasetKey = $record['datasetKey'] ?? null;
        $datasetWasKnown = $datasetKey ? Occurrence::where('dataset_key', $datasetKey)->exists() : true;

        try {
            $stored = $this->storeRecord($record);
        } catch (QueryException $e) {
            // Two requests for the same new record at once: the unique key stopped the second.
            $stored = null;
            if (($e->errorInfo[0] ?? null) !== '23000') {
                throw $e;
            }
        }

        $occurrence = $stored['occurrence']
            ?? Occurrence::where('gbif_occurrence_key', $key)->first();
        if (!$occurrence) {
            return null;
        }

        if ($stored && $stored['groupIsNew']) {
            $this->createAutoSuggestions($stored['group']);
        }
        if ($datasetKey) {
            $this->ensureDataset($datasetKey);
        }

        return [
            'occurrence'      => $occurrence,
            'created'         => (bool) ($stored['created'] ?? false),
            'datasetWasKnown' => $datasetWasKnown,
        ];
    }

    // Same row GbifSyncDatasets writes, for a dataset that appeared after its last run — so the
    // dataset has a title/publisher in filters and the API straight away.
    public function ensureDataset(string $datasetKey): void
    {
        try {
            if (DB::table('datasets')->where('key', $datasetKey)->whereNotNull('title')->exists()) {
                return;
            }

            $response = Http::timeout(8)->get(self::BASE_URL . '/dataset/' . rawurlencode($datasetKey));
            if (!$response->successful()) {
                return;
            }
            $d = $response->json();

            DB::table('datasets')->upsert([
                'key'            => $datasetKey,
                'title'          => mb_substr($d['title'] ?? '', 0, 255),
                'publisher_name' => mb_substr($d['publishingOrganizationTitle'] ?? '', 0, 255),
                'publisher_key'  => $d['publishingOrganizationKey'] ?? null,
                'license'        => $d['license'] ?? null,
                'type'           => $d['type'] ?? null,
                'synced_at'      => now(),
                'created_at'     => now(),
                'updated_at'     => now(),
            ], ['key'], ['title', 'publisher_name', 'publisher_key', 'license', 'type', 'synced_at', 'updated_at']);
        } catch (\Throwable $e) {
            // Metadata is nice to have; the occurrence is already stored.
            Log::warning('Could not store GBIF dataset metadata', ['dataset' => $datasetKey, 'message' => $e->getMessage()]);
        }
    }

    /** Eligible specimens of one dataset, any coordinates, paged. */
    public function fetchDatasetPage(string $datasetKey, int $offset): array
    {
        return $this->fetch([
            'datasetKey'    => $datasetKey,
            'basisOfRecord' => self::ELIGIBLE_BASIS_OF_RECORD,
            'limit'         => self::PAGE_LIMIT,
            'offset'        => $offset,
        ]);
    }

    /**
     * @return array{occurrence: Occurrence, group: ?LocalityGroup, groupIsNew: bool, created: bool}|null
     */
    private function storeRecord(array $record): ?array
    {
        $gbifKey = (string) ($record['key'] ?? $record['gbifID'] ?? null);
        if (!$gbifKey) return null;

        $existing = Occurrence::withTrashed()->where('gbif_occurrence_key', $gbifKey)->first();
        if ($existing) {
            return $existing->trashed()
                ? null
                : ['occurrence' => $existing, 'group' => null, 'groupIsNew' => false, 'created' => false];
        }

        $hasCoords = isset($record['decimalLatitude']) && isset($record['decimalLongitude'])
            && !($record['decimalLatitude'] == 0 && $record['decimalLongitude'] == 0);
        $georefStatus = $hasCoords ? 'gbif_georeferenced' : 'ungeoreferenced';

        $groupFields = [
            'continent'         => $record['continent'] ?? null,
            'country_code'      => $record['countryCode'] ?? null,
            'state_province'    => $record['stateProvince'] ?? null,
            'county'            => $record['county'] ?? null,
            'municipality'      => $record['municipality'] ?? null,
            'verbatim_locality' => $record['verbatimLocality'] ?? $record['locality'] ?? null,
            'water_body'        => $record['waterBody'] ?? null,
            'island_group'      => $record['islandGroup'] ?? null,
            'island'            => $record['island'] ?? null,
            'higher_geography'  => $record['higherGeography'] ?? null,
            'location_remarks'  => $record['locationRemarks'] ?? null,
        ];

        $groupHash = LocalityGroup::hashFromOccurrence($groupFields);

        // Same composition as the monthly import's locality_string (which leaves out
        // higher_geography), so a group created here is indistinguishable from one it creates.
        // (locality_groups has no water_body column: it only enters the hash and this string.)
        $localityString = implode(', ', array_filter([
            $groupFields['continent'], $groupFields['country_code'], $groupFields['state_province'],
            $groupFields['county'], $groupFields['municipality'], $groupFields['verbatim_locality'],
            $groupFields['water_body'], $groupFields['island_group'], $groupFields['island'],
            $groupFields['location_remarks'],
        ], fn($v) => $v !== null && $v !== ''));

        $localityGroup = LocalityGroup::firstOrCreate(
            ['group_hash' => $groupHash],
            [
                'locality_string'     => $localityString,
                'continent'           => $groupFields['continent'],
                'country_code'        => $groupFields['country_code'],
                'state_province'      => $groupFields['state_province'],
                'county'              => $groupFields['county'],
                'municipality'        => $groupFields['municipality'],
                'island'              => $groupFields['island'] ?: null,
                'island_group'        => $groupFields['island_group'] ?: null,
                'verbatim_locality'   => $groupFields['verbatim_locality'],
                'location_remarks'    => $groupFields['location_remarks'],
                'higher_geography'    => $groupFields['higher_geography'],
                'normalized_locality' => LocalityGroup::normalizeLocality($groupFields['verbatim_locality'] ?? ''),
            ]
        );
        $isNewGroup = $localityGroup->wasRecentlyCreated;

        $media = [];
        if (!empty($record['media'])) {
            foreach ($record['media'] as $m) {
                if (!empty($m['identifier'])) {
                    $media[] = [
                        'identifier' => $m['identifier'],
                        'type' => $m['type'] ?? 'StillImage',
                        'title' => $m['title'] ?? null,
                        'license' => $m['license'] ?? null,
                    ];
                }
            }
        }

        // Every column the monthly DWCA import fills, so a record added here is as searchable
        // (taxonomy, year, type status...) as one that arrived with the monthly download.
        $typeStatus = $record['typeStatus'] ?? null;
        if (is_array($typeStatus)) {
            $typeStatus = implode('|', $typeStatus);
        }
        // year is smallint unsigned, month/day tinyint unsigned: out-of-range values become NULL rather than failing the whole record
        $intOrNull = fn($v, int $max = 65535) => is_numeric($v) && $v >= 0 && $v <= $max ? (int) $v : null;

        $occurrence = Occurrence::create(
            [
                'gbif_occurrence_key' => $gbifKey,
                'dataset_key' => $record['datasetKey'] ?? null,
                'publisher_key' => $record['publishingOrgKey'] ?? null,
                'catalog_number' => $record['catalogNumber'] ?? null,
                'institution_code' => $record['institutionCode'] ?? null,
                'collection_code' => $record['collectionCode'] ?? null,
                'basis_of_record' => $record['basisOfRecord'] ?? null,
                'verbatim_locality' => $record['verbatimLocality'] ?? $record['locality'] ?? null,
                'country' => $record['country'] ?? null,
                'country_code' => $record['countryCode'] ?? null,
                'state_province' => $record['stateProvince'] ?? null,
                'county' => $record['county'] ?? null,
                'municipality' => $record['municipality'] ?? null,
                'island' => $record['island'] ?? null,
                'island_group' => $record['islandGroup'] ?? null,
                'water_body' => $record['waterBody'] ?? null,
                'higher_geography' => $record['higherGeography'] ?? null,
                'event_date' => $record['eventDate'] ?? null,
                'recorded_by' => $record['recordedBy'] ?? null,
                'field_number' => $record['fieldNumber'] ?? null,
'scientific_name' => $record['scientificName'] ?? $record['species'] ?? null,
'taxon_rank' => $record['taxonRank'] ?? null,
'kingdom' => $record['kingdom'] ?? null,
'phylum' => $record['phylum'] ?? null,
'class' => $record['class'] ?? null,
'order' => $record['order'] ?? null,
'family' => $record['family'] ?? null,
'genus' => $record['genus'] ?? null,
'specific_epithet' => $record['specificEpithet'] ?? null,
'type_status' => $typeStatus ?: null,
'year' => $intOrNull($record['year'] ?? null),
'month' => $intOrNull($record['month'] ?? null, 12),
'day' => $intOrNull($record['day'] ?? null, 31),
                'gbif_decimal_latitude' => $hasCoords ? $record['decimalLatitude'] : null,
                'gbif_decimal_longitude' => $hasCoords ? $record['decimalLongitude'] : null,
                'gbif_geodetic_datum' => $record['geodeticDatum'] ?? null,
                'gbif_coordinate_uncertainty_m' => $record['coordinateUncertaintyInMeters'] ?? null,
                'locality_group_id' => $localityGroup->id,
                'georef_status' => $georefStatus,
                'media' => !empty($media) ? $media : null,
                'synced_at' => now(),
            ]
        );

        // +1 instead of recalculateCounters(): that one re-reads every row of the group, which on
        // a group with hundreds of thousands of occurrences blows the web request's 20 s limit.
        $statusColumn = $georefStatus === 'gbif_georeferenced' ? 'gbif_georeferenced_count' : 'ungeoreferenced_count';
        DB::table('locality_groups')->where('id', $localityGroup->id)->update([
            'occurrence_count' => DB::raw('occurrence_count + 1'),
            $statusColumn      => DB::raw("{$statusColumn} + 1"),
            'updated_at'       => now(),
        ]);

        return ['occurrence' => $occurrence, 'group' => $localityGroup, 'groupIsNew' => $isNewGroup, 'created' => true];
    }

    public function checkConsistency(LocalityGroup $group, int $threshold = 5000, array &$pendingConsistentIds = []): string
    {
        // Skip groups without a specific locality name — country-only and province-only
        // groups aggregate unrelated records across large areas and cannot be meaningfully
        // clustered for consistency checking.
        if (!$group->verbatim_locality && !$group->municipality && !$group->county) {
            $pendingConsistentIds[] = $group->id;
            return 'consistent';
        }

        // Only groups with 2+ georeferenced occurrences are interesting
        $georefOccurrences = $group->occurrences()
            ->where('georef_status', 'gbif_georeferenced')
            ->whereNotNull('gbif_decimal_latitude')
            ->whereNotNull('gbif_decimal_longitude')
            ->where('gbif_decimal_latitude', '!=', 0)
            ->where('gbif_decimal_longitude', '!=', 0)
            ->limit(500) // clusterByOverlap is O(n²) — cap at 500 to stay fast
            ->get(['id', 'gbif_decimal_latitude', 'gbif_decimal_longitude', 'gbif_coordinate_uncertainty_m']);

        if ($georefOccurrences->count() < 2) {
            $pendingConsistentIds[] = $group->id;
            return 'consistent';
        }

        $points = $georefOccurrences->map(fn($o) => [
            'id'          => $o->id,
            'lat'         => (float) $o->gbif_decimal_latitude,
            'lng'         => (float) $o->gbif_decimal_longitude,
            'uncertainty' => (float) ($o->gbif_coordinate_uncertainty_m ?: 10000),
        ])->toArray();

        $clusters = $this->clusterByOverlap($points);

        if (count($clusters) === 1) {
            $pendingConsistentIds[] = $group->id;
            return 'consistent';
        }

        // Multiple clusters — check if they're geographically close.
        // Two known precise points within the same locality (e.g. a botanical garden)
        // are both correct; only flag if centroids are far apart.
        $centroids = array_map(fn($c) => $this->computeCentroid($c), $clusters);
        $maxDist = 0;
        for ($i = 0; $i < count($centroids); $i++) {
            for ($j = $i + 1; $j < count($centroids); $j++) {
                $d = $this->haversineDistance(
                    $centroids[$i]['lat'], $centroids[$i]['lng'],
                    $centroids[$j]['lat'], $centroids[$j]['lng']
                );
                $maxDist = max($maxDist, $d);
            }
        }

        if ($maxDist < $threshold) {
            $pendingConsistentIds[] = $group->id;
            return 'consistent';
        }

        // Multiple clusters far apart — inconsistency detected.
        // Skip if competing suggestions already exist for this group.
        $existingSystemSuggestions = GeorefSuggestion::where('locality_group_id', $group->id)
            ->whereNull('user_id')
            ->where('georeference_sources', 'GBIF_CONSISTENCY_CHECK')
            ->exists();

        if ($existingSystemSuggestions) {
            $group->update(['consistency_status' => 'inconsistent']);
            return 'inconsistent';
        }

        // Create one competing suggestion per cluster.
        // Each suggestion excludes the occurrences from OTHER clusters,
        // so when it wins validation those occurrences get flagged as gbif_reviewed.
        $allIds = array_column($points, 'id');

        foreach ($clusters as $cluster) {
            $centroid   = $this->computeCentroid($cluster);
            $clusterIds = array_column($cluster, 'id');
            $n          = count($cluster);

            // firstOrCreate guards against concurrent duplicate runs on the same group
            $suggestion = GeorefSuggestion::firstOrCreate(
                [
                    'locality_group_id'    => $group->id,
                    'user_id'              => null,
                    'decimal_latitude'     => $centroid['lat'],
                    'decimal_longitude'    => $centroid['lng'],
                    'georeference_sources' => 'GBIF_CONSISTENCY_CHECK',
                ],
                [
                    'anon_name'                => 'System',
                    'coordinate_uncertainty_m' => $centroid['uncertainty'],
                    'geodetic_datum'           => 'WGS84',
                    'georeference_remarks'     => 'Cluster of ' . $n . ' georeferenced occurrence' . ($n > 1 ? 's' : '') . ' — inconsistency detected within locality group',
                    'status'                   => 'pending',
                    'georeferenced_date'       => now(),
                ]
            );

        }

        $group->update(['consistency_status' => 'inconsistent']);
        // The new suggestions above are the only thing that changed — none of the group's
        // occurrences flipped status yet — but pending_count must reflect them, so recompute
        // from occurrences rather than hand-incrementing by count($clusters): that used to
        // add the number of new suggestion *clusters* to a counter that's meant to track
        // occurrences, inflating it by the wrong unit every time this ran.
        $group->recalculateCounters();

        return 'inconsistent';
    }

    public function createAutoSuggestions(LocalityGroup $group): void
    {
        // Skip country/province-only groups — too vague to cluster meaningfully
        if (!$group->verbatim_locality && !$group->municipality && !$group->county) {
            return;
        }

        // Skip if the group already has any suggestion (human or system)
        if (GeorefSuggestion::where('locality_group_id', $group->id)->exists()) {
            return;
        }

        // Need at least one ungeoreferenced occurrence to act on
        $hasUngeoreferenced = $group->occurrences()
            ->where('georef_status', 'ungeoreferenced')
            ->exists();

        if (!$hasUngeoreferenced) {
            return;
        }

        // Find georeferenced occurrences in this same locality group
        $georefOccurrences = $group->occurrences()
            ->where('georef_status', 'gbif_georeferenced')
            ->whereNotNull('gbif_decimal_latitude')
            ->whereNotNull('gbif_decimal_longitude')
            ->where('gbif_decimal_latitude', '!=', 0)
            ->where('gbif_decimal_longitude', '!=', 0)
            ->limit(500) // clusterByOverlap is O(n²) — cap to stay fast
            ->get(['gbif_decimal_latitude', 'gbif_decimal_longitude', 'gbif_coordinate_uncertainty_m']);

        if ($georefOccurrences->isEmpty()) {
            return;
        }

        $points = $georefOccurrences->map(fn($o) => [
            'lat'         => (float) $o->gbif_decimal_latitude,
            'lng'         => (float) $o->gbif_decimal_longitude,
            'uncertainty' => (float) ($o->gbif_coordinate_uncertainty_m ?: 10000),
        ])->toArray();

        $clusters = $this->clusterByOverlap($points);

        foreach ($clusters as $cluster) {
            $centroid = $this->computeCentroid($cluster);
            $n        = count($cluster);

            GeorefSuggestion::create([
                'locality_group_id'        => $group->id,
                'user_id'                  => null,
                'anon_name'                => 'System',
                'decimal_latitude'         => $centroid['lat'],
                'decimal_longitude'        => $centroid['lng'],
                'coordinate_uncertainty_m' => $centroid['uncertainty'],
                'geodetic_datum'           => 'WGS84',
                'georeference_sources'     => 'GBIF',
                'georeference_remarks'     => 'Auto-generated from ' . $n . ' georeferenced occurrence' . ($n > 1 ? 's' : '') . ' sharing the same locality',
                'status'                   => 'pending',
                'georeferenced_date'       => now(),
            ]);
        }

        // Mark ungeoreferenced occurrences in this group as having a suggestion
        $group->occurrences()
            ->where('georef_status', 'ungeoreferenced')
            ->update(['georef_status' => 'has_suggestion']);

        $this->updateGroupCounters($group);
    }

    private function clusterByOverlap(array $points): array
    {
        $clusters = [];
        $assigned = array_fill(0, count($points), false);

        for ($i = 0; $i < count($points); $i++) {
            if ($assigned[$i]) continue;

            $cluster = [$points[$i]];
            $assigned[$i] = true;

            for ($j = $i + 1; $j < count($points); $j++) {
                if ($assigned[$j]) continue;

                $distance = $this->haversineDistance(
                    $points[$i]['lat'], $points[$i]['lng'],
                    $points[$j]['lat'], $points[$j]['lng']
                );

                $sumRadii = $points[$i]['uncertainty'] + $points[$j]['uncertainty'];

                if ($distance <= $sumRadii) {
                    $cluster[] = $points[$j];
                    $assigned[$j] = true;
                }
            }

            $clusters[] = $cluster;
        }

        return $clusters;
    }

    private function computeCentroid(array $points): array
    {
        $latSum = array_sum(array_column($points, 'lat'));
        $lngSum = array_sum(array_column($points, 'lng'));
        $count = count($points);

        // Use the maximum uncertainty to encompass all points
        $maxUncertainty = max(array_column($points, 'uncertainty'));

        return [
            'lat' => round($latSum / $count, 7),
            'lng' => round($lngSum / $count, 7),
            'uncertainty' => $maxUncertainty,
        ];
    }

    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // metres
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function updateGroupCounters(LocalityGroup $group): void
    {
        $group->recalculateCounters();
    }
}