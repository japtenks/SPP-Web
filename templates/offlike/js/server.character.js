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
