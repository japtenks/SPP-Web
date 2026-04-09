<br>
<?php builddiv_start(1, 'Backup') ?>
<div class="backup-admin" data-backup-lookup-url="<?php echo htmlspecialchars((string)$backup_lookup_url, ENT_QUOTES, 'UTF-8'); ?>">
  <section class="backup-admin__panel">
    <p class="backup-admin__eyebrow">Backup Tooling</p>
    <h2 class="backup-admin__title">Backup And Xfer Packages</h2>
    <p class="backup-admin__copy">This page now builds SQL packages for controlled backups and cross-realm transfers. It models the character side after CMaNGOS `pdump` ideas, but writes website-generated SQL files into a local cache folder so you can review and apply them deliberately.</p>
    <div class="backup-admin__grid">
      <div class="backup-admin__mini">
        <strong><?php echo htmlspecialchars((string)($backupView['source_realm_name'] ?? '')); ?></strong>
        <span>Current source realm</span>
      </div>
      <div class="backup-admin__mini">
        <strong><?php echo htmlspecialchars((string)($backupView['target_realm_name'] ?? 'Not selected')); ?></strong>
        <span>Current target realm</span>
      </div>
      <div class="backup-admin__mini">
        <strong><?php echo !empty($backupView['output_dir_writable']) ? 'Writable' : 'Locked'; ?></strong>
        <span><?php echo htmlspecialchars((string)($backupView['output_dir'] ?? '')); ?></span>
      </div>
    </div>
  </section>

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

  <section class="backup-admin__panel">
    <p class="backup-admin__eyebrow">Recent Packages</p>
    <h2 class="backup-admin__title">Cache Files</h2>
    <p class="backup-admin__note">Generated backup and xfer packages are stored in the local cache folder and can be downloaded again here.</p>

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
    <p class="backup-admin__eyebrow">Backup Export</p>
    <h2 class="backup-admin__title">Create Backup SQL</h2>
    <p class="backup-admin__note">Backup exports preserve the selected entity as-is from the source realm. Use this for safe snapshots of a single character, full account, or guild before doing maintenance or promotion work.</p>

    <form method="post" action="<?php echo htmlspecialchars((string)$backup_action_url, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$admin_backup_csrf_token); ?>">
      <input type="hidden" name="backup_action" value="create_backup_package">
      <div class="backup-admin__form">
        <label for="backup_source_realm_id">Source Realm</label>
        <select id="backup_source_realm_id" name="source_realm_id">
          <?php foreach ((array)$backupView['realm_options'] as $realmOption): ?>
            <option value="<?php echo (int)$realmOption['id']; ?>"<?php if ((int)$realmOption['id'] === (int)$backupView['source_realm_id']) echo ' selected'; ?>>
              <?php echo htmlspecialchars((string)$realmOption['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label for="backup_entity_type">Entity Type</label>
        <select id="backup_entity_type" name="backup_entity_type">
          <?php foreach ((array)$backupView['entity_options'] as $entityKey => $entityLabel): ?>
            <option value="<?php echo htmlspecialchars((string)$entityKey); ?>"<?php if ((string)$entityKey === (string)$backupView['backup_entity_type']) echo ' selected'; ?>>
              <?php echo htmlspecialchars((string)$entityLabel); ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label for="backup_source_account_id">Source Account</label>
        <select id="backup_source_account_id" name="source_account_id">
          <?php foreach ((array)$backupView['source_account_options'] as $accountOption): ?>
            <option value="<?php echo (int)$accountOption['id']; ?>"<?php if ((int)$accountOption['id'] === (int)$backupView['selected_account_id']) echo ' selected'; ?>>
              <?php echo '#' . (int)$accountOption['id'] . ' - ' . htmlspecialchars((string)$accountOption['username']); ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label for="backup_source_character_guid">Character</label>
        <select id="backup_source_character_guid" name="source_character_guid">
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

        <label for="backup_source_guild_id">Guild</label>
        <select id="backup_source_guild_id" name="source_guild_id">
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
      </div>

      <div class="backup-admin__actions">
        <input type="submit" value="Create Backup SQL" <?php if (empty($backupView['output_dir_writable'])) echo 'disabled="disabled"'; ?>>
      </div>
    </form>
  </section>

  <section class="backup-admin__panel">
    <p class="backup-admin__eyebrow">Realm Xfer</p>
    <h2 class="backup-admin__title">Create Xfer SQL</h2>
    <p class="backup-admin__note">Xfer packages are target-ready SQL bundles for `Classic -> TBC -> WotLK` style promotion. Characters are remapped to new GUID ranges on the target realm, accounts reuse an existing username when possible, and guild packages assume the member characters have already been transferred with the same names. vMaNGOS account SQL stays separate from character SQL, and vMaNGOS character xfer now requires a live schema validation pass before a package is trusted.</p>
    <?php if (!empty($backupView['xfer_route_help'])): ?>
      <p class="backup-admin__note" id="xfer_route_help"><?php echo htmlspecialchars((string)$backupView['xfer_route_help']); ?></p>
    <?php endif; ?>

    <form method="post" action="<?php echo htmlspecialchars((string)$backup_action_url, ENT_QUOTES, 'UTF-8'); ?>">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$admin_backup_csrf_token); ?>">
      <input type="hidden" name="backup_action" value="create_xfer_package">
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

        <div class="backup-admin__pair xfer-field" data-entities="character,account">
          <label for="xfer_source_account_id">Source Account</label>
          <select id="xfer_source_account_id" name="source_account_id">
          <?php foreach ((array)$backupView['source_account_options'] as $accountOption): ?>
            <option value="<?php echo (int)$accountOption['id']; ?>"<?php if ((int)$accountOption['id'] === (int)$backupView['selected_account_id']) echo ' selected'; ?>>
              <?php echo '#' . (int)$accountOption['id'] . ' - ' . htmlspecialchars((string)$accountOption['username']); ?>
            </option>
          <?php endforeach; ?>
          </select>
        </div>

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
        </div>

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
        </div>

        <div class="backup-admin__pair xfer-field" data-entities="character">
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

        <div class="backup-admin__pair xfer-field" data-entities="character">
          <label for="target_character_name">Target Character Name</label>
          <input type="text" id="target_character_name" name="target_character_name" value="<?php echo htmlspecialchars((string)$target_character_name, ENT_QUOTES, 'UTF-8'); ?>" maxlength="12">
        </div>
      </div>

      <div class="backup-admin__actions">
        <input type="submit" value="Create Xfer SQL" <?php if (empty($backupView['output_dir_writable']) || empty($backupView['has_target_realm'])) echo 'disabled="disabled"'; ?>>
      </div>
    </form>
  </section>
</div>
<?php builddiv_end() ?>
