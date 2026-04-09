<?php

if (!function_exists('spp_character_load_core_content_state')) {
    function spp_character_load_core_content_state(array $args): array
    {
        $characterGuid = (int)($args['character_guid'] ?? 0);
        $character = (array)($args['character'] ?? []);
        $charsPdo = $args['chars_pdo'] ?? null;
        $worldPdo = $args['world_pdo'] ?? null;
        $armoryPdo = $args['armory_pdo'] ?? null;
        $slotNames = (array)($args['slot_names'] ?? []);

        $stats = [];
        $equipment = [];
        $activeQuestLog = [];
        $completedQuestHistory = [];
        $completedQuestTotal = 0;

        if (!$charsPdo instanceof PDO || !$worldPdo instanceof PDO || !$armoryPdo instanceof PDO || $characterGuid <= 0 || empty($character)) {
            return [
                'stats' => $stats,
                'equipment' => $equipment,
                'active_quest_log' => $activeQuestLog,
                'completed_quest_history' => $completedQuestHistory,
                'completed_quest_total' => $completedQuestTotal,
            ];
        }

        if (spp_character_table_exists($charsPdo, 'character_stats')) {
            $stmt = $charsPdo->prepare('SELECT * FROM `character_stats` WHERE `guid` = ? LIMIT 1');
            $stmt->execute(array($characterGuid));
            $stats = $stmt->fetch(PDO::FETCH_ASSOC) ?: array();
        }

        if (spp_character_table_exists($charsPdo, 'character_inventory')) {
            $inventoryItemGuidColumn = spp_character_resolve_column($charsPdo, 'character_inventory', array('item', 'item_guid', 'guid'));
            $inventoryItemEntryColumn = spp_character_resolve_column($charsPdo, 'character_inventory', array('item_template', 'item_id', 'item_entry'));
            $itemTemplateQualityColumn = spp_character_resolve_column($worldPdo, 'item_template', array('Quality', 'quality'));
            $itemTemplateLevelColumn = spp_character_resolve_column($worldPdo, 'item_template', array('ItemLevel', 'itemlevel', 'item_level'));
            $itemTemplateRequiredLevelColumn = spp_character_resolve_column($worldPdo, 'item_template', array('RequiredLevel', 'requiredlevel', 'required_level'));
            $itemTemplateDisplayIdColumn = spp_character_resolve_column($worldPdo, 'item_template', array('displayid', 'display_id'));

            if ($inventoryItemEntryColumn !== null) {
                $inventorySelect = array('`slot`');
                if ($inventoryItemGuidColumn !== null) {
                    $inventorySelect[] = '`' . $inventoryItemGuidColumn . '` AS `item_guid`';
                } else {
                    $inventorySelect[] = '0 AS `item_guid`';
                }
                $inventorySelect[] = '`' . $inventoryItemEntryColumn . '` AS `item_template`';

                $stmt = $charsPdo->prepare('SELECT ' . implode(', ', $inventorySelect) . ' FROM `character_inventory` WHERE `guid` = ? AND `bag` = 0 AND `slot` BETWEEN 0 AND 18 ORDER BY `slot` ASC');
                $stmt->execute(array($characterGuid));
                $inventoryRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $inventoryRows = array();
            }

            $itemIds = array();
            foreach ($inventoryRows as $row) {
                $itemIds[(int)$row['item_template']] = true;
            }
            $itemMap = array();
            $iconMap = array();
            if (!empty($itemIds) && $itemTemplateQualityColumn !== null && $itemTemplateLevelColumn !== null && $itemTemplateRequiredLevelColumn !== null && $itemTemplateDisplayIdColumn !== null) {
                $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
                $stmt = $worldPdo->prepare(
                    'SELECT `entry`, `name`, `' . $itemTemplateQualityColumn . '` AS `Quality`, `' . $itemTemplateLevelColumn . '` AS `ItemLevel`, `' . $itemTemplateRequiredLevelColumn . '` AS `RequiredLevel`, `' . $itemTemplateDisplayIdColumn . '` AS `displayid` FROM `item_template` WHERE `entry` IN (' . $placeholders . ')'
                );
                $stmt->execute(array_keys($itemIds));
                $displayIds = array();
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $itemRow) {
                    $itemMap[(int)$itemRow['entry']] = $itemRow;
                    if (!empty($itemRow['displayid'])) {
                        $displayIds[(int)$itemRow['displayid']] = true;
                    }
                }
                if (!empty($displayIds)) {
                    $placeholders = implode(',', array_fill(0, count($displayIds), '?'));
                    $stmt = $armoryPdo->prepare('SELECT `id`, `name` FROM `dbc_itemdisplayinfo` WHERE `id` IN (' . $placeholders . ')');
                    $stmt->execute(array_keys($displayIds));
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $iconRow) {
                        $iconMap[(int)$iconRow['id']] = (string)$iconRow['name'];
                    }
                }
            }
            foreach ($inventoryRows as $row) {
                $entry = (int)$row['item_template'];
                if (!isset($itemMap[$entry])) {
                    continue;
                }
                $slotId = (int)$row['slot'];
                $itemRow = $itemMap[$entry];
                $equipment[$slotId] = array(
                    'slot_name' => $slotNames[$slotId] ?? ('Slot ' . $slotId),
                    'item_guid' => isset($row['item_guid']) ? (int)$row['item_guid'] : 0,
                    'entry' => $entry,
                    'name' => (string)$itemRow['name'],
                    'quality' => (int)$itemRow['Quality'],
                    'item_level' => (int)$itemRow['ItemLevel'],
                    'required_level' => (int)$itemRow['RequiredLevel'],
                    'icon' => spp_character_icon_url($iconMap[(int)$itemRow['displayid']] ?? ''),
                );
            }
        }

        if (spp_character_table_exists($charsPdo, 'character_queststatus')) {
            $stmt = $charsPdo->prepare('SELECT * FROM `character_queststatus` WHERE `guid` = ? ORDER BY `rewarded` ASC, `quest` ASC');
            $stmt->execute(array($characterGuid));
            $questRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $questIds = array();
            foreach ($questRows as $questRow) {
                $questId = (int)($questRow['quest'] ?? 0);
                if ($questId > 0) {
                    $questIds[] = $questId;
                }
            }
            $questMeta = spp_character_fetch_quest_meta($worldPdo, $questIds);
            $questRewardItemIds = array();
            $questObjectiveEntityIds = array();
            $questObjectiveItemIds = array();
            foreach ($questMeta as $meta) {
                foreach (($meta['reward_choice_ids'] ?? array()) as $itemId) {
                    if ((int)$itemId > 0) {
                        $questRewardItemIds[(int)$itemId] = true;
                    }
                }
                foreach (($meta['reward_item_ids'] ?? array()) as $itemId) {
                    if ((int)$itemId > 0) {
                        $questRewardItemIds[(int)$itemId] = true;
                    }
                }
                foreach (($meta['required_entity_ids'] ?? array()) as $entityId) {
                    if ((int)$entityId !== 0) {
                        $questObjectiveEntityIds[(int)$entityId] = true;
                    }
                }
                foreach (($meta['required_item_ids'] ?? array()) as $itemId) {
                    if ((int)$itemId > 0) {
                        $questObjectiveItemIds[(int)$itemId] = true;
                    }
                }
            }
            $questRewardItems = spp_character_fetch_item_summaries($worldPdo, $armoryPdo, array_keys($questRewardItemIds));
            $questObjectiveNames = spp_character_fetch_quest_objective_names($worldPdo, array_keys($questObjectiveEntityIds), array_keys($questObjectiveItemIds));
            foreach ($questRows as $questRow) {
                $questId = (int)($questRow['quest'] ?? 0);
                if ($questId <= 0) {
                    continue;
                }
                $meta = $questMeta[$questId] ?? array();
                $meta['objective_names'] = $questObjectiveNames;
                $entry = array(
                    'quest' => $questId,
                    'title' => $meta['title'] ?? ('Quest #' . $questId),
                    'quest_level' => $meta['quest_level'] ?? null,
                    'description' => $meta['description'] ?? '',
                    'status_label' => spp_character_format_quest_status($questRow, $meta),
                    'progress_parts' => spp_character_build_quest_objectives($meta, $questRow),
                    'rewards' => spp_character_build_quest_rewards($meta, $questRewardItems),
                );
                if (!empty($questRow['rewarded'])) {
                    $completedQuestHistory[] = $entry;
                } else {
                    $activeQuestLog[] = $entry;
                }
            }
            $completedQuestTotal = count($completedQuestHistory);
            $completedQuestHistory = array_slice(array_reverse($completedQuestHistory), 0, 50);
            $activeQuestLog = array_slice($activeQuestLog, 0, 50);
        }

        return [
            'stats' => $stats,
            'equipment' => $equipment,
            'active_quest_log' => $activeQuestLog,
            'completed_quest_history' => $completedQuestHistory,
            'completed_quest_total' => $completedQuestTotal,
        ];
    }
}
