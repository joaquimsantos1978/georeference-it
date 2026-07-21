<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GeorefSuggestion;
use App\Models\Occurrence;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OccurrenceController extends Controller
{
    // Populated per-page by preloadSuggestions(), keyed by locality_group_id — lets
    // resolveGeoref() do a plain array lookup instead of a query per occurrence.
    private array $suggestionCache = [];

    // Same string used for both manually- and system-submitted georeferences (see
    // GeorefController::submit()) — keeps georeferenceProtocol consistent regardless
    // of who/what produced the suggestion.
    private const GEOREFERENCE_PROTOCOL = 'Georeferencing Quick Reference Guide (Zermoglio et al. 2020, https://doi.org/10.35035/e09p-h128)';

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

    public function index(Request $request): Response|JsonResponse|StreamedResponse
    {
        $query = Occurrence::query()->with(['localityGroup']);

        if ($request->filled('country')) {
            $query->where('country_code', strtoupper($request->country));
        }
        if ($request->filled('datasetKey')) {
            $query->where('dataset_key', $request->datasetKey);
        }
        if ($request->filled('institutionCode')) {
            $query->where('institution_code', $request->institutionCode);
        }
        if ($request->filled('status')) {
            $query->whereIn('georef_status', explode('|', $request->status));
        }
        if ($request->filled('scientificName')) {
            $query->where('scientific_name', 'like', '%' . $request->scientificName . '%');
        }
        // 'updated_at' doubles as "last georeferenced/status-changed" — there's no
        // separate per-occurrence georeference timestamp column, but this is what the
        // idx_georef_status_updated_id index (see migration 2026_06_29_185043) exists
        // for, and ImpactController's recent-activity feed already relies on the same
        // proxy. Dates are inclusive, interpreted at day granularity.
        if ($request->filled('georeferencedAfter')) {
            $query->where('updated_at', '>=', $request->georeferencedAfter);
        }
        if ($request->filled('georeferencedBefore')) {
            $query->where('updated_at', '<=', $request->georeferencedBefore . ' 23:59:59');
        }

        if ($request->get('format') === 'csv') {
            return $this->csvResponse($query, $request);
        }

        $perPage = min((int) $request->get('perPage', 100), 500);
        $results = $query->paginate($perPage);
        $this->preloadSuggestions($results->getCollection());
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
                'total'       => $results->total(),
                'perPage'     => $results->perPage(),
                'currentPage' => $results->currentPage(),
                'lastPage'    => $results->lastPage(),
                'nextPageUrl' => $results->nextPageUrl(),
                'prevPageUrl' => $results->previousPageUrl(),
            ],
            'data' => $records,
        ]);
    }

    private function csvResponse($query, Request $request): StreamedResponse
    {
        $columns = [
            'occurrenceID', 'datasetKey', 'institutionCode', 'collectionCode', 'catalogNumber',
            'basisOfRecord', 'scientificName', 'taxonRank', 'kingdom', 'family',
            'eventDate', 'recordedBy',
            'country', 'countryCode', 'stateProvince', 'county', 'municipality',
            'island', 'islandGroup', 'waterBody', 'verbatimLocality',
            'decimalLatitude', 'decimalLongitude', 'geodeticDatum',
            'coordinateUncertaintyInMeters', 'georeferencedBy', 'georeferencedDate',
            'georeferenceProtocol', 'georeferenceSources', 'georeferenceRemarks',
            'georeferenceVerificationStatus',
        ];

        $filename = 'georeference-it-occurrences-' . now()->format('Y-m-d') . '.csv';

        $callback = function () use ($query, $columns) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM for Excel
            fputcsv($out, $columns);

            $query->with(['localityGroup'])->chunk(500, function ($occurrences) use ($out, $columns) {
                $this->preloadSuggestions($occurrences);
                foreach ($occurrences as $o) {
                    $record = $this->format($o);
                    fputcsv($out, array_map(fn($col) => $record[$col] ?? '', $columns));
                }
            });

            fclose($out);
        };

        return response()->stream($callback, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'X-Accel-Buffering'   => 'no',
        ]);
    }

    public function show(Request $request, string $occurrenceID): Response|JsonResponse
    {
        $occurrence = Occurrence::with(['localityGroup'])
            ->where('gbif_occurrence_key', $occurrenceID)
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
            'phylum'                         => $o->phylum,
            'class'                          => $o->class,
            'order'                          => $o->order,
            'family'                         => $o->family,
            'genus'                          => $o->genus,
            'specificEpithet'                => $o->specific_epithet,
            'typeStatus'                     => $o->type_status,
            'eventDate'                      => $o->event_date,
            'year'                           => $o->year,
            'month'                          => $o->month,
            'day'                            => $o->day,
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

    // Batch-resolves the "winning" suggestion (same ranking resolveGeoref() used to compute
    // per-occurrence: accepted before pending, then highest net weighted vote) for every
    // locality_group_id present in $occurrences, in a single query — replaces what used to
    // be one query per occurrence (with two correlated subqueries each in its ORDER BY),
    // observed taking ~3 minutes for a 99-row page. ROW_NUMBER() OVER (PARTITION BY ...)
    // picks exactly one winner per group in the same pass; MariaDB 10.2+ (this app targets
    // 10.11) supports window functions.
    private function preloadSuggestions(iterable $occurrences): void
    {
        $this->suggestionCache = [];

        $groupIds = collect($occurrences)
            ->filter(fn($o) => in_array($o->georef_status, ['validated', 'has_suggestion', 'conflicted', 'gbif_georeferenced']))
            ->pluck('locality_group_id')
            ->filter()
            ->unique()
            ->values();

        if ($groupIds->isEmpty()) {
            return;
        }

        $placeholders = implode(',', array_fill(0, $groupIds->count(), '?'));
        $rows = DB::select("
            SELECT * FROM (
                SELECT gs.*,
                    ROW_NUMBER() OVER (
                        PARTITION BY gs.locality_group_id
                        ORDER BY FIELD(gs.status, 'accepted', 'pending'),
                            (SELECT COALESCE(SUM(CASE WHEN gv.vote='agree' THEN ul.vote_weight ELSE 0 END), 0)
                             FROM georef_validations gv
                             JOIN users u ON u.id = gv.user_id
                             LEFT JOIN user_levels ul ON ul.id = u.user_level_id
                             WHERE gv.suggestion_id = gs.id)
                            -
                            (SELECT COALESCE(SUM(CASE WHEN gv.vote='disagree' THEN ul.vote_weight ELSE 0 END), 0)
                             FROM georef_validations gv
                             JOIN users u ON u.id = gv.user_id
                             LEFT JOIN user_levels ul ON ul.id = u.user_level_id
                             WHERE gv.suggestion_id = gs.id)
                            DESC
                    ) AS rn
                FROM georef_suggestions gs
                WHERE gs.locality_group_id IN ({$placeholders})
                  AND gs.decimal_latitude IS NOT NULL
                  AND gs.status IN ('accepted', 'pending')
            ) ranked
            WHERE rn = 1
        ", $groupIds->all());

        $prototype = new GeorefSuggestion();
        $suggestions = EloquentCollection::make(
            collect($rows)->map(fn($row) => $prototype->newFromBuilder((array) $row))
        );
        $suggestions->load('user:id,name,public_name,orcid');

        foreach ($suggestions as $suggestion) {
            $this->suggestionCache[$suggestion->locality_group_id] = $suggestion;
        }
    }

    private function resolveGeoref(Occurrence $o): array
    {
        if (in_array($o->georef_status, ['validated', 'has_suggestion', 'conflicted', 'gbif_georeferenced'])) {
            $suggestion = $this->suggestionCache[$o->locality_group_id] ?? null;

            if ($suggestion) {
                return [
                    'lat'         => $suggestion->decimal_latitude,
                    'lng'         => $suggestion->decimal_longitude,
                    'datum'       => $suggestion->geodetic_datum ?? 'WGS84',
                    'uncertainty' => $suggestion->coordinate_uncertainty_m,
                    'by'          => $suggestion->user_id
                                        ? (($suggestion->user?->public_name ?? true)
                                            ? ($suggestion->user?->name . ($suggestion->user?->orcid ? ' (https://orcid.org/' . $suggestion->user->orcid . ')' : ''))
                                            : 'georeference.it contributor')
                                        : 'georeference.it system',
                    'date'        => $suggestion->georeferenced_date ?? $suggestion->created_at?->toDateString(),
                    'protocol'    => $suggestion->georeference_protocol ?? self::GEOREFERENCE_PROTOCOL,
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
