let modernTooltipNode = null;
const modernTooltipCache = new Map();
let modernTooltipRequestToken = 0;

function modernTooltipEnsure() {
  if (!modernTooltipNode) {
    modernTooltipNode = document.createElement('div');
    modernTooltipNode.id = 'modern-item-tooltip';
    modernTooltipNode.className = 'talent-tt';
    document.body.appendChild(modernTooltipNode);
  }

  return modernTooltipNode;
}

function modernShowTooltip(event, html) {
  const tip = modernTooltipEnsure();
  tip.innerHTML = html;
  tip.style.display = 'block';
  modernMoveTooltip(event);
}

function modernTooltipLoadingHtml() {
  return '<div class="modern-item-tooltip modern-item-tooltip-loading">Loading item tooltip...</div>';
}

function modernTooltipErrorHtml() {
  return '<div class="modern-item-tooltip modern-item-tooltip-loading">Unable to load item tooltip.</div>';
}

function modernRequestTooltip(event, itemId, realmId) {
  const cacheKey = String(realmId) + ':' + String(itemId);
  if (modernTooltipCache.has(cacheKey)) {
    modernShowTooltip(event, modernTooltipCache.get(cacheKey));
    return;
  }

  modernShowTooltip(event, modernTooltipLoadingHtml());
  modernTooltipRequestToken += 1;
  const token = modernTooltipRequestToken;
  const url = 'index.php?n=server&sub=itemtooltip&nobody=1&item=' + encodeURIComponent(itemId) + '&realm=' + encodeURIComponent(realmId);

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
      const safeHtml = html && html.trim() !== '' ? html : modernTooltipErrorHtml();
      modernTooltipCache.set(cacheKey, safeHtml);
      if (token === modernTooltipRequestToken) {
        modernShowTooltip(event, safeHtml);
      }
    })
    .catch(function () {
      if (token === modernTooltipRequestToken) {
        modernShowTooltip(event, modernTooltipErrorHtml());
      }
    });
}

function modernMoveTooltip(event) {
  const tip = modernTooltipEnsure();
  if (tip.style.display === 'none') {
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

function modernHideTooltip() {
  modernTooltipRequestToken += 1;
  if (modernTooltipNode) {
    modernTooltipNode.style.display = 'none';
  }
}

document.addEventListener('DOMContentLoaded', function () {
  const qualityFilter = document.getElementById('ah-quality-filter');
  if (qualityFilter) {
    const qualityClasses = ['quality-any', 'quality-0', 'quality-1', 'quality-2', 'quality-3', 'quality-4'];
    const syncQualityFilterClass = function () {
      qualityFilter.classList.remove.apply(qualityFilter.classList, qualityClasses);
      const value = qualityFilter.value;
      qualityFilter.classList.add(value === '-1' ? 'quality-any' : 'quality-' + value);
    };

    qualityFilter.addEventListener('change', syncQualityFilterClass);
    syncQualityFilterClass();
  }

  document.querySelectorAll('.js-ah-tooltip').forEach(function (link) {
    link.addEventListener('mouseover', function (event) {
      modernRequestTooltip(event, link.getAttribute('data-item-id'), link.getAttribute('data-realm-id'));
    });
    link.addEventListener('mousemove', function (event) {
      modernMoveTooltip(event);
    });
    link.addEventListener('mouseout', function () {
      modernHideTooltip();
    });
    link.addEventListener('blur', function () {
      modernHideTooltip();
    });
  });
});
