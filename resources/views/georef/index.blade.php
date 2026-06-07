<x-layouts.georef>
    <div id="georef-wrap" style="position:relative; height:100%; width:100%; display:flex; flex-direction:row;">

        {{-- MAP --}}
        <div id="map" style="flex:1; position:relative; z-index:0;"></div>

        {{-- Draggable image viewer --}}
        <div id="img-viewer" style="display:none; position:absolute; top:60px; left:12px; z-index:25; width:360px; height:320px; min-width:200px; min-height:150px;"
            class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl border border-gray-300 dark:border-gray-600 flex flex-col overflow-hidden">
            <div id="img-viewer-bar" class="flex items-center justify-between px-3 py-1.5 bg-gray-100 dark:bg-gray-800 cursor-move select-none shrink-0 border-b border-gray-200 dark:border-gray-700">
                <span id="img-viewer-title" class="text-xs text-gray-500 truncate flex-1 mr-2"></span>
                <div class="flex items-center gap-2 shrink-0">
                    <a id="img-viewer-link" href="#" target="_blank" class="text-xs text-green-600 hover:underline">{{ __('Full size') }}</a>
                    <button onclick="closeImgViewer()" class="text-gray-400 hover:text-gray-600 text-sm leading-none ml-1">✕</button>
                </div>
            </div>
            <div class="flex items-center gap-1 px-2 py-1 bg-gray-50 dark:bg-gray-800 shrink-0 border-b border-gray-100 dark:border-gray-700">
                <button onclick="zoomImg(-0.25)" class="text-xs bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded px-2 py-0.5 hover:bg-gray-50">−</button>
                <button onclick="zoomImg(0.25)" class="text-xs bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded px-2 py-0.5 hover:bg-gray-50">+</button>
                <button onclick="resetImgZoom()" class="text-xs bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded px-2 py-0.5 hover:bg-gray-50">1:1</button>
                <span id="img-zoom-label" class="text-xs text-gray-400 ml-1">100%</span>
                <span class="text-xs text-gray-300 ml-auto">{{ __('scroll to zoom · drag to pan') }}</span>
            </div>
            <div id="img-pan-area" class="flex-1 overflow-hidden relative cursor-grab" style="background:#f3f4f6;">
                <img id="img-viewer-img" src="" alt="" style="position:absolute; transform-origin:0 0; cursor:grab; user-select:none;" draggable="false">
            </div>
            <div id="img-resize-handle" style="position:absolute; bottom:0; right:0; width:16px; height:16px; cursor:se-resize; z-index:10;">
                <svg viewBox="0 0 16 16" style="width:16px;height:16px;opacity:0.4;">
                    <line x1="4" y1="16" x2="16" y2="4" stroke="#6b7280" stroke-width="1.5"/>
                    <line x1="8" y1="16" x2="16" y2="8" stroke="#6b7280" stroke-width="1.5"/>
                    <line x1="12" y1="16" x2="16" y2="12" stroke="#6b7280" stroke-width="1.5"/>
                </svg>
            </div>
        </div>

        {{-- SIDE PANEL --}}
        <div id="side-panel" style="width:380px; flex-shrink:0; z-index:10; display:flex; flex-direction:column; height:100%; overflow:hidden; position:relative;"
            class="bg-white dark:bg-gray-900 shadow-2xl border-l border-gray-200 dark:border-gray-700">
            <div id="occ-tooltip" style="display:none; position:absolute; z-index:100; left:8px; right:8px; pointer-events:none;"
                class="bg-gray-900 dark:bg-gray-700 text-white text-xs rounded-lg px-3 py-2 shadow-xl"></div>

            <div class="flex flex-col flex-1 overflow-hidden">

                {{-- Country selector --}}
                <div style="flex-shrink:0; border-bottom:1px solid #e5e7eb; padding:8px 12px;">
                    <select id="country-select" class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded px-2 py-1.5 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500">
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
                </div>

                {{-- Locality + Nominatim --}}
                <div class="p-3 border-b border-gray-200 dark:border-gray-700 shrink-0">
                    <div id="occurrence-loading" class="text-center py-4 text-gray-400 text-xs">{{ __('Loading occurrences...') }}</div>
                    <div id="occurrence-info" class="hidden">
                        <div id="locality-fields" class="space-y-0.5 mb-2"></div>
                        <div class="flex gap-1 mt-2">
                            <input type="text" id="nominatim-input" class="flex-1 text-xs border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1.5 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500" placeholder="{{ __('Search locality on map...') }}">
                            <button id="nominatim-btn" class="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-1.5 rounded-lg hover:bg-gray-200 shrink-0">🔍</button>
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
                    <div id="occurrences-list" class="space-y-0.5 overflow-y-auto" style="max-height:160px;"></div>
                </div>

                {{-- Existing suggestions --}}
                <div id="existing-suggestions" class="p-3 border-b border-gray-200 dark:border-gray-700 hidden shrink-0">
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">{{ __('Existing suggestions') }}</span>
                    <div id="suggestions-list" class="mt-1 space-y-2"></div>
                </div>

                {{-- Georef form --}}
                <div class="p-3 flex-1 overflow-y-auto">
                    <p class="text-xs text-gray-400 mb-2">{{ __('Click on the map to place a point. Drag to adjust.') }}</p>
                    <form id="georef-form" class="space-y-2">
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Latitude') }}</label>
                                <input type="number" id="lat-input" step="0.0000001" class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1.5 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500" placeholder="0.0000000">
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Longitude') }}</label>
                                <input type="number" id="lng-input" step="0.0000001" class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1.5 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500" placeholder="0.0000000">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Uncertainty') }} <span id="uncertainty-display" class="text-green-600 font-semibold"></span></label>
                            <input type="number" id="uncertainty-input" min="1" class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1.5 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500" placeholder="1000">
                            <input type="range" id="uncertainty-slider" min="100" max="500000" step="1000" value="1000" class="w-full mt-1 accent-green-600">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Remarks') }}</label>
                            <textarea id="remarks-input" rows="2" class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1.5 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500" placeholder="{{ __('Optional notes...') }}"></textarea>
                        </div>
                        @guest
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Your name (optional)') }}</label>
                            <input type="text" id="anon-name" class="w-full text-xs border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1.5 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500" placeholder="{{ __('Anonymous') }}">
                        </div>
                        @endguest
                    </form>
                </div>

                {{-- Discussion --}}
                <div class="p-3 border-t border-gray-200 dark:border-gray-700 shrink-0">
                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Discussion') }}</span>
                    <div id="comments-list" class="mt-1 space-y-1 max-h-24 overflow-y-auto"></div>
                    @auth
                    <div class="mt-2 flex gap-1">
                        <input type="text" id="comment-input" class="flex-1 text-xs border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1.5 bg-white dark:bg-gray-800 focus:outline-none focus:ring-1 focus:ring-green-500" placeholder="{{ __('Add a comment...') }}">
                        <button id="comment-submit" class="text-xs bg-gray-100 dark:bg-gray-700 px-3 py-1.5 rounded-lg hover:bg-gray-200">{{ __('Send') }}</button>
                    </div>
                    @endauth
                </div>

                {{-- Action buttons --}}
                <div class="p-3 border-t border-gray-200 dark:border-gray-700 flex gap-2 shrink-0">
                    <button id="skip-btn" class="flex-1 text-sm border border-gray-200 dark:border-gray-700 text-gray-600 rounded-lg py-2 hover:bg-gray-50">{{ __('Skip') }}</button>
                    <button id="submit-btn" class="flex-1 text-sm bg-green-600 text-white rounded-lg py-2 hover:bg-green-700 font-medium disabled:opacity-50 disabled:cursor-not-allowed" disabled>{{ __('Submit') }}</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    const APP_URL   = document.querySelector('meta[name="app-url"]').getAttribute('content');
    const CSRF      = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const THRESHOLD = {{ \App\Models\PlatformSetting::get('validation_threshold', 60) }};
    const IS_AUTH   = {{ auth()->check() ? 'true' : 'false' }};
    const TXT = {
        agree:        "{{ __('Agree') }}",
        disagree:     "{{ __('Disagree') }}",
        loginToVal:   "{{ __('Login to validate') }}",
        previewMap:   "{{ __('Preview on map') }}",
        searching:    "{{ __('Searching...') }}",
        noResults:    "{{ __('No results found.') }}",
        searchFailed: "{{ __('Search failed.') }}",
        noOcc:        "{{ __('No occurrences found. Try a different country.') }}",
        occurrences:  "{{ __('occurrences') }}",
    };

    // ── Map ───────────────────────────────────────────────────────────────────
    let map, marker, circle, currentGroup = null;
    map = L.map('map', { zoomControl: false }).setView([39.5, -8.0], 6);
    const osm           = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap contributors', maxZoom: 19 });
    const esriSat       = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { attribution: 'Tiles © Esri', maxZoom: 19 });
    const esriLabels    = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19, pane: 'overlayPane' });
    const esriSatLabels = L.layerGroup([esriSat, esriLabels]);
    const esriStreet    = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', { attribution: 'Tiles © Esri', maxZoom: 19 });
    const esriTopo      = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}', { attribution: 'Tiles © Esri', maxZoom: 19 });
    osm.addTo(map);
    L.control.layers({ 'OpenStreetMap': osm, 'ESRI Satellite': esriSat, 'ESRI Satellite + Labels': esriSatLabels, 'ESRI Street Map': esriStreet, 'ESRI Topo': esriTopo }, {}, { position: 'bottomleft' }).addTo(map);
    L.control.zoom({ position: 'bottomleft' }).addTo(map);
    map.on('click', e => placeMarker(e.latlng.lat, e.latlng.lng));

    function placeMarker(lat, lng) {
        const unc = parseInt(document.getElementById('uncertainty-input').value) || 1000;
        if (marker) { map.removeLayer(marker); marker = null; }
        if (circle) { map.removeLayer(circle); circle = null; }
        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
        circle = L.circle([lat, lng], { radius: unc, color: '#16a34a', fillColor: '#16a34a', fillOpacity: 0.15, weight: 2 }).addTo(map);
        document.getElementById('lat-input').value = lat.toFixed(7);
        document.getElementById('lng-input').value = lng.toFixed(7);
        document.getElementById('uncertainty-display').textContent = unc.toLocaleString() + 'm';
        document.getElementById('uncertainty-slider').value = Math.min(unc, 500000);
        document.getElementById('submit-btn').disabled = false;
        marker.on('drag', e => {
            const p = e.target.getLatLng();
            circle.setLatLng(p);
            document.getElementById('lat-input').value = p.lat.toFixed(7);
            document.getElementById('lng-input').value = p.lng.toFixed(7);
        });
    }

    document.getElementById('uncertainty-input').addEventListener('input', function() {
        const v = parseInt(this.value) || 1000;
        document.getElementById('uncertainty-slider').value = Math.min(v, 500000);
        document.getElementById('uncertainty-display').textContent = v.toLocaleString() + 'm';
        if (circle) circle.setRadius(v);
    });
    document.getElementById('uncertainty-slider').addEventListener('input', function() {
        const v = parseInt(this.value);
        document.getElementById('uncertainty-input').value = v;
        document.getElementById('uncertainty-display').textContent = v.toLocaleString() + 'm';
        if (circle) circle.setRadius(v);
    });

    // ── Image viewer ──────────────────────────────────────────────────────────
    let imgScale = 1, imgX = 0, imgY = 0, isPanning = false, panStartX, panStartY;
    const imgViewer = document.getElementById('img-viewer');
    const imgEl     = document.getElementById('img-viewer-img');
    const panArea   = document.getElementById('img-pan-area');
    const zoomLabel = document.getElementById('img-zoom-label');

    function applyImgTransform() { imgEl.style.transform = 'translate('+imgX+'px,'+imgY+'px) scale('+imgScale+')'; zoomLabel.textContent = Math.round(imgScale*100)+'%'; }
    function resetImgZoom() { imgScale=1; imgX=0; imgY=0; applyImgTransform(); }
    function zoomImg(d) { imgScale=Math.max(0.2,Math.min(8,imgScale+d)); applyImgTransform(); }

    panArea.addEventListener('mousedown', e => { if(e.button!==0)return; isPanning=true; panStartX=e.clientX-imgX; panStartY=e.clientY-imgY; panArea.style.cursor='grabbing'; e.preventDefault(); });
    window.addEventListener('mousemove', e => { if(!isPanning)return; imgX=e.clientX-panStartX; imgY=e.clientY-panStartY; applyImgTransform(); });
    window.addEventListener('mouseup', () => { isPanning=false; panArea.style.cursor='grab'; });
    panArea.addEventListener('wheel', e => { e.preventDefault(); zoomImg(e.deltaY<0?0.15:-0.15); }, { passive: false });

    async function resolveImageUrl(url) {
        if (url && (url.includes('/manifest') || url.includes('manifest.json'))) {
            try {
                const m = await (await fetch(url, { headers: { 'Accept': 'application/json' } })).json();
                const imgRes = m.sequences?.[0]?.canvases?.[0]?.images?.[0]?.resource;
                if (imgRes) { const sid = imgRes.service?.['@id']||imgRes.service?.id; if(sid) return sid+'/full/max/0/default.jpg'; if(imgRes['@id']||imgRes.id) return imgRes['@id']||imgRes.id; }
                const item = m.items?.[0]?.items?.[0]?.items?.[0]?.body; if(item?.id) return item.id;
            } catch(e) {}
            return null;
        }
        return url;
    }
    async function openImgViewer(rawUrl, title, link) {
        document.getElementById('img-viewer-title').textContent = title||'';
        document.getElementById('img-viewer-link').href = link||rawUrl;
        imgEl.src=''; resetImgZoom(); imgViewer.style.display='flex';
        const resolved = await resolveImageUrl(rawUrl);
        if (resolved) { imgEl.src=resolved; } else { window.open(link||rawUrl,'_blank'); imgViewer.style.display='none'; }
    }
    function closeImgViewer() { imgViewer.style.display='none'; imgEl.src=''; }

    (function() {
        const bar=document.getElementById('img-viewer-bar'); let drag=false,dx,dy;
        bar.addEventListener('mousedown', e=>{ drag=true; const r=imgViewer.getBoundingClientRect(); dx=e.clientX-r.left; dy=e.clientY-r.top; e.preventDefault(); });
        window.addEventListener('mousemove', e=>{ if(!drag)return; imgViewer.style.left=(e.clientX-dx)+'px'; imgViewer.style.top=(e.clientY-dy)+'px'; });
        window.addEventListener('mouseup', ()=>{ drag=false; });
    })();
    (function() {
        const h=document.getElementById('img-resize-handle'); let res=false,sx,sy,sw,sh;
        h.addEventListener('mousedown', e=>{ res=true; sx=e.clientX; sy=e.clientY; sw=imgViewer.offsetWidth; sh=imgViewer.offsetHeight; e.preventDefault(); });
        window.addEventListener('mousemove', e=>{ if(!res)return; imgViewer.style.width=Math.max(200,sw+e.clientX-sx)+'px'; imgViewer.style.height=Math.max(150,sh+e.clientY-sy)+'px'; });
        window.addEventListener('mouseup', ()=>{ res=false; });
    })();

    // ── Tooltip ───────────────────────────────────────────────────────────────
    const tooltipEl = document.getElementById('occ-tooltip');
    const panelEl   = document.getElementById('side-panel');
    const occTooltipData = new Map();

    function showTooltip(el, data) {
        const lines = [
            data.recorded_by ? '<span style="opacity:.6">Collector:</span> '+data.recorded_by : null,
            data.event_date  ? '<span style="opacity:.6">Date:</span> '+data.event_date : null,
            data.institution ? '<span style="opacity:.6">Institution:</span> '+data.institution : null,
            data.collection  ? '<span style="opacity:.6">Collection:</span> '+data.collection : null,
            data.basis       ? '<span style="opacity:.6">Basis:</span> '+data.basis : null,
            data.key         ? '<span style="opacity:.6">GBIF key:</span> '+data.key : null,
        ].filter(Boolean);
        if (!lines.length) return;
        tooltipEl.innerHTML = lines.map(l=>'<div style="padding:1px 0">'+l+'</div>').join('');
        const rect=el.getBoundingClientRect(), pr=panelEl.getBoundingClientRect();
        tooltipEl.style.top=(rect.bottom-pr.top+4)+'px';
        tooltipEl.style.display='block';
    }
    function hideTooltip() { tooltipEl.style.display='none'; }

    // ── Nominatim ─────────────────────────────────────────────────────────────
    function buildLocalityString(g) {
        return [g.verbatim_locality,g.municipality,g.county,g.state_province,g.country_code].filter(Boolean).join(', ');
    }
    async function searchNominatim(query) {
        if (!query) return;
        document.getElementById('nominatim-results').innerHTML = '<p style="font-size:11px;color:#9ca3af;padding:4px">'+TXT.searching+'</p>';
        try {
            const results = await (await fetch('https://nominatim.openstreetmap.org/search?q='+encodeURIComponent(query)+'&format=json&polygon_geojson=1&limit=5', { headers: {'Accept-Language':'en'} })).json();
            if (!results.length) { document.getElementById('nominatim-results').innerHTML='<p style="font-size:11px;color:#9ca3af;padding:4px">'+TXT.noResults+'</p>'; return; }
            window._nominatimResults=results;
            document.getElementById('nominatim-results').innerHTML=results.map((r,i)=>
                '<button onclick="applyNominatimResult('+i+')" style="display:block;width:100%;text-align:left;font-size:11px;padding:5px;border-radius:4px;border:1px solid #e5e7eb;margin-bottom:2px;background:white;cursor:pointer" onmouseover="this.style.background=\'#f0fdf4\'" onmouseout="this.style.background=\'white\'">'+
                '<span style="font-weight:500;display:block;overflow:hidden;white-space:nowrap;text-overflow:ellipsis">'+r.display_name+'</span>'+
                '<span style="color:#9ca3af">'+r.type+' · '+parseFloat(r.lat).toFixed(4)+', '+parseFloat(r.lon).toFixed(4)+'</span></button>'
            ).join('');
        } catch(e) { document.getElementById('nominatim-results').innerHTML='<p style="font-size:11px;color:#ef4444;padding:4px">'+TXT.searchFailed+'</p>'; }
    }
    function applyNominatimResult(index) {
        const r=window._nominatimResults[index], lat=parseFloat(r.lat), lon=parseFloat(r.lon);
        if (r.geojson && (r.geojson.type==='Polygon'||r.geojson.type==='MultiPolygon')) {
            if (window._nominatimPolygon) map.removeLayer(window._nominatimPolygon);
            window._nominatimPolygon=L.geoJSON(r.geojson,{style:{color:'#16a34a',weight:2,fillOpacity:0.05}}).addTo(map);
            const bounds=window._nominatimPolygon.getBounds(), center=bounds.getCenter();
            const verts=[]; function cv(c){if(Array.isArray(c[0]))c.forEach(x=>cv(x));else verts.push(c);}
            if(r.geojson.type==='Polygon') r.geojson.coordinates.forEach(ring=>cv(ring));
            else r.geojson.coordinates.forEach(poly=>poly.forEach(ring=>cv(ring)));
            const R=6371000; let mx=0;
            verts.forEach(([vLon,vLat])=>{
                const a=Math.sin(((vLat-center.lat)*Math.PI/180)/2)**2+Math.cos(center.lat*Math.PI/180)*Math.cos(vLat*Math.PI/180)*Math.sin(((vLon-center.lng)*Math.PI/180)/2)**2;
                const d=R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a)); if(d>mx)mx=d;
            });
            const unc=Math.round(mx);
            document.getElementById('uncertainty-input').value=unc;
            document.getElementById('uncertainty-slider').max=Math.max(500000,Math.round(unc*1.5));
            document.getElementById('uncertainty-slider').value=unc;
            document.getElementById('uncertainty-display').textContent=unc.toLocaleString()+'m';
            placeMarker(center.lat,center.lng); map.fitBounds(bounds,{padding:[20,20]});
        } else { placeMarker(lat,lon); map.flyTo([lat,lon],12); }
        document.getElementById('nominatim-results').innerHTML='';
    }
    document.getElementById('nominatim-btn').addEventListener('click', ()=>searchNominatim(document.getElementById('nominatim-input').value.trim()));
    document.getElementById('nominatim-input').addEventListener('keydown', e=>{ if(e.key==='Enter') searchNominatim(e.target.value.trim()); });

    // ── Load next group ───────────────────────────────────────────────────────
    function clearSuggestionLayers() {
        if (window._suggestionLayers) window._suggestionLayers.forEach(l=>map.removeLayer(l));
        window._suggestionLayers=[];
    }
    function loadNextGroup() {
        if(marker){map.removeLayer(marker);marker=null;} if(circle){map.removeLayer(circle);circle=null;}
        if(window._nominatimPolygon){map.removeLayer(window._nominatimPolygon);window._nominatimPolygon=null;}
        clearSuggestionLayers(); closeImgViewer(); hideTooltip();
        document.getElementById('submit-btn').disabled=true;
        document.getElementById('lat-input').value=''; document.getElementById('lng-input').value='';
        document.getElementById('uncertainty-display').textContent=''; document.getElementById('remarks-input').value='';
        const country=document.getElementById('country-select').value;
        document.getElementById('occurrence-loading').classList.remove('hidden');
        document.getElementById('occurrence-info').classList.add('hidden');
        fetch(APP_URL+'/georef/next?country='+country, {headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}})
        .then(r=>r.json())
        .then(data=>{
            document.getElementById('occurrence-loading').classList.add('hidden');
            if(data.group){currentGroup=data.group; renderGroup(data.group,data.occurrences,data.suggestions,data.comments);}
            else{document.getElementById('occurrence-info').classList.remove('hidden'); document.getElementById('locality-fields').innerHTML='<p style="color:#9ca3af;font-size:11px">'+TXT.noOcc+'</p>';}
        })
        .catch(()=>document.getElementById('occurrence-loading').classList.add('hidden'));
    }
    loadNextGroup();

    // ── Render group ──────────────────────────────────────────────────────────
    function renderGroup(group, occurrences, suggestions, comments) {
        document.getElementById('occurrence-info').classList.remove('hidden');
        occTooltipData.clear();
        const fields=['verbatim_locality','country','state_province','county','municipality','island','water_body'].filter(f=>group[f]);
        document.getElementById('locality-fields').innerHTML=fields.map(f=>
            '<div style="display:flex;gap:8px"><span style="color:#9ca3af;width:112px;flex-shrink:0;font-size:11px">'+f.replace(/_/g,' ')+'</span>'+
            '<span style="font-weight:500;font-size:11px">'+group[f]+'</span></div>'
        ).join('');
        document.getElementById('nominatim-input').value=buildLocalityString(group);
        document.getElementById('nominatim-results').innerHTML='';

        document.getElementById('occurrence-count').textContent=occurrences.length+' '+TXT.occurrences;
        document.getElementById('occurrences-list').innerHTML=occurrences.map(function(o){
            const label=[o.recorded_by,o.event_date].filter(Boolean).join(' · ')||o.gbif_occurrence_key;
            const taxon=o.scientific_name||'', meta=[o.institution_code,o.collection_code].filter(Boolean).join(' · ');
            const occId='occ-'+o.id, media=(o.media&&o.media.length>0)?o.media[0]:null;
            occTooltipData.set(occId,{recorded_by:o.recorded_by||'',event_date:o.event_date||'',institution:o.institution_code||'',collection:o.collection_code||'',basis:o.basis_of_record||'',key:o.gbif_occurrence_key||''});
            var imgBtn='';
            if(media) imgBtn='<button class="img-btn" style="flex-shrink:0;width:28px;height:28px;border-radius:4px;overflow:hidden;border:1px solid #e5e7eb;cursor:pointer" data-src="'+media.identifier+'" data-title="'+(media.title||'').replace(/"/g,'&quot;')+'" data-link="'+media.identifier+'"><img src="'+media.identifier+'" style="width:28px;height:28px;object-fit:cover" loading="lazy" onerror="this.parentElement.style.display=\'none\'"></button>';
            return '<div class="occ-row" id="'+occId+'" style="font-size:11px;border-radius:4px;border:1px solid transparent;padding:2px 0">'+
                '<div style="display:flex;align-items:flex-start;gap:6px;padding:4px 6px">'+
                '<input type="checkbox" class="occurrence-checkbox" value="'+o.id+'" checked style="flex-shrink:0;margin-top:2px">'+
                '<div style="flex:1;min-width:0"><div style="word-break:break-word">'+label+'</div>'+
                (taxon?'<div style="color:#9ca3af;font-style:italic;word-break:break-word;line-height:1.2">'+taxon+'</div>':'')+
                (meta?'<div style="color:#9ca3af">'+meta+'</div>':'')+
                '</div><a href="https://www.gbif.org/occurrence/'+o.gbif_occurrence_key+'" target="_blank" style="color:#16a34a;flex-shrink:0;text-decoration:none">↗</a>'+
                imgBtn+'</div></div>';
        }).join('');

        document.querySelectorAll('.occ-row').forEach(function(row){
            row.addEventListener('mouseenter',function(){var d=occTooltipData.get(this.id);if(d)showTooltip(this,d);});
            row.addEventListener('mouseleave',hideTooltip);
        });
        document.querySelectorAll('.img-btn').forEach(function(btn){
            btn.addEventListener('click',function(e){e.stopPropagation();openImgViewer(this.dataset.src,this.dataset.title,this.dataset.link);});
        });

        clearSuggestionLayers();
        if (suggestions&&suggestions.length>0) {
            document.getElementById('existing-suggestions').classList.remove('hidden');
            const colors=['#3b82f6','#f59e0b','#ef4444','#8b5cf6','#06b6d4'];
            var sugHtml='';
            suggestions.forEach(function(s,i){
                var color=colors[i%colors.length];
                var c=L.circle([s.decimal_latitude,s.decimal_longitude],{radius:s.coordinate_uncertainty_m||1000,color:color,fillColor:color,fillOpacity:0.1,weight:2,dashArray:'6'}).addTo(map);
                var m=L.circleMarker([s.decimal_latitude,s.decimal_longitude],{radius:6,color:color,fillColor:color,fillOpacity:0.8,weight:2}).bindTooltip(s.submitted_by+' · ±'+s.coordinate_uncertainty_m+'m · '+s.total_points+'pts',{permanent:false}).addTo(map);
                window._suggestionLayers.push(c,m);
                var pct=Math.min(100,(s.total_points/THRESHOLD)*100);
                var dot='<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:'+color+';flex-shrink:0;margin-top:2px"></span>';
                var valButtons=IS_AUTH
                    ?'<button onclick="validateSuggestion('+s.id+',\'agree\')" style="color:#16a34a;background:none;border:none;cursor:pointer;font-size:11px">'+TXT.agree+'</button>'+
                      '<button onclick="validateSuggestion('+s.id+',\'disagree\')" style="color:#ef4444;background:none;border:none;cursor:pointer;font-size:11px">'+TXT.disagree+'</button>'
                    :'<span style="color:#9ca3af;font-style:italic;font-size:10px">'+TXT.loginToVal+'</span>';
                sugHtml+='<div style="font-size:11px;border:1px solid #e5e7eb;border-radius:6px;padding:8px;margin-bottom:4px">'+
                    '<div style="display:flex;align-items:flex-start;gap:4px">'+dot+
                    '<div style="flex:1">'+
                    '<div style="display:flex;justify-content:space-between"><span style="font-weight:500">'+parseFloat(s.decimal_latitude).toFixed(5)+', '+parseFloat(s.decimal_longitude).toFixed(5)+'</span><span style="color:#9ca3af">±'+s.coordinate_uncertainty_m+'m</span></div>'+
                    '<div style="display:flex;justify-content:space-between;margin-top:4px;color:#9ca3af"><span>'+s.submitted_by+'</span><div style="display:flex;gap:8px">'+valButtons+'</div></div>'+
                    '<div style="background:#f3f4f6;border-radius:4px;height:4px;margin-top:6px"><div style="background:'+color+';height:4px;border-radius:4px;width:'+pct+'%"></div></div>'+
                    '<button onclick="previewSuggestion('+s.decimal_latitude+','+s.decimal_longitude+','+s.coordinate_uncertainty_m+')" style="color:#3b82f6;background:none;border:none;cursor:pointer;font-size:10px;margin-top:4px;padding:0">'+TXT.previewMap+'</button>'+
                    '</div></div></div>';
            });
            document.getElementById('suggestions-list').innerHTML=sugHtml;
        } else { document.getElementById('existing-suggestions').classList.add('hidden'); }

        renderComments(comments||[]);

        const cf={'PT':[39.5,-8.0,7],'ES':[40.0,-3.7,6],'GB':[54.0,-2.0,6],'FR':[46.5,2.3,6],'DE':[51.2,10.4,6],'IT':[42.5,12.5,6],'BR':[-14.2,-51.9,4],'US':[37.1,-95.7,4]};
        if (window._suggestionLayers&&window._suggestionLayers.length>0) {
            map.fitBounds(L.featureGroup(window._suggestionLayers).getBounds().pad(0.5));
        } else if (group.gbif_decimal_latitude&&group.gbif_decimal_longitude) {
            map.flyTo([group.gbif_decimal_latitude,group.gbif_decimal_longitude],10);
        } else { const f=cf[group.country_code]; if(f) map.flyTo([f[0],f[1]],f[2]); }
    }

    function renderComments(comments) {
        document.getElementById('comments-list').innerHTML=comments.map(function(c){
            return '<div style="font-size:11px;border-bottom:1px solid #f3f4f6;padding-bottom:4px"><span style="font-weight:500">'+c.user_name+'</span><span style="color:#9ca3af;margin-left:4px">'+c.created_at+'</span><p style="color:#6b7280;margin-top:2px">'+c.body+'</p></div>';
        }).join('');
    }
    function previewSuggestion(lat,lng,unc) {
        if(marker){map.removeLayer(marker);marker=null;} if(circle){map.removeLayer(circle);circle=null;}
        circle=L.circle([lat,lng],{radius:unc||1000,color:'#3b82f6',fillColor:'#3b82f6',fillOpacity:0.1,weight:2,dashArray:'6'}).addTo(map);
        map.flyTo([lat,lng],12);
    }
    function validateSuggestion(id,vote) {
        fetch(APP_URL+'/georef/validate/'+id,{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({vote:vote})})
        .then(r=>r.json()).then(d=>{if(d.success)loadNextGroup();});
    }
    document.getElementById('submit-btn').addEventListener('click',function(){
        if(!currentGroup)return;
        var excl=Array.from(document.querySelectorAll('.occurrence-checkbox:not(:checked)')).map(function(c){return c.value;});
        fetch(APP_URL+'/georef/submit',{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Content-Type':'application/json','Accept':'application/json'},
            body:JSON.stringify({locality_group_id:currentGroup.id,decimal_latitude:document.getElementById('lat-input').value,decimal_longitude:document.getElementById('lng-input').value,coordinate_uncertainty_m:document.getElementById('uncertainty-input').value,georeference_remarks:document.getElementById('remarks-input').value,anon_name:document.getElementById('anon-name')?document.getElementById('anon-name').value:null,excluded_occurrence_ids:excl})})
        .then(r=>r.json()).then(d=>{if(d.success)loadNextGroup();});
    });
    document.getElementById('skip-btn').addEventListener('click',loadNextGroup);
    document.getElementById('country-select').addEventListener('change',loadNextGroup);
    var commentSubmit=document.getElementById('comment-submit');
    if(commentSubmit){
        commentSubmit.addEventListener('click',function(){
            var body=document.getElementById('comment-input').value.trim();
            if(!body||!currentGroup)return;
            fetch(APP_URL+'/georef/comment',{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({locality_group_id:currentGroup.id,body:body})})
            .then(r=>r.json()).then(d=>{if(d.success){document.getElementById('comment-input').value='';renderComments(d.comments);}});
        });
    }

    // ── Layout ────────────────────────────────────────────────────────────────
    function applyLayout() {
        var wrap=document.getElementById('georef-wrap'), panel=document.getElementById('side-panel'), mapDiv=document.getElementById('map');
        var innerPanel=panel.querySelector('.flex.flex-col.flex-1');
        if (window.innerWidth<768) {
            wrap.style.flexDirection='column'; panel.style.width='100%'; panel.style.height='55%';
            mapDiv.style.height='45%'; mapDiv.style.flex='none';
            if(innerPanel)innerPanel.style.overflowY='auto';
        } else {
            wrap.style.flexDirection='row'; panel.style.width='380px'; panel.style.height='100%';
            mapDiv.style.height=''; mapDiv.style.flex='1';
            if(innerPanel)innerPanel.style.overflowY='hidden';
        }
        map.invalidateSize();
    }
    applyLayout();
    window.addEventListener('resize',applyLayout);
    </script>
    @endpush
</x-layouts.georef>