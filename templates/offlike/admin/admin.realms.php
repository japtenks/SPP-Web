<br>
<?php builddiv_start(1, $lang['realms'] ?? 'Realms') ?>
<div class="realm-admin feature-shell">
<?php
$realmConfirmLabel = (string)($lang['sure_q'] ?? 'Are you sure?');
$runtimeWarnings = array_values((array)($runtime_warnings ?? array()));
$schemaScan = (array)($schema_scan ?? array());
$schemaWarnings = array_values((array)($schemaScan['warnings'] ?? array()));
$slotErrors = array_values((array)($slot_errors ?? array()));
$slotFormState = !empty($slot_form) ? (array)$slot_form : array(
  'id' => '',
  'name' => '',
  'address' => '',
  'port' => 8085,
  'realmd' => '',
  'world' => '',
  'chars' => '',
  'armory' => '',
  'bots' => '',
  'icon' => 0,
  'realmflags' => 0,
  'timezone' => 1,
  'allowedSecurityLevel' => 0,
  'population' => '0',
  'realmbuilds' => '',
  'enabled' => 1,
  'make_default' => 0,
);
?>
<?php if ($view_mode === 'list') { ?>
  <div class="realm-admin__intro feature-hero">
    <strong>Runtime Realm Management</strong><br>
    Runtime realm slots now come from website settings first, with file config slots shown as labeled fallbacks until they are migrated.
    Matching <code>realmlist</code> rows are synchronized here, while <code>realmd_db_version</code> remains read-only launcher/bootstrap diagnostics.
  </div>

  <?php if (!empty($runtimeWarnings) || !empty($schemaWarnings) || !empty($runtime_errors) || !empty($slotErrors)) { ?>
    <div class="realm-admin__card feature-panel">
      <h3>Warnings</h3>
      <?php if (!empty($runtime_errors)) { ?>
        <div class="realm-admin__advanced">
          <p class="realm-admin__advanced-title">Runtime Settings</p>
          <ul>
            <?php foreach ((array)$runtime_errors as $runtimeError) { ?>
              <li><?php echo htmlspecialchars((string)$runtimeError); ?></li>
            <?php } ?>
          </ul>
        </div>
      <?php } ?>
      <?php if (!empty($slotErrors)) { ?>
        <div class="realm-admin__advanced">
          <p class="realm-admin__advanced-title">Runtime Slot</p>
          <ul>
            <?php foreach ($slotErrors as $slotError) { ?>
              <li><?php echo htmlspecialchars((string)$slotError); ?></li>
            <?php } ?>
          </ul>
        </div>
      <?php } ?>
      <?php if (!empty($runtimeWarnings)) { ?>
        <div class="realm-admin__advanced">
          <p class="realm-admin__advanced-title">Runtime Diagnostics</p>
          <ul>
            <?php foreach ($runtimeWarnings as $runtimeWarning) { ?>
              <li><?php echo htmlspecialchars(is_array($runtimeWarning) ? trim((string)($runtimeWarning['label'] ?? 'Warning')) . ': ' . (string)($runtimeWarning['detail'] ?? '') : (string)$runtimeWarning); ?></li>
            <?php } ?>
          </ul>
        </div>
      <?php } ?>
      <?php if (!empty($schemaWarnings)) { ?>
        <div class="realm-admin__advanced">
          <p class="realm-admin__advanced-title">Read-Only Topology Diagnostics</p>
          <ul>
            <?php foreach ($schemaWarnings as $schemaWarning) { ?>
              <li><?php echo htmlspecialchars((string)$schemaWarning); ?></li>
            <?php } ?>
          </ul>
        </div>
      <?php } ?>
    </div>
  <?php } ?>

  <div class="realm-admin__card feature-panel">
    <h3>Runtime Realm Settings</h3>
    <p>These controls decide which runtime slots are enabled and which slot is the default.</p>
    <form action="index.php?n=admin&amp;sub=realms&amp;action=runtime-save" method="post">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($admin_realms_csrf_token ?? spp_csrf_token('admin_realms')); ?>">
      <div class="realm-admin__form-grid">
        <div class="realm-admin__field">
          <label>Multirealm</label>
          <input type="hidden" name="multirealm" value="0">
          <label class="realm-admin__toggle">
            <input type="checkbox" name="multirealm" value="1"<?php echo ((int)($runtime_form['multirealm'] ?? 0) === 1) ? ' checked' : ''; ?>>
            Enabled
          </label>
        </div>
        <div class="realm-admin__field">
          <label>Default Realm</label>
          <select name="default_realm_id">
            <?php foreach ((array)$runtime_realm_options as $realmOption) { ?>
              <option value="<?php echo (int)$realmOption['id']; ?>"<?php echo ((int)($runtime_form['default_realm_id'] ?? 0) === (int)$realmOption['id']) ? ' selected' : ''; ?>><?php echo htmlspecialchars((string)$realmOption['label']); ?></option>
            <?php } ?>
          </select>
        </div>
      </div>
      <div class="realm-admin__advanced">
        <p class="realm-admin__advanced-title">Enabled Runtime Slots</p>
        <?php if (!empty($runtime_realm_options)) { ?>
          <div class="realm-admin__form-grid">
            <?php foreach ((array)$runtime_realm_options as $realmOption) { ?>
              <div class="realm-admin__field">
                <label>
                  <input type="checkbox" name="enabled_realm_ids[]" value="<?php echo (int)$realmOption['id']; ?>"<?php echo in_array((int)$realmOption['id'], (array)($runtime_form['enabled_realm_ids'] ?? array()), true) ? ' checked' : ''; ?>>
                  <?php echo htmlspecialchars((string)$realmOption['label']); ?>
                </label>
                <div class="realm-admin__muted">
                  <?php echo !empty($realmOption['is_config_only']) ? 'Config-only fallback slot.' : 'DB-backed runtime slot.'; ?>
                  <?php if (empty($realmOption['has_realmlist_row'])) { ?>
                    No matching <code>realmlist</code> row is present yet.
                  <?php } ?>
                </div>
              </div>
            <?php } ?>
          </div>
        <?php } else { ?>
          <p class="realm-admin__muted">No runtime realm slots are currently available.</p>
        <?php } ?>
      </div>
      <div class="realm-admin__actions">
        <input class="realm-admin__button" type="submit" value="Save Runtime Settings">
      </div>
    </form>
  </div>

  <div class="realm-admin__card feature-panel">
    <h3>Runtime Realm Slots</h3>
    <p>DB-backed runtime slots are the active source of truth. Config-only fallback slots can be enabled temporarily or edited to migrate them into DB-backed definitions.</p>
    <div class="realm-admin__table-wrap">
      <table class="realm-admin__table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Source</th>
            <th>Realmd</th>
            <th>World</th>
            <th>Chars</th>
            <th>Address</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ((array)$items as $item) { ?>
          <tr>
            <td><?php echo (int)$item['id']; ?></td>
            <td><a class="realm-admin__name" href="index.php?n=admin&amp;sub=realms&amp;action=edit&amp;id=<?php echo (int)$item['id']; ?>"><?php echo htmlspecialchars((string)$item['name']); ?></a></td>
            <td><?php echo !empty($item['is_config_only']) ? 'Config-only' : 'DB-backed'; ?></td>
            <td><?php echo htmlspecialchars((string)($item['realmd'] ?? '')); ?></td>
            <td><?php echo htmlspecialchars((string)($item['world'] ?? '')); ?></td>
            <td><?php echo htmlspecialchars((string)($item['chars'] ?? '')); ?></td>
            <td><?php echo htmlspecialchars(trim((string)($item['address'] ?? '')) . (((int)($item['port'] ?? 0) > 0) ? ':' . (int)$item['port'] : '')); ?></td>
            <td>
              <?php
                $statusBits = array();
                if (in_array((int)$item['id'], (array)($runtime_settings['enabled_realm_ids'] ?? array()), true)) {
                  $statusBits[] = 'Enabled';
                } else {
                  $statusBits[] = 'Disabled';
                }
                if ((int)($runtime_settings['default_realm_id'] ?? 0) === (int)$item['id']) {
                  $statusBits[] = 'Default';
                }
                if (empty($item['has_realmlist_row'])) {
                  $statusBits[] = 'No realmlist row';
                }
                echo htmlspecialchars(implode(' | ', $statusBits));
              ?>
            </td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="realm-admin__card feature-panel">
    <h3>Realmlist Directory</h3>
    <p>These are the live <code>realmlist</code> rows currently present in the selected website realmd database.</p>
    <div class="realm-admin__table-wrap">
      <table class="realm-admin__table">
        <thead>
          <tr>
            <th>#</th>
            <th>Name</th>
            <th>Address</th>
            <th>Port</th>
            <th>Type</th>
            <th>Flags</th>
            <th>Timezone</th>
            <th>Allowed Security</th>
            <th>Population</th>
            <th>Builds</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ((array)($realmlist_items ?? array()) as $realmlistItem) { ?>
          <tr>
            <td><?php echo (int)$realmlistItem['id']; ?></td>
            <td><?php echo htmlspecialchars((string)$realmlistItem['name']); ?></td>
            <td><?php echo htmlspecialchars((string)$realmlistItem['address']); ?></td>
            <td><?php echo (int)$realmlistItem['port']; ?></td>
            <td><?php echo htmlspecialchars($realm_type_def[$realmlistItem['icon']] ?? 'Unknown'); ?></td>
            <td><?php echo (int)($realmlistItem['realmflags'] ?? 0); ?></td>
            <td><?php echo htmlspecialchars($realm_timezone_def[$realmlistItem['timezone']] ?? 'Unknown'); ?></td>
            <td><?php echo (int)($realmlistItem['allowedSecurityLevel'] ?? 0); ?></td>
            <td><?php echo htmlspecialchars((string)($realmlistItem['population'] ?? '0')); ?></td>
            <td><?php echo htmlspecialchars((string)($realmlistItem['realmbuilds'] ?? '')); ?></td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php $schemaSummary = (array)($schemaScan['summary'] ?? array()); ?>
  <div class="realm-admin__card feature-panel">
    <h3>Schema Scan</h3>
    <p>Checks the website DB once, scans each distinct runtime <code>realmd</code> once, and scans <code>chars</code> for enabled runtime slots only.</p>
    <p class="realm-admin__muted">
      Databases scanned: <?php echo (int)($schemaSummary['database_count'] ?? 0); ?> |
      Checks: <?php echo (int)($schemaSummary['check_count'] ?? 0); ?> |
      Healthy scopes: <?php echo (int)($schemaSummary['healthy_count'] ?? 0); ?> |
      Missing items: <?php echo (int)($schemaSummary['missing_count'] ?? 0); ?>
    </p>
    <?php foreach ((array)($schemaScan['databases'] ?? array()) as $databaseScan) { ?>
      <?php $scopeStatus = (string)($databaseScan['status'] ?? 'warn'); ?>
      <div class="realm-admin__advanced">
        <p class="realm-admin__advanced-title">
          <?php echo htmlspecialchars((string)($databaseScan['label'] ?? 'Database')); ?>
          <span class="realm-admin__muted"> | <?php echo htmlspecialchars($scopeStatus === 'ok' ? 'OK' : ($scopeStatus === 'error' ? 'Connection Error' : 'Needs Attention')); ?> | Missing checks: <?php echo (int)($databaseScan['missing_count'] ?? 0); ?></span>
        </p>
        <?php if (!empty($databaseScan['detail'])) { ?>
          <p class="realm-admin__muted"><?php echo htmlspecialchars((string)$databaseScan['detail']); ?></p>
        <?php } ?>
        <?php if (!empty($databaseScan['error'])) { ?>
          <p class="realm-admin__muted"><?php echo htmlspecialchars((string)$databaseScan['error']); ?></p>
        <?php } ?>
        <table class="realm-admin__table">
          <thead>
            <tr>
              <th>Check</th>
              <th>Status</th>
              <th>Missing</th>
              <th>Why It Matters</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ((array)($databaseScan['items'] ?? array()) as $scanItem) { ?>
              <tr>
                <td><?php echo htmlspecialchars((string)($scanItem['label'] ?? 'Schema check')); ?></td>
                <td><?php echo !empty($scanItem['ok']) ? 'OK' : 'Missing'; ?></td>
                <td><?php echo !empty($scanItem['ok']) ? '<span class="realm-admin__muted">None</span>' : '<code>' . htmlspecialchars(implode(', ', (array)($scanItem['missing'] ?? array()))) . '</code>'; ?></td>
                <td><?php echo htmlspecialchars((string)($scanItem['notes'] ?? '')); ?></td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    <?php } ?>
  </div>

  <div class="realm-admin__card feature-panel">
    <h3>Add Runtime Realm Slot</h3>
    <p>Create a DB-backed runtime slot, optionally enable it immediately, and upsert the matching <code>realmlist</code> row.</p>
    <form action="index.php?n=admin&amp;sub=realms&amp;action=create-slot" method="post" onsubmit="return popup_ask('<?php echo htmlspecialchars($realmConfirmLabel, ENT_QUOTES); ?>');">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($admin_realms_csrf_token ?? spp_csrf_token('admin_realms')); ?>">
      <div class="realm-admin__form-grid">
        <div class="realm-admin__field"><label>ID</label><input type="text" name="id" value="<?php echo htmlspecialchars((string)($slotFormState['id'] ?? '')); ?>"></div>
        <div class="realm-admin__field"><label>Name</label><input type="text" name="name" value="<?php echo htmlspecialchars((string)($slotFormState['name'] ?? '')); ?>"></div>
        <div class="realm-admin__field"><label>Address</label><input type="text" name="address" value="<?php echo htmlspecialchars((string)($slotFormState['address'] ?? '')); ?>"></div>
        <div class="realm-admin__field"><label>Port</label><input type="text" name="port" value="<?php echo htmlspecialchars((string)($slotFormState['port'] ?? 8085)); ?>"></div>
        <div class="realm-admin__field"><label>Realmd DB</label><input type="text" name="realmd" value="<?php echo htmlspecialchars((string)($slotFormState['realmd'] ?? '')); ?>"></div>
        <div class="realm-admin__field"><label>World DB</label><input type="text" name="world" value="<?php echo htmlspecialchars((string)($slotFormState['world'] ?? '')); ?>"></div>
        <div class="realm-admin__field"><label>Chars DB</label><input type="text" name="chars" value="<?php echo htmlspecialchars((string)($slotFormState['chars'] ?? '')); ?>"></div>
        <div class="realm-admin__field"><label>Armory DB</label><input type="text" name="armory" value="<?php echo htmlspecialchars((string)($slotFormState['armory'] ?? '')); ?>"></div>
        <div class="realm-admin__field"><label>Bots DB</label><input type="text" name="bots" value="<?php echo htmlspecialchars((string)($slotFormState['bots'] ?? '')); ?>"></div>
        <div class="realm-admin__field"><label>Type</label><select name="icon"><?php foreach ($realm_type_def as $tmp_id => $tmp_name) { ?><option value="<?php echo (int)$tmp_id; ?>"<?php echo ((int)($slotFormState['icon'] ?? 0) === (int)$tmp_id) ? ' selected' : ''; ?>><?php echo htmlspecialchars($tmp_name); ?></option><?php } ?></select></div>
        <div class="realm-admin__field"><label>Realm Flags</label><input type="text" name="realmflags" value="<?php echo htmlspecialchars((string)($slotFormState['realmflags'] ?? 0)); ?>"></div>
        <div class="realm-admin__field"><label>Timezone</label><select name="timezone"><?php foreach ($realm_timezone_def as $tmp_id => $tmp_name) { ?><option value="<?php echo (int)$tmp_id; ?>"<?php echo ((int)($slotFormState['timezone'] ?? 1) === (int)$tmp_id) ? ' selected' : ''; ?>><?php echo htmlspecialchars($tmp_name); ?></option><?php } ?></select></div>
        <div class="realm-admin__field"><label>Allowed Security Level</label><input type="text" name="allowedSecurityLevel" value="<?php echo htmlspecialchars((string)($slotFormState['allowedSecurityLevel'] ?? 0)); ?>"></div>
        <div class="realm-admin__field"><label>Population</label><input type="text" name="population" value="<?php echo htmlspecialchars((string)($slotFormState['population'] ?? '0')); ?>"></div>
        <div class="realm-admin__field"><label>Realm Builds</label><input type="text" name="realmbuilds" value="<?php echo htmlspecialchars((string)($slotFormState['realmbuilds'] ?? '')); ?>"></div>
      </div>
      <div class="realm-admin__advanced">
        <label><input type="hidden" name="enabled" value="0"><input type="checkbox" name="enabled" value="1"<?php echo ((int)($slotFormState['enabled'] ?? 1) === 1) ? ' checked' : ''; ?>> Enable this runtime slot after save</label><br>
        <label><input type="hidden" name="make_default" value="0"><input type="checkbox" name="make_default" value="1"<?php echo ((int)($slotFormState['make_default'] ?? 0) === 1) ? ' checked' : ''; ?>> Make this the default runtime realm</label>
      </div>
      <div class="realm-admin__actions">
        <input class="realm-admin__button" type="submit" value="Create Runtime Slot">
      </div>
    </form>
  </div>
<?php } elseif ($view_mode === 'edit' && !empty($item)) { ?>
  <div class="realm-admin__card feature-panel">
    <h3>Edit Runtime Realm Slot</h3>
    <p>Saving here updates <code>realm_runtime.realm_definitions</code>, refreshes runtime enabled/default state, and upserts the matching <code>realmlist</code> row. <code>realmd_db_version</code> stays read-only.</p>
    <form action="index.php?n=admin&amp;sub=realms&amp;action=update-slot&amp;id=<?php echo (int)$item['id']; ?>" method="post" onsubmit="return popup_ask('<?php echo htmlspecialchars($realmConfirmLabel, ENT_QUOTES); ?>');">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($admin_realms_csrf_token ?? spp_csrf_token('admin_realms')); ?>">
      <div class="realm-admin__form-grid">
        <div class="realm-admin__field"><label>ID</label><input type="text" value="<?php echo (int)$item['id']; ?>" readonly></div>
        <div class="realm-admin__field"><label>Name</label><input type="text" name="name" value="<?php echo htmlspecialchars((string)$item['name']); ?>"></div>
        <div class="realm-admin__field"><label>Address</label><input type="text" name="address" value="<?php echo htmlspecialchars((string)($item['address'] ?? '')); ?>"></div>
        <div class="realm-admin__field"><label>Port</label><input type="text" name="port" value="<?php echo htmlspecialchars((string)($item['port'] ?? 8085)); ?>"></div>
        <div class="realm-admin__field"><label>Realmd DB</label><input type="text" name="realmd" value="<?php echo htmlspecialchars((string)($item['realmd'] ?? '')); ?>"></div>
        <div class="realm-admin__field"><label>World DB</label><input type="text" name="world" value="<?php echo htmlspecialchars((string)($item['world'] ?? '')); ?>"></div>
        <div class="realm-admin__field"><label>Chars DB</label><input type="text" name="chars" value="<?php echo htmlspecialchars((string)($item['chars'] ?? '')); ?>"></div>
        <div class="realm-admin__field"><label>Armory DB</label><input type="text" name="armory" value="<?php echo htmlspecialchars((string)($item['armory'] ?? '')); ?>"></div>
        <div class="realm-admin__field"><label>Bots DB</label><input type="text" name="bots" value="<?php echo htmlspecialchars((string)($item['bots'] ?? '')); ?>"></div>
        <div class="realm-admin__field"><label>Type</label><select name="icon"><?php foreach ($realm_type_def as $tmp_id => $tmp_name) { ?><option value="<?php echo (int)$tmp_id; ?>"<?php echo ((int)($item['icon'] ?? 0) === (int)$tmp_id) ? ' selected' : ''; ?>><?php echo htmlspecialchars($tmp_name); ?></option><?php } ?></select></div>
        <div class="realm-admin__field"><label>Realm Flags</label><input type="text" name="realmflags" value="<?php echo htmlspecialchars((string)($item['realmflags'] ?? 0)); ?>"></div>
        <div class="realm-admin__field"><label>Timezone</label><select name="timezone"><?php foreach ($realm_timezone_def as $tmp_id => $tmp_name) { ?><option value="<?php echo (int)$tmp_id; ?>"<?php echo ((int)($item['timezone'] ?? 1) === (int)$tmp_id) ? ' selected' : ''; ?>><?php echo htmlspecialchars($tmp_name); ?></option><?php } ?></select></div>
        <div class="realm-admin__field"><label>Allowed Security Level</label><input type="text" name="allowedSecurityLevel" value="<?php echo htmlspecialchars((string)($item['allowedSecurityLevel'] ?? 0)); ?>"></div>
        <div class="realm-admin__field"><label>Population</label><input type="text" name="population" value="<?php echo htmlspecialchars((string)($item['population'] ?? '0')); ?>"></div>
        <div class="realm-admin__field"><label>Realm Builds</label><input type="text" name="realmbuilds" value="<?php echo htmlspecialchars((string)($item['realmbuilds'] ?? '')); ?>"></div>
      </div>
      <div class="realm-admin__advanced">
        <label><input type="hidden" name="enabled" value="0"><input type="checkbox" name="enabled" value="1"<?php echo in_array((int)$item['id'], (array)($runtime_settings['enabled_realm_ids'] ?? array()), true) ? ' checked' : ''; ?>> Enable this runtime slot</label><br>
        <label><input type="hidden" name="make_default" value="0"><input type="checkbox" name="make_default" value="1"<?php echo ((int)($runtime_settings['default_realm_id'] ?? 0) === (int)$item['id']) ? ' checked' : ''; ?>> Make this the default runtime realm</label><br>
        <span class="realm-admin__muted"><?php echo !empty($item['is_config_only']) ? 'Saving will migrate this config-only fallback slot into DB-backed runtime definitions.' : 'This slot is already DB-backed.'; ?></span>
      </div>
      <div class="realm-admin__actions">
        <a class="realm-admin__button" href="index.php?n=admin&amp;sub=realms">Back to Realms</a>
        <input class="realm-admin__button" type="submit" value="<?php echo !empty($item['is_config_only']) ? 'Migrate and Save Slot' : 'Save Runtime Slot'; ?>">
      </div>
    </form>

    <form action="index.php?n=admin&amp;sub=realms&amp;action=remove-slot&amp;id=<?php echo (int)$item['id']; ?>" method="post" onsubmit="return popup_ask('<?php echo htmlspecialchars($realmConfirmLabel, ENT_QUOTES); ?>');">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($admin_realms_csrf_token ?? spp_csrf_token('admin_realms')); ?>">
      <div class="realm-admin__advanced">
        <p class="realm-admin__advanced-title">Removal</p>
        <p class="realm-admin__muted">Default behavior removes only the runtime slot definition and normalizes enabled/default runtime settings.</p>
      </div>
      <div class="realm-admin__actions">
        <button class="realm-admin__button realm-admin__button--danger" type="submit" name="delete_realmlist_row" value="0"><?php echo !empty($item['is_config_only']) ? 'Remove From Active Runtime Use' : 'Remove Runtime Slot'; ?></button>
        <button class="realm-admin__button realm-admin__button--danger" type="submit" name="delete_realmlist_row" value="1">Remove Runtime Slot + Realmlist Row</button>
      </div>
    </form>
  </div>
<?php } ?>
</div>
<?php builddiv_end() ?>
