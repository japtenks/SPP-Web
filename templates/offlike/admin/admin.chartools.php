<?php builddiv_start(0, 'Character Rename') ?>

<div class="admin-tool-shell feature-shell">
  <div class="admin-tool-card feature-panel">
    <p class="admin-tool-kicker">Character Tools</p>
    <h2 class="admin-tool-title">Rename Character</h2>
    <p class="admin-tool-copy">Rename a character directly from the admin panel. The character must exist on the selected realm and must be offline before the rename can be applied.</p>
    <form action="<?php echo htmlspecialchars((string)$chartoolsActionUrl, ENT_QUOTES, 'UTF-8'); ?>" method="post" class="admin-tool-form-wrap">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$adminChartoolsCsrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
      <div class="admin-tool-form">
        <label for="rename_realm">Realm</label>
        <select id="rename_realm" name="realm" onchange="this.form.submit()">
          <?php foreach ($realmOptions as $realmOption): ?>
            <option value="<?php echo (int)$realmOption['id']; ?>"<?php if ((int)$realmOption['id'] === $selectedRealmId) echo ' selected'; ?>><?php echo htmlspecialchars((string)$realmOption['name']); ?></option>
          <?php endforeach; ?>
        </select>

        <label for="rename_account_id">Account</label>
        <select id="rename_account_id" name="account_id" onchange="this.form.submit()">
          <?php if (!empty($accountOptions)) { ?>
            <?php foreach ($accountOptions as $accountOption) { ?>
              <option value="<?php echo (int)$accountOption['id']; ?>"<?php if ((int)$accountOption['id'] === $selectedAccountId) echo ' selected'; ?>>
                <?php echo '#' . (int)$accountOption['id'] . ' - ' . htmlspecialchars((string)$accountOption['username']); ?>
              </option>
            <?php } ?>
          <?php } else { ?>
            <option value="0">No accounts available</option>
          <?php } ?>
        </select>

        <label for="rename_character_guid">Character</label>
        <select id="rename_character_guid" name="character_guid">
          <?php if (!empty($characterOptions)) { ?>
            <?php foreach ($characterOptions as $characterOption) { ?>
              <option value="<?php echo (int)$characterOption['guid']; ?>"<?php if ((int)$characterOption['guid'] === $selectedCharacterGuid) echo ' selected'; ?>>
                <?php
                echo htmlspecialchars((string)$characterOption['name']);
                if (!empty($characterOption['level'])) {
                    echo ' (Lvl ' . (int)$characterOption['level'] . ')';
                }
                ?>
              </option>
            <?php } ?>
          <?php } else { ?>
            <option value="0">No characters on this account</option>
          <?php } ?>
        </select>

        <label for="rename_new_name">New Character Name</label>
        <input type="text" id="rename_new_name" name="newname" maxlength="20" value="<?php echo htmlspecialchars((string)$renameNewName, ENT_QUOTES, 'UTF-8'); ?>" />
      </div>

      <div class="admin-tool-actions">
        <input type="submit" name="rename" value="Rename Character" <?php if (empty($characterOptions)) echo 'disabled="disabled"'; ?> />
      </div>
    </form>
    <?php echo $renameMessageHtml; ?>
  </div>

  <div class="admin-tool-card feature-panel">
    <p class="admin-tool-kicker">Character Tools</p>
    <h2 class="admin-tool-title">Race / Faction Change</h2>
    <p class="admin-tool-copy">Admin-only character race and faction changes. The selected race must be valid for the character class. Faction swaps still require the character to be guild-free and offline.</p>
    <?php if (!empty($selectedCharacterProfile)) { ?>
      <p class="admin-tool-meta">
        Current character:
        <?php
        echo htmlspecialchars((string)$selectedCharacterProfile['name']);
        echo ' | ' . htmlspecialchars((string)$selectedCharacterRaceLabel);
        echo ' ' . htmlspecialchars((string)$selectedCharacterClassLabel);
        echo ' | Level ' . (int)$selectedCharacterLevel;
        ?>
      </p>
    <?php } ?>
    <form action="<?php echo htmlspecialchars((string)$chartoolsActionUrl, ENT_QUOTES, 'UTF-8'); ?>" method="post" class="admin-tool-form-wrap">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$adminChartoolsCsrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
      <div class="admin-tool-form">
        <label for="race_realm">Realm</label>
        <select id="race_realm" name="realm" onchange="this.form.submit()">
          <?php foreach ($realmOptions as $realmOption): ?>
            <option value="<?php echo (int)$realmOption['id']; ?>"<?php if ((int)$realmOption['id'] === $selectedRealmId) echo ' selected'; ?>><?php echo htmlspecialchars((string)$realmOption['name']); ?></option>
          <?php endforeach; ?>
        </select>

        <label for="race_account_id">Account</label>
        <select id="race_account_id" name="account_id" onchange="this.form.submit()">
          <?php if (!empty($accountOptions)) { ?>
            <?php foreach ($accountOptions as $accountOption) { ?>
              <option value="<?php echo (int)$accountOption['id']; ?>"<?php if ((int)$accountOption['id'] === $selectedAccountId) echo ' selected'; ?>>
                <?php echo '#' . (int)$accountOption['id'] . ' - ' . htmlspecialchars((string)$accountOption['username']); ?>
              </option>
            <?php } ?>
          <?php } else { ?>
            <option value="0">No accounts available</option>
          <?php } ?>
        </select>

        <label for="race_character_guid">Character</label>
        <select id="race_character_guid" name="character_guid" onchange="this.form.submit()">
          <?php if (!empty($characterOptions)) { ?>
            <?php foreach ($characterOptions as $characterOption) { ?>
              <option value="<?php echo (int)$characterOption['guid']; ?>"<?php if ((int)$characterOption['guid'] === $selectedCharacterGuid) echo ' selected'; ?>>
                <?php
                echo htmlspecialchars((string)$characterOption['name']);
                if (!empty($characterOption['level'])) {
                    echo ' (Lvl ' . (int)$characterOption['level'] . ')';
                }
                ?>
              </option>
            <?php } ?>
          <?php } else { ?>
            <option value="0">No characters on this account</option>
          <?php } ?>
        </select>

        <label for="race_newrace">New Race</label>
        <select id="race_newrace" name="newrace" <?php if (empty($availableRaceOptions)) echo 'disabled="disabled"'; ?>>
          <?php if (!empty($availableRaceOptions)) { ?>
            <option value="0">Select a new race</option>
            <?php foreach ($availableRaceOptions as $raceOption) { ?>
              <option value="<?php echo (int)$raceOption['id']; ?>"<?php if ((int)$raceOption['id'] === (int)$selectedRaceId) echo ' selected'; ?>>
                <?php echo htmlspecialchars($raceOption['label'] . ' (' . $raceOption['faction'] . ')'); ?>
              </option>
            <?php } ?>
          <?php } else { ?>
            <option value="0">No alternate races available</option>
          <?php } ?>
        </select>
      </div>

      <div class="admin-tool-actions">
        <input type="submit" name="race_change" value="Apply Race / Faction Change" <?php if (empty($characterOptions) || empty($availableRaceOptions)) echo 'disabled="disabled"'; ?> />
      </div>
    </form>
    <?php echo $raceMessageHtml; ?>
  </div>

  <div class="admin-tool-card feature-panel">
    <p class="admin-tool-kicker">Character Tools</p>
    <h2 class="admin-tool-title">Send Item Pack</h2>
    <p class="admin-tool-copy">Mail an existing item pack directly to the selected character. This keeps the useful in-game delivery flow without the old donation admin page.</p>
    <form action="<?php echo htmlspecialchars((string)$chartoolsActionUrl, ENT_QUOTES, 'UTF-8'); ?>" method="post" class="admin-tool-form-wrap">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$adminChartoolsCsrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
      <div class="admin-tool-form">
        <label for="delivery_realm">Realm</label>
        <select id="delivery_realm" name="realm" onchange="this.form.submit()">
          <?php foreach ($realmOptions as $realmOption): ?>
            <option value="<?php echo (int)$realmOption['id']; ?>"<?php if ((int)$realmOption['id'] === $selectedRealmId) echo ' selected'; ?>><?php echo htmlspecialchars((string)$realmOption['name']); ?></option>
          <?php endforeach; ?>
        </select>

        <label for="delivery_account_id">Account</label>
        <select id="delivery_account_id" name="account_id" onchange="this.form.submit()">
          <?php if (!empty($accountOptions)) { ?>
            <?php foreach ($accountOptions as $accountOption) { ?>
              <option value="<?php echo (int)$accountOption['id']; ?>"<?php if ((int)$accountOption['id'] === $selectedAccountId) echo ' selected'; ?>>
                <?php echo '#' . (int)$accountOption['id'] . ' - ' . htmlspecialchars((string)$accountOption['username']); ?>
              </option>
            <?php } ?>
          <?php } else { ?>
            <option value="0">No accounts available</option>
          <?php } ?>
        </select>

        <label for="delivery_character_guid">Character</label>
        <select id="delivery_character_guid" name="character_guid" onchange="this.form.submit()">
          <?php if (!empty($characterOptions)) { ?>
            <?php foreach ($characterOptions as $characterOption) { ?>
              <option value="<?php echo (int)$characterOption['guid']; ?>"<?php if ((int)$characterOption['guid'] === $selectedCharacterGuid) echo ' selected'; ?>>
                <?php
                echo htmlspecialchars((string)$characterOption['name']);
                if (!empty($characterOption['level'])) {
                    echo ' (Lvl ' . (int)$characterOption['level'] . ')';
                }
                ?>
              </option>
            <?php } ?>
          <?php } else { ?>
            <option value="0">No characters on this account</option>
          <?php } ?>
        </select>

        <label for="donation_pack_id">Item Pack</label>
        <select id="donation_pack_id" name="donation_pack_id">
          <?php if (!empty($donationPackOptions)) { ?>
            <?php foreach ($donationPackOptions as $donationPackOption) { ?>
              <option value="<?php echo htmlspecialchars((string)$donationPackOption['id'], ENT_QUOTES, 'UTF-8'); ?>"<?php if ((string)($donationPackOption['id'] ?? '') === (string)$selectedDonationPackId) echo ' selected'; ?>>
                <?php
                $packDescription = trim((string)($donationPackOption['description'] ?? 'Untitled pack'));
                if (($donationPackOption['kind'] ?? 'database') === 'profession_pack') {
                    $packLabel = '[Profession] ' . $packDescription;
                } else {
                    $packLabel = '#' . (int)$donationPackOption['id'] . ' - ' . $packDescription;
                }
                if (!empty($donationPackOption['donation']) || !empty($donationPackOption['currency'])) {
                    $packLabel .= ' (' . trim((string)$donationPackOption['donation'] . ' ' . (string)($donationPackOption['currency'] ?? '')) . ')';
                }
                echo htmlspecialchars($packLabel);
                ?>
              </option>
            <?php } ?>
          <?php } else { ?>
            <option value="0">No item packs configured</option>
          <?php } ?>
        </select>
      </div>

      <div class="admin-tool-actions">
        <input type="submit" name="send_pack" value="Send Item Pack" <?php if (empty($characterOptions) || empty($donationPackOptions)) echo 'disabled="disabled"'; ?> />
      </div>
    </form>

    <?php echo $deliveryMessageHtml; ?>
  </div>

  <div class="admin-tool-card feature-panel">
    <p class="admin-tool-kicker">Character Tools</p>
    <h2 class="admin-tool-title">Full Package</h2>
    <p class="admin-tool-copy">Offline-only character bootstrap. This prepares the selected character for a class-specific role package, sets the realm level cap, queues spell and talent resets for next login, grants any profession required by the selected BIS set, and mails the matching playerbot BIS gear for the chosen phase.</p>
    <?php if (!empty($selectedCharacterProfile)) { ?>
      <p class="admin-tool-meta">
        Current character:
        <?php
        echo htmlspecialchars((string)$selectedCharacterProfile['name']);
        echo ' | Class ' . htmlspecialchars((string)$selectedCharacterClassLabel);
        echo ' | Level ' . (int)$selectedCharacterLevel;
        echo ' | Target cap ' . (int)$selectedRealmTargetLevelCap;
        ?>
      </p>
    <?php } ?>
    <form action="<?php echo htmlspecialchars((string)$chartoolsActionUrl, ENT_QUOTES, 'UTF-8'); ?>" method="post" class="admin-tool-form-wrap">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$adminChartoolsCsrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
      <div class="admin-tool-form">
        <label for="fullpkg_realm">Realm</label>
        <select id="fullpkg_realm" name="realm" onchange="this.form.submit()">
          <?php foreach ($realmOptions as $realmOption): ?>
            <option value="<?php echo (int)$realmOption['id']; ?>"<?php if ((int)$realmOption['id'] === $selectedRealmId) echo ' selected'; ?>><?php echo htmlspecialchars((string)$realmOption['name']); ?></option>
          <?php endforeach; ?>
        </select>

        <label for="fullpkg_account_id">Account</label>
        <select id="fullpkg_account_id" name="account_id" onchange="this.form.submit()">
          <?php if (!empty($accountOptions)) { ?>
            <?php foreach ($accountOptions as $accountOption) { ?>
              <option value="<?php echo (int)$accountOption['id']; ?>"<?php if ((int)$accountOption['id'] === $selectedAccountId) echo ' selected'; ?>>
                <?php echo '#' . (int)$accountOption['id'] . ' - ' . htmlspecialchars((string)$accountOption['username']); ?>
              </option>
            <?php } ?>
          <?php } else { ?>
            <option value="0">No accounts available</option>
          <?php } ?>
        </select>

        <label for="fullpkg_character_guid">Character</label>
        <select id="fullpkg_character_guid" name="character_guid" onchange="this.form.submit()">
          <?php if (!empty($characterOptions)) { ?>
            <?php foreach ($characterOptions as $characterOption) { ?>
              <option value="<?php echo (int)$characterOption['guid']; ?>"<?php if ((int)$characterOption['guid'] === $selectedCharacterGuid) echo ' selected'; ?>>
                <?php
                echo htmlspecialchars((string)$characterOption['name']);
                if (!empty($characterOption['level'])) {
                    echo ' (Lvl ' . (int)$characterOption['level'] . ')';
                }
                ?>
              </option>
            <?php } ?>
          <?php } else { ?>
            <option value="0">No characters on this account</option>
          <?php } ?>
        </select>

        <label for="fullpkg_phase">BIS Phase</label>
        <select id="fullpkg_phase" name="full_package_phase" onchange="this.form.submit()" <?php if (empty($availableFullPackagePhases)) echo 'disabled="disabled"'; ?>>
          <?php if (!empty($availableFullPackagePhases)) { ?>
            <?php foreach ($availableFullPackagePhases as $phaseOption) { ?>
              <option value="<?php echo htmlspecialchars((string)$phaseOption['id'], ENT_QUOTES, 'UTF-8'); ?>"<?php if ((string)($phaseOption['id'] ?? '') === (string)$selectedFullPackagePhaseId) echo ' selected'; ?>>
                <?php echo htmlspecialchars((string)$phaseOption['label']); ?>
              </option>
            <?php } ?>
          <?php } else { ?>
            <option value="">No phase gear data available for this role</option>
          <?php } ?>
        </select>

        <label for="fullpkg_role">Role Package</label>
        <select id="fullpkg_role" name="full_package_role" <?php if (empty($availableFullPackageRoles)) echo 'disabled="disabled"'; ?>>
          <?php if (!empty($availableFullPackageRoles)) { ?>
            <?php foreach ($availableFullPackageRoles as $roleOption) { ?>
              <option value="<?php echo htmlspecialchars((string)$roleOption['id'], ENT_QUOTES, 'UTF-8'); ?>"<?php if ((string)$selectedFullPackageRoleId === (string)$roleOption['id']) echo ' selected'; ?>>
                <?php echo htmlspecialchars((string)$roleOption['label']); ?>
              </option>
            <?php } ?>
          <?php } else { ?>
            <option value="">No role packages available for this class and phase</option>
          <?php } ?>
        </select>
      </div>

      <div class="admin-tool-actions">
        <input type="submit" name="apply_full_package" value="Apply Full Package" <?php if (empty($characterOptions) || empty($availableFullPackageRoles) || empty($availableFullPackagePhases)) echo 'disabled="disabled"'; ?> />
      </div>
    </form>

    <?php echo $fullPackageMessageHtml; ?>
  </div>
</div>
<?php builddiv_end() ?>
