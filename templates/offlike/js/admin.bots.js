(function () {
  document.addEventListener('click', function (event) {
    var target = event.target;
    if (!(target instanceof HTMLElement)) {
      return;
    }

    var copyTrigger = target.closest('[data-copy-target]');
    if (!(copyTrigger instanceof HTMLElement)) {
      return;
    }

    var shell = copyTrigger.closest('.admin-bots');
    var targetId = copyTrigger.getAttribute('data-copy-target') || '';
    if (!(shell instanceof HTMLElement) || !targetId) {
      return;
    }

    var box = document.getElementById(targetId);
    if (!(box instanceof HTMLElement)) {
      return;
    }

    if (shell.getAttribute('data-is-windows-host') === '1' && box.classList.contains('is-collapsed')) {
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
})();
