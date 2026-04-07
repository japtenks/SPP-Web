<?php

if (!function_exists('spp_character_load_advancement_state')) {
    function spp_character_load_advancement_state(array $args): array
    {
        $characterGuid = (int)($args['character_guid'] ?? 0);
        $character = (array)($args['character'] ?? []);
        $charsPdo = $args['chars_pdo'] ?? null;
        $worldPdo = $args['world_pdo'] ?? null;
        $armoryPdo = $args['armory_pdo'] ?? null;

        $talentTabs = (array)($args['talent_tabs'] ?? []);
        $reputations = (array)($args['reputations'] ?? []);
        $reputationSections = (array)($args['reputation_sections'] ?? []);
        $skillsByCategory = (array)($args['skills_by_category'] ?? []);
        $professionsByCategory = (array)($args['professions_by_category'] ?? []);
        $professionRecipesBySkillId = (array)($args['profession_recipes_by_skill_id'] ?? []);
        $knownCharacterSpells = (array)($args['known_character_spells'] ?? []);

        if (!$charsPdo instanceof PDO || !$worldPdo instanceof PDO || !$armoryPdo instanceof PDO || $characterGuid <= 0 || empty($character)) {
            return array(
                'talent_tabs' => $talentTabs,
                'reputations' => $reputations,
                'reputation_sections' => $reputationSections,
                'skills_by_category' => $skillsByCategory,
                'professions_by_category' => $professionsByCategory,
                'profession_recipes_by_skill_id' => $professionRecipesBySkillId,
                'known_character_spells' => $knownCharacterSpells,
            );
        }

        $talentMetaPdo = spp_character_pick_dbc_pdo(array($worldPdo, $armoryPdo), 'dbc_talenttab') ?: $armoryPdo;
        $talentDataPdo = spp_character_pick_dbc_pdo(array($worldPdo, $armoryPdo), 'dbc_talent') ?: $talentMetaPdo;
        $spellMetaPdo = spp_character_pick_dbc_pdo(array($worldPdo, $armoryPdo), 'dbc_spell') ?: $talentDataPdo;
        $spellIconPdo = spp_character_pick_dbc_pdo(array($talentMetaPdo, $worldPdo, $armoryPdo), 'dbc_spellicon') ?: $talentMetaPdo;
        $spellIconFields = spp_character_spellicon_fields($spellIconPdo);
        $talentTabSpellIconField = spp_character_talenttab_icon_field($talentMetaPdo);
        $talentTabSql = 'SELECT tt.`id`, tt.`name`, tt.`tab_number`, NULL AS `icon_name` FROM `dbc_talenttab` tt WHERE (tt.`refmask_chrclasses` & ?) <> 0 ORDER BY tt.`tab_number` ASC';
        if ($spellIconFields['id'] && $spellIconFields['name'] && $talentTabSpellIconField !== null) {
            $talentTabSql = 'SELECT tt.`id`, tt.`name`, tt.`tab_number`, si.`' . $spellIconFields['name'] . '` AS `icon_name` ' .
                'FROM `dbc_talenttab` tt LEFT JOIN `dbc_spellicon` si ON si.`' . $spellIconFields['id'] . '` = tt.`' . $talentTabSpellIconField . '` ' .
                'WHERE (tt.`refmask_chrclasses` & ?) <> 0 ORDER BY tt.`tab_number` ASC';
        }
        try {
            $stmt = $talentMetaPdo->prepare($talentTabSql);
            $stmt->execute(array(1 << ((int)$character['class'] - 1)));
        } catch (Throwable $e) {
            error_log('[character.talents] Falling back to talent-tab load without icon join: ' . $e->getMessage());
            $stmt = $talentMetaPdo->prepare('SELECT tt.`id`, tt.`name`, tt.`tab_number`, NULL AS `icon_name` FROM `dbc_talenttab` tt WHERE (tt.`refmask_chrclasses` & ?) <> 0 ORDER BY tt.`tab_number` ASC');
            $stmt->execute(array(1 << ((int)$character['class'] - 1)));
        }
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $tabRow) {
            $tabId = (int)$tabRow['id'];
            $talentTabs[$tabId] = array(
                'name' => (string)$tabRow['name'],
                'points' => 0,
                'icon' => spp_character_icon_url($tabRow['icon_name'] ?? ''),
            );
        }
        if (!empty($talentTabs)) {
            foreach ($talentTabs as $tabId => $tabMeta) {
                $talentTabs[$tabId]['points'] = spp_character_talent_points($charsPdo, $talentDataPdo, $characterGuid, $tabId);
                $currentIcon = (string)($talentTabs[$tabId]['icon'] ?? '');
                if ($currentIcon === '' || substr($currentIcon, -7) === '404.png') {
                    $fallbackIconName = spp_character_talent_tab_icon_name($tabId);
                    if ($fallbackIconName !== '') {
                        $talentTabs[$tabId]['icon'] = spp_character_icon_url($fallbackIconName);
                    }
                }
            }
        }
        if (!empty($talentTabs) && $spellMetaPdo instanceof PDO && $spellIconPdo instanceof PDO && spp_character_table_exists($talentDataPdo, 'dbc_talent') && spp_character_table_exists($spellMetaPdo, 'dbc_spell') && spp_character_table_exists($spellIconPdo, 'dbc_spellicon')) {
            $tabIds = array_keys($talentTabs);
            $placeholders = implode(',', array_fill(0, count($tabIds), '?'));
            $spellIconMap = array();
            $talentStmt = $talentDataPdo->prepare('SELECT `ref_talenttab`, `rank1`, `rank2`, `rank3`, `rank4`, `rank5` FROM `dbc_talent` WHERE `ref_talenttab` IN (' . $placeholders . ') ORDER BY `ref_talenttab`, `row`, `col`');
            $talentStmt->execute($tabIds);
            $firstSpellByTab = array();
            foreach ($talentStmt->fetchAll(PDO::FETCH_ASSOC) as $talentRow) {
                $tabId = (int)($talentRow['ref_talenttab'] ?? 0);
                if ($tabId <= 0 || isset($firstSpellByTab[$tabId])) {
                    continue;
                }
                for ($rankIndex = 1; $rankIndex <= 5; $rankIndex++) {
                    $spellId = (int)($talentRow['rank' . $rankIndex] ?? 0);
                    if ($spellId > 0) {
                        $firstSpellByTab[$tabId] = $spellId;
                        break;
                    }
                }
            }
            if (!empty($firstSpellByTab)) {
                $spellIds = array_values(array_unique(array_values($firstSpellByTab)));
                $spellPlaceholders = implode(',', array_fill(0, count($spellIds), '?'));
                $spellIconField = spp_character_spell_icon_field($spellMetaPdo, 'dbc_spell');
                if ($spellIconField !== null && $spellIconFields['id'] && $spellIconFields['name']) {
                    try {
                        $spellStmt = $spellMetaPdo->prepare('SELECT `' . $spellIconField . '` AS `icon_id`, `id` FROM `dbc_spell` WHERE `id` IN (' . $spellPlaceholders . ')');
                        $spellStmt->execute($spellIds);
                    } catch (Throwable $e) {
                        error_log('[character.talents] Spell icon lookup skipped due to schema mismatch: ' . $e->getMessage());
                        $spellStmt = null;
                    }
                    $iconIds = array();
                    $spellToIconId = array();
                    foreach (($spellStmt ? $spellStmt->fetchAll(PDO::FETCH_ASSOC) : array()) as $spellRow) {
                        $spellId = (int)($spellRow['id'] ?? 0);
                        $iconId = (int)($spellRow['icon_id'] ?? 0);
                        if ($spellId > 0 && $iconId > 0) {
                            $spellToIconId[$spellId] = $iconId;
                            $iconIds[$iconId] = true;
                        }
                    }
                    if (!empty($iconIds)) {
                        $iconPlaceholders = implode(',', array_fill(0, count($iconIds), '?'));
                        $iconStmt = $spellIconPdo->prepare('SELECT `' . $spellIconFields['id'] . '` AS `id`, `' . $spellIconFields['name'] . '` AS `name` FROM `dbc_spellicon` WHERE `' . $spellIconFields['id'] . '` IN (' . $iconPlaceholders . ')');
                        $iconStmt->execute(array_keys($iconIds));
                        foreach ($iconStmt->fetchAll(PDO::FETCH_ASSOC) as $iconRow) {
                            $spellIconMap[(int)($iconRow['id'] ?? 0)] = (string)($iconRow['name'] ?? '');
                        }
                    }
                    foreach ($firstSpellByTab as $tabId => $spellId) {
                        $currentIcon = (string)($talentTabs[$tabId]['icon'] ?? '');
                        if ($currentIcon !== '' && substr($currentIcon, -7) !== '404.png') {
                            continue;
                        }
                        $iconName = $spellIconMap[$spellToIconId[$spellId] ?? 0] ?? '';
                        if ($iconName !== '') {
                            $talentTabs[$tabId]['icon'] = spp_character_icon_url($iconName);
                        }
                    }
                }
            }
        }

        if (spp_character_table_exists($charsPdo, 'character_reputation')) {
            $reputationSectionIds = array(
                1118 => 'Classic',
                469 => 'Alliance',
                891 => 'Alliance Forces',
                1037 => 'Classic',
                67 => 'Horde',
                892 => 'Horde Forces',
                1052 => 'Classic',
                936 => 'Shattrath City',
                1117 => 'Classic',
                169 => 'Steamwheedle Cartel',
                980 => 'Outland',
                1097 => 'Classic',
                0 => 'Other',
            );
            $sectionFactionIds = array_keys($reputationSectionIds);
            $stmt = $charsPdo->prepare('SELECT `faction`, `standing`, `flags` FROM `character_reputation` WHERE `guid` = ? AND (`flags` & 1 = 1)' . (!empty($sectionFactionIds) ? ' AND `faction` NOT IN (' . implode(', ', array_map('intval', $sectionFactionIds)) . ')' : ''));
            $stmt->execute(array($characterGuid));
            $repRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $factionIds = array();
            foreach ($repRows as $row) {
                $factionIds[(int)$row['faction']] = true;
            }
            $factionMap = array();
            $sectionNameMap = $reputationSectionIds;
            if (!empty($factionIds)) {
                $placeholders = implode(',', array_fill(0, count($factionIds), '?'));
                $factionColumns = spp_character_columns($armoryPdo, 'dbc_faction');
                $selectParts = array('`id`', '`name`', '`description`');
                if (isset($factionColumns['ref_faction'])) {
                    $selectParts[] = '`ref_faction`';
                }
                for ($idx = 0; $idx <= 4; $idx++) {
                    $raceField = 'base_ref_chrraces_' . $idx;
                    $modifierField = 'base_modifier_' . $idx;
                    if (isset($factionColumns[$raceField])) {
                        $selectParts[] = '`' . $raceField . '`';
                    }
                    if (isset($factionColumns[$modifierField])) {
                        $selectParts[] = '`' . $modifierField . '`';
                    }
                }
                $stmt = $armoryPdo->prepare('SELECT ' . implode(', ', $selectParts) . ' FROM `dbc_faction` WHERE `id` IN (' . $placeholders . ')');
                $stmt->execute(array_keys($factionIds));
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $factionMap[(int)$row['id']] = $row;
                }
                $sectionLookupIds = array();
                foreach ($factionMap as $row) {
                    $sectionId = (int)($row['ref_faction'] ?? 0);
                    if ($sectionId > 0) {
                        $sectionLookupIds[$sectionId] = true;
                    }
                }
                $missingSectionIds = array();
                foreach (array_keys($sectionLookupIds) as $sectionId) {
                    if (!isset($sectionNameMap[$sectionId])) {
                        $missingSectionIds[] = $sectionId;
                    }
                }
                if (!empty($missingSectionIds)) {
                    $sectionPlaceholders = implode(',', array_fill(0, count($missingSectionIds), '?'));
                    $stmt = $armoryPdo->prepare('SELECT `id`, `name` FROM `dbc_faction` WHERE `id` IN (' . $sectionPlaceholders . ')');
                    $stmt->execute($missingSectionIds);
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $sectionRow) {
                        $sectionNameMap[(int)$sectionRow['id']] = trim((string)$sectionRow['name']) !== '' ? (string)$sectionRow['name'] : ('Group ' . (int)$sectionRow['id']);
                    }
                }
            }
            foreach ($repRows as $row) {
                $faction = $factionMap[(int)$row['faction']] ?? null;
                if (!$faction) {
                    continue;
                }
                $standing = (int)$row['standing'];
                for ($idx = 0; $idx <= 4; $idx++) {
                    $raceField = 'base_ref_chrraces_' . $idx;
                    $modifierField = 'base_modifier_' . $idx;
                    if (!isset($faction[$raceField], $faction[$modifierField])) {
                        continue;
                    }
                    if (((int)$faction[$raceField]) & (1 << ((int)$character['race'] - 1))) {
                        $standing += (int)$faction[$modifierField];
                        break;
                    }
                }
                $rank = spp_character_rep_rank($standing);
                $sectionId = (int)($faction['ref_faction'] ?? 0);
                $sectionLabel = $sectionNameMap[$sectionId] ?? ($sectionId > 0 ? ('Group ' . $sectionId) : 'Other');
                $entry = array(
                    'name' => (string)$faction['name'],
                    'icon' => spp_character_reputation_icon_url((string)$faction['name'], spp_character_reputation_tier($rank['label'])),
                    'description' => trim((string)($faction['description'] ?? '')),
                    'label' => $rank['label'],
                    'tier' => spp_character_reputation_tier($rank['label']),
                    'value' => $rank['value'],
                    'max' => $rank['max'],
                    'standing' => $standing,
                    'percent' => $rank['max'] > 0 ? min(100, max(0, round(($rank['value'] / $rank['max']) * 100))) : 0,
                    'section_id' => $sectionId,
                    'section' => $sectionLabel,
                );
                $reputations[] = $entry;
                if (!isset($reputationSections[$sectionLabel])) {
                    $reputationSections[$sectionLabel] = array();
                }
                $reputationSections[$sectionLabel][] = $entry;
            }
        }

        if (spp_character_table_exists($charsPdo, 'character_skills')) {
            $stmt = $charsPdo->prepare('SELECT `skill`, `value`, `max` FROM `character_skills` WHERE `guid` = ?');
            $stmt->execute(array($characterGuid));
            $skillRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $skillIds = array();
            foreach ($skillRows as $row) {
                $skillIds[(int)$row['skill']] = true;
            }
            $skillMap = array();
            if (!empty($skillIds)) {
                $placeholders = implode(',', array_fill(0, count($skillIds), '?'));
                $spellIconFields = spp_character_spellicon_fields($armoryPdo);
                $skillSql = 'SELECT sl.`id`, sl.`name`, sl.`description`, sc.`name` AS `category_name`, NULL AS `icon_name` ' .
                    'FROM `dbc_skillline` sl LEFT JOIN `dbc_skilllinecategory` sc ON sc.`id` = sl.`ref_skilllinecategory` ';
                if ($spellIconFields['id'] && $spellIconFields['name']) {
                    $skillSql = 'SELECT sl.`id`, sl.`name`, sl.`description`, sc.`name` AS `category_name`, si.`' . $spellIconFields['name'] . '` AS `icon_name` ' .
                        'FROM `dbc_skillline` sl LEFT JOIN `dbc_skilllinecategory` sc ON sc.`id` = sl.`ref_skilllinecategory` ' .
                        'LEFT JOIN `dbc_spellicon` si ON si.`' . $spellIconFields['id'] . '` = sl.`ref_spellicon` ';
                }
                $skillSql .= 'WHERE sl.`id` IN (' . $placeholders . ')';
                $stmt = $armoryPdo->prepare($skillSql);
                $stmt->execute(array_keys($skillIds));
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $skillMap[(int)$row['id']] = $row;
                }
            }
            foreach ($skillRows as $row) {
                $skillId = (int)$row['skill'];
                if (!isset($skillMap[$skillId])) {
                    continue;
                }
                $meta = $skillMap[$skillId];
                $category = trim((string)($meta['category_name'] ?? 'Other'));
                if ($category === '') {
                    $category = 'Other';
                }
                $categoryKey = strtolower($category);
                if (strpos($categoryKey, 'class skill') !== false) {
                    continue;
                }
                $target =& $skillsByCategory;
                if (strpos($categoryKey, 'profession') !== false || strpos($categoryKey, 'secondary') !== false) {
                    $target =& $professionsByCategory;
                }
                $entry = spp_character_skill_entry($meta, $row, array(
                    'race' => (int)$character['race'],
                    'gender' => (int)$character['gender'],
                ));
                if ($entry === null) {
                    unset($target);
                    continue;
                }
                if (!isset($target[$category])) {
                    $target[$category] = array();
                }
                $target[$category][] = $entry;
                unset($target);
            }
        }

        if (spp_character_table_exists($charsPdo, 'character_spell') && !empty($professionsByCategory)) {
            $professionSkillIds = array();
            $professionSkillRanks = array();
            foreach ($professionsByCategory as $categorySkills) {
                foreach ($categorySkills as $skill) {
                    $skillId = (int)($skill['skill_id'] ?? 0);
                    if ($skillId > 0) {
                        $professionSkillIds[$skillId] = true;
                        $professionSkillRanks[$skillId] = max($professionSkillRanks[$skillId] ?? 0, (int)($skill['value'] ?? 0));
                    }
                }
            }

            if (!empty($professionSkillIds)) {
                $stmt = $charsPdo->prepare('SELECT `spell` FROM `character_spell` WHERE `guid` = ? AND `disabled` = 0');
                $stmt->execute(array($characterGuid));
                $learnedSpellIds = array();
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $spellId = (int)($row['spell'] ?? 0);
                    if ($spellId > 0) {
                        $learnedSpellIds[$spellId] = true;
                    }
                }
                $knownCharacterSpells = $learnedSpellIds;

                if (!empty($learnedSpellIds)) {
                    $spellPlaceholders = implode(',', array_fill(0, count($learnedSpellIds), '?'));
                    $skillPlaceholders = implode(',', array_fill(0, count($professionSkillIds), '?'));
                    $recipeStmt = $worldPdo->prepare(
                        'SELECT `entry`, `name`, `Quality`, `RequiredSkill`, `RequiredSkillRank`, `RequiredReputationFaction`, `RequiredReputationRank`, `displayid`, `spellid_1` ' .
                        'FROM `item_template` ' .
                        'WHERE `class` = 9 AND `RequiredSkill` IN (' . $skillPlaceholders . ') ' .
                        'AND `spellid_1` IN (' . $spellPlaceholders . ')'
                    );
                    $recipeStmt->execute(array_merge(array_keys($professionSkillIds), array_keys($learnedSpellIds)));
                    $recipeRows = $recipeStmt->fetchAll(PDO::FETCH_ASSOC);

                    if (!empty($recipeRows)) {
                        $recipeIds = array();
                        $displayIds = array();
                        $repFactionIds = array();
                        foreach ($recipeRows as $recipeRow) {
                            $entryId = (int)$recipeRow['entry'];
                            $recipeIds[$entryId] = true;
                            if (!empty($recipeRow['displayid'])) {
                                $displayIds[(int)$recipeRow['displayid']] = true;
                            }
                            if (!empty($recipeRow['RequiredReputationFaction'])) {
                                $repFactionIds[(int)$recipeRow['RequiredReputationFaction']] = true;
                            }
                        }

                        $recipeIconMap = array();
                        if (!empty($displayIds)) {
                            $displayPlaceholders = implode(',', array_fill(0, count($displayIds), '?'));
                            $iconStmt = $armoryPdo->prepare('SELECT `id`, `name` FROM `dbc_itemdisplayinfo` WHERE `id` IN (' . $displayPlaceholders . ')');
                            $iconStmt->execute(array_keys($displayIds));
                            foreach ($iconStmt->fetchAll(PDO::FETCH_ASSOC) as $iconRow) {
                                $recipeIconMap[(int)$iconRow['id']] = (string)$iconRow['name'];
                            }
                        }

                        $repFactionNames = array();
                        if (!empty($repFactionIds)) {
                            $repPlaceholders = implode(',', array_fill(0, count($repFactionIds), '?'));
                            $repStmt = $armoryPdo->prepare('SELECT `id`, `name` FROM `dbc_faction` WHERE `id` IN (' . $repPlaceholders . ')');
                            $repStmt->execute(array_keys($repFactionIds));
                            foreach ($repStmt->fetchAll(PDO::FETCH_ASSOC) as $repRow) {
                                $repFactionNames[(int)$repRow['id']] = trim((string)$repRow['name']);
                            }
                        }

                        $recipeIdList = array_keys($recipeIds);
                        $recipePlaceholders = implode(',', array_fill(0, count($recipeIdList), '?'));

                        $vendorRecipeIds = array();
                        $vendorStmt = $worldPdo->prepare(
                            'SELECT DISTINCT `item` FROM `npc_vendor` WHERE `item` IN (' . $recipePlaceholders . ') ' .
                            'UNION SELECT DISTINCT `item` FROM `npc_vendor_template` WHERE `item` IN (' . $recipePlaceholders . ')'
                        );
                        $vendorStmt->execute(array_merge($recipeIdList, $recipeIdList));
                        foreach ($vendorStmt->fetchAll(PDO::FETCH_COLUMN) as $vendorId) {
                            $vendorRecipeIds[(int)$vendorId] = true;
                        }

                        $lootRecipeIds = array();
                        $lootTables = array('creature_loot_template', 'reference_loot_template', 'gameobject_loot_template', 'fishing_loot_template', 'disenchant_loot_template');
                        foreach ($lootTables as $lootTable) {
                            if (!spp_character_table_exists($worldPdo, $lootTable)) {
                                continue;
                            }
                            $lootStmt = $worldPdo->prepare('SELECT DISTINCT `item` FROM `' . $lootTable . '` WHERE `item` IN (' . $recipePlaceholders . ')');
                            $lootStmt->execute($recipeIdList);
                            foreach ($lootStmt->fetchAll(PDO::FETCH_COLUMN) as $lootId) {
                                $lootRecipeIds[(int)$lootId] = true;
                            }
                        }

                        $questRecipeIds = array();
                        if (spp_character_table_exists($worldPdo, 'quest_template')) {
                            $questRewardFields = array(
                                'RewChoiceItemId1', 'RewChoiceItemId2', 'RewChoiceItemId3', 'RewChoiceItemId4', 'RewChoiceItemId5', 'RewChoiceItemId6',
                                'RewItemId1', 'RewItemId2', 'RewItemId3', 'RewItemId4',
                            );
                            $questConditions = array();
                            $questParams = array();
                            foreach ($questRewardFields as $field) {
                                $questConditions[] = '`' . $field . '` IN (' . $recipePlaceholders . ')';
                                $questParams = array_merge($questParams, $recipeIdList);
                            }
                            $questStmt = $worldPdo->prepare('SELECT ' . implode(', ', array_map(function ($field) {
                                return '`' . $field . '`';
                            }, $questRewardFields)) . ' FROM `quest_template` WHERE ' . implode(' OR ', $questConditions));
                            $questStmt->execute($questParams);
                            foreach ($questStmt->fetchAll(PDO::FETCH_ASSOC) as $questRow) {
                                foreach ($questRewardFields as $field) {
                                    $itemId = (int)($questRow[$field] ?? 0);
                                    if ($itemId > 0) {
                                        $questRecipeIds[$itemId] = true;
                                    }
                                }
                            }
                        }

                        foreach ($recipeRows as $recipeRow) {
                            $skillId = (int)$recipeRow['RequiredSkill'];
                            if ($skillId <= 0) {
                                continue;
                            }
                            $entryId = (int)$recipeRow['entry'];
                            $quality = (int)$recipeRow['Quality'];
                            $requiredRank = (int)$recipeRow['RequiredSkillRank'];
                            $repFactionId = (int)$recipeRow['RequiredReputationFaction'];
                            $repRank = (int)$recipeRow['RequiredReputationRank'];
                            $isFactionRecipe = $repFactionId > 0 || $repRank > 0;
                            $isLootRecipe = isset($lootRecipeIds[$entryId]);
                            $isVendorRecipe = isset($vendorRecipeIds[$entryId]);
                            $isQuestRecipe = isset($questRecipeIds[$entryId]);
                            $isRareDrop = $isLootRecipe && !$isVendorRecipe && !$isQuestRecipe;
                            $displayName = spp_character_recipe_display_name((string)$recipeRow['name']);
                            $tags = array('all');
                            if ($isFactionRecipe) {
                                $tags[] = 'faction';
                            }
                            if ($isRareDrop) {
                                $tags[] = 'rare-drop';
                            }
                            if ($requiredRank >= 300) {
                                $tags[] = 'endgame';
                            }
                            if (stripos($displayName, 'flask') !== false || stripos((string)$recipeRow['name'], 'flask') !== false) {
                                $tags[] = 'flask';
                            }

                            $sourceParts = array();
                            if ($isFactionRecipe) {
                                $repLabel = $repFactionNames[$repFactionId] ?? ('Faction #' . $repFactionId);
                                $sourceParts[] = 'Rep: ' . $repLabel;
                            }
                            if ($isRareDrop) {
                                $sourceParts[] = 'Rare Drop';
                            } elseif ($isVendorRecipe) {
                                $sourceParts[] = 'Vendor';
                            } elseif ($isQuestRecipe) {
                                $sourceParts[] = 'Quest';
                            }
                            if ($requiredRank > 0) {
                                $sourceParts[] = 'Req ' . $requiredRank;
                            }

                            if (!isset($professionRecipesBySkillId[$skillId])) {
                                $professionRecipesBySkillId[$skillId] = array();
                            }
                            $professionRecipesBySkillId[$skillId][] = array(
                                'entry' => $entryId,
                                'name' => $displayName,
                                'full_name' => (string)$recipeRow['name'],
                                'quality' => $quality,
                                'icon' => spp_character_icon_url($recipeIconMap[(int)$recipeRow['displayid']] ?? ''),
                                'required_rank' => $requiredRank,
                                'source' => implode(' - ', $sourceParts),
                                'tags' => $tags,
                                'tag_map' => array_fill_keys($tags, true),
                            );
                        }

                        foreach ($professionRecipesBySkillId as &$recipeList) {
                            usort($recipeList, function ($left, $right) {
                                $leftWeight = (isset($left['tag_map']['faction']) ? 5 : 0) + (isset($left['tag_map']['rare-drop']) ? 4 : 0) + (isset($left['tag_map']['flask']) ? 3 : 0) + (isset($left['tag_map']['endgame']) ? 2 : 0);
                                $rightWeight = (isset($right['tag_map']['faction']) ? 5 : 0) + (isset($right['tag_map']['rare-drop']) ? 4 : 0) + (isset($right['tag_map']['flask']) ? 3 : 0) + (isset($right['tag_map']['endgame']) ? 2 : 0);
                                if ($leftWeight !== $rightWeight) {
                                    return $rightWeight <=> $leftWeight;
                                }
                                if ((int)$left['required_rank'] !== (int)$right['required_rank']) {
                                    return (int)$right['required_rank'] <=> (int)$left['required_rank'];
                                }
                                return strcasecmp((string)$left['name'], (string)$right['name']);
                            });
                        }
                        unset($recipeList);

                        foreach ($professionsByCategory as &$categorySkills) {
                            foreach ($categorySkills as &$skill) {
                                $skillId = (int)($skill['skill_id'] ?? 0);
                                $skill['recipes'] = $professionRecipesBySkillId[$skillId] ?? array();
                                $skill['specializations'] = spp_character_profession_specializations((string)($skill['name'] ?? ''), $knownCharacterSpells);
                                $skill['recipe_filters'] = array();
                                if (!empty($skill['recipes'])) {
                                    $filterCounts = array('all' => count($skill['recipes']), 'faction' => 0, 'rare-drop' => 0, 'endgame' => 0, 'flask' => 0);
                                    foreach ($skill['recipes'] as $recipe) {
                                        foreach (array('faction', 'rare-drop', 'endgame', 'flask') as $filterKey) {
                                            if (isset($recipe['tag_map'][$filterKey])) {
                                                $filterCounts[$filterKey]++;
                                            }
                                        }
                                    }
                                    foreach (spp_character_recipe_filter_labels() as $filterKey => $filterLabel) {
                                        if ($filterKey === 'all' || !empty($filterCounts[$filterKey])) {
                                            $skill['recipe_filters'][] = array(
                                                'key' => $filterKey,
                                                'label' => $filterLabel,
                                                'count' => $filterCounts[$filterKey] ?? 0,
                                            );
                                        }
                                    }
                                }
                            }
                            unset($skill);
                        }
                        unset($categorySkills);
                    }
                }
            }
        }

        if (spp_character_table_exists($charsPdo, 'character_spell') && !empty($professionsByCategory)) {
            $professionSkillIds = array();
            foreach ($professionsByCategory as $categorySkills) {
                foreach ($categorySkills as $skill) {
                    $skillId = (int)($skill['skill_id'] ?? 0);
                    if ($skillId > 0) {
                        $professionSkillIds[$skillId] = true;
                    }
                }
            }

            if (!empty($professionSkillIds)) {
                $stmt = $charsPdo->prepare('SELECT `spell` FROM `character_spell` WHERE `guid` = ? AND `disabled` = 0');
                $stmt->execute(array($characterGuid));
                $learnedSpellIds = array();
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $spellId = (int)($row['spell'] ?? 0);
                    if ($spellId > 0) {
                        $learnedSpellIds[$spellId] = true;
                    }
                }
                $knownCharacterSpells = $learnedSpellIds;

                $trainerSpellIdsBySkill = array();
                if (!empty($professionSkillIds) && spp_character_table_exists($worldPdo, 'npc_trainer') && spp_character_table_exists($worldPdo, 'npc_trainer_template')) {
                    $skillPlaceholders = implode(',', array_fill(0, count($professionSkillIds), '?'));
                    $trainerStmt = $worldPdo->prepare(
                        'SELECT `spell`, `reqskill`, `reqskillvalue` FROM `npc_trainer` WHERE `reqskill` IN (' . $skillPlaceholders . ') ' .
                        'UNION SELECT `spell`, `reqskill`, `reqskillvalue` FROM `npc_trainer_template` WHERE `reqskill` IN (' . $skillPlaceholders . ')'
                    );
                    $trainerStmt->execute(array_merge(array_keys($professionSkillIds), array_keys($professionSkillIds)));
                    foreach ($trainerStmt->fetchAll(PDO::FETCH_ASSOC) as $trainerRow) {
                        $skillId = (int)($trainerRow['reqskill'] ?? 0);
                        $spellId = (int)($trainerRow['spell'] ?? 0);
                        if ($skillId <= 0 || $spellId <= 0) {
                            continue;
                        }
                        if (!isset($trainerSpellIdsBySkill[$skillId])) {
                            $trainerSpellIdsBySkill[$skillId] = array();
                        }
                        $existingRequired = $trainerSpellIdsBySkill[$skillId][$spellId] ?? null;
                        $requiredValue = (int)($trainerRow['reqskillvalue'] ?? 0);
                        if ($existingRequired === null || $requiredValue < $existingRequired) {
                            $trainerSpellIdsBySkill[$skillId][$spellId] = $requiredValue;
                        }
                    }
                }

                $professionSpellbookBySkillId = array();
                $eligibleTrainerSpellIds = array();
                foreach ($trainerSpellIdsBySkill as $skillId => $trainerSpells) {
                    $currentRank = (int)($professionSkillRanks[$skillId] ?? 0);
                    foreach ($trainerSpells as $spellId => $requiredValue) {
                        if ((int)$requiredValue <= $currentRank) {
                            $eligibleTrainerSpellIds[(int)$spellId] = true;
                        }
                    }
                }

                $candidateSpellIds = $learnedSpellIds + $eligibleTrainerSpellIds;
                if (!empty($candidateSpellIds)) {
                    $spellPlaceholders = implode(',', array_fill(0, count($candidateSpellIds), '?'));
                    $spellStmt = $worldPdo->prepare(
                        'SELECT `Id`, `SpellName`, `SpellIconID`, `Effect1`, `Effect2`, `Effect3`, `EffectTriggerSpell1`, `EffectTriggerSpell2`, `EffectTriggerSpell3`, `EffectItemType1`, `EffectItemType2`, `EffectItemType3` ' .
                        'FROM `spell_template` WHERE `Id` IN (' . $spellPlaceholders . ')'
                    );
                    $spellStmt->execute(array_keys($candidateSpellIds));
                    $spellRows = $spellStmt->fetchAll(PDO::FETCH_ASSOC);

                    $spellMetaMap = array();
                    $spellOutputMap = array();
                    $spellIconIds = array();
                    $craftedItemIds = array();
                    foreach ($spellRows as $spellRow) {
                        $spellId = (int)($spellRow['Id'] ?? 0);
                        if ($spellId <= 0) {
                            continue;
                        }
                        $spellMetaMap[$spellId] = $spellRow;
                        $spellIconId = (int)($spellRow['SpellIconID'] ?? 0);
                        if ($spellIconId > 0) {
                            $spellIconIds[$spellIconId] = true;
                        }
                        foreach (array('EffectItemType1', 'EffectItemType2', 'EffectItemType3') as $field) {
                            $itemId = (int)($spellRow[$field] ?? 0);
                            if ($itemId <= 0) {
                                continue;
                            }
                            if (!isset($spellOutputMap[$spellId])) {
                                $spellOutputMap[$spellId] = array();
                            }
                            $spellOutputMap[$spellId][$itemId] = true;
                            $craftedItemIds[$itemId] = true;
                        }
                    }

                    $triggerSpellIds = array();
                    foreach ($spellMetaMap as $spellId => $spellRow) {
                        foreach (array(1, 2, 3) as $index) {
                            $triggerSpellId = (int)($spellRow['EffectTriggerSpell' . $index] ?? 0);
                            if ($triggerSpellId > 0) {
                                $triggerSpellIds[$triggerSpellId] = true;
                            }
                        }
                    }
                    if (!empty($triggerSpellIds)) {
                        $triggerPlaceholders = implode(',', array_fill(0, count($triggerSpellIds), '?'));
                        $triggerStmt = $worldPdo->prepare(
                            'SELECT `Id`, `SpellName`, `SpellIconID`, `EffectItemType1`, `EffectItemType2`, `EffectItemType3` ' .
                            'FROM `spell_template` WHERE `Id` IN (' . $triggerPlaceholders . ')'
                        );
                        $triggerStmt->execute(array_keys($triggerSpellIds));
                        $triggerRowsById = array();
                        foreach ($triggerStmt->fetchAll(PDO::FETCH_ASSOC) as $triggerRow) {
                            $triggerRowsById[(int)($triggerRow['Id'] ?? 0)] = $triggerRow;
                        }

                        foreach ($spellMetaMap as $spellId => $spellRow) {
                            if (!empty($spellOutputMap[$spellId])) {
                                continue;
                            }
                            foreach (array(1, 2, 3) as $index) {
                                $triggerSpellId = (int)($spellRow['EffectTriggerSpell' . $index] ?? 0);
                                if ($triggerSpellId <= 0 || empty($triggerRowsById[$triggerSpellId])) {
                                    continue;
                                }
                                $triggerRow = $triggerRowsById[$triggerSpellId];
                                if ((int)($spellMetaMap[$spellId]['SpellIconID'] ?? 0) <= 1 && (int)($triggerRow['SpellIconID'] ?? 0) > 0) {
                                    $spellMetaMap[$spellId]['SpellIconID'] = (int)$triggerRow['SpellIconID'];
                                    $spellIconIds[(int)$triggerRow['SpellIconID']] = true;
                                }
                                foreach (array('EffectItemType1', 'EffectItemType2', 'EffectItemType3') as $field) {
                                    $itemId = (int)($triggerRow[$field] ?? 0);
                                    if ($itemId <= 0) {
                                        continue;
                                    }
                                    if (!isset($spellOutputMap[$spellId])) {
                                        $spellOutputMap[$spellId] = array();
                                    }
                                    $spellOutputMap[$spellId][$itemId] = true;
                                    $craftedItemIds[$itemId] = true;
                                }
                            }
                        }
                    }

                    $spellIconMap = array();
                    $spellIconPdo = spp_character_pick_dbc_pdo(array($armoryPdo, $worldPdo), 'dbc_spellicon') ?: $armoryPdo;
                    if (!empty($spellIconIds) && $spellIconPdo && spp_character_table_exists($spellIconPdo, 'dbc_spellicon')) {
                        $spellIconFields = spp_character_spellicon_fields($spellIconPdo);
                        if ($spellIconFields['id'] && $spellIconFields['name']) {
                            $iconPlaceholders = implode(',', array_fill(0, count($spellIconIds), '?'));
                            $iconStmt = $spellIconPdo->prepare(
                                'SELECT `' . $spellIconFields['id'] . '` AS `id`, `' . $spellIconFields['name'] . '` AS `name` ' .
                                'FROM `dbc_spellicon` WHERE `' . $spellIconFields['id'] . '` IN (' . $iconPlaceholders . ')'
                            );
                            $iconStmt->execute(array_keys($spellIconIds));
                            foreach ($iconStmt->fetchAll(PDO::FETCH_ASSOC) as $iconRow) {
                                $spellIconMap[(int)$iconRow['id']] = (string)$iconRow['name'];
                            }
                        }
                    }

                    $craftedItemMap = array();
                    $craftedItemIconMap = array();
                    if (!empty($craftedItemIds) && spp_character_table_exists($worldPdo, 'item_template')) {
                        $itemPlaceholders = implode(',', array_fill(0, count($craftedItemIds), '?'));
                        $itemStmt = $worldPdo->prepare(
                            'SELECT `entry`, `name`, `Quality`, `RequiredSkill`, `RequiredSkillRank`, `displayid` FROM `item_template` WHERE `entry` IN (' . $itemPlaceholders . ')'
                        );
                        $itemStmt->execute(array_keys($craftedItemIds));
                        $displayIds = array();
                        foreach ($itemStmt->fetchAll(PDO::FETCH_ASSOC) as $itemRow) {
                            $craftedItemMap[(int)$itemRow['entry']] = $itemRow;
                            $displayId = (int)($itemRow['displayid'] ?? 0);
                            if ($displayId > 0) {
                                $displayIds[$displayId] = true;
                            }
                        }
                        if (!empty($displayIds) && spp_character_table_exists($armoryPdo, 'dbc_itemdisplayinfo')) {
                            $displayPlaceholders = implode(',', array_fill(0, count($displayIds), '?'));
                            $displayStmt = $armoryPdo->prepare('SELECT `id`, `name` FROM `dbc_itemdisplayinfo` WHERE `id` IN (' . $displayPlaceholders . ')');
                            $displayStmt->execute(array_keys($displayIds));
                            foreach ($displayStmt->fetchAll(PDO::FETCH_ASSOC) as $displayRow) {
                                $craftedItemIconMap[(int)$displayRow['id']] = (string)$displayRow['name'];
                            }
                        }
                    }

                    $trainerRecipeRequiredRanksBySkill = array();
                    foreach ($trainerSpellIdsBySkill as $skillId => $trainerSpells) {
                        foreach ($trainerSpells as $trainerSpellId => $requiredValue) {
                            if (!isset($spellMetaMap[$trainerSpellId])) {
                                continue;
                            }
                            $trainerSpellName = trim((string)($spellMetaMap[$trainerSpellId]['SpellName'] ?? ''));
                            $trainerItemNames = array();
                            foreach (array_keys($spellOutputMap[$trainerSpellId] ?? array()) as $trainerItemId) {
                                $trainerItemName = trim((string)($craftedItemMap[$trainerItemId]['name'] ?? ''));
                                if ($trainerItemName !== '') {
                                    $trainerItemNames[] = $trainerItemName;
                                }
                            }
                            $trainerSignature = spp_character_profession_recipe_signature($trainerSpellName, $trainerItemNames);
                            if ($trainerSignature === '|') {
                                continue;
                            }
                            $existingRequired = $trainerRecipeRequiredRanksBySkill[$skillId][$trainerSignature] ?? null;
                            if ($existingRequired === null || (int)$requiredValue < (int)$existingRequired) {
                                if (!isset($trainerRecipeRequiredRanksBySkill[$skillId])) {
                                    $trainerRecipeRequiredRanksBySkill[$skillId] = array();
                                }
                                $trainerRecipeRequiredRanksBySkill[$skillId][$trainerSignature] = (int)$requiredValue;
                            }
                        }
                    }

                    foreach (array_keys($candidateSpellIds) as $spellId) {
                        if (!isset($spellMetaMap[$spellId])) {
                            continue;
                        }
                        $hasCraftOutput = !empty($spellOutputMap[$spellId]);
                        $isLearnedSpell = !empty($learnedSpellIds[$spellId]);
                        $assignedSkills = array();
                        foreach ($trainerSpellIdsBySkill as $skillId => $trainerSpells) {
                            if (isset($trainerSpells[$spellId])) {
                                $assignedSkills[$skillId] = true;
                            }
                        }
                        if (empty($assignedSkills) && $hasCraftOutput) {
                            $spellRow = $spellMetaMap[$spellId];
                            $spellName = trim((string)($spellRow['SpellName'] ?? ''));
                            $signatureItemNames = array();
                            foreach (array_keys($spellOutputMap[$spellId] ?? array()) as $itemId) {
                                if (!empty($craftedItemMap[$itemId]['name'])) {
                                    $signatureItemNames[] = (string)$craftedItemMap[$itemId]['name'];
                                }
                            }
                            $recipeSignature = spp_character_profession_recipe_signature($spellName, $signatureItemNames);
                            foreach ($trainerRecipeRequiredRanksBySkill as $skillId => $trainerRecipes) {
                                if (!empty($trainerRecipes[$recipeSignature])) {
                                    $assignedSkills[$skillId] = true;
                                }
                            }
                        }
                        if (empty($assignedSkills) && $hasCraftOutput) {
                            $itemRequiredSkills = array();
                            foreach (array_keys($spellOutputMap[$spellId] ?? array()) as $itemId) {
                                $requiredSkill = (int)($craftedItemMap[$itemId]['RequiredSkill'] ?? 0);
                                if ($requiredSkill > 0 && !empty($professionSkillIds[$requiredSkill])) {
                                    $itemRequiredSkills[$requiredSkill] = true;
                                }
                            }
                            if (count($itemRequiredSkills) === 1) {
                                $assignedSkills = $itemRequiredSkills;
                            }
                        }
                        if (count($assignedSkills) !== 1) {
                            $assignedSkills = array();
                        }
                        if (empty($assignedSkills)) {
                            continue;
                        }

                        $spellRow = $spellMetaMap[$spellId];
                        $spellName = trim((string)($spellRow['SpellName'] ?? ''));
                        if ($spellName === '') {
                            $spellName = 'Spell #' . $spellId;
                        }
                        $craftedItems = array();
                        $craftedItemNames = array();
                        foreach (array_keys($spellOutputMap[$spellId] ?? array()) as $itemId) {
                            if (!isset($craftedItemMap[$itemId])) {
                                continue;
                            }
                            $itemRow = $craftedItemMap[$itemId];
                            $craftedItemNames[] = (string)($itemRow['name'] ?? '');
                            $craftedItems[] = array(
                                'entry' => $itemId,
                                'name' => (string)($itemRow['name'] ?? ('Item #' . $itemId)),
                                'quality' => (int)($itemRow['Quality'] ?? 1),
                                'required_rank' => (int)($itemRow['RequiredSkillRank'] ?? 0),
                                'icon' => spp_character_icon_url($craftedItemIconMap[(int)($itemRow['displayid'] ?? 0)] ?? ''),
                            );
                        }
                        $spellIcon = spp_character_icon_url($spellIconMap[(int)($spellRow['SpellIconID'] ?? 0)] ?? '');

                        foreach (array_keys($assignedSkills) as $skillId) {
                            $recipeSignature = spp_character_profession_recipe_signature($spellName, $craftedItemNames);
                            $recipeRequiredRank = isset($trainerSpellIdsBySkill[$skillId][$spellId])
                                ? (int)$trainerSpellIdsBySkill[$skillId][$spellId]
                                : ($trainerRecipeRequiredRanksBySkill[$skillId][$recipeSignature] ?? 0);
                            if (!isset($professionSpellbookBySkillId[$skillId])) {
                                $professionSpellbookBySkillId[$skillId] = array();
                            }
                            $professionSpellbookBySkillId[$skillId][] = array(
                                'spell_id' => $spellId,
                                'spell_name' => $spellName,
                                'is_learned' => $isLearnedSpell,
                                'is_trainer' => isset($trainerSpellIdsBySkill[$skillId][$spellId]) || !empty($trainerRecipeRequiredRanksBySkill[$skillId][$recipeSignature]),
                                'icon' => !empty($craftedItems[0]['icon']) ? $craftedItems[0]['icon'] : $spellIcon,
                                'quality' => !empty($craftedItems[0]['quality']) ? (int)$craftedItems[0]['quality'] : 1,
                                'item_entry' => !empty($craftedItems[0]['entry']) ? (int)$craftedItems[0]['entry'] : 0,
                                'item_name' => !empty($craftedItems[0]['name']) ? (string)$craftedItems[0]['name'] : '',
                                'required_rank' => $recipeRequiredRank > 0 ? (int)$recipeRequiredRank : (!empty($craftedItems[0]['required_rank']) ? (int)$craftedItems[0]['required_rank'] : 0),
                                'created_items' => $craftedItems,
                            );
                        }
                    }

                    foreach ($professionSpellbookBySkillId as &$spellbook) {
                        usort($spellbook, function ($left, $right) {
                            if (!empty($left['is_learned']) !== !empty($right['is_learned'])) {
                                return !empty($right['is_learned']) <=> !empty($left['is_learned']);
                            }
                            if ((int)$left['required_rank'] !== (int)$right['required_rank']) {
                                return (int)$right['required_rank'] <=> (int)$left['required_rank'];
                            }
                            return strcasecmp((string)$left['spell_name'], (string)$right['spell_name']);
                        });
                    }
                    unset($spellbook);
                }

                foreach ($professionsByCategory as &$categorySkills) {
                    foreach ($categorySkills as &$skill) {
                        $skillId = (int)($skill['skill_id'] ?? 0);
                        $skill['recipes'] = $professionSpellbookBySkillId[$skillId] ?? array();
                        $skill['specializations'] = spp_character_profession_specializations((string)($skill['name'] ?? ''), $knownCharacterSpells);
                        $skill['recipe_filters'] = array();
                    }
                    unset($skill);
                }
                unset($categorySkills);
            }
        }

        return array(
            'talent_tabs' => $talentTabs,
            'reputations' => $reputations,
            'reputation_sections' => $reputationSections,
            'skills_by_category' => $skillsByCategory,
            'professions_by_category' => $professionsByCategory,
            'profession_recipes_by_skill_id' => $professionRecipesBySkillId,
            'known_character_spells' => $knownCharacterSpells,
        );
    }
}
