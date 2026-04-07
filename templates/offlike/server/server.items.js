(function () {
  var tooltipNode = null;
  var tooltipCache = new Map();
  var requestToken = 0;
  var activeAnchor = null;

  function ensureTooltip() {
    if (!tooltipNode) {
      tooltipNode = document.createElement('div');
      tooltipNode.id = 'modern-item-tooltip';
      tooltipNode.className = 'talent-tt';
      document.body.appendChild(tooltipNode);
    }
    return tooltipNode;
  }

  function showTooltip(event, html) {
    var tip = ensureTooltip();
    tip.innerHTML = html;
    tip.style.display = 'block';
    moveTooltip(event);
  }

  function loadingHtml() {
    return '<div class="modern-item-tooltip modern-item-tooltip-loading">Loading vault tooltip...</div>';
  }

  function errorHtml() {
    return '<div class="modern-item-tooltip modern-item-tooltip-loading">Unable to load this vault entry.</div>';
  }

  function moveTooltip(event) {
    var tip = ensureTooltip();
    if (tip.style.display === 'none') {
      return;
    }

    var offset = 18;
    var left = event.clientX + offset;
    var top = event.clientY + offset;
    var rect = tip.getBoundingClientRect();
    if (left + rect.width > window.innerWidth - 12) {
      left = event.clientX - rect.width - offset;
    }
    if (top + rect.height > window.innerHeight - 12) {
      top = event.clientY - rect.height - offset;
    }

    tip.style.left = left + 'px';
    tip.style.top = top + 'px';
  }

  function hideTooltip() {
    requestToken += 1;
    activeAnchor = null;
    if (tooltipNode) {
      tooltipNode.style.display = 'none';
    }
  }

  function fetchTooltip(event, itemId, realmId) {
    var cacheKey = realmId + ':' + itemId;
    if (tooltipCache.has(cacheKey)) {
      showTooltip(event, tooltipCache.get(cacheKey));
      return;
    }

    showTooltip(event, loadingHtml());
    requestToken += 1;
    var token = requestToken;
    var url = 'index.php?n=server&sub=itemtooltip&nobody=1&item=' + encodeURIComponent(itemId) + '&realm=' + encodeURIComponent(realmId);

    fetch(url, {
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('tooltip request failed');
        }
        return response.text();
      })
      .then(function (html) {
        var safeHtml = html && html.trim() !== '' ? html : errorHtml();
        tooltipCache.set(cacheKey, safeHtml);
        if (token === requestToken && activeAnchor && activeAnchor.dataset.itemTooltipId === String(itemId)) {
          showTooltip(event, safeHtml);
        }
      })
      .catch(function () {
        if (token === requestToken) {
          showTooltip(event, errorHtml());
        }
      });
  }

  function bindTooltipAnchor(anchor) {
    anchor.addEventListener('mouseenter', function (event) {
      activeAnchor = anchor;
      fetchTooltip(event, anchor.dataset.itemTooltipId, anchor.dataset.itemTooltipRealm);
    });
    anchor.addEventListener('mousemove', function (event) {
      if (activeAnchor === anchor) {
        moveTooltip(event);
      }
    });
    anchor.addEventListener('mouseleave', function () {
      if (activeAnchor === anchor) {
        hideTooltip();
      }
    });
  }

  function bindTooltips() {
    document.querySelectorAll('[data-item-tooltip-id]').forEach(bindTooltipAnchor);
  }

  bindTooltips();
})();
