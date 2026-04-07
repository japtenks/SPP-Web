document.addEventListener('DOMContentLoaded', function () {
  var form = document.querySelector('.register-form');
  if (!form) {
    return;
  }

  var pass1 = form.querySelector('#password');
  var pass2 = form.querySelector('#verify');
  if (!pass1 || !pass2) {
    return;
  }

  function validatePasswords() {
    if (pass1.value && pass2.value && pass1.value !== pass2.value) {
      pass2.setCustomValidity('Passwords do not match');
    } else {
      pass2.setCustomValidity('');
    }
  }

  pass1.addEventListener('input', validatePasswords);
  pass2.addEventListener('input', validatePasswords);
});
