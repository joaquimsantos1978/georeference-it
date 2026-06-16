<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GeorefSuggestion;
use App\Models\Occurrence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OccurrenceController extends Controller
{
    private const VERIFICATION_STATUS = [
        'validated'          => 'verified by contributor',
        'has_suggestion'     => 'requires verification',
        'conflicted'         => 'requires verification',
        'gbif_georeferenced' => 'requires verification',
        'ungeoreferenced'    => 'requires georeference',
    ];

    // Standard DwC JSON-LD context — maps camelCase term names to their IRIs
    private const JSONLD_CONTEXT = [
        '@vocab'                          => 'http://rs.tdwg.org/dwc/terms/',
        'dcterms'                         => 'http://purl.org/dc/terms/',
        'occurrenceID'                    => 'dwc:occurrenceID',
        'datasetKey'                      => 'dcterms:datasetKey',
        'institutionCode'                 => 'dwc:institutionCode',
        'collectionCode'                  => 'dwc:collectionCode',
        'catalogNumber'                   => 'dwc:catalogNumber',
        'basisOfRecord'                   => 'dwc:basisOfRecord',
        'scientificName'                  => 'dwc:scientificName',
        'taxonRank'                       => 'dwc:taxonRank',
        'kingdom'                         => 'dwc:kingdom',
        'family'                          => 'dwc:family',
        'eventDate'                       => 'dwc:eventDate',
        'recordedBy'                      => 'dwc:recordedBy',
        'higherGeography'                 => 'dwc:higherGeography',
        'country'                         => 'dwc:country',
        'countryCode'                     => 'dwc:countryCode',
        'stateProvince'                   => 'dwc:stateProvince',
        'county'                          => 'dwc:county',
        'municipality'                    => 'dwc:municipality',
        'island'                          => 'dwc:island',
        'islandGroup'                     => 'dwc:islandGroup',
        'waterBody'                       => 'dwc:waterBody',
        'verbatimLocality'                => 'dwc:verbatimLocality',
        'decimalLatitude'                 => 'dwc:decimalLatitude',
        'decimalLongitude'                => 'dwc:decimalLongitude',
        'geodeticDatum'                   => 'dwc:geodeticDatum',
        'coordinateUncertaintyInMeters'   => 'dwc:coordinateUncertaintyInMeters',
        'georeferencedBy'                 => 'dwc:georeferencedBy',
        'georeferencedDate'               => 'dwc:georeferencedDate',
        'georeferenceProtocol'            => 'dwc:georeferenceProtocol',
        'georeferenceSources'             => 'dwc:georeferenceSources',
        'georeferenceRemarks'             => 'dwc:georeferenceRemarks',
        'georeferenceVerificationStatus'  => 'dwc:georeferenceVerificationStatus',
    ];

    public function index(Request $request): Response|JsonResponse
    {
        $query = Occurrence::query()->with(['localityGroup']);

        if ($request->filled('country')) {
            $query->where('country_code', strtoupper($request->country));
        }
        if ($request->filled('dataset_key')) {
            $query->where('dataset_key', $request->dataset_key);
        }
        if ($request->filled('status')) {
            $query->where('georef_status', $request->status);
        }
        if ($request->filled('scientific_name')) {
            $query->where('scientific_name', 'like', '%' . $request->scientific_name . '%');
        }

        $perPage = min((int) $request->get('per_page', 100), 500);
        $results = $query->paginate($perPage);
        $records = $results->getCollection()->map(fn($o) => $this->format($o))->all();

        if ($this->wantsJsonLd($request)) {
            return $this->jsonldResponse([
                '@context'     => self::JSONLD_CONTEXT,
                '@type'        => 'owl:Ontology',
                'totalRecords' => $results->total(),
                'currentPage'  => $results->currentPage(),
                'lastPage'     => $results->lastPage(),
                '@graph'       => array_map(fn($r) => $this->toJsonLdNode($r), $records),
            ]);
        }

        return response()->json([
            'meta' => [
                'total'        => $results->total(),
                'per_page'     => $results->perPage(),
                'current_page' => $results->currentPage(),
                'last_page'    => $results->lastPage(),
            ],
            'data' => $records,
        ]);
    }

    public function show(Request $request, string $gbifKey): Response|JsonResponse
    {
        $occurrence = Occurrence::with(['localityGroup'])
            ->where('gbif_occurrence_key', $gbifKey)
            ->firstOrFail();

        $record = $this->format($occurrence);

        if ($this->wantsJsonLd($request)) {
            return $this->jsonldResponse(array_merge(
                ['@context' => self::JSONLD_CONTEXT],
                $this->toJsonLdNode($record)
            ));
        }

        return response()->json($record);
    }

    private function wantsJsonLd(Request $request): bool
    {
        return str_contains($request->header('Accept', ''), 'application/ld+json')
            || $request->get('format') === 'jsonld';
    }

    private function jsonldResponse(array $data): Response
    {
        return response(
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            200,
            ['Content-Type' => 'application/ld+json; charset=utf-8']
        );
    }

    private function toJsonLdNode(array $record): array
    {
        $node = ['@type' => 'dwc:Occurrence'];

        if (!empty($record['occurrenceID'])) {
            $node['@id'] = 'https://www.gbif.org/occurrence/' . $record['occurrenceID'];
        }

        // Platform-specific fields don't belong in JSON-LD output
        unset($record['georef_status'], $record['localityGroupID']);

        return array_merge($node, array_filter($record, fn($v) => $v !== null));
    }

    private function divergesFromGbif(Occurrence $o, array $georef): bool
    {
        if ($o->gbif_decimal_latitude === null) return false;
        if ($georef['lat'] === null) return false;
        if ($georef['sources'] === 'GBIF') return false;

        $latDiff = abs((float) $georef['lat'] - (float) $o->gbif_decimal_latitude);
        $lngDiff = abs((float) $georef['lng'] - (float) $o->gbif_decimal_longitude);
        return $latDiff > 0.0001 || $lngDiff > 0.0001;
    }

    private function format(Occurrence $o): array
    {
        $georef = $this->resolveGeoref($o);

        return [
            'occurrenceID'                   => $o->gbif_occurrence_key,
            'datasetKey'                     => $o->dataset_key,
            'institutionCode'                => $o->institution_code,
            'collectionCode'                 => $o->collection_code,
            'catalogNumber'                  => $o->catalog_number,
            'basisOfRecord'                  => $o->basis_of_record,
            'scientificName'                 => $o->scientific_name,
            'taxonRank'                      => $o->taxon_rank,
            'kingdom'                        => $o->kingdom,
            'family'                         => $o->family,
            'eventDate'                      => $o->event_date,
            'recordedBy'                     => $o->recorded_by,
            'higherGeography'                => $o->higher_geography,
            'country'                        => $o->country,
            'countryCode'                    => $o->country_code,
            'stateProvince'                  => $o->state_province,
            'county'                         => $o->county,
            'municipality'                   => $o->municipality,
            'island'                         => $o->island,
            'islandGroup'                    => $o->island_group,
            'waterBody'                      => $o->water_body,
            'verbatimLocality'               => $o->verbatim_locality,
            'decimalLatitude'                => $georef['lat'],
            'decimalLongitude'               => $georef['lng'],
            'geodeticDatum'                  => $georef['datum'],
            'coordinateUncertaintyInMeters'  => $georef['uncertainty'],
            'georeferencedBy'                => $georef['by'],
            'georeferencedDate'              => $georef['date'],
            'georeferenceProtocol'           => $georef['protocol'],
            'georeferenceSources'            => $georef['sources'],
            'georeferenceRemarks'            => $georef['remarks'],
            'georeferenceVerificationStatus' => self::VERIFICATION_STATUS[$o->georef_status] ?? 'requires georeference',
            // Non-DwC platform metadata (omitted from JSON-LD nodes)
            'georef_status'                  => $o->georef_status,
            'localityGroupID'                => $o->locality_group_id,
            'georef_url'                     => $o->locality_group_id
                ? rtrim(config('app.url'), '/') . '/georef?group=' . $o->locality_group_id
                : null,
            'diverges_from_gbif'             => $this->divergesFromGbif($o, $georef),
        ];
    }

    private function resolveGeoref(Occurrence $o): array
    {
        if (in_array($o->georef_status, ['validated', 'has_suggestion', 'conflicted'])) {
            $suggestion = GeorefSuggestion::where('locality_group_id', $o->locality_group_id)
                ->whereNotNull('decimal_latitude')
                ->where(function ($q) {
                    $q->where('status', 'accepted')->orWhere('status', 'pending');
                })
                ->orderByRaw("FIELD(status, 'accepted', 'pending')")
                ->first();

            if ($suggestion) {
                return [
                    'lat'         => $suggestion->decimal_latitude,
                    'lng'         => $suggestion->decimal_longitude,
                    'datum'       => $suggestion->geodetic_datum ?? 'WGS84',
                    'uncertainty' => $suggestion->coordinate_uncertainty_m,
                    'by'          => $suggestion->user_id ? 'georeference.it contributor' : 'georeference.it system',
                    'date'        => $suggestion->georeferenced_date ?? $suggestion->created_at?->toDateString(),
                    'protocol'    => $suggestion->georeference_protocol ?? 'https://doi.org/10.35035/e09p-h128',
                    'sources'     => $suggestion->georeference_sources ?? 'georeference.it',
                    'remarks'     => $suggestion->georeference_remarks,
                ];
            }
        }

        if ($o->gbif_decimal_latitude !== null) {
            return [
                'lat'         => $o->gbif_decimal_latitude,
                'lng'         => $o->gbif_decimal_longitude,
                'datum'       => $o->gbif_geodetic_datum,
                'uncertainty' => $o->gbif_coordinate_uncertainty_m,
                'by'          => null,
                'date'        => null,
                'protocol'    => null,
                'sources'     => 'GBIF',
                'remarks'     => null,
            ];
        }

        return [
            'lat' => null, 'lng' => null, 'datum' => null, 'uncertainty' => null,
            'by' => null, 'date' => null, 'protocol' => null, 'sources' => null, 'remarks' => null,
        ];
    }
}
