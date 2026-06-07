<?php

namespace App\Services;

use App\Models\GeorefSuggestion;
use App\Models\LocalityGroup;
use App\Models\Occurrence;
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
        'basisOfRecord' => 'PRESERVED_SPECIMEN',
        'limit' => self::PAGE_LIMIT,
        'offset' => $offset,
    ]);
}

public function fetchByDataset(string $datasetKey, int $offset = 0): array
{
    return $this->fetch([
        'datasetKey' => $datasetKey,
        'hasCoordinate' => 'false',
        'basisOfRecord' => 'PRESERVED_SPECIMEN',
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

    private function fetch(array $params): array
    {
        try {
            $response = Http::timeout(30)
                ->get(self::BASE_URL . '/occurrence/search', $params);

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

    public function importResults(array $results): int
    {
        $imported = 0;
        $newGroups = [];

        foreach ($results as $record) {
            try {
                $group = $this->importRecord($record);
                if ($group) {
                    $newGroups[$group->id] = $group;
                }
                $imported++;
            } catch (\Exception $e) {
                Log::warning('Failed to import GBIF record', [
                    'key' => $record['key'] ?? null,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        // For each new locality group, fetch GBIF references and create auto-suggestions
        foreach ($newGroups as $group) {
            $this->createAutoSuggestions($group);
        }

        return $imported;
    }

    private function importRecord(array $record): ?LocalityGroup
    {
        $gbifKey = (string) ($record['key'] ?? $record['gbifID'] ?? null);
        if (!$gbifKey) return null;

        $hasCoords = isset($record['decimalLatitude']) && isset($record['decimalLongitude']);
        $georefStatus = $hasCoords ? 'gbif_georeferenced' : 'ungeoreferenced';

        $groupFields = [
            'country_code' => $record['countryCode'] ?? null,
            'state_province' => $record['stateProvince'] ?? null,
            'county' => $record['county'] ?? null,
            'municipality' => $record['municipality'] ?? null,
            'verbatim_locality' => $record['verbatimLocality'] ?? $record['locality'] ?? null,
        ];

        $groupHash = LocalityGroup::hashFromOccurrence($groupFields);

        $isNewGroup = !LocalityGroup::where('group_hash', $groupHash)->exists();

        $localityGroup = LocalityGroup::firstOrCreate(
            ['group_hash' => $groupHash],
            [
                'locality_string' => implode(', ', array_filter(array_values($groupFields))),
                'verbatim_locality' => $groupFields['verbatim_locality'],
                'country_code' => $groupFields['country_code'],
                'state_province' => $groupFields['state_province'],
                'county' => $groupFields['county'],
                'municipality' => $groupFields['municipality'],
            ]
        );

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

        Occurrence::updateOrCreate(
            ['gbif_occurrence_key' => $gbifKey],
            [
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
'family' => $record['family'] ?? null,
                'gbif_decimal_latitude' => $record['decimalLatitude'] ?? null,
                'gbif_decimal_longitude' => $record['decimalLongitude'] ?? null,
                'gbif_geodetic_datum' => $record['geodeticDatum'] ?? null,
                'gbif_coordinate_uncertainty_m' => $record['coordinateUncertaintyInMeters'] ?? null,
                'locality_group_id' => $localityGroup->id,
                'georef_status' => $georefStatus,
                'media' => !empty($media) ? $media : null,
                'synced_at' => now(),
            ]
        );

        $this->updateGroupCounters($localityGroup);

        return $isNewGroup ? $localityGroup : null;
    }

    public function createAutoSuggestions(LocalityGroup $group): void
    {
   // Skip for now - will implement reference lookup later
    return;
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
        $group->update([
            'occurrence_count' => $group->occurrences()->count(),
            'pending_count' => $group->occurrences()
                ->whereIn('georef_status', ['has_suggestion', 'conflicted'])
                ->count(),
            'validated_count' => $group->occurrences()
                ->where('georef_status', 'validated')
                ->count(),
        ]);
    }
}