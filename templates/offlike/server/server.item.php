<?php
$siteDatabaseHandle = $GLOBALS['DB'] ?? null;
$siteRoot = dirname(__DIR__, 3);
require_once($siteRoot . '/app/support/terminology.php');
require_once($siteRoot . '/config/config-protected.php');
require_once($siteRoot . '/components/server/server.items.helpers.php');
require_once($siteRoot . '/core/xfer/item_set_shared.php');
require_once($siteRoot . '/app/server/item-detail-page.php');
require_once($siteRoot . '/app/server/item-detail-data.php');
require_once($siteRoot . '/app/server/item-detail-comments.php');
if ($siteDatabaseHandle !== null) {
    $GLOBALS['DB'] = $siteDatabaseHandle;
    $DB = $siteDatabaseHandle;
}

$armoryConfig = (array)($GLOBALS['armoryRuntime'] ?? []);
$publicTerms = spp_terminology_public();

if (!function_exists('spp_modern_item_quality_color')) {
    function spp_modern_item_quality_color($quality) {
        switch ((int)$quality) {
            case 0: return '#9d9d9d';
            case 1: return '#ffffff';
            case 2: return '#1eff00';
            case 3: return '#0070dd';
            case 4: return '#a335ee';
            case 5: return '#ff8000';
            default: return '#e6cc80';
        }
    }
}

if (!function_exists('spp_modern_item_quality_label')) {
    function spp_modern_item_quality_label($quality) {
        $labels = [0 => 'Poor', 1 => 'Common', 2 => 'Uncommon', 3 => 'Rare', 4 => 'Epic', 5 => 'Legendary'];
        $quality = (int)$quality;
        return $labels[$quality] ?? 'Unknown';
    }
}

if (!function_exists('spp_modern_item_icon_url')) {
    function spp_modern_item_icon_url($iconName) {
        $iconName = trim((string)$iconName);
        if ($iconName === '') {
            return spp_armory_icon_url('404.png');
        }
        if (preg_match('#^https?://#i', $iconName) || strpos($iconName, '//') === 0) {
            return $iconName;
        }
        if ($iconName[0] === '/') {
            return $iconName;
        }
        if (strpos($iconName, 'images/') === 0) {
            return spp_armory_image_url(substr($iconName, strlen('images/')));
        }
        if (strpos($iconName, 'armory/images/') === 0) {
            return spp_armory_image_url(substr($iconName, strlen('armory/images/')));
        }
        return spp_resolve_armory_icon_url(strtolower($iconName));
    }
}

if (!function_exists('spp_class_icon_url')) {
    function spp_class_icon_url($classId)
    {
        $classId = (int)$classId;
        $icons = [
            1 => 'class-1',
            2 => 'class-2',
            3 => 'class-3',
            4 => 'class-4',
            5 => 'class-5',
            6 => 'class-6',
            7 => 'class-7',
            8 => 'class-8',
            9 => 'class-9',
            11 => 'class-11',
        ];

        if (!isset($icons[$classId])) {
            return spp_armory_icon_url('404.png');
        }

        return spp_resolve_armory_icon_url($icons[$classId]);
    }
}

if (!function_exists('spp_race_icon_url')) {
    function spp_race_icon_url($raceId, $gender)
    {
        $raceId = (int)$raceId;
        $gender = ((int)$gender === 1) ? 'female' : 'male';
        $icons = [
            1 => 'achievement_character_human_' . $gender,
            2 => 'achievement_character_orc_' . $gender,
            3 => 'achievement_character_dwarf_' . $gender,
            4 => 'achievement_character_nightelf_' . $gender,
            5 => 'achievement_character_undead_' . $gender,
            6 => 'achievement_character_tauren_' . $gender,
            7 => 'achievement_character_gnome_' . $gender,
            8 => 'achievement_character_troll_' . $gender,
            10 => 'achievement_character_bloodelf_' . $gender,
            11 => 'achievement_character_draenei_' . $gender,
        ];

        if (!isset($icons[$raceId])) {
            return spp_armory_icon_url('404.png');
        }

        return spp_resolve_armory_icon_url($icons[$raceId]);
    }
}

if (!function_exists('spp_modern_item_inventory_type_name')) {
    function spp_modern_item_inventory_type_name($inventoryType) {
        $map = [0 => 'None', 1 => 'Head', 2 => 'Neck', 3 => 'Shoulder', 5 => 'Chest', 6 => 'Waist', 7 => 'Legs', 8 => 'Feet', 9 => 'Wrist', 10 => 'Hands', 11 => 'Finger', 12 => 'Trinket', 13 => 'One Hand', 14 => 'Shield', 15 => 'Weapon', 16 => 'Back', 17 => 'Two-Hand', 21 => 'Main Hand', 22 => 'Off Hand', 23 => 'Held In Off-hand'];
        $inventoryType = (int)$inventoryType;
        return $map[$inventoryType] ?? ('Slot ' . $inventoryType);
    }
}

if (!function_exists('spp_modern_item_class_name')) {
    function spp_modern_item_class_name($class, $subclass) {
        $class = (int)$class;
        $subclass = (int)$subclass;
        switch ($class) {
            case 2:
                $map = [0 => 'Axe (1H)', 1 => 'Axe (2H)', 2 => 'Bow', 3 => 'Gun', 4 => 'Mace (1H)', 5 => 'Mace (2H)', 6 => 'Polearm', 7 => 'Sword (1H)', 8 => 'Sword (2H)', 10 => 'Staff', 13 => 'Fist Weapon', 15 => 'Dagger', 16 => 'Thrown', 18 => 'Crossbow', 19 => 'Wand', 20 => 'Fishing Pole'];
                return $map[$subclass] ?? 'Weapon';
            case 4:
                $map = [0 => 'Misc', 1 => 'Cloth', 2 => 'Leather', 3 => 'Mail', 4 => 'Plate', 6 => 'Shield', 7 => 'Libram', 8 => 'Idol', 9 => 'Totem', 10 => 'Sigil'];
                return $map[$subclass] ?? 'Armor';
            case 15:
                $map = [0 => 'Junk', 2 => 'Pet', 3 => 'Holiday', 4 => 'Other', 5 => 'Mount'];
                return $map[$subclass] ?? 'Misc';
            default:
                return 'Item';
        }
    }
}

if (!function_exists('spp_modern_item_source_search')) {
    function spp_modern_item_source_search($source)
    {
        $source = trim((string)$source);
        if ($source === '') {
            return '';
        }

        $patterns = [
            '/^Dropped by\s+(.+)$/i',
            '/^Found in\s+(.+)$/i',
            '/^PvP Reward(?:\s+\(.+\))?$/i',
            '/^Quest Reward$/i',
            '/^Quest Item$/i',
        ];

        if (preg_match($patterns[0], $source, $matches)) {
            return trim((string)$matches[1]);
        }
        if (preg_match($patterns[1], $source, $matches)) {
            return trim((string)$matches[1]);
        }
        if (preg_match($patterns[2], $source)) {
            return 'PvP';
        }
        if (preg_match($patterns[3], $source)) {
            return 'Quest Reward';
        }
        if (preg_match($patterns[4], $source)) {
            return 'Quest Item';
        }

        if (strpos($source, ' - ') !== false) {
            $parts = explode(' - ', $source);
            return trim((string)$parts[0]);
        }

        return $source;
    }
}

if (!function_exists('spp_modern_item_format_money')) {
    function spp_modern_item_format_money($value) {
        $value = (int)$value;
        $gold = intdiv($value, 10000);
        $silver = intdiv($value % 10000, 100);
        $copper = $value % 100;
        $parts = [];
        if ($gold > 0) $parts[] = $gold . 'g';
        if ($silver > 0) $parts[] = $silver . 's';
        if ($copper > 0 || !$parts) $parts[] = $copper . 'c';
        return implode(' ', $parts);
    }
}

if (!function_exists('spp_modern_item_cache_source')) {
    function spp_modern_item_cache_source(PDO $worldPdo, PDO $armoryPdo, $itemId, $isPvpReward) {
        $itemId = (int)$itemId;
        $checks = [
            ['SELECT `entry` FROM `quest_template` WHERE `SrcItemId` = ? LIMIT 1', 'Quest Item'],
            ['SELECT `entry` FROM `npc_vendor` WHERE `item` = ? LIMIT 1', $isPvpReward ? 'PvP Reward (Vendor)' : 'Vendor'],
            ['SELECT `entry` FROM `npc_vendor_template` WHERE `item` = ? LIMIT 1', $isPvpReward ? 'PvP Reward (Vendor)' : 'Vendor'],
        ];
        foreach ($checks as $check) {
            $stmt = $worldPdo->prepare($check[0]);
            $stmt->execute([$itemId]);
            if ($stmt->fetchColumn()) {
                return $check[1];
            }
        }

        $objectStmt = $worldPdo->prepare('SELECT `entry` FROM `gameobject_loot_template` WHERE `item` = ? LIMIT 1');
        $objectStmt->execute([$itemId]);
        $objectLootId = $objectStmt->fetchColumn();
        if ($objectLootId) {
            $instanceStmt = $armoryPdo->prepare('SELECT * FROM `armory_instance_data` WHERE (`id` = ? OR `lootid_1` = ? OR `lootid_2` = ? OR `lootid_3` = ? OR `lootid_4` = ? OR `name_id` = ?) AND `type` = \'object\' LIMIT 1');
            $instanceStmt->execute([$objectLootId, $objectLootId, $objectLootId, $objectLootId, $objectLootId, $objectLootId]);
            $instanceLoot = $instanceStmt->fetch(PDO::FETCH_ASSOC);
            if ($instanceLoot) {
                $templateStmt = $armoryPdo->prepare('SELECT * FROM `armory_instance_template` WHERE `id` = ? LIMIT 1');
                $templateStmt->execute([(int)$instanceLoot['instance_id']]);
                $instanceInfo = $templateStmt->fetch(PDO::FETCH_ASSOC);
                if ($instanceInfo) {
                    $suffix = (((int)$instanceInfo['expansion'] < 2) || !(int)$instanceInfo['raid']) ? '' : ((int)$instanceInfo['is_heroic'] ? ' (H)' : '');
                    return trim($instanceLoot['name_en_gb'] . ' - ' . $instanceInfo['name_en_gb'] . $suffix);
                }
            }
            $objectNameStmt = $worldPdo->prepare('SELECT `name` FROM `gameobject_template` WHERE `entry` = ? LIMIT 1');
            $objectNameStmt->execute([$objectLootId]);
            $objectName = $objectNameStmt->fetchColumn();
            if ($objectName) {
                return 'Found in ' . (string)$objectName;
            }
            return 'Container Drop';
        }

        $creatureStmt = $worldPdo->prepare('SELECT `entry` FROM `creature_loot_template` WHERE `item` = ? LIMIT 1');
        $creatureStmt->execute([$itemId]);
        $creatureLootId = $creatureStmt->fetchColumn();
        if ($creatureLootId) {
            $instanceStmt = $armoryPdo->prepare('SELECT * FROM `armory_instance_data` WHERE (`id` = ? OR `lootid_1` = ? OR `lootid_2` = ? OR `lootid_3` = ? OR `lootid_4` = ? OR `name_id` = ?) AND `type` = \'npc\' LIMIT 1');
            $instanceStmt->execute([$creatureLootId, $creatureLootId, $creatureLootId, $creatureLootId, $creatureLootId, $creatureLootId]);
            $instanceLoot = $instanceStmt->fetch(PDO::FETCH_ASSOC);
            if ($instanceLoot) {
                $templateStmt = $armoryPdo->prepare('SELECT * FROM `armory_instance_template` WHERE `id` = ? LIMIT 1');
                $templateStmt->execute([(int)$instanceLoot['instance_id']]);
                $instanceInfo = $templateStmt->fetch(PDO::FETCH_ASSOC);
                if ($instanceInfo) {
                    $suffix = (((int)$instanceInfo['expansion'] < 2) || !(int)$instanceInfo['raid']) ? '' : ((int)$instanceInfo['is_heroic'] ? ' (H)' : '');
                    return trim($instanceLoot['name_en_gb'] . ' - ' . $instanceInfo['name_en_gb'] . $suffix);
                }
            }
            $creatureNameStmt = $worldPdo->prepare('SELECT `Name` FROM `creature_template` WHERE `entry` = ? LIMIT 1');
            $creatureNameStmt->execute([$creatureLootId]);
            $creatureName = $creatureNameStmt->fetchColumn();
            if ($creatureName) {
                return 'Dropped by ' . (string)$creatureName;
            }
            return 'Dropped Item';
        }

        $referenceStmt = $worldPdo->prepare('SELECT `entry`, `groupid` FROM `reference_loot_template` WHERE `item` = ?');
        $referenceStmt->execute([$itemId]);
        $referenceRows = $referenceStmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($referenceRows)) {
            $referenceEntry = (int)($referenceRows[0]['entry'] ?? 0);
            $referenceGroupId = (int)($referenceRows[0]['groupid'] ?? 0);
            if (count($referenceRows) > 1) {
                return 'World Drop';
            }

            if ($referenceEntry > 0) {
                $bossStmt = $worldPdo->prepare('SELECT `entry` FROM `creature_loot_template` WHERE `mincountOrRef` = ?');
                $bossStmt->execute([-1 * $referenceEntry]);
                $bossRows = $bossStmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($bossRows) && count($bossRows) <= 2) {
                    $creature = (int)($bossRows[0]['entry'] ?? 0);
                    if ($creature > 0) {
                        $instanceStmt = $armoryPdo->prepare('SELECT * FROM `armory_instance_data` WHERE (`id` = ? OR `lootid_1` = ? OR `lootid_2` = ? OR `lootid_3` = ? OR `lootid_4` = ? OR `name_id` = ?) AND `type` = \'npc\' LIMIT 1');
                        $instanceStmt->execute([$creature, $creature, $creature, $creature, $creature, $creature]);
                        $instanceLoot = $instanceStmt->fetch(PDO::FETCH_ASSOC);
                        if ($instanceLoot) {
                            $templateStmt = $armoryPdo->prepare('SELECT * FROM `armory_instance_template` WHERE `id` = ? LIMIT 1');
                            $templateStmt->execute([(int)$instanceLoot['instance_id']]);
                            $instanceInfo = $templateStmt->fetch(PDO::FETCH_ASSOC);
                            if ($instanceInfo) {
                                $suffix = (((int)$instanceInfo['expansion'] < 2) || !(int)$instanceInfo['raid']) ? '' : ((int)$instanceInfo['is_heroic'] ? ' (H)' : '');
                                return trim($instanceLoot['name_en_gb'] . ' - ' . $instanceInfo['name_en_gb'] . $suffix);
                            }
                        }

                        $creatureNameStmt = $worldPdo->prepare('SELECT `Name` FROM `creature_template` WHERE `entry` = ? LIMIT 1');
                        $creatureNameStmt->execute([$creature]);
                        $creatureName = $creatureNameStmt->fetchColumn();
                        if ($creatureName) {
                            return (string)$creatureName;
                        }
                    }
                }
            }

            return 'Dropped Item';
        }

        $questRewardStmt = $worldPdo->prepare('SELECT `entry` FROM `quest_template` WHERE `RewItemId1` = ? OR `RewItemId2` = ? OR `RewItemId3` = ? OR `RewItemId4` = ? LIMIT 1');
        $questRewardStmt->execute([$itemId, $itemId, $itemId, $itemId]);
        if ($questRewardStmt->fetchColumn()) {
            return 'Quest Reward';
        }

        return 'Created';
    }
}

if (!function_exists('spp_modern_item_random_properties')) {
    function spp_modern_item_random_properties(PDO $worldPdo, PDO $armoryPdo, array $itemRow) {
        $randomGroupId = (int)($itemRow['RandomProperty'] ?? 0);
        if ($randomGroupId <= 0) {
            return [];
        }

        $stmt = $worldPdo->prepare('SELECT `ench`, `chance` FROM `item_enchantment_template` WHERE `entry` = ? ORDER BY `chance` DESC, `ench` ASC');
        $stmt->execute([$randomGroupId]);
        $templateRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$templateRows) {
            return [];
        }

        $propertyIds = [];
        foreach ($templateRows as $templateRow) {
            $propertyId = (int)($templateRow['ench'] ?? 0);
            if ($propertyId > 0) {
                $propertyIds[] = $propertyId;
            }
        }
        $propertyIds = array_values(array_unique($propertyIds));
        if (!$propertyIds) {
            return [];
        }

        $propertyPlaceholders = implode(',', array_fill(0, count($propertyIds), '?'));
        $propertyStmt = $armoryPdo->prepare('SELECT * FROM `dbc_itemrandomproperties` WHERE `id` IN (' . $propertyPlaceholders . ')');
        $propertyStmt->execute($propertyIds);
        $propertyMap = [];
        $enchantIds = [];
        foreach ($propertyStmt->fetchAll(PDO::FETCH_ASSOC) as $propertyRow) {
            $propertyId = (int)($propertyRow['id'] ?? 0);
            $propertyMap[$propertyId] = $propertyRow;
            for ($i = 1; $i <= 3; $i++) {
                $enchantId = (int)($propertyRow['ref_spellitemenchantment_' . $i] ?? 0);
                if ($enchantId > 0) {
                    $enchantIds[] = $enchantId;
                }
            }
        }

        $enchantMap = [];
        $enchantIds = array_values(array_unique($enchantIds));
        if ($enchantIds) {
            $enchantPlaceholders = implode(',', array_fill(0, count($enchantIds), '?'));
            $enchantStmt = $armoryPdo->prepare('SELECT `id`, `name` FROM `dbc_spellitemenchantment` WHERE `id` IN (' . $enchantPlaceholders . ')');
            $enchantStmt->execute($enchantIds);
            foreach ($enchantStmt->fetchAll(PDO::FETCH_ASSOC) as $enchantRow) {
                $enchantMap[(int)$enchantRow['id']] = trim((string)($enchantRow['name'] ?? ''));
            }
        }

        $results = [];
        foreach ($templateRows as $templateRow) {
            $propertyId = (int)($templateRow['ench'] ?? 0);
            if ($propertyId <= 0 || empty($propertyMap[$propertyId])) {
                continue;
            }
            $propertyRow = $propertyMap[$propertyId];
            $statLines = [];
            for ($i = 1; $i <= 3; $i++) {
                $enchantId = (int)($propertyRow['ref_spellitemenchantment_' . $i] ?? 0);
                $enchantName = $enchantId > 0 ? trim((string)($enchantMap[$enchantId] ?? '')) : '';
                if ($enchantName !== '') {
                    $statLines[] = $enchantName;
                }
            }
            $results[] = [
                'id' => $propertyId,
                'name' => trim((string)($propertyRow['name'] ?? ('Property #' . $propertyId))),
                'chance' => (float)($templateRow['chance'] ?? 0),
                'stats' => $statLines,
            ];
        }

        $grouped = [];
        foreach ($results as $result) {
            $name = (string)$result['name'];
            if (!isset($grouped[$name])) {
                $grouped[$name] = [
                    'id' => (int)$result['id'],
                    'name' => $name,
                    'chance' => 0.0,
                    'stat_map' => [],
                    'stat_order' => [],
                ];
            }

            $grouped[$name]['chance'] += (float)$result['chance'];

            foreach ((array)$result['stats'] as $statLine) {
                $statLine = trim((string)$statLine);
                if ($statLine === '') {
                    continue;
                }

                if (preg_match('/^\+(\d+)\s+(.+)$/', $statLine, $matches)) {
                    $amount = (int)$matches[1];
                    $label = trim((string)$matches[2]);
                    if (!isset($grouped[$name]['stat_map'][$label])) {
                        $grouped[$name]['stat_map'][$label] = [
                            'min' => $amount,
                            'max' => $amount,
                        ];
                        $grouped[$name]['stat_order'][] = $label;
                    } else {
                        $grouped[$name]['stat_map'][$label]['min'] = min($grouped[$name]['stat_map'][$label]['min'], $amount);
                        $grouped[$name]['stat_map'][$label]['max'] = max($grouped[$name]['stat_map'][$label]['max'], $amount);
                    }
                } else {
                    if (!isset($grouped[$name]['stat_map'][$statLine])) {
                        $grouped[$name]['stat_map'][$statLine] = [
                            'min' => null,
                            'max' => null,
                        ];
                        $grouped[$name]['stat_order'][] = $statLine;
                    }
                }
            }
        }

        $collapsed = [];
        foreach ($grouped as $group) {
            $collapsedStats = [];
            foreach ($group['stat_order'] as $label) {
                $range = $group['stat_map'][$label];
                if ($range['min'] === null || $range['max'] === null) {
                    $collapsedStats[] = spp_modern_item_random_stat_label($label);
                } elseif ($range['min'] === $range['max']) {
                    $collapsedStats[] = '+' . $range['min'] . ' ' . spp_modern_item_random_stat_label($label);
                } else {
                    $collapsedStats[] = '+' . $range['min'] . '-' . $range['max'] . ' ' . spp_modern_item_random_stat_label($label);
                }
            }

            $collapsed[] = [
                'id' => (int)$group['id'],
                'name' => (string)$group['name'],
                'chance' => (float)$group['chance'],
                'stats' => $collapsedStats,
            ];
        }

        usort($collapsed, function ($a, $b) {
            if ((float)$a['chance'] === (float)$b['chance']) {
                return strcmp((string)$a['name'], (string)$b['name']);
            }
            return ((float)$b['chance'] <=> (float)$a['chance']);
        });

        return $collapsed;
    }
}

if (!function_exists('spp_modern_item_random_stat_label')) {
    function spp_modern_item_random_stat_label($label) {
        $label = trim((string)$label);
        $map = [
            'Strength' => 'Str',
            'Agility' => 'Agi',
            'Stamina' => 'Stm',
            'Intellect' => 'Int',
            'Spirit' => 'Spr',
            'Defense' => 'Def',
            'Healing Spells' => 'Healing',
            'Ranged Attack Power' => 'RAP',
            'Arcane Spell Damage' => 'Arcane',
            'Fire Spell Damage' => 'Fire',
            'Frost Spell Damage' => 'Frost',
            'Shadow Spell Damage' => 'Shadow',
            'Nature Spell Damage' => 'Nature',
            'Holy Spell Damage' => 'Holy',
        ];
        return $map[$label] ?? $label;
    }
}

if (!function_exists('slot_order')) {
    function slot_order($inventoryType) {
        switch ((int)$inventoryType) {
            case 1: return 1;
            case 2: return 2;
            case 3: return 3;
            case 16: return 4;
            case 5: return 5;
            case 20: return 6;
            case 4: return 7;
            case 19: return 8;
            case 9: return 9;
            case 10: return 10;
            case 6: return 11;
            case 7: return 12;
            case 8: return 13;
            case 11: return 14;
            case 12: return 15;
            case 13: return 16;
            case 17: return 17;
            case 14: return 18;
            case 21: return 19;
            case 22: return 20;
            case 15: return 21;
            case 25: return 22;
            case 26: return 23;
            case 23: return 24;
            case 24: return 25;
            case 27: return 26;
            case 28: return 27;
            default: return 99;
        }
    }
}

if (!function_exists('spp_item_set_spell_duration_seconds')) {
    function spp_item_set_spell_duration_seconds(int $realmId, int $durationId): int
    {
        static $cache = [];

        $realmId = max(1, $realmId);
        $durationId = (int)$durationId;
        if ($durationId <= 0) {
            return 0;
        }

        $cacheKey = $realmId . ':' . $durationId;
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        try {
            $armoryPdo = spp_get_pdo('armory', $realmId);
            $stmt = $armoryPdo->prepare('SELECT `duration1`, `duration2` FROM `dbc_spellduration` WHERE `id` = ? LIMIT 1');
            $stmt->execute([$durationId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$row) {
                $cache[$cacheKey] = 0;
                return 0;
            }

            $cache[$cacheKey] = (int)(max((int)($row['duration1'] ?? 0), (int)($row['duration2'] ?? 0)) / 1000);
            return $cache[$cacheKey];
        } catch (Throwable $e) {
            $cache[$cacheKey] = 0;
            return 0;
        }
    }
}

if (!function_exists('spp_item_set_spell_radius_text')) {
    function spp_item_set_spell_radius_text(int $realmId, int $radiusId): string
    {
        static $cache = [];

        $realmId = max(1, $realmId);
        $radiusId = (int)$radiusId;
        if ($radiusId <= 0) {
            return '0';
        }

        $cacheKey = $realmId . ':' . $radiusId;
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        try {
            $armoryPdo = spp_get_pdo('armory', $realmId);
            $stmt = $armoryPdo->prepare('SELECT `radius1` FROM `dbc_spellradius` WHERE `id` = ? LIMIT 1');
            $stmt->execute([$radiusId]);
            $value = (float)$stmt->fetchColumn();
            $formatted = number_format($value, 1, '.', '');
            $cache[$cacheKey] = rtrim(rtrim($formatted, '0'), '.') ?: '0';
            return $cache[$cacheKey];
        } catch (Throwable $e) {
            $cache[$cacheKey] = '0';
            return $cache[$cacheKey];
        }
    }
}

if (!function_exists('spp_item_set_bonus_description')) {
    function spp_item_set_bonus_description(int $realmId, array $spell): string
    {
        return spp_shared_item_set_bonus_description($spell, $realmId);
    }
}

if (!function_exists('get_itemset_data')) {
    function get_itemset_data(int $setId): array
    {
        return spp_shared_get_itemset_data($setId);
    }
}

if (!function_exists('spp_item_set_detail_payload')) {
    function spp_item_set_detail_payload($realmId, $setName, $section, $selectedClass)
    {
        $realmId = (int)$realmId;
        $section = strtolower(trim((string)$section));
        if (!in_array($section, ['all', 'misc', 'world', 'pvp'], true)) {
            $section = 'all';
        }

        $catalogEntry = function_exists('spp_itemset_catalog_find')
            ? (array)spp_itemset_catalog_find($realmId, (string)$setName)
            : [];
        $setId = (int)($catalogEntry['id'] ?? 0);
        $setData = $setId > 0 && function_exists('get_itemset_data') ? (array)get_itemset_data($setId) : [];

        $classOptions = ['all'];
        $catalogNotes = (array)($catalogEntry['notes'] ?? []);
        foreach ($catalogNotes as $note) {
            $className = trim((string)($note['class_name'] ?? ''));
            if ($className !== '' && !in_array($className, $classOptions, true)) {
                $classOptions[] = $className;
            }
        }

        $selectedClass = trim((string)$selectedClass);
        if ($selectedClass === '' || !in_array($selectedClass, $classOptions, true)) {
            $selectedClass = 'all';
        }

        if (empty($setData)) {
            return [
                'section' => $section,
                'class' => $selectedClass,
                'class_options' => $classOptions,
                'error' => 'That set could not be found in the current item-set catalog.',
                'data' => [],
                'blurb_title' => '',
                'blurb_text' => '',
            ];
        }

        if (empty($catalogEntry)) {
            $catalogEntry = [
                'id' => $setId,
                'name' => (string)($setData['name'] ?? $setName),
                'notes' => [],
            ];
        }

        $dbNote = function_exists('spp_itemset_catalog_select_note')
            ? (array)spp_itemset_catalog_select_note($catalogNotes, $section, $selectedClass)
            : [];

        if (empty($dbNote) && function_exists('spp_itemset_catalog_select_note')) {
            $dbNote = (array)spp_itemset_catalog_select_note($catalogNotes, 'all', 'all');
        }

        $resolvedSection = trim((string)($dbNote['section'] ?? '')) !== ''
            ? strtolower((string)$dbNote['section'])
            : ($section !== 'all' ? $section : 'all');
        $resolvedClass = trim((string)($dbNote['class_name'] ?? '')) !== ''
            ? trim((string)$dbNote['class_name'])
            : $selectedClass;

        return [
            'section' => $resolvedSection,
            'class' => $resolvedClass,
            'class_options' => $classOptions,
            'error' => '',
            'data' => $setData,
            'blurb_title' => (string)($dbNote['title'] ?? ''),
            'blurb_text' => (string)($dbNote['text'] ?? ''),
        ];
    }
}

$realmMap = $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null);
$itemDetailState = spp_item_detail_bootstrap_state($armoryConfig, $user, $realmMap, is_array($realms ?? null) ? $realms : []);

$realmId = $itemDetailState['realm_id'];
$realmLabel = $itemDetailState['realm_label'];
$itemId = $itemDetailState['item_id'];
$requestType = $itemDetailState['request_type'];
$requestedSetName = $itemDetailState['requested_set_name'];
$isSetDetailMode = $itemDetailState['is_set_detail_mode'];
$setSection = $itemDetailState['set_section'];
$setClass = $itemDetailState['set_class'];
$classNames = $itemDetailState['class_names'];
$raceNames = $itemDetailState['race_names'];
$allianceRaces = $itemDetailState['alliance_races'];
$item = $itemDetailState['item'];
$itemSet = $itemDetailState['item_set'];
$randomProperties = $itemDetailState['random_properties'];
$owners = $itemDetailState['owners'];
$upgrades = $itemDetailState['upgrades'];
$upgradePresets = $itemDetailState['upgrade_presets'];
$upgradeMode = $itemDetailState['upgrade_mode'];
$upgradeProfileId = $itemDetailState['upgrade_profile_id'];
$upgradeWeightsRaw = $itemDetailState['upgrade_weights_raw'];
$upgradeManualWeights = $itemDetailState['upgrade_manual_weights'];
$upgradeActiveWeights = $itemDetailState['upgrade_active_weights'];
$upgradeActiveProfile = $itemDetailState['upgrade_active_profile'];
$upgradeAvailablePresets = $itemDetailState['upgrade_available_presets'];
$upgradeCurrentStats = $itemDetailState['upgrade_current_stats'];
$upgradeCurrentScore = $itemDetailState['upgrade_current_score'];
$upgradeNotice = $itemDetailState['upgrade_notice'];
$upgradeFallbackUrl = $itemDetailState['upgrade_fallback_url'];
$upgradeClearUrl = $itemDetailState['upgrade_clear_url'];
$commentTopic = $itemDetailState['comment_topic'];
$commentPosts = $itemDetailState['comment_posts'];
$commentError = $itemDetailState['comment_error'];
$commentSuccess = $itemDetailState['comment_success'];
$commentPosterOptions = $itemDetailState['comment_poster_options'];
$commentPosterSelection = $itemDetailState['comment_poster_selection'];
$itemCommentForumContext = $itemDetailState['item_comment_forum_context'];
$setDetail = $itemDetailState['set_detail'];
$setCommentForumContext = $itemDetailState['set_comment_forum_context'];
$commentSubjectLabel = $itemDetailState['comment_subject_label'];
$commentEmptyCopy = $itemDetailState['comment_empty_copy'];
$commentLoginCopy = $itemDetailState['comment_login_copy'];
$commentNoPosterCopy = $itemDetailState['comment_no_poster_copy'];
$pageError = $itemDetailState['page_error'];
$legacyRealmName = $itemDetailState['legacy_realm_name'];
$itemRealmOptions = $itemDetailState['item_realm_options'];
$itemRealmSwitchParams = $itemDetailState['item_realm_switch_params'];

if ($isSetDetailMode && (!is_array($realmMap) || !isset($realmMap[$realmId]))) {
    $pageError = 'The requested realm could not be loaded.';
} elseif ($isSetDetailMode) {
    $setDetail = spp_item_set_detail_payload($realmId, $requestedSetName, $setSection, $setClass);
    $setSection = (string)($setDetail['section'] ?? $setSection);
    $setClass = (string)($setDetail['class'] ?? $setClass);

    if (($setDetail['error'] ?? '') !== '') {
        $pageError = (string)$setDetail['error'];
    } else {
        $commentSubjectLabel = 'set';
        $commentEmptyCopy = 'No comments yet for this set.';
        $commentLoginCopy = 'Log in to join this set discussion.';
        $commentNoPosterCopy = 'You need a character on this realm to comment in this set discussion.';

        $setCommentForumId = (int)($setCommentForumContext['forum_id'] ?? 0);
        $setCommentForumRealmId = (int)($setCommentForumContext['realm_id'] ?? 1);
        $setTopicTitle = '[' . $realmLabel . '][Set #' . (int)($setDetail['data']['id'] ?? 0) . '] ' . trim((string)($setDetail['data']['name'] ?? $requestedSetName));

        if ($setCommentForumId > 0 && $setTopicTitle !== '') {
            try {
                $forumPdo = spp_get_pdo('realmd', $setCommentForumRealmId);

                if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['item_comment_action'] ?? '') === 'submit_comment') {
                    spp_require_csrf('item_comments');
                    $commentBody = trim((string)($_POST['comment_body'] ?? ''));

                    if ((int)($user['id'] ?? 0) <= 0) {
                        $commentError = 'You must be logged in to post set comments.';
                    } elseif ($commentBody === '') {
                        $commentError = 'Comment text cannot be empty.';
                    } elseif (mb_strlen($commentBody) < 3) {
                        $commentError = 'Comment text must be at least 3 characters.';
                    } elseif (!isset($commentPosterOptions[$commentPosterSelection])) {
                        $commentError = 'Choose a character from this realm to post as.';
                    } else {
                        $posterOption = $commentPosterOptions[$commentPosterSelection];
                        $posterName = (string)($posterOption['poster'] ?? ($user['username'] ?? $user['login'] ?? 'Adventurer'));
                        $posterCharacterId = !empty($posterOption['character_id']) ? (int)$posterOption['character_id'] : 0;
                        $posterIdentityId = !empty($posterOption['identity_id']) ? (int)$posterOption['identity_id'] : null;

                        try {
                            $forumPdo->beginTransaction();

                            $topicStmt = $forumPdo->prepare('SELECT * FROM `f_topics` WHERE `forum_id` = ? AND `topic_name` = ? LIMIT 1');
                            $topicStmt->execute([$setCommentForumId, $setTopicTitle]);
                            $commentTopic = $topicStmt->fetch(PDO::FETCH_ASSOC) ?: null;
                            $commentTopicHasPosts = false;

                            if ($commentTopic) {
                                $postCountStmt = $forumPdo->prepare('SELECT COUNT(*) FROM `f_posts` WHERE `topic_id` = ?');
                                $postCountStmt->execute([(int)$commentTopic['topic_id']]);
                                $commentTopicHasPosts = ((int)$postCountStmt->fetchColumn() > 0);
                            }

                            if (!$commentTopic) {
                                $topicPostTime = time();
                                $newTopicStmt = $forumPdo->prepare(
                                    "INSERT INTO f_topics (topic_poster, topic_poster_id, topic_poster_identity_id, topic_name, topic_posted, forum_id, num_replies)
                                     VALUES (?, ?, ?, ?, ?, ?, 0)"
                                );
                                $newTopicStmt->execute([
                                    $posterName,
                                    (int)$user['id'],
                                    $posterIdentityId,
                                    $setTopicTitle,
                                    $topicPostTime,
                                    $setCommentForumId,
                                ]);
                                $newTopicId = (int)$forumPdo->lastInsertId();

                                $newPostStmt = $forumPdo->prepare(
                                    "INSERT INTO f_posts (poster, poster_id, poster_character_id, poster_identity_id, poster_ip, message, posted, topic_id)
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                                );
                                $newPostStmt->execute([
                                    $posterName,
                                    (int)$user['id'],
                                    $posterCharacterId,
                                    $posterIdentityId,
                                    (string)($user['ip'] ?? ''),
                                    $commentBody,
                                    $topicPostTime,
                                    $newTopicId,
                                ]);
                                $newPostId = (int)$forumPdo->lastInsertId();

                                $topicUpdateStmt = $forumPdo->prepare("UPDATE f_topics SET last_post = ?, last_post_id = ?, last_poster = ? WHERE topic_id = ?");
                                $topicUpdateStmt->execute([$topicPostTime, $newPostId, $posterName, $newTopicId]);

                                $forumUpdateStmt = $forumPdo->prepare("UPDATE f_forums SET num_topics = num_topics + 1, num_posts = num_posts + 1, last_topic_id = ? WHERE forum_id = ?");
                                $forumUpdateStmt->execute([$newTopicId, $setCommentForumId]);
                            } elseif (!$commentTopicHasPosts) {
                                $topicPostTime = time();
                                $firstPostStmt = $forumPdo->prepare(
                                    "INSERT INTO f_posts (poster, poster_id, poster_character_id, poster_identity_id, poster_ip, message, posted, topic_id)
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                                );
                                $firstPostStmt->execute([
                                    $posterName,
                                    (int)$user['id'],
                                    $posterCharacterId,
                                    $posterIdentityId,
                                    (string)($user['ip'] ?? ''),
                                    $commentBody,
                                    $topicPostTime,
                                    (int)$commentTopic['topic_id'],
                                ]);
                                $newPostId = (int)$forumPdo->lastInsertId();

                                $topicUpdateStmt = $forumPdo->prepare("UPDATE f_topics SET topic_poster = ?, topic_poster_id = ?, topic_poster_identity_id = ?, topic_posted = ?, last_post = ?, last_post_id = ?, last_poster = ?, num_replies = 0 WHERE topic_id = ?");
                                $topicUpdateStmt->execute([
                                    $posterName,
                                    (int)$user['id'],
                                    $posterIdentityId,
                                    $topicPostTime,
                                    $topicPostTime,
                                    $newPostId,
                                    $posterName,
                                    (int)$commentTopic['topic_id'],
                                ]);

                                $forumUpdateStmt = $forumPdo->prepare("UPDATE f_forums SET num_posts = num_posts + 1, last_topic_id = ? WHERE forum_id = ?");
                                $forumUpdateStmt->execute([(int)$commentTopic['topic_id'], $setCommentForumId]);
                            } else {
                                $topicPostTime = time();
                                $replyStmt = $forumPdo->prepare(
                                    "INSERT INTO f_posts (poster, poster_id, poster_character_id, poster_identity_id, poster_ip, message, posted, topic_id)
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                                );
                                $replyStmt->execute([
                                    $posterName,
                                    (int)$user['id'],
                                    $posterCharacterId,
                                    $posterIdentityId,
                                    (string)($user['ip'] ?? ''),
                                    $commentBody,
                                    $topicPostTime,
                                    (int)$commentTopic['topic_id'],
                                ]);
                                $newPostId = (int)$forumPdo->lastInsertId();

                                $topicUpdateStmt = $forumPdo->prepare("UPDATE f_topics SET last_post = ?, last_post_id = ?, last_poster = ?, num_replies = num_replies + 1 WHERE topic_id = ?");
                                $topicUpdateStmt->execute([$topicPostTime, $newPostId, $posterName, (int)$commentTopic['topic_id']]);

                                $forumUpdateStmt = $forumPdo->prepare("UPDATE f_forums SET num_posts = num_posts + 1, last_topic_id = ? WHERE forum_id = ?");
                                $forumUpdateStmt->execute([(int)$commentTopic['topic_id'], $setCommentForumId]);
                            }

                            $forumPdo->commit();
                            spp_item_detail_post_redirect(spp_item_detail_comment_redirect_url(['comment_posted' => 1]));
                        } catch (Throwable $e) {
                            if ($forumPdo->inTransaction()) {
                                $forumPdo->rollBack();
                            }
                            error_log('[set comments] Failed posting set comment: ' . $e->getMessage());
                            $commentError = 'Unable to post set comment right now.';
                        }
                    }
                }

                $topicStmt = $forumPdo->prepare('SELECT * FROM `f_topics` WHERE `forum_id` = ? AND `topic_name` = ? LIMIT 1');
                $topicStmt->execute([$setCommentForumId, $setTopicTitle]);
                $commentTopic = $topicStmt->fetch(PDO::FETCH_ASSOC) ?: null;

                if ($commentTopic) {
                    $postStmt = $forumPdo->prepare('SELECT * FROM `f_posts` WHERE `topic_id` = ? ORDER BY `posted` ASC LIMIT 25');
                    $postStmt->execute([(int)$commentTopic['topic_id']]);
                    $commentPosts = $postStmt->fetchAll(PDO::FETCH_ASSOC);
                    $commentPosts = spp_item_detail_enrich_comment_posts($commentPosts, (int)$realmId, $classNames);
                }
            } catch (Throwable $e) {
                error_log('[set comments] Failed loading set discussion: ' . $e->getMessage());
            }
        }
    }
} elseif ($itemId <= 0) {
    $pageError = 'No item was selected.';
} elseif (!is_array($realmMap) || !isset($realmMap[$realmId])) {
    $pageError = 'The requested realm could not be loaded.';
} else {
    $itemDetailData = spp_item_detail_load_core_data([
        'realm_id' => $realmId,
        'item_id' => $itemId,
        'config' => $config,
        'class_names' => $classNames,
        'race_names' => $raceNames,
        'alliance_races' => $allianceRaces,
        'upgrade_presets' => $upgradePresets,
        'upgrade_mode' => $upgradeMode,
        'upgrade_profile_id' => $upgradeProfileId,
        'upgrade_weights_raw' => $upgradeWeightsRaw,
    ]);

    $pageError = (string)($itemDetailData['page_error'] ?? '');
    $item = $itemDetailData['item'] ?? null;
    $itemSet = $itemDetailData['item_set'] ?? null;
    $randomProperties = $itemDetailData['random_properties'] ?? [];
    $owners = $itemDetailData['owners'] ?? [];
    $upgrades = $itemDetailData['upgrades'] ?? [];
    $upgradeMode = (string)($itemDetailData['upgrade_mode'] ?? $upgradeMode);
    $upgradeManualWeights = $itemDetailData['upgrade_manual_weights'] ?? [];
    $upgradeActiveWeights = $itemDetailData['upgrade_active_weights'] ?? [];
    $upgradeActiveProfile = $itemDetailData['upgrade_active_profile'] ?? null;
    $upgradeAvailablePresets = $itemDetailData['upgrade_available_presets'] ?? [];
    $upgradeCurrentStats = $itemDetailData['upgrade_current_stats'] ?? [];
    $upgradeCurrentScore = $itemDetailData['upgrade_current_score'] ?? null;
    $upgradeNotice = (string)($itemDetailData['upgrade_notice'] ?? $upgradeNotice);
    $iconName = (string)($itemDetailData['icon_name'] ?? '');

    if ($item && $pageError === '') {
        $itemCommentState = spp_item_detail_sync_comments([
            'realm_id' => $realmId,
            'realm_label' => $realmLabel,
            'item_id' => $itemId,
            'item' => $item,
            'user' => $user,
            'class_names' => $classNames,
            'comment_poster_options' => $commentPosterOptions,
            'comment_poster_selection' => $commentPosterSelection,
            'item_comment_forum_context' => $itemCommentForumContext,
        ]);

        $commentTopic = $itemCommentState['comment_topic'] ?? null;
        $commentPosts = $itemCommentState['comment_posts'] ?? [];
        if (($itemCommentState['comment_error'] ?? '') !== '') {
            $commentError = (string)$itemCommentState['comment_error'];
        }
    }
    }
$searchBackUrl = 'index.php?n=server&sub=items&realm=' . (int)$realmId;
if (!empty($_GET['search'])) $searchBackUrl .= '&search=' . urlencode((string)$_GET['search']);
if (!empty($_GET['type'])) $searchBackUrl .= '&type=' . urlencode((string)$_GET['type']);
if (!empty($_GET['quality'])) $searchBackUrl .= '&quality=' . urlencode((string)$_GET['quality']);
if (!empty($_GET['item_class'])) $searchBackUrl .= '&item_class=' . urlencode((string)$_GET['item_class']);
if (!empty($_GET['slot'])) $searchBackUrl .= '&slot=' . urlencode((string)$_GET['slot']);
if (!empty($_GET['min_level'])) $searchBackUrl .= '&min_level=' . urlencode((string)$_GET['min_level']);
if (!empty($_GET['max_level'])) $searchBackUrl .= '&max_level=' . urlencode((string)$_GET['max_level']);
if (!empty($_GET['p'])) $searchBackUrl .= '&p=' . max(1, (int)$_GET['p']);
if (!empty($_GET['per_page'])) $searchBackUrl .= '&per_page=' . max(1, (int)$_GET['per_page']);
if (!empty($_GET['sort'])) $searchBackUrl .= '&sort=' . urlencode((string)$_GET['sort']);
if (!empty($_GET['dir'])) $searchBackUrl .= '&dir=' . urlencode((string)$_GET['dir']);
if (!empty($_GET['set_section'])) $searchBackUrl .= '&set_section=' . urlencode((string)$_GET['set_section']);
if (!empty($_GET['set_class'])) $searchBackUrl .= '&set_class=' . urlencode((string)$_GET['set_class']);

$slotLink = '';
$typeLink = '';
$sourceLink = '';
$nameLink = '';
$qualityLink = '';
$levelLink = '';
$requiredLevelLink = '';
$iconLink = '';
if ($item) {
    $baseFilters = [
        'type' => 'items',
        'search' => '',
        'quality' => '',
        'class' => '',
        'slot' => '',
        'min_level' => '',
        'max_level' => '',
        'sort' => 'featured',
        'dir' => 'DESC',
        'p' => 1,
        'per_page' => 24,
    ];

    $nameLink = spp_item_database_url($realmId, $baseFilters, ['search' => $item['name']]);
    $qualityLink = spp_item_database_url($realmId, $baseFilters, ['quality' => (string)(int)$item['quality']]);
    $levelLink = spp_item_database_url($realmId, $baseFilters, ['min_level' => (string)(int)$item['level'], 'max_level' => (string)(int)$item['level']]);
    if ((int)$item['required_level'] > 0) {
        $requiredLevelLink = spp_item_database_url($realmId, $baseFilters, ['min_level' => (string)(int)$item['required_level'], 'max_level' => (string)(int)$item['required_level']]);
    }
    if (!empty($iconName)) {
        $iconLink = spp_item_database_url($realmId, $baseFilters, ['type' => 'icons', 'icon' => $iconName, 'search' => '']);
    }

    if ((int)$item['inventory_type'] > 0) {
        $slotLink = spp_item_database_url($realmId, $baseFilters, ['slot' => (string)(int)$item['inventory_type']]);
    }
    if ((int)$item['item_class_id'] >= 0) {
        $typeLink = spp_item_database_url($realmId, $baseFilters, ['item_class' => (string)(int)$item['item_class_id']]);
    }

    $sourceSearch = spp_modern_item_source_search($item['source']);
    if ($sourceSearch !== '') {
        $sourceType = (stripos($item['source'], 'Dropped by ') === 0 || stripos($item['source'], 'Found in ') === 0) ? 'all' : 'items';
        $sourceLink = spp_item_database_url($realmId, $baseFilters, ['type' => $sourceType, 'search' => $sourceSearch]);
    }
}

if (is_array($realmMap) && !empty($realmMap)) {
    foreach ($realmMap as $candidateRealmId => $realmInfo) {
        $candidateRealmId = (int)$candidateRealmId;
        if ($candidateRealmId <= 0) {
            continue;
        }
        $itemRealmOptions[$candidateRealmId] = spp_realm_display_name($candidateRealmId, $realmMap);
    }
}

$itemRealmSwitchParams = [
    'n' => 'server',
    'sub' => 'item',
];
if ($itemId > 0) {
    $itemRealmSwitchParams['item'] = $itemId;
}
foreach (['type', 'search', 'quality', 'item_class', 'slot', 'min_level', 'max_level', 'p', 'per_page', 'sort', 'dir', 'upgrade_mode', 'upgrade_profile', 'upgrade_weights'] as $preserveKey) {
    if (isset($_GET[$preserveKey]) && $_GET[$preserveKey] !== '') {
        $itemRealmSwitchParams[$preserveKey] = (string)$_GET[$preserveKey];
    }
}
foreach (['set', 'set_section', 'set_class'] as $preserveKey) {
    if (isset($_GET[$preserveKey]) && $_GET[$preserveKey] !== '') {
        $itemRealmSwitchParams[$preserveKey] = (string)$_GET[$preserveKey];
    }
}

$upgradeWeightsValue = $upgradeWeightsRaw !== '' ? $upgradeWeightsRaw : spp_item_upgrade_encode_weights($upgradeManualWeights);
$upgradeBaseParams = [];
if ($upgradeMode !== '') {
    $upgradeBaseParams['upgrade_mode'] = $upgradeMode;
}
if ($upgradeProfileId !== '') {
    $upgradeBaseParams['upgrade_profile'] = $upgradeProfileId;
}
if ($upgradeWeightsValue !== '') {
    $upgradeBaseParams['upgrade_weights'] = $upgradeWeightsValue;
}
if (!function_exists('spp_item_detail_url')) {
    function spp_item_detail_url($realmId, $itemId, array $params = [])
    {
        $query = array_merge([
            'n' => 'server',
            'sub' => 'item',
            'realm' => (int)$realmId,
            'item' => (int)$itemId,
        ], $params);
        return 'index.php?' . http_build_query($query);
    }
}
if ($item) {
    $upgradeFallbackUrl = spp_item_detail_url($realmId, (int)$item['id'], ['upgrade_mode' => 'ilvl']);
    $upgradeClearUrl = spp_item_detail_url($realmId, (int)$item['id']);
}

if (!function_exists('spp_item_detail_render_comment_html')) {
    function spp_item_detail_render_comment_html($message)
    {
        $message = trim((string)$message);
        if ($message === '') {
            return '';
        }
        if (function_exists('bbcode')) {
            return bbcode($message, true, true, true, false);
        }
        return nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
    }
}

builddiv_start(1, 'Item Detail', 1);
?>
<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars(spp_template_asset_url('css/armory-tooltips.css'), ENT_QUOTES); ?>" />
<script src="<?php echo htmlspecialchars(spp_template_asset_url('js/spp.async.js'), ENT_QUOTES); ?>"></script>
<script src="<?php echo htmlspecialchars(spp_template_asset_url('js/item-tooltips.js'), ENT_QUOTES); ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var tabButtons = document.querySelectorAll('[data-item-tab-target]');
  if (!tabButtons.length) return;

  function activateTab(tabId) {
    tabButtons.forEach(function (button) {
      button.classList.toggle('is-active', button.getAttribute('data-item-tab-target') === tabId);
    });
    document.querySelectorAll('[data-item-tab-panel]').forEach(function (panel) {
      panel.classList.toggle('is-active', panel.getAttribute('data-item-tab-panel') === tabId);
    });
  }

  tabButtons.forEach(function (button) {
    button.addEventListener('click', function () {
      activateTab(button.getAttribute('data-item-tab-target'));
    });
  });

  activateTab(<?php echo $isSetDetailMode ? "'comments'" : "'upgrades'"; ?>);
});
</script>

<div class="item-detail-page">
  <?php if ($pageError !== ''): ?>
    <div class="item-detail-error"><?php echo htmlspecialchars($pageError); ?></div>
  <?php elseif ($isSetDetailMode && $setDetail && !empty($setDetail['data'])): ?>
    <?php
      $setData = (array)$setDetail['data'];
      $setItems = (array)($setData['items'] ?? []);
      $setBonuses = (array)($setData['bonuses'] ?? []);
      $setTitle = trim((string)($setDetail['blurb_title'] ?? '')) !== '' ? (string)$setDetail['blurb_title'] : (string)($setData['name'] ?? $requestedSetName);
      $setIcon = !empty($setItems[0]['icon']) ? spp_modern_item_icon_url((string)$setItems[0]['icon']) : spp_modern_item_icon_url('inv_chest_chain_10');
      $setBackLabel = ($setSectionOptions[$setSection] ?? 'Item Sets');
    ?>
    <section class="item-detail-hero">
      <div class="item-detail-hero-main">
        <div class="item-detail-head">
          <img class="item-detail-icon" src="<?php echo htmlspecialchars($setIcon); ?>" alt="">
          <div class="item-detail-hero-copy">
            <p class="item-detail-eyebrow"><?php echo htmlspecialchars($realmLabel); ?> Set Detail</p>
            <h1 class="item-detail-title"><?php echo htmlspecialchars((string)($setData['name'] ?? $requestedSetName)); ?></h1>
            <p class="item-detail-subtitle"><?php echo htmlspecialchars(($setSectionOptions[$setSection] ?? 'Item Sets') . ' | ' . $setClass); ?></p>
          </div>
        </div>
        <?php if (!empty($setDetail['blurb_text'])): ?>
          <div class="item-detail-description item-detail-description--set"><?php echo $setDetail['blurb_text']; ?></div>
        <?php endif; ?>
      </div>
      <div class="item-detail-meta">
        <div class="item-detail-card"><span class="item-detail-card-label">Section</span><div class="item-detail-card-value"><?php echo htmlspecialchars($setSectionOptions[$setSection] ?? 'Item Sets'); ?></div></div>
        <div class="item-detail-card"><span class="item-detail-card-label">Class</span><div class="item-detail-card-value"><?php echo htmlspecialchars($setClass); ?></div></div>
        <div class="item-detail-card"><span class="item-detail-card-label">Pieces</span><div class="item-detail-card-value"><?php echo count($setItems); ?></div></div>
        <div class="item-detail-card"><span class="item-detail-card-label">Bonuses</span><div class="item-detail-card-value"><?php echo count($setBonuses); ?></div></div>
        <div class="item-detail-card item-detail-card--action item-detail-card--span-2">
          <a class="item-detail-link" href="<?php echo htmlspecialchars($searchBackUrl); ?>">Back to <?php echo htmlspecialchars($setBackLabel); ?></a>
        </div>
      </div>
    </section>

    <section class="item-detail-grid">
      <div class="item-detail-panel">
        <h2 class="item-detail-panel-title">Set Pieces</h2>
        <?php if ($setItems): ?>
          <div class="item-set-piece-list item-set-piece-list--with-icons">
            <?php foreach ($setItems as $setPiece): ?>
              <a class="item-set-piece item-set-piece--full" href="<?php echo htmlspecialchars(spp_item_detail_url((int)$realmId, (int)($setPiece['entry'] ?? 0), array('type' => 'items', 'search' => (string)($_GET['search'] ?? ''), 'quality' => (string)($_GET['quality'] ?? ''), 'item_class' => (string)($_GET['item_class'] ?? ''), 'slot' => (string)($_GET['slot'] ?? ''), 'min_level' => (string)($_GET['min_level'] ?? ''), 'max_level' => (string)($_GET['max_level'] ?? ''), 'p' => (string)($_GET['p'] ?? 1), 'per_page' => (string)($_GET['per_page'] ?? 25), 'sort' => (string)($_GET['sort'] ?? 'featured'), 'dir' => (string)($_GET['dir'] ?? 'DESC')))); ?>" onmousemove="modernMoveTooltip(event)" onmouseover="modernRequestTooltip(event, <?php echo (int)($setPiece['entry'] ?? 0); ?>, <?php echo (int)$realmId; ?>)" onmouseout="modernHideTooltip()">
                <img src="<?php echo htmlspecialchars(spp_modern_item_icon_url((string)($setPiece['icon'] ?? ''))); ?>" alt="">
                <span><?php echo htmlspecialchars((string)($setPiece['name'] ?? 'Unknown Piece')); ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="item-detail-tooltip-loading">No set pieces were found for this set.</div>
        <?php endif; ?>
      </div>

      <div class="item-detail-panel">
        <h2 class="item-detail-panel-title">Set Bonuses</h2>
        <?php if ($setBonuses): ?>
          <div class="item-set-bonus-list">
            <?php foreach ($setBonuses as $bonus): ?>
              <div class="item-set-bonus">
                <span class="item-set-bonus-pieces">(<?php echo (int)($bonus['pieces'] ?? 0); ?> pieces)</span>
                <span><?php echo htmlspecialchars((string)($bonus['resolved_desc'] ?? ($bonus['desc'] ?? ($bonus['description'] ?? ($bonus['raw_desc'] ?? ''))))); ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="item-detail-tooltip-loading">No set bonuses were found for this set.</div>
        <?php endif; ?>
      </div>
    </section>

    <section class="item-detail-panel item-tabs">
      <div class="item-tabs-nav">
        <button type="button" data-item-tab-target="comments">Comments (<?php echo count($commentPosts); ?>)</button>
      </div>

      <div class="item-tab-panel" data-item-tab-panel="comments">
        <h2 class="item-detail-panel-title">Comments</h2>
        <?php if ($commentError !== ''): ?><div class="item-comment-message is-error"><?php echo htmlspecialchars($commentError); ?></div><?php endif; ?>
        <?php if ($commentSuccess !== ''): ?><div class="item-comment-message is-success"><?php echo htmlspecialchars($commentSuccess); ?></div><?php endif; ?>

        <?php if ($commentTopic): ?>
          <div class="item-comment-meta">Discussion thread: <a class="item-detail-subtitle-link" href="<?php echo htmlspecialchars('index.php?n=forum&sub=viewtopic&realm=' . (int)($setCommentForumContext['realm_id'] ?? 1) . '&tid=' . (int)$commentTopic['topic_id']); ?>"><?php echo htmlspecialchars($commentTopic['topic_name']); ?></a></div>
        <?php endif; ?>

        <?php if ($commentPosts): ?>
          <div class="item-comment-list">
            <?php foreach ($commentPosts as $commentPost): ?>
              <article class="item-comment-card">
                <div class="item-comment-header">
                  <span class="item-comment-poster-meta">
                    <span class="item-comment-poster-name<?php echo !empty($commentPost['poster_class_slug']) ? ' class-' . htmlspecialchars((string)$commentPost['poster_class_slug']) : ''; ?>"><?php echo htmlspecialchars((string)($commentPost['poster'] ?? 'Unknown')); ?></span>
                    <?php if ((int)($commentPost['poster_level'] ?? 0) > 0): ?><span class="item-comment-poster-level">Lvl - <?php echo (int)$commentPost['poster_level']; ?></span><?php endif; ?>
                  </span>
                  <span class="item-comment-meta"><?php echo date('M j, Y g:i A', (int)($commentPost['posted'] ?? time())); ?></span>
                </div>
                <div class="item-comment-body"><?php echo spp_item_detail_render_comment_html((string)($commentPost['message'] ?? '')); ?></div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="item-detail-tooltip-loading"><?php echo htmlspecialchars($commentEmptyCopy); ?></div>
        <?php endif; ?>

        <?php if ((int)($user['id'] ?? 0) > 0 && (int)($setCommentForumContext['forum_id'] ?? 0) > 0 && !empty($commentPosterOptions)): ?>
          <form method="post" class="item-comment-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(spp_csrf_token('item_comments')); ?>">
            <input type="hidden" name="item_comment_action" value="submit_comment">
            <label class="item-comment-poster">
              <span>Post As</span>
              <select name="comment_poster">
                <?php foreach ($commentPosterOptions as $optionKey => $option): ?>
                  <option value="<?php echo htmlspecialchars($optionKey); ?>"<?php echo $commentPosterSelection === $optionKey ? ' selected' : ''; ?>><?php echo htmlspecialchars((string)($option['label'] ?? $optionKey)); ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <textarea name="comment_body" placeholder="Share why this set matters, where it shines, or who should chase it..."><?php echo htmlspecialchars((string)($_POST['comment_body'] ?? '')); ?></textarea>
            <button type="submit" class="item-comment-submit">Post Comment</button>
          </form>
        <?php elseif ((int)($user['id'] ?? 0) > 0 && (int)($setCommentForumContext['forum_id'] ?? 0) > 0): ?>
          <div class="item-detail-tooltip-loading"><?php echo htmlspecialchars($commentNoPosterCopy); ?></div>
        <?php elseif ((int)($user['id'] ?? 0) > 0): ?>
          <div class="item-detail-tooltip-loading">Set discussion is not configured yet.</div>
        <?php else: ?>
          <div class="item-detail-tooltip-loading"><?php echo htmlspecialchars($commentLoginCopy); ?></div>
        <?php endif; ?>
      </div>
    </section>
  <?php elseif ($item): ?>
    <section class="item-detail-hero">
      <div class="item-detail-hero-main">
        <div class="item-detail-head">
          <?php if ($iconLink !== ''): ?><a href="<?php echo htmlspecialchars($iconLink); ?>"><img class="item-detail-icon" src="<?php echo htmlspecialchars($item['icon']); ?>" alt=""></a><?php else: ?><img class="item-detail-icon" src="<?php echo htmlspecialchars($item['icon']); ?>" alt=""><?php endif; ?>
          <div class="item-detail-hero-copy">
            <p class="item-detail-eyebrow"><?php echo htmlspecialchars($realmLabel); ?> Item Detail</p>
            <h1 class="item-detail-title item-quality-<?php echo (int)$item['quality']; ?>"><?php if ($nameLink !== ''): ?><a href="<?php echo htmlspecialchars($nameLink); ?>"><?php echo htmlspecialchars($item['name']); ?></a><?php else: ?><?php echo htmlspecialchars($item['name']); ?><?php endif; ?></h1>
            <p class="item-detail-subtitle">
              <?php if ($slotLink !== ''): ?><a class="item-detail-subtitle-link" href="<?php echo htmlspecialchars($slotLink); ?>"><?php echo htmlspecialchars($item['slot_name']); ?></a><?php else: ?><?php echo htmlspecialchars($item['slot_name']); ?><?php endif; ?>
              |
              <?php if ($typeLink !== ''): ?><a class="item-detail-subtitle-link" href="<?php echo htmlspecialchars($typeLink); ?>"><?php echo htmlspecialchars($item['class_name']); ?></a><?php else: ?><?php echo htmlspecialchars($item['class_name']); ?><?php endif; ?>
              |
              <?php if ($sourceLink !== ''): ?><a class="item-detail-subtitle-link" href="<?php echo htmlspecialchars($sourceLink); ?>"><?php echo htmlspecialchars($item['source']); ?></a><?php else: ?><?php echo htmlspecialchars($item['source']); ?><?php endif; ?>
            </p>
          </div>
        </div>
        <?php if ($item['description'] !== ''): ?>
          <p class="item-detail-description">"<?php echo htmlspecialchars($item['description']); ?>"</p>
        <?php endif; ?>
      </div>
      <div class="item-detail-meta">
        <div class="item-detail-card"><span class="item-detail-card-label">Item Level</span><div class="item-detail-card-value"><?php if ($levelLink !== ''): ?><a href="<?php echo htmlspecialchars($levelLink); ?>"><?php echo (int)$item['level']; ?></a><?php else: ?><?php echo (int)$item['level']; ?><?php endif; ?></div></div>
        <div class="item-detail-card"><span class="item-detail-card-label">Quality</span><div class="item-detail-card-value item-quality-<?php echo (int)$item['quality']; ?>"><?php if ($qualityLink !== ''): ?><a href="<?php echo htmlspecialchars($qualityLink); ?>"><?php echo htmlspecialchars($item['quality_label']); ?></a><?php else: ?><?php echo htmlspecialchars($item['quality_label']); ?><?php endif; ?></div></div>
        <div class="item-detail-card"><span class="item-detail-card-label">Required Level</span><div class="item-detail-card-value"><?php if ($requiredLevelLink !== ''): ?><a href="<?php echo htmlspecialchars($requiredLevelLink); ?>"><?php echo (int)$item['required_level']; ?></a><?php else: ?><?php echo $item['required_level'] > 0 ? (int)$item['required_level'] : 'None'; ?><?php endif; ?></div></div>
        <div class="item-detail-card"><span class="item-detail-card-label">Owned By</span><div class="item-detail-card-value"><?php echo count($owners); ?> <?php echo htmlspecialchars($publicTerms['all_characters']); ?></div></div>
        <div class="item-detail-card item-detail-card--action item-detail-card--span-2">
          <a class="item-detail-link" href="<?php echo htmlspecialchars($searchBackUrl); ?>">Back to Item Search</a>
        </div>
      </div>
    </section>

    <section class="item-detail-grid">
      <div class="item-detail-panel">
        <h2 class="item-detail-panel-title">Quick Facts</h2>
        <div class="item-detail-facts">
          <div class="item-detail-fact"><span>Source</span><strong><?php if ($sourceLink !== ''): ?><a href="<?php echo htmlspecialchars($sourceLink); ?>"><?php echo htmlspecialchars($item['source']); ?></a><?php else: ?><?php echo htmlspecialchars($item['source']); ?><?php endif; ?></strong></div>
          <div class="item-detail-fact"><span>Slot</span><strong><?php if ($slotLink !== ''): ?><a href="<?php echo htmlspecialchars($slotLink); ?>"><?php echo htmlspecialchars($item['slot_name']); ?></a><?php else: ?><?php echo htmlspecialchars($item['slot_name']); ?><?php endif; ?></strong></div>
          <div class="item-detail-fact"><span>Type</span><strong><?php if ($typeLink !== ''): ?><a href="<?php echo htmlspecialchars($typeLink); ?>"><?php echo htmlspecialchars($item['class_name']); ?></a><?php else: ?><?php echo htmlspecialchars($item['class_name']); ?><?php endif; ?></strong></div>
          <?php if (!empty($itemSet) && !empty($itemSet['detail_url']) && !empty($itemSet['name'])): ?><div class="item-detail-fact"><span>Item Set</span><strong><a href="<?php echo htmlspecialchars((string)$itemSet['detail_url']); ?>"><?php echo htmlspecialchars((string)$itemSet['name']); ?></a></strong></div><?php endif; ?>
          <div class="item-detail-fact"><span>Buy Price</span><strong><?php echo htmlspecialchars(spp_modern_item_format_money($item['buy_price'])); ?></strong></div>
          <div class="item-detail-fact"><span>Sell Price</span><strong><?php echo htmlspecialchars(spp_modern_item_format_money($item['sell_price'])); ?></strong></div>
          <?php if ($item['max_durability'] > 0): ?><div class="item-detail-fact"><span>Durability</span><strong><?php echo (int)$item['max_durability']; ?></strong></div><?php endif; ?>
          <?php if ($item['required_skill'] > 0): ?><div class="item-detail-fact"><span>Disenchant Skill</span><strong><?php echo (int)$item['required_skill']; ?></strong></div><?php endif; ?>
        </div>
      </div>

      <div class="item-detail-panel item-detail-tooltip-shell">
        <h2 class="item-detail-panel-title">Item Stats</h2>
        <div id="item-detail-tooltip-panel" data-item-tooltip-panel="1" data-item-tooltip-panel-class="item-detail-tooltip-loading" data-item-id="<?php echo (int)$item['id']; ?>" data-realm-id="<?php echo (int)$realmId; ?>"><div class="item-detail-tooltip-loading">Loading full item details...</div></div>
      </div>
    </section>

    <?php if (!empty($randomProperties)): ?>
      <section class="item-detail-panel">
        <h2 class="item-detail-panel-title">Possible Random Properties</h2>
        <div class="item-random-grid">
          <?php foreach ($randomProperties as $property): ?>
            <article class="item-random-card">
              <div class="item-random-name"><a class="item-detail-subtitle-link" href="<?php echo htmlspecialchars(spp_item_database_url($realmId, ['type' => 'all', 'search' => '', 'quality' => '', 'class' => '', 'slot' => '', 'min_level' => '', 'max_level' => '', 'sort' => 'featured', 'dir' => 'DESC', 'p' => 1, 'per_page' => 24], ['search' => $property['name']])); ?>"><?php echo htmlspecialchars($property['name']); ?></a></div>
              <div class="item-random-chance"><?php echo rtrim(rtrim(number_format((float)$property['chance'], 2, '.', ''), '0'), '.'); ?>% chance</div>
              <?php if (!empty($property['stats'])): ?>
                <ul class="item-random-stats">
                  <?php foreach ($property['stats'] as $statLine): ?>
                    <li><a class="item-detail-subtitle-link" href="<?php echo htmlspecialchars(spp_item_database_url($realmId, ['type' => 'all', 'search' => '', 'quality' => '', 'class' => '', 'slot' => '', 'min_level' => '', 'max_level' => '', 'sort' => 'featured', 'dir' => 'DESC', 'p' => 1, 'per_page' => 24], ['search' => $statLine])); ?>"><?php echo htmlspecialchars($statLine); ?></a></li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>

    <section class="item-detail-panel item-tabs">
      <div class="item-tabs-nav">
        <button type="button" data-item-tab-target="upgrades">Upgrades (<?php echo count($upgrades); ?>)</button>
        <button type="button" data-item-tab-target="players"><?php echo htmlspecialchars($publicTerms['all_characters']); ?> (<?php echo count($owners); ?>)</button>
        <button type="button" data-item-tab-target="comments">Comments (<?php echo count($commentPosts); ?>)</button>
      </div>

      <div class="item-tab-panel" data-item-tab-panel="upgrades">
        <h2 class="item-detail-panel-title">Find Upgrades</h2>
        <div class="item-upgrade-shell">
          <div class="item-upgrade-controls">
            <form method="get" class="item-upgrade-form">
              <input type="hidden" name="n" value="server">
              <input type="hidden" name="sub" value="item">
              <input type="hidden" name="realm" value="<?php echo (int)$realmId; ?>">
              <input type="hidden" name="item" value="<?php echo (int)$item['id']; ?>">
              <div class="item-upgrade-form-row">
                <label class="item-upgrade-field">
                  <span>Upgrade Mode</span>
                  <select name="upgrade_mode">
                    <option value="preset"<?php echo $upgradeMode === 'preset' ? ' selected' : ''; ?>>Built-in preset</option>
                    <option value="manual"<?php echo $upgradeMode === 'manual' ? ' selected' : ''; ?>>Manual weights</option>
                    <option value="ilvl"<?php echo $upgradeMode === 'ilvl' ? ' selected' : ''; ?>>iLvl fallback</option>
                  </select>
                </label>
                <label class="item-upgrade-field">
                  <span>Preset</span>
                  <select name="upgrade_profile">
                    <option value="">Choose a class/spec profile</option>
                    <?php foreach ($upgradeAvailablePresets as $presetId => $preset): ?>
                      <option value="<?php echo htmlspecialchars($presetId); ?>"<?php echo $upgradeProfileId === $presetId ? ' selected' : ''; ?>><?php echo htmlspecialchars((string)$preset['label']); ?></option>
                    <?php endforeach; ?>
                  </select>
                </label>
                <div class="item-upgrade-form-actions">
                  <button type="submit" class="item-upgrade-button">Update Upgrades</button>
                  <a class="item-upgrade-fallback" href="<?php echo htmlspecialchars($upgradeFallbackUrl); ?>">Use iLvl Fallback</a>
                  <a class="item-upgrade-clear" href="<?php echo htmlspecialchars($upgradeClearUrl); ?>">Clear</a>
                </div>
              </div>
              <label class="item-upgrade-field">
                <span>Manual Weights</span>
                <input type="text" name="upgrade_weights" value="<?php echo htmlspecialchars($upgradeWeightsValue); ?>" placeholder="strength:1, crit:0.7, hit:0.9, spell_power:1">
              </label>
            </form>

            <div class="item-upgrade-summary">
              <?php if ($upgradeActiveProfile): ?><span class="item-upgrade-pill">Profile: <?php echo htmlspecialchars((string)$upgradeActiveProfile['label']); ?></span><?php endif; ?>
              <?php if ($upgradeMode === 'manual' && $upgradeManualWeights): ?><span class="item-upgrade-pill">Custom weights active</span><?php endif; ?>
              <?php if ($upgradeMode === 'ilvl'): ?><span class="item-upgrade-pill">Using simple iLvl fallback</span><?php endif; ?>
              <?php if ($upgradeCurrentScore): ?><span class="item-upgrade-pill">Current score: <?php echo htmlspecialchars(number_format((float)$upgradeCurrentScore['score'], 2)); ?></span><?php endif; ?>
            </div>

            <div class="item-upgrade-note">Weight keys: <code><?php echo htmlspecialchars(implode(', ', array_slice(array_keys(spp_item_upgrade_weight_fields()), 0, 12))); ?></code></div>
            <?php if ($upgradeNotice !== ''): ?><div class="item-detail-tooltip-loading"><?php echo htmlspecialchars($upgradeNotice); ?></div><?php endif; ?>
          </div>

          <?php if ($upgrades): ?>
            <div class="item-upgrade-grid">
              <?php foreach ($upgrades as $upgrade): ?>
                <?php
                  $upgradeParams = $upgradeBaseParams;
                  $upgradeUrl = spp_item_detail_url($realmId, (int)$upgrade['id'], $upgradeParams);
                ?>
                <article class="item-upgrade-card">
                  <a href="<?php echo htmlspecialchars($upgradeUrl); ?>"><img src="<?php echo htmlspecialchars($upgrade['icon']); ?>" alt=""></a>
                  <div>
                    <h3 class="item-upgrade-name"><a class="item-quality-<?php echo (int)$upgrade['quality']; ?>" href="<?php echo htmlspecialchars($upgradeUrl); ?>"><?php echo htmlspecialchars($upgrade['name']); ?></a></h3>
                    <div class="item-upgrade-meta"><?php echo htmlspecialchars($upgrade['slot_name']); ?> | <?php echo htmlspecialchars($upgrade['class_name']); ?> | <?php echo htmlspecialchars($upgrade['source']); ?></div>
                    <div class="item-upgrade-meta">ilvl <?php echo (int)$upgrade['level']; ?><?php if ($upgrade['required_level'] > 0): ?> | req <?php echo (int)$upgrade['required_level']; ?><?php endif; ?> | <?php echo htmlspecialchars($upgrade['quality_label']); ?></div>
                    <?php if ($upgrade['score'] !== null): ?>
                      <div class="item-upgrade-score">
                        <span><strong><?php echo htmlspecialchars(number_format((float)$upgrade['score'], 2)); ?></strong> score</span>
                        <span><?php echo ((float)$upgrade['score_delta'] >= 0 ? '+' : ''); ?><?php echo htmlspecialchars(number_format((float)$upgrade['score_delta'], 2)); ?> vs current</span>
                      </div>
                    <?php endif; ?>
                    <?php if (!empty($upgrade['top_stats'])): ?><div class="item-upgrade-note">Key stats: <?php echo htmlspecialchars(implode(' | ', $upgrade['top_stats'])); ?></div><?php endif; ?>
                    <?php if ($upgrade['description'] !== ''): ?><div class="item-upgrade-note">"<?php echo htmlspecialchars($upgrade['description']); ?>"</div><?php endif; ?>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php elseif ($upgradeMode === 'ilvl' || $upgradeActiveWeights): ?>
            <div class="item-detail-tooltip-loading">No upgrades were found for <?php echo htmlspecialchars($item['slot_name']); ?> using the current comparison mode.</div>
          <?php endif; ?>
        </div>
      </div>

      <div class="item-tab-panel" data-item-tab-panel="players">
        <h2 class="item-detail-panel-title"><?php echo htmlspecialchars($publicTerms['owned_by_all_characters']); ?></h2>
        <?php if ($owners): ?>
          <table class="item-owner-table">
            <thead>
              <tr>
                <th>Character</th>
                <th>Level</th>
                <th>Race / Class</th>
                <th>Faction</th>
                <th>Guild</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($owners as $owner): ?>
                <tr>
                  <td class="class-<?php echo htmlspecialchars($owner['class_slug']); ?>"><a class="item-owner-link" href="<?php echo htmlspecialchars('index.php?n=server&sub=character&realm=' . (int)$realmId . '&character=' . rawurlencode($owner['name'])); ?>"><?php echo htmlspecialchars($owner['name']); ?></a></td>
                  <td><?php echo (int)$owner['level']; ?></td>
                  <td><div class="item-owner-icons"><img src="<?php echo htmlspecialchars(spp_race_icon_url($owner['race'], $owner['gender'])); ?>" alt="<?php echo htmlspecialchars($owner['race_name']); ?>" title="<?php echo htmlspecialchars($owner['race_name']); ?>"><img src="<?php echo htmlspecialchars(spp_class_icon_url($owner['class'])); ?>" alt="<?php echo htmlspecialchars($owner['class_name']); ?>" title="<?php echo htmlspecialchars($owner['class_name']); ?>"></div></td>
                  <td class="item-owner-faction"><img src="<?php echo htmlspecialchars($owner['faction_icon']); ?>" alt="<?php echo htmlspecialchars($owner['faction']); ?>" title="<?php echo htmlspecialchars($owner['faction']); ?>"></td>
                  <td><?php if ($owner['guild_id'] > 0 && $owner['guild_name'] !== ''): ?><a class="item-guild-link" href="<?php echo htmlspecialchars('index.php?n=server&sub=guild&realm=' . (int)$realmId . '&guildid=' . (int)$owner['guild_id']); ?>"><?php echo htmlspecialchars($owner['guild_name']); ?></a><?php else: ?>None<?php endif; ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <div class="item-detail-tooltip-loading">No characters currently own this item on <?php echo htmlspecialchars($realmLabel); ?>.</div>
        <?php endif; ?>
      </div>

      <div class="item-tab-panel" data-item-tab-panel="comments">
        <h2 class="item-detail-panel-title">Comments</h2>
        <?php if ($commentError !== ''): ?><div class="item-comment-message is-error"><?php echo htmlspecialchars($commentError); ?></div><?php endif; ?>
        <?php if ($commentSuccess !== ''): ?><div class="item-comment-message is-success"><?php echo htmlspecialchars($commentSuccess); ?></div><?php endif; ?>

        <?php if ($commentTopic): ?>
          <div class="item-comment-meta">Discussion thread: <a class="item-detail-subtitle-link" href="<?php echo htmlspecialchars('index.php?n=forum&sub=viewtopic&realm=' . (int)($itemCommentForumContext['realm_id'] ?? 1) . '&tid=' . (int)$commentTopic['topic_id']); ?>"><?php echo htmlspecialchars($commentTopic['topic_name']); ?></a></div>
        <?php endif; ?>

        <?php if ($commentPosts): ?>
          <div class="item-comment-list">
            <?php foreach ($commentPosts as $commentPost): ?>
              <article class="item-comment-card">
                <div class="item-comment-header">
                  <span class="item-comment-poster-meta">
                    <span class="item-comment-poster-name<?php echo !empty($commentPost['poster_class_slug']) ? ' class-' . htmlspecialchars((string)$commentPost['poster_class_slug']) : ''; ?>"><?php echo htmlspecialchars((string)($commentPost['poster'] ?? 'Unknown')); ?></span>
                    <?php if ((int)($commentPost['poster_level'] ?? 0) > 0): ?><span class="item-comment-poster-level">Lvl - <?php echo (int)$commentPost['poster_level']; ?></span><?php endif; ?>
                  </span>
                  <span class="item-comment-meta"><?php echo date('M j, Y g:i A', (int)($commentPost['posted'] ?? time())); ?></span>
                </div>
                <div class="item-comment-body"><?php echo spp_item_detail_render_comment_html((string)($commentPost['message'] ?? '')); ?></div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="item-detail-tooltip-loading">No comments yet for this item.</div>
        <?php endif; ?>

        <?php if ((int)($user['id'] ?? 0) > 0 && (int)($itemCommentForumContext['forum_id'] ?? 0) > 0 && !empty($commentPosterOptions)): ?>
          <form method="post" class="item-comment-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(spp_csrf_token('item_comments')); ?>">
            <input type="hidden" name="item_comment_action" value="submit_comment">
            <label class="item-comment-poster">
              <span>Post As</span>
              <select name="comment_poster">
                <?php foreach ($commentPosterOptions as $optionKey => $option): ?>
                  <option value="<?php echo htmlspecialchars($optionKey); ?>"<?php echo $commentPosterSelection === $optionKey ? ' selected' : ''; ?>><?php echo htmlspecialchars((string)($option['label'] ?? $optionKey)); ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <textarea name="comment_body" placeholder="Share a drop story, where you found it, or what makes this item good..."><?php echo htmlspecialchars((string)($_POST['comment_body'] ?? '')); ?></textarea>
            <button type="submit" class="item-comment-submit">Post Comment</button>
          </form>
        <?php elseif ((int)($user['id'] ?? 0) > 0 && (int)($itemCommentForumContext['forum_id'] ?? 0) > 0): ?>
          <div class="item-detail-tooltip-loading">You need a character on <?php echo htmlspecialchars($realmLabel); ?> to comment in this realm’s item discussion.</div>
        <?php elseif ((int)($user['id'] ?? 0) > 0): ?>
          <div class="item-detail-tooltip-loading">Item discussion is not configured yet.</div>
        <?php else: ?>
          <div class="item-detail-tooltip-loading">Log in to join this item discussion.</div>
        <?php endif; ?>
      </div>
    </section>
  <?php endif; ?>
</div>

<?php builddiv_end(); ?>




