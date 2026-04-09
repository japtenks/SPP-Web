<?php

require_once dirname(__DIR__, 2) . '/components/server/server.items.helpers.php';
require_once dirname(__DIR__, 2) . '/core/xfer/item_set_shared.php';

if (!function_exists('spp_item_detail_load_core_data')) {
    function spp_item_detail_load_core_data(array $args): array
    {
        $realmId = (int)($args['realm_id'] ?? 1);
        $itemId = (int)($args['item_id'] ?? 0);
        $config = (array)($args['config'] ?? []);
        $classNames = (array)($args['class_names'] ?? []);
        $raceNames = (array)($args['race_names'] ?? []);
        $allianceRaces = (array)($args['alliance_races'] ?? []);
        $upgradePresets = (array)($args['upgrade_presets'] ?? []);
        $upgradeMode = (string)($args['upgrade_mode'] ?? '');
        $upgradeProfileId = (string)($args['upgrade_profile_id'] ?? '');
        $upgradeWeightsRaw = (string)($args['upgrade_weights_raw'] ?? '');

        $pageError = '';
        $item = null;
        $itemSet = null;
        $randomProperties = [];
        $owners = [];
        $upgrades = [];
        $upgradeManualWeights = [];
        $upgradeActiveWeights = [];
        $upgradeActiveProfile = null;
        $upgradeAvailablePresets = [];
        $upgradeCurrentStats = [];
        $upgradeCurrentScore = null;
        $upgradeNotice = '';
        $iconName = '';

        try {
            $worldPdo = spp_get_pdo('world', $realmId);
            $charsPdo = spp_get_pdo('chars', $realmId);
            $itemColumns = spp_item_database_item_columns($worldPdo, $realmId);
            $metadataPdo = spp_item_database_pick_metadata_pdo($realmId, ['dbc_itemdisplayinfo', 'dbc_itemrandomproperties', 'dbc_spellitemenchantment', 'armory_instance_data', 'armory_instance_template'], $metadataSource);

            $localeId = isset($config['locales']) ? (int)$config['locales'] : 0;
            $localeField = $localeId > 0 ? 'name_loc' . $localeId : null;

            if ($localeField) {
                $itemStmt = $worldPdo->prepare(
                    'SELECT it.*, '
                    . spp_item_database_item_column_sql($itemColumns, 'displayid') . ' AS `displayid`, '
                    . spp_item_database_item_column_sql($itemColumns, 'Quality') . ' AS `Quality`, '
                    . spp_item_database_item_column_sql($itemColumns, 'Flags') . ' AS `Flags`, '
                    . spp_item_database_item_column_sql($itemColumns, 'ItemLevel') . ' AS `ItemLevel`, '
                    . spp_item_database_item_column_sql($itemColumns, 'RequiredLevel') . ' AS `RequiredLevel`, '
                    . spp_item_database_item_column_sql($itemColumns, 'InventoryType') . ' AS `InventoryType`, '
                    . 'li.`' . $localeField . '` AS `localized_name`, li.`description_loc' . $localeId . '` AS `localized_description` FROM `item_template` it LEFT JOIN `locales_item` li ON li.`entry` = it.`entry` WHERE it.`entry` = ? LIMIT 1'
                );
            } else {
                $itemStmt = $worldPdo->prepare(
                    'SELECT it.*, '
                    . spp_item_database_item_column_sql($itemColumns, 'displayid') . ' AS `displayid`, '
                    . spp_item_database_item_column_sql($itemColumns, 'Quality') . ' AS `Quality`, '
                    . spp_item_database_item_column_sql($itemColumns, 'Flags') . ' AS `Flags`, '
                    . spp_item_database_item_column_sql($itemColumns, 'ItemLevel') . ' AS `ItemLevel`, '
                    . spp_item_database_item_column_sql($itemColumns, 'RequiredLevel') . ' AS `RequiredLevel`, '
                    . spp_item_database_item_column_sql($itemColumns, 'InventoryType') . ' AS `InventoryType` FROM `item_template` it WHERE it.`entry` = ? LIMIT 1'
                );
            }
            $itemStmt->execute([$itemId]);
            $itemRow = $itemStmt->fetch(PDO::FETCH_ASSOC);

            if (!$itemRow) {
                return ['page_error' => 'That item could not be found.'];
            }

            $displayId = (int)($itemRow['displayid'] ?? 0);
            if ($displayId > 0) {
                $iconMap = spp_item_database_fetch_item_icons($realmId, [$displayId]);
                $iconName = (string)($iconMap[$displayId] ?? '');
            }

            $itemName = ($localeField && !empty($itemRow['localized_name'])) ? (string)$itemRow['localized_name'] : (string)$itemRow['name'];
            $itemDescription = ($localeField && !empty($itemRow['localized_description'])) ? (string)$itemRow['localized_description'] : trim((string)($itemRow['description'] ?? ''));
            $quality = (int)($itemRow['Quality'] ?? 0);
            $flags = (int)($itemRow['Flags'] ?? 0);

            $item = [
                'id' => $itemId,
                'name' => $itemName,
                'description' => $itemDescription,
                'quality' => $quality,
                'quality_label' => spp_modern_item_quality_label($quality),
                'quality_color' => spp_modern_item_quality_color($quality),
                'icon' => spp_modern_item_icon_url($iconName),
                'icon_name' => (string)$iconName,
                'level' => (int)($itemRow['ItemLevel'] ?? 0),
                'required_level' => (int)($itemRow['RequiredLevel'] ?? 0),
                'required_skill' => (int)($itemRow['RequiredDisenchantSkill'] ?? 0),
                'buy_price' => (int)($itemRow['BuyPrice'] ?? 0),
                'sell_price' => (int)($itemRow['SellPrice'] ?? 0),
                'max_durability' => (int)($itemRow['MaxDurability'] ?? 0),
                'inventory_type' => (int)($itemRow['InventoryType'] ?? 0),
                'item_class_id' => (int)($itemRow['class'] ?? 0),
                'item_subclass_id' => (int)($itemRow['subclass'] ?? 0),
                'slot_name' => spp_modern_item_inventory_type_name((int)($itemRow['InventoryType'] ?? 0)),
                'class_name' => spp_modern_item_class_name((int)($itemRow['class'] ?? 0), (int)($itemRow['subclass'] ?? 0)),
                'source' => spp_item_database_cache_source($worldPdo, $metadataPdo, $itemId, (($flags & 32768) === 32768), $realmId),
            ];

            $upgradeManualWeights = spp_item_upgrade_parse_weights($upgradeWeightsRaw);
            foreach ($upgradePresets as $presetId => $preset) {
                if (!empty($preset['label'])) {
                    $upgradeAvailablePresets[$presetId] = $preset;
                }
            }
            if ($upgradeProfileId !== '' && isset($upgradeAvailablePresets[$upgradeProfileId])) {
                $upgradeActiveProfile = $upgradeAvailablePresets[$upgradeProfileId];
            }

            $upgradeCurrentStats = spp_item_upgrade_extract_stats($itemRow);
            if ($upgradeMode === '' && $upgradeProfileId !== '') {
                $upgradeMode = 'preset';
            } elseif ($upgradeMode === '' && $upgradeWeightsRaw !== '') {
                $upgradeMode = 'manual';
            }

            if ($upgradeMode === 'manual') {
                if ($upgradeManualWeights) {
                    $upgradeActiveWeights = $upgradeManualWeights;
                } else {
                    $upgradeNotice = 'Enter one or more manual weights like strength:1, crit:0.7, hit:0.9 or switch to ilvl fallback.';
                }
            } elseif ($upgradeMode === 'preset') {
                if ($upgradeActiveProfile && !empty($upgradeActiveProfile['weights']) && is_array($upgradeActiveProfile['weights'])) {
                    $upgradeActiveWeights = $upgradeActiveProfile['weights'];
                } else {
                    $upgradeNotice = 'Choose a built-in class/spec preset to rank upgrades, or use the ilvl fallback.';
                }
            } elseif ($upgradeMode !== 'ilvl') {
                $upgradeNotice = 'Choose a built-in class/spec preset, paste manual weights, or use the ilvl fallback.';
            }

            if ($upgradeActiveWeights) {
                $upgradeCurrentScore = spp_item_upgrade_score($upgradeCurrentStats, $upgradeActiveWeights);
            }

            $upgradeSql = 'SELECT it.`entry`, it.`name`, '
                . spp_item_database_item_column_sql($itemColumns, 'Quality') . ' AS `Quality`, '
                . spp_item_database_item_column_sql($itemColumns, 'ItemLevel') . ' AS `ItemLevel`, '
                . spp_item_database_item_column_sql($itemColumns, 'RequiredLevel') . ' AS `RequiredLevel`, '
                . spp_item_database_item_column_sql($itemColumns, 'displayid') . ' AS `displayid`, '
                . spp_item_database_item_column_sql($itemColumns, 'InventoryType') . ' AS `InventoryType`, '
                . 'it.`class`, it.`subclass`, it.`description`, '
                . spp_item_database_item_column_sql($itemColumns, 'Flags') . ' AS `Flags`, '
                . 'it.`Armor`, it.`stat_type1`, it.`stat_value1`, it.`stat_type2`, it.`stat_value2`, it.`stat_type3`, it.`stat_value3`, it.`stat_type4`, it.`stat_value4`, it.`stat_type5`, it.`stat_value5`, it.`stat_type6`, it.`stat_value6`, it.`stat_type7`, it.`stat_value7`, it.`stat_type8`, it.`stat_value8`, it.`stat_type9`, it.`stat_value9`, it.`stat_type10`, it.`stat_value10`, it.`holy_res`, it.`fire_res`, it.`nature_res`, it.`frost_res`, it.`shadow_res`, it.`arcane_res` FROM `item_template` it WHERE it.`entry` <> :entry AND '
                . spp_item_database_item_column_sql($itemColumns, 'InventoryType') . ' = :inventory_type AND it.`class` = :item_class AND (it.`subclass` = :item_subclass OR :use_subclass = 0) AND '
                . spp_item_database_item_column_sql($itemColumns, 'Quality') . ' > 0 ORDER BY `Quality` DESC, `ItemLevel` DESC, `RequiredLevel` ASC, `name` ASC LIMIT 150';
            $upgradeStmt = $worldPdo->prepare($upgradeSql);
            $useSubclass = in_array((int)$item['item_class_id'], [2, 4], true) ? 1 : 0;
            $upgradeStmt->execute([
                ':entry' => $itemId,
                ':inventory_type' => (int)$item['inventory_type'],
                ':item_class' => (int)$item['item_class_id'],
                ':item_subclass' => (int)$item['item_subclass_id'],
                ':use_subclass' => $useSubclass,
            ]);
            $upgradeRows = $upgradeStmt->fetchAll(PDO::FETCH_ASSOC);
            if ($upgradeRows) {
                $upgradeDisplayIds = [];
                foreach ($upgradeRows as $upgradeRow) {
                    $upgradeDisplayId = (int)($upgradeRow['displayid'] ?? 0);
                    if ($upgradeDisplayId > 0) {
                        $upgradeDisplayIds[$upgradeDisplayId] = $upgradeDisplayId;
                    }
                }

                $upgradeIconMap = [];
                if ($upgradeDisplayIds) {
                    $upgradeIconMap = spp_item_database_fetch_item_icons($realmId, array_values($upgradeDisplayIds));
                }

                $scoredUpgrades = [];
                foreach ($upgradeRows as $upgradeRow) {
                    $upgradeQuality = (int)($upgradeRow['Quality'] ?? 0);
                    $upgradeLevel = (int)($upgradeRow['ItemLevel'] ?? 0);
                    $upgradeDisplayId = (int)($upgradeRow['displayid'] ?? 0);
                    $upgradeStats = spp_item_upgrade_extract_stats($upgradeRow);
                    $upgradeEntry = [
                        'id' => (int)$upgradeRow['entry'],
                        'name' => (string)$upgradeRow['name'],
                        'quality' => $upgradeQuality,
                        'quality_label' => spp_modern_item_quality_label($upgradeQuality),
                        'quality_color' => spp_modern_item_quality_color($upgradeQuality),
                        'level' => $upgradeLevel,
                        'required_level' => (int)($upgradeRow['RequiredLevel'] ?? 0),
                        'slot_name' => spp_modern_item_inventory_type_name((int)($upgradeRow['InventoryType'] ?? 0)),
                        'class_name' => spp_modern_item_class_name((int)($upgradeRow['class'] ?? 0), (int)($upgradeRow['subclass'] ?? 0)),
                        'description' => trim((string)($upgradeRow['description'] ?? '')),
                        'source' => '',
                        'flags' => (int)($upgradeRow['Flags'] ?? 0),
                        'icon' => spp_modern_item_icon_url($upgradeIconMap[$upgradeDisplayId] ?? ''),
                        'stats' => $upgradeStats,
                        'score' => null,
                        'score_delta' => null,
                        'top_stats' => [],
                        'matched_stats' => [],
                    ];

                    if ($upgradeMode === 'ilvl') {
                        if ($upgradeLevel === (int)$item['level'] && $upgradeQuality <= (int)$item['quality']) {
                            continue;
                        }
                        if ($upgradeLevel < (int)$item['level']) {
                            continue;
                        }
                        $scoredUpgrades[] = $upgradeEntry;
                        continue;
                    }

                    if (!$upgradeActiveWeights) {
                        continue;
                    }

                    $upgradeScore = spp_item_upgrade_score($upgradeStats, $upgradeActiveWeights);
                    if (!$upgradeScore['matched']) {
                        continue;
                    }

                    $upgradeEntry['score'] = $upgradeScore['score'];
                    $upgradeEntry['score_delta'] = $upgradeScore['score'] - (float)($upgradeCurrentScore['score'] ?? 0.0);
                    $upgradeEntry['matched_stats'] = $upgradeScore['matched'];
                    $upgradeEntry['top_stats'] = spp_item_upgrade_top_stat_lines($upgradeScore['matched']);
                    $scoredUpgrades[] = $upgradeEntry;
                }

                if ($upgradeMode === 'ilvl') {
                    usort($scoredUpgrades, static function ($left, $right) {
                        $levelCompare = ((int)($right['level'] ?? 0)) <=> ((int)($left['level'] ?? 0));
                        if ($levelCompare !== 0) {
                            return $levelCompare;
                        }
                        $qualityCompare = ((int)($right['quality'] ?? 0)) <=> ((int)($left['quality'] ?? 0));
                        if ($qualityCompare !== 0) {
                            return $qualityCompare;
                        }
                        $requiredCompare = ((int)($left['required_level'] ?? 0)) <=> ((int)($right['required_level'] ?? 0));
                        if ($requiredCompare !== 0) {
                            return $requiredCompare;
                        }
                        return strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
                    });
                } elseif ($upgradeActiveWeights) {
                    usort($scoredUpgrades, static function ($left, $right) {
                        $scoreCompare = ((float)($right['score'] ?? 0.0)) <=> ((float)($left['score'] ?? 0.0));
                        if ($scoreCompare !== 0) {
                            return $scoreCompare;
                        }
                        $levelCompare = ((int)($right['level'] ?? 0)) <=> ((int)($left['level'] ?? 0));
                        if ($levelCompare !== 0) {
                            return $levelCompare;
                        }
                        return strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
                    });
                }

                $scoredUpgrades = array_slice($scoredUpgrades, 0, 12);
                foreach ($scoredUpgrades as $upgradeEntry) {
                    $upgradeEntry['source'] = spp_item_database_cache_source($worldPdo, $metadataPdo, (int)$upgradeEntry['id'], (((int)($upgradeEntry['flags'] ?? 0) & 32768) === 32768), $realmId);
                    unset($upgradeEntry['flags']);
                    $upgrades[] = $upgradeEntry;
                }
            }

            try {
                if ($metadataPdo instanceof PDO && spp_realm_capability_table_exists($metadataPdo, 'dbc_itemrandomproperties')) {
                    $randomProperties = spp_modern_item_random_properties($worldPdo, $metadataPdo, $itemRow);
                } else {
                    spp_item_database_log_compatibility($realmId, 'metadata.dbc_itemrandomproperties', 'Optional random-property metadata unavailable; skipping.');
                }
            } catch (Throwable $e) {
                spp_item_database_log_compatibility($realmId, 'random_properties', $e->getMessage());
                $randomProperties = [];
            }

            $itemSetId = (int)($itemRow['itemset'] ?? 0);
            if ($itemSetId > 0) {
                $setData = spp_shared_get_itemset_data($itemSetId, $realmId);
                if (!empty($setData)) {
                    $itemSet = [
                        'id' => (int)($setData['id'] ?? $itemSetId),
                        'name' => (string)($setData['name'] ?? 'Item Set'),
                        'pieces' => [],
                        'bonuses' => [],
                        'detail_url' => function_exists('spp_sets_detail_url') ? spp_sets_detail_url((int)$realmId, (string)($setData['name'] ?? 'Item Set')) : ('index.php?n=server&sub=item&type=sets&realm=' . (int)$realmId . '&set=' . urlencode((string)($setData['name'] ?? 'Item Set'))),
                    ];

                    foreach ((array)($setData['items'] ?? []) as $setItemRow) {
                        $itemSet['pieces'][] = [
                            'entry' => (int)($setItemRow['entry'] ?? 0),
                            'name' => (string)($setItemRow['name'] ?? ''),
                            'active' => ((int)($setItemRow['entry'] ?? 0) === $itemId),
                        ];
                    }

                    foreach ((array)($setData['bonuses'] ?? []) as $bonusRow) {
                        $bonusDescription = trim((string)($bonusRow['resolved_desc'] ?? $bonusRow['raw_desc'] ?? ''));
                        if ($bonusDescription === '') {
                            continue;
                        }
                        $itemSet['bonuses'][] = [
                            'pieces' => (int)($bonusRow['pieces'] ?? 0),
                            'description' => $bonusDescription,
                        ];
                    }
                }
            }

            $inventoryItemEntryColumn = spp_realm_capability_pick_column($charsPdo, 'character_inventory', ['item_template', 'item_id', 'item_entry'], 'item_template');
            $guildMemberGuildIdColumn = spp_realm_capability_pick_column($charsPdo, 'guild_member', ['guildid', 'guild_id'], 'guildid');
            $guildGuildIdColumn = spp_realm_capability_pick_column($charsPdo, 'guild', ['guildid', 'guild_id'], $guildMemberGuildIdColumn);
            $ownerSql = 'SELECT DISTINCT c.`guid`, c.`name`, c.`level`, c.`race`, c.`class`, c.`gender`';
            if ($guildMemberGuildIdColumn !== null) {
                $ownerSql .= ', gm.`' . $guildMemberGuildIdColumn . '` AS `guildid`';
            } else {
                $ownerSql .= ', 0 AS `guildid`';
            }
            $ownerSql .= ', g.`name` AS `guild_name` FROM `character_inventory` ci INNER JOIN `characters` c ON c.`guid` = ci.`guid`';
            $ownerSql .= ' LEFT JOIN `guild_member` gm ON gm.`guid` = c.`guid`';
            if ($guildMemberGuildIdColumn !== null && $guildGuildIdColumn !== null) {
                $ownerSql .= ' LEFT JOIN `guild` g ON g.`' . $guildGuildIdColumn . '` = gm.`' . $guildMemberGuildIdColumn . '`';
            } else {
                $ownerSql .= ' LEFT JOIN `guild` g ON 1 = 0';
            }
            $ownerSql .= ' WHERE ci.`' . $inventoryItemEntryColumn . '` = ? ORDER BY c.`level` DESC, c.`name` ASC LIMIT 100';
            $ownerStmt = $charsPdo->prepare($ownerSql);
            $ownerStmt->execute([$itemId]);
            foreach ($ownerStmt->fetchAll(PDO::FETCH_ASSOC) as $owner) {
                $raceId = (int)($owner['race'] ?? 0);
                $classId = (int)($owner['class'] ?? 0);
                $className = $classNames[$classId] ?? 'Unknown';
                $isAlliance = in_array($raceId, $allianceRaces, true);
                $owners[] = [
                    'guid' => (int)$owner['guid'],
                    'name' => (string)$owner['name'],
                    'level' => (int)($owner['level'] ?? 0),
                    'race' => $raceId,
                    'class' => $classId,
                    'gender' => (int)($owner['gender'] ?? 0),
                    'race_name' => $raceNames[$raceId] ?? 'Unknown',
                    'class_name' => $className,
                    'class_slug' => strtolower(str_replace(' ', '', $className)),
                    'guild_id' => (int)($owner['guildid'] ?? 0),
                    'guild_name' => (string)($owner['guild_name'] ?? ''),
                    'faction' => $isAlliance ? 'Alliance' : 'Horde',
                    'faction_icon' => spp_modern_faction_logo_url($isAlliance ? 'alliance' : 'horde'),
                ];
            }
        } catch (Throwable $e) {
            spp_item_database_log_compatibility($realmId, 'item_detail', $e->getMessage());
            $pageError = 'Item details could not be loaded from the realm databases.';
        }

        return [
            'page_error' => $pageError,
            'item' => $item,
            'item_set' => $itemSet,
            'random_properties' => $randomProperties,
            'owners' => $owners,
            'upgrades' => $upgrades,
            'upgrade_mode' => $upgradeMode,
            'upgrade_manual_weights' => $upgradeManualWeights,
            'upgrade_active_weights' => $upgradeActiveWeights,
            'upgrade_active_profile' => $upgradeActiveProfile,
            'upgrade_available_presets' => $upgradeAvailablePresets,
            'upgrade_current_stats' => $upgradeCurrentStats,
            'upgrade_current_score' => $upgradeCurrentScore,
            'upgrade_notice' => $upgradeNotice,
            'icon_name' => $iconName,
        ];
    }
}
