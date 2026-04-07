<?php
require_once dirname(__DIR__, 3) . '/app/server/character-helpers.php';

builddiv_start(1, 'Character Profile', 0);

$portraitUrl = $character ? spp_character_portrait_path($character['level'], $character['gender'], $character['race'], $character['class']) : '';
$factionName = $character ? spp_character_faction_name($character['race']) : '';
$factionIcon = $factionName === 'Horde' ? spp_armory_image_url('icon-horde.gif') : spp_armory_image_url('icon-alliance.gif');
$factionHeroLogo = spp_modern_faction_logo_url($factionName === 'Horde' ? 'horde' : 'alliance');
$paperdollBackgroundUrl = spp_modern_image_url('character/paperdoll-profile-bg.jpg');
$classSlug = $character ? strtolower(str_replace(' ', '', $classNames[(int)$character['class']] ?? 'unknown')) : 'unknown';
$guildId = (int)($character['guildid'] ?? 0);
$guildName = (string)($character['guild_name'] ?? '');
$honorableKills = (int)($character['stored_honorable_kills'] ?? $character['totalKills'] ?? 0);
if ($honorableKills <= 0 && $character && spp_character_table_exists($charsPdo, 'character_honor_cp')) {
    $stmt = $charsPdo->prepare("SELECT COUNT(*) FROM `character_honor_cp` WHERE `guid` = ? AND `victim_type` > 0 AND `type` = 1");
    $stmt->execute(array((int)$character['guid']));
    $honorableKills = (int)$stmt->fetchColumn();
}
$honorPoints = (int)($character['totalHonorPoints'] ?? $character['stored_honor_rating'] ?? 0);
$gearScoreSlots = array_diff_key($equipment, array(3 => true, 18 => true));
$gearItemLevels = array();
foreach ($gearScoreSlots as $gearItem) {
    $itemLevel = (int)($gearItem['item_level'] ?? 0);
    if ($itemLevel > 0) $gearItemLevels[] = $itemLevel;
}
$averageItemLevel = !empty($gearItemLevels) ? round(array_sum($gearItemLevels) / count($gearItemLevels), 1) : 0;
$gearRank = 'Unranked';
if ($averageItemLevel >= 88) $gearRank = 'Tier 3';
elseif ($averageItemLevel >= 76) $gearRank = 'Tier 2';
elseif ($averageItemLevel >= 66) $gearRank = 'Tier 1';
elseif ($averageItemLevel > 0) $gearRank = 'N00b!';
$talentList = array_values($talentTabs);
usort($talentList, function ($a, $b) { return $b['points'] <=> $a['points']; });
$reputationHighlights = $reputations;
usort($reputationHighlights, function ($a, $b) { return $b['standing'] <=> $a['standing']; });
$reputationHighlights = array_slice($reputationHighlights, 0, 5);
if (!empty($forumSocial['recent_posts'])) $forumSocial['recent_posts'] = array_slice($forumSocial['recent_posts'], 0, 5);
if (!empty($forumSocial['recent_topics'])) $forumSocial['recent_topics'] = array_slice($forumSocial['recent_topics'], 0, 5);
$primaryPowerLabelMap = array(1 => 'Rage', 2 => 'Mana', 3 => 'Mana', 4 => 'Energy', 5 => 'Mana', 6 => 'Runic Power', 7 => 'Mana', 8 => 'Mana', 9 => 'Mana', 11 => 'Mana');
$primaryPowerLabel = $primaryPowerLabelMap[(int)$character['class']] ?? 'Power';
$specName = !empty($talentList) ? (string)$talentList[0]['name'] : 'No Specialization';
$specBreakdown = !empty($talentTabs) ? implode(' / ', array_map(function ($tab) { return (string)(int)$tab['points']; }, array_values($talentTabs))) : '0 / 0 / 0';
$resistanceStats = array(
    'Arcane' => (int)($stats['resArcane'] ?? 0),
    'Fire' => (int)($stats['resFire'] ?? 0),
    'Nature' => (int)($stats['resNature'] ?? 0),
    'Frost' => (int)($stats['resFrost'] ?? 0),
    'Shadow' => (int)($stats['resShadow'] ?? 0),
);
$baseStatsView = array(
    'Strength' => (int)($stats['strength'] ?? 0),
    'Agility' => (int)($stats['agility'] ?? 0),
    'Stamina' => (int)($stats['stamina'] ?? 0),
    'Intellect' => (int)($stats['intellect'] ?? 0),
    'Spirit' => (int)($stats['spirit'] ?? 0),
    'Armor' => (int)($stats['armor'] ?? 0),
);
$meleeStatsView = array(
    'Damage' => ((float)($stats['mainHandDamageMin'] ?? 0) > 0 || (float)($stats['mainHandDamageMax'] ?? 0) > 0) ? number_format((float)($stats['mainHandDamageMin'] ?? 0), 0, '.', ',') . ' - ' . number_format((float)($stats['mainHandDamageMax'] ?? 0), 0, '.', ',') : '0 - 0',
    'Speed' => (((float)($stats['mainHandSpeed'] ?? 0) > 0) || ((float)($stats['offHandSpeed'] ?? 0) > 0)) ? rtrim(rtrim(number_format((float)($stats['mainHandSpeed'] ?? 0), 2, '.', ''), '0'), '.') . ' / ' . rtrim(rtrim(number_format((float)($stats['offHandSpeed'] ?? 0), 2, '.', ''), '0'), '.') : '0 / 0',
    'Power' => number_format((float)($stats['attackPower'] ?? 0), 0, '.', ','),
    'Hit Rating' => number_format((float)($stats['meleeHitRating'] ?? 0), 0, '.', ','),
    'Crit Chance' => rtrim(rtrim(number_format((float)($stats['critPct'] ?? 0), 2, '.', ''), '0'), '.') . '%',
    'Expertise' => number_format((float)($stats['expertise'] ?? $stats['expertiseRating'] ?? 0), 0, '.', ','),
);
$gearShowcaseLeft = array(0, 2, 14, 4, 8, 15, 17);
$gearShowcaseRight = array(9, 1, 3, 5, 6, 7, 10, 11, 12, 13);
$gearShowcaseBottom = array(16, 18);
$resourceMap = array(
    1 => array('label' => 'Rage', 'current' => (int)($character['power1'] ?? 0), 'max' => (int)($stats['maxpower1'] ?? 100), 'class' => 'is-rage'),
    2 => array('label' => 'Mana', 'current' => (int)($character['power1'] ?? 0), 'max' => (int)($stats['maxpower1'] ?? 0), 'class' => 'is-mana'),
    3 => array('label' => 'Mana', 'current' => (int)($character['power1'] ?? 0), 'max' => (int)($stats['maxpower1'] ?? 0), 'class' => 'is-mana'),
    4 => array('label' => 'Energy', 'current' => (int)($character['power1'] ?? 0), 'max' => max(100, (int)($stats['maxpower1'] ?? 0)), 'class' => 'is-energy'),
    5 => array('label' => 'Mana', 'current' => (int)($character['power1'] ?? 0), 'max' => (int)($stats['maxpower1'] ?? 0), 'class' => 'is-mana'),
    7 => array('label' => 'Mana', 'current' => (int)($character['power1'] ?? 0), 'max' => (int)($stats['maxpower1'] ?? 0), 'class' => 'is-mana'),
    8 => array('label' => 'Mana', 'current' => (int)($character['power1'] ?? 0), 'max' => (int)($stats['maxpower1'] ?? 0), 'class' => 'is-mana'),
    9 => array('label' => 'Mana', 'current' => (int)($character['power1'] ?? 0), 'max' => (int)($stats['maxpower1'] ?? 0), 'class' => 'is-mana'),
    11 => array('label' => 'Mana', 'current' => (int)($character['power1'] ?? 0), 'max' => (int)($stats['maxpower1'] ?? 0), 'class' => 'is-mana'),
);
$primaryResource = $resourceMap[(int)($character['class'] ?? 0)] ?? array('label' => 'Power', 'current' => (int)($character['power1'] ?? 0), 'max' => (int)($stats['maxpower1'] ?? 100), 'class' => 'is-mana');
$healthCurrent = (int)($character['health'] ?? 0);
$healthMax = max($healthCurrent, (int)($stats['maxhealth'] ?? 0));
$resourceCurrent = (int)$primaryResource['current'];
$resourceMax = max($resourceCurrent, (int)$primaryResource['max']);
$paperdollLeftSlots = array(0, 1, 2, 14, 4, 3, 18, 8);
$paperdollRightSlots = array(9, 5, 6, 7, 10, 11, 12, 13);
$paperdollBottomSlots = array(15, 16, 17);
$talentBarCap = max(1, (int)($character['level'] ?? 0) - 9);
$talentTreesView = array_values($talentTabs);
$defenseStatsView = array(
    'Armor' => number_format((float)($stats['armor'] ?? 0), 0, '.', ','),
    'Defense' => number_format((float)($stats['defenseRating'] ?? 0), 0, '.', ','),
    'Dodge' => rtrim(rtrim(number_format((float)($stats['dodgePct'] ?? 0), 2, '.', ''), '0'), '.') . '%',
    'Parry' => rtrim(rtrim(number_format((float)($stats['parryPct'] ?? 0), 2, '.', ''), '0'), '.') . '%',
    'Block' => rtrim(rtrim(number_format((float)($stats['blockPct'] ?? 0), 2, '.', ''), '0'), '.') . '%',
    'Resilience' => number_format((float)($stats['resilience'] ?? 0), 0, '.', ','),
);
$spellStatsView = array(
    'Bonus Damage' => number_format((float)($stats['spellPower'] ?? 0), 0, '.', ','),
    'Bonus Healing' => number_format((float)($stats['healBonus'] ?? 0), 0, '.', ','),
    'Hit Rating' => number_format((float)($stats['spellHitRating'] ?? 0), 0, '.', ','),
    'Crit Chance' => rtrim(rtrim(number_format((float)($stats['spellCritPct'] ?? 0), 2, '.', ''), '0'), '.') . '%',
    'Haste Rating' => number_format((float)($stats['spellHasteRating'] ?? 0), 0, '.', ','),
    'Mana Regen' => number_format((float)($stats['manaRegen'] ?? 0), 0, '.', ','),
);
$rangedStatsView = array(
    'Damage' => ((float)($stats['rangedDamageMin'] ?? 0) > 0 || (float)($stats['rangedDamageMax'] ?? 0) > 0) ? number_format((float)($stats['rangedDamageMin'] ?? 0), 0, '.', ',') . ' - ' . number_format((float)($stats['rangedDamageMax'] ?? 0), 0, '.', ',') : '0 - 0',
    'Speed' => ((float)($stats['rangedSpeed'] ?? 0) > 0) ? rtrim(rtrim(number_format((float)($stats['rangedSpeed'] ?? 0), 2, '.', ''), '0'), '.') : '0',
    'Power' => number_format((float)($stats['rangedAttackPower'] ?? 0), 0, '.', ','),
    'Hit Rating' => number_format((float)($stats['rangedHitRating'] ?? 0), 0, '.', ','),
    'Crit Chance' => rtrim(rtrim(number_format((float)($stats['rangedCritPct'] ?? 0), 2, '.', ''), '0'), '.') . '%',
    'Haste Rating' => number_format((float)($stats['rangedHasteRating'] ?? 0), 0, '.', ','),
);
$paperdollLeftPanels = array(
    'base' => array('label' => 'Base Stats', 'rows' => $baseStatsView),
    'defense' => array('label' => 'Defense', 'rows' => $defenseStatsView),
);
$paperdollRightPanels = array(
    'melee' => array('label' => 'Melee', 'rows' => $meleeStatsView),
    'spell' => array('label' => 'Spell', 'rows' => $spellStatsView),
    'ranged' => array('label' => 'Ranged', 'rows' => $rangedStatsView),
);
$paperdollRightDefault = in_array((int)($character['class'] ?? 0), array(3), true) ? 'ranged' : (in_array((int)($character['class'] ?? 0), array(5, 8, 9), true) ? 'spell' : 'melee');
$tabIcons = array(
    'talents' => spp_modern_image_url('badges/talents.png'),
    'reputation' => spp_modern_image_url('badges/reputation.png'),
    'skills' => spp_modern_image_url('badges/skills.png'),
    'professions' => spp_modern_image_url('badges/professions.png'),
    'quest log' => spp_modern_image_url('badges/quest-log.png'),
    'achievements' => spp_modern_image_url('badges/point-shield.png'),
    'social' => spp_modern_image_url('badges/social.png'),
);
$achievementTabIcon = $tabIcons['achievements'];
?>
<div class="character-page">
<?php if ($pageError !== ''): ?>
  <div class="character-error"><?php echo htmlspecialchars($pageError); ?></div>
<?php elseif ($character): ?>
  <section class="character-hero">
    <div class="character-hero-mark" aria-hidden="true"><img src="<?php echo htmlspecialchars($factionHeroLogo); ?>" alt=""></div>
    <div class="character-hero-primary">
      <div class="character-identity">
        <div>
          <img class="character-portrait" src="<?php echo htmlspecialchars($portraitUrl); ?>" alt="">
        </div>
        <div>
          <p class="character-eyebrow"><?php echo htmlspecialchars($realmLabel); ?> Character Profile</p>
          <h1 class="character-title class-<?php echo htmlspecialchars($classSlug); ?>"><a href="<?php echo htmlspecialchars($characterUrl); ?>"><?php echo htmlspecialchars($characterName); ?></a></h1>
          <p class="character-subtitle">Level <?php echo (int)$character['level']; ?> <?php echo htmlspecialchars($raceNames[(int)$character['race']] ?? 'Unknown'); ?> <?php echo htmlspecialchars($classNames[(int)$character['class']] ?? 'Unknown'); ?></p>
          <div class="character-guildline"><?php if ($guildId > 0 && $guildName !== ''): ?><a class="character-guildline__link" href="<?php echo htmlspecialchars('index.php?n=server&sub=guild&realm=' . (int)$realmId . '&guildid=' . $guildId); ?>">&lt;<?php echo htmlspecialchars($guildName); ?>&gt;</a><?php else: ?><span class="character-guildline__empty">Unaffiliated</span><?php endif; ?></div>
        </div>
      </div>
      <?php if (!empty(trim((string)($forumSocial['rendered_signature'] ?? '')))): ?>
        <div class="character-hero-signature">
          <span class="character-hero-signature-label">Signature</span>
          <div class="character-hero-signature-text"><?php echo $forumSocial['rendered_signature']; ?></div>
        </div>
      <?php endif; ?>
    </div>
    <div class="character-hero-grid">
      <div class="character-stat-card"><span class="character-stat-label">Play Time</span><div class="character-stat-value"><?php echo htmlspecialchars(spp_character_format_playtime($character['totaltime'] ?? 0)); ?></div><div class="character-fact-sub"><?php echo !empty($character['online']) ? 'Online' : 'Offline'; ?></div></div>
      <div class="character-stat-card"><span class="character-stat-label">Achievement Points</span><div class="character-stat-value"><?php echo (int)$achievementSummary['points']; ?></div></div>
      <div class="character-stat-card"><span class="character-stat-label">Honorable Kills</span><div class="character-stat-value"><?php echo number_format($honorableKills, 0, '.', ','); ?></div></div>
      <div class="character-stat-card"><span class="character-stat-label">Honor Points</span><div class="character-stat-value"><?php echo number_format($honorPoints, 0, '.', ','); ?></div></div>
    </div>
  </section>

  <nav class="character-tabs">
    <?php foreach ($tabs as $tabName): ?>
      <?php if ($tab === $tabName) continue; ?>
      <a class="character-tab<?php echo $tab === $tabName ? ' is-active' : ''; ?><?php echo isset($tabIcons[$tabName]) ? ' has-icon' : ''; ?><?php echo $tabName === 'achievements' ? ' is-achievements' : ''; ?>" href="<?php echo htmlspecialchars($characterUrl . '&tab=' . urlencode($tabName)); ?>">
        <?php if (isset($tabIcons[$tabName])): ?><img class="character-tab-icon" src="<?php echo htmlspecialchars($tabIcons[$tabName]); ?>" alt=""><?php endif; ?>
        <span><?php echo htmlspecialchars(ucfirst($tabName)); ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <?php if ($tab === 'overview'): ?>
    <section class="character-grid character-grid-overview">
      <div class="character-panel"><div class="character-facts"><div class="character-fact"><span>Realm</span><strong><?php echo htmlspecialchars($realmLabel); ?></strong></div><div class="character-fact"><span>Guild</span><strong><?php echo $guildName !== "" ? htmlspecialchars($guildName) : "Unaffiliated"; ?></strong></div><div class="character-fact"><span>Time At Level</span><strong><?php echo htmlspecialchars(spp_character_format_playtime((int)($character['leveltime'] ?? 0))); ?></strong></div><div class="character-fact"><span>Gear Rank</span><strong><?php echo htmlspecialchars($gearRank); ?></strong><?php if ($averageItemLevel > 0): ?><div class="character-fact-sub">Average item level <?php echo number_format($averageItemLevel, 1); ?></div><?php endif; ?></div><div class="character-fact"><span>Recent Gear</span><?php if (!empty($recentGear)): ?><div class="character-fact-list"><?php foreach ($recentGear as $item): ?><a class="character-fact-link quality-<?php echo (int)$item['quality']; ?>" href="<?php echo htmlspecialchars('index.php?n=server&sub=item&realm=' . (int)$realmId . '&item=' . (int)$item['entry']); ?>" onmousemove="modernMoveTooltip(event)" onmouseover="modernRequestTooltip(event, <?php echo (int)$item['entry']; ?>, <?php echo (int)$realmId; ?>)" onmouseout="modernHideTooltip()"><img src="<?php echo htmlspecialchars($item['icon']); ?>" alt=""><span><?php echo htmlspecialchars($item['name']); ?></span></a><?php endforeach; ?></div><?php else: ?><strong>No recent gear recorded.</strong><?php endif; ?></div><div class="character-fact"><span>Recently Completed Quests</span><?php if (!empty($completedQuestHistory)): ?><div class="character-fact-list"><?php foreach (array_slice($completedQuestHistory, 0, 5) as $quest): ?><div class="character-fact-link is-quest"><?php echo htmlspecialchars($quest['title']); ?></div><?php endforeach; ?></div><?php else: ?><strong>No completed quests recorded.</strong><?php endif; ?></div><div class="character-fact"><span>Last Instance</span><strong><?php echo $lastInstance !== "" ? htmlspecialchars($lastInstance) : "No recorded run"; ?></strong><?php if ($lastInstanceDate > 0): ?><div class="character-fact-sub"><?php echo gmdate('M j, Y', $lastInstanceDate); ?></div><?php endif; ?></div></div></div>
      <section class="character-panel character-gear-showcase">
         <div class="character-gear-stage character-paperdoll-modern" style="--character-paperdoll-bg-image:url('<?php echo htmlspecialchars($paperdollBackgroundUrl, ENT_QUOTES); ?>');">
          <div class="character-gear-column character-gear-column-left">
            <?php foreach ($paperdollLeftSlots as $slotId): $item = $equipment[$slotId] ?? null; ?>
              <div class="character-gear-slot">
                <?php if ($item): ?>
                                <a class="character-gear-card" href="<?php echo htmlspecialchars('index.php?n=server&sub=item&realm=' . (int)$realmId . '&item=' . (int)$item['entry']); ?>" onmousemove="modernMoveTooltip(event)" onmouseover="modernRequestTooltip(event, <?php echo (int)$item['entry']; ?>, <?php echo (int)$realmId; ?>, <?php echo (int)($item['item_guid'] ?? 0); ?>)" onmouseout="modernHideTooltip()">
                    <img src="<?php echo htmlspecialchars($item['icon']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                  </a>
                <?php else: ?>
                  <div class="character-gear-card is-empty" data-slot="<?php echo htmlspecialchars($slotNames[$slotId] ?? ('Slot ' . $slotId)); ?>"></div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="character-gear-center character-paperdoll-core">
            <div class="character-paperdoll-top">
              <div class="character-paperdoll-card">
                <h3>Talent Specialization</h3>
                <div class="character-paperdoll-spec">
                  <?php if (!empty($talentList[0]['icon'])): ?><div class="character-paperdoll-spec-icon"><img src="<?php echo htmlspecialchars($talentList[0]['icon']); ?>" alt=""></div><?php endif; ?>
                  <div>
                    <div class="character-paperdoll-spec-name"><?php echo htmlspecialchars($specName); ?></div>
                    <div class="character-paperdoll-spec-breakdown"><?php echo htmlspecialchars($specBreakdown); ?></div>
                  </div>
                </div>
                <div class="character-paperdoll-trees">
                  <?php if (!empty($talentTreesView)): ?>
                    <?php foreach ($talentTreesView as $tree): ?>
                      <div class="character-paperdoll-tree">
                        <strong><?php echo htmlspecialchars($tree['name']); ?></strong>
                        <span><?php echo (int)$tree['points']; ?> / <?php echo (int)$talentBarCap; ?></span>
                        <div class="character-paperdoll-tree-bar"><b style="width: <?php echo min(100, max(0, round(((int)$tree['points'] / $talentBarCap) * 100))); ?>%"></b></div>
                      </div>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <div class="character-empty">No talent data yet.</div>
                  <?php endif; ?>
                </div>
              </div>
              <div class="character-paperdoll-card">
                <h3>Resistances</h3>
                <div class="character-paperdoll-resists">
                  <?php foreach (array('Arcane', 'Fire', 'Nature', 'Frost', 'Shadow') as $label): ?>
                    <div class="character-paperdoll-resist"><span><?php echo htmlspecialchars($label); ?></span><strong><?php echo (int)$resistanceStats[$label]; ?></strong></div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
            <div class="character-sheet-bars">
              <div class="character-sheet-bar-row"><span>Health</span><div class="character-sheet-bar-track"><div class="character-sheet-bar-fill is-health" style="width: <?php echo $healthMax > 0 ? min(100, max(0, round(($healthCurrent / $healthMax) * 100))) : 0; ?>%;"></div><div class="character-sheet-bar-value"><?php echo number_format($healthCurrent, 0, '.', ','); ?> / <?php echo number_format($healthMax, 0, '.', ','); ?></div></div></div>
              <div class="character-sheet-bar-row"><span><?php echo htmlspecialchars($primaryResource['label']); ?></span><div class="character-sheet-bar-track"><div class="character-sheet-bar-fill <?php echo htmlspecialchars($primaryResource['class']); ?>" style="width: <?php echo $resourceMax > 0 ? min(100, max(0, round(($resourceCurrent / $resourceMax) * 100))) : 0; ?>%;"></div><div class="character-sheet-bar-value"><?php echo number_format($resourceCurrent, 0, '.', ','); ?> / <?php echo number_format($resourceMax, 0, '.', ','); ?></div></div></div>
            </div>
          </div>
          <div class="character-gear-column character-gear-column-right">
            <?php foreach ($paperdollRightSlots as $slotId): $item = $equipment[$slotId] ?? null; ?>
              <div class="character-gear-slot">
                <?php if ($item): ?>
                                <a class="character-gear-card" href="<?php echo htmlspecialchars('index.php?n=server&sub=item&realm=' . (int)$realmId . '&item=' . (int)$item['entry']); ?>" onmousemove="modernMoveTooltip(event)" onmouseover="modernRequestTooltip(event, <?php echo (int)$item['entry']; ?>, <?php echo (int)$realmId; ?>, <?php echo (int)($item['item_guid'] ?? 0); ?>)" onmouseout="modernHideTooltip()">
                    <img src="<?php echo htmlspecialchars($item['icon']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                  </a>
                <?php else: ?>
                  <div class="character-gear-card is-empty" data-slot="<?php echo htmlspecialchars($slotNames[$slotId] ?? ('Slot ' . $slotId)); ?>"></div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
          <div class="character-gear-bottom">
            <?php foreach ($paperdollBottomSlots as $slotId): $item = $equipment[$slotId] ?? null; ?>
              <div class="character-gear-slot">
                <?php if ($item): ?>
                                <a class="character-gear-card" href="<?php echo htmlspecialchars('index.php?n=server&sub=item&realm=' . (int)$realmId . '&item=' . (int)$item['entry']); ?>" onmousemove="modernMoveTooltip(event)" onmouseover="modernRequestTooltip(event, <?php echo (int)$item['entry']; ?>, <?php echo (int)$realmId; ?>, <?php echo (int)($item['item_guid'] ?? 0); ?>)" onmouseout="modernHideTooltip()">
                    <img src="<?php echo htmlspecialchars($item['icon']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                  </a>
                <?php else: ?>
                  <div class="character-gear-card is-empty" data-slot="<?php echo htmlspecialchars($slotNames[$slotId] ?? ('Slot ' . $slotId)); ?>"></div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>
    </section>
      <section class="character-panel">
        <div class="character-paperdoll-stats-wide">
          <div class="character-sheet-statbox">
            <div class="character-paperdoll-select-wrap"><select class="character-paperdoll-select" onchange="sppPaperdollSwap(this, 'paperdoll-left-panels')"><?php foreach ($paperdollLeftPanels as $key => $panel): ?><option value="<?php echo htmlspecialchars($key); ?>"><?php echo htmlspecialchars($panel['label']); ?></option><?php endforeach; ?></select></div>
            <div class="character-sheet-statbox-body"><div id="paperdoll-left-panels"><?php foreach ($paperdollLeftPanels as $key => $panel): ?><div class="stats-panel<?php echo $key === 'base' ? ' is-active' : ''; ?>" data-panel="<?php echo htmlspecialchars($key); ?>"><ul class="character-sheet-statlist"><?php foreach ($panel['rows'] as $label => $value): ?><li class="character-sheet-statrow"><span><?php echo htmlspecialchars($label); ?></span><strong><?php echo htmlspecialchars((string)$value); ?></strong></li><?php endforeach; ?></ul></div><?php endforeach; ?></div></div>
          </div>
          <div class="character-sheet-statbox">
            <div class="character-paperdoll-select-wrap"><select class="character-paperdoll-select" onchange="sppPaperdollSwap(this, 'paperdoll-right-panels')"><?php foreach ($paperdollRightPanels as $key => $panel): ?><option value="<?php echo htmlspecialchars($key); ?>"<?php echo $key === $paperdollRightDefault ? ' selected' : ''; ?>><?php echo htmlspecialchars($panel['label']); ?></option><?php endforeach; ?></select></div>
            <div class="character-sheet-statbox-body"><div id="paperdoll-right-panels"><?php foreach ($paperdollRightPanels as $key => $panel): ?><div class="stats-panel<?php echo $key === $paperdollRightDefault ? ' is-active' : ''; ?>" data-panel="<?php echo htmlspecialchars($key); ?>"><ul class="character-sheet-statlist"><?php foreach ($panel['rows'] as $label => $value): ?><li class="character-sheet-statrow"><span><?php echo htmlspecialchars($label); ?></span><strong><?php echo htmlspecialchars((string)$value); ?></strong></li><?php endforeach; ?></ul></div><?php endforeach; ?></div></div>
          </div>
        </div>
      </section>
      <?php if (!empty($gearProgression['has_history']) && !empty($gearProgression['chart']) && !empty($gearProgression['chart']['points'])): ?>
      <section class="character-panel character-progress-panel">
        <div>
          <h2 class="character-panel-title">Gear Progression</h2>
          <div class="character-fact-sub">Snapshot-based equipped item level history from bot rotation logging.</div>
        </div>
        <div class="character-progress-summary">
          <div class="character-progress-card">
            <span class="character-progress-card-label">Current Avg iLvl</span>
            <span class="character-progress-card-value"><?php echo number_format((float)$gearProgression['latest_ilvl'], 1); ?></span>
            <div class="character-progress-card-meta">Last snapshot <?php echo htmlspecialchars($gearProgression['latest_snapshot_label']); ?></div>
          </div>
          <div class="character-progress-card">
            <span class="character-progress-card-label">Net Change</span>
            <span class="character-progress-card-value"><?php echo ($gearProgression['delta_ilvl'] >= 0 ? '+' : '') . number_format((float)$gearProgression['delta_ilvl'], 1); ?></span>
            <div class="character-progress-card-meta">Since <?php echo htmlspecialchars($gearProgression['first_snapshot_label']); ?></div>
          </div>
          <div class="character-progress-card">
            <span class="character-progress-card-label">Peak Avg iLvl</span>
            <span class="character-progress-card-value"><?php echo number_format((float)$gearProgression['peak_ilvl'], 1); ?></span>
            <div class="character-progress-card-meta"><?php echo (int)$gearProgression['snapshot_count']; ?> tracked snapshots</div>
          </div>
          <div class="character-progress-card">
            <span class="character-progress-card-label">Latest Snapshot</span>
            <span class="character-progress-card-value"><?php echo (int)($gearProgression['latest_level'] ?? 0); ?></span>
            <div class="character-progress-card-meta"><?php echo (int)($gearProgression['latest_equipped_count'] ?? 0); ?> equipped items averaged Ã¢â‚¬Â¢ <?php echo !empty($gearProgression['latest_online']) ? 'Online' : 'Offline'; ?></div>
          </div>
        </div>
        <div class="character-progress-chart">
          <svg viewBox="0 0 <?php echo (int)$gearProgression['chart']['width']; ?> <?php echo (int)$gearProgression['chart']['height']; ?>" role="img" aria-label="Average equipped item level progression over time">
            <defs>
              <linearGradient id="gearProgressFill" x1="0" x2="0" y1="0" y2="1">
                <stop offset="0%" stop-color="#ffd467" stop-opacity="0.42"></stop>
                <stop offset="100%" stop-color="#ffd467" stop-opacity="0.02"></stop>
              </linearGradient>
            </defs>
            <g class="character-progress-grid">
              <?php foreach ($gearProgression['chart']['y_ticks'] as $tick): ?>
                <line x1="52" x2="<?php echo (int)$gearProgression['chart']['width'] - 18; ?>" y1="<?php echo htmlspecialchars((string)$tick['y']); ?>" y2="<?php echo htmlspecialchars((string)$tick['y']); ?>"></line>
                <text x="44" y="<?php echo htmlspecialchars((string)($tick['y'] + 4)); ?>" text-anchor="end"><?php echo htmlspecialchars($tick['label']); ?></text>
              <?php endforeach; ?>
            </g>
            <?php if ($gearProgression['chart']['area_path'] !== ''): ?><path class="character-progress-area" d="<?php echo htmlspecialchars($gearProgression['chart']['area_path']); ?>"></path><?php endif; ?>
            <?php if ($gearProgression['chart']['path'] !== ''): ?><path class="character-progress-line" d="<?php echo htmlspecialchars($gearProgression['chart']['path']); ?>"></path><?php endif; ?>
            <?php foreach ($gearProgression['chart']['points'] as $point): ?>
              <circle class="character-progress-dot" cx="<?php echo htmlspecialchars((string)$point['x']); ?>" cy="<?php echo htmlspecialchars((string)$point['y']); ?>" r="4.5">
                <title><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($point['snapshot_time'])) . ' - iLvl ' . number_format((float)$point['value'], 1) . ' - Level ' . (int)$point['level'] . ' - ' . (int)$point['equipped_item_count'] . ' items - ' . ($point['online'] ? 'Online' : 'Offline')); ?></title>
              </circle>
            <?php endforeach; ?>
            <g class="character-progress-axis">
              <?php foreach ($gearProgression['chart']['x_labels'] as $label): ?>
                <text x="<?php echo htmlspecialchars((string)$label['x']); ?>" y="<?php echo (int)$gearProgression['chart']['height'] - 8; ?>" text-anchor="middle"><?php echo htmlspecialchars($label['label']); ?></text>
              <?php endforeach; ?>
            </g>
          </svg>
          <div class="character-progress-caption">
            <span>Start: <?php echo number_format((float)$gearProgression['first_ilvl'], 1); ?> avg iLvl</span>
            <span>Latest: <?php echo number_format((float)$gearProgression['latest_ilvl'], 1); ?> avg iLvl</span>
            <span>Peak: <?php echo number_format((float)$gearProgression['peak_ilvl'], 1); ?> avg iLvl</span>
          </div>
        </div>
      </section>
      <?php endif; ?>
  <?php endif; ?>

  <?php if ($tab === 'talents'): ?><div class="character-talents-embed"><?php echo $talent_embed_markup; ?></div><?php endif; ?>
  <?php if ($tab === 'reputation'): ?><section class="character-panel"><h2 class="character-panel-title">Reputation</h2><?php if (!empty($reputationSections)): ?><div class="character-reputation-sections"><?php $repSectionIndex = 0; foreach ($reputationSections as $sectionLabel => $sectionReputations): ?><details class="character-reputation-section collapse-card"<?php echo $repSectionIndex === 0 ? ' open' : ''; ?>><summary class="character-reputation-summary collapse-card__summary"><span class="collapse-card__copy"><strong class="collapse-card__title"><?php echo htmlspecialchars($sectionLabel); ?></strong><span class="collapse-card__meta"><?php echo (int)count($sectionReputations); ?> factions</span></span><span class="collapse-card__caret" aria-hidden="true"></span></summary><div class="character-reputation-body collapse-card__body"><div class="character-reputation-list"><?php foreach ($sectionReputations as $reputation): ?><article class="character-reputation-item rep-<?php echo htmlspecialchars($reputation['tier']); ?>"><div class="character-reputation-head"><div class="character-reputation-identity"><?php if (!empty($reputation['icon'])): ?><img class="character-reputation-icon" src="<?php echo htmlspecialchars((string)$reputation['icon']); ?>" alt="" loading="lazy"><?php endif; ?><h4 class="character-reputation-name"><?php echo htmlspecialchars($reputation['name']); ?></h4></div><span class="character-reputation-rank"><?php echo htmlspecialchars($reputation['label']); ?></span></div><div class="character-reputation-track"><div class="character-reputation-fill" style="width: <?php echo (int)$reputation['percent']; ?>%"></div><div class="character-reputation-value"><?php echo (int)$reputation['value']; ?>/<?php echo (int)$reputation['max']; ?></div></div><div class="character-reputation-meta"><?php if ($reputation['description'] !== ''): ?><?php echo htmlspecialchars($reputation['description']); ?><?php endif; ?></div></article><?php endforeach; ?></div></div></details><?php $repSectionIndex++; endforeach; ?></div><?php elseif (!empty($reputations)): ?><div class="character-reputation-list"><?php foreach ($reputations as $reputation): ?><article class="character-reputation-item rep-<?php echo htmlspecialchars($reputation['tier'] ?? spp_character_reputation_tier($reputation['label'] ?? 'neutral')); ?>"><div class="character-reputation-head"><div class="character-reputation-identity"><?php if (!empty($reputation['icon'])): ?><img class="character-reputation-icon" src="<?php echo htmlspecialchars((string)$reputation['icon']); ?>" alt="" loading="lazy"><?php endif; ?><h4 class="character-reputation-name"><?php echo htmlspecialchars($reputation['name']); ?></h4></div><span class="character-reputation-rank"><?php echo htmlspecialchars($reputation['label']); ?></span></div><div class="character-reputation-track"><div class="character-reputation-fill" style="width: <?php echo (int)$reputation['percent']; ?>%"></div><div class="character-reputation-value"><?php echo (int)$reputation['value']; ?>/<?php echo (int)$reputation['max']; ?></div></div><div class="character-reputation-meta"><?php if ($reputation['description'] !== ''): ?><?php echo htmlspecialchars($reputation['description']); ?><?php endif; ?></div></article><?php endforeach; ?></div><?php else: ?><div class="character-empty">No visible reputations were found for this character.</div><?php endif; ?></section><?php endif; ?>
  <?php if ($tab === 'social'): ?>
    <section class="character-panel">
      <h2 class="character-panel-title">Social</h2>
      <div class="character-social-summary">
        <div class="character-social-card">
          <span class="character-social-card-label">Forum Posts</span>
          <span class="character-social-card-value"><?php echo (int)$forumSocial['posts']; ?></span>
          <div class="character-social-card-meta"><?php echo !empty($forumSocial['last_post']) ? htmlspecialchars(date('M j, Y g:i A', (int)$forumSocial['last_post'])) : 'No posts yet'; ?></div>
        </div>
        <div class="character-social-card">
          <span class="character-social-card-label">Topics Started</span>
          <span class="character-social-card-value"><?php echo (int)$forumSocial['topics']; ?></span>
          <div class="character-social-card-meta"><?php echo !empty($forumSocial['last_topic']) ? htmlspecialchars(date('M j, Y g:i A', (int)$forumSocial['last_topic'])) : 'No topics yet'; ?></div>
        </div>
        <?php if ((int)($user['gmlevel'] ?? 0) >= 3): ?>
          <div class="character-social-card">
            <span class="character-social-card-label">Forum Account</span>
            <span class="character-social-card-value">
              <?php if (!empty($forumSocial['account_username']) && !empty($forumSocial['account_link'])): ?>
                <a class="character-social-account-link" href="<?php echo htmlspecialchars((string)$forumSocial['account_link']); ?>"><?php echo htmlspecialchars((string)$forumSocial['account_username']); ?></a>
              <?php elseif (!empty($forumSocial['account_username'])): ?>
                <?php echo htmlspecialchars((string)$forumSocial['account_username']); ?>
              <?php elseif ((int)$forumSocial['account_id'] > 0): ?>
                Account #<?php echo (int)$forumSocial['account_id']; ?>
              <?php else: ?>
                Unlinked
              <?php endif; ?>
            </span>
            <div class="character-social-card-meta">
              <?php if ((int)$forumSocial['identity_id'] > 0): ?>
                Identity #<?php echo (int)$forumSocial['identity_id']; ?> linked to this character
              <?php else: ?>
                No character forum identity found
              <?php endif; ?>
            </div>
          </div>
        <?php else: ?>
          <div class="character-social-card">
            <span class="character-social-card-label">Forum Identity</span>
            <span class="character-social-card-value"><?php echo (int)$forumSocial['identity_id'] > 0 ? 'Linked' : 'Unlinked'; ?></span>
            <div class="character-social-card-meta"><?php echo (int)$forumSocial['identity_id'] > 0 ? ('Identity #' . (int)$forumSocial['identity_id']) : 'No character forum identity found'; ?></div>
          </div>
        <?php endif; ?>
        <div class="character-social-card">
          <span class="character-social-card-label">Rotation Tracking</span>
          <span class="character-social-card-value"><?php echo (int)$forumSocial['rotation_full_cycles']; ?></span>
          <div class="character-social-card-meta is-statlines">
            <?php if (!empty($forumSocial['rotation_tracked'])): ?>
              Online sessions: <?php echo (int)$forumSocial['rotation_online_sessions']; ?><br>
              Offline sessions: <?php echo (int)$forumSocial['rotation_offline_sessions']; ?><br>
              Online avg: <?php echo htmlspecialchars(spp_character_format_duration_short($forumSocial['rotation_online_avg_seconds'])); ?><br>
              Offline avg: <?php echo htmlspecialchars(spp_character_format_duration_short($forumSocial['rotation_offline_avg_seconds'])); ?><br>
              <?php if (!empty($forumSocial['rotation_last_online'])): ?>
                Online now: <?php echo htmlspecialchars(spp_character_format_duration_short($forumSocial['rotation_current_span_seconds'])); ?>
              <?php elseif (!empty($forumSocial['rotation_last_seen'])): ?>
                Last seen: <?php echo htmlspecialchars(date('M j, Y g:i A', strtotime((string)$forumSocial['rotation_last_seen']))); ?>
              <?php else: ?>
                Status: Offline
              <?php endif; ?>
            <?php else: ?>
              No bot rotation tracking found yet
            <?php endif; ?>
          </div>
        </div>
      </div>
      <div class="character-social-signature-block">
        <h3 class="character-skill-section-title">Signature</h3>
        <?php if (!empty(trim((string)$forumSocial['rendered_signature']))): ?>
          <div class="character-social-signature"><?php echo $forumSocial['rendered_signature']; ?></div>
        <?php else: ?>
          <div class="character-social-empty">This character does not have a forum signature yet.</div>
        <?php endif; ?>
      </div>
    </section>    <section class="character-social-grid">
      <details class="character-panel character-social-collapse collapse-card" open>
        <summary class="collapse-card__summary">
          <span class="collapse-card__copy">
            <strong class="collapse-card__title">Recent Posts</strong>
            <span class="collapse-card__meta"><?php echo !empty($forumSocial['recent_posts']) ? count($forumSocial['recent_posts']) : 0; ?> entries shown</span>
          </span>
          <span class="collapse-card__caret" aria-hidden="true"></span>
        </summary>
        <div class="collapse-card__body">
        <?php if (!empty($forumSocial['recent_posts'])): ?>
          <div class="character-social-list">
            <?php foreach ($forumSocial['recent_posts'] as $post): ?>
              <article class="character-social-item">
                <div class="character-social-item-top">
                  <a class="character-social-item-title" href="<?php echo htmlspecialchars('index.php?n=forum&sub=viewtopic&tid=' . (int)$post['topic_id'] . '&to=' . (int)$post['post_id'] . '#post' . (int)$post['post_id']); ?>"><?php echo htmlspecialchars((string)($post['topic_name'] ?? ('Topic #' . (int)$post['topic_id']))); ?></a>
                </div>
                <div class="character-social-item-meta"><?php echo !empty($post['posted']) ? htmlspecialchars(date('M j, Y g:i A', (int)$post['posted'])) : 'Unknown date'; ?></div>
                <?php if (!empty($post['forum_name'])): ?>
                  <a class="character-social-item-forum" href="<?php echo htmlspecialchars('index.php?n=forum&sub=viewforum&fid=' . (int)$post['forum_id']); ?>"><?php echo htmlspecialchars((string)$post['forum_name']); ?></a>
                <?php endif; ?>
                <?php if (!empty(trim((string)($post['excerpt'] ?? '')))): ?>
                  <div class="character-social-item-excerpt"><?php echo htmlspecialchars((string)$post['excerpt']); ?><?php if (strlen((string)$post['excerpt']) >= 180) echo '...'; ?></div>
                <?php endif; ?>
              </article>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="character-social-empty">This character has not posted on the forum yet.</div>
        <?php endif; ?>
        </div>
      </details>

      <details class="character-panel character-social-collapse collapse-card" open>
        <summary class="collapse-card__summary">
          <span class="collapse-card__copy">
            <strong class="collapse-card__title">Recent Topics</strong>
            <span class="collapse-card__meta"><?php echo !empty($forumSocial['recent_topics']) ? count($forumSocial['recent_topics']) : 0; ?> entries shown</span>
          </span>
          <span class="collapse-card__caret" aria-hidden="true"></span>
        </summary>
        <div class="collapse-card__body">
        <?php if (!empty($forumSocial['recent_topics'])): ?>
          <div class="character-social-list">
            <?php foreach ($forumSocial['recent_topics'] as $topic): ?>
              <article class="character-social-item">
                <div class="character-social-item-top">
                  <a class="character-social-item-title" href="<?php echo htmlspecialchars('index.php?n=forum&sub=viewtopic&tid=' . (int)$topic['topic_id']); ?>"><?php echo htmlspecialchars((string)($topic['topic_name'] ?? ('Topic #' . (int)$topic['topic_id']))); ?></a>
                </div>
                <div class="character-social-item-meta">
                  Started <?php echo !empty($topic['topic_posted']) ? htmlspecialchars(date('M j, Y g:i A', (int)$topic['topic_posted'])) : 'Unknown date'; ?>
                  - <?php echo (int)($topic['num_replies'] ?? 0); ?> replies
                </div>
                <?php if (!empty($topic['forum_name'])): ?>
                  <a class="character-social-item-forum" href="<?php echo htmlspecialchars('index.php?n=forum&sub=viewforum&fid=' . (int)$topic['forum_id']); ?>"><?php echo htmlspecialchars((string)$topic['forum_name']); ?></a>
                <?php endif; ?>
                <?php if (!empty($topic['last_post'])): ?>
                  <div class="character-social-item-excerpt">Last reply <?php echo htmlspecialchars(date('M j, Y g:i A', (int)$topic['last_post'])); ?></div>
                <?php endif; ?>
              </article>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="character-social-empty">This character has not started any forum topics yet.</div>
        <?php endif; ?>
        </div>
      </details>
    </section>
  <?php endif; ?>
  <?php if ($tab === 'personality' && $canManageBotPersonality): ?>
    <section class="character-panel character-admin-grid">
      <?php if (!empty($personality_saved)): ?>
        <div class="character-admin-banner">
          LLM personality saved for <?php echo htmlspecialchars($characterName); ?>.
          <?php if (($personality_mode ?? '') === 'sql'): ?>
            No safe remote command exists for this setting, so it was saved directly to SQL and the bot will pick it up on next login/load.
          <?php endif; ?>
        </div>
      <?php endif; ?>
      <?php if (!empty($signature_saved)): ?>
        <div class="character-admin-banner">Bot signature saved for <?php echo htmlspecialchars($characterName); ?>.</div>
      <?php endif; ?>
      <?php if (!empty($bot_strategy_saved)): ?>
        <div class="character-admin-banner">
          Bot strategy overrides saved for <?php echo htmlspecialchars($characterName); ?>.
          <?php if (($bot_strategy_mode ?? '') === 'sql_fallback'): ?>
            SOAP was unavailable, so a direct SQL fallback write was used. This is less safe while the world server is offline.
          <?php endif; ?>
        </div>
      <?php endif; ?>
      <?php if ($characterAdminFeedback !== ''): ?>
        <div class="character-admin-banner"><?php echo htmlspecialchars($characterAdminFeedback); ?></div>
      <?php endif; ?>
      <?php if ($characterAdminError !== ''): ?>
        <div class="character-admin-banner is-error"><?php echo htmlspecialchars($characterAdminError); ?></div>
      <?php endif; ?>

      <div class="character-admin-meta">
        <div class="character-admin-stat"><span>Bot</span><strong><?php echo htmlspecialchars($characterName); ?></strong></div>
        <div class="character-admin-stat"><span>Guild</span><strong><?php echo $guildName !== '' ? htmlspecialchars($guildName) : 'Unaffiliated'; ?></strong></div>
        <div class="character-admin-stat"><span>Website Identity</span><strong><?php echo (int)($forumSocial['identity_id'] ?? 0) > 0 ? ('Identity #' . (int)$forumSocial['identity_id']) : 'Not linked yet'; ?></strong></div>
      </div>

      <section class="character-admin-card">
        <h2 class="character-panel-title">How Seeding Works</h2>
        <div class="character-admin-note">
          Per-bot personality text is seeded from the Playerbots config file <code>AiPlayerbot.LLMDefaultPromptsFile</code>. On startup, the bot loader reads lines like <code>Name::prompt text</code> and writes them into <code>ai_playerbot_db_store</code>. Saving from this page replaces that bot's stored override. The website signature is seeded separately from the bot identity: guild leaders get a guild-master line, and normal bots hash realm, guid, and name into a canned signature pool, with some bots intentionally left blank.
        </div>
      </section>

      <div class="character-admin-card-grid">
        <section class="character-admin-card character-admin-top-card">
          <h2 class="character-panel-title">LLM Personality</h2>
          <form class="character-admin-form" method="post" action="<?php echo htmlspecialchars($characterUrl . '&tab=personality'); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($characterPersonalityCsrfToken, ENT_QUOTES); ?>">
            <input type="hidden" name="character_admin_action" value="save_personality">
            <input type="hidden" name="character_guid" value="<?php echo (int)$characterGuid; ?>">
            <div>
              <label for="character-personality-text">Prompt Snapshot</label>
              <textarea id="character-personality-text" class="character-admin-textarea-compact" name="personality_text" placeholder="I am the keeper of the source."><?php echo htmlspecialchars($botPersonalityText); ?></textarea>
            </div>
            <div class="character-admin-actions">
              <button class="character-admin-button" type="submit">Save Personality</button>
              <span class="character-admin-note">Stored in <code>ai_playerbot_db_store</code> as this bot's prompt override.</span>
            </div>
          </form>
        </section>

        <section class="character-admin-card character-admin-top-card">
          <h2 class="character-panel-title">Bot Signature</h2>
          <form class="character-admin-form" method="post" action="<?php echo htmlspecialchars($characterUrl . '&tab=personality'); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($characterPersonalityCsrfToken, ENT_QUOTES); ?>">
            <input type="hidden" name="character_admin_action" value="save_signature">
            <input type="hidden" name="character_guid" value="<?php echo (int)$characterGuid; ?>">
            <div>
              <label for="character-llm-signature">Signature Text</label>
              <textarea id="character-llm-signature" class="character-admin-textarea-compact" name="llm_signature" placeholder="Watching the roads for the next adventure."><?php echo htmlspecialchars($botSignatureText); ?></textarea>
            </div>
            <div class="character-admin-actions">
              <button class="character-admin-button" type="submit">Save Signature</button>
              <span class="character-admin-note">Saved to the website identity profile for the public-facing forum identity.</span>
            </div>
          </form>
        </section>
      </div>

      <details class="character-admin-card is-collapsible collapse-card" open>
        <summary class="collapse-card__summary">
          <span class="collapse-card__copy">
            <strong class="collapse-card__title">Bot Strategy Builder</strong>
            <span class="collapse-card__meta">Per-bot strategy overrides for combat, non-combat, dead, and reaction states.</span>
          </span>
          <span class="collapse-card__caret" aria-hidden="true"></span>
        </summary>
        <div class="collapse-card__body">
        <form class="character-admin-form" method="post" action="<?php echo htmlspecialchars($characterUrl . '&tab=personality'); ?>">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($characterPersonalityCsrfToken, ENT_QUOTES); ?>">
          <input type="hidden" name="character_admin_action" value="save_bot_strategy">
          <input type="hidden" name="character_guid" value="<?php echo (int)$characterGuid; ?>">
          <div class="character-admin-form-grid">
            <div class="character-admin-form-wide">
              <label for="character-strategy-profile">Role Preset</label>
              <select id="character-strategy-profile" class="character-paperdoll-select" onchange="(function(sel){var data=JSON.parse(sel.options[sel.selectedIndex].getAttribute('data-strategy')||'{}');['co','nc','dead','react'].forEach(function(k){var el=document.getElementById('character-strategy-'+k); if(el && data[k]!==undefined){el.value=data[k];}});})(this)">
                <?php foreach ($botStrategyProfiles as $profileKey => $profile): ?>
                  <option value="<?php echo htmlspecialchars((string)$profileKey); ?>" data-strategy="<?php echo htmlspecialchars(json_encode(array(
                      'co' => (string)($profile['co'] ?? ''),
                      'nc' => (string)($profile['nc'] ?? ''),
                      'dead' => (string)($profile['dead'] ?? ''),
                      'react' => (string)($profile['react'] ?? ''),
                  )), ENT_QUOTES); ?>"<?php echo (($characterStrategyState['profile_key'] ?? 'custom') === $profileKey ? ' selected' : ''); ?>>
                    <?php echo htmlspecialchars((string)$profile['label']); ?> - <?php echo htmlspecialchars((string)$profile['description']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="character-admin-subcards character-admin-form-wide">
              <div class="character-admin-subcard">
                <label for="character-strategy-co">Combat (`co`)</label>
                <div class="character-strategy-builder">
                  <?php foreach (($strategyBuilderOptions['co'] ?? array()) as $token): ?>
                    <button class="character-strategy-chip" type="button" onclick="sppStrategyAppend('character-strategy-co', '<?php echo htmlspecialchars((string)$token, ENT_QUOTES); ?>', 'plus')">+<?php echo htmlspecialchars((string)$token); ?></button>
                  <?php endforeach; ?>
                </div>
                <textarea id="character-strategy-co" name="strategy_co" placeholder="+dps,+dps assist,-threat"><?php echo htmlspecialchars((string)($characterStrategyState['values']['co'] ?? '')); ?></textarea>
              </div>
              <div class="character-admin-subcard">
                <label for="character-strategy-nc">Non-Combat (`nc`)</label>
                <div class="character-strategy-builder">
                  <?php foreach (($strategyBuilderOptions['nc'] ?? array()) as $token): ?>
                    <button class="character-strategy-chip" type="button" onclick="sppStrategyAppend('character-strategy-nc', '<?php echo htmlspecialchars((string)$token, ENT_QUOTES); ?>', 'plus')">+<?php echo htmlspecialchars((string)$token); ?></button>
                  <?php endforeach; ?>
                </div>
                <textarea id="character-strategy-nc" name="strategy_nc" placeholder="+follow,+loot,+food"><?php echo htmlspecialchars((string)($characterStrategyState['values']['nc'] ?? '')); ?></textarea>
              </div>
              <div class="character-admin-subcard">
                <label for="character-strategy-dead">Dead (`dead`)</label>
                <div class="character-strategy-builder">
                  <?php foreach (($strategyBuilderOptions['dead'] ?? array()) as $token): ?>
                    <button class="character-strategy-chip" type="button" onclick="sppStrategyAppend('character-strategy-dead', '<?php echo htmlspecialchars((string)$token, ENT_QUOTES); ?>', 'plus')">+<?php echo htmlspecialchars((string)$token); ?></button>
                  <?php endforeach; ?>
                </div>
                <textarea id="character-strategy-dead" name="strategy_dead" placeholder="+auto release"><?php echo htmlspecialchars((string)($characterStrategyState['values']['dead'] ?? '')); ?></textarea>
              </div>
              <div class="character-admin-subcard">
                <label for="character-strategy-react">Reaction (`react`)</label>
                <div class="character-strategy-builder">
                  <?php foreach (($strategyBuilderOptions['react'] ?? array()) as $token): ?>
                    <button class="character-strategy-chip" type="button" onclick="sppStrategyAppend('character-strategy-react', '<?php echo htmlspecialchars((string)$token, ENT_QUOTES); ?>', 'plus')">+<?php echo htmlspecialchars((string)$token); ?></button>
                  <?php endforeach; ?>
                </div>
                <textarea id="character-strategy-react" name="strategy_react" placeholder="+pvp,+preheal"><?php echo htmlspecialchars((string)($characterStrategyState['values']['react'] ?? '')); ?></textarea>
              </div>
            </div>
          </div>
          <div class="character-admin-actions">
            <button class="character-admin-button" type="submit">Save Bot Strategy</button>
            <span class="character-admin-note">These are the effective per-bot strategy values. Saving here writes this bot's personal override layer in <code>ai_playerbot_db_store</code> and leaves any guild default flavor underneath.</span>
          </div>
        </form>
        </div>
      </details>

    </section>
  <?php endif; ?>
  <?php if ($tab === 'skills'): ?><section class="character-panel"><h2 class="character-panel-title">Skills</h2><?php if (!empty($skillsByCategory)): ?><div class="character-skill-sections"><?php $skillSectionIndex = 0; foreach ($skillsByCategory as $categoryName => $categorySkills): ?><details class="character-skill-section collapse-card"<?php echo $skillSectionIndex === 0 ? ' open' : ''; ?>><summary class="character-skill-summary collapse-card__summary"><span class="collapse-card__copy"><strong class="collapse-card__title"><?php echo htmlspecialchars($categoryName); ?></strong><span class="collapse-card__meta"><?php echo (int)count($categorySkills); ?> entries</span></span><span class="collapse-card__caret" aria-hidden="true"></span></summary><div class="character-skill-body collapse-card__body"><div class="character-skill-grid"><?php foreach ($categorySkills as $skill): ?><article class="character-skill-card"><div class="character-skill-card-head"><div class="character-skill-card-title"><img src="<?php echo htmlspecialchars($skill['icon']); ?>" alt=""><div><strong><?php echo htmlspecialchars($skill['name']); ?></strong><?php if ($skill['description'] !== ''): ?><div class="character-skill-meta"><?php echo htmlspecialchars($skill['description']); ?></div><?php endif; ?></div></div><span class="character-skill-rank"><?php echo htmlspecialchars($skill['display_value'] ?? ((int)$skill['value'] . '/' . (int)$skill['max'])); ?></span></div><div class="character-bar-track"><div class="character-bar-fill" style="width: <?php echo (int)$skill['percent']; ?>%"></div><div class="character-skill-value"><?php echo htmlspecialchars($skill['display_value'] ?? ((int)$skill['value'] . '/' . (int)$skill['max'])); ?></div></div></article><?php endforeach; ?></div></div></details><?php $skillSectionIndex++; endforeach; ?></div><?php else: ?><div class="character-empty">No non-class skills could be read from the realm database.</div><?php endif; ?></section><?php endif; ?>
<?php if ($tab === 'professions'): ?>
<section class="character-panel">
    <h2 class="character-panel-title">Professions</h2>
    <?php if (!empty($professionsByCategory)): ?>
    <div class="character-skill-sections">
        <?php $professionSectionIndex = 0; foreach ($professionsByCategory as $categoryName => $categorySkills): ?>
        <details class="character-skill-section collapse-card"<?php echo $professionSectionIndex === 0 ? ' open' : ''; ?>>
            <summary class="character-skill-summary collapse-card__summary">
                <span class="collapse-card__copy">
                    <strong class="collapse-card__title"><?php echo htmlspecialchars($categoryName); ?></strong>
                    <span class="collapse-card__meta"><?php echo (int)count($categorySkills); ?> entries</span>
                </span>
                <span class="collapse-card__caret" aria-hidden="true"></span>
            </summary>
            <div class="character-skill-body collapse-card__body">
            <div class="character-skill-grid">
                <?php foreach ($categorySkills as $skill): ?>
                <article class="character-skill-card">
                    <div class="character-skill-card-head">
                        <div class="character-skill-card-title">
                            <img src="<?php echo htmlspecialchars($skill['icon']); ?>" alt="">
                            <div>
                                <strong><?php echo htmlspecialchars($skill['name']); ?></strong>
                                <?php if (!empty($skill['specializations'])): ?>
                                <div class="character-skill-specialization"><?php echo htmlspecialchars(implode(' / ', $skill['specializations'])); ?></div>
                                <?php endif; ?>
                                <?php if ($skill['description'] !== ''): ?>
                                <div class="character-skill-meta"><?php echo htmlspecialchars($skill['description']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="character-skill-rank"><?php echo htmlspecialchars($skill['rank_label'] ?? ($skill['display_value'] ?? ((int)$skill['value'] . '/' . (int)$skill['max']))); ?></span>
                    </div>
                    <div class="character-bar-track">
                        <div class="character-bar-fill" style="width: <?php echo (int)$skill['percent']; ?>%"></div>
                        <div class="character-skill-value"><?php echo htmlspecialchars($skill['display_value'] ?? ((int)$skill['value'] . '/' . (int)$skill['max'])); ?></div>
                    </div>

                    <?php if (!empty($skill['recipes'])): ?>
                    <?php
                        $learnedRecipeCount = 0;
                        $unlearnedRecipeCount = 0;
                        $trainerRecipeCount = 0;
                        $nonTrainerRecipeCount = 0;
                        $recipeListId = 'characterRecipeList-' . (int)($skill['skill_id'] ?? 0);
                        foreach ($skill['recipes'] as $recipeMeta) {
                            if (!empty($recipeMeta['is_learned'])) {
                                $learnedRecipeCount++;
                            } else {
                                $unlearnedRecipeCount++;
                            }
                            if (!empty($recipeMeta['is_trainer'])) {
                                $trainerRecipeCount++;
                            } else {
                                $nonTrainerRecipeCount++;
                            }
                        }
                    ?>
                    <details class="character-profession-recipes collapse-card">
                        <summary class="collapse-card__summary">
                            <span class="collapse-card__copy">
                                <strong class="collapse-card__title">Profession Spellbook</strong>
                                <span class="collapse-card__meta">
                                    <?php echo (int)$learnedRecipeCount; ?> known
                                    <?php if ($unlearnedRecipeCount > 0): ?>
                                    - <?php echo (int)$unlearnedRecipeCount; ?> unlearned
                                    <?php endif; ?>
                                </span>
                            </span>
                            <span class="collapse-card__caret" aria-hidden="true"></span>
                        </summary>
                        <div class="collapse-card__body">
                            <?php if ($trainerRecipeCount > 0 || $nonTrainerRecipeCount > 0): ?>
                            <div class="character-recipe-filters">
                                <button type="button" class="character-recipe-filter is-active" onclick="sppRecipeFilter(this, '<?php echo htmlspecialchars($recipeListId); ?>', 'all')">All</button>
                                <?php if ($trainerRecipeCount > 0): ?>
                                <button type="button" class="character-recipe-filter" onclick="sppRecipeFilter(this, '<?php echo htmlspecialchars($recipeListId); ?>', 'trainer')">Trainer</button>
                                <?php endif; ?>
                                <?php if ($nonTrainerRecipeCount > 0): ?>
                                <button type="button" class="character-recipe-filter" onclick="sppRecipeFilter(this, '<?php echo htmlspecialchars($recipeListId); ?>', 'non-trainer')">Drop / Other</button>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        <div class="character-recipe-list" id="<?php echo htmlspecialchars($recipeListId); ?>">
                            <?php foreach ($skill['recipes'] as $recipe): ?>
                                <?php
                                    $rowTag = !empty($recipe['item_entry']) ? 'a' : 'div';
                                    $recipeTags = array(!empty($recipe['is_trainer']) ? 'trainer' : 'non-trainer');
                                    $recipeTags[] = !empty($recipe['is_learned']) ? 'known' : 'unlearned';
                                ?>
                                <<?php echo $rowTag; ?>
                                    class="character-recipe-row quality-<?php echo (int)$recipe['quality']; ?><?php echo empty($recipe['is_learned']) ? ' is-available' : ''; ?>"
                                    data-tags="|<?php echo htmlspecialchars(implode('|', $recipeTags)); ?>|"
                                    <?php if ($rowTag === 'a'): ?>
                                    href="<?php echo htmlspecialchars('index.php?n=server&sub=item&realm=' . (int)$realmId . '&item=' . (int)$recipe['item_entry']); ?>"
                                    onmousemove="modernMoveTooltip(event)"
                                    onmouseover="modernRequestTooltip(event, <?php echo (int)$recipe['item_entry']; ?>, <?php echo (int)$realmId; ?>)"
                                    onmouseout="modernHideTooltip()"
                                    <?php endif; ?>
                                >
                                    <img src="<?php echo htmlspecialchars($recipe['icon']); ?>" alt="<?php echo htmlspecialchars($recipe['spell_name']); ?>">
                                    <div class="character-recipe-copy">
                                        <div class="character-recipe-head">
                                            <strong><?php echo htmlspecialchars($recipe['spell_name']); ?></strong>
                                            <span class="character-recipe-status<?php echo empty($recipe['is_learned']) ? ' is-available' : ''; ?>">
                                                <?php echo !empty($recipe['is_learned']) ? 'Known' : (!empty($recipe['is_trainer']) ? 'Trainable' : 'Drop / Other'); ?>
                                            </span>
                                        </div>
                                        <div class="character-recipe-meta-line">
                                            <?php if (!empty($recipe['item_name'])): ?>
                                            <span class="character-recipe-source">Creates <?php echo htmlspecialchars($recipe['item_name']); ?></span>
                                            <?php elseif (!empty($recipe['created_items'])): ?>
                                            <span class="character-recipe-source">Creates <?php echo htmlspecialchars(implode(', ', array_map(function ($item) { return (string)($item['name'] ?? ''); }, $recipe['created_items']))); ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($recipe['required_rank'])): ?>
                                            <span class="character-recipe-source"><?php echo (int)$recipe['required_rank']; ?> skill</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </<?php echo $rowTag; ?>>
                            <?php endforeach; ?>
                        </div>
                        </div>
                    </details>
                    <?php elseif (stripos((string)$categoryName, 'profession') !== false || stripos((string)$categoryName, 'secondary') !== false): ?>
                    <div class="character-profession-recipes">
                        <div class="character-recipe-empty">No profession spellbook entries were recorded for this skill yet.</div>
                    </div>
                    <?php endif; ?>
                </article>
                <?php endforeach; ?>
            </div>
            </div>
        </details>
        <?php $professionSectionIndex++; endforeach; ?>
    </div>
    <?php else: ?>
    <div class="character-empty">No professions or secondary skills could be read from the realm database.</div>
    <?php endif; ?>
</section>
<?php endif; ?>
<?php if ($tab === 'quest log'): ?>
  <?php
    $questLogItems = array();
    $activeQuestCap = 20;
    $activeQuestCount = count($activeQuestLog);
    $completedQuestCount = count($completedQuestHistory);
    foreach ($activeQuestLog as $index => $quest) {
        $quest['panel_id'] = 'quest-panel-active-' . $index;
        $quest['entry_id'] = 'quest-entry-active-' . $index;
        $quest['group_label'] = 'Active';
        $questLogItems[] = $quest;
    }
    foreach ($completedQuestHistory as $index => $quest) {
        $quest['panel_id'] = 'quest-panel-completed-' . $index;
        $quest['entry_id'] = 'quest-entry-completed-' . $index;
        $quest['group_label'] = 'Completed';
        $questLogItems[] = $quest;
    }
    $initialQuestPanel = $questLogItems[0]['panel_id'] ?? '';
  ?>
  <section class="character-panel">
    <?php if (!empty($questLogItems)): ?>
      <div class="character-questlog-shell" data-questlog>
        <aside class="character-questlog-sidebar">
          <details class="character-questlog-group is-active collapse-card" data-quest-group="active" open>
            <summary class="collapse-card__summary">
              <span class="collapse-card__copy">
                <span class="character-questlog-heading collapse-card__title">Active Quests (<?php echo (int)$activeQuestCount; ?>/<?php echo (int)$activeQuestCap; ?>)</span>
              </span>
              <span class="collapse-card__caret" aria-hidden="true"></span>
            </summary>
            <div class="character-questlog-group-body collapse-card__body">
            <?php if (!empty($activeQuestLog)): ?>
              <div class="character-questlog-list is-scrollable">
                <?php foreach ($activeQuestLog as $index => $quest): $panelId = 'quest-panel-active-' . $index; $questDifficultyClass = spp_character_quest_difficulty_class((int)($quest['quest_level'] ?? 0), (int)($character['level'] ?? 0)); ?>
                  <button type="button" class="character-questlog-entry<?php echo $panelId === $initialQuestPanel ? ' is-active' : ''; ?>" data-quest-target="<?php echo htmlspecialchars($panelId); ?>" data-quest-group-owner="active">
                    <span class="character-questlog-entry-title <?php echo htmlspecialchars($questDifficultyClass); ?>"><?php echo htmlspecialchars($quest['title']); ?></span>
                    <span class="character-questlog-entry-meta-row">
                      <span class="character-questlog-entry-meta <?php echo htmlspecialchars($questDifficultyClass); ?>"><?php echo !empty($quest['quest_level']) ? 'Level ' . (int)$quest['quest_level'] : 'Quest'; ?></span>
                      <span class="character-questlog-entry-status character-questlog-status <?php echo htmlspecialchars(spp_character_quest_status_chip_class($quest['status_label'] ?? '')); ?>"><?php echo htmlspecialchars(spp_character_quest_status_chip_label($quest['status_label'] ?? '')); ?></span>
                    </span>
                  </button>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="character-questlog-empty">No active quests were found for this character.</div>
            <?php endif; ?>
            </div>
          </details>

          <?php if (!empty($completedQuestHistory)): ?>
            <details class="character-questlog-group is-completed collapse-card" data-quest-group="completed">
              <summary class="collapse-card__summary">
                <span class="collapse-card__copy">
                  <span class="character-questlog-heading collapse-card__title">Completed (<?php echo (int)$completedQuestCount; ?>/<?php echo (int)$completedQuestTotal; ?>)</span>
                </span>
                <span class="collapse-card__caret" aria-hidden="true"></span>
              </summary>
              <div class="character-questlog-group-body collapse-card__body">
              <div class="character-questlog-list is-scrollable">
                <?php foreach ($completedQuestHistory as $index => $quest): $panelId = 'quest-panel-completed-' . $index; $questDifficultyClass = spp_character_quest_difficulty_class((int)($quest['quest_level'] ?? 0), (int)($character['level'] ?? 0)); ?>
                  <button type="button" class="character-questlog-entry<?php echo $panelId === $initialQuestPanel ? ' is-active' : ''; ?>" data-quest-target="<?php echo htmlspecialchars($panelId); ?>" data-quest-group-owner="completed">
                    <span class="character-questlog-entry-title <?php echo htmlspecialchars($questDifficultyClass); ?>"><?php echo htmlspecialchars($quest['title']); ?></span>
                    <span class="character-questlog-entry-meta-row">
                      <span class="character-questlog-entry-meta <?php echo htmlspecialchars($questDifficultyClass); ?>"><?php echo !empty($quest['quest_level']) ? 'Level ' . (int)$quest['quest_level'] : 'Quest'; ?></span>
                      <span class="character-questlog-entry-status character-questlog-status is-complete">Completed</span>
                    </span>
                  </button>
                <?php endforeach; ?>
              </div>
              </div>
            </details>
          <?php endif; ?>
        </aside>

        <section class="character-questlog-detail">
          <?php foreach ($activeQuestLog as $index => $quest): $panelId = 'quest-panel-active-' . $index; ?>
            <article class="character-questlog-panel<?php echo $panelId === $initialQuestPanel ? ' is-active' : ''; ?>" data-quest-panel="<?php echo htmlspecialchars($panelId); ?>">
              <h3 class="character-questlog-title"><?php echo htmlspecialchars($quest['title']); ?></h3>
              <div class="character-questlog-level <?php echo htmlspecialchars(spp_character_quest_difficulty_class((int)($quest['quest_level'] ?? 0), (int)($character['level'] ?? 0))); ?>"><?php echo !empty($quest['quest_level']) ? 'Level ' . (int)$quest['quest_level'] : 'Active Quest'; ?></div>
              <div class="character-questlog-body"><?php echo spp_character_render_quest_text($quest['description'] ?? '', $character['name'] ?? 'adventurer') ?: '<span class="character-questlog-empty">No quest text is available for this quest entry.</span>'; ?></div>
              <div class="character-questlog-section">
                <h4 class="character-questlog-section-title">Objectives</h4>
                <?php if (!empty($quest['progress_parts'])): ?>
                  <div class="character-questlog-objectives">
                    <?php foreach ($quest['progress_parts'] as $part): ?>
                      <div class="character-questlog-objective"><?php echo htmlspecialchars($part); ?></div>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <div class="character-questlog-empty">No tracked objective counters are available yet.</div>
                <?php endif; ?>
              </div>
              <?php if (!empty($quest['rewards']['choice']) || !empty($quest['rewards']['guaranteed']) || !empty($quest['rewards']['money'])): ?>
                <div class="character-questlog-section">
                  <h4 class="character-questlog-section-title">Rewards</h4>
                  <div class="character-questlog-rewards">
                    <?php if (!empty($quest['rewards']['choice'])): ?>
                      <div class="character-questlog-reward-group">
                        <div class="character-questlog-reward-label">Choose One</div>
                        <div class="character-questlog-reward-items">
                          <?php foreach ($quest['rewards']['choice'] as $reward): ?>
                            <a class="character-questlog-reward-item quality-<?php echo (int)$reward['quality']; ?>" href="<?php echo htmlspecialchars('index.php?n=server&sub=item&realm=' . (int)$realmId . '&item=' . (int)$reward['entry']); ?>" onmousemove="modernMoveTooltip(event)" onmouseover="modernRequestTooltip(event, <?php echo (int)$reward['entry']; ?>, <?php echo (int)$realmId; ?>)" onmouseout="modernHideTooltip()"><img src="<?php echo htmlspecialchars($reward['icon']); ?>" alt=""><span><?php echo htmlspecialchars($reward['name']); ?><?php if ((int)$reward['count'] > 1): ?> x<?php echo (int)$reward['count']; ?><?php endif; ?></span></a>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    <?php endif; ?>
                    <?php if (!empty($quest['rewards']['guaranteed'])): ?>
                      <div class="character-questlog-reward-group">
                        <div class="character-questlog-reward-label">You Will Receive</div>
                        <div class="character-questlog-reward-items">
                          <?php foreach ($quest['rewards']['guaranteed'] as $reward): ?>
                            <a class="character-questlog-reward-item quality-<?php echo (int)$reward['quality']; ?>" href="<?php echo htmlspecialchars('index.php?n=server&sub=item&realm=' . (int)$realmId . '&item=' . (int)$reward['entry']); ?>" onmousemove="modernMoveTooltip(event)" onmouseover="modernRequestTooltip(event, <?php echo (int)$reward['entry']; ?>, <?php echo (int)$realmId; ?>)" onmouseout="modernHideTooltip()"><img src="<?php echo htmlspecialchars($reward['icon']); ?>" alt=""><span><?php echo htmlspecialchars($reward['name']); ?><?php if ((int)$reward['count'] > 1): ?> x<?php echo (int)$reward['count']; ?><?php endif; ?></span></a>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    <?php endif; ?>
                    <?php if (!empty($quest['rewards']['money']) && (int)$quest['rewards']['money'] > 0): ?>
                      <div class="character-questlog-reward-group">
                        <div class="character-questlog-reward-label">Money</div>
                        <div class="character-questlog-reward-money"><?php echo number_format((int)$quest['rewards']['money']); ?> copper</div>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>

          <?php foreach ($completedQuestHistory as $index => $quest): $panelId = 'quest-panel-completed-' . $index; ?>
            <article class="character-questlog-panel<?php echo $panelId === $initialQuestPanel ? ' is-active' : ''; ?>" data-quest-panel="<?php echo htmlspecialchars($panelId); ?>">
              <h3 class="character-questlog-title"><?php echo htmlspecialchars($quest['title']); ?></h3>
              <div class="character-questlog-level <?php echo htmlspecialchars(spp_character_quest_difficulty_class((int)($quest['quest_level'] ?? 0), (int)($character['level'] ?? 0))); ?>"><?php echo !empty($quest['quest_level']) ? 'Level ' . (int)$quest['quest_level'] : 'Completed Quest'; ?></div>
              <div class="character-questlog-body"><?php echo spp_character_render_quest_text($quest['description'] ?? '', $character['name'] ?? 'adventurer') ?: '<span class="character-questlog-empty">No quest text is available for this quest entry.</span>'; ?></div>
              <?php if (!empty($quest['rewards']['choice']) || !empty($quest['rewards']['guaranteed']) || !empty($quest['rewards']['money'])): ?>
                <div class="character-questlog-section">
                  <h4 class="character-questlog-section-title">Rewards</h4>
                  <div class="character-questlog-rewards">
                    <?php if (!empty($quest['rewards']['choice'])): ?>
                      <div class="character-questlog-reward-group">
                        <div class="character-questlog-reward-label">Choose One</div>
                        <div class="character-questlog-reward-items">
                          <?php foreach ($quest['rewards']['choice'] as $reward): ?>
                            <a class="character-questlog-reward-item quality-<?php echo (int)$reward['quality']; ?>" href="<?php echo htmlspecialchars('index.php?n=server&sub=item&realm=' . (int)$realmId . '&item=' . (int)$reward['entry']); ?>" onmousemove="modernMoveTooltip(event)" onmouseover="modernRequestTooltip(event, <?php echo (int)$reward['entry']; ?>, <?php echo (int)$realmId; ?>)" onmouseout="modernHideTooltip()"><img src="<?php echo htmlspecialchars($reward['icon']); ?>" alt=""><span><?php echo htmlspecialchars($reward['name']); ?><?php if ((int)$reward['count'] > 1): ?> x<?php echo (int)$reward['count']; ?><?php endif; ?></span></a>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    <?php endif; ?>
                    <?php if (!empty($quest['rewards']['guaranteed'])): ?>
                      <div class="character-questlog-reward-group">
                        <div class="character-questlog-reward-label">You Will Receive</div>
                        <div class="character-questlog-reward-items">
                          <?php foreach ($quest['rewards']['guaranteed'] as $reward): ?>
                            <a class="character-questlog-reward-item quality-<?php echo (int)$reward['quality']; ?>" href="<?php echo htmlspecialchars('index.php?n=server&sub=item&realm=' . (int)$realmId . '&item=' . (int)$reward['entry']); ?>" onmousemove="modernMoveTooltip(event)" onmouseover="modernRequestTooltip(event, <?php echo (int)$reward['entry']; ?>, <?php echo (int)$realmId; ?>)" onmouseout="modernHideTooltip()"><img src="<?php echo htmlspecialchars($reward['icon']); ?>" alt=""><span><?php echo htmlspecialchars($reward['name']); ?><?php if ((int)$reward['count'] > 1): ?> x<?php echo (int)$reward['count']; ?><?php endif; ?></span></a>
                          <?php endforeach; ?>
                        </div>
                      </div>
                    <?php endif; ?>
                    <?php if (!empty($quest['rewards']['money']) && (int)$quest['rewards']['money'] > 0): ?>
                      <div class="character-questlog-reward-group">
                        <div class="character-questlog-reward-label">Money</div>
                        <div class="character-questlog-reward-money"><?php echo number_format((int)$quest['rewards']['money']); ?> copper</div>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
        </section>
      </div>
    <?php else: ?>
      <div class="character-empty">No quest data was found for this character.</div>
    <?php endif; ?>
  </section>
<?php endif; ?>
<?php if ($tab === 'achievements'): ?><section class="character-panel"><h2 class="character-panel-title character-panel-title-with-icon"><img class="character-panel-title-icon" src="<?php echo htmlspecialchars($achievementTabIcon); ?>" alt=""><span>Achievements</span></h2><?php if ($achievementSummary['supported']): ?><div class="character-hero-grid" style="margin-bottom:18px;"><div class="character-stat-card"><span class="character-stat-label">Completed</span><div class="character-stat-value"><?php echo (int)$achievementSummary['count']; ?></div></div><div class="character-stat-card"><span class="character-stat-label">Points</span><div class="character-stat-value"><?php echo (int)$achievementSummary['points']; ?></div></div></div><?php if (!empty($achievementSummary['recent'])): ?><section class="character-achievement-section character-achievement-section-pinned" style="margin-bottom:22px;"><h3 class="character-achievement-section-title">Recent Earned</h3><div class="character-achievement-list"><?php foreach ($achievementSummary['recent'] as $achievement): ?><article class="character-achievement-item"><img class="character-achievement-icon" src="<?php echo htmlspecialchars($achievement['icon']); ?>" alt=""><div><div class="character-achievement-title"><?php echo htmlspecialchars($achievement['name']); ?></div><?php if (($achievement['description'] ?? '') !== ''): ?><div class="character-achievement-meta"><?php echo htmlspecialchars($achievement['description']); ?></div><?php endif; ?><div class="character-achievement-meta"><?php echo htmlspecialchars($achievement['category'] ?? ''); ?><?php if (($achievement['date_label'] ?? '') !== ''): ?> - <?php echo htmlspecialchars($achievement['date_label']); ?><?php endif; ?></div></div><div class="character-achievement-points-badge">+<?php echo (int)($achievement['points'] ?? 0); ?></div></article><?php endforeach; ?></div></section><?php endif; ?><?php if (!empty($achievementSummary['groups'])): ?><div class="character-achievement-sections"><?php foreach ($achievementSummary['groups'] as $groupName => $subgroups): ?><section class="character-achievement-section"><h3 class="character-achievement-section-title"><?php echo htmlspecialchars($groupName !== '' ? $groupName : 'Other'); ?></h3><?php foreach ($subgroups as $subgroupName => $achievements): ?><?php if ($subgroupName !== ''): ?><h4 class="character-achievement-subtitle"><?php echo htmlspecialchars($subgroupName); ?></h4><?php endif; ?><div class="character-achievement-grid"><?php foreach ($achievements as $achievement): ?><article class="character-achievement-item"><img class="character-achievement-icon" src="<?php echo htmlspecialchars($achievement['icon']); ?>" alt=""><div><div class="character-achievement-title"><?php echo htmlspecialchars($achievement['name']); ?></div><?php if (($achievement['description'] ?? '') !== ''): ?><div class="character-achievement-meta"><?php echo htmlspecialchars($achievement['description']); ?></div><?php endif; ?><?php if (($achievement['date_label'] ?? '') !== ''): ?><div class="character-achievement-meta"><?php echo htmlspecialchars($achievement['date_label']); ?></div><?php endif; ?></div><div class="character-achievement-points-badge">+<?php echo (int)($achievement['points'] ?? 0); ?></div></article><?php endforeach; ?></div><?php endforeach; ?></section><?php endforeach; ?></div><?php elseif (empty($achievementSummary['recent'])): ?><div class="character-empty">This character has no recorded achievements yet.</div><?php endif; ?><?php else: ?><div class="character-empty">Achievements are not available for this realm ruleset or database layout yet.</div><?php endif; ?></section><?php endif; ?>
<?php endif; ?>
</div>
<?php builddiv_end(); ?>














