document.addEventListener('DOMContentLoaded', function () {
  var root = document.querySelector('.backup-admin[data-backup-lookup-url]');
  if (!root) {
    return;
  }

  var endpoint = root.getAttribute('data-backup-lookup-url') || '';
  if (endpoint === '') {
    return;
  }

  function populateSelect(select, items, valueKey, labelBuilder, selectedValue, emptyLabel) {
    if (!select) return;
    select.innerHTML = '';

    if (!Array.isArray(items) || items.length === 0) {
      var emptyOption = document.createElement('option');
      emptyOption.value = '0';
      emptyOption.textContent = emptyLabel;
      select.appendChild(emptyOption);
      return;
    }

    items.forEach(function (item, index) {
      var option = document.createElement('option');
      option.value = String(item[valueKey] || 0);
      option.textContent = labelBuilder(item);
      if (String(selectedValue || '') !== '') {
        option.selected = String(selectedValue) === option.value;
      } else if (index === 0) {
        option.selected = true;
      }
      select.appendChild(option);
    });
  }

  function setSelectValue(select, value, fallbackValue) {
    if (!select) return;

    var nextValue = value;
    if (nextValue === undefined || nextValue === null || nextValue === '') {
      nextValue = fallbackValue;
    }

    select.value = String(nextValue);
  }

  function fetchOptions(params) {
    var separator = endpoint.indexOf('?') === -1 ? '?' : '&';
    var url = endpoint + separator + new URLSearchParams(params).toString();
    return fetch(url, {
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (response) {
      if (!response.ok) {
        throw new Error('Lookup failed');
      }
      return response.json();
    });
  }

  function bindSelectRefresh(select, handler) {
    if (!select) {
      return;
    }

    select.addEventListener('change', handler);
    select.addEventListener('input', handler);
  }

  function buildGuildHint(summary, selectedGuildId, vmangosTransformRoute) {
    if (String(selectedGuildId || '0') === '0') {
      return 'No guild selected.';
    }

    summary = summary || {};
    var text = 'Guild members: ' + Number(summary.member_count || 0)
      + ' | Owning accounts: ' + Number(summary.account_count || 0)
      + ' | Mix: ' + Number(summary.human_account_count || 0) + ' human / ' + Number(summary.bot_account_count || 0) + ' bot';
    if (vmangosTransformRoute) {
      text += ' | Target accounts: ' + Number(summary.create_count || 0) + ' create / ' + Number(summary.reuse_count || 0) + ' reuse';
    }
    return text;
  }

  function bindBackupForm() {
    var entityType = document.getElementById('backup_entity_type');
    var sourceRealm = document.getElementById('backup_source_realm_id');
    var sourceAccount = document.getElementById('backup_source_account_id');
    var sourceCharacter = document.getElementById('backup_source_character_guid');
    var sourceGuild = document.getElementById('backup_source_guild_id');
    var submit = document.getElementById('backup_submit');
    var characterHint = document.getElementById('backup_character_hint');
    var accountHint = document.getElementById('backup_account_hint');
    var refreshToken = 0;

    if (!entityType || !sourceRealm || !sourceAccount || !sourceCharacter || !sourceGuild || !submit) return;

    function applyVisibility() {
      var selectedEntity = entityType.value || 'character';
      document.querySelectorAll('.backup-field').forEach(function (row) {
        var allowed = (row.getAttribute('data-entities') || '').split(',');
        var shouldShow = allowed.indexOf(selectedEntity) !== -1;
        row.classList.toggle('is-hidden', !shouldShow);
      });
    }

    function updateSubmitState() {
      var selectedEntity = entityType.value || 'character';
      var shouldDisable = false;
      var selectedAccountId = String(sourceAccount.value || '0');
      var selectedAccountLabel = '';
      if (sourceAccount.selectedIndex >= 0 && sourceAccount.options[sourceAccount.selectedIndex]) {
        selectedAccountLabel = sourceAccount.options[sourceAccount.selectedIndex].textContent || '';
      }
      var characterCount = 0;
      if (sourceCharacter.options.length === 1 && String(sourceCharacter.value || '0') === '0') {
        characterCount = 0;
      } else {
        characterCount = sourceCharacter.options.length;
      }

      if (accountHint) {
        accountHint.textContent = selectedAccountId !== '0'
          ? ('Selected account ' + selectedAccountLabel + ' | Characters found: ' + characterCount)
          : 'No source account selected.';
      }

      if (selectedEntity === 'character') {
        shouldDisable = !sourceCharacter.options.length || String(sourceCharacter.value || '0') === '0';
        if (characterHint) {
          characterHint.textContent = shouldDisable
            ? 'Choose another account or switch entity type.'
            : 'Select one character to export.';
        }
      } else if (selectedEntity === 'account') {
        shouldDisable = !sourceAccount.options.length || String(sourceAccount.value || '0') === '0';
      } else if (selectedEntity === 'guild') {
        shouldDisable = !sourceGuild.options.length || String(sourceGuild.value || '0') === '0';
      }

      submit.disabled = shouldDisable;
    }

    function refresh() {
      var requestToken = ++refreshToken;
      fetchOptions({
        backup_entity_type: entityType.value,
        source_realm_id: sourceRealm.value,
        source_account_id: sourceAccount.value,
        source_character_guid: sourceCharacter.value,
        source_guild_id: sourceGuild.value
      }).then(function (data) {
        if (requestToken !== refreshToken) {
          return;
        }

        populateSelect(sourceAccount, data.source_account_options, 'id', function (item) {
          return '#' + item.id + ' - ' + item.username;
        }, data.selected_account_id, 'No accounts found on this realm');

        populateSelect(sourceCharacter, data.source_character_options, 'guid', function (item) {
          return item.name + ' (Lvl ' + item.level + ')';
        }, data.selected_character_guid, 'No characters on this account');

        populateSelect(sourceGuild, data.source_guild_options, 'guildid', function (item) {
          return item.name + (item.leader_name ? ' (Leader: ' + item.leader_name + ')' : '');
        }, data.selected_guild_id, 'No guilds found on this realm');

        setSelectValue(sourceAccount, data.selected_account_id, '0');
        setSelectValue(sourceCharacter, data.selected_character_guid, '0');
        setSelectValue(sourceGuild, data.selected_guild_id, '0');

        if (accountHint) {
          var selectedUsername = String(data.selected_account_username || '');
          accountHint.textContent = data.selected_account_id > 0
            ? ('Selected account #' + data.selected_account_id + (selectedUsername !== '' ? ' (' + selectedUsername + ')' : '') + ' | Characters found: ' + Number(data.source_character_count || 0))
            : 'No source account selected.';
        }

        applyVisibility();
        updateSubmitState();
      }).catch(function (error) {
        if (accountHint) {
          accountHint.textContent = 'Refresh failed: ' + (error && error.message ? error.message : 'Lookup failed');
        }
      });
    }

    bindSelectRefresh(entityType, function () {
      applyVisibility();
      refresh();
    });
    bindSelectRefresh(sourceRealm, refresh);
    bindSelectRefresh(sourceAccount, refresh);
    applyVisibility();
    updateSubmitState();
    refresh();
  }

  function bindXferForm() {
    var entityType = document.getElementById('xfer_entity_type');
    var xferRoute = document.getElementById('xfer_route');
    var sourceAccount = document.getElementById('xfer_source_account_id');
    var sourceCharacter = document.getElementById('xfer_source_character_guid');
    var sourceGuild = document.getElementById('xfer_source_guild_id');
    var targetAccount = document.getElementById('xfer_target_account_id');
    var routeHelp = document.getElementById('xfer_route_help');
    var submit = document.getElementById('xfer_submit');
    var accountHint = document.getElementById('xfer_account_hint');
    var characterHint = document.getElementById('xfer_character_hint');
    var guildHint = document.getElementById('xfer_guild_hint');
    var targetAccountField = document.querySelector('.xfer-field--target-account');
    var targetNameField = document.querySelector('.xfer-field--target-name');
    var refreshToken = 0;
    var lastLookup = null;

    if (!entityType || !xferRoute || !sourceAccount || !sourceCharacter || !sourceGuild || !targetAccount || !submit) return;

    function getSelectedRoute(data) {
      var routeId = String((data && data.selected_xfer_route_id) || xferRoute.value || '');
      var routeOptions = (data && data.xfer_route_options) || (lastLookup && lastLookup.xfer_route_options) || [];
      for (var i = 0; i < routeOptions.length; i += 1) {
        if (String(routeOptions[i].id) === routeId) {
          return routeOptions[i];
        }
      }
      return null;
    }

    function isVmangosTransformRoute(data) {
      var route = getSelectedRoute(data);
      return !!(route && route.target_is_vmangos && !route.source_is_vmangos);
    }

    function applyVisibility(data) {
      var selectedEntity = entityType.value || 'character';
      document.querySelectorAll('.xfer-field').forEach(function (row) {
        var allowed = (row.getAttribute('data-entities') || '').split(',');
        var shouldShow = allowed.indexOf(selectedEntity) !== -1;
        row.classList.toggle('is-hidden', !shouldShow);
      });

      var hideCharacterTargeting = selectedEntity !== 'character' || isVmangosTransformRoute(data);
      if (targetAccountField) {
        targetAccountField.classList.toggle('is-hidden', hideCharacterTargeting);
      }
      if (targetNameField) {
        targetNameField.classList.toggle('is-hidden', hideCharacterTargeting);
      }
    }

    function updateSubmitState(data) {
      var selectedEntity = entityType.value || 'character';
      var shouldDisable = false;
      var selectedAccountId = String(sourceAccount.value || '0');
      var selectedAccountLabel = '';
      var vmangosTransformRoute = isVmangosTransformRoute(data);
      if (sourceAccount.selectedIndex >= 0 && sourceAccount.options[sourceAccount.selectedIndex]) {
        selectedAccountLabel = sourceAccount.options[sourceAccount.selectedIndex].textContent || '';
      }
      var characterCount = 0;
      if (sourceCharacter.options.length === 1 && String(sourceCharacter.value || '0') === '0') {
        characterCount = 0;
      } else {
        characterCount = sourceCharacter.options.length;
      }

      if (accountHint) {
        accountHint.textContent = selectedAccountId !== '0'
          ? ('Selected account ' + selectedAccountLabel + ' | Characters found: ' + characterCount)
          : 'No source account selected.';
      }
      if (guildHint) {
        var summary = (data && data.selected_guild_summary) || (lastLookup && lastLookup.selected_guild_summary) || {};
        guildHint.textContent = buildGuildHint(summary, sourceGuild.value, vmangosTransformRoute);
      }

      if (selectedEntity === 'character') {
        shouldDisable = !sourceCharacter.options.length
          || String(sourceCharacter.value || '0') === '0'
          || (!vmangosTransformRoute && (!targetAccount.options.length || String(targetAccount.value || '0') === '0'));
        if (characterHint) {
          characterHint.textContent = shouldDisable
            ? 'Choose another account or switch transfer type.'
            : 'Select one character to export for the target realm.';
        }
      } else if (selectedEntity === 'account') {
        shouldDisable = !sourceAccount.options.length || String(sourceAccount.value || '0') === '0';
      } else if (selectedEntity === 'guild') {
        shouldDisable = !sourceGuild.options.length || String(sourceGuild.value || '0') === '0';
      }

      submit.disabled = shouldDisable;
    }

    function refresh() {
      var requestToken = ++refreshToken;
      fetchOptions({
        xfer_route: xferRoute.value,
        xfer_entity_type: entityType.value,
        source_account_id: sourceAccount.value,
        source_character_guid: sourceCharacter.value,
        source_guild_id: sourceGuild.value,
        target_account_id: targetAccount.value
      }).then(function (data) {
        if (requestToken !== refreshToken) {
          return;
        }
        lastLookup = data;

        populateSelect(xferRoute, data.xfer_route_options, 'id', function (item) {
          return item.label;
        }, data.selected_xfer_route_id, 'No transfer routes available');

        populateSelect(entityType, Object.keys(data.xfer_entity_options || {}).map(function (key) {
          return { key: key, label: data.xfer_entity_options[key] };
        }), 'key', function (item) {
          return item.label;
        }, data.selected_xfer_entity_type, 'No transfer types available');

        populateSelect(sourceAccount, data.source_account_options, 'id', function (item) {
          return '#' + item.id + ' - ' + item.username;
        }, data.selected_account_id, 'No accounts found on this realm');

        populateSelect(sourceCharacter, data.source_character_options, 'guid', function (item) {
          return item.name + ' (Lvl ' + item.level + ')';
        }, data.selected_character_guid, 'No characters on this account');

        populateSelect(sourceGuild, data.source_guild_options, 'guildid', function (item) {
          return item.name + (item.leader_name ? ' (Leader: ' + item.leader_name + ')' : '');
        }, data.selected_guild_id, 'No guilds found on this realm');

        populateSelect(targetAccount, data.target_account_options, 'id', function (item) {
          return '#' + item.id + ' - ' + item.username;
        }, data.selected_target_account_id, 'No accounts found on target realm');

        setSelectValue(xferRoute, data.selected_xfer_route_id, xferRoute.value || '');
        setSelectValue(entityType, data.selected_xfer_entity_type, 'character');
        setSelectValue(sourceAccount, data.selected_account_id, '0');
        setSelectValue(sourceCharacter, data.selected_character_guid, '0');
        setSelectValue(sourceGuild, data.selected_guild_id, '0');
        setSelectValue(targetAccount, data.selected_target_account_id, '0');

        if (routeHelp && typeof data.xfer_route_help === 'string') {
          routeHelp.textContent = data.xfer_route_help;
        }

        if (accountHint) {
          var selectedUsername = String(data.selected_account_username || '');
          accountHint.textContent = data.selected_account_id > 0
            ? ('Selected account #' + data.selected_account_id + (selectedUsername !== '' ? ' (' + selectedUsername + ')' : '') + ' | Characters found: ' + Number(data.source_character_count || 0))
            : 'No source account selected.';
        }
        if (guildHint) {
          var guildSummary = data.selected_guild_summary || {};
          guildHint.textContent = buildGuildHint(guildSummary, data.selected_guild_id, isVmangosTransformRoute(data));
        }

        applyVisibility(data);
        updateSubmitState(data);
      }).catch(function (error) {
        if (guildHint) {
          guildHint.textContent = 'Refresh failed: ' + (error && error.message ? error.message : 'Lookup failed');
        }
      });
    }

    bindSelectRefresh(entityType, function () {
      applyVisibility(lastLookup);
      refresh();
    });
    bindSelectRefresh(xferRoute, refresh);
    bindSelectRefresh(sourceAccount, refresh);
    bindSelectRefresh(sourceCharacter, function () {
      updateSubmitState(lastLookup);
    });
    bindSelectRefresh(sourceGuild, refresh);
    bindSelectRefresh(targetAccount, function () {
      updateSubmitState(lastLookup);
    });
    applyVisibility(lastLookup);
    updateSubmitState(lastLookup);
    refresh();
  }

  bindBackupForm();
  bindXferForm();
});
