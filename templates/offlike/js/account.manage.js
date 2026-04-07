document.addEventListener('DOMContentLoaded', function () {
  var signatureSelect = document.getElementById('signature-character-key');
  var signatureField = document.getElementById('profile-signature');
  var avatarImage = document.getElementById('selected-character-avatar');
  var resetButton = document.getElementById('profile-signature-reset');

  if (resetButton && signatureField) {
    resetButton.addEventListener('click', function () {
      signatureField.value = '';
    });
  }

  if (signatureSelect && signatureField) {
    var syncSelectedCharacter = function () {
      var selectedOption = signatureSelect.options[signatureSelect.selectedIndex];
      signatureField.value = selectedOption ? (selectedOption.getAttribute('data-signature') || '') : '';

      if (avatarImage && selectedOption) {
        var avatarUrl = selectedOption.getAttribute('data-avatar-url') || '';
        if (avatarUrl !== '') {
          avatarImage.setAttribute('src', avatarUrl);
        }
      }
    };

    signatureSelect.addEventListener('change', syncSelectedCharacter);
    syncSelectedCharacter();
  }

  var backgroundModeSelect = document.getElementById('background-mode-select');
  var backgroundImagePicker = document.getElementById('background-image-picker');
  if (!backgroundModeSelect || !backgroundImagePicker) {
    return;
  }

  var syncBackgroundPicker = function () {
    backgroundImagePicker.classList.toggle('is-hidden', backgroundModeSelect.value !== 'fixed');
  };

  backgroundModeSelect.addEventListener('change', syncBackgroundPicker);
  syncBackgroundPicker();
});
