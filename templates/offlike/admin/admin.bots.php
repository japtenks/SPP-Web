<?php
$botMaintenanceView = isset($botMaintenanceView) && is_array($botMaintenanceView) ? $botMaintenanceView : array();
$botFlash = $botMaintenanceView['flash'] ?? array();
$botManualNotice = (string)($botMaintenanceView['manual_notice'] ?? '');
$botManualCommand = (string)($botMaintenanceView['manual_command'] ?? '');
$botPageUrl = (string)($botMaintenanceView['page_url'] ?? 'index.php?n=admin&sub=bots');
$botSelectedRealmId = (int)($botMaintenanceView['selected_realm_id'] ?? 0);
$botSelectedPreview = $botMaintenanceView['selected_preview'] ?? array();
$botRealmOptions = $botMaintenanceView['realm_options'] ?? array();
$botHelperStatus = $botMaintenanceView['helper_status'] ?? array();
$botLastRun = $botMaintenanceView['last_run'] ?? array();
$botScriptCommands = $botMaintenanceView['script_commands'] ?? array();
$botAvailableScripts = $botMaintenanceView['available_scripts'] ?? array();
$botStepPreviews = $botMaintenanceView['step_previews'] ?? array();
$botPreviewRows = $botMaintenanceView['preview_rows'] ?? array();
$botAccountCounts = $botMaintenanceView['account_counts'] ?? array();
$botCacheCounts = $botMaintenanceView['cache_counts'] ?? array();
$botEventCounts = $botMaintenanceView['event_counts'] ?? array();
$botOperationsUrl = (string)($botMaintenanceView['operations_url'] ?? 'index.php?n=admin&sub=operations');
$botIsWindowsHost = !empty($botMaintenanceView['is_windows_host']);

$renderBotCommand = static function ($command) {
    return htmlspecialchars((string)$command, ENT_QUOTES, 'UTF-8');
};
$scriptMissing = static function ($key) use ($botAvailableScripts) {
    return empty($botAvailableScripts[$key]);
};
?>
<?php builddiv_start(1, 'Bot Maintenance'); ?>
<div class="admin-bots feature-shell" data-is-windows-host="<?php echo $botIsWindowsHost ? '1' : '0'; ?>">
  <?php if (!empty($botFlash['message'])): ?>
    <div class="admin-bots__flash <?php echo !empty($botFlash['type']) && $botFlash['type'] === 'success' ? 'admin-bots__flash--success' : 'admin-bots__flash--error'; ?>">
      <?php echo htmlspecialchars((string)$botFlash['message'], ENT_QUOTES, 'UTF-8'); ?>
    </div>
  <?php endif; ?>

  <section class="admin-bots__panel feature-hero">
    <p class="admin-bots__eyebrow">Bot Maintenance</p>
    <h2 class="admin-bots__title">Windows-safe preview and helper surface for bot reset workflows</h2>
    <p class="admin-bots__body">This page keeps the old reset visibility from the armory site, but adapts it for the supported `spp-web` beta surface. Use it to inspect scope, review preserved data, and copy manual commands when the helper scripts are available. For destructive launcher-parity work, prefer the reviewed <a href="<?php echo htmlspecialchars($botOperationsUrl, ENT_QUOTES, 'UTF-8'); ?>">Operations</a> surface.</p>
    <ul class="admin-bots__list">
      <li>Preserved: player accounts, player characters, GM4 accounts, and normal website users.</li>
      <li>Step 1 focuses on selected-realm forums only and preserves official seed authors.</li>
      <li>Step 2 clears website-side bot layers like bot events, identities, and portrait cache.</li>
      <li>Step 3 focuses on realm-side bot state and rotation records before a rebuild.</li>
    </ul>
    <div class="admin-bots__grid admin-bots__section-gap">
      <div class="admin-bots__mini"><strong><?php echo htmlspecialchars((string)($botLastRun['label'] ?? 'No helper action recorded'), ENT_QUOTES, 'UTF-8'); ?></strong><span>Last helper action</span></div>
      <div class="admin-bots__mini"><strong><?php echo htmlspecialchars((string)($botLastRun['ran_at'] ?? 'Never'), ENT_QUOTES, 'UTF-8'); ?></strong><span>Last action timestamp</span></div>
      <div class="admin-bots__mini"><strong><?php echo !empty($botHelperStatus['ok']) ? 'Helper status recorded' : 'No recent status refresh'; ?></strong><span>Cached helper state</span></div>
      <div class="admin-bots__mini"><strong><?php echo $botIsWindowsHost ? 'Windows host' : 'Linux host'; ?></strong><span>Platform-aware messaging is active</span></div>
    </div>
    <?php if ($scriptMissing('status') || $scriptMissing('reset_forum_realm') || $scriptMissing('clear_bot_web_state') || $scriptMissing('clear_bot_character_state')): ?>
      <p class="admin-bots__note admin-bots__note-gap admin-bots__note-danger">This checkout does not currently bundle all of the old helper scripts. Commands below still show the expected script paths, but use <a href="<?php echo htmlspecialchars($botOperationsUrl, ENT_QUOTES, 'UTF-8'); ?>">Operations</a> for reviewed destructive maintenance unless you add the missing tool files locally.</p>
    <?php endif; ?>
    <?php if ($botManualNotice !== '' || $botManualCommand !== ''): ?>
      <p class="admin-bots__note admin-bots__note-gap"><?php echo htmlspecialchars($botManualNotice, ENT_QUOTES, 'UTF-8'); ?></p>
      <div class="admin-bots__command" id="bot-manual-command"><?php echo $renderBotCommand($botManualCommand); ?></div>
      <div class="admin-bots__actions">
        <button type="button" class="admin-bots__btn-input" data-copy-target="bot-manual-command">Copy Manual Command</button>
      </div>
    <?php endif; ?>
  </section>

  <section class="admin-bots__panel feature-panel">
    <p class="admin-bots__eyebrow">Selected Realm</p>
    <form action="<?php echo htmlspecialchars($botPageUrl, ENT_QUOTES, 'UTF-8'); ?>" method="get" class="admin-bots__actions">
      <input type="hidden" name="n" value="admin">
      <input type="hidden" name="sub" value="bots">
      <select class="admin-bots__select" name="realm" onchange="this.form.submit()">
        <?php foreach ($botRealmOptions as $botRealmOption): ?>
          <option value="<?php echo (int)$botRealmOption['realm_id']; ?>"<?php echo (int)$botRealmOption['realm_id'] === $botSelectedRealmId ? ' selected' : ''; ?>><?php echo htmlspecialchars((string)$botRealmOption['label'], ENT_QUOTES, 'UTF-8'); ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  </section>

  <section class="admin-bots__panel feature-panel">
    <p class="admin-bots__eyebrow">Reset Preview</p>
    <p class="admin-bots__body">These counts estimate the selected realm footprint before any maintenance run. Protected counts stay visible so it is clear what remains out of scope.</p>
    <div class="admin-bots__grid admin-bots__section-gap">
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botAccountCounts['bot_accounts'] ?? 0)); ?></strong><span>`rndbot%` auth accounts</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botSelectedPreview['bot_characters'] ?? 0)); ?></strong><span>Bot characters on this realm</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botSelectedPreview['bot_guilds'] ?? 0)); ?></strong><span>Bot guild shells / memberships</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botSelectedPreview['bot_db_store_rows'] ?? 0)); ?></strong><span>`ai_playerbot_db_store` rows</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botSelectedPreview['bot_auction_rows'] ?? 0)); ?></strong><span>Bot auction rows</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botEventCounts['website_bot_events'] ?? 0)); ?></strong><span>Bot event rows</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botSelectedPreview['rotation_state_rows'] ?? 0) + (int)($botSelectedPreview['rotation_log_rows'] ?? 0) + (int)($botSelectedPreview['rotation_ilvl_log_rows'] ?? 0)); ?></strong><span>Rotation rows</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botCacheCounts['portrait_files'] ?? 0)); ?></strong><span>Portrait cache files</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botSelectedPreview['guild_json_files'] ?? 0)); ?></strong><span>Guild JSON files</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botAccountCounts['human_accounts'] ?? 0)); ?></strong><span>Human accounts preserved</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botAccountCounts['gm_accounts'] ?? 0)); ?></strong><span>GM accounts preserved</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botAccountCounts['website_users'] ?? 0)); ?></strong><span>Website users preserved</span></div>
    </div>
  </section>

  <section class="admin-bots__panel feature-panel">
    <p class="admin-bots__eyebrow">Step 1</p>
    <h3 class="admin-bots__title admin-bots__title-sm">Reset Selected Realm Forums</h3>
    <div class="admin-bots__grid admin-bots__section-gap">
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['forum_reset']['topics'] ?? 0)); ?></strong><span>Realm forum topics</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['forum_reset']['posts'] ?? 0)); ?></strong><span>Realm forum posts</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['forum_reset']['pms'] ?? 0)); ?></strong><span>PM rows</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['forum_reset']['preserved_topics'] ?? 0)); ?></strong><span>Preserved official topics</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['forum_reset']['preserved_posts'] ?? 0)); ?></strong><span>Preserved official posts</span></div>
    </div>
    <?php if (!empty($botStepPreviews['forum_reset']['forum_ids'])): ?>
      <p class="admin-bots__note admin-bots__note-gap">Included forum IDs: <span class="admin-bots__mono"><?php echo htmlspecialchars(implode(', ', array_map('intval', (array)$botStepPreviews['forum_reset']['forum_ids'])), ENT_QUOTES, 'UTF-8'); ?></span></p>
    <?php endif; ?>
    <div class="admin-bots__command<?php echo $botIsWindowsHost ? ' is-collapsed' : ''; ?>" id="bot-step1-dry"><?php echo $renderBotCommand($botScriptCommands['reset_forum_realm']['dry_run'] ?? ''); ?></div>
    <div class="admin-bots__command<?php echo $botIsWindowsHost ? ' is-collapsed' : ''; ?>" id="bot-step1-run"><?php echo $renderBotCommand($botScriptCommands['reset_forum_realm']['run'] ?? ''); ?></div>
    <div class="admin-bots__actions">
      <button type="button" class="admin-bots__btn-input" data-copy-target="bot-step1-dry">Copy Dry Run</button>
      <button type="button" class="admin-bots__btn-input" data-copy-target="bot-step1-run">Copy Run Command</button>
    </div>
    <?php if ($scriptMissing('reset_forum_realm')): ?><p class="admin-bots__note admin-bots__note-gap admin-bots__note-danger">The `reset_forum_realm.php` helper is not bundled in this checkout.</p><?php endif; ?>
  </section>

  <section class="admin-bots__panel feature-panel">
    <p class="admin-bots__eyebrow">Step 2</p>
    <h3 class="admin-bots__title admin-bots__title-sm">Clear Bot Web State</h3>
    <div class="admin-bots__grid admin-bots__section-gap">
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['web_state']['bot_events'] ?? 0)); ?></strong><span>Website bot events</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['web_state']['bot_identities'] ?? 0)); ?></strong><span>Bot identities</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['web_state']['bot_identity_profiles'] ?? 0)); ?></strong><span>Bot identity profiles</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['web_state']['portrait_files'] ?? 0)); ?></strong><span>Portrait cache files</span></div>
    </div>
    <div class="admin-bots__command<?php echo $botIsWindowsHost ? ' is-collapsed' : ''; ?>" id="bot-step2-dry"><?php echo $renderBotCommand($botScriptCommands['clear_bot_web_state']['dry_run'] ?? ''); ?></div>
    <div class="admin-bots__command<?php echo $botIsWindowsHost ? ' is-collapsed' : ''; ?>" id="bot-step2-run"><?php echo $renderBotCommand($botScriptCommands['clear_bot_web_state']['run'] ?? ''); ?></div>
    <div class="admin-bots__actions">
      <button type="button" class="admin-bots__btn-input" data-copy-target="bot-step2-dry">Copy Dry Run</button>
      <button type="button" class="admin-bots__btn-input" data-copy-target="bot-step2-run">Copy Run Command</button>
    </div>
    <?php if ($scriptMissing('clear_bot_web_state')): ?><p class="admin-bots__note admin-bots__note-gap admin-bots__note-danger">The `clear_bot_web_state.php` helper is not bundled in this checkout.</p><?php endif; ?>
  </section>

  <section class="admin-bots__panel feature-panel">
    <p class="admin-bots__eyebrow">Step 3</p>
    <h3 class="admin-bots__title admin-bots__title-sm">Clear Bot Character State</h3>
    <div class="admin-bots__grid admin-bots__section-gap">
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['character_state']['bot_characters'] ?? 0)); ?></strong><span>Bot characters</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['character_state']['bot_guilds'] ?? 0)); ?></strong><span>Bot guild shells</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['character_state']['bot_db_store_rows'] ?? 0)); ?></strong><span>Bot db-store rows</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['character_state']['bot_auction_rows'] ?? 0)); ?></strong><span>Bot auction rows</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['character_state']['rotation_state_rows'] ?? 0)); ?></strong><span>Rotation state rows</span></div>
      <div class="admin-bots__mini"><strong><?php echo number_format((int)($botStepPreviews['character_state']['rotation_log_rows'] ?? 0) + (int)($botStepPreviews['character_state']['rotation_ilvl_log_rows'] ?? 0)); ?></strong><span>Rotation history rows</span></div>
    </div>
    <div class="admin-bots__command<?php echo $botIsWindowsHost ? ' is-collapsed' : ''; ?>" id="bot-step3-dry"><?php echo $renderBotCommand($botScriptCommands['clear_bot_character_state']['dry_run'] ?? ''); ?></div>
    <div class="admin-bots__command<?php echo $botIsWindowsHost ? ' is-collapsed' : ''; ?>" id="bot-step3-run"><?php echo $renderBotCommand($botScriptCommands['clear_bot_character_state']['run'] ?? ''); ?></div>
    <div class="admin-bots__actions">
      <button type="button" class="admin-bots__btn-input" data-copy-target="bot-step3-dry">Copy Dry Run</button>
      <button type="button" class="admin-bots__btn-input" data-copy-target="bot-step3-run">Copy Run Command</button>
    </div>
    <?php if ($scriptMissing('clear_bot_character_state')): ?><p class="admin-bots__note admin-bots__note-gap admin-bots__note-danger">The `clear_bot_character_state.php` helper is not bundled in this checkout.</p><?php endif; ?>
    <p class="admin-bots__note admin-bots__note-gap">If you need full delete/reset workflows beyond these previews, use the reviewed <a href="<?php echo htmlspecialchars($botOperationsUrl, ENT_QUOTES, 'UTF-8'); ?>">Operations</a> catalog instead of relying on legacy Linux-only shell patterns.</p>
  </section>

  <section class="admin-bots__table-wrap feature-panel">
    <p class="admin-bots__eyebrow">Per-Realm Preview</p>
    <table class="admin-bots__table">
      <thead>
        <tr>
          <th>Realm</th>
          <th>Bot Scope</th>
          <th>Forum / Identity</th>
          <th>Rotation</th>
          <th>Protected</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($botPreviewRows as $botRow): ?>
          <tr>
            <td>
              <strong><?php echo htmlspecialchars((string)$botRow['realm_name'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
              <span class="admin-bots__mono">Realm <?php echo (int)$botRow['realm_id']; ?></span>
              <?php if (!empty($botRow['warning'])): ?><br><span class="admin-bots__warning"><?php echo htmlspecialchars((string)$botRow['warning'], ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
            </td>
            <td><?php echo number_format((int)$botRow['bot_characters']); ?> bot chars<br><?php echo number_format((int)$botRow['bot_guilds']); ?> bot guilds<br><?php echo number_format((int)$botRow['bot_db_store_rows']); ?> db-store rows</td>
            <td><?php echo number_format((int)$botRow['forum_topics']); ?> topics / <?php echo number_format((int)$botRow['forum_posts']); ?> posts<br><?php echo number_format((int)$botRow['bot_identities']); ?> identities / <?php echo number_format((int)$botRow['bot_identity_profiles']); ?> profiles</td>
            <td><?php echo number_format((int)$botRow['rotation_state_rows']); ?> live state rows<br><?php echo number_format((int)$botRow['rotation_log_rows']); ?> log rows<br><?php echo number_format((int)$botRow['rotation_ilvl_log_rows']); ?> ilvl rows</td>
            <td><?php echo number_format((int)$botRow['player_characters']); ?> player chars untouched</td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</div>
<?php builddiv_end(); ?>
