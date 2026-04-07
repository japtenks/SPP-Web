document.addEventListener('DOMContentLoaded', function () {
  var root = document.getElementById('wbuilder-app');
  if (!root) {
    return;
  }

  var classes = JSON.parse(root.getAttribute('data-wb-classes') || '{}');
  var spellCatalog = JSON.parse(root.getAttribute('data-wb-spells') || '{}');
  var defaultPreset = root.getAttribute('data-wb-default-preset') || '';
  var selectedPreset = '';
  var spellRows = [{ id: '', label: '' }];

  var classFilterSelect = document.getElementById('wb-class-filter');
  var classSelect = document.getElementById('wb-class');
  var specSelect = document.getElementById('wb-spec');
  var factionSelect = document.getElementById('wb-faction');
  var minInput = document.getElementById('wb-min');
  var maxInput = document.getElementById('wb-max');
  var eventInput = document.getElementById('wb-event');
  var nameInput = document.getElementById('wb-name');
  var classLabelInput = document.getElementById('wb-class-label');
  var specLabelInput = document.getElementById('wb-spec-label');
  var presetList = document.getElementById('wb-preset-list');
  var spellsContainer = document.getElementById('wb-spells');
  var output = document.getElementById('wb-out-line');

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function spellLabel(spellId) {
    return spellCatalog[String(spellId)] || '';
  }

  function classLabel(classId) {
    return classes[classId] && classes[classId].label ? classes[classId].label : '';
  }

  function presetLabel(classId, specId) {
    var classData = classes[classId];
    if (!classData) {
      return 'custom';
    }

    var presets = classData.presets || [];
    for (var i = 0; i < presets.length; i += 1) {
      if (String(presets[i].spec) === String(specId)) {
        return presets[i].name;
      }
    }

    return 'custom';
  }

  function selectedSpellIds(excludeIndex) {
    var selected = {};
    spellRows.forEach(function (row, index) {
      if (index === excludeIndex) {
        return;
      }
      var spellId = String((row && row.id) || '').trim();
      if (spellId) {
        selected[spellId] = true;
      }
    });
    return selected;
  }

  function spellSelectOptions(currentId, index) {
    var taken = selectedSpellIds(index);
    var ids = Object.keys(spellCatalog).sort(function (left, right) {
      return spellCatalog[left].localeCompare(spellCatalog[right]);
    });
    var options = [];

    ids.forEach(function (id) {
      if (taken[id] && String(id) !== String(currentId || '')) {
        return;
      }
      var selected = String(id) === String(currentId || '') ? ' selected' : '';
      options.push('<option value="' + escapeHtml(id) + '"' + selected + '>' + escapeHtml(spellCatalog[id]) + '</option>');
    });

    if (!options.length) {
      options.push('<option value="">No more buffs available</option>');
    }

    return options.join('');
  }

  function firstAvailableSpellId() {
    var taken = selectedSpellIds(-1);
    var ids = Object.keys(spellCatalog).sort(function (left, right) {
      return spellCatalog[left].localeCompare(spellCatalog[right]);
    });

    for (var i = 0; i < ids.length; i += 1) {
      if (!taken[ids[i]]) {
        return ids[i];
      }
    }

    return '';
  }

  function normalizedSpells() {
    return spellRows
      .map(function (row) {
        return String((row && row.id) || '').trim();
      })
      .filter(function (value) {
        return value !== '';
      });
  }

  function updateOutput() {
    var faction = factionSelect.value || '0';
    var classId = classSelect.value || '0';
    var specId = specSelect.value || '0';
    var minLevel = minInput.value || '1';
    var maxLevel = maxInput.value || minLevel;
    var eventId = eventInput.value.trim();
    var spellIds = normalizedSpells();

    classLabelInput.value = classLabel(classId);
    specLabelInput.value = presetLabel(classId, specId);

    var key = 'AiPlayerbot.WorldBuff.' + faction + '.' + classId + '.' + specId + '.' + minLevel + '.' + maxLevel;
    if (eventId) {
      key += '.' + eventId;
    }

    output.textContent = spellIds.length ? (key + ' = ' + spellIds.join(',')) : '(add one or more spell ids to build the line)';
  }

  function renderSpells() {
    spellsContainer.innerHTML = spellRows.map(function (row, index) {
      return '<div class="wb-spell-row">'
        + '<select class="wb-spell-name" data-wb-spell-index="' + index + '">' + spellSelectOptions(row.id, index) + '</select>'
        + '<input class="wb-input" type="text" value="' + escapeHtml(String(row.id || '')) + '" placeholder="Spell id" readonly>'
        + '<button type="button" class="wb-btn wb-btn-del" data-wb-remove-index="' + index + '">Remove</button>'
        + '</div>';
    }).join('');
    updateOutput();
  }

  function renderPresetList() {
    var classFilter = classFilterSelect.value;
    var html = '';

    Object.keys(classes).forEach(function (classId) {
      if (classFilter !== 'all' && classFilter !== classId) {
        return;
      }

      var classData = classes[classId];
      if (!classData.presets || !classData.presets.length) {
        html += '<div class="wb-preset-btn"><strong>' + escapeHtml(classData.label) + '</strong><span>No starter presets yet. Use the builder manually.</span></div>';
        return;
      }

      classData.presets.forEach(function (preset) {
        var activeClass = selectedPreset === preset.key ? ' active' : '';
        html += '<button type="button" class="wb-preset-btn' + activeClass + '" data-wb-preset-key="' + escapeHtml(preset.key) + '">';
        html += '<strong>' + escapeHtml(classData.label + ' - ' + preset.name) + '</strong>';
        html += '<span>Spec ' + escapeHtml(preset.spec) + ' | ' + escapeHtml(preset.spells.length) + ' spell ids | level ' + escapeHtml(preset.min) + '-' + escapeHtml(preset.max) + '</span>';
        html += '</button>';
      });
    });

    presetList.innerHTML = html || '<div class="wb-preset-btn"><strong>No presets found</strong><span>Pick a different class filter or build a line manually.</span></div>';
  }

  function handleClassChange() {
    var classId = classSelect.value;
    var classData = classes[classId] || { presets: [] };
    var options = ['<option value="0">0 = custom / default</option>'];

    (classData.presets || []).forEach(function (preset) {
      options.push('<option value="' + escapeHtml(String(preset.spec)) + '">' + escapeHtml(String(preset.spec) + ' = ' + preset.name) + '</option>');
    });

    specSelect.innerHTML = options.join('');
    updateOutput();
  }

  function loadPreset(presetKey) {
    selectedPreset = presetKey;
    var found = null;

    Object.keys(classes).some(function (classId) {
      return (classes[classId].presets || []).some(function (preset) {
        if (preset.key === presetKey) {
          found = { classId: classId, preset: preset };
          return true;
        }
        return false;
      });
    });

    if (!found) {
      return;
    }

    classSelect.value = found.classId;
    handleClassChange();
    specSelect.value = String(found.preset.spec);
    factionSelect.value = String(found.preset.faction);
    minInput.value = String(found.preset.min);
    maxInput.value = String(found.preset.max);
    eventInput.value = found.preset.event ? String(found.preset.event) : '';
    nameInput.value = found.preset.name;
    spellRows = found.preset.spells.map(function (spellId) {
      return { id: String(spellId), label: spellLabel(spellId) || 'Guide buff' };
    });
    renderPresetList();
    renderSpells();
  }

  function populateClassSelects() {
    var filterOptions = ['<option value="all">All classes</option>'];
    var classOptions = [];

    Object.keys(classes).forEach(function (classId) {
      var label = classes[classId].label + ' (' + classId + ')';
      filterOptions.push('<option value="' + escapeHtml(classId) + '">' + escapeHtml(label) + '</option>');
      classOptions.push('<option value="' + escapeHtml(classId) + '">' + escapeHtml(label) + '</option>');
    });

    classFilterSelect.innerHTML = filterOptions.join('');
    classSelect.innerHTML = classOptions.join('');
    classSelect.value = '1';
  }

  root.addEventListener('change', function (event) {
    if (event.target === classFilterSelect) {
      renderPresetList();
      return;
    }

    if (event.target === classSelect) {
      handleClassChange();
      return;
    }

    if (event.target === specSelect || event.target === factionSelect) {
      updateOutput();
      return;
    }

    if (event.target.matches('[data-wb-spell-index]')) {
      var spellIndex = parseInt(event.target.getAttribute('data-wb-spell-index'), 10);
      var spellId = String(event.target.value || '').trim();
      spellRows[spellIndex] = { id: spellId, label: spellLabel(spellId) };
      renderSpells();
    }
  });

  root.addEventListener('input', function (event) {
    if (event.target === minInput || event.target === maxInput || event.target === eventInput || event.target === nameInput) {
      updateOutput();
    }
  });

  root.addEventListener('click', function (event) {
    var presetButton = event.target.closest('[data-wb-preset-key]');
    if (presetButton) {
      loadPreset(presetButton.getAttribute('data-wb-preset-key'));
      return;
    }

    var removeButton = event.target.closest('[data-wb-remove-index]');
    if (removeButton) {
      var removeIndex = parseInt(removeButton.getAttribute('data-wb-remove-index'), 10);
      spellRows.splice(removeIndex, 1);
      if (!spellRows.length) {
        spellRows = [{ id: '', label: '' }];
      }
      renderSpells();
      return;
    }

    var actionButton = event.target.closest('[data-wb-action]');
    if (!actionButton) {
      return;
    }

    if (actionButton.getAttribute('data-wb-action') === 'add-spell') {
      var spellId = firstAvailableSpellId();
      if (!spellId) {
        return;
      }
      spellRows.push({ id: spellId, label: spellLabel(spellId) });
      renderSpells();
      return;
    }

    if (actionButton.getAttribute('data-wb-action') === 'copy-output') {
      var text = output.textContent || '';
      if (!text || text.charAt(0) === '(') {
        return;
      }
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text);
        return;
      }
      var area = document.createElement('textarea');
      area.value = text;
      document.body.appendChild(area);
      area.select();
      document.execCommand('copy');
      document.body.removeChild(area);
    }
  });

  populateClassSelects();
  renderPresetList();
  handleClassChange();
  renderSpells();
  if (defaultPreset) {
    loadPreset(defaultPreset);
  }
});
