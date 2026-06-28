<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>georeference.it</title>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  html, body { height: 100%; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 12px; color: #374151; background: #fff; }
  body { display: flex; flex-direction: column; height: 100%; overflow: hidden; }

  #map { flex: 1; min-height: 0; }

  .info { padding: 10px 12px; display: flex; flex-direction: column; gap: 6px; flex-shrink: 0; }

  .pill {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 10px; border-radius: 999px; font-size: 11px; font-weight: 600;
    align-self: flex-start;
  }
  .pill.ungeoreferenced   { color: #dc2626; background: #fef2f2; border: 1px solid #fca5a5; }
  .pill.has_suggestion    { color: #d97706; background: #fffbeb; border: 1px solid #fcd34d; }
  .pill.conflicted        { color: #7c3aed; background: #f5f3ff; border: 1px solid #c4b5fd; }
  .pill.validated         { color: #16a34a; background: #f0fdf4; border: 1px solid #86efac; }
  .pill.gbif_georeferenced,
  .pill.gbif_reviewed     { color: #1d4ed8; background: #eff6ff; border: 1px solid #93c5fd; }
  .pill.default           { color: #6b7280; background: #f9fafb; border: 1px solid #e5e7eb; }

  .coords { font-size: 10.5px; color: #6b7280; }
  .coords strong { font-family: monospace; color: #111; font-size: 11px; }
  .coords .uncertainty { color: #9ca3af; }

  .warn { font-size: 10px; color: #c2410c; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 4px; padding: 4px 8px; }

  .remarks { font-size: 10px; color: #6b7280; font-style: italic; }

  .btn {
    display: block; text-align: center; padding: 7px 0;
    background: #4C9C2E; color: #fff; border-radius: 7px;
    font-size: 12px; font-weight: 600; text-decoration: none;
    flex-shrink: 0; margin: 0 12px 10px;
  }
  .btn:hover { background: #3d8025; }

  .not-found { display: flex; align-items: center; justify-content: center; height: 100%; color: #9ca3af; font-size: 12px; }

  .leaflet-control-zoom a { width: 22px !important; height: 22px !important; line-height: 22px !important; }
</style>
</head>
<body>

@if (!$data)
  <div class="not-found">Occurrence not found in georeference.it</div>
@else

@php
  $hasCoords = $data['decimalLatitude'] !== null && $data['georeferenceSources'] !== 'GBIF';
  $isUngeoref = $data['georef_status'] === 'ungeoreferenced';
  $actionLabel = $isUngeoref ? 'Georeference on georeference.it →' : 'View / correct on georeference.it →';
  $pillClass = match($data['georef_status']) {
      'ungeoreferenced', 'has_suggestion', 'conflicted', 'validated', 'gbif_georeferenced', 'gbif_reviewed' => $data['georef_status'],
      default => 'default',
  };
@endphp

@if ($hasCoords)
<div id="map"></div>
@endif

<div class="info">
  <span class="pill {{ $pillClass }}">{{ $data['status_label'] }}</span>

  @if ($hasCoords)
    <div class="coords">
      <strong>{{ number_format((float)$data['decimalLatitude'], 5) }}, {{ number_format((float)$data['decimalLongitude'], 5) }}</strong>
      @if ($data['coordinateUncertaintyInMeters'])
        <span class="uncertainty"> ±{{ number_format($data['coordinateUncertaintyInMeters']) }}m</span>
      @endif
    </div>
  @endif

  @if ($data['diverges_from_gbif'])
    <div class="warn">⚠ Differs from GBIF coordinates</div>
  @endif

  @if ($data['georeferenceRemarks'])
    <div class="remarks">{{ $data['georeferenceRemarks'] }}</div>
  @endif
</div>

@if ($data['georef_url'])
  <a href="{{ $data['georef_url'] }}" target="_blank" class="btn">{{ $actionLabel }}</a>
@endif

@if ($hasCoords)
<script>
(function() {
  var lat = {{ (float)$data['decimalLatitude'] }};
  var lng = {{ (float)$data['decimalLongitude'] }};
  var uncertainty = {{ $data['coordinateUncertaintyInMeters'] ? (float)$data['coordinateUncertaintyInMeters'] : 'null' }};

  var zoom = uncertainty
    ? Math.max(3, Math.min(14, Math.round(14 - Math.log2(uncertainty / 100))))
    : 13;

  var map = L.map('map', { zoomControl: true, attributionControl: false }).setView([lat, lng], zoom);

  L.tileLayer('https://georeference.it/api/v1/tiles/{z}/{x}/{y}', { maxZoom: 18 }).addTo(map);

  if (uncertainty) {
    L.circle([lat, lng], {
      radius: uncertainty,
      color: '#4C9C2E', fillColor: '#4C9C2E', fillOpacity: 0.12, weight: 1.5
    }).addTo(map);
  }

  L.circleMarker([lat, lng], {
    radius: 6, color: '#4C9C2E', fillColor: '#4C9C2E', fillOpacity: 0.9, weight: 2
  }).addTo(map);
})();
</script>
@endif

@endif
</body>
</html>
