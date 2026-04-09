document.addEventListener('DOMContentLoaded', function () {
  var root = document.querySelector('.rot-shell[data-is-windows-host]');
  if (!root) {
    return;
  }

  function copyRotationCommand(targetId, button) {
    var box = document.getElementById(targetId);
    if (!box) {
      return;
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

  root.querySelectorAll('[data-rotation-copy-target]').forEach(function (button) {
    button.addEventListener('click', function () {
      var targetId = button.getAttribute('data-rotation-copy-target') || '';
      if (targetId !== '') {
        copyRotationCommand(targetId, button);
      }
    });
  });
});
