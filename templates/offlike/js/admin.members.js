document.addEventListener('change', function (event) {
  var target = event.target;
  if (!(target instanceof HTMLElement)) {
    return;
  }

  if (target.matches('[data-auto-submit="change"]')) {
    var form = target.form;
    if (form) {
      form.submit();
    }
  }
});

document.addEventListener('click', function (event) {
  var target = event.target;
  if (!(target instanceof HTMLElement)) {
    return;
  }

  var trigger = target.closest('[data-confirm]');
  if (!trigger || !(trigger instanceof HTMLElement)) {
    return;
  }

  if (trigger.hasAttribute('disabled')) {
    return;
  }

  var message = trigger.getAttribute('data-confirm') || 'Are you sure?';
  if (!window.confirm(message)) {
    event.preventDefault();
  }
});
