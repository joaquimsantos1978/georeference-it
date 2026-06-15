chrome.runtime.onMessage.addListener((msg, sender, sendResponse) => {
  if (msg.type === 'fetch_occurrence') {
    fetch('https://georeference.it/api/v1/occurrences/' + msg.key, {
      headers: { Accept: 'application/json' }
    })
      .then(r => r.ok ? r.json() : null)
      .then(data => sendResponse({ data }))
      .catch(() => sendResponse({ data: null }));
    return true;
  }

  if (msg.type === 'fetch_map_tile') {
    const { lat, lng } = msg;
    const zoom = 12;
    const x = Math.floor((lng + 180) / 360 * Math.pow(2, zoom));
    const latRad = lat * Math.PI / 180;
    const y = Math.floor((1 - Math.log(Math.tan(latRad) + 1 / Math.cos(latRad)) / Math.PI) / 2 * Math.pow(2, zoom));

    fetch(`https://georeference.it/api/v1/maptile?lat=${lat}&lng=${lng}`)
      .then(r => r.arrayBuffer())
      .then(buf => {
        const bytes = new Uint8Array(buf);
        let binary = '';
        for (let i = 0; i < bytes.byteLength; i++) binary += String.fromCharCode(bytes[i]);
        sendResponse({ dataUrl: 'data:image/png;base64,' + btoa(binary), x, y, zoom });
      })
      .catch(() => sendResponse({ dataUrl: null }));
    return true;
  }
});
