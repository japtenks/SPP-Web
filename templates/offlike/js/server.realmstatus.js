document.addEventListener('DOMContentLoaded', function () {
  function bindCollapseToggles(root) {
    (root || document).querySelectorAll('[data-realm-collapse="toggle"]').forEach(function (button) {
      if (button.dataset.realmCollapseBound === '1') {
        return;
      }

      button.dataset.realmCollapseBound = '1';
      button.addEventListener('click', function (event) {
        event.preventDefault();
        const card = button.closest('[data-realm-collapse="card"]');
        if (!card) {
          return;
        }

        const isCollapsed = card.classList.toggle('is-collapsed');
        button.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
        button.textContent = isCollapsed ? 'Show Details' : 'Hide Details';
      });
    });
  }

  bindCollapseToggles(document);

  const config = window.realmstatusConfig || null;
  const list = document.querySelector('[data-realmstatus-list]');
  if (!config || !config.pollUrl || !list) {
    return;
  }

  let pollInFlight = false;
  let lastSuccessfulPolledAt = '';

  function updateLegend(polledAt) {
    const legend = document.querySelector('[data-realmstatus-polled-at]');
    if (!legend || !polledAt) {
      return;
    }

    const realmIds = Array.isArray(config.targetRealmIds) ? config.targetRealmIds.join(', ') : '';
    lastSuccessfulPolledAt = polledAt;
    legend.textContent = 'Polling realms: ' + realmIds + ', updated ' + polledAt;
  }

  function markLegendFailure() {
    const legend = document.querySelector('[data-realmstatus-polled-at]');
    if (!legend) {
      return;
    }

    const realmIds = Array.isArray(config.targetRealmIds) ? config.targetRealmIds.join(', ') : '';
    legend.textContent = lastSuccessfulPolledAt
      ? 'Polling realms: ' + realmIds + ', last refresh ' + lastSuccessfulPolledAt + ' (retrying)'
      : 'Polling realms: ' + realmIds + ', refresh failed (retrying)';
  }

  function pollRealmStatus() {
    if (pollInFlight) {
      return;
    }

    pollInFlight = true;
    window.sppAsync.getJson(config.pollUrl, {
      errorMessage: 'Realm status refresh failed.'
    })
      .then(function (payload) {
        if (!payload || payload.ok !== true || typeof payload.html !== 'string') {
          throw new Error('Realm status payload was invalid.');
        }

        list.innerHTML = payload.html;
        bindCollapseToggles(list);
        updateLegend(payload.polledAt || '');
      })
      .catch(function () {
        markLegendFailure();
      })
      .finally(function () {
        pollInFlight = false;
      });
  }

  window.setInterval(pollRealmStatus, Math.max(5000, Number(config.pollIntervalMs) || 15000));
});
