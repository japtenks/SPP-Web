let CHAT_FILTER_OPTIONS = [];
let LAYER_FILTER_OPTIONS = [];
let MACRO_PRESETS = [];

function loadBotcommandsClientState() {
    const stateNode = document.getElementById('botcommands-client-state');
    let state = {};

    if (stateNode) {
        try {
            state = JSON.parse(stateNode.textContent || '{}');
        } catch (error) {
            state = {};
        }
    }

    CHAT_FILTER_OPTIONS = state.chatFilterOptions || [];
    LAYER_FILTER_OPTIONS = state.layerFilterOptions || [];
    MACRO_PRESETS = state.macroPresets || [];
}

function srefTab(btn, panelId) {
    document.querySelectorAll('.sref-tab-btn').forEach(function (b) { b.classList.remove('active'); });
    document.querySelectorAll('.sref-panel').forEach(function (p) { p.classList.remove('active'); });
    btn.classList.add('active');
    document.getElementById(panelId).classList.add('active');
    if (panelId === 'tab-builder') csbRender();
    if (panelId === 'tab-macros') mbRender();
}

function filterChatFilterCards() {
    const query = (((document.getElementById('chatFilterSearch') || {}).value) || '').toLowerCase().trim();
    document.querySelectorAll('#chatFilterCards .cff-card').forEach(function (card) {
        const haystack = card.getAttribute('data-filter-search') || '';
        card.style.display = !query || haystack.indexOf(query) !== -1 ? '' : 'none';
    });
}

function filterCardGrid(inputId, selector) {
    const query = (((document.getElementById(inputId) || {}).value) || '').toLowerCase().trim();
    document.querySelectorAll(selector).forEach(function (card) {
        const haystack = card.getAttribute('data-filter-search') || '';
        card.style.display = !query || haystack.indexOf(query) !== -1 ? '' : 'none';
    });
}

function toggleSelectValue(selectId, value) {
    const select = document.getElementById(selectId);
    if (!select) return;
    select.value = select.value === value ? 'all' : value;
}

function applyBotChipFilter(kind, value) {
    if (kind === 'type') toggleSelectValue('botCommandTypeFilter', value);
    if (kind === 'state') toggleSelectValue('botCommandStateFilter', value);
    if (kind === 'role') toggleSelectValue('botCommandRoleFilter', value);
    if (kind === 'class') toggleSelectValue('botCommandClassFilter', value);
    filterBotCommandCards();
}

function applyGmChipFilter(kind, value) {
    if (kind === 'security') toggleSelectValue('gmCommandSecurityFilter', value);
    if (kind === 'prefix') toggleSelectValue('gmCommandPrefixFilter', value);
    filterGmCommandCards();
}

function filterBotCommandCards() {
    const query = (((document.getElementById('botCommandSearch') || {}).value) || '').toLowerCase().trim();
    const type = (((document.getElementById('botCommandTypeFilter') || {}).value) || 'all').toLowerCase();
    const state = (((document.getElementById('botCommandStateFilter') || {}).value) || 'all').toLowerCase();
    const role = (((document.getElementById('botCommandRoleFilter') || {}).value) || 'all').toLowerCase();
    const playerClass = (((document.getElementById('botCommandClassFilter') || {}).value) || 'all').toLowerCase();

    document.querySelectorAll('#botCommandCards .cmd-card').forEach(function (card) {
        const haystack = card.getAttribute('data-filter-search') || '';
        const typeValue = (card.getAttribute('data-type') || '').toLowerCase();
        const stateTags = (card.getAttribute('data-state-tags') || '').split(',').filter(Boolean);
        const roleTags = (card.getAttribute('data-role-tags') || '').split(',').filter(Boolean);
        const classTags = (card.getAttribute('data-class-tags') || '').split(',').filter(Boolean);

        const searchMatch = !query || haystack.indexOf(query) !== -1;
        const typeMatch = type === 'all' || typeValue === type;
        const stateMatch = state === 'all' || stateTags.indexOf(state) !== -1;
        const roleMatch = role === 'all' || roleTags.indexOf(role) !== -1;
        const classMatch = playerClass === 'all' || classTags.indexOf(playerClass) !== -1;

        card.style.display = (searchMatch && typeMatch && stateMatch && roleMatch && classMatch) ? '' : 'none';
    });
}

function filterGmCommandCards() {
    const query = (((document.getElementById('gmCommandSearch') || {}).value) || '').toLowerCase().trim();
    const security = (((document.getElementById('gmCommandSecurityFilter') || {}).value) || 'all').toLowerCase();
    const prefix = (((document.getElementById('gmCommandPrefixFilter') || {}).value) || 'all').toLowerCase();

    document.querySelectorAll('#gmCommandCards .cmd-card').forEach(function (card) {
        const haystack = card.getAttribute('data-filter-search') || '';
        const securityValue = (card.getAttribute('data-security') || '').toLowerCase();
        const prefixValue = (card.getAttribute('data-prefix') || '').toLowerCase();

        const searchMatch = !query || haystack.indexOf(query) !== -1;
        const securityMatch = security === 'all' || securityValue === security;
        const prefixMatch = prefix === 'all' || prefixValue === prefix;

        card.style.display = (searchMatch && securityMatch && prefixMatch) ? '' : 'none';
    });
}

function resetBotCommandFilters() {
    const search = document.getElementById('botCommandSearch');
    const type = document.getElementById('botCommandTypeFilter');
    const state = document.getElementById('botCommandStateFilter');
    const role = document.getElementById('botCommandRoleFilter');
    const playerClass = document.getElementById('botCommandClassFilter');
    if (search) search.value = '';
    if (type) type.value = 'all';
    if (state) state.value = 'all';
    if (role) role.value = 'all';
    if (playerClass) playerClass.value = 'all';
    filterBotCommandCards();
}

function resetGmCommandFilters() {
    const search = document.getElementById('gmCommandSearch');
    const security = document.getElementById('gmCommandSecurityFilter');
    const prefix = document.getElementById('gmCommandPrefixFilter');
    if (search) search.value = '';
    if (security) security.value = 'all';
    if (prefix) prefix.value = 'all';
    filterGmCommandCards();
}

const CSB_TRIGGERS = {
  'Health and Resources': [
    'critical health','low health','medium health','almost full health',
    'no mana','low mana','medium mana','high mana','almost full mana',
    'no energy available','light energy available','medium energy available','high energy available',
    'light rage available','medium rage available','high rage available'
  ],
  'Combat': [
    'combat start','combat end','death',
    'no target','target in sight','target changed','invalid target','not facing target',
    'has aggro','lose aggro','high threat','medium threat','some threat','no threat',
    'multiple attackers','has attackers','no attackers','possible adds',
    'enemy is close','enemy player near','enemy player ten yards',
    'enemy out of melee','enemy out of spell',
    'behind target','not behind target','panic','outnumbered'
  ],
  'Party': [
    'party member critical health','party member low health','party member medium health',
    'party member almost full health','party member dead','protect party member','no pet'
  ],
  'Movement and Position': [
    'far from master','not near master',
    'wander far','wander medium','wander near',
    'swimming','move stuck','move long stuck','falling','falling far',
    'can loot','loot available','far from loot target'
  ],
  'Battleground and PvP': [
    'in battleground','in pvp','in pve',
    'bg active','bg ended','bg waiting','bg invite active',
    'player has flag','player has no flag','team has flag','enemy team has flag',
    'enemy flagcarrier near','in battleground without flag'
  ],
  'Status Effects': [
    'dead','corpse near','mounted','rooted','party member rooted',
    'feared','stunned','charmed'
  ],
  'RPG': [
    'no rpg target','has rpg target','far from rpg target','near rpg target',
    'rpg wander','rpg start quest','rpg end quest','rpg buy',
    'rpg sell','rpg repair','rpg train'
  ],
  'Timing': [
    'random','timer','seldom','often','very often',
    'random bot update','no non bot players around','new player nearby'
  ],
  'Buffs and Items': [
    'potion cooldown','use trinket','need world buff',
    'give food','give water',
    'has blessing of salvation','has greater blessing of salvation'
  ]
};

const CSB_ACTIONS = {
  'Qualified': ['say::','emote::'],
  'Communication': ['talk','suggest what to do','greet'],
  'Combat': [
    'attack','melee','dps assist','tank assist','dps aoe',
    'flee','flee with pet','shoot',
    'interrupt current spell','attack enemy player','attack least hp target'
  ],
  'Survival and Healing': [
    'healing potion','healthstone','mana potion','food','drink',
    'use bandage','try emergency','whipper root tuber',
    'fire protection potion','free action potion'
  ],
  'Movement': [
    'follow','stay','return','runaway','flee to master',
    'mount','hearthstone','move random','guard'
  ],
  'Loot': [
    'loot','move to loot','add loot','release loot','auto loot roll','reveal gathering item'
  ],
  'Battleground': [
    'free bg join','bg tactics','bg move to objective',
    'bg move to start','attack enemy flag carrier'
  ],
  'Racials': [
    'war stomp','berserking','blood fury','shadowmeld','stoneform',
    'arcane torrent','will of the forsaken','cannibalize','mana tap',
    'escape artist','perception','every_man_for_himself','gift of the naaru'
  ],
  'Misc': [
    'delay','reset','random bot update',
    'xp gain','honor gain','invite nearby','check mail','update gear'
  ]
};

let csbLines = [];
let csbInitialized = false;
  let mbFilters = [];
  let mbInitialized = false;
  let mbLayers = [];

function csbInit() {
  if (csbInitialized) return;
  csbInitialized = true;
  csbLines = [{
    trigger: 'critical health',
    actions: [
      { type: 'emote::', qualifier: 'helpme', priority: 99 },
      { type: 'say::', qualifier: 'critical health', priority: 98 }
    ]
  }];
  csbRender();
}

function csbToggleGuid() {
  const isGuid = document.querySelector('input[name="csb-owner"]:checked').value === 'guid';
  const guidInput = document.getElementById('csb-guid');
  if (guidInput) {
    guidInput.classList.toggle('is-hidden', !isGuid);
  }
  csbUpdateOutput();
}

function csbAddLine() {
  csbLines.push({ trigger: 'low health', actions: [{ type: 'say::', qualifier: 'low health', priority: 98 }] });
  csbRender();
}

function csbRemoveLine(idx) {
  csbLines.splice(idx, 1);
  csbRender();
}

function csbAddAction(lineIdx) {
  csbLines[lineIdx].actions.push({ type: 'emote::', qualifier: 'helpme', priority: 99 });
  csbRender();
}

function csbRemoveAction(lineIdx, actionIdx) {
  csbLines[lineIdx].actions.splice(actionIdx, 1);
  csbRender();
}

function csbSet(lineIdx, field, value) {
  csbLines[lineIdx][field] = value;
  csbUpdateOutput();
}

function csbSetAction(lineIdx, actionIdx, field, value) {
  if (field === 'type') {
    csbLines[lineIdx].actions[actionIdx].type = value;
    if (value === 'say::') csbLines[lineIdx].actions[actionIdx].qualifier = 'critical health';
    else if (value === 'emote::') csbLines[lineIdx].actions[actionIdx].qualifier = 'helpme';
    else csbLines[lineIdx].actions[actionIdx].qualifier = '';
    csbRender();
  } else {
    csbLines[lineIdx].actions[actionIdx][field] = value;
    csbUpdateOutput();
  }
}

function csbBuildTriggerSelect(lineIdx, selected) {
  let html = '<select class="csb-select is-trigger" data-action="csb-set-trigger" data-line-index="' + lineIdx + '">';
  Object.keys(CSB_TRIGGERS).forEach(function(group) {
    html += '<optgroup label="' + group + '">';
    CSB_TRIGGERS[group].forEach(function(trigger) {
      html += '<option value="' + trigger + '"' + (trigger === selected ? ' selected' : '') + '>' + trigger + '</option>';
    });
    html += '</optgroup>';
  });
  html += '</select>';
  return html;
}

function csbBuildActionSelect(lineIdx, actionIdx, selected) {
  let html = '<select class="csb-select is-action" data-action="csb-set-action-type" data-line-index="' + lineIdx + '" data-action-index="' + actionIdx + '">';
  Object.keys(CSB_ACTIONS).forEach(function(group) {
    html += '<optgroup label="' + group + '">';
    CSB_ACTIONS[group].forEach(function(action) {
      html += '<option value="' + action + '"' + (action === selected ? ' selected' : '') + '>' + action + '</option>';
    });
    html += '</optgroup>';
  });
  html += '</select>';
  return html;
}

function csbRender() {
  const container = document.getElementById('csb-lines');
  if (!container) return;
  let html = '';
  csbLines.forEach(function(line, li) {
    html += '<div class="csb-line-card">';
    html += '<div class="csb-line-header"><span class="csb-line-num">Line ' + (li + 1) + '</span>';
    html += '<button type="button" class="csb-btn csb-btn-del" data-action="csb-remove-line" data-line-index="' + li + '">Remove</button></div>';
    html += '<div class="csb-row"><span class="csb-label">Trigger</span>' + csbBuildTriggerSelect(li, line.trigger) + '</div>';
    html += '<div class="csb-line-actions">';
    line.actions.forEach(function(action, ai) {
      const needsQualifier = action.type === 'say::' || action.type === 'emote::';
      const listAttr = action.type === 'say::' ? 'list="csb-say-list"' : (action.type === 'emote::' ? 'list="csb-emote-list"' : '');
      html += '<div class="csb-action-row">';
      html += csbBuildActionSelect(li, ai, action.type);
      if (needsQualifier) {
        html += '<input class="csb-input csb-qual" type="text" ' + listAttr + ' placeholder="qualifier" value="' + (action.qualifier || '').replace(/"/g, '&quot;') + '" data-action="csb-set-action-qualifier" data-line-index="' + li + '" data-action-index="' + ai + '">';
      }
      html += '!<input class="csb-input csb-priority" type="number" min="1" max="100" value="' + action.priority + '" data-action="csb-set-action-priority" data-line-index="' + li + '" data-action-index="' + ai + '">';
      html += '<button type="button" class="csb-btn csb-btn-del" data-action="csb-remove-action" data-line-index="' + li + '" data-action-index="' + ai + '">Remove</button>';
      html += '</div>';
    });
    html += '<button type="button" class="csb-btn csb-btn-add is-tight-top" data-action="csb-add-action" data-line-index="' + li + '">+ Action</button>';
    html += '</div></div>';
  });
  container.innerHTML = html;
  csbUpdateOutput();
}

function csbBuildActionLine(line) {
  const actionString = line.actions.map(function(action) {
    const name = (action.type === 'say::' || action.type === 'emote::') ? action.type + action.qualifier : action.type;
    return name + '!' + action.priority;
  }).join(',');
  return line.trigger + '>' + actionString;
}

function csbUpdateOutput() {
  const name = ((document.getElementById('csb-name') || {}).value || 'mysay').trim() || 'mysay';
  const ownerRadio = document.querySelector('input[name="csb-owner"]:checked');
  const ownerValue = ownerRadio && ownerRadio.value === 'guid' ? (((document.getElementById('csb-guid') || {}).value || '0').trim() || '0') : '0';

  const preview = document.getElementById('csb-activation-preview');
  if (preview) preview.textContent = '+custom::' + name;

  const actionLines = csbLines.map(csbBuildActionLine);
  const activationOutput = document.getElementById('csb-out-activation');
  if (activationOutput) activationOutput.textContent = '+custom::' + name;

  const sqlRows = actionLines.map(function(line, index) {
    return "  ('" + name + "', " + (index + 1) + ", " + ownerValue + ", '" + line + "')";
  }).join(",\n");
  const sql = 'INSERT INTO ai_playerbot_custom_strategy (name, idx, owner, action_line) VALUES\n' + sqlRows + ';';
  const sqlOutput = document.getElementById('csb-out-sql');
  if (sqlOutput) sqlOutput.textContent = actionLines.length ? sql : '(add at least one line)';

  const csLines = actionLines.map(function(line, index) {
    return 'cs ' + name + ' ' + (index + 1) + ' ' + line;
  }).join('\n');
  const csOutput = document.getElementById('csb-out-cs');
  if (csOutput) csOutput.textContent = actionLines.length ? csLines : '(add at least one line)';
}

function csbCopy(elementId) {
  const element = document.getElementById(elementId);
  const text = element ? element.textContent : '';
  if (!text) return;
  navigator.clipboard.writeText(text).then(function() {
    const btn = document.querySelector('.csb-btn-copy[data-copy-target="' + elementId + '"]');
    if (!btn) return;
    const original = btn.textContent;
    btn.textContent = 'Copied!';
    setTimeout(function() { btn.textContent = original; }, 1500);
  });
}

function mbInit() {
  if (mbInitialized) return;
  mbInitialized = true;
  mbFilters = [];
  mbLayers = [mbCreateLayer()];
  mbAddFilter('@tank');
  mbRenderLayers();
}

function mbRender() {
  mbInit();
  mbRenderFilters();
  mbUpdateOutput();
}

function mbGetPresetByKey(key) {
  return MACRO_PRESETS.find(function(preset) { return preset.key === key; }) || MACRO_PRESETS[0];
}

function mbCreateLayer() {
  return {
    targetFilter: '',
    presetKey: (MACRO_PRESETS[0] || {}).key || '',
    optionValue: '',
    value: '',
    strategies: []
  };
}

function mbGetDeliveryMode() {
  return (((document.getElementById('mb-delivery') || {}).value) || 'whisper').trim();
}

function mbGetDeliveryPrefix() {
  const mode = mbGetDeliveryMode();
  if (mode === 'party') return '/p';
  if (mode === 'raid') return '/ra';
  if (mode === 'guild') return '/g';
  if (mode === 'say') return '/s';
  return '/w';
}

function mbUpdateDeliveryMode() {
  const targetRow = document.getElementById('mb-target-row');
  const target = document.getElementById('mb-target');
  const isWhisper = mbGetDeliveryMode() === 'whisper';
  if (targetRow) targetRow.style.display = isWhisper ? '' : 'none';
  if (target) {
    target.style.display = isWhisper ? '' : 'none';
    target.placeholder = isWhisper ? 'BotName' : '';
  }
  mbUpdateOutput();
}

function mbBuildPresetSelect(index, selectedKey) {
  let html = '<select class="csb-select mb-layer-preset" data-action="mb-set-layer-preset" data-layer-index="' + index + '">';
  let currentGroup = '';
  MACRO_PRESETS.forEach(function(preset) {
    if (preset.group !== currentGroup) {
      if (currentGroup) html += '</optgroup>';
      currentGroup = preset.group;
      html += '<optgroup label="' + preset.group + '">';
    }
    html += '<option value="' + preset.key + '"' + (preset.key === selectedKey ? ' selected' : '') + '>' + preset.label + '</option>';
  });
  if (currentGroup) html += '</optgroup>';
  html += '</select>';
  return html;
}

function mbBuildLayerTargetSelect(index, selectedValue) {
  let html = '<select class="csb-select mb-layer-target" data-action="mb-set-layer-target" data-layer-index="' + index + '">';
  html += '<option value="">No split target</option>';
  let currentGroup = '';
  LAYER_FILTER_OPTIONS.forEach(function(option) {
    if (option.group !== currentGroup) {
      if (currentGroup) html += '</optgroup>';
      currentGroup = option.group;
      html += '<optgroup label="' + option.group + '">';
    }
    html += '<option value="' + option.token + '"' + (option.token === selectedValue ? ' selected' : '') + '>' + option.label + '</option>';
  });
  if (currentGroup) html += '</optgroup>';
  html += '</select>';
  return html;
}

function mbGetLayerPreset(index) {
  return mbGetPresetByKey((mbLayers[index] || {}).presetKey || '');
}

function mbGetClassStrategyOptions(index) {
  const layer = mbLayers[index];
  const preset = mbGetLayerPreset(index);
  const selectedState = (layer && layer.optionValue) || '';
  if (!preset || (preset.mode || '') !== 'class_strategies' || !selectedState) return [];
  return (preset.strategyOptions && preset.strategyOptions[selectedState]) ? preset.strategyOptions[selectedState] : [];
}

function mbRenderLayerClassBuilder(index) {
  const layer = mbLayers[index];
  const options = mbGetClassStrategyOptions(index);
  if (!layer || !options.length) return '';

  layer.strategies = layer.strategies.filter(function(selection) {
    return options.indexOf(selection) !== -1;
  });
  if (!layer.strategies.length) {
    layer.strategies = [options[0]];
  }

  let html = '<div class="mb-sub-builder"><div class="mb-sub-title">Add strategies for this state</div>';
  layer.strategies.forEach(function(selection, strategyIndex) {
    html += '<div class="mb-strategy-row">';
    html += '<select class="csb-select" data-action="mb-set-layer-strategy" data-layer-index="' + index + '" data-strategy-index="' + strategyIndex + '">';
    options.forEach(function(option) {
      html += '<option value="' + option.replace(/"/g, '&quot;') + '"' + (option === selection ? ' selected' : '') + '>' + option + '</option>';
    });
    html += '</select>';
    if (layer.strategies.length > 1) {
      html += '<button type="button" class="csb-btn csb-btn-del" data-action="mb-remove-layer-strategy" data-layer-index="' + index + '" data-strategy-index="' + strategyIndex + '">Remove</button>';
    }
    html += '</div>';
  });
  html += '<button type="button" class="csb-btn csb-btn-add" data-action="mb-add-layer-strategy" data-layer-index="' + index + '">+ Add Row</button></div>';
  return html;
}

function mbRenderLayers() {
  const container = document.getElementById('mb-layers');
  if (!container) return;
  let html = '';
  mbLayers.forEach(function(layer, index) {
    const preset = mbGetLayerPreset(index);
    const mode = preset.mode || (preset.needsValue ? 'value' : 'direct');
    html += '<div class="mb-layer">';
    html += '<div class="mb-layer-head"><span class="mb-layer-title">Layer ' + (index + 1) + '</span>';
    if (mbLayers.length > 1) {
      html += '<button type="button" class="csb-btn csb-btn-del" data-action="mb-remove-layer" data-layer-index="' + index + '">Remove</button>';
    }
    html += '</div>';
    html += '<div class="mb-layer-grid">';
    html += mbBuildLayerTargetSelect(index, layer.targetFilter || '');
    html += mbBuildPresetSelect(index, layer.presetKey || '');
    if (mode === 'options' || mode === 'class_strategies') {
      html += '<select class="csb-select mb-layer-option" data-action="mb-set-layer-option" data-layer-index="' + index + '">';
      html += '<option value="">' + (preset.optionPlaceholder || 'Choose an option') + '</option>';
      (preset.options || []).forEach(function(option) {
        html += '<option value="' + option.value.replace(/"/g, '&quot;') + '"' + (option.value === (layer.optionValue || '') ? ' selected' : '') + '>' + option.label + '</option>';
      });
      if (mode === 'options') {
        html += '<option value="__custom__"' + ((layer.optionValue || '') === '__custom__' ? ' selected' : '') + '>Custom...</option>';
      }
      html += '</select>';
    }
    const showValue = mode === 'value' || mode === 'custom' || (mode === 'options' && (layer.optionValue || '') === '__custom__');
    if (showValue) {
      html += '<input class="csb-input mb-layer-value" type="text" placeholder="' + (((layer.optionValue || '') === '__custom__' ? (preset.customPlaceholder || preset.placeholder || '') : (preset.placeholder || '')).replace(/"/g, '&quot;')) + '" value="' + (layer.value || '').replace(/"/g, '&quot;') + '" data-action="mb-set-layer-value" data-layer-index="' + index + '">';
    }
    html += '</div>';
    if (mode === 'class_strategies') {
      html += mbRenderLayerClassBuilder(index);
    }
    html += '</div>';
  });
  container.innerHTML = html;
}

function mbSetLayerTargetFilter(index, value) {
  mbLayers[index].targetFilter = value;
  mbUpdateOutput();
}

function mbSetLayerPreset(index, value) {
  mbLayers[index].presetKey = value;
  mbLayers[index].optionValue = '';
  mbLayers[index].value = '';
  mbLayers[index].strategies = [];
  mbRenderLayers();
  mbUpdateOutput();
}

function mbSetLayerOption(index, value) {
  mbLayers[index].optionValue = value;
  if (value !== '__custom__') {
    mbLayers[index].value = '';
  }
  mbLayers[index].strategies = [];
  mbRenderLayers();
  mbUpdateOutput();
}

function mbSetLayerValue(index, value) {
  mbLayers[index].value = value;
  mbUpdateOutput();
}

function mbAddLayer() {
  mbLayers.push(mbCreateLayer());
  mbRenderLayers();
  mbUpdateOutput();
}

function mbRemoveLayer(index) {
  mbLayers.splice(index, 1);
  if (!mbLayers.length) mbLayers = [mbCreateLayer()];
  mbRenderLayers();
  mbUpdateOutput();
}

function mbAddLayerStrategy(index) {
  const options = mbGetClassStrategyOptions(index);
  if (!options.length) return;
  mbLayers[index].strategies.push(options[0]);
  mbRenderLayers();
  mbUpdateOutput();
}

function mbAddFilter(initialToken) {
  const fallback = CHAT_FILTER_OPTIONS[0] || { token: '@tank', needsValue: false, placeholder: '' };
  const match = CHAT_FILTER_OPTIONS.find(function(option) { return option.token === initialToken || option.label === initialToken; }) || fallback;
  mbFilters.push({
    token: match.token,
    needsValue: !!match.needsValue,
    placeholder: match.placeholder || '',
    value: ''
  });
  mbRenderFilters();
}

function mbAddQuickFilter(token) {
  const existing = mbFilters.some(function(filter) {
    return (filter.token || '').trim().toLowerCase() === String(token || '').trim().toLowerCase();
  });
  if (!existing) {
    mbAddFilter(token);
    return;
  }
  mbUpdateOutput();
}

function mbSetLayerStrategy(layerIndex, strategyIndex, value) {
  mbLayers[layerIndex].strategies[strategyIndex] = value;
  mbUpdateOutput();
}

function mbRemoveLayerStrategy(layerIndex, strategyIndex) {
  mbLayers[layerIndex].strategies.splice(strategyIndex, 1);
  if (!mbLayers[layerIndex].strategies.length) {
    const options = mbGetClassStrategyOptions(layerIndex);
    if (options.length) mbLayers[layerIndex].strategies = [options[0]];
  }
  mbRenderLayers();
  mbUpdateOutput();
}

function mbRemoveFilter(index) {
  mbFilters.splice(index, 1);
  mbRenderFilters();
}

function mbSetFilterToken(index, value) {
  const match = CHAT_FILTER_OPTIONS.find(function(option) { return option.token === value; });
  if (!match) return;
  mbFilters[index].token = match.token;
  mbFilters[index].needsValue = !!match.needsValue;
  mbFilters[index].placeholder = match.placeholder || '';
  if (!match.needsValue) mbFilters[index].value = '';
  mbRenderFilters();
}

function mbSetFilterValue(index, value) {
  mbFilters[index].value = value;
  mbUpdateOutput();
}

function mbBuildFilterSelect(index, selectedToken) {
  let html = '<select class="csb-select" data-action="mb-set-filter-token" data-filter-index="' + index + '">';
  let currentGroup = '';
  CHAT_FILTER_OPTIONS.forEach(function(option) {
    if (option.group !== currentGroup) {
      if (currentGroup) html += '</optgroup>';
      currentGroup = option.group;
      html += '<optgroup label="' + option.group + '">';
    }
    html += '<option value="' + option.token + '"' + (option.token === selectedToken ? ' selected' : '') + '>' + option.label + '</option>';
  });
  if (currentGroup) html += '</optgroup>';
  html += '</select>';
  return html;
}

function mbRenderFilters() {
  const container = document.getElementById('mb-filters');
  if (!container) return;
  let html = '';
  mbFilters.forEach(function(filter, index) {
    html += '<div class="mb-filter-row">';
    html += mbBuildFilterSelect(index, filter.token);
    if (filter.needsValue) {
      html += '<input class="csb-input" type="text" placeholder="' + (filter.placeholder || 'value') + '" value="' + (filter.value || '').replace(/"/g, '&quot;') + '" data-action="mb-set-filter-value" data-filter-index="' + index + '">';
    } else {
      html += '<span class="mb-empty-value">No value needed</span>';
    }
    html += '<button type="button" class="csb-btn csb-btn-del" data-action="mb-remove-filter" data-filter-index="' + index + '">Remove</button>';
    html += '</div>';
  });
  if (!mbFilters.length) {
    html = '<div class="cff-note">No filters added yet. You can still build a direct whisper command, or add filters to narrow the response pool.</div>';
  }
  container.innerHTML = html;
  mbUpdateOutput();
}

function mbBuildFilterText(filter) {
  const token = (filter.token || '').trim();
  const value = (filter.value || '').trim();
  if (!token) return '';
  if (!filter.needsValue) return token;
  if (token === '@') return value ? '@' + value : '';
  return value ? token + value : '';
}

function mbBuildLayerText(layer) {
  const preset = mbGetPresetByKey(layer.presetKey || '');
  const selectedOption = (layer.optionValue || '').trim();
  const rawValue = (layer.value || '').trim();
  if (!preset) return '';
  const mode = preset.mode || (preset.needsValue ? 'value' : 'direct');
  let commandText = '';
  if (mode === 'direct') commandText = preset.command;
  if (mode === 'value') commandText = rawValue ? (preset.command + ' ' + rawValue) : '';
  if (mode === 'custom') commandText = rawValue;
  if (mode === 'class_strategies') {
    const picks = (layer.strategies || []).filter(function(selection) { return !!selection; });
    commandText = (selectedOption && picks.length) ? (selectedOption + ' ' + picks.join(',')) : '';
  }
  if (mode === 'options') {
    if (!selectedOption) commandText = '';
    else if (selectedOption === '__custom__') commandText = rawValue;
    else commandText = selectedOption;
  }
  if (!commandText) return '';
  return ((layer.targetFilter || '').trim() ? ((layer.targetFilter || '').trim() + ' ') : '') + commandText;
}

function mbUpdateOutput() {
  const target = (((document.getElementById('mb-target') || {}).value) || '').trim();
  const delivery = mbGetDeliveryMode();
  const deliveryPrefix = mbGetDeliveryPrefix();
  const layerTexts = mbLayers.map(mbBuildLayerText);
  const commandText = layerTexts.filter(function(text) { return !!text; }).join(' ');
  const builtFilters = mbFilters.map(mbBuildFilterText);
  const validFilters = builtFilters.filter(function(text) { return !!text; });
  const hasMissingFilterValue = mbFilters.some(function(filter, index) {
    return filter.needsValue && !builtFilters[index];
  });

  const status = document.getElementById('mb-status');
  const macroOutput = document.getElementById('mb-out-macro');
  const commandOutput = document.getElementById('mb-out-command');

  let statusText = 'Ready to build.';
  let preview = '';
  let macro = '';
  const requiresTarget = delivery === 'whisper';

  if ((!target && requiresTarget) && !commandText) {
      statusText = 'Enter a bot target for whisper mode and build at least one command layer.';
    } else if (!target && requiresTarget) {
      statusText = 'Enter a bot target to produce a valid whisper macro.';
    } else if (!commandText) {
      statusText = 'Choose presets and fill any required layer values.';
  } else if (hasMissingFilterValue) {
    statusText = 'Fill in all value-based filters before copying the macro.';
  } else {
    preview = validFilters.concat([commandText]).join(' ');
    macro = requiresTarget ? (deliveryPrefix + ' ' + target + ' ' + preview) : (deliveryPrefix + ' ' + preview);
    statusText = requiresTarget ? 'Whisper macro is valid and ready to copy.' : 'Macro is valid and ready to copy.';
  }

  if (status) status.textContent = statusText;
  if (macroOutput) macroOutput.textContent = macro || '(complete the required fields to generate a macro)';
  if (commandOutput) commandOutput.textContent = preview || '(filters and command preview will appear here)';
}

function initializeBotcommandsRouteEvents() {
  const root = document.querySelector('.botcommands-page');
  if (!root) return;

  root.addEventListener('click', function(event) {
    const actionNode = event.target.closest('[data-action], [data-tab-target], [data-copy-target]');
    if (!actionNode || !root.contains(actionNode)) return;

    if (actionNode.hasAttribute('data-tab-target')) {
      srefTab(actionNode, actionNode.getAttribute('data-tab-target'));
      return;
    }

    if (actionNode.hasAttribute('data-copy-target')) {
      csbCopy(actionNode.getAttribute('data-copy-target'));
      return;
    }

    const action = actionNode.getAttribute('data-action') || '';
    const lineIndex = Number(actionNode.getAttribute('data-line-index'));
    const actionIndex = Number(actionNode.getAttribute('data-action-index'));
    const layerIndex = Number(actionNode.getAttribute('data-layer-index'));
    const strategyIndex = Number(actionNode.getAttribute('data-strategy-index'));
    const filterIndex = Number(actionNode.getAttribute('data-filter-index'));

    if (action === 'reset-bot-filters') resetBotCommandFilters();
    else if (action === 'reset-gm-filters') resetGmCommandFilters();
    else if (action === 'bot-chip-filter') applyBotChipFilter(actionNode.getAttribute('data-filter-kind') || '', actionNode.getAttribute('data-filter-value') || '');
    else if (action === 'gm-chip-filter') applyGmChipFilter(actionNode.getAttribute('data-filter-kind') || '', actionNode.getAttribute('data-filter-value') || '');
    else if (action === 'csb-add-line') csbAddLine();
    else if (action === 'csb-remove-line') csbRemoveLine(lineIndex);
    else if (action === 'csb-add-action') csbAddAction(lineIndex);
    else if (action === 'csb-remove-action') csbRemoveAction(lineIndex, actionIndex);
    else if (action === 'mb-add-quick-filter') mbAddQuickFilter(actionNode.getAttribute('data-filter-token') || '');
    else if (action === 'mb-add-filter') mbAddFilter();
    else if (action === 'mb-remove-filter') mbRemoveFilter(filterIndex);
    else if (action === 'mb-add-layer') mbAddLayer();
    else if (action === 'mb-remove-layer') mbRemoveLayer(layerIndex);
    else if (action === 'mb-add-layer-strategy') mbAddLayerStrategy(layerIndex);
    else if (action === 'mb-remove-layer-strategy') mbRemoveLayerStrategy(layerIndex, strategyIndex);
  });

  root.addEventListener('input', function(event) {
    const input = event.target;
    if (!(input instanceof HTMLElement)) return;

    const action = input.getAttribute('data-action') || '';
    const lineIndex = Number(input.getAttribute('data-line-index'));
    const actionIndex = Number(input.getAttribute('data-action-index'));
    const layerIndex = Number(input.getAttribute('data-layer-index'));
    const filterIndex = Number(input.getAttribute('data-filter-index'));

    if (input.getAttribute('data-filter-input') === 'bot') filterBotCommandCards();
    else if (input.getAttribute('data-filter-input') === 'gm') filterGmCommandCards();
    else if (input.getAttribute('data-filter-input') === 'chat') filterChatFilterCards();
    else if (action === 'csb-update-output') csbUpdateOutput();
    else if (action === 'csb-set-action-qualifier') csbSetAction(lineIndex, actionIndex, 'qualifier', input.value);
    else if (action === 'csb-set-action-priority') csbSetAction(lineIndex, actionIndex, 'priority', input.value);
    else if (action === 'mb-update-output') mbUpdateOutput();
    else if (action === 'mb-set-layer-value') mbSetLayerValue(layerIndex, input.value);
    else if (action === 'mb-set-filter-value') mbSetFilterValue(filterIndex, input.value);
  });

  root.addEventListener('change', function(event) {
    const input = event.target;
    if (!(input instanceof HTMLElement)) return;

    const action = input.getAttribute('data-action') || '';
    const lineIndex = Number(input.getAttribute('data-line-index'));
    const actionIndex = Number(input.getAttribute('data-action-index'));
    const layerIndex = Number(input.getAttribute('data-layer-index'));
    const strategyIndex = Number(input.getAttribute('data-strategy-index'));
    const filterIndex = Number(input.getAttribute('data-filter-index'));
    const value = 'value' in input ? input.value : '';

    if (input.getAttribute('data-filter-select') === 'bot') filterBotCommandCards();
    else if (input.getAttribute('data-filter-select') === 'gm') filterGmCommandCards();
    else if (action === 'csb-toggle-guid') csbToggleGuid();
    else if (action === 'csb-set-trigger') csbSet(lineIndex, 'trigger', value);
    else if (action === 'csb-set-action-type') csbSetAction(lineIndex, actionIndex, 'type', value);
    else if (action === 'mb-update-delivery') mbUpdateDeliveryMode();
    else if (action === 'mb-set-layer-target') mbSetLayerTargetFilter(layerIndex, value);
    else if (action === 'mb-set-layer-preset') mbSetLayerPreset(layerIndex, value);
    else if (action === 'mb-set-layer-option') mbSetLayerOption(layerIndex, value);
    else if (action === 'mb-set-layer-strategy') mbSetLayerStrategy(layerIndex, strategyIndex, value);
    else if (action === 'mb-set-filter-token') mbSetFilterToken(filterIndex, value);
  });
}

document.addEventListener('DOMContentLoaded', function() {
  loadBotcommandsClientState();
  initializeBotcommandsRouteEvents();
  mbUpdateDeliveryMode();
  csbInit();
  mbInit();
  filterBotCommandCards();
  filterGmCommandCards();
  filterChatFilterCards();
});
