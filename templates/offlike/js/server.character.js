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

function modernRequestTooltip(event, itemId, realmId, itemGuid) {
  const cacheKey = realmId + ':' + itemId + ':' + (itemGuid || 0);
  if (modernTooltipCache.has(cacheKey)) {
    modernShowTooltip(event, modernTooltipCache.get(cacheKey));
    return;
  }

  modernShowTooltip(event, modernTooltipLoadingHtml());
  modernTooltipRequestToken += 1;
  const token = modernTooltipRequestToken;
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

function sppPaperdollSwap(selectEl, targetId) {
  const wrap = document.getElementById(targetId);
  if (!wrap) {
    return;
  }

  const key = selectEl.value;
  wrap.querySelectorAll('[data-panel]').forEach(function (panel) {
    panel.classList.toggle('is-active', panel.getAttribute('data-panel') === key);
  });
}

function sppRecipeFilter(buttonEl, listId, filterKey) {
  const list = document.getElementById(listId);
  if (!list) {
    return;
  }

  const parent = buttonEl.parentNode;
  if (parent) {
    parent.querySelectorAll('.character-recipe-filter').forEach(function (button) {
      button.classList.toggle('is-active', button === buttonEl);
    });
  }

  list.querySelectorAll('.character-recipe-row').forEach(function (row) {
    const tags = row.getAttribute('data-tags') || '';
    const visible = filterKey === 'all' || tags.indexOf('|' + filterKey + '|') !== -1;
    row.classList.toggle('is-hidden', !visible);
  });
}

function sppStrategyAppend(textareaId, token, mode) {
  const el = document.getElementById(textareaId);
  if (!el) {
    return;
  }

  const normalizedToken = String(token || '').trim().toLowerCase();
  if (normalizedToken === '') {
    return;
  }

  const prefix = mode === 'minus' ? '-' : '+';
  const value = prefix + token;
  const parts = (el.value || '')
    .split(',')
    .map(function (part) { return part.trim(); })
    .filter(Boolean)
    .filter(function (part) { return part.replace(/^[-+]/, '').toLowerCase() !== normalizedToken; });

  parts.push(value);
  el.value = parts.join(', ');
  el.dispatchEvent(new Event('change', { bubbles: true }));
}

window.modernRequestTooltip = modernRequestTooltip;
window.modernMoveTooltip = modernMoveTooltip;
window.modernHideTooltip = modernHideTooltip;
window.sppPaperdollSwap = sppPaperdollSwap;
window.sppRecipeFilter = sppRecipeFilter;
window.sppStrategyAppend = sppStrategyAppend;

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-questlog]').forEach(function (questLog) {
    const entries = questLog.querySelectorAll('[data-quest-target]');
    const panels = questLog.querySelectorAll('[data-quest-panel]');
    const groups = questLog.querySelectorAll('[data-quest-group]');
    if (!entries.length || !panels.length) {
      return;
    }

    const focusGroup = function (groupId) {
      groups.forEach(function (group) {
        group.open = group.getAttribute('data-quest-group') === groupId;
      });
    };

    const activate = function (targetId) {
      entries.forEach(function (entry) {
        entry.classList.toggle('is-active', entry.getAttribute('data-quest-target') === targetId);
      });
      panels.forEach(function (panel) {
        panel.classList.toggle('is-active', panel.getAttribute('data-quest-panel') === targetId);
      });

      const activeEntry = questLog.querySelector('[data-quest-target="' + targetId + '"]');
      if (activeEntry) {
        focusGroup(activeEntry.getAttribute('data-quest-group-owner') || 'active');
      }
    };

    entries.forEach(function (entry) {
      entry.addEventListener('click', function () {
        activate(entry.getAttribute('data-quest-target'));
      });
    });

    focusGroup('active');
  });

  document.querySelectorAll('.character-panel').forEach(function (panel) {
    const heading = panel.querySelector('.character-panel-title');
    if (!heading || heading.textContent.trim() !== 'Achievements') {
      return;
    }

    panel.querySelectorAll('.character-achievement-section').forEach(function (section) {
      const title = section.querySelector(':scope > .character-achievement-section-title');
      if (!title) {
        return;
      }

      const body = document.createElement('div');
      body.className = 'character-achievement-section-body';

      while (title.nextSibling) {
        body.appendChild(title.nextSibling);
      }

      if (!body.children.length) {
        return;
      }

      if (section.classList.contains('character-achievement-section-pinned')) {
        section.appendChild(body);
        return;
      }

      const details = document.createElement('details');
      details.className = 'character-achievement-section collapse-card';

      const summary = document.createElement('summary');
      summary.className = 'character-achievement-summary collapse-card__summary';

      const copy = document.createElement('span');
      copy.className = 'character-achievement-summary-text collapse-card__copy';

      const summaryHeading = document.createElement('strong');
      summaryHeading.className = 'character-achievement-section-title collapse-card__title';
      summaryHeading.textContent = title.textContent;
      copy.appendChild(summaryHeading);

      const caret = document.createElement('span');
      caret.className = 'collapse-card__caret';
      caret.setAttribute('aria-hidden', 'true');

      body.classList.add('collapse-card__body');
      summary.appendChild(copy);
      summary.appendChild(caret);
      details.appendChild(summary);
      details.appendChild(body);
      section.replaceWith(details);
    });
  });
});
