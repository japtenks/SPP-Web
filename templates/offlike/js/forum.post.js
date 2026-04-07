(function () {
  function insertTag(tag) {
    var textarea = document.getElementById('message');
    if (!textarea || textarea.disabled) {
      return;
    }

    var start = textarea.selectionStart;
    var end = textarea.selectionEnd;
    var selected = textarea.value.substring(start, end);
    var tagParts = String(tag || '').split('=');
    var open = '[' + tag + ']';
    var close = '[/' + tagParts[0] + ']';

    textarea.setRangeText(open + selected + close, start, end, 'end');
    textarea.focus();
  }

  document.querySelectorAll('.editor-toolbar [data-forum-tag]').forEach(function (button) {
    button.addEventListener('click', function () {
      insertTag(button.getAttribute('data-forum-tag'));
    });
  });
})();
