(function () {
  function findTrigger(node) {
    while (node && node !== document) {
      if (node.getAttribute && node.getAttribute('data-frontpage-news-toggle') !== null) {
        return node;
      }
      node = node.parentNode;
    }

    return null;
  }

  function toggleNewsEntry(trigger) {
    if (!trigger) {
      return;
    }

    var newsId = trigger.getAttribute('data-frontpage-news-id');
    if (!newsId) {
      return;
    }

    var entry = document.getElementById('news' + newsId);
    if (entry) {
      entry.classList.toggle('collapsed');
    }
  }

  document.addEventListener('click', function (event) {
    var trigger = findTrigger(event.target);
    if (!trigger) {
      return;
    }

    toggleNewsEntry(trigger);
  });

  document.addEventListener('keydown', function (event) {
    var trigger = findTrigger(event.target);
    if (!trigger) {
      return;
    }

    if (event.key !== 'Enter' && event.key !== ' ') {
      return;
    }

    event.preventDefault();
    toggleNewsEntry(trigger);
  });
})();
