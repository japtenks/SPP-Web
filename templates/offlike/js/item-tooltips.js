(function () {
  const state = {
    node: null,
    cache: new Map(),
    requestToken: 0,
    activeAnchor: null
  };

  function ensureTooltip() {
    if (!state.node) {
      state.node = document.createElement('div');
      state.node.id = 'modern-item-tooltip';
      state.node.className = 'talent-tt';
      state.node.style.display = 'none';
      document.body.appendChild(state.node);
    }

    return state.node;
  }

  function showTooltip(event, html) {
    const tip = ensureTooltip();
    tip.innerHTML = html;
    tip.style.display = 'block';
    moveTooltip(event);
  }

  function loadingHtml(message) {
    return '<div class="modern-item-tooltip modern-item-tooltip-loading">' + escapeHtml(message || 'Loading item tooltip...') + '</div>';
  }

  function errorHtml(message) {
    return '<div class="modern-item-tooltip modern-item-tooltip-loading">' + escapeHtml(message || 'Unable to load item tooltip.') + '</div>';
  }

  function moveTooltip(event) {
    const tip = ensureTooltip();
    if (tip.style.display === 'none' || !event) {
      return;
    }

    const offset = 18;
    const rect = tip.getBoundingClientRect();
    const spaceRight = window.innerWidth - event.clientX - offset - 12;
    const spaceLeft = event.clientX - offset - 12;
    const spaceBelow = window.innerHeight - event.clientY - offset - 12;
    const spaceAbove = event.clientY - offset - 12;
    let left = spaceRight >= rect.width || spaceRight >= spaceLeft
      ? event.clientX + offset
      : event.clientX - rect.width - offset;
    let top = spaceBelow >= rect.height || spaceBelow >= spaceAbove
      ? event.clientY + offset
      : event.clientY - rect.height - offset;

    left = Math.max(12, Math.min(left, window.innerWidth - rect.width - 12));
    top = Math.max(12, Math.min(top, window.innerHeight - rect.height - 12));
    tip.style.left = left + 'px';
    tip.style.top = top + 'px';
  }

  function hideTooltip() {
    state.requestToken += 1;
    state.activeAnchor = null;
    if (state.node) {
      state.node.style.display = 'none';
    }
  }

  function requestTooltip(event, itemId, realmId, itemGuid, options) {
    const normalizedOptions = options || {};
    const cacheKey = String(realmId) + ':' + String(itemId) + ':' + String(itemGuid || 0);
    if (state.cache.has(cacheKey)) {
      showTooltip(event, state.cache.get(cacheKey));
      return;
    }

    showTooltip(event, loadingHtml(normalizedOptions.loadingMessage));
    state.requestToken += 1;
    const token = state.requestToken;
    let url = 'index.php?n=server&sub=itemtooltip&nobody=1&item=' + encodeURIComponent(itemId) + '&realm=' + encodeURIComponent(realmId);
    if (itemGuid) {
      url += '&guid=' + encodeURIComponent(itemGuid);
    }

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
        const safeHtml = html && html.trim() !== '' ? html : errorHtml(normalizedOptions.errorMessage);
        state.cache.set(cacheKey, safeHtml);
        if (token === state.requestToken) {
          showTooltip(event, safeHtml);
        }
      })
      .catch(function () {
        if (token === state.requestToken) {
          showTooltip(event, errorHtml(normalizedOptions.errorMessage));
        }
      });
  }

  function bindSelector(selector, options) {
    const normalizedOptions = options || {};
    document.querySelectorAll(selector).forEach(function (node) {
      bindAnchor(node, normalizedOptions);
    });
  }

  function bindAnchor(anchor, options) {
    if (!anchor || anchor.dataset.tooltipBound === '1') {
      return;
    }

    const normalizedOptions = options || {};
    const itemIdAttr = normalizedOptions.itemIdAttribute || 'itemTooltipId';
    const realmIdAttr = normalizedOptions.realmIdAttribute || 'itemTooltipRealm';
    const guidAttr = normalizedOptions.guidAttribute || 'itemGuid';

    anchor.dataset.tooltipBound = '1';
    anchor.addEventListener('mouseenter', function (event) {
      state.activeAnchor = anchor;
      requestTooltip(
        event,
        anchor.dataset[itemIdAttr] || anchor.getAttribute('data-item-id') || anchor.getAttribute('data-tooltip-item'),
        anchor.dataset[realmIdAttr] || anchor.getAttribute('data-realm-id') || normalizedOptions.realmId,
        anchor.dataset[guidAttr] || anchor.getAttribute('data-item-guid') || normalizedOptions.itemGuid,
        normalizedOptions
      );
    });
    anchor.addEventListener('mousemove', function (event) {
      if (state.activeAnchor === anchor) {
        moveTooltip(event);
      }
    });
    anchor.addEventListener('mouseleave', function () {
      if (state.activeAnchor === anchor) {
        hideTooltip();
      }
    });
    anchor.addEventListener('blur', function () {
      if (state.activeAnchor === anchor) {
        hideTooltip();
      }
    });
  }

  function bindDelegation(root, selector, options) {
    if (!root) {
      return;
    }

    const normalizedOptions = options || {};
    root.addEventListener('mouseover', function (event) {
      const target = event.target.closest(selector);
      if (!target) {
        return;
      }

      state.activeAnchor = target;
      requestTooltip(
        event,
        target.getAttribute('data-tooltip-item') || target.getAttribute('data-item-id'),
        target.getAttribute('data-realm-id') || normalizedOptions.realmId,
        target.getAttribute('data-item-guid') || normalizedOptions.itemGuid,
        normalizedOptions
      );
    });
    root.addEventListener('mousemove', function (event) {
      const target = event.target.closest(selector);
      if (target && state.activeAnchor === target) {
        moveTooltip(event);
      }
    });
    root.addEventListener('mouseout', function (event) {
      const target = event.target.closest(selector);
      if (target && state.activeAnchor === target) {
        hideTooltip();
      }
    });
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  window.sppItemTooltips = {
    ensureTooltip: ensureTooltip,
    showTooltip: showTooltip,
    loadingHtml: loadingHtml,
    errorHtml: errorHtml,
    moveTooltip: moveTooltip,
    hideTooltip: hideTooltip,
    requestTooltip: requestTooltip,
    bindSelector: bindSelector,
    bindDelegation: bindDelegation
  };

  window.modernRequestTooltip = requestTooltip;
  window.modernMoveTooltip = moveTooltip;
  window.modernHideTooltip = hideTooltip;
})();
