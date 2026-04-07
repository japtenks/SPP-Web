document.addEventListener('DOMContentLoaded', function () {
  var root = document.querySelector('.admin-identity-health[data-identity-is-windows-host]');
  if (!root) {
    return;
  }

  var isWindowsHost = root.getAttribute('data-identity-is-windows-host') === '1';

  function revealAndCopyCommand(targetId, button) {
    var box = document.getElementById(targetId);
    if (!box) {
      return;
    }

    if (isWindowsHost && box.classList.contains('is-collapsed')) {
      box.classList.remove('is-collapsed');
    }

    if (button) {
      button.setAttribute('aria-expanded', 'true');
    }

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
  }

  root.querySelectorAll('[data-identity-copy-target]').forEach(function (button) {
    button.addEventListener('click', function () {
      var targetId = button.getAttribute('data-identity-copy-target') || '';
      if (targetId !== '') {
        revealAndCopyCommand(targetId, button);
      }
    });
  });
});
