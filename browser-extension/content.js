(() => {
  const API = 'https://georeference.it/api/occurrences/';
  const GEOREF_BASE = 'https://georeference.it';

  function getGbifKey() {
    const m = location.pathname.match(/\/occurrence\/(\d+)/);
    return m ? m[1] : null;
  }

  function statusLabel(status) {
    return {
      'ungeoreferenced':  { text: 'Needs georeferencing', color: '#dc2626', bg: '#fef2f2' },
      'has_suggestion':   { text: 'Has suggestion (pending validation)', color: '#d97706', bg: '#fffbeb' },
      'conflicted':       { text: 'Conflicted suggestions', color: '#7c3aed', bg: '#f5f3ff' },
      'validated':        { text: 'Validated', color: '#16a34a', bg: '#f0fdf4' },
      'gbif_georeferenced':{ text: 'Georeferenced by GBIF', color: '#2563eb', bg: '#eff6ff' },
      'gbif_reviewed':    { text: 'GBIF georeference reviewed', color: '#16a34a', bg: '#f0fdf4' },
    }[status] || { text: status, color: '#6b7280', bg: '#f9fafb' };
  }

  function coordStr(lat, lng, unc) {
    if (lat == null) return '—';
    let s = `${parseFloat(lat).toFixed(5)}, ${parseFloat(lng).toFixed(5)}`;
    if (unc) s += ` ±${unc}m`;
    return s;
  }

  function buildPanel(data) {
    const status = statusLabel(data.georef_status);
    const hasGeorefCoords = data.decimalLatitude != null && data.georeferenceSources !== 'GBIF';
    const hasGbifCoords   = data.decimalLatitude != null && data.georeferenceSources === 'GBIF'
                         || data.georef_status === 'gbif_georeferenced';

    let coordsHtml = '';
    if (hasGeorefCoords) {
      coordsHtml = `
        <div style="margin-top:8px;font-size:12px">
          <div style="color:#6b7280;margin-bottom:2px">Suggested coordinates</div>
          <div style="font-family:monospace;font-size:11px">${coordStr(data.decimalLatitude, data.decimalLongitude, data.coordinateUncertaintyInMeters)}</div>
          ${data.georeferencedBy ? `<div style="color:#9ca3af;font-size:10px;margin-top:2px">by ${data.georeferencedBy}</div>` : ''}
        </div>`;
      if (data.diverges_from_gbif) {
        coordsHtml += `
          <div style="margin-top:6px;padding:6px 8px;background:#fff7ed;border:1px solid #fed7aa;border-radius:4px;font-size:11px;color:#c2410c">
            ⚠ Differs from GBIF coordinates
          </div>`;
      }
    }

    const actionLabel = data.georef_status === 'ungeoreferenced' ? 'Georeference this specimen' : 'View / suggest correction';

    return `
      <div id="georef-panel" style="
        margin: 16px 0;
        border: 1px solid #d1fae5;
        border-radius: 8px;
        background: #fff;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
      ">
        <div style="background:#166534;padding:8px 12px;display:flex;align-items:center;gap:8px">
          <span style="color:#fff;font-weight:600;font-size:13px">georeference.it</span>
        </div>
        <div style="padding:12px">
          <span style="
            display:inline-block;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:500;
            color:${status.color};background:${status.bg};border:1px solid ${status.color}40;
          ">${status.text}</span>
          ${coordsHtml}
          ${data.georef_url ? `
          <div style="margin-top:10px">
            <a href="${data.georef_url}" target="_blank" style="
              display:inline-block;padding:5px 12px;background:#166534;color:#fff;
              border-radius:6px;font-size:12px;font-weight:500;text-decoration:none;
            ">${actionLabel} →</a>
          </div>` : ''}
        </div>
      </div>`;
  }

  function inject(html) {
    // Try to insert after the map/coordinates section on the GBIF page
    const targets = [
      '.map-container',
      '[data-testid="occurrence-section-location"]',
      '.occurrence-detail-header',
      'article',
    ];
    for (const sel of targets) {
      const el = document.querySelector(sel);
      if (el) {
        const div = document.createElement('div');
        div.innerHTML = html;
        el.parentNode.insertBefore(div.firstElementChild, el.nextSibling);
        return true;
      }
    }
    return false;
  }

  async function run() {
    const key = getGbifKey();
    if (!key) return;

    // Wait for GBIF page to finish rendering
    await new Promise(r => setTimeout(r, 1500));

    if (document.getElementById('georef-panel')) return;

    let data;
    try {
      const res = await fetch(API + key, { headers: { Accept: 'application/json' } });
      if (!res.ok) return;
      data = await res.json();
    } catch (e) {
      return;
    }

    const html = buildPanel(data);
    if (!inject(html)) {
      // Fallback: prepend to body
      const div = document.createElement('div');
      div.style.cssText = 'position:fixed;bottom:16px;right:16px;z-index:9999;max-width:320px';
      div.innerHTML = html;
      document.body.appendChild(div);
    }
  }

  // Run on load and on SPA navigation (GBIF is a React SPA)
  run();
  const observer = new MutationObserver(() => {
    if (!document.getElementById('georef-panel') && getGbifKey()) run();
  });
  observer.observe(document.body, { childList: true, subtree: true });
})();
