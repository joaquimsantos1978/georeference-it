(() => {
  function getGbifKey() {
    const m = location.pathname.match(/\/occurrence\/(\d+)/);
    return m ? m[1] : null;
  }

  function statusConfig(status) {
    return {
      'ungeoreferenced':   { pill: 'Needs georeferencing', color: '#dc2626', bg: '#fef2f2', border: '#fca5a5' },
      'has_suggestion':    { pill: 'Suggestion pending',   color: '#d97706', bg: '#fffbeb', border: '#fcd34d' },
      'conflicted':        { pill: 'Conflicted',           color: '#7c3aed', bg: '#f5f3ff', border: '#c4b5fd' },
      'validated':         { pill: 'Validated ✓',          color: '#16a34a', bg: '#f0fdf4', border: '#86efac' },
      'gbif_georeferenced':{ pill: 'GBIF georef',          color: '#1d4ed8', bg: '#eff6ff', border: '#93c5fd' },
    }[status] || { pill: status, color: '#6b7280', bg: '#f9fafb', border: '#e5e7eb' };
  }

  function injectStyles() {
    if (document.getElementById('georef-styles')) return;
    const style = document.createElement('style');
    style.id = 'georef-styles';
    style.textContent = `
      #georef-badge .leaflet-control-zoom a {
        width: 20px !important; height: 20px !important;
        line-height: 20px !important; font-size: 13px !important;
      }
      #georef-badge .leaflet-control-zoom {
        margin: 6px !important;
      }
      #georef-resize-handle {
        position: absolute; bottom: 0; right: 0;
        width: 14px; height: 14px; cursor: nwse-resize;
        background: linear-gradient(135deg, transparent 50%, #9ca3af 50%, #9ca3af 60%, transparent 60%, transparent 70%, #9ca3af 70%, #9ca3af 80%, transparent 80%);
      }
    `;
    document.head.appendChild(style);
  }

  function buildBadge(data) {
    const s = statusConfig(data.georef_status);
    const hasCoords = data.decimalLatitude != null && data.georeferenceSources !== 'GBIF';
    const isUngeoreferenced = data.georef_status === 'ungeoreferenced';
    const actionLabel = isUngeoreferenced ? 'Georeference on georeference.it →' : 'View / correct on georeference.it →';

    const divergeWarn = data.diverges_from_gbif
      ? `<div style="margin:6px 10px 0;font-size:10px;color:#c2410c;background:#fff7ed;border:1px solid #fed7aa;border-radius:4px;padding:4px 6px">⚠ Differs from GBIF coordinates</div>`
      : '';

    const mapSection = hasCoords
      ? `<div id="georef-map" style="flex:1;min-height:120px;border-bottom:1px solid #e5e7eb"></div>`
      : '';

    const coordsSection = hasCoords
      ? `<div style="margin:8px 10px 0;font-size:10px;color:#6b7280;flex-shrink:0">
           Suggested coordinates
           <div style="font-family:monospace;font-size:10.5px;color:#111;margin-top:2px">
             ${parseFloat(data.decimalLatitude).toFixed(5)}, ${parseFloat(data.decimalLongitude).toFixed(5)}
             ${data.coordinateUncertaintyInMeters ? `<span style="color:#6b7280"> ±${data.coordinateUncertaintyInMeters}m</span>` : ''}
           </div>
         </div>`
      : '';

    const linkSection = data.georef_url
      ? `<a href="${data.georef_url}" target="_blank" style="
            display:block;margin:10px;text-align:center;flex-shrink:0;
            padding:7px 0;background:#16a34a;color:#fff;
            border-radius:7px;font-size:12px;font-weight:600;text-decoration:none;
          ">${actionLabel}</a>`
      : '';

    return `
      <div id="georef-badge" style="
        position:fixed;bottom:24px;right:24px;z-index:2147483647;
        width:260px;min-width:200px;min-height:80px;
        display:flex;flex-direction:column;
        font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
        border-radius:10px;overflow:hidden;
        box-shadow:0 4px 20px rgba(0,0,0,0.22);
        border:1px solid #d1fae5;
        background:#fff;
      ">
        <div id="georef-badge-header" style="background:#16a34a;padding:8px 10px;display:flex;align-items:center;justify-content:space-between;cursor:grab;user-select:none;flex-shrink:0">
          <span style="color:#fff;font-weight:700;font-size:12px;letter-spacing:.3px">⠿ georeference.it</span>
          <button id="georef-badge-close"
            style="background:none;border:none;color:#fff;cursor:pointer;font-size:15px;line-height:1;padding:0;opacity:.7">✕</button>
        </div>
        ${mapSection}
        <div style="padding:8px 10px 0;flex-shrink:0">
          <span style="
            display:inline-block;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:600;
            color:${s.color};background:${s.bg};border:1px solid ${s.border};
          ">${s.pill}</span>
        </div>
        ${coordsSection}
        ${divergeWarn}
        ${linkSection}
        <div id="georef-resize-handle"></div>
      </div>`;
  }

  function makeDraggable(badge) {
    const header = badge.querySelector('#georef-badge-header');

    header.addEventListener('mousedown', e => {
      if (e.target.id === 'georef-badge-close') return;
      e.preventDefault();
      header.style.cursor = 'grabbing';

      const rect = badge.getBoundingClientRect();
      badge.style.left   = rect.left + 'px';
      badge.style.top    = rect.top + 'px';
      badge.style.right  = 'auto';
      badge.style.bottom = 'auto';

      const startX = e.clientX - rect.left;
      const startY = e.clientY - rect.top;

      function onMove(e) {
        const left = e.clientX - startX;
        const top  = e.clientY - startY;
        badge.style.left = left + 'px';
        badge.style.top  = top  + 'px';
        savePos(left, top);
      }
      function onUp() {
        header.style.cursor = 'grab';
        document.removeEventListener('mousemove', onMove);
        document.removeEventListener('mouseup', onUp);
      }
      document.addEventListener('mousemove', onMove);
      document.addEventListener('mouseup', onUp);
    });

    badge.querySelector('#georef-badge-close').addEventListener('click', () => {
      badge.style.display = 'none';
    });
  }

  function makeResizable(badge, getMap) {
    const handle = badge.querySelector('#georef-resize-handle');

    handle.addEventListener('mousedown', e => {
      e.preventDefault();
      e.stopPropagation();

      const rect = badge.getBoundingClientRect();
      const startX  = e.clientX;
      const startY  = e.clientY;
      const startW  = rect.width;
      const startH  = rect.height;

      function onMove(e) {
        const newW = Math.max(200, startW + (e.clientX - startX));
        const newH = Math.max(80,  startH + (e.clientY - startY));
        badge.style.width  = newW + 'px';
        badge.style.height = newH + 'px';
        saveSize(newW, newH);
        const map = getMap();
        if (map) map.invalidateSize();
      }
      function onUp() {
        document.removeEventListener('mousemove', onMove);
        document.removeEventListener('mouseup', onUp);
      }
      document.addEventListener('mousemove', onMove);
      document.addEventListener('mouseup', onUp);
    });
  }

  function initMap(lat, lng, uncertaintyM) {
    const container = document.getElementById('georef-map');
    if (!container || typeof L === 'undefined') return null;

    const zoom = uncertaintyM
      ? Math.max(3, Math.min(14, Math.round(14 - Math.log2(uncertaintyM / 100))))
      : 13;

    const map = L.map(container, { zoomControl: true, attributionControl: false })
      .setView([lat, lng], zoom);

    L.tileLayer('https://georeference.it/api/v1/tiles/{z}/{x}/{y}', {
      maxZoom: 18,
    }).addTo(map);

    if (uncertaintyM) {
      L.circle([lat, lng], {
        radius: uncertaintyM,
        color: '#16a34a',
        fillColor: '#16a34a',
        fillOpacity: 0.15,
        weight: 2,
      }).addTo(map);
    }

    L.circleMarker([lat, lng], {
      radius: 6,
      color: '#16a34a',
      fillColor: '#16a34a',
      fillOpacity: 0.9,
      weight: 2,
    }).addTo(map);

    return map;
  }

  let _running = false;
  let _lastKey = null;

  function savePos(left, top)      { chrome.storage.local.set({ georef_pos:  { left, top } }); }
  function saveSize(width, height) { chrome.storage.local.set({ georef_size: { width, height } }); }
  function loadPrefs()             { return new Promise(r => chrome.storage.local.get(['georef_pos', 'georef_size'], r)); }

  async function run() {
    const key = getGbifKey();
    if (!key || _running || key === _lastKey) return;
    _running = true;
    _lastKey = key;

    document.getElementById('georef-badge')?.remove();

    await new Promise(r => setTimeout(r, 2000));

    const response = await new Promise(resolve =>
      chrome.runtime.sendMessage({ type: 'fetch_occurrence', key }, resolve)
    );
    const data = response?.data;
    if (!data) { _running = false; return; }
    if (!data.localityGroupID) { _running = false; return; }

    injectStyles();

    const div = document.createElement('div');
    div.innerHTML = buildBadge(data);
    const badge = div.firstElementChild;
    const { georef_pos: pos, georef_size: size } = await loadPrefs();
    if (pos) {
      badge.style.left   = pos.left + 'px';
      badge.style.top    = pos.top  + 'px';
      badge.style.right  = 'auto';
      badge.style.bottom = 'auto';
    }
    if (size) {
      badge.style.width  = size.width  + 'px';
      badge.style.height = size.height + 'px';
    }
    document.body.appendChild(badge);

    makeDraggable(badge);

    const hasCoords = data.decimalLatitude != null && data.georeferenceSources !== 'GBIF';
    let leafletMap = null;
    if (hasCoords) {
      leafletMap = initMap(
        parseFloat(data.decimalLatitude),
        parseFloat(data.decimalLongitude),
        data.coordinateUncertaintyInMeters ? parseFloat(data.coordinateUncertaintyInMeters) : null
      );
    }

    makeResizable(badge, () => leafletMap);

    _running = false;
  }

  let _prevPath = location.pathname;
  setInterval(() => {
    const key = getGbifKey();
    if (!key) {
      document.getElementById('georef-badge')?.remove();
      _lastKey = null;
      _running = false;
      return;
    }
    if (location.pathname !== _prevPath) {
      _prevPath = location.pathname;
      _lastKey = null;
      _running = false;
      run();
    }
  }, 500);

  run();
})();
