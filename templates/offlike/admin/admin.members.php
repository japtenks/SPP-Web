<?php builddiv_start(1, 'Accounts') ?>
<div class="admin-members feature-shell">
<?php
$memberAccountId = (int)($accountId ?? 0);
$currentCharFilter = (string)($currentCharFilter ?? '');
$memberIsSuperAdmin = !empty($isSuperAdmin);
?>
<?php if ($memberAccountId > 0 && !empty($profile)) { ?>
  <?php
    $characterRealmGroups = $characters_by_realm ?? array();
    $allUserCharacters = $all_userchars ?? ($userchars ?? array());
    $selectedToolRealmId = (int)($selected_tool_realm_id ?? 0);
    $toolRealmChars = $tool_realm_chars ?? array();
  ?>
  <div class="admin-panel feature-hero">
    <p class="admin-subheading">Member Profile</p>
    <h2 class="admin-members__heading"><?php echo htmlspecialchars($profile['username']); ?></h2>
    <div class="admin-status <?php echo !empty($active) ? 'banned' : 'active'; ?>">
      <?php echo !empty($active) ? 'Banned' : 'Active'; ?>
    </div>
    <div class="admin-meta">
      <div class="admin-meta-label">Forum Posts</div>
      <div class="admin-meta-value"><?php echo (int)$profile['forum_posts']; ?></div>

      <div class="admin-meta-label">Registered</div>
      <div class="admin-meta-value"><?php echo htmlspecialchars($profile['joindate']); ?></div>

      <div class="admin-meta-label">Registration IP</div>
      <div class="admin-meta-value"><?php echo htmlspecialchars($profile['registration_ip']); ?></div>

      <div class="admin-meta-label">Last Login (Game)</div>
      <div class="admin-meta-value"><?php echo htmlspecialchars($profile['last_login']); ?></div>

      <div class="admin-meta-label">Forum Group</div>
      <div class="admin-meta-value"><?php echo htmlspecialchars($allgroups[$profile['g_id']] ?? 'Unassigned'); ?></div>

      <div class="admin-meta-label">Expansion</div>
      <div class="admin-meta-value">
        <?php
        $expansionNames = array(0 => 'Classic', 1 => 'TBC', 2 => 'WotLK');
        echo htmlspecialchars($expansionNames[(int)$profile['expansion']] ?? 'Classic');
        ?>
      </div>

      <div class="admin-meta-label">Auth Realm</div>
      <div class="admin-meta-value"><?php echo htmlspecialchars((string)($authRealmName ?? ('Realm ' . (int)($activeRealmId ?? 0)))); ?></div>
    </div>

    <div class="admin-actions">
      <a class="admin-btn danger" href="<?php echo htmlspecialchars(spp_admin_members_action_url(array('n' => 'admin', 'sub' => 'members', 'id' => $memberAccountId, 'action' => 'dodeleteacc'))); ?>" data-confirm="Are you sure?">Delete Account</a>
      <?php if (!empty($active)) { ?>
        <a class="admin-btn" href="<?php echo htmlspecialchars(spp_admin_members_action_url(array('n' => 'admin', 'sub' => 'members', 'id' => $memberAccountId, 'action' => 'unban'))); ?>">Unban Account</a>
      <?php } else { ?>
        <a class="admin-btn danger" href="<?php echo htmlspecialchars(spp_admin_members_action_url(array('n' => 'admin', 'sub' => 'members', 'id' => $memberAccountId, 'action' => 'ban'))); ?>">Ban Account</a>
      <?php } ?>
      <a class="admin-btn" href="index.php?n=admin&sub=members">Back to Members</a>
    </div>
  </div>

  <div class="admin-detail-grid">
    <div class="admin-character-list feature-panel">
      <p class="admin-subheading">Realm Characters</p>
      <h3 class="admin-members__heading admin-members__heading--compact">Characters</h3>
      <p class="admin-members__note admin-members__note--status-summary"><?php echo (int)($onlineCharacterCount ?? 0); ?> / <?php echo count($allUserCharacters); ?> online</p>
      <?php if (!empty($characterRealmGroups)) { ?>
        <div class="admin-realm-groups">
          <?php
          $MANG = new Mangos;
          foreach ($characterRealmGroups as $realmGroupId => $realmChars) {
              $realmGroupOnline = 0;
              foreach ($realmChars as $realmCharCount) {
                  if (!empty($realmCharCount['online'])) {
                      $realmGroupOnline++;
                  }
              }
              $realmGroupName = $realmChars[0]['realm_name'] ?? ('Realm ' . (int)$realmGroupId);
          ?>
            <div class="admin-realm-group">
              <div class="admin-realm-heading">
                <div class="admin-realm-title"><?php echo htmlspecialchars($realmGroupName); ?></div>
                <div class="admin-realm-summary"><?php echo $realmGroupOnline; ?> / <?php echo count($realmChars); ?> online</div>
              </div>
              <ul>
                <?php foreach ($realmChars as $char) {
                    $charUrl = 'index.php?n=server&sub=character&realm=' . (int)($char['realm_id'] ?? $realmGroupId) . '&character=' . urlencode((string)$char['name']);
                    $statusClass = !empty($char['online']) ? 'online' : 'offline';
                    $statusTitle = !empty($char['online']) ? 'Online' : 'Offline';
                    echo '<li>';
                    echo '<span class="admin-character-status-dot ' . $statusClass . '" title="' . $statusTitle . '"></span>';
                    echo '<span class="admin-character-entry">';
                    echo '<a href="' . $charUrl . '">' . htmlspecialchars((string)$char['name']) . '</a> &middot; Level ' . (int)$char['level'] . ' &middot; ' .
                        htmlspecialchars($MANG->characterInfoByID['character_race'][$char['race']] ?? '') . ' ' .
                        htmlspecialchars($MANG->characterInfoByID['character_class'][$char['class']] ?? '');
                    echo '</span>';
                    echo '</li>';
                } ?>
              </ul>
            </div>
          <?php }
          unset($MANG);
          ?>
        </div>
      <?php } else { ?>
        <p class="admin-members__note">This account does not have any characters on any configured realm.</p>
      <?php } ?>
    </div>

    <div class="admin-form-panel feature-panel">
      <?php if (!empty($profile['is_bot_account'])) { ?>
        <p class="admin-subheading">Bot Profiles</p>
        <h3 class="admin-members__heading admin-members__heading--compact">Character Signatures</h3>
        <form method="post" action="<?php echo htmlspecialchars(spp_admin_members_action_url(array('n' => 'admin', 'sub' => 'members', 'id' => $memberAccountId, 'action' => 'setbotsignatures'))); ?>" class="admin-form-stack">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($admin_members_csrf_token ?? spp_csrf_token('admin_members'))); ?>">
          <div class="admin-signature-realm-groups">
            <?php if (!empty($characterRealmGroups)) { ?>
              <?php foreach ($characterRealmGroups as $realmGroupId => $realmChars) { ?>
                <?php $realmGroupName = $realmChars[0]['realm_name'] ?? ('Realm ' . (int)$realmGroupId); ?>
                <div class="admin-signature-realm-group">
                  <div class="admin-signature-realm-title"><?php echo htmlspecialchars((string)$realmGroupName); ?></div>
                  <div class="admin-signature-stack">
                    <?php foreach ($realmChars as $char) { ?>
                      <?php $charGuid = (int)($char['guid'] ?? 0); $charRealmId = (int)($char['realm_id'] ?? 0); $charSignatureKey = $charRealmId . ':' . $charGuid; ?>
                      <div class="admin-signature-row">
                        <label for="character_signature_<?php echo $charRealmId; ?>_<?php echo $charGuid; ?>"><?php echo htmlspecialchars((string)$char['name']); ?></label>
                        <input
                          type="text"
                          id="character_signature_<?php echo $charRealmId; ?>_<?php echo $charGuid; ?>"
                          name="character_signature[<?php echo $charSignatureKey; ?>]"
                          maxlength="255"
                          value="<?php echo htmlspecialchars((string)($profile['character_signatures'][$charSignatureKey] ?? '')); ?>"
                        />
                      </div>
                    <?php } ?>
                  </div>
                </div>
              <?php } ?>
            <?php } else { ?>
              <div class="admin-form-help">This bot account has no characters on any configured realm.</div>
            <?php } ?>
          </div>
          <div class="admin-form-help">Set one forum signature line per bot character. These signatures follow the character that posts.</div>
          <div class="admin-form-actions">
            <input type="submit" value="Set Signatures" />
          </div>
        </form>
        <div class="admin-spacer"></div>
      <?php } ?>
      <?php if (empty($profile['is_bot_account'])) { ?>
        <p class="admin-subheading">Security</p>
        <h3 class="admin-members__heading admin-members__heading--compact">Change Password</h3>
        <form method="post" action="<?php echo htmlspecialchars(spp_admin_members_action_url(array('n' => 'admin', 'sub' => 'members', 'id' => $memberAccountId, 'action' => 'changepass'))); ?>" class="admin-form-stack">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($admin_members_csrf_token ?? spp_csrf_token('admin_members'))); ?>">
          <input type="hidden" name="character_realm_id" value="<?php echo $selectedToolRealmId; ?>">
          <div class="admin-form-grid">
            <label for="member_new_pass">New Password</label>
            <input type="password" id="member_new_pass" name="new_pass" />

            <label for="member_confirm_new_pass">Confirm Password</label>
            <input type="password" id="member_confirm_new_pass" name="confirm_new_pass" />
          </div>
          <div class="admin-form-actions">
            <input type="submit" value="Change Password" />
          </div>
        </form>
      <?php } ?>
    </div>
  </div>

  <div class="admin-form-panel feature-panel">
    <p class="admin-subheading">Character Tools</p>
    <h3 class="admin-members__heading admin-members__heading--compact">Character Transfer And Cleanup</h3>
    <form method="get" action="index.php" class="admin-form-stack is-gap-bottom">
      <input type="hidden" name="n" value="admin" />
      <input type="hidden" name="sub" value="members" />
      <input type="hidden" name="id" value="<?php echo $memberAccountId; ?>" />
      <div class="admin-form-grid">
        <label for="character_realm_id">Realm</label>
        <select id="character_realm_id" name="character_realm_id" data-auto-submit="change">
          <?php foreach (($characterRealmGroups ?? array()) as $realmGroupId => $realmChars): ?>
            <?php $realmGroupName = $realmChars[0]['realm_name'] ?? ('Realm ' . (int)$realmGroupId); ?>
            <option value="<?php echo (int)$realmGroupId; ?>"<?php if ((int)$realmGroupId === $selectedToolRealmId) echo ' selected'; ?>>
              <?php echo htmlspecialchars($realmGroupName . ' (' . count($realmChars) . ')'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="admin-form-help">Choose which realm's characters you want the admin tools below to operate on.</div>
    </form>
    <div class="admin-tool-stack">
      <form method="post" action="<?php echo htmlspecialchars(spp_admin_members_action_url(array('n' => 'admin', 'sub' => 'members', 'id' => $memberAccountId, 'action' => 'transferchar'))); ?>" class="admin-form-stack">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($admin_members_csrf_token ?? spp_csrf_token('admin_members'))); ?>">
        <input type="hidden" name="character_realm_id" value="<?php echo $selectedToolRealmId; ?>">
        <div class="admin-form-grid">
          <label for="transfer_character_guid">Character</label>
          <select id="transfer_character_guid" name="transfer_character_guid">
            <?php
            if (!empty($toolRealmChars)) {
                foreach ($toolRealmChars as $char) {
                    echo '<option value="' . (int)($char['guid'] ?? 0) . '">';
                    echo htmlspecialchars((string)($char['name'] ?? 'Unknown'));
                    if (!empty($char['level'])) {
                        echo ' (Lvl ' . (int)$char['level'] . ')';
                    }
                    echo '</option>';
                }
            } else {
            ?>
              <option value="0">No characters available</option>
            <?php } ?>
          </select>

          <label for="target_account_id">Target Account</label>
          <select id="target_account_id" name="target_account_id">
            <?php if (!empty($eligibleTransferAccounts)) { ?>
              <?php foreach ($eligibleTransferAccounts as $eligibleAccount) { ?>
                <option value="<?php echo (int)($eligibleAccount['id'] ?? 0); ?>">
                  <?php echo '#' . (int)($eligibleAccount['id'] ?? 0) . ' - ' . htmlspecialchars((string)($eligibleAccount['username'] ?? 'Unknown')); ?>
                </option>
              <?php } ?>
            <?php } else { ?>
              <option value="0">No eligible human accounts available</option>
            <?php } ?>
          </select>
        </div>
        <?php
          $sourceOnlineForTransfer = false;
          $selectedCharacterOnline = false;
          if (!empty($toolRealmChars)) {
              foreach ($toolRealmChars as $toolRealmChar) {
                  if (!empty($toolRealmChar['online'])) {
                      $sourceOnlineForTransfer = true;
                  }
              }
          }
          $selectedTransferCharacter = $selected_transfer_character ?? null;
          if (!empty($selectedTransferCharacter['online'])) {
              $selectedCharacterOnline = true;
          }
        ?>
        <div class="admin-preflight">
          <div class="admin-preflight-card">
            <div class="admin-preflight-label">Source Account</div>
            <div class="admin-preflight-value <?php echo $sourceOnlineForTransfer ? 'online' : 'offline'; ?>"><?php echo $sourceOnlineForTransfer ? 'Online' : 'Offline'; ?></div>
          </div>
          <div class="admin-preflight-card">
            <div class="admin-preflight-label">Selected Character</div>
            <div class="admin-preflight-value <?php echo $selectedCharacterOnline ? 'online' : 'offline'; ?>"><?php echo $selectedCharacterOnline ? 'Online' : 'Offline'; ?></div>
          </div>
          <div class="admin-preflight-card">
            <div class="admin-preflight-label">Target Account</div>
            <div class="admin-preflight-value">Checked on submit</div>
          </div>
        </div>
        <div class="admin-form-help">Move a character to another account on the selected realm. Forum ownership follows the destination account. The transfer only goes through when the source account, destination account, and selected character are all offline.</div>
        <div class="admin-form-actions">
          <input type="submit" value="Transfer Character" <?php if (empty($toolRealmChars) || empty($eligibleTransferAccounts)) echo 'disabled="disabled"'; ?> data-confirm="Transfer this character to the target account?" />
        </div>
      </form>
      <div class="admin-tool-divider"></div>
      <form method="post" action="<?php echo htmlspecialchars(spp_admin_members_action_url(array('n' => 'admin', 'sub' => 'members', 'id' => $memberAccountId, 'action' => 'deletechar'))); ?>" class="admin-form-stack">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($admin_members_csrf_token ?? spp_csrf_token('admin_members'))); ?>">
        <input type="hidden" name="character_realm_id" value="<?php echo $selectedToolRealmId; ?>">
        <div class="admin-form-grid">
          <label for="delete_character_guid">Delete Character</label>
          <select id="delete_character_guid" name="delete_character_guid">
            <?php
            if (!empty($toolRealmChars)) {
                foreach ($toolRealmChars as $char) {
                    echo '<option value="' . (int)($char['guid'] ?? 0) . '">';
                    echo htmlspecialchars((string)($char['name'] ?? 'Unknown'));
                    if (!empty($char['level'])) {
                        echo ' (Lvl ' . (int)$char['level'] . ')';
                    }
                    echo '</option>';
                }
            } else {
            ?>
              <option value="0">No characters available</option>
            <?php } ?>
          </select>
        </div>
        <div class="admin-form-help">Deletes the selected character from the active realm and clears that character from the accountÃ¢â‚¬â„¢s selected-character slot if needed.</div>
        <div class="admin-form-actions">
          <input type="submit" value="Delete Character" class="danger" <?php if (empty($toolRealmChars)) echo 'disabled="disabled"'; ?> data-confirm="Delete this character from the selected realm? This cannot be undone." />
        </div>
      </form>
    </div>
  </div>

  <div class="admin-detail-grid">
    <div class="admin-form-panel feature-panel">
        <p class="admin-subheading">Account Controls</p>
        <h3 class="admin-members__heading admin-members__heading--compact">Game Account</h3>
        <form method="post" action="<?php echo htmlspecialchars(spp_admin_members_action_url(array('n' => 'admin', 'sub' => 'members', 'id' => $memberAccountId, 'action' => 'change'))); ?>" class="admin-form-stack">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($admin_members_csrf_token ?? spp_csrf_token('admin_members'))); ?>">
          <input type="hidden" name="character_realm_id" value="<?php echo $selectedToolRealmId; ?>">
          <div class="admin-form-grid">
            <?php if ($memberIsSuperAdmin) { ?>
              <label for="profile_gmlevel">GM Level</label>
              <input type="text" id="profile_gmlevel" name="profile[gmlevel]" value="<?php echo htmlspecialchars($profile['gmlevel']); ?>" />
            <?php } ?>

            <label for="profile_expansion">Account Expansion</label>
            <select id="profile_expansion" name="profile[expansion]">
              <option value="0"<?php if ((int)$profile['expansion'] === 0) echo ' selected'; ?>>Classic</option>
              <option value="1"<?php if ((int)$profile['expansion'] === 1) echo ' selected'; ?>>TBC</option>
              <option value="2"<?php if ((int)$profile['expansion'] === 2) echo ' selected'; ?>>WotLK</option>
            </select>
          </div>
          <div class="admin-form-actions">
            <input type="reset" value="Reset" />
            <input type="submit" value="Save Changes" />
          </div>
        </form>
    </div>

    <div class="admin-form-panel feature-panel">
      <?php if (!empty($profile['is_bot_account'])) { ?>
        <p class="admin-subheading">Security</p>
        <h3 class="admin-members__heading admin-members__heading--compact">Change Password</h3>
        <form method="post" action="<?php echo htmlspecialchars(spp_admin_members_action_url(array('n' => 'admin', 'sub' => 'members', 'id' => $memberAccountId, 'action' => 'changepass'))); ?>" class="admin-form-stack">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($admin_members_csrf_token ?? spp_csrf_token('admin_members'))); ?>">
          <input type="hidden" name="character_realm_id" value="<?php echo $selectedToolRealmId; ?>">
          <div class="admin-form-grid">
            <label for="member_new_pass_bot">New Password</label>
            <input type="password" id="member_new_pass_bot" name="new_pass" />

            <label for="member_confirm_new_pass_bot">Confirm Password</label>
            <input type="password" id="member_confirm_new_pass_bot" name="confirm_new_pass" />
          </div>
          <div class="admin-form-actions">
            <input type="submit" value="Change Password" />
          </div>
        </form>
      <?php } else { ?>
        <p class="admin-subheading">Website Profile</p>
        <h3 class="admin-members__heading admin-members__heading--compact">Forum Settings</h3>
        <form method="post" action="<?php echo htmlspecialchars(spp_admin_members_action_url(array('n' => 'admin', 'sub' => 'members', 'id' => $memberAccountId, 'action' => 'change2'))); ?>" enctype="multipart/form-data" class="admin-form-stack">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($admin_members_csrf_token ?? spp_csrf_token('admin_members'))); ?>">
          <div class="admin-form-grid">
            <label for="profile_gid">Group</label>
            <select id="profile_gid" name="profile[g_id]">
              <?php foreach ($allgroups as $group_id => $group_name) { ?>
                <option value="<?php echo (int)$group_id; ?>"<?php if ((int)$profile['g_id'] === (int)$group_id) echo ' selected'; ?>><?php echo htmlspecialchars($group_name); ?></option>
              <?php } ?>
            </select>

            <?php if ((int)spp_config_generic('change_template', 0) === 1) { ?>
              <label for="profile_theme">Theme</label>
              <select id="profile_theme" name="profile[theme]">
                <?php
                $i = 0;
                foreach ((array)($templateOptions ?? array()) as $template) {
                    echo '<option value="' . (int)$i . '"' . ((int)$profile['theme'] === $i ? ' selected' : '') . '>' . htmlspecialchars($template) . '</option>';
                    $i++;
                }
                ?>
              </select>
            <?php } ?>

            <label for="profile_hideprofile">Hide Profile</label>
            <select id="profile_hideprofile" name="profile[hideprofile]">
              <option value="0"<?php if ((int)$profile['hideprofile'] === 0) echo ' selected'; ?>>No</option>
              <option value="1"<?php if ((int)$profile['hideprofile'] === 1) echo ' selected'; ?>>Yes</option>
            </select>

            <label for="profile_signature">Signature</label>
            <textarea id="profile_signature" name="profile[signature]" maxlength="255"><?php echo htmlspecialchars($profile['signature']); ?></textarea>

            <div class="admin-field-label">Avatar</div>
            <div>
              <?php if ($profile['avatar']) { ?>
                <div class="admin-avatar-preview">
                  <img src="uploads/avatars/<?php echo htmlspecialchars($profile['avatar']); ?>" alt="Avatar" />
                  <div>
                    <input type="hidden" name="avatarfile" value="<?php echo htmlspecialchars($profile['avatar']); ?>">
                    <label><input type="checkbox" name="deleteavatar" value="1"> Delete current avatar</label>
                  </div>
                </div>
              <?php } else { ?>
                <div class="admin-form-help">Max file size: <?php echo (int)spp_config_generic('max_avatar_file', 102400); ?> bytes. Max resolution: <?php echo htmlspecialchars((string)spp_config_generic('max_avatar_size', '64x64')); ?>.</div>
                <input type="file" name="avatar" />
              <?php } ?>
            </div>
          </div>
          <div class="admin-form-actions">
            <input type="reset" value="Reset" />
            <input type="submit" value="Save Profile" />
          </div>
        </form>
      <?php } ?>
    </div>
  </div>

<?php } else { ?>
  <div class="admin-list-toolbar feature-hero">
    <div class="admin-members-toolbar__row">
      <form action="index.php?n=admin&sub=members" method="post" class="admin-members-toolbar__form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(spp_csrf_token('admin_members')); ?>">
        <div class="admin-members-toolbar__group">
          <label class="admin-field-label" for="search_member">Search Username</label>
          <input type="text" id="search_member" name="search_member" placeholder="Account name" />
        </div>
        <button type="submit">Search</button>
      </form>
    </div>

    <div class="admin-members-toolbar__row">
      <form method="get" action="index.php" class="admin-members-toolbar__form">
        <input type="hidden" name="n" value="admin" />
        <input type="hidden" name="sub" value="members" />

        <div class="admin-members-toolbar__group">
          <label class="admin-field-label" for="show_bots">Account Scope</label>
          <select id="show_bots" name="show_bots">
            <option value="1"<?php if (($accountScope ?? ($includeBots ? '1' : '0')) === '1') echo ' selected'; ?>>Humans + Bots</option>
            <option value="0"<?php if (($accountScope ?? ($includeBots ? '1' : '0')) === '0') echo ' selected'; ?>>Humans Only</option>
            <option value="bots_offline"<?php if (($accountScope ?? ($includeBots ? '1' : '0')) === 'bots_offline') echo ' selected'; ?>>Bot Accounts All Offline</option>
          </select>
        </div>

        <div class="admin-members-toolbar__group">
          <label class="admin-field-label" for="char_filter">Letter Filter</label>
          <select id="char_filter" name="char">
            <option value="">All</option>
            <option value="1"<?php if ($currentCharFilter === '1') echo ' selected'; ?>>#</option>
            <?php foreach (range('a', 'z') as $letter) { ?>
              <option value="<?php echo $letter; ?>"<?php if ($currentCharFilter === $letter) echo ' selected'; ?>><?php echo strtoupper($letter); ?></option>
            <?php } ?>
          </select>
        </div>

        <button type="submit">Apply Filters</button>
      </form>
    </div>

    <div class="admin-members-toolbar__row">
      <div class="admin-members-toolbar__group admin-members-toolbar__group--full-width">
        <span class="admin-subheading is-tight">Browse</span>
        <div class="admin-members-toolbar__links">
          <?php
          $currentAccountScope = (string)($accountScope ?? ($includeBots ? '1' : '0'));
          $baseMembers = 'index.php?n=admin&sub=members&show_bots=' . urlencode($currentAccountScope);
          $currentChar = $currentCharFilter;
          ?>
          <a href="<?php echo $baseMembers; ?>"<?php if ($currentChar === '') echo ' class="active"'; ?>>All</a>
          <a href="<?php echo $baseMembers; ?>&char=1"<?php if ($currentChar === '1') echo ' class="active"'; ?>>#</a>
          <?php foreach (range('a', 'z') as $letter) { ?>
            <a href="<?php echo $baseMembers; ?>&char=<?php echo $letter; ?>"<?php if ($currentChar === $letter) echo ' class="active"'; ?>><?php echo strtoupper($letter); ?></a>
          <?php } ?>
        </div>
      </div>
    </div>

    <div class="admin-members-toolbar__row">
      <?php $maxBotExpansion = spp_admin_members_highest_installed_expansion($GLOBALS['realmDbMap'] ?? array()); ?>
      <div class="admin-members-toolbar__group admin-members-toolbar__group--full-width">
        <label class="admin-field-label">Bot Expansion</label>
        <div class="admin-form-help">Apply one account expansion choice to all `rndbot` accounts. TBC and WotLK stay unavailable until those realms are installed.</div>
        <div class="admin-expansion-grid">
          <form action="index.php?n=admin&sub=members&action=normalizebotexpansion" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(spp_csrf_token('admin_members')); ?>">
            <input type="hidden" name="switch_wow_type" value="classic">
            <button type="submit" class="admin-btn admin-expansion-btn" data-confirm="Set all bot accounts to Classic access?">Classic</button>
          </form>
          <form action="index.php?n=admin&sub=members&action=normalizebotexpansion" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(spp_csrf_token('admin_members')); ?>">
            <input type="hidden" name="switch_wow_type" value="tbc">
            <button type="submit" class="admin-btn admin-expansion-btn"<?php if ($maxBotExpansion < 1) echo ' disabled="disabled"'; ?> data-confirm="Set all bot accounts to TBC access?">TBC</button>
          </form>
          <form action="index.php?n=admin&sub=members&action=normalizebotexpansion" method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(spp_csrf_token('admin_members')); ?>">
            <input type="hidden" name="switch_wow_type" value="wotlk">
            <button type="submit" class="admin-btn admin-expansion-btn"<?php if ($maxBotExpansion < 2) echo ' disabled="disabled"'; ?> data-confirm="Set all bot accounts to WotLK access?">WotLK</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <div class="admin-list-shell feature-panel">
    <div class="admin-list-row header">
      <div>User Name</div>
      <div>Type</div>
      <div>Joined</div>
      <div>Status</div>
      <div>Manage</div>
    </div>

    <?php if (is_array($items) && count($items)) { ?>
      <?php foreach ($items as $item) { ?>
        <?php
        $isBot = stripos((string)$item['username'], 'rndbot') === 0;
        $isLocked = isset($item['locked']) && (int)$item['locked'] === 1;
        $isBanned = isset($item['g_id']) && (int)$item['g_id'] === 5;
        ?>
        <div class="admin-list-row">
          <div class="name">
            <a href="index.php?n=admin&sub=members&id=<?php echo (int)$item['id']; ?>"><?php echo htmlspecialchars($item['username']); ?></a>
          </div>
          <div>
            <span class="admin-badge <?php echo $isBot ? 'bot' : 'human'; ?>"><?php echo $isBot ? 'Bot' : 'Human'; ?></span>
          </div>
          <div><?php echo htmlspecialchars($item['joindate']); ?></div>
          <div>
            <?php if ($isLocked || $isBanned) { ?>
              <span class="admin-badge warn"><?php echo $isBanned ? 'Banned' : 'Locked'; ?></span>
            <?php } else { ?>
              <span class="admin-badge good">Active</span>
            <?php } ?>
          </div>
          <div class="manage">
            <a href="index.php?n=admin&sub=members&id=<?php echo (int)$item['id']; ?>">Manage</a>
          </div>
        </div>
      <?php } ?>
    <?php } else { ?>
      <div class="admin-list-empty">No members found for the current filter.</div>
    <?php } ?>

    <div class="admin-pagination">
      Pages: <?php echo $pages_str; ?>
    </div>
  </div>
<?php } ?>
</div>
<?php builddiv_end() ?>
