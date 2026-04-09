<br>
<?php builddiv_start(1, 'Backup') ?>
<div class="backup-admin">
  <?php if (!empty($backupActionState['notice'])): ?>
    <div class="backup-admin__msg success">
      <?php echo htmlspecialchars((string)$backupActionState['notice']); ?>
      <?php if (!empty($backupActionState['downloads'])): ?>
        <?php foreach ((array)$backupActionState['downloads'] as $download): ?>
          <a href="<?php echo htmlspecialchars((string)($download['download_url'] ?? '')); ?>">
            <?php echo 'Download ' . htmlspecialchars(strtoupper((string)($download['lane'] ?? 'sql'))); ?>
          </a>
        <?php endforeach; ?>
      <?php elseif (!empty($backupActionState['download_url'])): ?>
        <a href="<?php echo htmlspecialchars((string)$backupActionState['download_url']); ?>">Download SQL</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
  <?php if (!empty($backupActionState['error'])): ?>
    <div class="backup-admin__msg error"><?php echo htmlspecialchars((string)$backupActionState['error']); ?></div>
  <?php endif; ?>
  <?php if (!empty($backupView['warnings'])): ?>
    <div class="backup-admin__msg error">
      <?php foreach ((array)$backupView['warnings'] as $warning): ?>
        <div><?php echo htmlspecialchars((string)$warning); ?></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <section class="backup-admin__panel">
    <p class="backup-admin__eyebrow">Recent Packages</p>
    <h2 class="backup-admin__title">Cache Files</h2>
    <p class="backup-admin__note">Generated backup and xfer packages are stored in the local cache folder and can be downloaded again here.</p>
    <div class="backup-admin__actions">
      <form method="post" action="<?php echo htmlspecialchars((string)$backup_action_url, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$admin_backup_csrf_token); ?>">
        <input type="hidden" name="backup_action" value="clear_cache">
        <input type="submit" value="Clear Cache" onclick="return confirm('Delete all generated package files from the cache folder?');">
      </form>
    </div>

    <div class="backup-admin__files">
      <?php if (!empty($backupView['recent_files'])): ?>
        <?php foreach ((array)$backupView['recent_files'] as $backupFile): ?>
          <div class="backup-admin__file">
            <div class="backup-admin__file-meta">
              <div class="backup-admin__file-name"><?php echo htmlspecialchars((string)$backupFile['filename']); ?></div>
              <div class="backup-admin__file-sub">
                <?php echo date('Y-m-d H:i:s', (int)($backupFile['mtime'] ?? time())); ?>
                <?php echo ' | ' . number_format(((int)($backupFile['size'] ?? 0)) / 1024, 1) . ' KB'; ?>
              </div>
            </div>
            <a class="backup-admin__file-link" href="<?php echo htmlspecialchars((string)$backupFile['download_url']); ?>">Download</a>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="backup-admin__file">
          <div class="backup-admin__file-meta">
            <div class="backup-admin__file-name">No cached SQL packages yet</div>
            <div class="backup-admin__file-sub"><?php echo htmlspecialchars((string)($backupView['output_dir'] ?? '')); ?></div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="backup-admin__panel">
    <p class="backup-admin__eyebrow">Realm Xfer</p>
    <h2 class="backup-admin__title">Create Xfer SQL</h2>
    <p class="backup-admin__note">Xfer builds target-ready SQL artifacts for manual review and apply. Standard CMaNGOS routes package direct cross-realm SQL, while vMaNGOS routes use explicit transform-export logic instead of same-schema transfer assumptions.</p>
    <?php if (!empty($backupView['xfer_route_help'])): ?>
      <p class="backup-admin__note" id="xfer_route_help"><?php echo htmlspecialchars((string)$backupView['xfer_route_help']); ?></p>
    <?php endif; ?>
    <?php
      $selectedXferType = (string)($backupView['xfer_entity_type'] ?? 'character');
      $selectedXferRoute = (array)($backupView['selected_xfer_route'] ?? array());
      $isVmangosTransformRoute = !empty($selectedXferRoute['target_is_vmangos']) && empty($selectedXferRoute['source_is_vmangos']);
    ?>

    <form method="get" action="<?php echo htmlspecialchars((string)$backup_action_url, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="n" value="admin">
      <input type="hidden" name="sub" value="backup">
      <div class="backup-admin__form">
        <div class="backup-admin__pair xfer-field" data-entities="character,account,guild">
          <label for="xfer_entity_type">Type</label>
          <select id="xfer_entity_type" name="xfer_entity_type">
            <?php foreach ((array)$backupView['xfer_entity_options'] as $entityKey => $entityLabel): ?>
              <option value="<?php echo htmlspecialchars((string)$entityKey); ?>"<?php if ((string)$entityKey === (string)$backupView['xfer_entity_type']) echo ' selected'; ?>>
                <?php echo htmlspecialchars((string)$entityLabel); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="backup-admin__pair xfer-field" data-entities="character,account,guild">
          <label for="xfer_route">Realm Route</label>
          <select id="xfer_route" name="xfer_route">
          <?php foreach ((array)$backupView['xfer_route_options'] as $routeOption): ?>
            <option value="<?php echo htmlspecialchars((string)$routeOption['id']); ?>"<?php if ((string)$routeOption['id'] === (string)$backupView['selected_xfer_route_id']) echo ' selected'; ?>>
              <?php echo htmlspecialchars((string)$routeOption['label']); ?>
            </option>
          <?php endforeach; ?>
          </select>
        </div>

        <?php if ($selectedXferType === 'character' || $selectedXferType === 'account'): ?>
          <div class="backup-admin__pair xfer-field" data-entities="character,account">
            <label for="xfer_source_account_id">Source Account</label>
            <select id="xfer_source_account_id" name="source_account_id">
            <?php foreach ((array)$backupView['source_account_options'] as $accountOption): ?>
              <option value="<?php echo (int)$accountOption['id']; ?>"<?php if ((int)$accountOption['id'] === (int)$backupView['selected_account_id']) echo ' selected'; ?>>
                <?php echo '#' . (int)$accountOption['id'] . ' - ' . htmlspecialchars((string)$accountOption['username']); ?>
              </option>
            <?php endforeach; ?>
            </select>
            <div class="backup-admin__muted" id="xfer_account_hint">
              <?php
                $selectedXferAccountUsername = (string)($backupView['selected_account_row']['username'] ?? '');
                $selectedXferAccountId = (int)($backupView['selected_account_id'] ?? 0);
                $xferCharacterCount = count((array)($backupView['source_character_options'] ?? array()));
                if ($selectedXferAccountId > 0) {
                  echo htmlspecialchars('Selected account #' . $selectedXferAccountId . ($selectedXferAccountUsername !== '' ? ' (' . $selectedXferAccountUsername . ')' : '') . ' | Characters found: ' . $xferCharacterCount);
                } else {
                  echo 'No source account selected.';
                }
              ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($selectedXferType === 'character'): ?>
          <div class="backup-admin__pair xfer-field" data-entities="character">
            <label for="xfer_source_character_guid">Character</label>
            <select id="xfer_source_character_guid" name="source_character_guid">
            <?php if (!empty($backupView['source_character_options'])): ?>
              <?php foreach ((array)$backupView['source_character_options'] as $characterOption): ?>
                <option value="<?php echo (int)$characterOption['guid']; ?>"<?php if ((int)$characterOption['guid'] === (int)$backupView['selected_character_guid']) echo ' selected'; ?>>
                  <?php echo htmlspecialchars((string)$characterOption['name'] . ' (Lvl ' . (int)$characterOption['level'] . ')'); ?>
                </option>
              <?php endforeach; ?>
            <?php else: ?>
              <option value="0">No characters on this account</option>
            <?php endif; ?>
            </select>
            <div class="backup-admin__muted" id="xfer_character_hint">
              <?php echo empty($backupView['source_character_options']) ? 'Choose another account or switch transfer type.' : 'Select one character to export for the target realm.'; ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($selectedXferType === 'guild'): ?>
          <div class="backup-admin__pair xfer-field" data-entities="guild">
            <label for="xfer_source_guild_id">Guild</label>
            <select id="xfer_source_guild_id" name="source_guild_id">
            <?php if (!empty($backupView['source_guild_options'])): ?>
              <?php foreach ((array)$backupView['source_guild_options'] as $guildOption): ?>
                <option value="<?php echo (int)$guildOption['guildid']; ?>"<?php if ((int)$guildOption['guildid'] === (int)$backupView['selected_guild_id']) echo ' selected'; ?>>
                  <?php echo htmlspecialchars((string)$guildOption['name'] . (!empty($guildOption['leader_name']) ? ' (Leader: ' . $guildOption['leader_name'] . ')' : '')); ?>
                </option>
              <?php endforeach; ?>
            <?php else: ?>
              <option value="0">No guilds found on this realm</option>
            <?php endif; ?>
            </select>
            <div class="backup-admin__muted" id="xfer_guild_hint">
              <?php
                $guildSummary = (array)($backupView['selected_guild_summary'] ?? array());
                $guildMemberCount = (int)($guildSummary['member_count'] ?? 0);
                $guildAccountCount = (int)($guildSummary['account_count'] ?? 0);
                $guildHumanCount = (int)($guildSummary['human_account_count'] ?? 0);
                $guildBotCount = (int)($guildSummary['bot_account_count'] ?? 0);
                if (!empty($backupView['selected_guild_id'])) {
                  $guildHint = 'Guild members: ' . $guildMemberCount . ' | Owning accounts: ' . $guildAccountCount . ' | Mix: ' . $guildHumanCount . ' human / ' . $guildBotCount . ' bot';
                  echo htmlspecialchars($guildHint);
                } else {
                  echo 'No guild selected.';
                }
              ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($selectedXferType === 'character' && !$isVmangosTransformRoute): ?>
          <div class="backup-admin__pair xfer-field xfer-field--target-account" data-entities="character">
            <label for="xfer_target_account_id">Target Account</label>
            <select id="xfer_target_account_id" name="target_account_id">
            <?php if (!empty($backupView['target_account_options'])): ?>
              <?php foreach ((array)$backupView['target_account_options'] as $accountOption): ?>
                <option value="<?php echo (int)$accountOption['id']; ?>"<?php if ((int)$accountOption['id'] === (int)$backupView['selected_target_account_id']) echo ' selected'; ?>>
                  <?php echo '#' . (int)$accountOption['id'] . ' - ' . htmlspecialchars((string)$accountOption['username']); ?>
                </option>
              <?php endforeach; ?>
            <?php else: ?>
              <option value="0">No accounts found on target realm</option>
            <?php endif; ?>
            </select>
          </div>
        <?php endif; ?>

        <?php if ($selectedXferType === 'character' && !$isVmangosTransformRoute): ?>
          <div class="backup-admin__pair xfer-field xfer-field--target-name" data-entities="character">
            <label for="target_character_name">Target Character Name</label>
            <input type="text" id="target_character_name" name="target_character_name" value="<?php echo htmlspecialchars((string)$target_character_name, ENT_QUOTES, 'UTF-8'); ?>" maxlength="12">
          </div>
        <?php endif; ?>
      </div>

      <div class="backup-admin__actions">
        <button type="submit">Refresh Selection</button>
      </div>
    </form>

    <form method="post" action="<?php echo htmlspecialchars((string)$backup_action_url, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$admin_backup_csrf_token); ?>">
      <input type="hidden" name="backup_action" value="create_xfer_package">
      <input type="hidden" name="n" value="admin">
      <input type="hidden" name="sub" value="backup">
      <input type="hidden" name="xfer_entity_type" value="<?php echo htmlspecialchars((string)$backupView['xfer_entity_type']); ?>">
      <input type="hidden" name="xfer_route" value="<?php echo htmlspecialchars((string)$backupView['selected_xfer_route_id']); ?>">
      <input type="hidden" name="source_realm_id" value="<?php echo (int)($backupView['source_realm_id'] ?? 0); ?>">
      <input type="hidden" name="source_account_id" value="<?php echo (int)($backupView['selected_account_id'] ?? 0); ?>">
      <input type="hidden" name="source_character_guid" value="<?php echo (int)($backupView['selected_character_guid'] ?? 0); ?>">
      <input type="hidden" name="source_guild_id" value="<?php echo (int)($backupView['selected_guild_id'] ?? 0); ?>">
      <input type="hidden" name="target_account_id" value="<?php echo (int)($backupView['selected_target_account_id'] ?? 0); ?>">
      <input type="hidden" name="target_character_name" value="<?php echo htmlspecialchars((string)$target_character_name, ENT_QUOTES, 'UTF-8'); ?>">
      <div class="backup-admin__actions">
        <?php
          $xferDisabled = empty($backupView['output_dir_writable']) || empty($backupView['has_target_realm']);
          if ((string)$backupView['xfer_entity_type'] === 'character') {
            $xferDisabled = $xferDisabled
              || empty($backupView['source_character_options'])
              || ((empty($backupView['selected_xfer_route']['target_is_vmangos']) || !empty($backupView['selected_xfer_route']['source_is_vmangos'])) && empty($backupView['target_account_options']));
          } elseif ((string)$backupView['xfer_entity_type'] === 'account') {
            $xferDisabled = $xferDisabled || empty($backupView['source_account_options']);
          } elseif ((string)$backupView['xfer_entity_type'] === 'guild') {
            $xferDisabled = $xferDisabled || empty($backupView['source_guild_options']);
          }
        ?>
        <button id="xfer_submit" type="submit"<?php if ($xferDisabled) echo ' disabled="disabled"'; ?>>Create Xfer SQL</button>
      </div>
    </form>
  </section>
</div>
<?php builddiv_end() ?>
