<x-layouts.georef>
    <div id="georef-wrap" style="position:relative; height:100vh; width:100vw; display:flex; flex-direction:row;">

        {{-- MAP --}}
        <div id="map" style="flex:1; position:relative; z-index:0;"></div>

        {{-- Top bar overlay (over map only) --}}
        <div style="position:absolute;top:0;left:0;right:380px;z-index:10;" class="flex items-center justify-between px-4 py-3 bg-gradient-to-b from-black/60 to-transparent pointer-events-none">
            <span class="text-white font-bold text-lg tracking-tight pointer-events-auto">georeference.it</span>
            <div class="flex items-center gap-3 pointer-events-auto">
                <select id="country-select" class="text-sm bg-white/20 text-white border border-white/30 rounded-lg px-3 py-1.5 backdrop-blur focus:outline-none">
                    <option value="">{{ __('All countries') }}</option>
                    <option value="PT">Portugal</option>
                    <option value="ES">Spain</option>
                    <option value="GB">United Kingdom</option>
                    <option value="FR">France</option>
                    <option value="DE">Germany</option>
                    <option value="IT">Italy</option>
                    <option value="BR">Brazil</option>
                    <option value="US">United States</option>
                </select>
                @auth
                    <a href="{{ route('dashboard') }}" class="text-sm text-white/80 hover:text-white">{{ __('Dashboard') }}</a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-sm text-white/80 hover:text-white">{{ __('Logout') }}</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-sm bg-white/20 text-white border border-white/30 rounded-lg px-3 py-1.5 backdrop-blur hover:bg-white/30">{{ __('Login') }}</a>
                    <a href="{{ route('register') }}" class="text-sm bg-green-600 text-white rounded-lg px-3 py-1.5 hover:bg-green-700">{{ __('Register') }}</a>
                @endauth
            </div>
        </div>

        {{-- SIDE PANEL — always visible, fixed width --}}
        <div id="side-panel" style="width:380px; flex-shrink:0; z-index:10; display:flex; flex-direction:column; height:100vh; overflow:hidden; position:relative;"
             class="bg-white dark:bg-gray-900 shadow-2xl border-l border-gray-200 dark:border-gray-700">

            {{-- Tooltip div — positioned inside panel --}}
            <div id="occ-tooltip" style="display:none; position:absolute; z-index:100; left:8px; right:8px; pointer-events:none;"
                 class="bg-gray-900 dark:bg-gray-700 text-white text-xs rounded-lg px-3 py-2 shadow-xl"></div>

            {{-- Panel content --}}
            <div class="flex flex-col flex-1 overflow-hidden">

                {{-- Locality info + Nominatim --}}
                <div class="p-3 border-b border-gray-200 dark:border-gray-700 shrink-0">
                    <div id="occurrence-loading" class="text-center py-6 text-gray-400 text-xs">
                        {{ __('Loading occurrences...') }}
                    </div>
                    <div id="occurrence-info" class="hidden">
                        <div id="locality-fields" class="space-y-0.5 mb-2"></div>
                        <div class="flex gap-1 mt-2">
                            <input type="text" id="nominatim-input"
                                class="flex-1 text-xs border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1.5 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500"
                                placeholder="{{ __('Search locality on map...') }}">
                            <button id="nominatim-btn" class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1.5 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 shrink-0">🔍</button>
                        </div>
                        <div id="nominatim-results" class="mt-1 space-y-1 max-h-36 overflow-y-auto"></div>
                    </div>
                </div>

                {{-- Occurrences list --}}
                <div class="p-3 border-b border-gray-200 dark:border-gray-700 shrink-0">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('Occurrences in this group') }}</span>
                        <span id="occurrence-count" class="text-xs text-gray-400"></span>
                    </div>
                    <div id="occurrences-list" class="space-y-0.5 overflow-y-auto" style="max-height:180px;"></div>
                </div>

                {{-- Existing suggestions --}}
                <div id="existing-suggestions" class="p-3 border-b border-gray-200 dark:border-gray-700 hidden shrink-0">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('Existing suggestions') }}</span>
                    <div id="suggestions-list" class="mt-1 space-y-2"></div>
                </div>

                {{-- Georef form --}}
                <div class="p-3 flex-1 overflow-y-auto">
                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-3">
                        {{ __('Click on the map to place a point. Drag to adjust.') }}
                    </p>
                    <form id="georef-form" class="space-y-2">
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('Latitude') }}</label>
                                <input type="number" id="lat-input" step="0.0000001"
                                    class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1.5 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500"
                                    placeholder="0.0000000">
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('Longitude') }}</label>
                                <input type="number" id="lng-input" step="0.0000001"
                                    class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1.5 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500"
                                    placeholder="0.0000000">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">
                                {{ __('Uncertainty') }} <span id="uncertainty-display" class="text-green-600 font-semibold"></span>
                            </label>
                            <input type="number" id="uncertainty-input" min="1"
                                class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1.5 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500"
                                placeholder="1000">
                            <input type="range" id="uncertainty-slider" min="100" max="100000" step="100" value="1000"
                                class="w-full mt-1 accent-green-600">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('Remarks') }}</label>
                            <textarea id="remarks-input" rows="2"
                                class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1.5 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500"
                                placeholder="{{ __('Optional notes...') }}"></textarea>
                        </div>
                        @guest
                        <div>
                            <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('Your name (optional)') }}</label>
                            <input type="text" id="anon-name"
                                class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1.5 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500"
                                placeholder="{{ __('Anonymous') }}">
                        </div>
                        @endguest
                    </form>
                </div>

                {{-- Discussion --}}
                <div class="p-3 border-t border-gray-200 dark:border-gray-700 shrink-0">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('Discussion') }}</span>
                    <div id="comments-list" class="mt-1 space-y-1 max-h-24 overflow-y-auto"></div>
                    @auth
                    <div class="mt-2 flex gap-1">
                        <input type="text" id="comment-input"
                            class="flex-1 text-xs border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1.5 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500"
                            placeholder="{{ __('Add a comment...') }}">
                        <button id="comment-submit" class="text-xs bg-gray-100 dark:bg-gray-700 px-3 py-1.5 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">{{ __('Send') }}</button>
                    </div>
                    @endauth
                </div>

                {{-- Action buttons --}}
                <div class="p-3 border-t border-gray-200 dark:border-gray-700 flex gap-2 shrink-0">
                    <button id="skip-btn" class="flex-1 text-sm border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 rounded-lg py-2 hover:bg-gray-50 dark:hover:bg-gray-800">
                        {{ __('Skip') }}
                    </button>
                    <button id="submit-btn" class="flex-1 text-sm bg-green-600 text-white rounded-lg py-2 hover:bg-green-700 font-medium disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        {{ __('Submit') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        const APP_URL = document.querySelector('meta[name="app-url"]').getAttribute('content');
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const THRESHOLD = {{ \App\Models\PlatformSetting::get('validation_threshold', 60) }};
        let map, marker, circle, currentGroup = null;

        // ── Map init ──────────────────────────────────────────────────────────
        map = L.map('map', { zoomControl: false }).setView([39.5, -8.0], 6);

        const osm = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors', maxZoom: 19
        });
        const esriSat = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles © Esri — Source: Esri, Maxar, GeoEye, Earthstar Geographics, CNES/Airbus DS, USDA, USGS', maxZoom: 19
        });
        const esriLabels = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 19, pane: 'overlayPane'
        });
        const esriSatLabels = L.layerGroup([esriSat, esriLabels]);
        const esriStreet = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles © Esri', maxZoom: 19
        });
        const esriTopo = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles © Esri', maxZoom: 19
        });

        osm.addTo(map);
        L.control.layers({
            'OpenStreetMap': osm,
            'ESRI Satellite': esriSat,
            'ESRI Satellite + Labels': esriSatLabels,
            'ESRI Street Map': esriStreet,
            'ESRI Topo': esriTopo,
        }, {}, { position: 'bottomleft' }).addTo(map);
        L.control.zoom({ position: 'bottomleft' }).addTo(map);

        // ── Map click ─────────────────────────────────────────────────────────
        map.on('click', function(e) { placeMarker(e.latlng.lat, e.latlng.lng); });

        function placeMarker(lat, lng) {
            const uncertainty = parseInt(document.getElementById('uncertainty-input').value) || 1000;
            if (marker) { map.removeLayer(marker); marker = null; }
            if (circle) { map.removeLayer(circle); circle = null; }
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            circle = L.circle([lat, lng], {
                radius: uncertainty, color: '#16a34a', fillColor: '#16a34a', fillOpacity: 0.15, weight: 2
            }).addTo(map);
            document.getElementById('lat-input').value = lat.toFixed(7);
            document.getElementById('lng-input').value = lng.toFixed(7);
            document.getElementById('uncertainty-display').textContent = uncertainty.toLocaleString() + 'm';
            document.getElementById('uncertainty-slider').value = Math.min(uncertainty, 100000);
            document.getElementById('submit-btn').disabled = false;
            marker.on('drag', function(e) {
                const pos = e.target.getLatLng();
                circle.setLatLng(pos);
                document.getElementById('lat-input').value = pos.lat.toFixed(7);
                document.getElementById('lng-input').value = pos.lng.toFixed(7);
            });
        }

        // ── Uncertainty controls ──────────────────────────────────────────────
        document.getElementById('uncertainty-input').addEventListener('input', function() {
            const val = parseInt(this.value) || 1000;
            document.getElementById('uncertainty-slider').value = Math.min(val, 100000);
            document.getElementById('uncertainty-display').textContent = val.toLocaleString() + 'm';
            if (circle) circle.setRadius(val);
        });
        document.getElementById('uncertainty-slider').addEventListener('input', function() {
            const val = parseInt(this.value);
            document.getElementById('uncertainty-input').value = val;
            document.getElementById('uncertainty-display').textContent = val.toLocaleString() + 'm';
            if (circle) circle.setRadius(val);
        });

        // ── Tooltip ───────────────────────────────────────────────────────────
        const tooltipEl = document.getElementById('occ-tooltip');
        const panelEl   = document.getElementById('side-panel');

function showTooltip(el, data) {
    const lines = [
        data.institution ? `<span class="text-gray-400">Institution:</span> ${data.institution}` : null,
        data.collection  ? `<span class="text-gray-400">Collection:</span> ${data.collection}` : null,
        data.dataset     ? `<span class="text-gray-400">Dataset:</span> <span class="font-mono text-xs">${data.dataset}</span>` : null,
        data.basis       ? `<span class="text-gray-400">Basis:</span> ${data.basis}` : null,
        data.key         ? `<span class="text-gray-400">GBIF key:</span> ${data.key}` : null,
    ].filter(Boolean);
    if (!lines.length) return;
    tooltipEl.innerHTML = lines.map(l => `<div class="py-0.5">${l}</div>`).join('');
    const rect      = el.getBoundingClientRect();
    const panelRect = panelEl.getBoundingClientRect();
    tooltipEl.style.top = (rect.bottom - panelRect.top + 4) + 'px';
    tooltipEl.style.display = 'block';
}
        function hideTooltip() { tooltipEl.style.display = 'none'; }

        // ── Nominatim ─────────────────────────────────────────────────────────
        function buildLocalityString(group) {
            return [group.verbatim_locality, group.municipality, group.county, group.state_province, group.country_code]
                .filter(Boolean).join(', ');
        }

        async function searchNominatim(query) {
            if (!query) return;
            document.getElementById('nominatim-results').innerHTML = '<p class="text-xs text-gray-400 p-1">{{ __("Searching...") }}</p>';
            try {
                const resp = await fetch(
                    `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(query)}&format=json&polygon_geojson=1&limit=5`,
                    { headers: { 'Accept-Language': 'en' } }
                );
                const results = await resp.json();
                if (!results.length) {
                    document.getElementById('nominatim-results').innerHTML = '<p class="text-xs text-gray-400 p-1">{{ __("No results found.") }}</p>';
                    return;
                }
                window._nominatimResults = results;
                document.getElementById('nominatim-results').innerHTML = results.map((r, i) => `
                    <button onclick="applyNominatimResult(${i})"
                        class="w-full text-left text-xs p-1.5 rounded hover:bg-green-50 dark:hover:bg-green-900/20 border border-gray-100 dark:border-gray-700">
                        <span class="font-medium text-gray-700 dark:text-gray-300 block truncate">${r.display_name}</span>
                        <span class="text-gray-400">${r.type} · ${parseFloat(r.lat).toFixed(4)}, ${parseFloat(r.lon).toFixed(4)}</span>
                    </button>
                `).join('');
            } catch (e) {
                document.getElementById('nominatim-results').innerHTML = '<p class="text-xs text-red-400 p-1">{{ __("Search failed.") }}</p>';
            }
        }

        function applyNominatimResult(index) {
            const r = window._nominatimResults[index];
            const lat = parseFloat(r.lat);
            const lon = parseFloat(r.lon);
            if (r.geojson && (r.geojson.type === 'Polygon' || r.geojson.type === 'MultiPolygon')) {
                if (window._nominatimPolygon) map.removeLayer(window._nominatimPolygon);
                window._nominatimPolygon = L.geoJSON(r.geojson, {
                    style: { color: '#16a34a', weight: 2, fillOpacity: 0.05 }
                }).addTo(map);
                const bounds = window._nominatimPolygon.getBounds();
                const center = bounds.getCenter();
                const vertices = [];
                function collectVertices(coords) {
                    if (Array.isArray(coords[0])) { coords.forEach(c => collectVertices(c)); }
                    else { vertices.push(coords); }
                }
                if (r.geojson.type === 'Polygon') {
                    r.geojson.coordinates.forEach(ring => collectVertices(ring));
                } else {
                    r.geojson.coordinates.forEach(poly => poly.forEach(ring => collectVertices(ring)));
                }
                const R = 6371000;
                let maxDist = 0;
                vertices.forEach(([vLon, vLat]) => {
                    const dLat = (vLat - center.lat) * Math.PI / 180;
                    const dLon = (vLon - center.lng) * Math.PI / 180;
                    const a = Math.sin(dLat/2)**2 + Math.cos(center.lat*Math.PI/180) * Math.cos(vLat*Math.PI/180) * Math.sin(dLon/2)**2;
                    const dist = R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
                    if (dist > maxDist) maxDist = dist;
                });
                const uncertainty = Math.round(maxDist);
                document.getElementById('uncertainty-input').value = uncertainty;
                document.getElementById('uncertainty-slider').value = Math.min(uncertainty, 100000);
                document.getElementById('uncertainty-display').textContent = uncertainty.toLocaleString() + 'm';
                placeMarker(center.lat, center.lng);
                map.fitBounds(bounds, { padding: [20, 20] });
            } else {
                placeMarker(lat, lon);
                map.flyTo([lat, lon], 12);
            }
            document.getElementById('nominatim-results').innerHTML = '';
        }

        document.getElementById('nominatim-btn').addEventListener('click', () => {
            searchNominatim(document.getElementById('nominatim-input').value.trim());
        });
        document.getElementById('nominatim-input').addEventListener('keydown', e => {
            if (e.key === 'Enter') searchNominatim(e.target.value.trim());
        });

        // ── Load next group ───────────────────────────────────────────────────
        loadNextGroup();

        function loadNextGroup() {
            if (marker) { map.removeLayer(marker); marker = null; }
            if (circle) { map.removeLayer(circle); circle = null; }
            if (window._nominatimPolygon) { map.removeLayer(window._nominatimPolygon); window._nominatimPolygon = null; }
            hideTooltip();
            document.getElementById('submit-btn').disabled = true;
            document.getElementById('lat-input').value = '';
            document.getElementById('lng-input').value = '';
            document.getElementById('uncertainty-display').textContent = '';
            document.getElementById('remarks-input').value = '';
            const country = document.getElementById('country-select').value;
            document.getElementById('occurrence-loading').classList.remove('hidden');
            document.getElementById('occurrence-info').classList.add('hidden');
            fetch(`${APP_URL}/georef/next?country=${country}`, {
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                document.getElementById('occurrence-loading').classList.add('hidden');
                if (data.group) {
                    currentGroup = data.group;
                    renderGroup(data.group, data.occurrences, data.suggestions, data.comments);
                } else {
                    document.getElementById('occurrence-info').classList.remove('hidden');
                    document.getElementById('locality-fields').innerHTML =
                        '<p class="text-gray-400 text-xs">{{ __("No occurrences found. Try a different country.") }}</p>';
                }
            })
            .catch(() => { document.getElementById('occurrence-loading').classList.add('hidden'); });
        }
const occTooltipData = new Map();
        // ── Render group ──────────────────────────────────────────────────────
        function renderGroup(group, occurrences, suggestions, comments) {
            document.getElementById('occurrence-info').classList.remove('hidden');

            const fields = ['verbatim_locality','country','state_province','county','municipality','island','water_body'].filter(f => group[f]);
            document.getElementById('locality-fields').innerHTML = fields.map(f =>
                `<div class="flex gap-2">
                    <span class="text-gray-400 w-28 shrink-0 text-xs">${f.replace(/_/g,' ')}</span>
                    <span class="text-gray-700 dark:text-gray-200 text-xs font-medium">${group[f]}</span>
                </div>`
            ).join('');

            document.getElementById('nominatim-input').value = buildLocalityString(group);
            document.getElementById('nominatim-results').innerHTML = '';

            // Occurrences list
            document.getElementById('occurrence-count').textContent = `${occurrences.length} {{ __('occurrences') }}`;
            document.getElementById('occurrences-list').innerHTML = occurrences.map(o => {
                const label   = [o.recorded_by, o.event_date].filter(Boolean).join(' · ') || o.gbif_occurrence_key;
                const taxon   = o.scientific_name || '';
                const meta    = [o.institution_code, o.collection_code].filter(Boolean).join(' · ');
                const dataset = o.dataset_key || '';
                // Tooltip lines
const occId = `occ-${o.id}`;
occTooltipData.set(occId, {
    institution: o.institution_code || '',
    collection: o.collection_code || '',
    dataset: o.dataset_key || '',
    basis: o.basis_of_record || '',
    key: o.gbif_occurrence_key || '',
});

                return `
                    <div class="occ-row text-xs rounded border border-transparent hover:border-gray-200 dark:hover:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors cursor-default"
                         id="${occId}">
                        <div class="flex items-start gap-2 p-1.5">
                            <input type="checkbox" class="occurrence-checkbox shrink-0 mt-0.5" value="${o.id}" checked>
                            <div class="flex-1 min-w-0">
                                <div class="truncate text-gray-700 dark:text-gray-300">${label}</div>
                                ${taxon ? `<div class="truncate text-gray-400 italic">${taxon}</div>` : ''}
                                ${meta  ? `<div class="truncate text-gray-400">${meta}</div>` : ''}
                            </div>
                            <a href="https://www.gbif.org/occurrence/${o.gbif_occurrence_key}" target="_blank"
                               class="text-green-600 hover:underline shrink-0">↗</a>
                            ${o.media && o.media.length > 0
                                ? `<img src="${o.media[0].identifier}" class="h-7 w-7 rounded object-cover cursor-pointer shrink-0 border border-gray-200"
                                        onclick="window.open('${o.media[0].identifier}')">`
                                : ''}
                        </div>
                    </div>`;
            }).join('');

            // Attach tooltip events
document.querySelectorAll('.occ-row').forEach(row => {
    row.addEventListener('mouseenter', function() {
        const data = occTooltipData.get(this.id);
        if (data) showTooltip(this, data);
    });
    row.addEventListener('mouseleave', hideTooltip);
});

            // Suggestions
            if (suggestions && suggestions.length > 0) {
                document.getElementById('existing-suggestions').classList.remove('hidden');
                document.getElementById('suggestions-list').innerHTML = suggestions.map(s => `
                    <div class="text-xs border border-gray-200 dark:border-gray-700 rounded-lg p-2">
                        <div class="flex justify-between">
                            <span class="font-medium">${parseFloat(s.decimal_latitude).toFixed(5)}, ${parseFloat(s.decimal_longitude).toFixed(5)}</span>
                            <span class="text-gray-400">±${s.coordinate_uncertainty_m}m</span>
                        </div>
                        <div class="flex justify-between mt-1 text-gray-400">
                            <span>${s.submitted_by}</span>
                            <div class="flex gap-2">
                                <button onclick="validateSuggestion(${s.id}, 'agree')" class="text-green-600 hover:underline">{{ __('Agree') }}</button>
                                <button onclick="validateSuggestion(${s.id}, 'disagree')" class="text-red-500 hover:underline">{{ __('Disagree') }}</button>
                            </div>
                        </div>
                        <div class="bg-gray-100 dark:bg-gray-700 rounded-full h-1 mt-2">
                            <div class="bg-green-500 h-1 rounded-full" style="width:${Math.min(100, (s.total_points / THRESHOLD) * 100)}%"></div>
                        </div>
                        <button onclick="previewSuggestion(${s.decimal_latitude}, ${s.decimal_longitude}, ${s.coordinate_uncertainty_m})"
                                class="mt-1 text-blue-500 hover:underline text-xs">{{ __('Preview on map') }}</button>
                    </div>
                `).join('');
            } else {
                document.getElementById('existing-suggestions').classList.add('hidden');
            }

            renderComments(comments ?? []);

            // Fly to location
            const countryFlights = {
                'PT': [39.5, -8.0, 7], 'ES': [40.0, -3.7, 6], 'GB': [54.0, -2.0, 6],
                'FR': [46.5, 2.3, 6],  'DE': [51.2, 10.4, 6], 'IT': [42.5, 12.5, 6],
                'BR': [-14.2, -51.9, 4], 'US': [37.1, -95.7, 4],
            };
            if (group.gbif_decimal_latitude && group.gbif_decimal_longitude) {
                map.flyTo([group.gbif_decimal_latitude, group.gbif_decimal_longitude], 10);
            } else {
                const fly = countryFlights[group.country_code];
                if (fly) map.flyTo([fly[0], fly[1]], fly[2]);
            }
        }

        // ── Helpers ───────────────────────────────────────────────────────────
        function renderComments(comments) {
            document.getElementById('comments-list').innerHTML = comments.map(c => `
                <div class="text-xs border-b border-gray-100 dark:border-gray-800 pb-1">
                    <span class="font-medium text-gray-700 dark:text-gray-300">${c.user_name}</span>
                    <span class="text-gray-400 ml-1">${c.created_at}</span>
                    <p class="text-gray-600 dark:text-gray-400 mt-0.5">${c.body}</p>
                </div>
            `).join('');
        }

        function previewSuggestion(lat, lng, uncertainty) {
            if (marker) { map.removeLayer(marker); marker = null; }
            if (circle) { map.removeLayer(circle); circle = null; }
            circle = L.circle([lat, lng], {
                radius: uncertainty || 1000,
                color: '#3b82f6', fillColor: '#3b82f6', fillOpacity: 0.1, weight: 2, dashArray: '6'
            }).addTo(map);
            map.flyTo([lat, lng], 12);
        }

        function validateSuggestion(suggestionId, vote) {
            fetch(`${APP_URL}/georef/validate/${suggestionId}`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ vote })
            })
            .then(r => r.json())
            .then(data => { if (data.success) loadNextGroup(); });
        }

        document.getElementById('submit-btn').addEventListener('click', function() {
            if (!currentGroup) return;
            const excludedIds = Array.from(document.querySelectorAll('.occurrence-checkbox:not(:checked)')).map(c => c.value);
            fetch(`${APP_URL}/georef/submit`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({
                    locality_group_id: currentGroup.id,
                    decimal_latitude: document.getElementById('lat-input').value,
                    decimal_longitude: document.getElementById('lng-input').value,
                    coordinate_uncertainty_m: document.getElementById('uncertainty-input').value,
                    georeference_remarks: document.getElementById('remarks-input').value,
                    anon_name: document.getElementById('anon-name')?.value ?? null,
                    excluded_occurrence_ids: excludedIds,
                })
            })
            .then(r => r.json())
            .then(data => { if (data.success) loadNextGroup(); });
        });

        document.getElementById('skip-btn').addEventListener('click', loadNextGroup);
        document.getElementById('country-select').addEventListener('change', loadNextGroup);

        const commentSubmit = document.getElementById('comment-submit');
        if (commentSubmit) {
            commentSubmit.addEventListener('click', function() {
                const body = document.getElementById('comment-input').value.trim();
                if (!body || !currentGroup) return;
                fetch(`${APP_URL}/georef/comment`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ locality_group_id: currentGroup.id, body })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('comment-input').value = '';
                        renderComments(data.comments);
                    }
                });
            });
        }

        // ── Mobile layout ─────────────────────────────────────────────────────
        function applyLayout() {
            const wrap   = document.getElementById('georef-wrap');
            const panel  = document.getElementById('side-panel');
            const mapDiv = document.getElementById('map');
            if (window.innerWidth < 768) {
                wrap.style.flexDirection = 'column';
                panel.style.width  = '100%';
                panel.style.height = '55vh';
                mapDiv.style.height = '45vh';
                mapDiv.style.flex  = 'none';
            } else {
                wrap.style.flexDirection = 'row';
                panel.style.width  = '380px';
                panel.style.height = '100vh';
                mapDiv.style.height = '';
                mapDiv.style.flex  = '1';
            }
            map.invalidateSize();
        }
        applyLayout();
        window.addEventListener('resize', applyLayout);
    </script>
    @endpush
</x-layouts.georef>