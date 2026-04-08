(function () {
  function bindTooltips() {
    if (!window.sppItemTooltips) {
      return;
    }

    window.sppItemTooltips.bindSelector('[data-item-tooltip-id]', {
      itemIdAttribute: 'itemTooltipId',
      realmIdAttribute: 'itemTooltipRealm',
      loadingMessage: 'Loading vault tooltip...',
      errorMessage: 'Unable to load this vault entry.'
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindTooltips, { once: true });
  } else {
    bindTooltips();
  }
})();
