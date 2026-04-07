document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-realm-collapse="toggle"]').forEach(function (button) {
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
});
