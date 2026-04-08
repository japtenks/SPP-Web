<br>
<?php builddiv_start(1, $lang['realms'] ?? 'Realms') ?>
<div class="realm-admin feature-shell">
<?php $realmConfirmLabel = (string)($lang['sure_q'] ?? 'Are you sure?'); ?>
<?php if ($view_mode === 'list') { ?>
  <div class="realm-admin__intro feature-hero">
    <strong>Realm Directory</strong><br>
    Realm Management now reflects the real <code>realmlist</code> shape only. Rename and address changes move through
    <a href="<?php echo htmlspecialchars((string)($realm_operations_href ?? 'index.php?n=admin&sub=operations')); ?>">Operations</a>
    so reviewed jobs always show scope, preview text, and audit history.
  </div>
  <div class="realm-admin__table-wrap feature-panel">
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
      <?php foreach ($items as $item) { ?>
        <tr>
          <td><?php echo (int)$item['id']; ?></td>
          <td><a class="realm-admin__name" href="index.php?n=admin&amp;sub=realms&amp;action=edit&amp;id=<?php echo (int)$item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?></a></td>
          <td><?php echo htmlspecialchars($item['address']); ?></td>
          <td><?php echo (int)$item['port']; ?></td>
          <td><?php echo htmlspecialchars($realm_type_def[$item['icon']] ?? 'Unknown'); ?></td>
          <td><?php echo (int)($item['realmflags'] ?? 0); ?></td>
          <td><?php echo htmlspecialchars($realm_timezone_def[$item['timezone']] ?? 'Unknown'); ?></td>
          <td><?php echo (int)($item['allowedSecurityLevel'] ?? 0); ?></td>
          <td><?php echo htmlspecialchars((string)($item['population'] ?? '0')); ?></td>
          <td><?php echo htmlspecialchars((string)($item['realmbuilds'] ?? '')); ?></td>
        </tr>
      <?php } ?>
      </tbody>
    </table>
  </div>

  <div class="realm-admin__card feature-panel">
    <h3>Runtime Realm Settings</h3>
    <p>These controls govern website runtime behavior and are separate from the realmlist rows above.</p>
    <?php if (!empty($runtime_errors)) { ?>
      <div class="realm-admin__advanced">
        <p class="realm-admin__advanced-title">Validation</p>
        <ul>
          <?php foreach ((array)$runtime_errors as $runtimeError) { ?>
            <li><?php echo htmlspecialchars((string)$runtimeError); ?></li>
          <?php } ?>
        </ul>
      </div>
    <?php } ?>
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
          <label>Selection Mode</label>
          <select name="selection_mode">
            <?php foreach ((array)$runtime_selection_modes as $modeKey => $modeLabel) { ?>
              <option value="<?php echo htmlspecialchars((string)$modeKey); ?>"<?php echo ((string)($runtime_form['selection_mode'] ?? 'manual') === (string)$modeKey) ? ' selected' : ''; ?>><?php echo htmlspecialchars((string)$modeLabel); ?></option>
            <?php } ?>
          </select>
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
        <p class="realm-admin__advanced-title">Enabled Realms</p>
        <?php if (!empty($runtime_realm_options)) { ?>
          <div class="realm-admin__form-grid">
            <?php foreach ((array)$runtime_realm_options as $realmOption) { ?>
              <div class="realm-admin__field">
                <label>
                  <input type="checkbox" name="enabled_realm_ids[]" value="<?php echo (int)$realmOption['id']; ?>"<?php echo in_array((int)$realmOption['id'], (array)($runtime_form['enabled_realm_ids'] ?? array()), true) ? ' checked' : ''; ?>>
                  <?php echo htmlspecialchars((string)$realmOption['label']); ?>
                </label>
              </div>
            <?php } ?>
          </div>
        <?php } else { ?>
          <p class="realm-admin__muted">No configured realms are currently available in the runtime map.</p>
        <?php } ?>
      </div>
      <div class="realm-admin__actions">
        <input class="realm-admin__button" type="submit" value="Save Runtime Settings">
      </div>
    </form>
    <p class="realm-admin__muted">
      Saved values are written to the website settings table and survive Apache restarts.
    </p>
  </div>

  <?php $schemaScan = (array)($schema_scan ?? array()); ?>
  <?php $schemaSummary = (array)($schemaScan['summary'] ?? array()); ?>
  <div class="realm-admin__card feature-panel">
    <h3>Schema Scan</h3>
    <p>Checks the site database plus each configured realm for the tables and columns this website currently expects.</p>
    <p class="realm-admin__muted">
      Databases scanned: <?php echo (int)($schemaSummary['database_count'] ?? 0); ?> |
      Checks: <?php echo (int)($schemaSummary['check_count'] ?? 0); ?> |
      Healthy scopes: <?php echo (int)($schemaSummary['healthy_count'] ?? 0); ?> |
      Missing items: <?php echo (int)($schemaSummary['missing_count'] ?? 0); ?>
    </p>

    <?php foreach ((array)($schemaScan['databases'] ?? array()) as $databaseScan) { ?>
      <?php
        $scopeStatus = (string)($databaseScan['status'] ?? 'warn');
        $statusLabel = $scopeStatus === 'ok' ? 'OK' : ($scopeStatus === 'error' ? 'Connection Error' : 'Needs Attention');
      ?>
      <div class="realm-admin__advanced">
        <p class="realm-admin__advanced-title">
          <?php echo htmlspecialchars((string)($databaseScan['label'] ?? 'Database')); ?>
          <span class="realm-admin__muted"> | <?php echo htmlspecialchars($statusLabel); ?> | Missing checks: <?php echo (int)($databaseScan['missing_count'] ?? 0); ?></span>
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
                <td>
                  <?php if (!empty($scanItem['ok'])) { ?>
                    <span class="realm-admin__muted">None</span>
                  <?php } else { ?>
                    <code><?php echo htmlspecialchars(implode(', ', (array)($scanItem['missing'] ?? array()))); ?></code>
                  <?php } ?>
                </td>
                <td><?php echo htmlspecialchars((string)($scanItem['notes'] ?? '')); ?></td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    <?php } ?>
  </div>

  <div class="realm-admin__card feature-panel">
    <h3>Create New Realm</h3>
    <p>Add a realm entry with the supported <code>realmlist</code> columns only.</p>
    <form action="index.php?n=admin&amp;sub=realms&amp;action=create" method="post" onsubmit="return popup_ask('<?php echo htmlspecialchars($realmConfirmLabel, ENT_QUOTES); ?>');">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($admin_realms_csrf_token ?? spp_csrf_token('admin_realms')); ?>">
      <div class="realm-admin__form-grid">
        <div class="realm-admin__field"><label>Name</label><input type="text" name="name"></div>
        <div class="realm-admin__field"><label>Address</label><input type="text" name="address"></div>
        <div class="realm-admin__field"><label>Port</label><input type="text" name="port"></div>
        <div class="realm-admin__field"><label>Type</label><select name="icon"><?php foreach ($realm_type_def as $tmp_id => $tmp_name) { ?><option value="<?php echo (int)$tmp_id; ?>"><?php echo htmlspecialchars($tmp_name); ?></option><?php } ?></select></div>
        <div class="realm-admin__field"><label>Realm Flags</label><input type="text" name="realmflags" value="0"></div>
        <div class="realm-admin__field"><label>Timezone</label><select name="timezone"><?php foreach ($realm_timezone_def as $tmp_id => $tmp_name) { ?><option value="<?php echo (int)$tmp_id; ?>"><?php echo htmlspecialchars($tmp_name); ?></option><?php } ?></select></div>
        <div class="realm-admin__field"><label>Allowed Security Level</label><input type="text" name="allowedSecurityLevel" value="0"></div>
        <div class="realm-admin__field"><label>Population</label><input type="text" name="population" value="0"></div>
        <div class="realm-admin__field"><label>Realm Builds</label><input type="text" name="realmbuilds"></div>
      </div>
      <div class="realm-admin__actions"><input class="realm-admin__button" type="submit" value="Create Realm"></div>
    </form>
  </div>
<?php } elseif ($view_mode === 'edit') { ?>
  <div class="realm-admin__card feature-panel">
    <h3>Edit Realm</h3>
    <p>Name and address stay visible here, but reviewed rename/address jobs now route through Operations.</p>
    <form action="index.php?n=admin&amp;sub=realms&amp;action=update&amp;id=<?php echo (int)$item['id']; ?>" method="post" onsubmit="return confirm('<?php echo htmlspecialchars($realmConfirmLabel, ENT_QUOTES); ?>');">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($admin_realms_csrf_token ?? spp_csrf_token('admin_realms')); ?>">
      <div class="realm-admin__form-grid">
        <div class="realm-admin__field"><label>Name</label><input type="text" value="<?php echo htmlspecialchars($item['name']); ?>" readonly></div>
        <div class="realm-admin__field"><label>Address</label><input type="text" value="<?php echo htmlspecialchars($item['address']); ?>" readonly></div>
        <div class="realm-admin__field"><label>Port</label><input type="text" name="port" value="<?php echo (int)$item['port']; ?>"></div>
        <div class="realm-admin__field"><label>Type</label><select name="icon"><?php foreach ($realm_type_def as $tmp_id => $tmp_name) { ?><option value="<?php echo (int)$tmp_id; ?>"<?php if ((int)$item['icon'] === (int)$tmp_id) echo ' selected'; ?>><?php echo htmlspecialchars($tmp_name); ?></option><?php } ?></select></div>
        <div class="realm-admin__field"><label>Realm Flags</label><input type="text" name="realmflags" value="<?php echo (int)($item['realmflags'] ?? 0); ?>"></div>
        <div class="realm-admin__field"><label>Timezone</label><select name="timezone"><?php foreach ($realm_timezone_def as $tmp_id => $tmp_name) { ?><option value="<?php echo (int)$tmp_id; ?>"<?php if ((int)$item['timezone'] === (int)$tmp_id) echo ' selected'; ?>><?php echo htmlspecialchars($tmp_name); ?></option><?php } ?></select></div>
        <div class="realm-admin__field"><label>Allowed Security Level</label><input type="text" name="allowedSecurityLevel" value="<?php echo (int)($item['allowedSecurityLevel'] ?? 0); ?>"></div>
        <div class="realm-admin__field"><label>Population</label><input type="text" name="population" value="<?php echo htmlspecialchars((string)($item['population'] ?? '0')); ?>"></div>
        <div class="realm-admin__field"><label>Realm Builds</label><input type="text" name="realmbuilds" value="<?php echo htmlspecialchars((string)($item['realmbuilds'] ?? '')); ?>"></div>
      </div>
      <div class="realm-admin__advanced">
        <p class="realm-admin__advanced-title">Operational Changes</p>
        <p class="realm-admin__muted">
          Use <a href="index.php?n=admin&amp;sub=operations&amp;operation=apply_realm_name">Apply Realm Name</a> or
          <a href="index.php?n=admin&amp;sub=operations&amp;operation=apply_realm_address">Apply Realm Address</a>
          for reviewed changes that should leave a preview and audit trail.
        </p>
      </div>
      <div class="realm-admin__actions">
        <a class="realm-admin__button" href="index.php?n=admin&amp;sub=realms">Back to Realms</a>
        <input class="realm-admin__button" type="submit" value="Update Realm">
      </div>
    </form>
    <form action="index.php?n=admin&amp;sub=realms&amp;action=delete&amp;id=<?php echo (int)$item['id']; ?>" method="post" onsubmit="return popup_ask('<?php echo htmlspecialchars($realmConfirmLabel, ENT_QUOTES); ?>');">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($admin_realms_csrf_token ?? spp_csrf_token('admin_realms')); ?>">
      <div class="realm-admin__actions">
        <input class="realm-admin__button realm-admin__button--danger" type="submit" value="Delete Realm">
      </div>
    </form>
  </div>
<?php } ?>
</div>
<?php builddiv_end() ?>
