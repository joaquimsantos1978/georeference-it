(() => {
  const BASE_URL = 'https://georeference.it';

  function getGbifKey() {
    const m = location.pathname.match(/\/occurrence\/(\d+)/);
    return m ? m[1] : null;
  }

  function savePos(right, top) { chrome.storage.local.set({ georef_pos: { right, top } }); }
  function saveSize(w, h)      { chrome.storage.local.set({ georef_size: { width: w, height: h } }); }
  function loadPrefs()         { return new Promise(r => chrome.storage.local.get(['georef_pos', 'georef_size'], r)); }

  function injectStyles() {
    if (document.getElementById('georef-ext-styles')) return;
    const s = document.createElement('style');
    s.id = 'georef-ext-styles';
    s.textContent = `
      #georef-container {
        position: fixed; z-index: 2147483647;
        bottom: 24px; right: 24px;
        width: 300px; height: 420px;
        min-width: 220px; min-height: 120px;
        border-radius: 10px; overflow: hidden;
        box-shadow: 0 4px 24px rgba(0,0,0,0.18);
        border: 1px solid #d1fae5;
        background: #fff;
        display: flex; flex-direction: column;
      }
      #georef-header {
        background: #4C9C2E; padding: 7px 10px;
        display: flex; align-items: center; justify-content: space-between;
        cursor: grab; user-select: none; flex-shrink: 0;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      }
      #georef-header span { color: #fff; font-weight: 700; font-size: 12px; letter-spacing: .3px; }
      #georef-close { background: none; border: none; color: #fff; cursor: pointer; font-size: 15px; line-height: 1; padding: 0; opacity: .7; }
      #georef-frame { flex: 1; border: none; width: 100%; display: block; }
      #georef-resize {
        position: absolute; bottom: 0; right: 0; width: 14px; height: 14px; cursor: nwse-resize;
        background: linear-gradient(135deg, transparent 50%, #9ca3af 50%, #9ca3af 60%, transparent 60%, transparent 70%, #9ca3af 70%, #9ca3af 80%, transparent 80%);
      }
    `;
    document.head.appendChild(s);
  }

  let _lastKey = null;
  let _running = false;

  async function run() {
    const key = getGbifKey();
    if (!key || _running || key === _lastKey) return;
    _running = true;
    _lastKey = key;

    document.getElementById('georef-container')?.remove();
    await new Promise(r => setTimeout(r, 1500));

    injectStyles();

    const container = document.createElement('div');
    container.id = 'georef-container';
    container.innerHTML = `
      <div id="georef-header">
        <span>⠿ georeference.it</span>
        <button id="georef-close">✕</button>
      </div>
      <iframe id="georef-frame" src="${BASE_URL}/embed/occurrence/${key}"></iframe>
      <div id="georef-resize"></div>
    `;

    const { georef_pos: pos, georef_size: size } = await loadPrefs();
    if (pos) {
      const w = size?.width  ?? 300;
      const h = size?.height ?? 420;
      const right = Math.max(8, Math.min(pos.right ?? 24, window.innerWidth  - w - 8));
      const top   = Math.max(8, Math.min(pos.top,          window.innerHeight - h - 8));
      container.style.right  = right + 'px';
      container.style.top    = top   + 'px';
      container.style.bottom = 'auto';
    }
    if (size) {
      container.style.width  = size.width  + 'px';
      container.style.height = size.height + 'px';
    }

    document.body.appendChild(container);

    container.querySelector('#georef-close').addEventListener('click', () => {
      container.style.display = 'none';
    });

    // Drag
    const header = container.querySelector('#georef-header');
    header.addEventListener('mousedown', e => {
      if (e.target.id === 'georef-close') return;
      e.preventDefault();
      header.style.cursor = 'grabbing';
      const rect = container.getBoundingClientRect();
      container.style.left = rect.left + 'px';
      container.style.top  = rect.top  + 'px';
      container.style.right  = 'auto';
      container.style.bottom = 'auto';
      const startX = e.clientX - rect.left;
      const startY = e.clientY - rect.top;
      function onMove(e) {
        const left = e.clientX - startX;
        const top  = e.clientY - startY;
        container.style.left = left + 'px';
        container.style.top  = top  + 'px';
        savePos(Math.max(0, window.innerWidth - left - container.offsetWidth), top);
      }
      function onUp() {
        header.style.cursor = 'grab';
        document.removeEventListener('mousemove', onMove);
        document.removeEventListener('mouseup', onUp);
      }
      document.addEventListener('mousemove', onMove);
      document.addEventListener('mouseup', onUp);
    });

    // Resize
    container.querySelector('#georef-resize').addEventListener('mousedown', e => {
      e.preventDefault(); e.stopPropagation();
      const rect  = container.getBoundingClientRect();
      const startX = e.clientX, startY = e.clientY;
      const startW = rect.width,  startH = rect.height;
      function onMove(e) {
        const w = Math.max(220, startW + (e.clientX - startX));
        const h = Math.max(120, startH + (e.clientY - startY));
        container.style.width  = w + 'px';
        container.style.height = h + 'px';
        saveSize(w, h);
      }
      function onUp() {
        document.removeEventListener('mousemove', onMove);
        document.removeEventListener('mouseup', onUp);
      }
      document.addEventListener('mousemove', onMove);
      document.addEventListener('mouseup', onUp);
    });

    _running = false;
  }

  let _prevPath = location.pathname;
  setInterval(() => {
    const key = getGbifKey();
    if (!key) {
      document.getElementById('georef-container')?.remove();
      _lastKey = null; _running = false;
      return;
    }
    if (location.pathname !== _prevPath) {
      _prevPath = location.pathname;
      _lastKey = null; _running = false;
      run();
    }
  }, 500);

  run();
})();
