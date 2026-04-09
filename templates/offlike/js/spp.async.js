(function () {
  function request(url, options) {
    const config = options || {};
    const method = (config.method || 'GET').toUpperCase();
    const responseType = config.responseType === 'json' ? 'json' : 'text';
    const headers = Object.assign({
      'X-Requested-With': 'XMLHttpRequest'
    }, config.headers || {});
    const timeoutMs = Number(config.timeoutMs || 0);
    const controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
    let timerId = null;

    if (method !== 'GET') {
      return Promise.reject(new Error('sppAsync only supports GET requests.'));
    }

    if (timeoutMs > 0 && controller) {
      timerId = window.setTimeout(function () {
        controller.abort();
      }, timeoutMs);
    }

    return fetch(url, {
      method: 'GET',
      credentials: 'same-origin',
      headers: headers,
      signal: controller ? controller.signal : undefined
    }).then(function (response) {
      if (!response.ok) {
        throw new Error(config.errorMessage || ('Request failed with status ' + response.status + '.'));
      }

      return responseType === 'json' ? response.json() : response.text();
    }).finally(function () {
      if (timerId !== null) {
        window.clearTimeout(timerId);
      }
    });
  }

  function getJson(url, options) {
    return request(url, Object.assign({}, options || {}, { responseType: 'json' }));
  }

  function getText(url, options) {
    return request(url, Object.assign({}, options || {}, { responseType: 'text' }));
  }

  window.sppAsync = {
    request: request,
    getJson: getJson,
    getText: getText,
    getHtml: getText
  };
})();
