document.addEventListener('click', function (event) {
  var target = event.target;
  if (!(target instanceof HTMLElement)) {
    return;
  }

  var confirmTrigger = target.closest('[data-confirm]');
  if (confirmTrigger instanceof HTMLElement) {
    var message = confirmTrigger.getAttribute('data-confirm') || 'Are you sure?';
    if (!window.confirm(message)) {
      event.preventDefault();
      return;
    }
  }

  var copyTrigger = target.closest('[data-copy-target]');
  if (!(copyTrigger instanceof HTMLElement)) {
    return;
  }

  var targetId = copyTrigger.getAttribute('data-copy-target') || '';
  if (!targetId) {
    return;
  }

  var shell = copyTrigger.closest('.bot-admin-shell');
  var isWindowsHost = shell instanceof HTMLElement && shell.getAttribute('data-is-windows-host') === '1';
  var box = document.getElementById(targetId);
  if (!(box instanceof HTMLElement)) {
    return;
  }

  if (isWindowsHost && box.classList.contains('is-collapsed')) {
    box.classList.remove('is-collapsed');
  }

  copyTrigger.setAttribute('aria-expanded', 'true');

  var text = box.textContent || box.innerText || '';
  if (!text) {
    return;
  }

  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(text);
    return;
  }

  var temp = document.createElement('textarea');
  temp.value = text;
  document.body.appendChild(temp);
  temp.select();
  document.execCommand('copy');
  document.body.removeChild(temp);
});

document.addEventListener('DOMContentLoaded', function () {
  var input = document.getElementById('achievement-filter-input');
  if (!(input instanceof HTMLInputElement)) {
    return;
  }

  var rows = document.querySelectorAll('.js-achievement-row');
  input.addEventListener('input', function () {
    var needle = (input.value || '').toLowerCase().trim();
    rows.forEach(function (row) {
      if (!(row instanceof HTMLElement)) {
        return;
      }

      var hay = row.getAttribute('data-achievement-text') || '';
      row.style.display = !needle || hay.indexOf(needle) !== -1 ? '' : 'none';
    });
  });
});
