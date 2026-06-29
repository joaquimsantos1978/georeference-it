<?php

namespace App\Http\Controllers;

use App\Models\GeorefSuggestion;
use App\Models\Occurrence;
use Illuminate\Http\Response;

class EmbedController extends Controller
{
    public function occurrence(string $gbifKey): Response
    {
        $occurrence = Occurrence::with(['localityGroup'])
            ->where('gbif_occurrence_key', $gbifKey)
            ->first();

        $data = $occurrence ? $this->format($occurrence) : null;

        $html = view('embed.occurrence', compact('data'))->render();

        return response($html, 200, [
            'Content-Type'    => 'text/html; charset=utf-8',
            'X-Frame-Options' => 'ALLOWALL',
            'Content-Security-Policy' => "frame-ancestors *",
        ]);
    }

    private function format(Occurrence $o): array
    {
        $georef = $this->resolveGeoref($o);

        $statusLabels = [
            'ungeoreferenced'    => 'Needs georeferencing',
            'has_suggestion'     => 'Suggestion pending',
            'conflicted'         => 'Conflicted',
            'validated'          => 'Validated',
            'gbif_georeferenced' => 'GBIF georef',
            'gbif_reviewed'      => 'GBIF reviewed',
        ];

        return [
            'georef_status'                 => $o->georef_status,
            'status_label'                  => $statusLabels[$o->georef_status] ?? $o->georef_status,
            'decimalLatitude'               => $georef['lat'],
            'decimalLongitude'              => $georef['lng'],
            'coordinateUncertaintyInMeters' => $georef['uncertainty'],
            'georeferenceRemarks'           => $georef['remarks'],
            'georeferenceSources'           => $georef['sources'],
            'georef_url'                    => $o->locality_group_id
                ? rtrim(config('app.url'), '/') . '/georef?group=' . $o->locality_group_id
                : null,
            'diverges_from_gbif'            => $this->divergesFromGbif($o, $georef),
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
                ->orderByRaw("
                    (SELECT COALESCE(SUM(CASE WHEN gv.vote='agree' THEN ul.vote_weight ELSE 0 END), 0)
                     FROM georef_validations gv
                     JOIN users u ON u.id = gv.user_id
                     LEFT JOIN user_levels ul ON ul.id = u.user_level_id
                     WHERE gv.suggestion_id = georef_suggestions.id)
                    -
                    (SELECT COALESCE(SUM(CASE WHEN gv.vote='disagree' THEN ul.vote_weight ELSE 0 END), 0)
                     FROM georef_validations gv
                     JOIN users u ON u.id = gv.user_id
                     LEFT JOIN user_levels ul ON ul.id = u.user_level_id
                     WHERE gv.suggestion_id = georef_suggestions.id)
                    DESC
                ")
                ->first();

            if ($suggestion) {
                return [
                    'lat'         => $suggestion->decimal_latitude,
                    'lng'         => $suggestion->decimal_longitude,
                    'uncertainty' => $suggestion->coordinate_uncertainty_m,
                    'sources'     => $suggestion->georeference_sources ?? 'georeference.it',
                    'remarks'     => $suggestion->georeference_remarks,
                ];
            }
        }

        if ($o->gbif_decimal_latitude !== null) {
            return [
                'lat'         => $o->gbif_decimal_latitude,
                'lng'         => $o->gbif_decimal_longitude,
                'uncertainty' => $o->gbif_coordinate_uncertainty_m,
                'sources'     => 'GBIF',
                'remarks'     => null,
            ];
        }

        return ['lat' => null, 'lng' => null, 'uncertainty' => null, 'sources' => null, 'remarks' => null];
    }

    private function divergesFromGbif(Occurrence $o, array $georef): bool
    {
        if ($o->gbif_decimal_latitude === null || $georef['lat'] === null || $georef['sources'] === 'GBIF') return false;
        return abs((float) $georef['lat'] - (float) $o->gbif_decimal_latitude) > 0.0001
            || abs((float) $georef['lng'] - (float) $o->gbif_decimal_longitude) > 0.0001;
    }
}
