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
  html, body { height: 100%; background: #fff; color: #111827;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
    font-size: 12px; line-height: 1.4; }
  body { display: flex; flex-direction: column; overflow: hidden; }

  #map { flex: 1; min-height: 0; }

  .footer { padding: 9px 11px 8px; display: flex; flex-direction: column; gap: 5px; flex-shrink: 0; border-top: 1px solid #f3f4f6; }

  .row-status { display: flex; align-items: center; justify-content: space-between; }

  .logo { font-size: 10px; font-weight: 600; color: #4C9C2E; letter-spacing: .3px; }

  .pill {
    display: inline-flex; align-items: center;
    padding: 1px 8px; border-radius: 999px; font-size: 10.5px; font-weight: 600;
  }
  .pill.ungeoreferenced   { color: #dc2626; background: #fef2f2; border: 1px solid #fca5a5; }
  .pill.has_suggestion    { color: #d97706; background: #fffbeb; border: 1px solid #fcd34d; }
  .pill.conflicted        { color: #7c3aed; background: #f5f3ff; border: 1px solid #c4b5fd; }
  .pill.validated         { color: #16a34a; background: #f0fdf4; border: 1px solid #86efac; }
  .pill.gbif_georeferenced,
  .pill.gbif_reviewed     { color: #1d4ed8; background: #eff6ff; border: 1px solid #93c5fd; }
  .pill.unknown           { color: #6b7280; background: #f9fafb; border: 1px solid #e5e7eb; }

  .coords { font-size: 11px; color: #374151; }
  .coords code { font-family: 'SFMono-Regular', Consolas, monospace; font-size: 11px; }
  .uncertainty { color: #9ca3af; font-size: 10.5px; margin-left: 3px; }

  .remarks { font-size: 10px; color: #6b7280; font-style: italic; }

  .warn { display: flex; align-items: flex-start; gap: 4px; font-size: 10px; color: #92400e;
          background: #fffbeb; border: 1px solid #fde68a; border-radius: 4px; padding: 3px 7px; }

  .btn {
    display: block; text-align: center; padding: 6px 0; margin-top: 1px;
    background: #fff; color: #4C9C2E; border-radius: 4px;
    border: 1px solid #4C9C2E;
    font-size: 11.5px; font-weight: 600; text-decoration: none;
  }
  .btn:hover { background: #f0fdf4; }

  .not-found { display: flex; align-items: center; justify-content: center;
               height: 100%; color: #9ca3af; font-size: 11px; }

  .leaflet-control-zoom a { width: 22px !important; height: 22px !important; line-height: 22px !important; font-size: 13px !important; }
  .leaflet-control-attribution { display: none; }
</style>
</head>
<body>

@if (!$data)
  <div class="not-found">Not found in georeference.it</div>
@else

@php
  $hasCoords = $data['decimalLatitude'] !== null;
  $isUngeoref = $data['georef_status'] === 'ungeoreferenced';
  $actionLabel = $isUngeoref ? 'Georeference on georeference.it →' : 'View / correct →';
  $pillClass = in_array($data['georef_status'], ['ungeoreferenced','has_suggestion','conflicted','validated','gbif_georeferenced','gbif_reviewed'])
    ? $data['georef_status'] : 'unknown';
@endphp

@if ($hasCoords)
<div id="map"></div>
@endif

<div class="footer">
  <div class="row-status">
    <span class="logo">georeference.it</span>
    <span class="pill {{ $pillClass }}">{{ $data['status_label'] }}</span>
  </div>

  @if ($hasCoords)
    <div class="coords">
      <code>{{ number_format((float)$data['decimalLatitude'], 5) }}, {{ number_format((float)$data['decimalLongitude'], 5) }}</code>
      @if ($data['coordinateUncertaintyInMeters'])
        <span class="uncertainty">±{{ number_format($data['coordinateUncertaintyInMeters']) }}m</span>
      @endif
    </div>
  @endif

  @if ($data['diverges_from_gbif'])
    <div class="coords" style="color:#9ca3af;font-size:10px;margin-top:-2px">
      <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#9ca3af;margin-right:3px;vertical-align:middle"></span>GBIF
      <code style="font-size:10px">{{ number_format((float)$data['gbif_lat'], 5) }}, {{ number_format((float)$data['gbif_lng'], 5) }}</code>
    </div>
  @endif

  @if ($data['georeferenceRemarks'])
    <div class="remarks">{{ $data['georeferenceRemarks'] }}</div>
  @endif

  @if ($data['georef_url'])
    <a href="{{ $data['georef_url'] }}" target="_blank" class="btn">{{ $actionLabel }}</a>
  @endif
</div>

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
      radius: uncertainty, color: '#4C9C2E', fillColor: '#4C9C2E',
      fillOpacity: 0.1, weight: 1.5
    }).addTo(map);
  }

  L.circleMarker([lat, lng], {
    radius: 5, color: '#fff', fillColor: '#4C9C2E', fillOpacity: 1, weight: 2
  }).addTo(map);

  @if ($data['gbif_lat'] && $data['diverges_from_gbif'])
  var gbifLat = {{ (float)$data['gbif_lat'] }};
  var gbifLng = {{ (float)$data['gbif_lng'] }};
  L.circleMarker([gbifLat, gbifLng], {
    radius: 4, color: '#fff', fillColor: '#9ca3af', fillOpacity: 0.8, weight: 2
  }).bindTooltip('GBIF', {permanent: false, direction: 'top'}).addTo(map);
  @endif
})();
</script>
@endif

@endif
</body>
</html>
