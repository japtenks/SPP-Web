<?php if($user['id']>0 && isset($profile)){ ?>
<?php $GLOBALS['builddiv_header_actions'] = '<a href="index.php?n=account&sub=userlist" class="btn secondary">Back to User List</a>'; ?>
<?php builddiv_start(1, 'Account Settings'); ?>
<?php
$currentExpansionId = (int)($profile['expansion'] ?? 0);
$nextExpansionLabel = '';
$transferPathLabel = '';
if ($currentExpansionId === 0) {
  $nextExpansionLabel = 'TBC';
  $transferPathLabel = 'Classic -> TBC';
} elseif ($currentExpansionId === 1) {
  $nextExpansionLabel = 'WotLK';
  $transferPathLabel = 'TBC -> WotLK';
}
?>

<div class="modern-content settings-page feature-shell">
  <section class="settings-hero feature-hero">
    <div>
      <div class="settings-kicker feature-eyebrow">Account Center</div>
      <h2><?php echo htmlspecialchars($profile['username']); ?></h2>
      <p class="settings-intro feature-copy">Use this page as your account home base: update your public identity, review access settings, and jump into deeper character services when needed.</p>
    </div>
    <div class="settings-badges">
      <span>
        <?php
        $currentExpansion = 'Classic';
        if ((int)($profile['expansion'] ?? 0) === 1) $currentExpansion = 'TBC';
        if ((int)($profile['expansion'] ?? 0) === 2) $currentExpansion = 'WotLK';
        echo $currentExpansion;
        ?>
      </span>
    </div>
  </section>

  <div class="settings-grid">
    <section class="settings-card feature-panel">
      <div class="settings-card-title feature-eyebrow">Profile</div>
      <p class="settings-card-intro feature-copy">These settings shape how your website identity appears to other members.</p>
      <form method="post" action="index.php?n=account&sub=manage&action=change" enctype="multipart/form-data" class="settings-stack-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($manage_csrf_token ?? '')); ?>">
        <div class="settings-field">
          <label>Username</label>
          <input type="text" value="<?php echo htmlspecialchars($profile['username']); ?>" disabled="disabled">
        </div>
        <div class="settings-toggle-row">
          <?php if((int)($user['gmlevel'] ?? 0) >= 3): ?>
          <label class="settings-field settings-compact">
            <span>Hide profile</span>
            <select name="profile[hideprofile]">
              <option value="0"<?php if((int)$profile['hideprofile']===0)echo' selected';?>>No</option>
              <option value="1"<?php if((int)$profile['hideprofile']===1)echo' selected';?>>Yes</option>
            </select>
          </label>
          <?php endif; ?>
        </div>

        <?php if ((int)spp_config_generic('change_template', 0) === 1) { ?>
        <div class="settings-field">
          <label>Theme</label>
          <select name="profile[theme]">
            <?php
            $i = 0;
            foreach((array)($templateOptions ?? array()) as $template){ ?>
            <option value="<?php echo $i; ?>"<?php if((int)$profile['theme']===$i)echo' selected';?>><?php echo htmlspecialchars((string)$template); ?></option>
            <?php $i++; } ?>
          </select>
        </div>
        <?php } ?>

        <?php if(!empty($backgroundPreferencesAvailable)): ?>
        <div class="settings-field">
          <label>Site Background Behavior</label>
          <select name="profile[background_mode]" id="background-mode-select">
            <?php foreach($backgroundModeOptions as $backgroundModeValue => $backgroundModeLabel): ?>
            <option value="<?php echo htmlspecialchars($backgroundModeValue); ?>"<?php if(($profile['background_mode'] ?? 'daily') === $backgroundModeValue) echo ' selected'; ?>>
              <?php echo htmlspecialchars($backgroundModeLabel); ?>
            </option>
            <?php endforeach; ?>
          </select>
          <div class="settings-help-text">Random picks a new image on each page load. Once a Day keeps one background for the current calendar day. By Main Section keeps one image per top-level area like Frontpage, Forums, Account, Armory, Workshop, Media, or Community. Fixed Background locks the site to one image you pick.</div>
        </div>

        <div class="settings-field settings-background-image-picker" id="background-image-picker">
          <label>Fixed Background Image</label>
          <select name="profile[background_image]">
            <?php foreach($availableBackgroundImages as $backgroundFilename => $backgroundPath): ?>
            <option value="<?php echo htmlspecialchars($backgroundFilename); ?>"<?php if(($profile['background_image'] ?? '') === $backgroundFilename) echo ' selected'; ?>>
              <?php echo htmlspecialchars(spp_background_image_label($backgroundFilename)); ?>
            </option>
            <?php endforeach; ?>
          </select>
          <div class="settings-help-text">This image is used when Fixed Background is selected.</div>
        </div>
        <?php endif; ?>

        <?php if(!empty($canManageHiddenForums) && !empty($hiddenForumPreferenceAvailable)): ?>
        <label class="settings-checkbox-row">
          <input type="checkbox" name="profile[show_hidden_forums]" value="1"<?php if(!empty($profile['show_hidden_forums'])) echo ' checked'; ?>>
          <span>Show hidden forums by default</span>
        </label>
        <div class="settings-help-text">Visible only to GM/admin-capable accounts. Turn this off if you want your normal forum view to match regular players.</div>
        <?php endif; ?>

        <div class="settings-avatar-block">
          <div class="settings-avatar-preview">
            <?php if(!empty($profile['selected_character_avatar_url'])) { ?>
              <img id="selected-character-avatar" src="<?php echo htmlspecialchars($profile['selected_character_avatar_url']); ?>" alt="Character Portrait">
            <?php } elseif(!empty($profile['avatar'])) { ?>
              <img src="uploads/avatars/<?php echo htmlspecialchars($profile['avatar']); ?>" alt="Avatar">
            <?php } elseif(!empty($profile['avatar_fallback_url'])) { ?>
              <img src="<?php echo htmlspecialchars($profile['avatar_fallback_url']); ?>" alt="Forum Avatar">
            <?php } else { ?>
              <div class="settings-avatar-placeholder"><?php echo strtoupper(substr($profile['username'], 0, 1)); ?></div>
            <?php } ?>
          </div>
          <div class="settings-avatar-controls">
            <label class="settings-field">
              <span>Upload avatar</span>
              <input type="file" name="avatar">
            </label>
            <div class="settings-help-text">
              Max file size: <?php echo (int)spp_config_generic('max_avatar_file', 102400); ?> bytes. Max size: <?php echo htmlspecialchars((string)spp_config_generic('max_avatar_size', '64x64')); ?> px.
            </div>
            <?php if(!empty($profile['avatar'])) { ?>
            <label class="settings-checkbox-row">
              <input type="checkbox" name="deleteavatar" value="1">
              <span>Delete current avatar</span>
            </label>
            <input type="hidden" name="avatarfile" value="<?php echo htmlspecialchars($profile['avatar']); ?>">
            <?php } ?>
          </div>
        </div>

        <?php if(!empty($accountCharacters)): ?>
        <div class="settings-field">
          <label>Signature Character</label>
          <select id="signature-character-key" name="signature_character_key">
            <?php foreach($accountCharacters as $character): ?>
              <?php
                $sigKey = (int)($character['realm_id'] ?? 0) . ':' . (int)$character['guid'];
                $sigValue = (string)($profile['character_signatures'][$sigKey]['signature'] ?? '');
                $sigRealmName = (string)($character['realm_name'] ?? spp_realm_display_name((int)($character['realm_id'] ?? 0)));
              ?>
              <option
                value="<?php echo htmlspecialchars($sigKey); ?>"
                data-signature="<?php echo htmlspecialchars($sigValue); ?>"
                data-avatar-url="<?php echo htmlspecialchars((string)($profile['character_signatures'][$sigKey]['avatar_url'] ?? '')); ?>"
                <?php if((string)($profile['signature_character_key'] ?? '') === $sigKey) echo 'selected'; ?>
              >
                <?php echo htmlspecialchars($character['name']); ?><?php if(!empty($character['level'])) echo ' (Lvl ' . (int)$character['level'] . ')'; ?> - <?php echo htmlspecialchars($sigRealmName); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="settings-help-text">This signature will be used by the selected character when posting on the forum.</div>
        </div>
        <?php endif; ?>

        <div class="settings-field">
          <label>Signature</label>
          <textarea id="profile-signature" name="profile[signature]" maxlength="255" rows="4"><?php echo htmlspecialchars(my_previewreverse($profile['signature'])); ?></textarea>
          <div class="settings-help-text">Supports normal BBCode. Keep it short and readable.</div>
        </div>

        <div class="settings-actions feature-actions">
          <button type="button" class="feature-button" id="profile-signature-reset">Reset</button>
          <button type="submit" class="feature-button is-primary">Save Changes</button>
        </div>
      </form>
    </section>

    <section class="settings-card feature-panel">
      <div class="settings-card-title feature-eyebrow">Game Access</div>
      <p class="settings-card-intro feature-copy">Expansion status and game-facing account progression live here.</p>

      <div class="settings-expansion-panel settings-section-gap">
        <div class="settings-mini-title">Game Expansion</div>
        <div class="settings-expansion-grid">
          <form method="post" action="index.php?n=account&sub=manage&action=change_gameplay">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($manage_csrf_token ?? '')); ?>">
            <input type="hidden" name="switch_wow_type" value="classic">
            <button type="submit" class="settings-expansion-btn<?php if((int)$profile['expansion']===0)echo' active'; ?>">Classic</button>
          </form>
          <form method="post" action="index.php?n=account&sub=manage&action=change_gameplay">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($manage_csrf_token ?? '')); ?>">
            <input type="hidden" name="switch_wow_type" value="tbc">
            <button type="submit" class="settings-expansion-btn<?php if((int)$profile['expansion']===1)echo' active'; ?>">TBC</button>
          </form>
          <form method="post" action="index.php?n=account&sub=manage&action=change_gameplay">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($manage_csrf_token ?? '')); ?>">
            <input type="hidden" name="switch_wow_type" value="wotlk">
            <button type="submit" class="settings-expansion-btn<?php if((int)$profile['expansion']===2)echo' active'; ?>">WotLK</button>
          </form>
        </div>
      </div>

    </section>
  </div>

  <section class="settings-card feature-panel settings-recovery-card">
    <div class="settings-card-title feature-eyebrow">Security And Quick Actions</div>
    <p class="settings-card-intro feature-copy">Use these for account-level security changes and small one-off character actions.</p>
    <div class="settings-recovery-grid">
      <form id="password-change-form" method="post" action="index.php?n=account&sub=manage&action=changepass" class="settings-stack-form settings-tool-panel">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($manage_csrf_token ?? '')); ?>">
        <div class="settings-mini-title">Password</div>
        <div class="settings-help-text">Change your website/game login password for this account.</div>
        <div class="settings-field">
          <label>New Password</label>
          <input type="password" name="new_pass">
        </div>
        <div class="settings-field">
          <label>Confirm New Password</label>
          <input type="password" name="confirm_new_pass">
        </div>
        <div class="settings-actions feature-actions">
          <button type="submit" class="feature-button is-primary">Change Password</button>
        </div>
      </form>

      <form method="post" action="index.php?n=account&sub=manage&action=renamechar" class="settings-stack-form settings-tool-panel">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)($manage_csrf_token ?? '')); ?>">
        <div class="settings-mini-title">Quick Character Rename</div>
        <div class="settings-help-text">Rename a character on this account for the currently selected realm: <?php echo htmlspecialchars($manageRealmName ?? 'Current Realm'); ?>. For broader character changes, use Character Tools above.</div>
        <div class="settings-field">
          <label>Character</label>
          <select name="character_guid">
            <?php if(!empty($renameCharacters)): ?>
              <?php foreach($renameCharacters as $character): ?>
              <option value="<?php echo (int)$character['guid']; ?>">
                <?php echo htmlspecialchars($character['name']); ?><?php if(!empty($character['level'])) echo ' (Lvl ' . (int)$character['level'] . ')'; ?>
              </option>
              <?php endforeach; ?>
            <?php else: ?>
              <option value="0">No characters available</option>
            <?php endif; ?>
          </select>
        </div>
        <div class="settings-field">
          <label>New Character Name</label>
          <input type="text" name="new_character_name" maxlength="20">
        </div>
        <div class="settings-help-text">The character must be offline and the new name must be unused.</div>
        <div class="settings-actions feature-actions">
          <button type="submit" class="feature-button is-primary"<?php if(empty($renameCharacters)) echo ' disabled="disabled"'; ?>>Rename Character</button>
        </div>
      </form>

    </div>
  </section>
</div>

<?php builddiv_end() ?>
<?php } ?>
