(function () {
  function normalizeStrategyValue(value) {
    return String(value || '')
      .replace(/\r\n?/g, '\n')
      .trim()
      .replace(/\s*\n\s*/g, '');
  }

  function parseStrategyTokens(value) {
    var normalized = normalizeStrategyValue(value);
    if (!normalized) {
      return [];
    }

    return normalized.split(',').map(function (token) {
      return token.trim();
    }).filter(function (token) {
      return token !== '';
    });
  }

  function strategyTokenKey(token) {
    var normalized = String(token || '').trim();
    if (!normalized) {
      return '';
    }

    var prefix = normalized.charAt(0);
    if (prefix === '+' || prefix === '-' || prefix === '~') {
      normalized = normalized.slice(1);
    }

    return normalized.trim().toLowerCase();
  }

  function mergeStrategyValue(currentValue, deltaValue) {
    var merged = {};
    var order = [];

    parseStrategyTokens(currentValue).forEach(function (token) {
      var key = strategyTokenKey(token);
      if (!key) {
        return;
      }
      if (!Object.prototype.hasOwnProperty.call(merged, key)) {
        order.push(key);
      }
      merged[key] = token;
    });

    parseStrategyTokens(deltaValue).forEach(function (token) {
      var key = strategyTokenKey(token);
      if (!key) {
        return;
      }
      if (!Object.prototype.hasOwnProperty.call(merged, key)) {
        order.push(key);
      }
      merged[key] = token;
    });

    return order.map(function (key) {
      return merged[key] || '';
    }).filter(function (token) {
      return token.trim() !== '';
    }).join(',');
  }

  function toggleStrategyToken(textareaId, token, mode) {
    var textarea = document.getElementById(textareaId);
    if (!(textarea instanceof HTMLTextAreaElement)) {
      return;
    }

    var normalizedToken = String(token || '').trim();
    if (!normalizedToken) {
      return;
    }

    var prefix = mode === 'minus' ? '-' : (mode === 'tilde' ? '~' : '+');
    var target = prefix + normalizedToken;
    var targetKey = normalizedToken.toLowerCase();
    var tokens = parseStrategyTokens(textarea.value || '');
    var next = [];
    var foundExact = false;

    tokens.forEach(function (entry) {
      var value = String(entry || '').trim();
      if (!value) {
        return;
      }

      var valuePrefix = value.charAt(0);
      var bare = value;
      if (valuePrefix === '+' || valuePrefix === '-' || valuePrefix === '~') {
        bare = value.slice(1).trim();
      } else {
        bare = value.trim();
      }

      if (bare.toLowerCase() !== targetKey) {
        next.push(value);
        return;
      }

      if (value === target) {
        foundExact = true;
      }
    });

    if (!foundExact) {
      next.push(target);
    }

    textarea.value = next.join(',');
  }

  function parseProfiles(shell, attributeName) {
    if (!(shell instanceof HTMLElement)) {
      return {};
    }

    var raw = shell.getAttribute(attributeName) || '{}';
    try {
      return JSON.parse(raw);
    } catch (error) {
      return {};
    }
  }

  function applyStrategyProfile(shell, scope, profileKey) {
    var profiles = scope === 'bot'
      ? parseProfiles(shell, 'data-bot-strategy-profiles')
      : parseProfiles(shell, 'data-guild-strategy-profiles');

    if (!profiles[profileKey] || profileKey === 'custom') {
      return;
    }

    var profile = profiles[profileKey];
    var prefix = scope === 'bot' ? 'playerbots-bot-strategy-' : 'playerbots-guild-strategy-';
    var fields = {
      co: document.getElementById(prefix + 'co'),
      nc: document.getElementById(prefix + 'nc'),
      dead: document.getElementById(prefix + 'dead'),
      react: document.getElementById(prefix + 'react')
    };

    Object.keys(fields).forEach(function (strategyKey) {
      var field = fields[strategyKey];
      if (!(field instanceof HTMLTextAreaElement)) {
        return;
      }

      if (scope === 'bot') {
        field.value = mergeStrategyValue(field.value || '', profile[strategyKey] || '');
        return;
      }

      field.value = profile[strategyKey] || '';
    });
  }

  function buildNavigationUrl(shell, select) {
    if (!(shell instanceof HTMLElement) || !(select instanceof HTMLSelectElement)) {
      return '';
    }

    var baseUrl = shell.getAttribute('data-playerbots-base-url') || 'index.php?n=admin&sub=playerbots';
    var url = new URL(baseUrl, window.location.href);
    var navType = select.getAttribute('data-playerbots-nav') || '';

    if (navType === 'realm') {
      url.searchParams.set('realm', select.value);
      url.searchParams.delete('guildid');
      url.searchParams.delete('character_guid');
      return url.toString();
    }

    var realmId = select.getAttribute('data-playerbots-realm-id') || '';
    if (realmId) {
      url.searchParams.set('realm', realmId);
    }

    if (navType === 'guild') {
      url.searchParams.set('guildid', select.value);
      url.searchParams.delete('character_guid');
      return url.toString();
    }

    if (navType === 'character') {
      var guildId = select.getAttribute('data-playerbots-guild-id') || '';
      if (guildId) {
        url.searchParams.set('guildid', guildId);
      }
      url.searchParams.set('character_guid', select.value);
      return url.toString();
    }

    return '';
  }

  document.addEventListener('change', function (event) {
    var target = event.target;
    if (!(target instanceof HTMLElement)) {
      return;
    }

    var shell = target.closest('.playerbots-shell');
    if (!(shell instanceof HTMLElement)) {
      return;
    }

    if (target instanceof HTMLSelectElement && target.hasAttribute('data-playerbots-nav')) {
      var nextUrl = buildNavigationUrl(shell, target);
      if (nextUrl) {
        window.location.href = nextUrl;
      }
      return;
    }

    if (target instanceof HTMLSelectElement && target.hasAttribute('data-strategy-profile')) {
      applyStrategyProfile(shell, target.getAttribute('data-strategy-profile') || '', target.value);
    }
  });

  document.addEventListener('click', function (event) {
    var target = event.target;
    if (!(target instanceof HTMLElement)) {
      return;
    }

    var chip = target.closest('[data-strategy-target]');
    if (!(chip instanceof HTMLElement)) {
      return;
    }

    event.preventDefault();
    toggleStrategyToken(
      chip.getAttribute('data-strategy-target') || '',
      chip.getAttribute('data-strategy-token') || '',
      chip.getAttribute('data-strategy-mode') || 'plus'
    );
  });
})();
