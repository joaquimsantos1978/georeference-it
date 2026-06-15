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
      'validated':         { pill: 'Validated ✓',          color: '#166534', bg: '#f0fdf4', border: '#86efac' },
      'gbif_georeferenced':{ pill: 'GBIF georef',          color: '#1d4ed8', bg: '#eff6ff', border: '#93c5fd' },
      'gbif_reviewed':     { pill: 'Reviewed ✓',           color: '#166534', bg: '#f0fdf4', border: '#86efac' },
    }[status] || { pill: status, color: '#6b7280', bg: '#f9fafb', border: '#e5e7eb' };
  }

  // Convert lat/lng to pixel offset within a 256x256 tile at given zoom
  function latLngToTilePixel(lat, lng, zoom, tileX, tileY) {
    const n = Math.pow(2, zoom);
    const xFrac = (lng + 180) / 360 * n - tileX;
    const latRad = lat * Math.PI / 180;
    const yFrac = (1 - Math.log(Math.tan(latRad) + 1 / Math.cos(latRad)) / Math.PI) / 2 * n - tileY;
    return { px: Math.round(xFrac * 256), py: Math.round(yFrac * 256) };
  }

  function buildMapSection(dataUrl, lat, lng, tileX, tileY, zoom, osmUrl) {
    const { px, py } = latLngToTilePixel(parseFloat(lat), parseFloat(lng), zoom, tileX, tileY);
    // Crop a 260x130 window centred on the marker
    const offX = Math.max(0, Math.min(px - 130, 256 - 260));
    const offY = Math.max(0, Math.min(py - 65, 256 - 130));
    const markerX = px - offX;
    const markerY = py - offY;

    return `
      <a href="${osmUrl}" target="_blank" style="display:block;overflow:hidden;height:130px;position:relative;border-bottom:1px solid #e5e7eb;cursor:pointer">
        <img src="${dataUrl}" width="256" height="256"
          style="position:absolute;left:${-offX}px;top:${-offY}px;display:block;image-rendering:auto" />
        <svg width="24" height="32" viewBox="0 0 24 32"
          style="position:absolute;left:${markerX - 12}px;top:${markerY - 30}px;pointer-events:none"
          xmlns="http://www.w3.org/2000/svg">
          <path d="M12 0C5.4 0 0 5.4 0 12c0 9 12 20 12 20S24 21 24 12C24 5.4 18.6 0 12 0z" fill="#dc2626"/>
          <circle cx="12" cy="12" r="5" fill="#fff"/>
        </svg>
      </a>`;
  }

  function buildBadge(data, mapHtml) {
    const s = statusConfig(data.georef_status);
    const hasCoords = data.decimalLatitude != null && data.georeferenceSources !== 'GBIF';
    const isUngeoreferenced = data.georef_status === 'ungeoreferenced';
    const actionLabel = isUngeoreferenced ? 'Georeference on georeference.it →' : 'View / correct on georeference.it →';

    const divergeWarn = data.diverges_from_gbif
      ? `<div style="margin:6px 10px 0;font-size:10px;color:#c2410c;background:#fff7ed;border:1px solid #fed7aa;border-radius:4px;padding:4px 6px">⚠ Differs from GBIF coordinates</div>`
      : '';

    const coordsSection = hasCoords
      ? `<div style="margin:8px 10px 0;font-size:10px;color:#6b7280">
           Suggested coordinates
           <div style="font-family:monospace;font-size:10.5px;color:#111;margin-top:2px">
             ${parseFloat(data.decimalLatitude).toFixed(5)}, ${parseFloat(data.decimalLongitude).toFixed(5)}
             ${data.coordinateUncertaintyInMeters ? `<span style="color:#6b7280"> ±${data.coordinateUncertaintyInMeters}m</span>` : ''}
           </div>
         </div>`
      : '';

    const linkSection = data.georef_url
      ? `<a href="${data.georef_url}" target="_blank" style="
            display:block;margin:10px;text-align:center;
            padding:7px 0;background:#166534;color:#fff;
            border-radius:7px;font-size:12px;font-weight:600;text-decoration:none;
          ">${actionLabel}</a>`
      : '';

    return `
      <div id="georef-badge" style="
        position:fixed;bottom:24px;right:24px;z-index:2147483647;
        width:260px;
        font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;
        border-radius:10px;overflow:hidden;
        box-shadow:0 4px 20px rgba(0,0,0,0.22);
        border:1px solid #d1fae5;
        background:#fff;
      ">
        <div style="background:#166534;padding:8px 10px;display:flex;align-items:center;justify-content:space-between">
          <span style="color:#fff;font-weight:700;font-size:12px;letter-spacing:.3px">georeference.it</span>
          <button onclick="document.getElementById('georef-badge').style.display='none'"
            style="background:none;border:none;color:#fff;cursor:pointer;font-size:15px;line-height:1;padding:0;opacity:.7">✕</button>
        </div>
        ${mapHtml || ''}
        <div style="padding:8px 10px 0">
          <span style="
            display:inline-block;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:600;
            color:${s.color};background:${s.bg};border:1px solid ${s.border};
          ">${s.pill}</span>
        </div>
        ${coordsSection}
        ${divergeWarn}
        ${linkSection}
      </div>`;
  }

  let _running = false;
  let _lastKey = null;

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

    let mapHtml = '';
    if (data.decimalLatitude != null && data.georeferenceSources !== 'GBIF') {
      const lat = parseFloat(data.decimalLatitude);
      const lng = parseFloat(data.decimalLongitude);
      const zoom = 12;
      const tileX = Math.floor((lng + 180) / 360 * Math.pow(2, zoom));
      const latRad = lat * Math.PI / 180;
      const tileY = Math.floor((1 - Math.log(Math.tan(latRad) + 1 / Math.cos(latRad)) / Math.PI) / 2 * Math.pow(2, zoom));
      const osmUrl = `https://www.openstreetmap.org/?mlat=${lat}&mlon=${lng}#map=13/${lat}/${lng}`;

      const tileResp = await new Promise(resolve =>
        chrome.runtime.sendMessage({ type: 'fetch_map_tile', lat, lng }, resolve)
      );
      if (tileResp?.dataUrl) {
        mapHtml = buildMapSection(tileResp.dataUrl, lat, lng, tileX, tileY, zoom, osmUrl);
      }
    }

    const div = document.createElement('div');
    div.innerHTML = buildBadge(data, mapHtml);
    document.body.appendChild(div.firstElementChild);

    _running = false;
  }

  run();

  let _prevPath = location.pathname;
  setInterval(() => {
    if (location.pathname !== _prevPath) {
      _prevPath = location.pathname;
      _lastKey = null;
      _running = false;
      run();
    }
  }, 500);
})();
