document.addEventListener('DOMContentLoaded', function () {
  var input = document.querySelector("input[name='owner']");
  var picker = document.getElementById('pm-owner-picker');
  var form = document.getElementById('pm-compose-form');

  if (form) {
    form.addEventListener('submit', function (event) {
      var ownerField = form.elements.owner;
      var messageField = form.elements.message;
      if (!ownerField || !messageField) {
        return;
      }

      if (!ownerField.value.trim() || !messageField.value.trim()) {
        event.preventDefault();
      }
    });
  }

  if (!input) {
    return;
  }

  if (picker) {
    picker.addEventListener('change', function () {
      if (picker.value) {
        input.value = picker.value;
      }
    });
  }

  var box = document.createElement('div');
  box.className = 'suggestion-box';
  input.parentNode.style.position = 'relative';
  input.parentNode.appendChild(box);

  var timer = null;
  input.addEventListener('input', function () {
    clearTimeout(timer);
    var val = input.value.trim();
    if (val.length < 2) {
      box.innerHTML = '';
      return;
    }

    timer = setTimeout(function () {
      window.sppAsync.getJson('modules/account/pm_user_search.php?q=' + encodeURIComponent(val), {
        errorMessage: 'Recipient lookup failed.'
      })
        .then(function (names) {
          box.innerHTML = names.map(function (n) {
            return '<div class="suggestion-item">' + n + '</div>';
          }).join('');

          box.querySelectorAll('.suggestion-item').forEach(function (el) {
            el.addEventListener('click', function () {
              input.value = el.textContent;
              if (picker) {
                picker.value = el.textContent;
              }
              box.innerHTML = '';
            });
          });
        })
        .catch(function () {
          box.innerHTML = '';
        });
    }, 250);
  });

  document.addEventListener('click', function (event) {
    if (!box.contains(event.target) && event.target !== input) {
      box.innerHTML = '';
    }
  });
});
