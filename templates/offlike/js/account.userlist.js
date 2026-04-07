document.addEventListener('DOMContentLoaded', function () {
  var letterSelect = document.getElementById('letterSelect');
  if (!letterSelect || !letterSelect.form) {
    return;
  }

  letterSelect.addEventListener('change', function () {
    letterSelect.form.submit();
  });
});
