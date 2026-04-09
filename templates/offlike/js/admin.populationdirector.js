(function () {
  function setOverrideMinutes(root, value) {
    var input = root.querySelector('[data-populationdirector-minutes]');
    if (!(input instanceof HTMLInputElement)) {
      return;
    }

    input.value = String(value);
    input.focus();
    input.select();
  }

  function copyText(text) {
    if (!text) {
      return;
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text);
      return;
    }

    var temp = document.createElement('textarea');
    temp.value = text;
    temp.setAttribute('readonly', 'true');
    temp.style.position = 'absolute';
    temp.style.left = '-9999px';
    document.body.appendChild(temp);
    temp.select();
    document.execCommand('copy');
    document.body.removeChild(temp);
  }

  document.addEventListener('click', function (event) {
    var target = event.target;
    if (!(target instanceof HTMLElement)) {
      return;
    }

    var shell = target.closest('.populationdirector-shell');
    if (!(shell instanceof HTMLElement)) {
      return;
    }

    var minutesButton = target.closest('[data-populationdirector-set-minutes]');
    if (minutesButton instanceof HTMLElement) {
      event.preventDefault();
      setOverrideMinutes(shell, minutesButton.getAttribute('data-populationdirector-set-minutes') || '');
      return;
    }

    var copyButton = target.closest('[data-populationdirector-copy-endpoint]');
    if (copyButton instanceof HTMLElement) {
      event.preventDefault();
      var endpointId = copyButton.getAttribute('data-populationdirector-copy-endpoint') || '';
      var endpoint = endpointId ? document.getElementById(endpointId) : null;
      var text = endpoint ? (endpoint.textContent || endpoint.innerText || '') : '';
      copyText(text.trim());
    }
  });
})();
