<?php
if (!function_exists('spp_character_table_exists')) {
function spp_character_table_exists(PDO $pdo, $tableName) {
    return spp_db_table_exists($pdo, (string)$tableName);
}

function spp_character_pick_dbc_pdo(array $candidates, $tableName) {
    foreach ($candidates as $pdo) {
        if ($pdo instanceof PDO && spp_character_table_exists($pdo, $tableName)) {
            return $pdo;
        }
    }
    return null;
}

function spp_character_talent_points(PDO $charsPdo, PDO $talentPdo, $guid, $tabId) {
    $guid = (int)$guid;
    $tabId = (int)$tabId;
    if ($guid <= 0 || $tabId <= 0) return 0;
    $points = 0;
    if (spp_character_table_exists($charsPdo, 'character_talent')) {
        $stmt = $charsPdo->prepare('SELECT `talent_id`, `current_rank` FROM `character_talent` WHERE `guid` = ?');
        $stmt->execute(array($guid));
        $talentRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($talentRows) && spp_character_table_exists($talentPdo, 'dbc_talent')) {
            $talentIds = array();
            $rankByTalent = array();
            foreach ($talentRows as $row) {
                $talentId = (int)($row['talent_id'] ?? 0);
                if ($talentId <= 0) continue;
                $talentIds[$talentId] = true;
                $rankByTalent[$talentId] = (int)($row['current_rank'] ?? 0);
            }
            if (!empty($talentIds)) {
                $placeholders = implode(',', array_fill(0, count($talentIds), '?'));
                $talentStmt = $talentPdo->prepare('SELECT `id`, `ref_talenttab` FROM `dbc_talent` WHERE `id` IN (' . $placeholders . ')');
                $talentStmt->execute(array_keys($talentIds));
                foreach ($talentStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    if ((int)($row['ref_talenttab'] ?? 0) === $tabId) {
                        $talentId = (int)$row['id'];
                        $points += ($rankByTalent[$talentId] ?? 0) + 1;
                    }
                }
                if ($points > 0) return $points;
            }
        }
    }
    if (!spp_character_table_exists($charsPdo, 'character_spell')) return 0;
    $spellRows = $charsPdo->prepare('SELECT `spell` FROM `character_spell` WHERE `guid` = ? AND `disabled` = 0');
    $spellRows->execute(array($guid));
    $learned = array();
    foreach ($spellRows->fetchAll(PDO::FETCH_ASSOC) as $row) $learned[(int)$row['spell']] = true;
    if (empty($learned)) return 0;
    if (!spp_character_table_exists($talentPdo, 'dbc_talent')) return 0;
    $stmt = $talentPdo->prepare('SELECT `rank1`, `rank2`, `rank3`, `rank4`, `rank5` FROM `dbc_talent` WHERE `ref_talenttab` = ?');
    $stmt->execute(array($tabId));
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        for ($rank = 5; $rank >= 1; $rank--) {
            $spellId = (int)($row['rank' . $rank] ?? 0);
            if ($spellId > 0 && isset($learned[$spellId])) {
                $points += $rank;
                break;
            }
        }
    }
    return $points;
}

function spp_character_reputation_tier($label) {
    $key = strtolower(trim((string)$label));
    return preg_replace('/[^a-z0-9]+/', '-', $key);
}

function spp_character_float_or_null($value) {
    if ($value === null || $value === '') return null;
    return is_numeric($value) ? (float)$value : null;
}

function spp_character_build_ilvl_chart(array $historyRows, $width = 760, $height = 220) {
    $count = count($historyRows);
    if ($count === 0) {
        return array(
            'width' => $width,
            'height' => $height,
            'points' => array(),
            'path' => '',
            'area_path' => '',
            'y_ticks' => array(),
            'x_labels' => array(),
            'min' => 0,
            'max' => 0,
        );
    }

    $paddingLeft = 52;
    $paddingRight = 18;
    $paddingTop = 16;
    $paddingBottom = 30;
    $plotWidth = max(1, $width - $paddingLeft - $paddingRight);
    $plotHeight = max(1, $height - $paddingTop - $paddingBottom);

    $values = array();
    foreach ($historyRows as $row) {
        $value = spp_character_float_or_null($row['avg_equipped_ilvl'] ?? null);
        if ($value !== null) $values[] = $value;
    }
    if (empty($values)) {
        return array(
            'width' => $width,
            'height' => $height,
            'points' => array(),
            'path' => '',
            'area_path' => '',
            'y_ticks' => array(),
            'x_labels' => array(),
            'min' => 0,
            'max' => 0,
        );
    }

    $minValue = floor(min($values));
    $maxValue = ceil(max($values));
    if ($maxValue <= $minValue) $maxValue = $minValue + 1;
    $range = $maxValue - $minValue;

    $points = array();
    $pathParts = array();
    foreach ($historyRows as $index => $row) {
        $value = spp_character_float_or_null($row['avg_equipped_ilvl'] ?? null);
        if ($value === null) continue;
        $x = $count === 1
            ? ($paddingLeft + ($plotWidth / 2))
            : ($paddingLeft + (($index / ($count - 1)) * $plotWidth));
        $y = $paddingTop + (($maxValue - $value) / $range) * $plotHeight;
        $points[] = array(
            'x' => round($x, 2),
            'y' => round($y, 2),
            'value' => $value,
            'snapshot_time' => (string)($row['snapshot_time'] ?? ''),
            'equipped_item_count' => isset($row['equipped_item_count']) ? (int)$row['equipped_item_count'] : 0,
            'level' => isset($row['level']) ? (int)$row['level'] : 0,
            'online' => !empty($row['online']),
        );
        $pathParts[] = ($index === 0 ? 'M' : 'L') . round($x, 2) . ' ' . round($y, 2);
    }

    $areaPath = '';
    if (!empty($points)) {
        $firstPoint = $points[0];
        $lastPoint = $points[count($points) - 1];
        $baselineY = $paddingTop + $plotHeight;
        $areaPath = 'M' . $firstPoint['x'] . ' ' . $baselineY . ' ' .
            implode(' ', $pathParts) . ' L' . $lastPoint['x'] . ' ' . $baselineY . ' Z';
    }

    $tickCount = min(4, max(2, $range + 1));
    $yTicks = array();
    for ($i = 0; $i < $tickCount; $i++) {
        $tickValue = $minValue + ($range * ($i / ($tickCount - 1)));
        $tickY = $paddingTop + (($maxValue - $tickValue) / $range) * $plotHeight;
        $yTicks[] = array(
            'label' => number_format($tickValue, 0),
            'y' => round($tickY, 2),
        );
    }

    $labelIndexes = array_unique(array(
        0,
        $count === 1 ? 0 : (int)floor(($count - 1) / 2),
        $count - 1,
    ));
    sort($labelIndexes);
    $xLabels = array();
    foreach ($labelIndexes as $index) {
        if (!isset($historyRows[$index])) continue;
        $x = $count === 1
            ? ($paddingLeft + ($plotWidth / 2))
            : ($paddingLeft + (($index / ($count - 1)) * $plotWidth));
        $labelTime = strtotime((string)($historyRows[$index]['snapshot_time'] ?? ''));
        $xLabels[] = array(
            'x' => round($x, 2),
            'label' => $labelTime ? date('M j', $labelTime) : '',
        );
    }

    return array(
        'width' => $width,
        'height' => $height,
        'points' => $points,
        'path' => implode(' ', $pathParts),
        'area_path' => $areaPath,
        'y_ticks' => $yTicks,
        'x_labels' => $xLabels,
        'min' => $minValue,
        'max' => $maxValue,
    );
}


function spp_character_columns(PDO $pdo, $tableName) {
    static $cache = array();
    $key = spl_object_hash($pdo) . ':' . $tableName;
    if (isset($cache[$key])) return $cache[$key];
    $columns = array();
    if (!spp_character_table_exists($pdo, $tableName)) return $cache[$key] = $columns;
    foreach ($pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $tableName) . '`')->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $columns[$row['Field']] = true;
    }
    return $cache[$key] = $columns;
}

function spp_character_spellicon_fields(PDO $pdo) {
    static $cache = array();
    $key = spl_object_hash($pdo);
    if (isset($cache[$key])) return $cache[$key];
    $columns = spp_character_columns($pdo, 'dbc_spellicon');
    $idField = isset($columns['id']) ? 'id' : (isset($columns['ID']) ? 'ID' : null);
    $nameField = isset($columns['name']) ? 'name' : (isset($columns['TextureFilename']) ? 'TextureFilename' : null);
    return $cache[$key] = array(
        'id' => $idField,
        'name' => $nameField,
    );
}

function spp_character_resolve_column(PDO $pdo, $tableName, array $candidates) {
    $columns = spp_character_columns($pdo, $tableName);
    foreach ($candidates as $candidate) {
        $candidate = (string)$candidate;
        if ($candidate !== '' && isset($columns[$candidate])) {
            return $candidate;
        }
    }
    return null;
}

function spp_character_talenttab_icon_field(PDO $pdo) {
    static $cache = array();
    $key = spl_object_hash($pdo);
    if (array_key_exists($key, $cache)) return $cache[$key];
    return $cache[$key] = spp_character_resolve_column($pdo, 'dbc_talenttab', array(
        'SpellIconID',
        'spelliconid',
        'ref_spellicon',
    ));
}

function spp_character_spell_icon_field(PDO $pdo, $tableName = 'dbc_spell') {
    static $cache = array();
    $tableName = (string)$tableName;
    $key = spl_object_hash($pdo) . ':' . $tableName;
    if (array_key_exists($key, $cache)) return $cache[$key];
    return $cache[$key] = spp_character_resolve_column($pdo, $tableName, array(
        'ref_spellicon',
        'SpellIconID',
        'spelliconid',
    ));
}

function spp_character_portrait_path($level, $gender, $race, $class) {
    $portraitRelativePath = spp_find_portrait_relative_path(
        spp_portrait_bucket_chain_for_level((int)$level),
        (int)$gender,
        (int)$race,
        (int)$class
    );
    if ($portraitRelativePath !== null) {
        return spp_portrait_image_url($portraitRelativePath);
    }

    return spp_portrait_image_url('wow-default/' . (int)$gender . '-' . (int)$race . '-' . (int)$class . '.gif');
}

function spp_character_icon_url($iconName) {
    $iconName = trim((string)$iconName);
    return spp_resolve_armory_icon_url($iconName, '404.png');
}

function spp_character_reputation_icon_url($factionName, $tier = 'neutral') {
    $normalizedName = strtolower(trim((string)$factionName));
    $normalizedName = preg_replace('/[^a-z0-9]+/', ' ', $normalizedName);
    $normalizedName = trim((string)$normalizedName);

    $namedIcons = array(
        'darkspear trolls' => 'inv_misc_tournaments_symbol_troll',
        'orgrimmar' => 'spell_arcane_portalorgrimmar',
        'thunder bluff' => 'spell_arcane_portalthunderbluff',
        'undercity' => 'spell_arcane_portalundercity',
        'argent dawn' => 'achievement_reputation_argentcrusader',
        'argent crusade' => 'achievement_reputation_argentcrusader',
        'argent champion' => 'achievement_reputation_argentchampion',
        'ashtongue deathsworn' => 'achievement_reputation_ashtonguedeathsworn',
        'cenarion circle' => 'achievement_reputation_guardiansofcenarius',
        'guardians of cenarius' => 'achievement_reputation_guardiansofcenarius',
        'kirin tor' => 'achievement_reputation_kirintor',
        'knights of the ebon blade' => 'achievement_reputation_knightsoftheebonblade',
        'murloc oracle' => 'achievement_reputation_murlocoracle',
        'oracles' => 'achievement_reputation_murlocoracle',
        'ogre' => 'achievement_reputation_ogre',
        'ogri la' => 'achievement_reputation_ogre',
        'timbermaw hold' => 'achievement_reputation_timbermaw',
        'timbermaw' => 'achievement_reputation_timbermaw',
        'the tuskarr' => 'achievement_reputation_tuskarr',
        'tuskarr' => 'achievement_reputation_tuskarr',
        'frenzyheart tribe' => 'achievement_reputation_wolvar',
        'wolvar' => 'achievement_reputation_wolvar',
        'wyrmrest accord' => 'achievement_reputation_wyrmresttemple',
        'wyrmrest temple' => 'achievement_reputation_wyrmresttemple',
    );
    if (isset($namedIcons[$normalizedName])) {
        return spp_character_icon_url($namedIcons[$normalizedName]);
    }

    $tierIcons = array(
        'hated' => 'achievement_reputation_01',
        'hostile' => 'achievement_reputation_02',
        'unfriendly' => 'achievement_reputation_03',
        'neutral' => 'achievement_reputation_04',
        'friendly' => 'achievement_reputation_05',
        'honored' => 'achievement_reputation_06',
        'revered' => 'achievement_reputation_07',
        'exalted' => 'achievement_reputation_08',
    );
    $tierKey = strtolower(trim((string)$tier));

    return spp_character_icon_url($tierIcons[$tierKey] ?? $tierIcons['neutral']);
}

function spp_character_talent_tab_icon_name($tabId) {
    static $map = array(
        41 => 'spell_fire_fire',
        61 => 'spell_frost_freezingbreath',
        81 => 'spell_nature_wispsplode',
        161 => 'inv_sword_27',
        163 => 'inv_shield_06',
        164 => 'ability_warrior_battleshout',
        181 => 'inv_weapon_shortblade_14',
        182 => 'ability_rogue_garrote',
        183 => 'ability_ambush',
        201 => 'spell_holy_auraoflight',
        202 => 'spell_holy_layonhands',
        203 => 'spell_shadow_possession',
        261 => 'spell_fire_volcano',
        262 => 'spell_nature_healingwavegreater',
        263 => 'spell_nature_unyeildingstamina',
        281 => 'ability_physical_taunt',
        282 => 'spell_nature_healingtouch',
        283 => 'spell_nature_lightning',
        301 => 'spell_fire_incinerate',
        302 => 'spell_shadow_unsummonbuilding',
        303 => 'spell_shadow_curseoftounges',
        361 => 'ability_hunter_beasttaming',
        362 => 'ability_hunter_swiftstrike',
        363 => 'ability_marksmanship',
        381 => 'spell_holy_auraoflight',
        382 => 'spell_holy_holybolt',
        383 => 'spell_holy_devotionaura',
    );

    $tabId = (int)$tabId;
    return $map[$tabId] ?? '';
}

function spp_character_skill_icon_url($skillName, $iconName) {
    $skillName = trim((string)$skillName);
    $overrides = array(
        'Swords' => 'INV_Sword_04',
        'Axes' => 'INV_Axe_04',
        'Two-Handed Axes' => 'INV_Axe_09',
        'Maces' => 'INV_Mace_01',
        'Two-Handed Maces' => 'INV_Mace_04',
        'Bows' => 'INV_Weapon_Bow_08',
        'Guns' => 'INV_Weapon_Rifle_06',
        'Crossbows' => 'INV_Weapon_Crossbow_04',
        'Daggers' => 'INV_Weapon_ShortBlade_05',
        'Two-Handed Swords' => 'INV_Sword_27',
        'Polearms' => 'INV_Weapon_Halberd_09',
        'Staves' => 'INV_Staff_08',
        'Wands' => 'INV_Wand_04',
        'Thrown' => 'INV_ThrowingKnife_02',
        'Defense' => 'Ability_Defend',
        'Dual Wield' => 'Ability_DualWield',
        'Fist Weapons' => 'INV_Gauntlets_04',
        'Unarmed' => 'Ability_MeleeDamage',
        'Fishing' => 'INV_Misc_Fish_08',
        'Cooking' => 'INV_Misc_Food_15',
        'First Aid' => 'INV_Misc_Bandage_15',
        'Alchemy' => 'Trade_Alchemy',
        'Enchanting' => 'Trade_Engraving',
        'Skinning' => 'INV_Misc_Pelt_Wolf_01',
        'Riding' => 'Ability_Mount_RidingHorse',
        'Shield' => 'INV_Shield_06',
        'Shields' => 'INV_Shield_06',
        'Cloth' => 'INV_Chest_Cloth_21',
        'Leather' => 'INV_Chest_Leather_08',
        'Mail' => 'INV_Chest_Chain_10',
        'Plate Mail' => 'INV_Chest_Plate01',
        'Plate' => 'INV_Chest_Plate01',
    );
    if (isset($overrides[$skillName])) {
        return spp_character_icon_url($overrides[$skillName]);
    }
    return spp_character_icon_url($iconName);
}

function spp_character_language_icon_url($skillName, $raceId, $gender) {
    $skillName = strtolower(trim((string)$skillName));
    $raceId = (int)$raceId;
    $gender = (int)$gender;
    $genderSlug = $gender === 1 ? 'female' : 'male';
    $raceIconMap = array(
        1 => 'achievement_character_human_' . $genderSlug,
        2 => 'achievement_character_orc_' . $genderSlug,
        3 => 'achievement_character_dwarf_' . $genderSlug,
        4 => 'achievement_character_nightelf_' . $genderSlug,
        5 => 'achievement_character_undead_' . $genderSlug,
        6 => 'achievement_character_tauren_' . $genderSlug,
        7 => 'achievement_character_gnome_' . $genderSlug,
        8 => 'achievement_character_troll_' . $genderSlug,
        10 => 'achievement_character_bloodelf_' . $genderSlug,
        11 => 'achievement_character_draenei_' . $genderSlug,
    );
    $sharedAlliance = array('language: common', 'common');
    $sharedHorde = array('language: orcish', 'orcish');
    if (in_array($skillName, $sharedAlliance, true) || in_array($skillName, $sharedHorde, true)) {
        return isset($raceIconMap[$raceId]) ? spp_resolve_armory_icon_url($raceIconMap[$raceId]) : spp_armory_icon_url('404.png');
    }
    $languageMap = array(
        'language: darnassian' => array(4),
        'darnassian' => array(4),
        'language: dwarven' => array(3),
        'dwarven' => array(3),
        'language: gnomish' => array(7),
        'gnomish' => array(7),
        'language: troll' => array(8),
        'troll' => array(8),
        'language: taurahe' => array(6),
        'taurahe' => array(6),
        'language: gutterspeak' => array(5),
        'gutterspeak' => array(5),
        'language: draconic' => array(1, 2, 3, 4, 5, 6, 7, 8),
        'draconic' => array(1, 2, 3, 4, 5, 6, 7, 8),
        'language: demon tongue' => array(5, 8),
        'demon tongue' => array(5, 8),
        'language: titan' => array(1, 3, 7),
        'titan' => array(1, 3, 7),
        'language: old tongue' => array(4, 8),
        'old tongue' => array(4, 8),
    );
    if (!isset($languageMap[$skillName])) return null;
    $choices = $languageMap[$skillName];
    $pickedRace = $choices[abs(crc32($skillName . ':' . $raceId . ':' . $gender)) % count($choices)];
    return isset($raceIconMap[$pickedRace]) ? spp_resolve_armory_icon_url($raceIconMap[$pickedRace]) : spp_armory_icon_url('404.png');
}

function spp_character_profession_tier_label($max, $name = '') {
    $max = (int)$max;
    $name = trim((string)$name);
    if (strcasecmp($name, 'Riding') === 0) {
        if ($max >= 150) return 'Expert Riding';
        if ($max >= 75) return 'Apprentice Riding';
        return 'No Riding Training';
    }
    if ($max >= 300) return 'Artisan';
    if ($max >= 225) return 'Expert';
    if ($max >= 150) return 'Journeyman';
    if ($max >= 75) return 'Apprentice';
    return $max > 0 ? 'Training' : 'Untrained';
}

function spp_character_binary_skill_label($categoryName, $value, $max) {
    $categoryName = strtolower(trim((string)$categoryName));
    if (strpos($categoryName, 'language') !== false) {
        return (int)$value > 0 ? 'Known' : 'Unknown';
    }
    if (strpos($categoryName, 'armor prof') !== false || strpos($categoryName, 'armor proficiency') !== false) {
        return (int)$value > 0 ? 'Learned' : 'Unlearned';
    }
    return null;
}

function spp_character_skill_entry(array $meta, array $row, array $characterContext = array()) {
    $name = trim((string)($meta['name'] ?? ''));
    $description = trim((string)($meta['description'] ?? ''));
    if ($name === '' || stripos($name, 'racial') !== false) return null;
    $value = (int)($row['value'] ?? 0);
    $max = max(1, (int)($row['max'] ?? 0));
    $displayValue = $value . '/' . $max;
    $rankLabel = $displayValue;
    $binaryLabel = spp_character_binary_skill_label((string)($meta['category_name'] ?? ''), $value, $max);
    if ($binaryLabel !== null) {
        $displayValue = $binaryLabel;
        $rankLabel = $binaryLabel;
    }
    if (strcasecmp($name, 'Riding') === 0) {
        if ($value >= 150 || $max >= 150) {
            $displayValue = '100% Mounts';
            $description = 'Expert riding unlocked.';
        } elseif ($value >= 75 || $max >= 75) {
            $displayValue = '60% Mounts';
            $description = 'Apprentice riding unlocked.';
        } else {
            $displayValue = 'No Mount Training';
            $description = 'Riding training not yet unlocked.';
        }
        $rankLabel = spp_character_profession_tier_label($max, $name);
    } elseif (
        stripos((string)($meta['category_name'] ?? ''), 'profession') !== false ||
        stripos((string)($meta['category_name'] ?? ''), 'secondary') !== false
    ) {
        $rankLabel = spp_character_profession_tier_label($max, $name);
    }
    return array(
        'skill_id' => (int)($row['skill'] ?? 0),
        'name' => $name,
        'description' => $description,
        'value' => $value,
        'max' => $max,
        'rank_label' => $rankLabel,
        'display_value' => $displayValue,
        'percent' => min(100, max(0, round(($value / $max) * 100))),
        'icon' => (
            spp_character_binary_skill_label((string)($meta['category_name'] ?? ''), $value, $max) !== null &&
            stripos((string)($meta['category_name'] ?? ''), 'language') !== false &&
            isset($characterContext['race'], $characterContext['gender'])
        )
            ? (spp_character_language_icon_url($name, (int)$characterContext['race'], (int)$characterContext['gender']) ?: spp_character_skill_icon_url($name, $meta['icon_name'] ?? ''))
            : spp_character_skill_icon_url($name, $meta['icon_name'] ?? ''),
    );
}

function spp_character_recipe_display_name($name) {
    $name = trim((string)$name);
    if ($name === '') return '';
    $display = preg_replace('/^(recipe|pattern|plans|formula|manual|schematic|book|design|tome|technique)\s*:\s*/i', '', $name);
    return trim((string)$display) !== '' ? trim((string)$display) : $name;
}

function spp_character_decode_personality_value(?string $storedValue): string {
    $storedValue = (string)$storedValue;
    $prefix = 'manual saved string::llmdefaultprompt>';
    if ($storedValue === '') return '';
    if (strpos($storedValue, $prefix) !== 0) return $storedValue;
    return substr($storedValue, strlen($prefix));
}

function spp_character_is_playerbot_account_name(?string $accountUsername): bool {
    $accountUsername = strtolower(trim((string)$accountUsername));
    return $accountUsername !== '' && strpos($accountUsername, 'rndbot') !== false;
}

function spp_character_format_duration_short($seconds): string {
    if ($seconds === null || $seconds === '' || !is_numeric($seconds) || (float)$seconds < 0) return 'N/A';
    $seconds = (int)round((float)$seconds);
    if ($seconds >= 86400) return round($seconds / 86400, 1) . 'd';
    if ($seconds >= 3600) return round($seconds / 3600, 1) . 'h';
    if ($seconds >= 60) return round($seconds / 60, 1) . 'm';
    return $seconds . 's';
}

function spp_character_profession_recipe_signature($spellName, $itemNames = array()) {
    $spellName = strtolower(trim((string)$spellName));
    $spellName = preg_replace('/\s+/', ' ', $spellName);
    $items = array();
    foreach ((array)$itemNames as $itemName) {
        $itemName = strtolower(trim((string)$itemName));
        $itemName = preg_replace('/\s+/', ' ', $itemName);
        if ($itemName !== '') {
            $items[] = $itemName;
        }
    }
    sort($items);

    return $spellName . '|' . implode('|', $items);
}

function spp_character_recipe_filter_labels() {
    return array(
        'all' => 'All',
        'faction' => 'Faction Rep',
        'rare-drop' => 'Rare Drops',
        'endgame' => '300 Skill',
        'flask' => 'Flasks',
    );
}

function spp_character_profession_specializations($professionName, array $knownSpells) {
    $professionName = strtolower(trim((string)$professionName));
    $maps = array(
        'leatherworking' => array(
            10656 => 'Dragonscale',
            10658 => 'Elemental',
            10660 => 'Tribal',
        ),
        'blacksmithing' => array(
            9787 => 'Weaponsmith',
            9788 => 'Armorsmith',
            17039 => 'Master Swordsmith',
            17040 => 'Master Hammersmith',
            17041 => 'Master Axesmith',
        ),
        'engineering' => array(
            20219 => 'Gnomish',
            20222 => 'Goblin',
        ),
    );
    if (!isset($maps[$professionName])) return array();
    $specializations = array();
    foreach ($maps[$professionName] as $spellId => $label) {
        if (!empty($knownSpells[$spellId])) $specializations[] = $label;
    }
    return $specializations;
}

function spp_character_format_playtime($seconds) {
    $seconds = max(0, (int)$seconds);
    $days = intdiv($seconds, 86400);
    $hours = intdiv($seconds % 86400, 3600);
    $minutes = intdiv($seconds % 3600, 60);
    $parts = array();
    if ($days > 0) $parts[] = $days . 'd';
    if ($hours > 0) $parts[] = $hours . 'h';
    if ($minutes > 0 || empty($parts)) $parts[] = $minutes . 'm';
    return implode(' ', $parts);
}

function spp_character_faction_name($raceId) {
    return in_array((int)$raceId, array(1, 3, 4, 7, 11, 22, 25, 29), true) ? 'Alliance' : 'Horde';
}

function spp_character_quest_template_fields(PDO $worldPdo) {
    static $cache = array();
    $key = spl_object_hash($worldPdo);
    if (isset($cache[$key])) return $cache[$key];
    $columns = spp_character_columns($worldPdo, 'quest_template');
    $pick = function (array $candidates) use ($columns) {
        foreach ($candidates as $candidate) {
            if (isset($columns[$candidate])) return $candidate;
        }
        return null;
    };
    return $cache[$key] = array(
        'entry' => $pick(array('entry', 'Entry')),
        'title' => $pick(array('Title', 'title', 'LogTitle', 'QuestTitle')),
        'level' => $pick(array('QuestLevel', 'questlevel', 'MinLevel', 'Min_Level')),
        'details' => $pick(array('Details', 'details', 'LogDescription', 'logdescription', 'ObjectiveText1', 'Objectives', 'objectives')),
        'objectives_text' => $pick(array('Objectives', 'objectives', 'QuestDescription', 'questdescription')),
        'objective_text_1' => $pick(array('ObjectiveText1', 'objectiveText1', 'ObjectiveText_1')),
        'objective_text_2' => $pick(array('ObjectiveText2', 'objectiveText2', 'ObjectiveText_2')),
        'objective_text_3' => $pick(array('ObjectiveText3', 'objectiveText3', 'ObjectiveText_3')),
        'objective_text_4' => $pick(array('ObjectiveText4', 'objectiveText4', 'ObjectiveText_4')),
        'req_creature_count_1' => $pick(array('ReqCreatureOrGOCount1', 'ReqCreatureOrGOcount1')),
        'req_creature_count_2' => $pick(array('ReqCreatureOrGOCount2', 'ReqCreatureOrGOcount2')),
        'req_creature_count_3' => $pick(array('ReqCreatureOrGOCount3', 'ReqCreatureOrGOcount3')),
        'req_creature_count_4' => $pick(array('ReqCreatureOrGOCount4', 'ReqCreatureOrGOcount4')),
        'req_creature_or_go_id_1' => $pick(array('ReqCreatureOrGOId1', 'ReqCreatureOrGOid1')),
        'req_creature_or_go_id_2' => $pick(array('ReqCreatureOrGOId2', 'ReqCreatureOrGOid2')),
        'req_creature_or_go_id_3' => $pick(array('ReqCreatureOrGOId3', 'ReqCreatureOrGOid3')),
        'req_creature_or_go_id_4' => $pick(array('ReqCreatureOrGOId4', 'ReqCreatureOrGOid4')),
        'req_item_id_1' => $pick(array('ReqItemId1', 'ReqItemid1')),
        'req_item_id_2' => $pick(array('ReqItemId2', 'ReqItemid2')),
        'req_item_id_3' => $pick(array('ReqItemId3', 'ReqItemid3')),
        'req_item_id_4' => $pick(array('ReqItemId4', 'ReqItemid4')),
        'req_item_count_1' => $pick(array('ReqItemCount1', 'ReqItemcount1')),
        'req_item_count_2' => $pick(array('ReqItemCount2', 'ReqItemcount2')),
        'req_item_count_3' => $pick(array('ReqItemCount3', 'ReqItemcount3')),
        'req_item_count_4' => $pick(array('ReqItemCount4', 'ReqItemcount4')),
        'rew_choice_item_1' => $pick(array('RewChoiceItemId1')),
        'rew_choice_item_2' => $pick(array('RewChoiceItemId2')),
        'rew_choice_item_3' => $pick(array('RewChoiceItemId3')),
        'rew_choice_item_4' => $pick(array('RewChoiceItemId4')),
        'rew_choice_item_5' => $pick(array('RewChoiceItemId5')),
        'rew_choice_item_6' => $pick(array('RewChoiceItemId6')),
        'rew_choice_count_1' => $pick(array('RewChoiceItemCount1')),
        'rew_choice_count_2' => $pick(array('RewChoiceItemCount2')),
        'rew_choice_count_3' => $pick(array('RewChoiceItemCount3')),
        'rew_choice_count_4' => $pick(array('RewChoiceItemCount4')),
        'rew_choice_count_5' => $pick(array('RewChoiceItemCount5')),
        'rew_choice_count_6' => $pick(array('RewChoiceItemCount6')),
        'rew_item_1' => $pick(array('RewItemId1')),
        'rew_item_2' => $pick(array('RewItemId2')),
        'rew_item_3' => $pick(array('RewItemId3')),
        'rew_item_4' => $pick(array('RewItemId4')),
        'rew_item_count_1' => $pick(array('RewItemCount1')),
        'rew_item_count_2' => $pick(array('RewItemCount2')),
        'rew_item_count_3' => $pick(array('RewItemCount3')),
        'rew_item_count_4' => $pick(array('RewItemCount4')),
        'rew_money' => $pick(array('RewOrReqMoney', 'RewMoneyMaxLevel', 'RewardMoney')),
    );
}

function spp_character_fetch_quest_meta(PDO $worldPdo, array $questIds) {
    $questIds = array_values(array_unique(array_filter(array_map('intval', $questIds))));
    if (empty($questIds) || !spp_character_table_exists($worldPdo, 'quest_template')) return array();
    $fields = spp_character_quest_template_fields($worldPdo);
    if (!$fields['entry'] || !$fields['title']) return array();
    $select = array(
        '`' . $fields['entry'] . '` AS `entry`',
        '`' . $fields['title'] . '` AS `title`',
    );
    $aliases = array(
        'level' => 'quest_level',
        'details' => 'quest_description',
        'objectives_text' => 'objectives_text',
        'objective_text_1' => 'objective_text_1',
        'objective_text_2' => 'objective_text_2',
        'objective_text_3' => 'objective_text_3',
        'objective_text_4' => 'objective_text_4',
        'req_creature_count_1' => 'req_creature_count_1',
        'req_creature_count_2' => 'req_creature_count_2',
        'req_creature_count_3' => 'req_creature_count_3',
        'req_creature_count_4' => 'req_creature_count_4',
        'req_creature_or_go_id_1' => 'req_creature_or_go_id_1',
        'req_creature_or_go_id_2' => 'req_creature_or_go_id_2',
        'req_creature_or_go_id_3' => 'req_creature_or_go_id_3',
        'req_creature_or_go_id_4' => 'req_creature_or_go_id_4',
        'req_item_id_1' => 'req_item_id_1',
        'req_item_id_2' => 'req_item_id_2',
        'req_item_id_3' => 'req_item_id_3',
        'req_item_id_4' => 'req_item_id_4',
        'req_item_count_1' => 'req_item_count_1',
        'req_item_count_2' => 'req_item_count_2',
        'req_item_count_3' => 'req_item_count_3',
        'req_item_count_4' => 'req_item_count_4',
        'rew_choice_item_1' => 'rew_choice_item_1',
        'rew_choice_item_2' => 'rew_choice_item_2',
        'rew_choice_item_3' => 'rew_choice_item_3',
        'rew_choice_item_4' => 'rew_choice_item_4',
        'rew_choice_item_5' => 'rew_choice_item_5',
        'rew_choice_item_6' => 'rew_choice_item_6',
        'rew_choice_count_1' => 'rew_choice_count_1',
        'rew_choice_count_2' => 'rew_choice_count_2',
        'rew_choice_count_3' => 'rew_choice_count_3',
        'rew_choice_count_4' => 'rew_choice_count_4',
        'rew_choice_count_5' => 'rew_choice_count_5',
        'rew_choice_count_6' => 'rew_choice_count_6',
        'rew_item_1' => 'rew_item_1',
        'rew_item_2' => 'rew_item_2',
        'rew_item_3' => 'rew_item_3',
        'rew_item_4' => 'rew_item_4',
        'rew_item_count_1' => 'rew_item_count_1',
        'rew_item_count_2' => 'rew_item_count_2',
        'rew_item_count_3' => 'rew_item_count_3',
        'rew_item_count_4' => 'rew_item_count_4',
        'rew_money' => 'rew_money',
    );
    foreach ($aliases as $fieldKey => $alias) {
        if (!empty($fields[$fieldKey])) $select[] = '`' . $fields[$fieldKey] . '` AS `' . $alias . '`';
    }
    $placeholders = implode(',', array_fill(0, count($questIds), '?'));
    $stmt = $worldPdo->prepare('SELECT ' . implode(', ', $select) . ' FROM `quest_template` WHERE `' . $fields['entry'] . '` IN (' . $placeholders . ')');
    $stmt->execute($questIds);
    $meta = array();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $questId = (int)($row['entry'] ?? 0);
        if ($questId <= 0) continue;
        $meta[$questId] = array(
            'title' => trim((string)($row['title'] ?? ('Quest #' . $questId))),
            'quest_level' => isset($row['quest_level']) ? (int)$row['quest_level'] : null,
            'description' => trim((string)($row['quest_description'] ?? '')),
            'objectives_text' => trim((string)($row['objectives_text'] ?? '')),
            'objective_texts' => array(
                trim((string)($row['objective_text_1'] ?? '')),
                trim((string)($row['objective_text_2'] ?? '')),
                trim((string)($row['objective_text_3'] ?? '')),
                trim((string)($row['objective_text_4'] ?? '')),
            ),
            'required_counts' => array(
                max((int)($row['req_creature_count_1'] ?? 0), (int)($row['req_item_count_1'] ?? 0)),
                max((int)($row['req_creature_count_2'] ?? 0), (int)($row['req_item_count_2'] ?? 0)),
                max((int)($row['req_creature_count_3'] ?? 0), (int)($row['req_item_count_3'] ?? 0)),
                max((int)($row['req_creature_count_4'] ?? 0), (int)($row['req_item_count_4'] ?? 0)),
            ),
            'required_entity_ids' => array(
                (int)($row['req_creature_or_go_id_1'] ?? 0),
                (int)($row['req_creature_or_go_id_2'] ?? 0),
                (int)($row['req_creature_or_go_id_3'] ?? 0),
                (int)($row['req_creature_or_go_id_4'] ?? 0),
            ),
            'required_item_ids' => array(
                (int)($row['req_item_id_1'] ?? 0),
                (int)($row['req_item_id_2'] ?? 0),
                (int)($row['req_item_id_3'] ?? 0),
                (int)($row['req_item_id_4'] ?? 0),
            ),
            'reward_choice_ids' => array((int)($row['rew_choice_item_1'] ?? 0), (int)($row['rew_choice_item_2'] ?? 0), (int)($row['rew_choice_item_3'] ?? 0), (int)($row['rew_choice_item_4'] ?? 0), (int)($row['rew_choice_item_5'] ?? 0), (int)($row['rew_choice_item_6'] ?? 0)),
            'reward_choice_counts' => array((int)($row['rew_choice_count_1'] ?? 0), (int)($row['rew_choice_count_2'] ?? 0), (int)($row['rew_choice_count_3'] ?? 0), (int)($row['rew_choice_count_4'] ?? 0), (int)($row['rew_choice_count_5'] ?? 0), (int)($row['rew_choice_count_6'] ?? 0)),
            'reward_item_ids' => array((int)($row['rew_item_1'] ?? 0), (int)($row['rew_item_2'] ?? 0), (int)($row['rew_item_3'] ?? 0), (int)($row['rew_item_4'] ?? 0)),
            'reward_item_counts' => array((int)($row['rew_item_count_1'] ?? 0), (int)($row['rew_item_count_2'] ?? 0), (int)($row['rew_item_count_3'] ?? 0), (int)($row['rew_item_count_4'] ?? 0)),
            'reward_money' => (int)($row['rew_money'] ?? 0),
        );
    }
    return $meta;
}

function spp_character_build_quest_progress(array $row) {
    $parts = array();
    for ($i = 1; $i <= 4; $i++) {
        $mob = (int)($row['mobcount' . $i] ?? 0);
        $item = (int)($row['itemcount' . $i] ?? 0);
        if ($mob > 0) $parts[] = 'Mob ' . $i . ': ' . $mob;
        if ($item > 0) $parts[] = 'Item ' . $i . ': ' . $item;
    }
    if (!empty($row['explored'])) $parts[] = 'Explored';
    if (!empty($row['timer'])) $parts[] = 'Timed';
    return $parts;
}

function spp_character_fetch_item_summaries(PDO $worldPdo, PDO $armoryPdo, array $itemIds) {
    $itemIds = array_values(array_unique(array_filter(array_map('intval', $itemIds))));
    if (empty($itemIds) || !spp_character_table_exists($worldPdo, 'item_template')) return array();
    $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
    $stmt = $worldPdo->prepare('SELECT `entry`, `name`, `Quality`, `displayid` FROM `item_template` WHERE `entry` IN (' . $placeholders . ')');
    $stmt->execute($itemIds);
    $itemMap = array();
    $displayIds = array();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $itemRow) {
        $itemMap[(int)$itemRow['entry']] = $itemRow;
        if (!empty($itemRow['displayid'])) $displayIds[(int)$itemRow['displayid']] = true;
    }
    $iconMap = array();
    if (!empty($displayIds) && spp_character_table_exists($armoryPdo, 'dbc_itemdisplayinfo')) {
        $placeholders = implode(',', array_fill(0, count($displayIds), '?'));
        $stmt = $armoryPdo->prepare('SELECT `id`, `name` FROM `dbc_itemdisplayinfo` WHERE `id` IN (' . $placeholders . ')');
        $stmt->execute(array_keys($displayIds));
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $displayRow) {
            $iconMap[(int)$displayRow['id']] = (string)$displayRow['name'];
        }
    }
    $items = array();
    foreach ($itemMap as $entry => $itemRow) {
        $items[$entry] = array(
            'entry' => (int)$entry,
            'name' => (string)$itemRow['name'],
            'quality' => (int)$itemRow['Quality'],
            'icon' => spp_character_icon_url($iconMap[(int)($itemRow['displayid'] ?? 0)] ?? ''),
        );
    }
    return $items;
}

function spp_character_fetch_quest_objective_names(PDO $worldPdo, array $entityIds, array $itemIds) {
    $names = array('entities' => array(), 'items' => array());
    $entityIds = array_values(array_unique(array_filter(array_map('intval', $entityIds))));
    $itemIds = array_values(array_unique(array_filter(array_map('intval', $itemIds))));

    $creatureIds = array();
    $gameobjectIds = array();
    foreach ($entityIds as $entityId) {
        if ($entityId > 0) $creatureIds[] = $entityId;
        if ($entityId < 0) $gameobjectIds[] = abs($entityId);
    }

    if (!empty($creatureIds) && spp_character_table_exists($worldPdo, 'creature_template')) {
        $placeholders = implode(',', array_fill(0, count($creatureIds), '?'));
        $stmt = $worldPdo->prepare('SELECT `entry`, `name` FROM `creature_template` WHERE `entry` IN (' . $placeholders . ')');
        $stmt->execute($creatureIds);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $names['entities'][(int)$row['entry']] = trim((string)($row['name'] ?? ''));
        }
    }

    if (!empty($gameobjectIds) && spp_character_table_exists($worldPdo, 'gameobject_template')) {
        $placeholders = implode(',', array_fill(0, count($gameobjectIds), '?'));
        $stmt = $worldPdo->prepare('SELECT `entry`, `name` FROM `gameobject_template` WHERE `entry` IN (' . $placeholders . ')');
        $stmt->execute($gameobjectIds);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $names['entities'][-(int)$row['entry']] = trim((string)($row['name'] ?? ''));
        }
    }

    if (!empty($itemIds) && spp_character_table_exists($worldPdo, 'item_template')) {
        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        $stmt = $worldPdo->prepare('SELECT `entry`, `name` FROM `item_template` WHERE `entry` IN (' . $placeholders . ')');
        $stmt->execute($itemIds);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $names['items'][(int)$row['entry']] = trim((string)($row['name'] ?? ''));
        }
    }

    return $names;
}

function spp_character_build_quest_objectives(array $meta, array $row) {
    $objectives = array();
    $objectiveTexts = $meta['objective_texts'] ?? array();
    $requiredCounts = $meta['required_counts'] ?? array();
    $requiredEntityIds = $meta['required_entity_ids'] ?? array();
    $requiredItemIds = $meta['required_item_ids'] ?? array();
    $objectiveNames = $meta['objective_names'] ?? array('entities' => array(), 'items' => array());
    for ($i = 0; $i < 4; $i++) {
        $label = trim((string)($objectiveTexts[$i] ?? ''));
        $target = (int)($requiredCounts[$i] ?? 0);
        $current = max((int)($row['mobcount' . ($i + 1)] ?? 0), (int)($row['itemcount' . ($i + 1)] ?? 0));
        if ($label !== '') {
            $objectives[] = $target > 0 ? ($label . ': ' . $current . '/' . $target) : $label;
            continue;
        }
        $itemId = (int)($requiredItemIds[$i] ?? 0);
        if ($itemId > 0) {
            $itemName = trim((string)($objectiveNames['items'][$itemId] ?? ''));
            if ($itemName !== '') {
                $objectives[] = ($target > 0 ? 'Collect ' . $itemName . ': ' . $current . '/' . $target : 'Collect ' . $itemName);
                continue;
            }
        }
        $entityId = (int)($requiredEntityIds[$i] ?? 0);
        if ($entityId !== 0) {
            $entityName = trim((string)($objectiveNames['entities'][$entityId] ?? ''));
            if ($entityName !== '') {
                $verb = $entityId < 0 ? 'Use' : 'Kill';
                $objectives[] = ($target > 0 ? $verb . ' ' . $entityName . ': ' . $current . '/' . $target : $verb . ' ' . $entityName);
                continue;
            }
        }
        if ($target > 0) {
            $objectives[] = 'Objective ' . ($i + 1) . ': ' . $current . '/' . $target;
        }
    }
    if (empty($objectives) && !empty($meta['objectives_text'])) {
        $objectives[] = trim((string)$meta['objectives_text']);
    }
    if (empty($objectives)) {
        $objectives = spp_character_build_quest_progress($row);
    }
    return array_values(array_filter(array_map('trim', $objectives)));
}

function spp_character_is_quest_ready_to_turn_in(array $meta, array $row) {
    if (!empty($row['rewarded'])) return false;
    $hasTrackedObjectives = false;
    $requiredCounts = $meta['required_counts'] ?? array();
    for ($i = 0; $i < 4; $i++) {
        $target = (int)($requiredCounts[$i] ?? 0);
        if ($target <= 0) continue;
        $hasTrackedObjectives = true;
        $current = max((int)($row['mobcount' . ($i + 1)] ?? 0), (int)($row['itemcount' . ($i + 1)] ?? 0));
        if ($current < $target) return false;
    }
    if (!empty($row['explored'])) $hasTrackedObjectives = true;
    return $hasTrackedObjectives;
}

function spp_character_build_quest_rewards(array $meta, array $itemSummaries) {
    $rewards = array('choice' => array(), 'guaranteed' => array(), 'money' => 0);
    foreach (($meta['reward_choice_ids'] ?? array()) as $index => $itemId) {
        $itemId = (int)$itemId;
        if ($itemId <= 0 || empty($itemSummaries[$itemId])) continue;
        $rewards['choice'][] = $itemSummaries[$itemId] + array('count' => max(1, (int)(($meta['reward_choice_counts'][$index] ?? 0))));
    }
    foreach (($meta['reward_item_ids'] ?? array()) as $index => $itemId) {
        $itemId = (int)$itemId;
        if ($itemId <= 0 || empty($itemSummaries[$itemId])) continue;
        $rewards['guaranteed'][] = $itemSummaries[$itemId] + array('count' => max(1, (int)(($meta['reward_item_counts'][$index] ?? 0))));
    }
    $rewards['money'] = (int)($meta['reward_money'] ?? 0);
    return $rewards;
}

function spp_character_format_quest_status(array $row, array $meta = array()) {
    if (!empty($row['rewarded'])) return 'Completed';
    if (spp_character_is_quest_ready_to_turn_in($meta, $row)) return 'Ready to Turn In';
    $status = (int)($row['status'] ?? 0);
    if ($status >= 1) return 'In Progress';
    return 'Accepted';
}

function spp_character_quest_status_chip_label($statusLabel) {
    $statusLabel = trim((string)$statusLabel);
    if ($statusLabel === 'Ready to Turn In') return 'Turn In';
    return $statusLabel !== '' ? $statusLabel : 'Accepted';
}

function spp_character_quest_status_chip_class($statusLabel) {
    $statusLabel = strtolower(trim((string)$statusLabel));
    if ($statusLabel === 'ready to turn in') return 'is-turn-in';
    if ($statusLabel === 'in progress') return 'is-progress';
    if ($statusLabel === 'completed') return 'is-complete';
    return 'is-accepted';
}

function spp_character_quest_difficulty_class($questLevel, $characterLevel) {
    $questLevel = (int)$questLevel;
    $characterLevel = (int)$characterLevel;
    if ($questLevel <= 0 || $characterLevel <= 0) return 'is-yellow';
    $delta = $questLevel - $characterLevel;
    if ($delta >= 5) return 'is-red';
    if ($delta >= 3) return 'is-orange';
    if ($delta >= -2) return 'is-yellow';
    if ($delta >= -4) return 'is-green';
    return 'is-gray';
}

function spp_character_render_quest_text($text, $fallbackName = 'adventurer') {
    $text = (string)$text;
    if (trim($text) === '') return '';
    $replacements = array(
        '$B' => "\n\n",
        '$b' => "\n",
        '$N' => $fallbackName,
        '$n' => $fallbackName,
        '$C' => 'adventurer',
        '$c' => 'adventurer',
        '$R' => '',
        '$r' => '',
    );
    $text = strtr($text, $replacements);
    $text = preg_replace('/\|c[0-9a-fA-F]{8}/', '', $text);
    $text = str_replace('|r', '', $text);
    $text = preg_replace("/\r\n?/", "\n", $text);
    $text = preg_replace("/\n{3,}/", "\n\n", trim($text));
    return nl2br(htmlspecialchars($text), false);
}

function spp_character_rep_rank($standing) {
    $lengths = array(36000, 3000, 3000, 3000, 6000, 12000, 21000, 1000);
    $labels = array('Hated', 'Hostile', 'Unfriendly', 'Neutral', 'Friendly', 'Honored', 'Revered', 'Exalted');
    $limit = 42999;
    $standing = (int)$standing;
    for ($rank = count($lengths) - 1; $rank >= 0; --$rank) {
        $limit -= $lengths[$rank];
        if ($standing >= $limit) {
            return array('label' => $labels[$rank], 'value' => $standing - $limit, 'max' => $lengths[$rank]);
        }
    }
    return array('label' => 'Hated', 'value' => 0, 'max' => $lengths[0]);
}
}
