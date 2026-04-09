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

  function fetchOptions(params) {
    var url = endpoint + '?' + new URLSearchParams(params).toString();
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

  function bindBackupForm() {
    var sourceRealm = document.getElementById('backup_source_realm_id');
    var sourceAccount = document.getElementById('backup_source_account_id');
    var sourceCharacter = document.getElementById('backup_source_character_guid');
    var sourceGuild = document.getElementById('backup_source_guild_id');

    if (!sourceRealm || !sourceAccount || !sourceCharacter || !sourceGuild) return;

    function refresh() {
      fetchOptions({
        source_realm_id: sourceRealm.value,
        source_account_id: sourceAccount.value,
        source_character_guid: sourceCharacter.value,
        source_guild_id: sourceGuild.value
      }).then(function (data) {
        populateSelect(sourceAccount, data.source_account_options, 'id', function (item) {
          return '#' + item.id + ' - ' + item.username;
        }, data.selected_account_id, 'No accounts found on this realm');

        populateSelect(sourceCharacter, data.source_character_options, 'guid', function (item) {
          return item.name + ' (Lvl ' + item.level + ')';
        }, data.selected_character_guid, 'No characters on this account');

        populateSelect(sourceGuild, data.source_guild_options, 'guildid', function (item) {
          return item.name + (item.leader_name ? ' (Leader: ' + item.leader_name + ')' : '');
        }, data.selected_guild_id, 'No guilds found on this realm');
      }).catch(function () {});
    }

    sourceRealm.addEventListener('change', refresh);
    sourceAccount.addEventListener('change', refresh);
  }

  function bindXferForm() {
    var entityType = document.getElementById('xfer_entity_type');
    var xferRoute = document.getElementById('xfer_route');
    var sourceAccount = document.getElementById('xfer_source_account_id');
    var sourceCharacter = document.getElementById('xfer_source_character_guid');
    var sourceGuild = document.getElementById('xfer_source_guild_id');
    var targetAccount = document.getElementById('xfer_target_account_id');
    var routeHelp = document.getElementById('xfer_route_help');

    if (!entityType || !xferRoute || !sourceAccount || !sourceCharacter || !sourceGuild || !targetAccount) return;

    function applyVisibility() {
      var selectedEntity = entityType.value || 'character';
      document.querySelectorAll('.xfer-field').forEach(function (row) {
        var allowed = (row.getAttribute('data-entities') || '').split(',');
        var shouldShow = allowed.indexOf(selectedEntity) !== -1;
        row.classList.toggle('is-hidden', !shouldShow);
      });
    }

    function refresh() {
      fetchOptions({
        xfer_route: xferRoute.value,
        xfer_entity_type: entityType.value,
        source_account_id: sourceAccount.value,
        source_character_guid: sourceCharacter.value,
        source_guild_id: sourceGuild.value,
        target_account_id: targetAccount.value
      }).then(function (data) {
        populateSelect(xferRoute, data.xfer_route_options, 'id', function (item) {
          return item.label;
        }, data.selected_xfer_route_id, 'No transfer routes available');

        populateSelect(entityType, Object.keys(data.xfer_entity_options || {}).map(function (key) {
          return { key: key, label: data.xfer_entity_options[key] };
        }), 'key', function (item) {
          return item.label;
        }, entityType.value, 'No transfer types available');

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

        if (routeHelp && typeof data.xfer_route_help === 'string' && data.xfer_route_help !== '') {
          routeHelp.textContent = data.xfer_route_help;
        }

        applyVisibility();
      }).catch(function () {});
    }

    entityType.addEventListener('change', function () {
      applyVisibility();
      refresh();
    });
    xferRoute.addEventListener('change', refresh);
    sourceAccount.addEventListener('change', refresh);
    applyVisibility();
  }

  bindBackupForm();
  bindXferForm();
});
