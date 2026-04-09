(function () {
  const state = {
    node: null,
    cache: new Map(),
    pending: new Map(),
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

  function panelMessageHtml(panel, defaultClassName, message) {
    const className = panel && panel.getAttribute('data-item-tooltip-panel-class')
      ? panel.getAttribute('data-item-tooltip-panel-class')
      : defaultClassName;
    return '<div class="' + escapeHtml(className) + '">' + escapeHtml(message) + '</div>';
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

  function tooltipUrl(itemId, realmId, itemGuid) {
    let url = 'index.php?n=server&sub=itemtooltip&nobody=1&item=' + encodeURIComponent(itemId) + '&realm=' + encodeURIComponent(realmId);
    if (itemGuid) {
      url += '&guid=' + encodeURIComponent(itemGuid);
    }
    return url;
  }

  function tooltipCacheKey(itemId, realmId, itemGuid) {
    return String(realmId) + ':' + String(itemId) + ':' + String(itemGuid || 0);
  }

  function fetchTooltipHtml(itemId, realmId, itemGuid, options) {
    const normalizedOptions = options || {};
    const cacheKey = tooltipCacheKey(itemId, realmId, itemGuid);
    if (state.cache.has(cacheKey)) {
      return Promise.resolve(state.cache.get(cacheKey));
    }

    if (state.pending.has(cacheKey)) {
      return state.pending.get(cacheKey);
    }

    const request = window.sppAsync.getText(tooltipUrl(itemId, realmId, itemGuid), {
      errorMessage: 'Unable to load item tooltip.',
      timeoutMs: normalizedOptions.timeoutMs || 8000
    }).then(function (html) {
      const safeHtml = html && html.trim() !== '' ? html : errorHtml(normalizedOptions.errorMessage);
      state.cache.set(cacheKey, safeHtml);
      state.pending.delete(cacheKey);
      return safeHtml;
    }).catch(function () {
      state.pending.delete(cacheKey);
      throw new Error('tooltip request failed');
    });

    state.pending.set(cacheKey, request);
    return request;
  }

  function requestTooltip(event, itemId, realmId, itemGuid, options) {
    const normalizedOptions = options || {};
    const cacheKey = tooltipCacheKey(itemId, realmId, itemGuid);
    if (state.cache.has(cacheKey)) {
      showTooltip(event, state.cache.get(cacheKey));
      return;
    }

    showTooltip(event, loadingHtml(normalizedOptions.loadingMessage));
    state.requestToken += 1;
    const token = state.requestToken;
    fetchTooltipHtml(itemId, realmId, itemGuid, normalizedOptions)
      .then(function (html) {
        if (token === state.requestToken) {
          showTooltip(event, html);
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

  function bindPanel(panel, options) {
    if (!panel || panel.dataset.tooltipPanelBound === '1') {
      return;
    }

    const normalizedOptions = options || {};
    const itemId = panel.getAttribute('data-item-id');
    const realmId = panel.getAttribute('data-realm-id') || normalizedOptions.realmId;
    const itemGuid = panel.getAttribute('data-item-guid') || normalizedOptions.itemGuid;
    if (!itemId || !realmId) {
      return;
    }

    panel.dataset.tooltipPanelBound = '1';
    panel.innerHTML = panelMessageHtml(panel, 'modern-item-tooltip modern-item-tooltip-loading', normalizedOptions.loadingMessage || 'Loading full item details...');

    fetchTooltipHtml(itemId, realmId, itemGuid, normalizedOptions)
      .then(function (html) {
        panel.innerHTML = html;
      })
      .catch(function () {
        panel.innerHTML = panelMessageHtml(panel, 'modern-item-tooltip modern-item-tooltip-loading', normalizedOptions.errorMessage || 'Unable to load the full item details.');
      });
  }

  function bindPanels(selector, options) {
    document.querySelectorAll(selector).forEach(function (panel) {
      bindPanel(panel, options);
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
    fetchTooltipHtml: fetchTooltipHtml,
    requestTooltip: requestTooltip,
    bindSelector: bindSelector,
    bindDelegation: bindDelegation,
    bindPanel: bindPanel,
    bindPanels: bindPanels
  };

  window.modernRequestTooltip = requestTooltip;
  window.modernMoveTooltip = moveTooltip;
  window.modernHideTooltip = hideTooltip;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () {
      bindPanels('[data-item-tooltip-panel]');
    }, { once: true });
  } else {
    bindPanels('[data-item-tooltip-panel]');
  }
})();
