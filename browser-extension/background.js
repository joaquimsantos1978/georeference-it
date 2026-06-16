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
});
