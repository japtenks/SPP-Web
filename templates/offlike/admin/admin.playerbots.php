<?php builddiv_start(1, 'Playerbots Control'); ?>
<?php
$guildStrategyValues = is_array($guildStrategyState['values'] ?? null) ? $guildStrategyState['values'] : array();
$guildStrategyProfileKey = (string)($guildStrategyState['profile_key'] ?? 'custom');
$guildStrategyConsistent = !empty($guildStrategyState['consistent']);
$guildStrategyMemberCount = (int)($guildStrategyState['member_count'] ?? 0);
$guildStrategyMixedCount = (int)($guildStrategyState['mixed_count'] ?? 0);
$randomBotBaselineProfile = is_array($randomBotBaselineProfile ?? null) ? $randomBotBaselineProfile : array();
$characterStrategyValues = is_array($characterStrategyState['values'] ?? null) ? $characterStrategyState['values'] : array();
$characterStrategyProfileKey = (string)($characterStrategyState['profile_key'] ?? 'custom');
$renderWriteSuffix = static function ($mode) {
    $mode = (string)$mode;
    if ($mode === 'soap') {
        return ' Saved via SOAP.';
    }
    if ($mode === 'sql_fallback') {
        return ' SOAP was unavailable, so a direct SQL fallback write was used. This is less safe while the world server is offline.';
    }
    if ($mode === 'sql') {
        return ' Saved with a direct SQL write.';
    }
    return '';
};
?>
<div
  class="playerbots-shell feature-shell"
  data-playerbots-base-url="index.php?n=admin&amp;sub=playerbots"
  data-guild-strategy-profiles="<?php echo htmlspecialchars(json_encode($guildStrategyProfiles ?? array(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>"
  data-bot-strategy-profiles="<?php echo htmlspecialchars(json_encode($botStrategyProfiles ?? array(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>"
>
  <?php if ($meetingSaved): ?><div class="playerbots-success">Guild meeting directive saved.<?php echo htmlspecialchars($renderWriteSuffix($meetingWriteMode ?? '')); ?></div><?php endif; ?>
  <?php if ($shareSaved): ?><div class="playerbots-success">Guild share block saved.<?php echo htmlspecialchars($renderWriteSuffix($shareWriteMode ?? '')); ?></div><?php endif; ?>
  <?php if ($notesSaved): ?><div class="playerbots-success">Officer order notes saved.<?php echo htmlspecialchars($renderWriteSuffix($notesWriteMode ?? '')); ?></div><?php endif; ?>
  <?php if ($personalitySaved): ?><div class="playerbots-success">Bot personality saved.<?php echo htmlspecialchars($renderWriteSuffix($personalityWriteMode ?? '')); ?></div><?php endif; ?>
  <?php if ($forumToneSaved): ?><div class="playerbots-success">Forum chatter tone saved.<?php echo htmlspecialchars($renderWriteSuffix($forumToneWriteMode ?? '')); ?></div><?php endif; ?>
  <?php if ($botStrategySaved): ?><div class="playerbots-success">Bot strategy override saved.<?php echo htmlspecialchars($renderWriteSuffix($botStrategyWriteMode ?? '')); ?></div><?php endif; ?>
  <?php if ($strategySaved): ?><div class="playerbots-success">Guild flavor applied to member bots.<?php echo htmlspecialchars($renderWriteSuffix($strategyWriteMode ?? '')); ?></div><?php endif; ?>
  <?php if (!empty($invalidRealmRequested)): ?><div class="playerbots-success is-warning">Realm <?php echo (int)$requestedRealmId; ?> is not configured here. Showing the nearest valid configured realm instead.</div><?php endif; ?>

  <div class="playerbots-card feature-hero">
    <div class="playerbots-grid">
      <div class="playerbots-field">
        <label>Realm</label>
        <select data-playerbots-nav="realm">
          <?php foreach (($realmOptions ?? array()) as $realmOption): ?>
            <option value="<?php echo (int)$realmOption['realm_id']; ?>"<?php echo (int)$realmOption['realm_id'] === (int)$realmId ? ' selected' : ''; ?>><?php echo htmlspecialchars((string)$realmOption['label']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="playerbots-field">
        <label>Guild</label>
        <select data-playerbots-nav="guild" data-playerbots-realm-id="<?php echo (int)$realmId; ?>">
          <?php foreach ($guildOptions as $guildOption): ?>
            <option value="<?php echo (int)$guildOption['guildid']; ?>"<?php echo (int)$guildOption['guildid'] === (int)$selectedGuildId ? ' selected' : ''; ?>><?php echo htmlspecialchars((string)$guildOption['name']); ?><?php if (!empty($guildOption['leader_name'])): ?> - <?php echo htmlspecialchars((string)$guildOption['leader_name']); ?><?php endif; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <?php if (!empty($selectedGuild)): ?>
      <p class="playerbots-note">Managing <strong><?php echo htmlspecialchars((string)$selectedGuild['name']); ?></strong> on <strong><?php echo htmlspecialchars((string)$realmName); ?></strong>. Guild leader GUID: <?php echo (int)($selectedGuild['leaderguid'] ?? 0); ?>.</p>
    <?php else: ?>
      <p class="playerbots-note">No guild was found for the selected realm.</p>
    <?php endif; ?>
    <p class="playerbots-note">Launcher-parity bot reset and delete workflows now live in <a href="<?php echo htmlspecialchars((string)$playerbotOperationsHref); ?>">Operations</a>, while strategy, tone, and personality tuning stay native here.</p>
  </div>

  <div class="playerbots-grid">
    <div class="playerbots-card feature-panel">
      <h3 class="playerbots-section-title">Guild Meetings</h3>
      <?php if (!empty($selectedGuild)): ?>
      <form method="post" action="index.php?n=admin&sub=playerbots&realm=<?php echo (int)$realmId; ?>&guildid=<?php echo (int)$selectedGuildId; ?>&character_guid=<?php echo (int)$selectedCharacterGuid; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($admin_playerbots_csrf_token, ENT_QUOTES); ?>">
        <input type="hidden" name="playerbots_action" value="save_meeting">
        <input type="hidden" name="guildid" value="<?php echo (int)$selectedGuildId; ?>">
        <input type="hidden" name="character_guid" value="<?php echo (int)$selectedCharacterGuid; ?>">
        <div class="playerbots-field">
          <label>Meeting Location</label>
          <select name="meeting_location">
            <option value="">Choose a named travel location...</option>
            <?php foreach (($meetingLocationOptions ?? array()) as $meetingLocation): ?>
              <option value="<?php echo htmlspecialchars((string)$meetingLocation); ?>"<?php echo (string)$meetingLocation === (string)($meetingPreview['location'] ?? '') ? ' selected' : ''; ?>><?php echo htmlspecialchars((string)$meetingLocation); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="playerbots-inline">
          <div class="playerbots-field">
            <label>Start</label>
            <input type="text" name="meeting_start" value="<?php echo htmlspecialchars((string)($meetingPreview['normalized_start'] ?? '')); ?>" placeholder="15:00">
          </div>
          <div class="playerbots-field">
            <label>End</label>
            <input type="text" name="meeting_end" value="<?php echo htmlspecialchars((string)($meetingPreview['normalized_end'] ?? '')); ?>" placeholder="18:00">
          </div>
        </div>
        <div class="playerbots-actions">
          <button class="playerbots-button" type="submit">Save Meeting</button>
          <span class="playerbots-note">Saved into guild MOTD as <code>Meeting: location HH:MM HH:MM</code>. The dropdown is expansion-aware and comes from the selected realm's playerbot travel node names.</span>
        </div>
      </form>
      <?php endif; ?>
      <div class="playerbots-preview is-gap-top">
        <strong>Decoded State Preview</strong><br>
        <?php if (!empty($meetingPreview['found'])): ?>
          <?php if (!empty($meetingPreview['valid'])): ?>
            <?php echo htmlspecialchars((string)$meetingPreview['display']); ?>
          <?php else: ?>
            <span class="playerbots-empty"><?php echo htmlspecialchars((string)($meetingPreview['error'] ?? 'Meeting directive found, but it could not be parsed.')); ?></span>
          <?php endif; ?>
        <?php else: ?>
          <span class="playerbots-empty">No <code>Meeting:</code> directive found in the current MOTD.</span>
        <?php endif; ?>
      </div>
    </div>

    <div class="playerbots-card feature-panel">
      <h3 class="playerbots-section-title">Guild Share / Orders</h3>
      <?php if (!empty($selectedGuild)): ?>
      <form method="post" action="index.php?n=admin&sub=playerbots&realm=<?php echo (int)$realmId; ?>&guildid=<?php echo (int)$selectedGuildId; ?>&character_guid=<?php echo (int)$selectedCharacterGuid; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($admin_playerbots_csrf_token, ENT_QUOTES); ?>">
        <input type="hidden" name="playerbots_action" value="save_share">
        <input type="hidden" name="guildid" value="<?php echo (int)$selectedGuildId; ?>">
        <input type="hidden" name="character_guid" value="<?php echo (int)$selectedCharacterGuid; ?>">
        <div class="playerbots-field">
          <label><code>Share:</code> Block</label>
          <textarea name="share_block" placeholder="Warrior: Elixir of the Mongoose 10&#10;All: Major Healing Potion 20"><?php echo htmlspecialchars((string)$shareBlock); ?></textarea>
        </div>
        <div class="playerbots-actions">
          <button class="playerbots-button" type="submit">Save Share Block</button>
          <span class="playerbots-note">Each line must be <code>&lt;filter&gt;: &lt;item&gt; &lt;amount&gt;, &lt;item&gt; &lt;amount&gt;</code>.</span>
        </div>
      </form>
      <?php endif; ?>
      <div class="playerbots-preview is-gap-top">
        <strong>Decoded Share Preview</strong>
        <?php if (!empty($sharePreview['entries'])): ?>
          <table class="playerbots-table is-compact">
            <thead><tr><th>Filter</th><th>Items</th></tr></thead>
            <tbody>
              <?php foreach ($sharePreview['entries'] as $entry): ?>
                <tr>
                  <td><?php echo htmlspecialchars((string)$entry['filter']); ?></td>
                  <td class="playerbots-note">
                    <?php
                    $parts = array();
                    foreach (($entry['items'] ?? array()) as $itemRow) {
                        $parts[] = (string)$itemRow['item_name'] . ' x' . (int)$itemRow['amount'];
                    }
                    echo htmlspecialchars(implode(', ', $parts));
                    ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php elseif (!empty($sharePreview['errors'])): ?>
          <div class="playerbots-note"><?php echo implode('<br>', array_map('htmlspecialchars', $sharePreview['errors'])); ?></div>
        <?php else: ?>
          <span class="playerbots-empty">No <code>Share:</code> block is currently configured.</span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="playerbots-card feature-panel">
    <h3 class="playerbots-section-title">Officer Order Notes</h3>
    <?php if (!empty($guildMembers)): ?>
    <form method="post" action="index.php?n=admin&sub=playerbots&realm=<?php echo (int)$realmId; ?>&guildid=<?php echo (int)$selectedGuildId; ?>&character_guid=<?php echo (int)$selectedCharacterGuid; ?>">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($admin_playerbots_csrf_token, ENT_QUOTES); ?>">
      <input type="hidden" name="playerbots_action" value="save_notes">
      <input type="hidden" name="guildid" value="<?php echo (int)$selectedGuildId; ?>">
      <input type="hidden" name="character_guid" value="<?php echo (int)$selectedCharacterGuid; ?>">
      <table class="playerbots-table is-compact">
        <thead><tr><th>Character</th><th>Officer Note</th><th>Decoded</th></tr></thead>
        <tbody>
          <?php foreach ($orderPreview as $orderRow): $parsed = $orderRow['parsed']; ?>
            <tr>
              <td><?php echo htmlspecialchars((string)$orderRow['name']); ?></td>
              <td><input type="text" maxlength="31" name="offnote[<?php echo (int)$orderRow['guid']; ?>]" value="<?php echo htmlspecialchars((string)$orderRow['offnote']); ?>"></td>
              <td class="playerbots-note">
                <?php if (!empty($parsed['valid'])): ?>
                  <?php echo htmlspecialchars((string)($parsed['normalized'] !== '' ? $parsed['normalized'] : 'No order')); ?>
                <?php else: ?>
                  <?php echo htmlspecialchars((string)($parsed['error'] ?? 'Invalid order')); ?>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="playerbots-actions">
        <button class="playerbots-button" type="submit">Save Officer Notes</button>
        <span class="playerbots-note">Allowed: empty, <code>skip order</code>, or <code>Craft:/Farm:/Kill:/Explore:</code> notes.</span>
      </div>
    </form>
    <?php else: ?>
      <p class="playerbots-empty">No guild members were found for this guild.</p>
    <?php endif; ?>
  </div>

  <div class="playerbots-card feature-panel">
    <h3 class="playerbots-section-title">Guild Strategy Flavor</h3>
    <p class="playerbots-note">Use this area as a comparison between two things: the unguilded random-bot baseline that makes bots feel like sloppy, social noobs, and the guild flavor layer that turns a guild into a culture. The baseline below is read-only reference. Saving here writes the shared guild layer into <code>preset='default'</code> for the selected guild.</p>
    <div class="playerbots-actions is-flush-top">
      <span class="playerbots-status"><?php echo $guildStrategyConsistent ? 'Sampled bots match' : ('Mixed sampled state across ' . $guildStrategyMixedCount . ' members'); ?></span>
      <span class="playerbots-status">Sample profile: <?php echo htmlspecialchars((string)(($guildStrategyProfiles[$guildStrategyProfileKey]['label'] ?? 'Custom'))); ?></span>
    </div>
    <div class="playerbots-preview is-gap-bottom">
      <strong>Unguilded Baseline Reference</strong>
      <div class="playerbots-note is-gap-md"><?php echo htmlspecialchars((string)($randomBotBaselineProfile['description'] ?? '')); ?></div>
      <div class="playerbots-strategy-grid">
        <div class="playerbots-field">
          <label>Combat (<code>co</code>)</label>
          <textarea readonly><?php echo htmlspecialchars((string)($randomBotBaselineProfile['co'] ?? '')); ?></textarea>
        </div>
        <div class="playerbots-field">
          <label>Non-Combat (<code>nc</code>)</label>
          <textarea readonly><?php echo htmlspecialchars((string)($randomBotBaselineProfile['nc'] ?? '')); ?></textarea>
        </div>
        <div class="playerbots-field">
          <label>Dead (<code>dead</code>)</label>
          <textarea readonly><?php echo htmlspecialchars((string)($randomBotBaselineProfile['dead'] ?? '')); ?></textarea>
        </div>
        <div class="playerbots-field">
          <label>Reaction (<code>react</code>)</label>
          <textarea readonly><?php echo htmlspecialchars((string)($randomBotBaselineProfile['react'] ?? '')); ?></textarea>
        </div>
      </div>
    </div>
    <?php if (!empty($selectedGuild)): ?>
    <form method="post" action="index.php?n=admin&sub=playerbots&realm=<?php echo (int)$realmId; ?>&guildid=<?php echo (int)$selectedGuildId; ?>&character_guid=<?php echo (int)$selectedCharacterGuid; ?>">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($admin_playerbots_csrf_token, ENT_QUOTES); ?>">
      <input type="hidden" name="playerbots_action" value="save_strategy">
      <input type="hidden" name="guildid" value="<?php echo (int)$selectedGuildId; ?>">
      <input type="hidden" name="character_guid" value="<?php echo (int)$selectedCharacterGuid; ?>">
      <div class="playerbots-field">
        <label>Guild Flavor Preset</label>
        <select id="playerbots-guild-strategy-profile" data-strategy-profile="guild">
          <?php foreach (($guildStrategyProfiles ?? array()) as $profileKey => $profile): ?>
            <option value="<?php echo htmlspecialchars((string)$profileKey); ?>"<?php echo $profileKey === $guildStrategyProfileKey ? ' selected' : ''; ?>><?php echo htmlspecialchars((string)$profile['label']); ?> - <?php echo htmlspecialchars((string)$profile['description']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="playerbots-profile-grid is-gap-bottom">
        <?php foreach (($guildStrategyProfiles ?? array()) as $profileKey => $profile): ?>
          <?php if ($profileKey === 'custom'): continue; endif; ?>
          <div class="playerbots-profile-card">
            <strong><?php echo htmlspecialchars((string)$profile['label']); ?></strong>
            <div class="playerbots-note"><?php echo htmlspecialchars((string)$profile['description']); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="playerbots-strategy-grid">
        <div class="playerbots-field">
          <label>Guild Combat Layer (<code>co</code>)</label>
          <div class="playerbots-strategy-builder">
            <?php foreach (($strategyBuilderOptions['co'] ?? array()) as $token): ?>
              <button class="playerbots-strategy-chip" type="button" data-strategy-target="playerbots-guild-strategy-co" data-strategy-token="<?php echo htmlspecialchars((string)$token, ENT_QUOTES); ?>" data-strategy-mode="plus">+<?php echo htmlspecialchars((string)$token); ?></button>
            <?php endforeach; ?>
          </div>
          <textarea id="playerbots-guild-strategy-co" name="strategy_co" placeholder="+dps,+dps assist,-threat"><?php echo htmlspecialchars((string)($guildStrategyProfiles[$guildStrategyProfileKey]['co'] ?? ($guildStrategyValues['co'] ?? ''))); ?></textarea>
        </div>
        <div class="playerbots-field">
          <label>Guild Non-Combat Layer (<code>nc</code>)</label>
          <div class="playerbots-strategy-builder">
            <?php foreach (($strategyBuilderOptions['nc'] ?? array()) as $token): ?>
              <button class="playerbots-strategy-chip" type="button" data-strategy-target="playerbots-guild-strategy-nc" data-strategy-token="<?php echo htmlspecialchars((string)$token, ENT_QUOTES); ?>" data-strategy-mode="plus">+<?php echo htmlspecialchars((string)$token); ?></button>
            <?php endforeach; ?>
          </div>
          <textarea id="playerbots-guild-strategy-nc" name="strategy_nc" placeholder="+rpg,+quest,+grind"><?php echo htmlspecialchars((string)($guildStrategyProfiles[$guildStrategyProfileKey]['nc'] ?? ($guildStrategyValues['nc'] ?? ''))); ?></textarea>
        </div>
        <div class="playerbots-field">
          <label>Guild Dead Layer (<code>dead</code>)</label>
          <div class="playerbots-strategy-builder">
            <?php foreach (($strategyBuilderOptions['dead'] ?? array()) as $token): ?>
              <button class="playerbots-strategy-chip" type="button" data-strategy-target="playerbots-guild-strategy-dead" data-strategy-token="<?php echo htmlspecialchars((string)$token, ENT_QUOTES); ?>" data-strategy-mode="plus">+<?php echo htmlspecialchars((string)$token); ?></button>
            <?php endforeach; ?>
          </div>
          <textarea id="playerbots-guild-strategy-dead" name="strategy_dead" placeholder="+auto release"><?php echo htmlspecialchars((string)($guildStrategyProfiles[$guildStrategyProfileKey]['dead'] ?? ($guildStrategyValues['dead'] ?? ''))); ?></textarea>
        </div>
        <div class="playerbots-field">
          <label>Guild Reaction Layer (<code>react</code>)</label>
          <div class="playerbots-strategy-builder">
            <?php foreach (($strategyBuilderOptions['react'] ?? array()) as $token): ?>
              <button class="playerbots-strategy-chip" type="button" data-strategy-target="playerbots-guild-strategy-react" data-strategy-token="<?php echo htmlspecialchars((string)$token, ENT_QUOTES); ?>" data-strategy-mode="plus">+<?php echo htmlspecialchars((string)$token); ?></button>
            <?php endforeach; ?>
          </div>
          <textarea id="playerbots-guild-strategy-react" name="strategy_react" placeholder="+pvp,+preheal"><?php echo htmlspecialchars((string)($guildStrategyProfiles[$guildStrategyProfileKey]['react'] ?? ($guildStrategyValues['react'] ?? ''))); ?></textarea>
        </div>
      </div>
      <div class="playerbots-actions">
        <button class="playerbots-button" type="submit">Apply Guild Flavor</button>
        <span class="playerbots-note">The baseline above is the noisy random-bot world. These presets are the "guild culture" layer that makes a leveling guild, quest guild, PvP guild, or profession guild feel intentionally different from it.</span>
      </div>
    </form>
    <?php endif; ?>
    <div class="playerbots-preview is-gap-top">
      <strong>Sample Effective Strategy Preview</strong>
      <div class="playerbots-list">
        <div class="playerbots-row"><strong>co</strong><div class="playerbots-note"><?php echo htmlspecialchars((string)(($guildStrategyValues['co'] ?? '') !== '' ? $guildStrategyValues['co'] : 'No sampled bot value')); ?></div></div>
        <div class="playerbots-row"><strong>nc</strong><div class="playerbots-note"><?php echo htmlspecialchars((string)(($guildStrategyValues['nc'] ?? '') !== '' ? $guildStrategyValues['nc'] : 'No sampled bot value')); ?></div></div>
        <div class="playerbots-row"><strong>dead</strong><div class="playerbots-note"><?php echo htmlspecialchars((string)(($guildStrategyValues['dead'] ?? '') !== '' ? $guildStrategyValues['dead'] : 'No sampled bot value')); ?></div></div>
        <div class="playerbots-row"><strong>react</strong><div class="playerbots-note"><?php echo htmlspecialchars((string)(($guildStrategyValues['react'] ?? '') !== '' ? $guildStrategyValues['react'] : 'No sampled bot value')); ?></div></div>
      </div>
    </div>
  </div>

  <div class="playerbots-card feature-panel">
    <h3 class="playerbots-section-title">Bot Personality</h3>
      <div class="playerbots-field">
        <label>Character</label>
        <select data-playerbots-nav="character" data-playerbots-realm-id="<?php echo (int)$realmId; ?>" data-playerbots-guild-id="<?php echo (int)$selectedGuildId; ?>">
          <?php foreach ($guildMembers as $member): ?>
            <option value="<?php echo (int)$member['guid']; ?>"<?php echo (int)$member['guid'] === (int)$selectedCharacterGuid ? ' selected' : ''; ?>><?php echo htmlspecialchars((string)$member['name']); ?> (Lvl <?php echo (int)$member['level']; ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if (!empty($selectedCharacter)): ?>
      <form method="post" action="index.php?n=admin&sub=playerbots&realm=<?php echo (int)$realmId; ?>&guildid=<?php echo (int)$selectedGuildId; ?>&character_guid=<?php echo (int)$selectedCharacterGuid; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($admin_playerbots_csrf_token, ENT_QUOTES); ?>">
        <input type="hidden" name="playerbots_action" value="save_personality">
        <input type="hidden" name="guildid" value="<?php echo (int)$selectedGuildId; ?>">
        <input type="hidden" name="character_guid" value="<?php echo (int)$selectedCharacterGuid; ?>">
        <div class="playerbots-field">
          <label>LLM Personality Text</label>
          <textarea name="personality_text" placeholder="I am the keeper of the source."><?php echo htmlspecialchars((string)$selectedPersonality); ?></textarea>
        </div>
        <div class="playerbots-actions">
          <button class="playerbots-button" type="submit">Save Personality</button>
          <span class="playerbots-note">The editor starts from the current stored prompt for this bot and saves the newest full prompt snapshot back to <code>ai_playerbot_db_store</code>.</span>
        </div>
      </form>
      <?php else: ?>
        <p class="playerbots-empty">Select a guild member to edit bot personality text.</p>
      <?php endif; ?>
      <div class="playerbots-preview is-gap-top">
        <strong>Decoded Personality</strong><br>
        <?php if ($selectedPersonality !== ''): ?>
          <code><?php echo htmlspecialchars((string)$selectedPersonality); ?></code>
        <?php else: ?>
          <span class="playerbots-empty">No stored personality prompt for the selected character.</span>
        <?php endif; ?>
      </div>

      <?php if (!empty($selectedCharacter)): ?>
      <div class="playerbots-preview is-gap-top">
        <strong>Bot React / Role Builder</strong>
        <p class="playerbots-note is-gap-md">These fields start from the current effective strategy values for <strong><?php echo htmlspecialchars((string)($selectedCharacter['name'] ?? 'this bot')); ?></strong>, including the guild flavor stored in <code>preset='default'</code>. Saving here stores only this bot's override layer, so future guild flavor updates can still flow through.</p>
        <form method="post" action="index.php?n=admin&sub=playerbots&realm=<?php echo (int)$realmId; ?>&guildid=<?php echo (int)$selectedGuildId; ?>&character_guid=<?php echo (int)$selectedCharacterGuid; ?>">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($admin_playerbots_csrf_token, ENT_QUOTES); ?>">
          <input type="hidden" name="playerbots_action" value="save_bot_strategy">
          <input type="hidden" name="guildid" value="<?php echo (int)$selectedGuildId; ?>">
          <input type="hidden" name="character_guid" value="<?php echo (int)$selectedCharacterGuid; ?>">
          <div class="playerbots-field">
            <label>Bot Role Preset</label>
            <select id="playerbots-bot-strategy-profile" data-strategy-profile="bot">
              <?php foreach (($botStrategyProfiles ?? array()) as $profileKey => $profile): ?>
                <option value="<?php echo htmlspecialchars((string)$profileKey); ?>"<?php echo $profileKey === $characterStrategyProfileKey ? ' selected' : ''; ?>><?php echo htmlspecialchars((string)$profile['label']); ?> - <?php echo htmlspecialchars((string)$profile['description']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="playerbots-profile-grid is-gap-bottom">
            <?php foreach (($botStrategyProfiles ?? array()) as $profileKey => $profile): ?>
              <?php if ($profileKey === 'custom'): continue; endif; ?>
              <div class="playerbots-profile-card">
                <strong><?php echo htmlspecialchars((string)$profile['label']); ?></strong>
                <div class="playerbots-note"><?php echo htmlspecialchars((string)$profile['description']); ?></div>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="playerbots-strategy-grid">
            <div class="playerbots-field">
              <label>Combat (<code>co</code>)</label>
              <div class="playerbots-strategy-builder">
                <?php foreach (($strategyBuilderOptions['co'] ?? array()) as $token): ?>
                  <button class="playerbots-strategy-chip" type="button" data-strategy-target="playerbots-bot-strategy-co" data-strategy-token="<?php echo htmlspecialchars((string)$token, ENT_QUOTES); ?>" data-strategy-mode="plus">+<?php echo htmlspecialchars((string)$token); ?></button>
                <?php endforeach; ?>
              </div>
              <textarea id="playerbots-bot-strategy-co" name="strategy_co" placeholder="+dps,+dps assist,-threat"><?php echo htmlspecialchars((string)($characterStrategyValues['co'] ?? '')); ?></textarea>
            </div>
            <div class="playerbots-field">
              <label>Non-Combat (<code>nc</code>)</label>
              <div class="playerbots-strategy-builder">
                <?php foreach (($strategyBuilderOptions['nc'] ?? array()) as $token): ?>
                  <button class="playerbots-strategy-chip" type="button" data-strategy-target="playerbots-bot-strategy-nc" data-strategy-token="<?php echo htmlspecialchars((string)$token, ENT_QUOTES); ?>" data-strategy-mode="plus">+<?php echo htmlspecialchars((string)$token); ?></button>
                <?php endforeach; ?>
              </div>
              <textarea id="playerbots-bot-strategy-nc" name="strategy_nc" placeholder="+follow,+loot,+food"><?php echo htmlspecialchars((string)($characterStrategyValues['nc'] ?? '')); ?></textarea>
            </div>
            <div class="playerbots-field">
              <label>Dead (<code>dead</code>)</label>
              <div class="playerbots-strategy-builder">
                <?php foreach (($strategyBuilderOptions['dead'] ?? array()) as $token): ?>
                  <button class="playerbots-strategy-chip" type="button" data-strategy-target="playerbots-bot-strategy-dead" data-strategy-token="<?php echo htmlspecialchars((string)$token, ENT_QUOTES); ?>" data-strategy-mode="plus">+<?php echo htmlspecialchars((string)$token); ?></button>
                <?php endforeach; ?>
              </div>
              <textarea id="playerbots-bot-strategy-dead" name="strategy_dead" placeholder="+auto release"><?php echo htmlspecialchars((string)($characterStrategyValues['dead'] ?? '')); ?></textarea>
            </div>
            <div class="playerbots-field">
              <label>Reaction (<code>react</code>)</label>
              <div class="playerbots-strategy-builder">
                <?php foreach (($strategyBuilderOptions['react'] ?? array()) as $token): ?>
                  <button class="playerbots-strategy-chip" type="button" data-strategy-target="playerbots-bot-strategy-react" data-strategy-token="<?php echo htmlspecialchars((string)$token, ENT_QUOTES); ?>" data-strategy-mode="plus">+<?php echo htmlspecialchars((string)$token); ?></button>
                <?php endforeach; ?>
              </div>
              <textarea id="playerbots-bot-strategy-react" name="strategy_react" placeholder="+pvp,+preheal"><?php echo htmlspecialchars((string)($characterStrategyValues['react'] ?? '')); ?></textarea>
            </div>
          </div>
          <div class="playerbots-actions">
            <button class="playerbots-button" type="submit">Save Bot Role / React</button>
            <span class="playerbots-note">The editor starts from this bot's current saved strategy snapshot. Choosing a preset layers that role onto the current values instead of flattening the existing guild flavor.</span>
          </div>
        </form>
      </div>
      <?php endif; ?>
  </div>

  <div class="playerbots-card feature-panel">
    <h3 class="playerbots-section-title">Forum Chatter Tone</h3>
    <p class="playerbots-note">These lines populate the <code>forum:...</code> rows in <code>ai_playerbot_texts</code> for the selected realm. Each non-empty line becomes one possible bit of bot chatter for forum reactions and guild seed posts. If you leave them empty, the site falls back to its built-in default lines.</p>
    <form method="post" action="index.php?n=admin&sub=playerbots&realm=<?php echo (int)$realmId; ?>&guildid=<?php echo (int)$selectedGuildId; ?>&character_guid=<?php echo (int)$selectedCharacterGuid; ?>">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($admin_playerbots_csrf_token, ENT_QUOTES); ?>">
      <input type="hidden" name="playerbots_action" value="save_forum_tone">
      <input type="hidden" name="guildid" value="<?php echo (int)$selectedGuildId; ?>">
      <input type="hidden" name="character_guid" value="<?php echo (int)$selectedCharacterGuid; ?>">
      <?php foreach (($forumToneGroups ?? array()) as $group): ?>
        <div class="playerbots-preview is-gap-bottom">
          <strong><?php echo htmlspecialchars((string)($group['label'] ?? 'Forum Tone')); ?></strong>
          <div class="playerbots-note is-gap-sm"><?php echo htmlspecialchars((string)($group['description'] ?? '')); ?></div>
          <div class="playerbots-tone-grid">
            <?php foreach ((array)($group['keys'] ?? array()) as $toneKey => $toneMeta): ?>
              <?php $toneFieldName = 'forum_tone_' . md5((string)$toneKey); ?>
              <div class="playerbots-tone-card">
                <h4><?php echo htmlspecialchars((string)($toneMeta['label'] ?? $toneKey)); ?></h4>
                <div class="playerbots-tone-key"><code><?php echo htmlspecialchars((string)$toneKey); ?></code></div>
                <div class="playerbots-field">
                  <textarea name="<?php echo htmlspecialchars($toneFieldName, ENT_QUOTES); ?>" placeholder="<?php echo htmlspecialchars((string)($toneMeta['placeholder'] ?? '')); ?>"><?php echo htmlspecialchars((string)($forumToneState[$toneKey] ?? '')); ?></textarea>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
      <div class="playerbots-actions">
        <button class="playerbots-button" type="submit">Save Chatter Tone</button>
        <span class="playerbots-note">Placeholders supported by the event processor include <code>&lt;name&gt;</code>, <code>&lt;guild&gt;</code>, <code>&lt;level&gt;</code>, <code>&lt;skill&gt;</code>, <code>&lt;achievement&gt;</code>, <code>&lt;member_count&gt;</code>, <code>&lt;joined_count&gt;</code>, <code>&lt;left_count&gt;</code>, <code>&lt;needs&gt;</code>, and <code>&lt;role_summary&gt;</code>.</span>
      </div>
    </form>
  </div>
</div>
<?php builddiv_end(); ?>
