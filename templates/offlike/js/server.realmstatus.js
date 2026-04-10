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
      })
      .catch(function () {})
      .finally(function () {
        pollInFlight = false;
      });
  }

  window.setInterval(pollRealmStatus, Math.max(5000, Number(config.pollIntervalMs) || 15000));
});
