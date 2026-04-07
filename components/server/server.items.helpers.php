<?php
if (!defined('INCLUDED') || INCLUDED !== true) {
    exit;
}

if (!defined('SPP_ITEM_DATABASE_DEFAULT_PER_PAGE')) {
    define('SPP_ITEM_DATABASE_DEFAULT_PER_PAGE', 25);
}

if (!defined('SPP_ITEM_DATABASE_MIN_SEARCH_LENGTH')) {
    define('SPP_ITEM_DATABASE_MIN_SEARCH_LENGTH', 2);
}

if (!function_exists('spp_item_database_quality_color')) {
    function spp_item_database_quality_color($quality)
    {
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

if (!function_exists('spp_item_database_quality_label')) {
    function spp_item_database_quality_label($quality)
    {
        $labels = [
            0 => 'Poor',
            1 => 'Common',
            2 => 'Uncommon',
            3 => 'Rare',
            4 => 'Epic',
            5 => 'Legendary',
        ];

        $quality = (int)$quality;
        return $labels[$quality] ?? 'Unknown';
    }
}

if (!function_exists('spp_item_database_icon_url')) {
    function spp_item_database_icon_url($iconName)
    {
        $iconName = trim((string)$iconName);
        if ($iconName === '') {
            return spp_modern_icon_url('404.png');
        }
        if (preg_match('#^https?://#i', $iconName) || strpos($iconName, '//') === 0) {
            return $iconName;
        }
        if ($iconName[0] === '/') {
            return $iconName;
        }
        if (strpos($iconName, 'images/') === 0) {
            return '/templates/offlike/images/armory/' . substr($iconName, strlen('images/'));
        }
        if (strpos($iconName, 'armory/images/') === 0) {
            return '/templates/offlike/images/armory/' . substr($iconName, strlen('armory/images/'));
        }
        if (substr($iconName, -4) !== '.png') {
            $iconName .= '.png';
        }
        return spp_modern_icon_url(strtolower($iconName));
    }
}

if (!function_exists('spp_item_database_inventory_type_name')) {
    function spp_item_database_inventory_type_name($inventoryType)
    {
        $map = [
            0 => 'None',
            1 => 'Head',
            2 => 'Neck',
            3 => 'Shoulder',
            5 => 'Chest',
            6 => 'Waist',
            7 => 'Legs',
            8 => 'Feet',
            9 => 'Wrist',
            10 => 'Hands',
            11 => 'Finger',
            12 => 'Trinket',
            13 => 'One Hand',
            14 => 'Shield',
            15 => 'Weapon',
            16 => 'Back',
            17 => 'Two-Hand',
            18 => 'Bag',
            21 => 'Main Hand',
            22 => 'Off Hand',
            23 => 'Held In Off-hand',
        ];

        $inventoryType = (int)$inventoryType;
        return $map[$inventoryType] ?? ('Slot ' . $inventoryType);
    }
}

if (!function_exists('spp_item_database_class_label')) {
    function spp_item_database_class_label($classId)
    {
        $labels = [
            0 => 'Consumable',
            1 => 'Container',
            2 => 'Weapon',
            3 => 'Gem',
            4 => 'Armor',
            5 => 'Reagent',
            6 => 'Projectile',
            7 => 'Trade Goods',
            8 => 'Generic',
            9 => 'Recipe',
            10 => 'Money',
            11 => 'Quiver',
            12 => 'Quest',
            13 => 'Key',
            14 => 'Permanent',
            15 => 'Miscellaneous',
        ];

        $classId = (int)$classId;
        return $labels[$classId] ?? 'Item';
    }
}

if (!function_exists('spp_item_database_sort_options')) {
    function spp_item_database_sort_options()
    {
        return [
            'featured' => 'Vault Picks',
            'name' => 'Name',
            'level' => 'Item Level',
            'required' => 'Required Level',
            'quality' => 'Quality',
            'newest' => 'Newest Entries',
        ];
    }
}

if (!function_exists('spp_item_database_type_options')) {
    function spp_item_database_type_options()
    {
        return [
            'all' => 'All Records',
            'items' => 'Items',
            'sets' => 'Item Sets',
            'icons' => 'Icons',
            'quests' => 'Quests',
            'npcs' => 'NPCs',
            'spells' => 'Spells',
            'talents' => 'Talents',
        ];
    }
}

if (!function_exists('spp_item_database_quality_options')) {
    function spp_item_database_quality_options()
    {
        return [
            '' => 'Any quality',
            '0' => 'Poor',
            '1' => 'Common',
            '2' => 'Uncommon',
            '3' => 'Rare',
            '4' => 'Epic',
            '5' => 'Legendary',
        ];
    }
}

if (!function_exists('spp_item_database_class_options')) {
    function spp_item_database_class_options()
    {
        return [
            '' => 'Any type',
            '0' => 'Consumable',
            '1' => 'Container',
            '2' => 'Weapon',
            '4' => 'Armor',
            '7' => 'Trade Goods',
            '9' => 'Recipe',
            '12' => 'Quest',
            '15' => 'Miscellaneous',
        ];
    }
}

if (!function_exists('spp_item_database_slot_options')) {
    function spp_item_database_slot_options()
    {
        return [
            '' => 'Any slot',
            '1' => 'Head',
            '2' => 'Neck',
            '3' => 'Shoulder',
            '5' => 'Chest',
            '6' => 'Waist',
            '7' => 'Legs',
            '8' => 'Feet',
            '9' => 'Wrist',
            '10' => 'Hands',
            '11' => 'Finger',
            '12' => 'Trinket',
            '13' => 'One Hand',
            '14' => 'Shield',
            '15' => 'Weapon',
            '16' => 'Back',
            '17' => 'Two-Hand',
            '18' => 'Bag',
            '21' => 'Main Hand',
            '22' => 'Off Hand',
            '23' => 'Held In Off-hand',
        ];
    }
}

if (!function_exists('spp_item_upgrade_stat_labels')) {
    function spp_item_upgrade_stat_labels()
    {
        return [
            'strength' => 'Strength',
            'agility' => 'Agility',
            'intellect' => 'Intellect',
            'spirit' => 'Spirit',
            'stamina' => 'Stamina',
            'attack_power' => 'Attack Power',
            'ranged_attack_power' => 'Ranged Attack Power',
            'spell_power' => 'Spell Power',
            'mana_regen' => 'Mana Regen',
            'defense' => 'Defense Rating',
            'dodge' => 'Dodge Rating',
            'parry' => 'Parry Rating',
            'block' => 'Block Rating',
            'block_value' => 'Block Value',
            'hit' => 'Hit Rating',
            'ranged_hit' => 'Ranged Hit Rating',
            'spell_hit' => 'Spell Hit Rating',
            'crit' => 'Critical Strike Rating',
            'ranged_crit' => 'Ranged Crit Rating',
            'spell_crit' => 'Spell Crit Rating',
            'haste' => 'Haste Rating',
            'ranged_haste' => 'Ranged Haste Rating',
            'spell_haste' => 'Spell Haste Rating',
            'resilience' => 'Resilience Rating',
            'expertise' => 'Expertise Rating',
            'armor_pen' => 'Armor Penetration',
            'spell_pen' => 'Spell Penetration',
            'armor' => 'Armor',
            'fire_res' => 'Fire Resistance',
            'nature_res' => 'Nature Resistance',
            'frost_res' => 'Frost Resistance',
            'shadow_res' => 'Shadow Resistance',
            'arcane_res' => 'Arcane Resistance',
        ];
    }
}

if (!function_exists('spp_item_upgrade_stat_definitions')) {
    function spp_item_upgrade_stat_definitions()
    {
        return [
            3 => ['key' => 'agility', 'label' => 'Agility'],
            4 => ['key' => 'strength', 'label' => 'Strength'],
            5 => ['key' => 'intellect', 'label' => 'Intellect'],
            6 => ['key' => 'spirit', 'label' => 'Spirit'],
            7 => ['key' => 'stamina', 'label' => 'Stamina'],
            12 => ['key' => 'defense', 'label' => 'Defense Rating'],
            13 => ['key' => 'dodge', 'label' => 'Dodge Rating'],
            14 => ['key' => 'parry', 'label' => 'Parry Rating'],
            15 => ['key' => 'block', 'label' => 'Block Rating'],
            16 => ['key' => 'hit', 'label' => 'Hit Rating'],
            17 => ['key' => 'ranged_hit', 'label' => 'Ranged Hit Rating'],
            18 => ['key' => 'spell_hit', 'label' => 'Spell Hit Rating'],
            19 => ['key' => 'crit', 'label' => 'Crit Rating'],
            20 => ['key' => 'ranged_crit', 'label' => 'Ranged Crit Rating'],
            21 => ['key' => 'spell_crit', 'label' => 'Spell Crit Rating'],
            25 => ['key' => 'resilience', 'label' => 'Resilience Rating'],
            28 => ['key' => 'haste', 'label' => 'Haste Rating'],
            29 => ['key' => 'ranged_haste', 'label' => 'Ranged Haste Rating'],
            30 => ['key' => 'spell_haste', 'label' => 'Spell Haste Rating'],
            31 => ['key' => 'hit', 'label' => 'Hit Rating'],
            32 => ['key' => 'crit', 'label' => 'Critical Strike Rating'],
            35 => ['key' => 'resilience', 'label' => 'Resilience Rating'],
            36 => ['key' => 'haste', 'label' => 'Haste Rating'],
            37 => ['key' => 'expertise', 'label' => 'Expertise Rating'],
            38 => ['key' => 'attack_power', 'label' => 'Attack Power'],
            39 => ['key' => 'ranged_attack_power', 'label' => 'Ranged Attack Power'],
            43 => ['key' => 'mana_regen', 'label' => 'Mana Regen'],
            44 => ['key' => 'armor_pen', 'label' => 'Armor Penetration'],
            45 => ['key' => 'spell_power', 'label' => 'Spell Power'],
            47 => ['key' => 'spell_pen', 'label' => 'Spell Penetration'],
            48 => ['key' => 'block_value', 'label' => 'Block Value'],
        ];
    }
}

if (!function_exists('spp_item_upgrade_weight_fields')) {
    function spp_item_upgrade_weight_fields()
    {
        return spp_item_upgrade_stat_labels();
    }
}

if (!function_exists('spp_item_upgrade_presets')) {
    function spp_item_upgrade_presets()
    {
        static $presets = null;
        if ($presets !== null) {
            return $presets;
        }

        $path = dirname(__DIR__, 2) . '/config/armory/item_upgrade_presets.php';
        $loaded = is_file($path) ? require $path : [];
        $presets = is_array($loaded) ? $loaded : [];
        return $presets;
    }
}

if (!function_exists('spp_item_upgrade_extract_stats')) {
    function spp_item_upgrade_extract_stats(array $itemRow)
    {
        $definitions = spp_item_upgrade_stat_definitions();
        $stats = [];

        for ($index = 1; $index <= 10; $index++) {
            $type = (int)($itemRow['stat_type' . $index] ?? 0);
            $value = (int)($itemRow['stat_value' . $index] ?? 0);
            if ($type <= 0 || $value === 0 || !isset($definitions[$type])) {
                continue;
            }

            $key = $definitions[$type]['key'];
            $stats[$key] = ($stats[$key] ?? 0) + $value;
        }

        foreach ([
            'Armor' => 'armor',
            'fire_res' => 'fire_res',
            'nature_res' => 'nature_res',
            'frost_res' => 'frost_res',
            'shadow_res' => 'shadow_res',
            'arcane_res' => 'arcane_res',
        ] as $sourceKey => $targetKey) {
            $value = (int)($itemRow[$sourceKey] ?? 0);
            if ($value > 0) {
                $stats[$targetKey] = ($stats[$targetKey] ?? 0) + $value;
            }
        }

        return $stats;
    }
}

if (!function_exists('spp_item_upgrade_parse_weights')) {
    function spp_item_upgrade_parse_weights($raw)
    {
        $raw = trim((string)$raw);
        if ($raw === '') {
            return [];
        }

        $allowed = spp_item_upgrade_weight_fields();
        $weights = [];
        $parts = preg_split('/[\r\n,;]+/', $raw);
        foreach ($parts as $part) {
            $part = trim((string)$part);
            if ($part === '' || strpos($part, ':') === false) {
                continue;
            }

            [$key, $value] = array_map('trim', explode(':', $part, 2));
            $key = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $key));
            $key = trim($key, '_');
            if ($key === '' || !isset($allowed[$key]) || !is_numeric($value)) {
                continue;
            }

            $weight = (float)$value;
            if (abs($weight) < 0.0001) {
                continue;
            }
            $weights[$key] = $weight;
        }

        return $weights;
    }
}

if (!function_exists('spp_item_upgrade_encode_weights')) {
    function spp_item_upgrade_encode_weights(array $weights)
    {
        if (!$weights) {
            return '';
        }

        ksort($weights);
        $parts = [];
        foreach ($weights as $key => $value) {
            $parts[] = $key . ':' . rtrim(rtrim(number_format((float)$value, 2, '.', ''), '0'), '.');
        }
        return implode(', ', $parts);
    }
}

if (!function_exists('spp_item_upgrade_score')) {
    function spp_item_upgrade_score(array $stats, array $weights)
    {
        $score = 0.0;
        $matched = [];

        foreach ($weights as $key => $weight) {
            $value = (float)($stats[$key] ?? 0);
            if (abs($value) < 0.0001 || abs((float)$weight) < 0.0001) {
                continue;
            }

            $contribution = $value * (float)$weight;
            $matched[$key] = [
                'value' => $value,
                'weight' => (float)$weight,
                'contribution' => $contribution,
            ];
            $score += $contribution;
        }

        uasort($matched, static function ($left, $right) {
            return abs((float)$right['contribution']) <=> abs((float)$left['contribution']);
        });

        return [
            'score' => $score,
            'matched' => $matched,
        ];
    }
}

if (!function_exists('spp_item_upgrade_top_stat_lines')) {
    function spp_item_upgrade_top_stat_lines(array $matched, $limit = 3)
    {
        $labels = spp_item_upgrade_stat_labels();
        $lines = [];
        foreach ($matched as $key => $info) {
            if ((float)($info['contribution'] ?? 0.0) <= 0) {
                continue;
            }
            $value = (float)($info['value'] ?? 0.0);
            $label = $labels[$key] ?? ucfirst(str_replace('_', ' ', (string)$key));
            $lines[] = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') . ' ' . $label;
            if (count($lines) >= (int)$limit) {
                break;
            }
        }
        return $lines;
    }
}

if (!function_exists('spp_item_database_normalize_search')) {
    function spp_item_database_normalize_search($search)
    {
        $search = trim((string)$search);
        $search = preg_replace('/\s\s+/', ' ', $search);
        if ($search === '') {
            return '';
        }
        if (preg_match('/[^[:alnum:]\s\'\-\:\(\)]/u', $search)) {
            return '';
        }
        return $search;
    }
}

if (!function_exists('spp_item_database_parse_request')) {
    function spp_item_database_parse_request(array $config)
    {
        $sortOptions = spp_item_database_sort_options();
        $qualityOptions = spp_item_database_quality_options();
        $classOptions = spp_item_database_class_options();
        $slotOptions = spp_item_database_slot_options();

        $defaultPerPage = SPP_ITEM_DATABASE_DEFAULT_PER_PAGE;
        $filters = [
            'type' => trim((string)($_GET['type'] ?? 'all')),
            'set_section' => trim((string)($_GET['set_section'] ?? 'all')),
            'set_class' => trim((string)($_GET['set_class'] ?? 'all')),
            'search' => trim((string)($_GET['search'] ?? '')),
            'icon' => trim((string)($_GET['icon'] ?? '')),
            'quality' => trim((string)($_GET['quality'] ?? '')),
            'class' => trim((string)($_GET['item_class'] ?? '')),
            'slot' => trim((string)($_GET['slot'] ?? '')),
            'min_level' => trim((string)($_GET['min_level'] ?? '')),
            'max_level' => trim((string)($_GET['max_level'] ?? '')),
            'sort' => trim((string)($_GET['sort'] ?? 'featured')),
            'dir' => strtoupper(trim((string)($_GET['dir'] ?? 'DESC'))),
            'p' => max(1, (int)($_GET['p'] ?? 1)),
            'per_page' => max(1, (int)($_GET['per_page'] ?? $defaultPerPage)),
        ];

        if (!isset($sortOptions[$filters['sort']])) {
            $filters['sort'] = 'featured';
        }
        if (!array_key_exists($filters['type'], spp_item_database_type_options())) {
            $filters['type'] = 'all';
        }
        if (!in_array($filters['set_section'], ['all', 'misc', 'world', 'pvp'], true)) {
            $filters['set_section'] = 'all';
        }
        if ($filters['dir'] !== 'ASC' && $filters['dir'] !== 'DESC') {
            $filters['dir'] = 'DESC';
        }

        if (!array_key_exists($filters['quality'], $qualityOptions)) {
            $filters['quality'] = '';
        }
        if (!array_key_exists($filters['class'], $classOptions)) {
            $filters['class'] = '';
        }
        if (!array_key_exists($filters['slot'], $slotOptions)) {
            $filters['slot'] = '';
        }

        $filters['min_level'] = ($filters['min_level'] !== '' && ctype_digit($filters['min_level'])) ? (string)max(0, min(500, (int)$filters['min_level'])) : '';
        $filters['max_level'] = ($filters['max_level'] !== '' && ctype_digit($filters['max_level'])) ? (string)max(0, min(500, (int)$filters['max_level'])) : '';

        if ($filters['min_level'] !== '' && $filters['max_level'] !== '' && (int)$filters['min_level'] > (int)$filters['max_level']) {
            $swap = $filters['min_level'];
            $filters['min_level'] = $filters['max_level'];
            $filters['max_level'] = $swap;
        }

        return $filters;
    }
}

if (!function_exists('spp_item_database_has_filters')) {
    function spp_item_database_has_filters(array $filters)
    {
        foreach (['search', 'quality', 'class', 'slot', 'min_level', 'max_level'] as $key) {
            if (!empty($filters[$key])) {
                return true;
            }
        }
        if (!empty($filters['icon'])) {
            return true;
        }
        if (($filters['type'] ?? 'all') !== 'all') {
            return true;
        }
        return false;
    }
}

if (!function_exists('spp_item_database_url')) {
    function spp_item_database_url($realmId, array $filters, array $overrides = [])
    {
        $params = [
            'n' => 'server',
            'sub' => 'items',
            'realm' => (int)$realmId,
            'type' => (string)$filters['type'],
            'set_section' => (string)($filters['set_section'] ?? 'all'),
            'set_class' => (string)($filters['set_class'] ?? 'all'),
            'search' => (string)$filters['search'],
            'icon' => (string)$filters['icon'],
            'quality' => (string)$filters['quality'],
            'item_class' => (string)$filters['class'],
            'slot' => (string)$filters['slot'],
            'min_level' => (string)$filters['min_level'],
            'max_level' => (string)$filters['max_level'],
            'sort' => (string)$filters['sort'],
            'dir' => (string)$filters['dir'],
            'p' => (int)$filters['p'],
            'per_page' => (int)$filters['per_page'],
        ];

        foreach ($overrides as $key => $value) {
            $params[$key] = $value;
        }

        foreach ($params as $key => $value) {
            if ($value === '' || $value === null) {
                unset($params[$key]);
            }
        }

        return 'index.php?' . http_build_query($params);
    }
}

if (!function_exists('spp_item_database_set_section_options')) {
    function spp_item_database_set_section_options()
    {
        return [
            'all' => 'Item Sets',
            'misc' => 'Class & Tier Sets',
            'world' => 'World Sets',
            'pvp' => 'PvP Sets',
        ];
    }
}

if (!function_exists('spp_item_database_sets_provider_path')) {
    function spp_item_database_sets_provider_path($section)
    {
        $section = strtolower(trim((string)$section));
        $map = [
            'misc' => dirname(__DIR__, 2) . '/templates/offlike/server/_sets_class.php',
            'world' => dirname(__DIR__, 2) . '/templates/offlike/server/_sets_world.php',
            'pvp' => dirname(__DIR__, 2) . '/templates/offlike/server/_sets_pvp.php',
        ];
        return $map[$section] ?? $map['misc'];
    }
}

if (!function_exists('spp_item_database_sets_load_provider')) {
    function spp_item_database_sets_load_provider($section, $realmId, $selectedClass = '')
    {
        static $loaded = [];

        $section = strtolower(trim((string)$section));
        if (isset($loaded[$section])) {
            return $loaded[$section];
        }

        $realmId = (int)$realmId;
        $selectedClass = trim((string)$selectedClass);
        $expansion = (int)($GLOBALS['expansion'] ?? 1);
        $currtmp = (string)($GLOBALS['currtmp'] ?? '');
        $realms = $GLOBALS['realms'] ?? [];

        ob_start();
        include spp_item_database_sets_provider_path($section);
        ob_end_clean();

        $loaded[$section] = [
            'classes' => isset($classes) && is_array($classes) ? $classes : [],
            'tierOrder' => isset($tierOrder) && is_array($tierOrder) ? $tierOrder : [],
            'tier_N' => isset($tier_N) && is_array($tier_N) ? $tier_N : [],
            'TIER_BLURB' => isset($TIER_BLURB) && is_array($TIER_BLURB) ? $TIER_BLURB : [],
            'order' => isset($order) && is_array($order) ? $order : [],
            'N' => isset($N) && is_array($N) ? $N : [],
            'BLURB' => isset($BLURB) && is_array($BLURB) ? $BLURB : [],
            'pvporder' => isset($pvporder) && is_array($pvporder) ? $pvporder : [],
            'N_PVP' => isset($N_PVP) && is_array($N_PVP) ? $N_PVP : [],
            'PVP_BLURB' => isset($PVP_BLURB) && is_array($PVP_BLURB) ? $PVP_BLURB : [],
        ];

        return $loaded[$section];
    }
}

if (!function_exists('spp_item_database_set_match_note')) {
    function spp_item_database_set_match_note(string $setName, string $section, array $providerData, string $collectionKey, string $blurbKey): array
    {
        $target = function_exists('spp_itemset_catalog_normalize_name')
            ? spp_itemset_catalog_normalize_name($setName)
            : strtolower((string)preg_replace('/[^a-z0-9]+/i', '', trim($setName)));
        if ($target === '') {
            return [];
        }

        $collection = isset($providerData[$collectionKey]) && is_array($providerData[$collectionKey])
            ? $providerData[$collectionKey]
            : [];
        $blurbs = isset($providerData[$blurbKey]) && is_array($providerData[$blurbKey])
            ? $providerData[$blurbKey]
            : [];

        $matches = [];
        foreach ($collection as $groupKey => $classMap) {
            if (!is_array($classMap)) {
                continue;
            }

            foreach ($classMap as $className => $candidateName) {
                $candidateName = trim((string)$candidateName);
                if ($candidateName === '') {
                    continue;
                }

                $normalizedCandidate = function_exists('spp_itemset_catalog_normalize_name')
                    ? spp_itemset_catalog_normalize_name($candidateName)
                    : strtolower((string)preg_replace('/[^a-z0-9]+/i', '', $candidateName));
                if ($normalizedCandidate !== $target) {
                    continue;
                }

                $blurb = isset($blurbs[$groupKey]) && is_array($blurbs[$groupKey]) ? $blurbs[$groupKey] : [];
                $matches[] = [
                    'section' => $section,
                    'class_name' => trim((string)$className),
                    'title' => trim((string)($blurb['title'] ?? '')),
                    'text' => trim((string)($blurb['text'] ?? '')),
                    'pieces' => (int)($blurb['pieces'] ?? 0),
                    'sort_order' => count($matches),
                ];
            }
        }

        return $matches;
    }
}

if (!function_exists('spp_item_database_set_infer_notes')) {
    function spp_item_database_set_infer_notes($realmId, string $setName): array
    {
        static $cache = [];

        $realmId = (int)$realmId;
        $cacheKey = $realmId . ':' . strtolower(trim($setName));
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $sections = [
            'misc' => ['collection' => 'tier_N', 'blurbs' => 'TIER_BLURB'],
            'world' => ['collection' => 'N', 'blurbs' => 'BLURB'],
            'pvp' => ['collection' => 'N_PVP', 'blurbs' => 'PVP_BLURB'],
        ];

        $notes = [];
        foreach ($sections as $section => $keys) {
            $providerData = spp_item_database_sets_load_provider($section, $realmId, '');
            $notes = array_merge(
                $notes,
                spp_item_database_set_match_note($setName, $section, $providerData, $keys['collection'], $keys['blurbs'])
            );
        }

        $cache[$cacheKey] = $notes;
        return $cache[$cacheKey];
    }
}

if (!function_exists('spp_item_database_sets_catalog')) {
    function spp_item_database_sets_catalog($realmId)
    {
        static $cache = [];

        $realmId = (int)$realmId;
        if (isset($cache[$realmId])) {
            return $cache[$realmId];
        }

        if (function_exists('spp_itemset_catalog_rows')) {
            $cache[$realmId] = (array)spp_itemset_catalog_rows($realmId);
            return $cache[$realmId];
        }

        $setRows = [];
        try {
            $setRows = armory_query("SELECT `id`, `name`, `item_1`, `item_2`, `item_3`, `item_4`, `item_5`, `item_6`, `item_7`, `item_8` FROM `dbc_itemset` ORDER BY `name` ASC", 0);
            if (!is_array($setRows)) {
                $setRows = [];
            }
        } catch (Throwable $e) {
            $setRows = [];
        }

        $notesBySetId = [];
        try {
            $tableCheck = armory_query("SHOW TABLES LIKE 'armory_itemset_notes'", 0);
            if (is_array($tableCheck) && !empty($tableCheck)) {
                $noteRows = armory_query("SELECT `set_id`, `set_name`, `section`, `class_name`, `note_title`, `note_body`, `piece_count`, `source_key`, `sort_order`
                                          FROM `armory_itemset_notes`
                                          WHERE `is_active` = 1
                                          ORDER BY `sort_order` ASC, `id` ASC", 0);
                if (is_array($noteRows)) {
                    foreach ($noteRows as $row) {
                        $setId = (int)($row['set_id'] ?? 0);
                        if ($setId <= 0) {
                            continue;
                        }
                        $notesBySetId[$setId][] = [
                            'set_id' => $setId,
                            'set_name' => trim((string)($row['set_name'] ?? '')),
                            'section' => strtolower(trim((string)($row['section'] ?? ''))),
                            'class_name' => trim((string)($row['class_name'] ?? '')),
                            'title' => trim((string)($row['note_title'] ?? '')),
                            'text' => trim((string)($row['note_body'] ?? '')),
                            'pieces' => (int)($row['piece_count'] ?? 0),
                            'source_key' => trim((string)($row['source_key'] ?? '')),
                            'sort_order' => (int)($row['sort_order'] ?? 0),
                        ];
                    }
                }
            }
        } catch (Throwable $e) {
            $notesBySetId = [];
        }

        $catalog = [];
        foreach ($setRows as $row) {
            $setId = (int)($row['id'] ?? 0);
            if ($setId <= 0) {
                continue;
            }
            $itemIds = [];
            for ($i = 1; $i <= 10; $i++) {
                $itemId = (int)($row['item_' . $i] ?? 0);
                if ($itemId > 0) {
                    $itemIds[] = $itemId;
                }
            }
            $name = trim((string)($row['name'] ?? ''));
            $catalog[] = [
                'id' => $setId,
                'name' => $name,
                'normalized_name' => function_exists('spp_itemset_catalog_normalize_name')
                    ? spp_itemset_catalog_normalize_name($name)
                    : strtolower((string)preg_replace('/[^a-z0-9]+/i', '', $name)),
                'item_ids' => $itemIds,
                'notes' => $notesBySetId[$setId] ?? [],
            ];
        }

        $cache[$realmId] = $catalog;
        return $cache[$realmId];
    }
}

if (!function_exists('spp_item_database_sets_fetch')) {
    function spp_item_database_sets_fetch($realmId, array $filters, array $config)
    {
        $realmId = (int)$realmId;
        $section = strtolower(trim((string)($filters['set_section'] ?? 'all')));
        $sectionOptions = spp_item_database_set_section_options();
        $catalog = spp_item_database_sets_catalog($realmId);

        $classOptions = ['all'];
        foreach ($catalog as $entry) {
            $entryNotes = (array)($entry['notes'] ?? []);
            foreach ($entryNotes as $note) {
                $className = trim((string)($note['class_name'] ?? ''));
                if ($className !== '' && !in_array($className, $classOptions, true)) {
                    $classOptions[] = $className;
                }
            }
        }
        natcasesort($classOptions);
        $classOptions = array_values(array_unique($classOptions));

        $selectedClass = trim((string)($filters['set_class'] ?? 'all'));
        if ($selectedClass === '' || !in_array($selectedClass, $classOptions, true)) {
            $selectedClass = 'all';
        }
        $filters['set_class'] = $selectedClass;

        $searchNeedle = spp_item_database_normalize_search((string)($filters['search'] ?? ''));
        $filters['search'] = $searchNeedle;

        $records = [];
        foreach ($catalog as $entry) {
            $notes = (array)($entry['notes'] ?? []);
            $selectedNote = function_exists('spp_itemset_catalog_select_note')
                ? (array)spp_itemset_catalog_select_note($notes, $section, $selectedClass)
                : [];

            if (($section !== 'all' || $selectedClass !== 'all') && empty($selectedNote)) {
                continue;
            }

            $fallbackNote = empty($selectedNote) && function_exists('spp_itemset_catalog_select_note')
                ? (array)spp_itemset_catalog_select_note($notes, 'all', 'all')
                : [];

            $displayNote = !empty($selectedNote) ? $selectedNote : $fallbackNote;
            $effectiveSection = strtolower(trim((string)($displayNote['section'] ?? '')));
            $effectiveClass = trim((string)($displayNote['class_name'] ?? ''));
            $pieceCount = (int)($displayNote['pieces'] ?? 0);
            if ($pieceCount <= 0) {
                $pieceCount = count((array)($entry['item_ids'] ?? []));
            }

            $records[] = [
                'set_id' => (int)($entry['id'] ?? 0),
                'set_name' => (string)($entry['name'] ?? ''),
                'group_title' => (string)($displayNote['title'] ?? ''),
                'pieces' => $pieceCount,
                'description' => '',
                'section' => $effectiveSection,
                'class_name' => $effectiveClass,
                'item_ids' => array_values(array_map('intval', (array)($entry['item_ids'] ?? []))),
            ];
        }

        if ($searchNeedle !== '') {
            $records = array_values(array_filter($records, static function ($row) use ($searchNeedle) {
                $haystack = strtolower(
                    trim((string)($row['set_name'] ?? '')) . ' ' .
                    trim((string)($row['group_title'] ?? '')) . ' ' .
                    trim((string)($row['section'] ?? '')) . ' ' .
                    trim((string)($row['class_name'] ?? ''))
                );
                return strpos($haystack, strtolower($searchNeedle)) !== false;
            }));
        }

        usort($records, static function ($left, $right) {
            return strcasecmp((string)$left['set_name'], (string)$right['set_name']);
        });

        $total = count($records);
        $perPage = (int)$filters['per_page'];
        $pageCount = max(1, (int)ceil($total / max(1, $perPage)));
        $page = min(max(1, (int)$filters['p']), $pageCount);
        $offset = ($page - 1) * $perPage;
        $filters['p'] = $page;
        $pageRecords = array_slice($records, $offset, $perPage);

        $pageFirstItemIds = [];
        foreach ($pageRecords as $record) {
            $firstItemId = (int)($record['item_ids'][0] ?? 0);
            if ($firstItemId > 0) {
                $pageFirstItemIds[$firstItemId] = $firstItemId;
            }
        }

        $firstItemMeta = [];
        if (!empty($pageFirstItemIds)) {
            try {
                $worldPdo = spp_get_pdo('world', $realmId);
                $armoryPdo = spp_get_pdo('armory', $realmId);

                $placeholders = implode(',', array_fill(0, count($pageFirstItemIds), '?'));
                $itemStmt = $worldPdo->prepare("SELECT `entry`, `displayid` FROM `item_template` WHERE `entry` IN ($placeholders)");
                $itemStmt->execute(array_values($pageFirstItemIds));
                $displayIds = [];
                foreach (($itemStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $metaRow) {
                    $entryId = (int)($metaRow['entry'] ?? 0);
                    $displayId = (int)($metaRow['displayid'] ?? 0);
                    if ($entryId <= 0) {
                        continue;
                    }
                    $firstItemMeta[$entryId] = [
                        'entry' => $entryId,
                        'displayid' => $displayId,
                        'icon' => '',
                    ];
                    if ($displayId > 0) {
                        $displayIds[$displayId] = $displayId;
                    }
                }

                if (!empty($displayIds)) {
                    $displayPlaceholders = implode(',', array_fill(0, count($displayIds), '?'));
                    $iconStmt = $armoryPdo->prepare("SELECT `id`, `icon1` FROM `dbc_itemdisplayinfo` WHERE `id` IN ($displayPlaceholders)");
                    $iconStmt->execute(array_values($displayIds));
                    $iconsByDisplayId = [];
                    foreach (($iconStmt->fetchAll(PDO::FETCH_ASSOC) ?: []) as $iconRow) {
                        $displayId = (int)($iconRow['id'] ?? 0);
                        if ($displayId > 0) {
                            $iconsByDisplayId[$displayId] = trim((string)($iconRow['icon1'] ?? ''));
                        }
                    }

                    foreach ($firstItemMeta as $entryId => $meta) {
                        $displayId = (int)($meta['displayid'] ?? 0);
                        if ($displayId > 0 && !empty($iconsByDisplayId[$displayId])) {
                            $firstItemMeta[$entryId]['icon'] = (string)$iconsByDisplayId[$displayId];
                        }
                    }
                }
            } catch (Throwable $e) {
                error_log('[item sets] failed loading first item metadata: ' . $e->getMessage());
            }
        }

        $rows = [];
        foreach ($pageRecords as $record) {
            $setId = (int)($record['set_id'] ?? 0);
            $setData = $setId > 0 && function_exists('get_itemset_data') ? (array)get_itemset_data($setId) : [];
            $firstItem = !empty($setData['items'][0]) ? (array)$setData['items'][0] : [];
            $firstItemId = (int)($record['item_ids'][0] ?? 0);
            $firstItemLookup = ($firstItemId > 0 && !empty($firstItemMeta[$firstItemId])) ? (array)$firstItemMeta[$firstItemId] : [];
            $pieceCount = !empty($setData['items']) ? count((array)$setData['items']) : (int)($record['pieces'] ?? 0);
            $rowSection = strtolower(trim((string)($record['section'] ?? '')));
            $rowClass = trim((string)($record['class_name'] ?? ''));
            $rows[] = [
                'entity_type' => 'sets',
                'id' => $setId,
                'name' => (string)$record['set_name'],
                'meta' => 'Item Sets',
                'submeta' => '',
                'detail_summary' => trim(($rowClass !== '' ? $rowClass : '') . (($rowClass !== '' && $pieceCount > 0) ? ' | ' : '') . ($pieceCount > 0 ? $pieceCount . ' pieces' : '')),
                'description' => (string)($record['description'] ?? ''),
                'icon' => !empty($firstItemLookup['icon'])
                    ? spp_item_database_icon_url((string)$firstItemLookup['icon'])
                    : (!empty($firstItem['icon']) ? spp_item_database_icon_url((string)$firstItem['icon']) : spp_item_database_icon_url('inv_chest_chain_10')),
                'tooltip_item_id' => $firstItemId,
                'detail_url' => function_exists('spp_sets_detail_url')
                    ? spp_sets_detail_url($realmId, (string)$record['set_name'], [
                        'section' => ($rowSection !== '' ? $rowSection : ($section !== 'all' ? $section : '')),
                        'class' => ($rowClass !== '' ? $rowClass : ($selectedClass !== 'all' ? $selectedClass : '')),
                        'search' => (string)($filters['search'] ?? ''),
                        'quality' => (string)($filters['quality'] ?? ''),
                        'item_class' => (string)($filters['class'] ?? ''),
                        'slot' => (string)($filters['slot'] ?? ''),
                        'min_level' => (string)($filters['min_level'] ?? ''),
                        'max_level' => (string)($filters['max_level'] ?? ''),
                        'p' => (string)($filters['p'] ?? 1),
                        'per_page' => (string)($filters['per_page'] ?? 25),
                        'sort' => (string)($filters['sort'] ?? 'featured'),
                        'dir' => (string)($filters['dir'] ?? 'DESC'),
                    ])
                    : ('index.php?n=server&sub=item&type=sets&set_section=' . urlencode($section) . '&set_class=' . urlencode($selectedClass) . '&realm=' . $realmId . '&set=' . urlencode((string)$record['set_name'])),
            ];
        }

        return [
            'error' => '',
            'filters' => $filters,
            'rows' => $rows,
            'total' => $total,
            'page_count' => $pageCount,
            'per_page' => $perPage,
            'summary' => [
                'epic' => 0,
                'weapon' => 0,
                'armor' => 0,
            'set_sections' => count($sectionOptions),
            ],
            'counts' => array_merge(spp_item_database_search_counts($realmId, $searchNeedle, $config), ['sets' => $total]),
            'sections' => [],
            'set_section_options' => $sectionOptions,
            'set_class_options' => $classOptions,
        ];
    }
}

if (!function_exists('spp_item_database_normalize_icon_search')) {
    function spp_item_database_normalize_icon_search($icon)
    {
        $icon = trim((string)$icon);
        $icon = preg_replace('/\s+/', '_', $icon);
        if ($icon === '') {
            return '';
        }
        if (preg_match('/[^a-z0-9_\-]/i', $icon)) {
            return '';
        }
        return $icon;
    }
}

if (!function_exists('spp_item_database_count_query')) {
    function spp_item_database_count_query(PDO $pdo, $sql, array $params = [])
    {
        $stmt = $pdo->prepare($sql);
        foreach ($params as $name => $value) {
            $stmt->bindValue(':' . $name, $value);
        }
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
}

if (!function_exists('spp_item_database_search_counts')) {
    function spp_item_database_search_counts($realmId, $search, array $config)
    {
        $counts = [
            'items' => 0,
            'icons' => 0,
            'quests' => 0,
            'npcs' => 0,
            'spells' => 0,
            'talents' => 0,
        ];

        $search = spp_item_database_normalize_search($search);
        $minSearchLength = SPP_ITEM_DATABASE_MIN_SEARCH_LENGTH;
        if ($search === '' || strlen($search) < $minSearchLength) {
            return $counts;
        }

        try {
            $worldPdo = spp_get_pdo('world', $realmId);
            $armoryPdo = spp_get_pdo('armory', $realmId);
            $term = '%' . str_replace(' ', '%', $search) . '%';

            $counts['items'] = spp_item_database_count_query($worldPdo, 'SELECT COUNT(*) FROM `item_template` WHERE `name` LIKE :search', ['search' => $term]);
            $counts['icons'] = spp_item_database_count_query($armoryPdo, 'SELECT COUNT(*) FROM `dbc_itemdisplayinfo` WHERE `name` LIKE :search', ['search' => $term]);
            $counts['quests'] = spp_item_database_count_query($worldPdo, 'SELECT COUNT(*) FROM `quest_template` WHERE `Title` LIKE :search', ['search' => $term]);
            $counts['npcs'] = spp_item_database_count_query($worldPdo, 'SELECT COUNT(*) FROM `creature_template` WHERE `Name` LIKE :search', ['search' => $term]);
            $counts['spells'] = spp_item_database_count_query($armoryPdo, 'SELECT COUNT(*) FROM `dbc_spell` WHERE `name` LIKE :search OR `description` LIKE :search', ['search' => $term]);
            $counts['talents'] = spp_item_database_count_query($armoryPdo, 'SELECT COUNT(*) FROM `dbc_talent` t INNER JOIN `dbc_spell` s ON s.`id` = t.`rank1` WHERE s.`name` LIKE :search OR s.`description` LIKE :search', ['search' => $term]);
        } catch (Throwable $e) {
            return $counts;
        }

        return $counts;
    }
}

if (!function_exists('spp_item_database_icon_counts')) {
    function spp_item_database_icon_counts($realmId, $icon)
    {
        $counts = [
            'items' => 0,
            'icons' => 0,
            'quests' => 0,
            'npcs' => 0,
            'spells' => 0,
            'talents' => 0,
        ];

        $icon = spp_item_database_normalize_icon_search($icon);
        if ($icon === '') {
            return $counts;
        }

        try {
            $worldPdo = spp_get_pdo('world', $realmId);
            $armoryPdo = spp_get_pdo('armory', $realmId);

            $iconStmt = $armoryPdo->prepare('SELECT `id` FROM `dbc_itemdisplayinfo` WHERE LOWER(`name`) = LOWER(:icon)');
            $iconStmt->execute(['icon' => $icon]);
            $ids = $iconStmt->fetchAll(PDO::FETCH_COLUMN);
            $counts['icons'] = count($ids);
            if (!$ids) {
                return $counts;
            }

            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $itemStmt = $worldPdo->prepare('SELECT COUNT(*) FROM `item_template` WHERE `displayid` IN (' . $placeholders . ')');
            $itemStmt->execute(array_map('intval', $ids));
            $counts['items'] = (int)$itemStmt->fetchColumn();
        } catch (Throwable $e) {
            return $counts;
        }

        return $counts;
    }
}

if (!function_exists('spp_item_database_paginate_rows')) {
    function spp_item_database_paginate_rows(array $rows, array $filters)
    {
        $total = count($rows);
        $perPage = max(1, (int)$filters['per_page']);
        $pageCount = max(1, (int)ceil($total / $perPage));
        $page = min(max(1, (int)$filters['p']), $pageCount);
        $offset = ($page - 1) * $perPage;
        $filters['p'] = $page;

        return [
            'rows' => array_slice($rows, $offset, $perPage),
            'filters' => $filters,
            'total' => $total,
            'page_count' => $pageCount,
        ];
    }
}

if (!function_exists('spp_item_database_generic_search')) {
    function spp_item_database_generic_search($realmId, $type, array $filters, array $config)
    {
        $minSearchLength = SPP_ITEM_DATABASE_MIN_SEARCH_LENGTH;
        $search = spp_item_database_normalize_search($filters['search']);
        $icon = spp_item_database_normalize_icon_search($filters['icon'] ?? '');
        $counts = spp_item_database_search_counts($realmId, $search, $config);
        if ($type === 'icons' && $icon !== '') {
            $counts = spp_item_database_icon_counts($realmId, $icon);
        }

        if ($type === 'icons' && $icon !== '') {
            $filters['icon'] = $icon;
        } elseif ($search === '' || strlen($search) < $minSearchLength) {
            $emptyError = ($type === 'all')
                ? ''
                : 'Start typing at least ' . $minSearchLength . ' characters to search across items, quests, NPCs, spells, and talents.';
            return [
                'error' => $emptyError,
                'filters' => $filters,
                'rows' => [],
                'total' => 0,
                'page_count' => 1,
                'counts' => $counts,
                'sections' => [],
            ];
        }

        $filters['search'] = $search;

        try {
            $worldPdo = spp_get_pdo('world', $realmId);
            $armoryPdo = spp_get_pdo('armory', $realmId);
            $term = '%' . str_replace(' ', '%', $search) . '%';
            $rows = [];
            $sections = [];

            if ($type === 'all') {
                $sectionQueries = [
                    'items' => ['pdo' => $worldPdo, 'sql' => 'SELECT `entry`, `name`, `ItemLevel`, `Quality`, `displayid` FROM `item_template` WHERE `name` LIKE :search ORDER BY `Quality` DESC, `ItemLevel` DESC, `name` ASC LIMIT 5'],
                    'icons' => ['pdo' => $armoryPdo, 'sql' => 'SELECT `id`, `name` FROM `dbc_itemdisplayinfo` WHERE `name` LIKE :search ORDER BY `name` ASC LIMIT 5'],
                    'quests' => ['pdo' => $worldPdo, 'sql' => 'SELECT `entry`, `Title`, `QuestLevel`, `MinLevel` FROM `quest_template` WHERE `Title` LIKE :search ORDER BY `QuestLevel` DESC, `Title` ASC LIMIT 5'],
                    'npcs' => ['pdo' => $worldPdo, 'sql' => 'SELECT `entry`, `Name`, `MinLevel`, `MaxLevel`, `Rank` FROM `creature_template` WHERE `Name` LIKE :search ORDER BY `MaxLevel` DESC, `Name` ASC LIMIT 5'],
                    'spells' => ['pdo' => $armoryPdo, 'sql' => 'SELECT s.`id`, s.`name`, s.`description`, si.`name` AS `icon_name` FROM `dbc_spell` s LEFT JOIN `dbc_spellicon` si ON si.`id` = s.`ref_spellicon` WHERE s.`name` LIKE :search OR s.`description` LIKE :search ORDER BY s.`name` ASC LIMIT 5'],
                    'talents' => ['pdo' => $armoryPdo, 'sql' => 'SELECT t.`id`, t.`row`, t.`col`, tt.`name` AS `tab_name`, s.`name`, s.`description`, si.`name` AS `icon_name` FROM `dbc_talent` t INNER JOIN `dbc_spell` s ON s.`id` = t.`rank1` LEFT JOIN `dbc_talenttab` tt ON tt.`id` = t.`ref_talenttab` LEFT JOIN `dbc_spellicon` si ON si.`id` = s.`ref_spellicon` WHERE s.`name` LIKE :search OR s.`description` LIKE :search ORDER BY tt.`name` ASC, t.`row` ASC, t.`col` ASC LIMIT 5'],
                ];

                foreach ($sectionQueries as $sectionType => $query) {
                    $stmt = $query['pdo']->prepare($query['sql']);
                    $stmt->execute(['search' => $term]);
                    $sectionRows = [];
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        if ($sectionType === 'items') {
                            $sectionRows[] = [
                                'entity_type' => 'items',
                                'id' => (int)$row['entry'],
                                'name' => (string)$row['name'],
                                'meta' => 'Item',
                                'submeta' => 'ilvl ' . (int)($row['ItemLevel'] ?? 0) . ' | ' . spp_item_database_quality_label((int)($row['Quality'] ?? 0)),
                                'description' => 'Item entry #' . (int)$row['entry'],
                                'icon' => spp_modern_icon_url('404.png'),
                                'quality_color' => spp_item_database_quality_color((int)($row['Quality'] ?? 0)),
                            ];
                        } elseif ($sectionType === 'icons') {
                            $sectionRows[] = [
                                'entity_type' => 'icons',
                                'id' => (int)$row['id'],
                                'name' => (string)$row['name'],
                                'meta' => 'Icon',
                                'submeta' => 'Display ID ' . (int)$row['id'],
                                'description' => 'Icon asset',
                                'icon' => spp_item_database_icon_url((string)$row['name']),
                            ];
                        } elseif ($sectionType === 'quests') {
                            $sectionRows[] = [
                                'entity_type' => 'quests',
                                'id' => (int)$row['entry'],
                                'name' => (string)$row['Title'],
                                'meta' => 'Quest',
                                'submeta' => 'Quest Level ' . (int)($row['QuestLevel'] ?? 0) . ' | Required Level ' . (int)($row['MinLevel'] ?? 0),
                                'description' => 'Quest entry #' . (int)$row['entry'],
                                'icon' => spp_modern_icon_url('inv_misc_book_09.png'),
                            ];
                        } elseif ($sectionType === 'npcs') {
                            $sectionRows[] = [
                                'entity_type' => 'npcs',
                                'id' => (int)$row['entry'],
                                'name' => (string)$row['Name'],
                                'meta' => 'NPC',
                                'submeta' => 'Level ' . (int)($row['MinLevel'] ?? 0) . '-' . (int)($row['MaxLevel'] ?? 0) . ' | Rank ' . (int)($row['Rank'] ?? 0),
                                'description' => 'Creature entry #' . (int)$row['entry'],
                                'icon' => spp_modern_icon_url('achievement_boss_kingymiron_01.png'),
                            ];
                        } elseif ($sectionType === 'spells') {
                            $sectionRows[] = [
                                'entity_type' => 'spells',
                                'id' => (int)$row['id'],
                                'name' => (string)$row['name'],
                                'meta' => 'Spell',
                                'submeta' => 'Spell ID ' . (int)$row['id'],
                                'description' => trim((string)($row['description'] ?? '')),
                                'icon' => spp_item_database_icon_url((string)($row['icon_name'] ?? '')),
                            ];
                        } else {
                            $sectionRows[] = [
                                'entity_type' => 'talents',
                                'id' => (int)$row['id'],
                                'name' => (string)$row['name'],
                                'meta' => 'Talent',
                                'submeta' => trim((string)($row['tab_name'] ?? 'Talent Tree')) . ' | Row ' . ((int)($row['row'] ?? 0) + 1) . ' | Column ' . ((int)($row['col'] ?? 0) + 1),
                                'description' => trim((string)($row['description'] ?? '')),
                                'icon' => spp_item_database_icon_url((string)($row['icon_name'] ?? '')),
                            ];
                        }
                    }
                    $sections[$sectionType] = $sectionRows;
                }

                return [
                    'error' => '',
                    'filters' => $filters,
                    'rows' => [],
                    'total' => array_sum($counts),
                    'page_count' => 1,
                    'counts' => $counts,
                    'sections' => $sections,
                ];
            }

            if ($type === 'icons') {
                $iconSearch = $filters['icon'];
                $iconStmt = $armoryPdo->prepare('SELECT `id`, `name` FROM `dbc_itemdisplayinfo` WHERE LOWER(`name`) = LOWER(:icon) ORDER BY `id` ASC');
                $iconStmt->execute(['icon' => $iconSearch]);
                $iconRows = $iconStmt->fetchAll(PDO::FETCH_ASSOC);
                $displayIds = [];
                foreach ($iconRows as $iconRow) {
                    $displayIds[] = (int)$iconRow['id'];
                }

                if ($displayIds) {
                    $placeholders = implode(',', array_fill(0, count($displayIds), '?'));
                    $itemStmt = $worldPdo->prepare('SELECT `entry`, `name`, `ItemLevel`, `RequiredLevel`, `Quality`, `displayid`, `InventoryType`, `class`, `description` FROM `item_template` WHERE `displayid` IN (' . $placeholders . ') ORDER BY `Quality` DESC, `ItemLevel` DESC, `name` ASC');
                    $itemStmt->execute($displayIds);
                    foreach ($itemStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                        $quality = (int)($row['Quality'] ?? 0);
                        $rows[] = [
                            'entity_type' => 'items',
                            'id' => (int)$row['entry'],
                            'name' => (string)$row['name'],
                            'quality' => $quality,
                            'quality_label' => spp_item_database_quality_label($quality),
                            'quality_color' => spp_item_database_quality_color($quality),
                            'level' => (int)($row['ItemLevel'] ?? 0),
                            'required_level' => (int)($row['RequiredLevel'] ?? 0),
                            'slot_name' => spp_item_database_inventory_type_name((int)($row['InventoryType'] ?? 0)),
                            'class_name' => spp_item_database_class_label((int)($row['class'] ?? 0)),
                            'description' => trim((string)($row['description'] ?? '')),
                            'source' => 'Shares icon ' . $iconSearch,
                            'icon' => spp_item_database_icon_url($iconSearch),
                            'meta' => 'Item',
                            'icon_name' => $iconSearch,
                        ];
                    }
                }
            } elseif ($type === 'quests') {
                $stmt = $worldPdo->prepare('SELECT `entry`, `Title`, `QuestLevel`, `MinLevel` FROM `quest_template` WHERE `Title` LIKE :search ORDER BY `QuestLevel` DESC, `Title` ASC');
                $stmt->execute(['search' => $term]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $rows[] = [
                        'entity_type' => 'quests',
                        'id' => (int)$row['entry'],
                        'name' => (string)$row['Title'],
                        'meta' => 'Quest',
                        'submeta' => 'Quest Level ' . (int)($row['QuestLevel'] ?? 0) . ' | Required Level ' . (int)($row['MinLevel'] ?? 0),
                        'description' => 'Quest entry #' . (int)$row['entry'],
                        'icon' => spp_modern_icon_url('inv_misc_book_09.png'),
                    ];
                }
            } elseif ($type === 'npcs') {
                $stmt = $worldPdo->prepare('SELECT `entry`, `Name`, `MinLevel`, `MaxLevel`, `Rank` FROM `creature_template` WHERE `Name` LIKE :search ORDER BY `MaxLevel` DESC, `Name` ASC');
                $stmt->execute(['search' => $term]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $rows[] = [
                        'entity_type' => 'npcs',
                        'id' => (int)$row['entry'],
                        'name' => (string)$row['Name'],
                        'meta' => 'NPC',
                        'submeta' => 'Level ' . (int)($row['MinLevel'] ?? 0) . '-' . (int)($row['MaxLevel'] ?? 0) . ' | Rank ' . (int)($row['Rank'] ?? 0),
                        'description' => 'Creature entry #' . (int)$row['entry'],
                        'icon' => spp_modern_icon_url('achievement_boss_kingymiron_01.png'),
                    ];
                }
            } elseif ($type === 'spells') {
                $stmt = $armoryPdo->prepare('SELECT s.`id`, s.`name`, s.`description`, si.`name` AS `icon_name` FROM `dbc_spell` s LEFT JOIN `dbc_spellicon` si ON si.`id` = s.`ref_spellicon` WHERE s.`name` LIKE :search OR s.`description` LIKE :search ORDER BY s.`name` ASC');
                $stmt->execute(['search' => $term]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $rows[] = [
                        'entity_type' => 'spells',
                        'id' => (int)$row['id'],
                        'name' => (string)$row['name'],
                        'meta' => 'Spell',
                        'submeta' => 'Spell ID ' . (int)$row['id'],
                        'description' => trim((string)($row['description'] ?? '')),
                        'icon' => spp_item_database_icon_url((string)($row['icon_name'] ?? '')),
                    ];
                }
            } elseif ($type === 'talents') {
                $stmt = $armoryPdo->prepare('SELECT t.`id`, t.`row`, t.`col`, tt.`name` AS `tab_name`, s.`id` AS `spell_id`, s.`name`, s.`description`, si.`name` AS `icon_name` FROM `dbc_talent` t INNER JOIN `dbc_spell` s ON s.`id` = t.`rank1` LEFT JOIN `dbc_talenttab` tt ON tt.`id` = t.`ref_talenttab` LEFT JOIN `dbc_spellicon` si ON si.`id` = s.`ref_spellicon` WHERE s.`name` LIKE :search OR s.`description` LIKE :search ORDER BY tt.`name` ASC, t.`row` ASC, t.`col` ASC');
                $stmt->execute(['search' => $term]);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $rows[] = [
                        'entity_type' => 'talents',
                        'id' => (int)$row['id'],
                        'name' => (string)$row['name'],
                        'meta' => 'Talent',
                        'submeta' => trim((string)($row['tab_name'] ?? 'Talent Tree')) . ' | Row ' . ((int)($row['row'] ?? 0) + 1) . ' | Column ' . ((int)($row['col'] ?? 0) + 1),
                        'description' => trim((string)($row['description'] ?? '')),
                        'icon' => spp_item_database_icon_url((string)($row['icon_name'] ?? '')),
                    ];
                }
            }

            $paged = spp_item_database_paginate_rows($rows, $filters);
            return [
                'error' => '',
                'filters' => $paged['filters'],
                'rows' => $paged['rows'],
                'total' => $paged['total'],
                'page_count' => $paged['page_count'],
                'counts' => $counts,
                'sections' => [],
            ];
        } catch (Throwable $e) {
            return [
                'error' => 'The Armory database search could not load ' . $type . ' from the realm databases.',
                'filters' => $filters,
                'rows' => [],
                'total' => 0,
                'page_count' => 1,
                'counts' => $counts,
                'sections' => [],
            ];
        }
    }
}

if (!function_exists('spp_item_database_sort_sql')) {
    function spp_item_database_sort_sql($sortKey, $direction, $hasSearch)
    {
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        switch ($sortKey) {
            case 'name':
                return 'display_name ' . $direction . ', it.`ItemLevel` DESC, it.`entry` DESC';
            case 'level':
                return 'it.`ItemLevel` ' . $direction . ', display_name ASC, it.`entry` DESC';
            case 'required':
                return 'it.`RequiredLevel` ' . $direction . ', it.`ItemLevel` DESC, display_name ASC';
            case 'quality':
                return 'it.`Quality` ' . $direction . ', it.`ItemLevel` DESC, display_name ASC';
            case 'newest':
                return 'it.`entry` ' . $direction;
            case 'featured':
            default:
                if ($hasSearch) {
                    return 'it.`Quality` DESC, it.`ItemLevel` DESC, display_name ASC';
                }
                return 'it.`ItemLevel` DESC, it.`Quality` DESC, display_name ASC';
        }
    }
}

if (!function_exists('spp_item_database_build_where')) {
    function spp_item_database_build_where(array $filters, $localeField)
    {
        $where = [];
        $params = [];

        if ($filters['search'] !== '') {
            $searchLike = '%' . str_replace(' ', '%', $filters['search']) . '%';
            if ($localeField) {
                $where[] = '(COALESCE(li.`' . $localeField . '`, it.`name`) LIKE :search OR it.`name` LIKE :search)';
            } else {
                $where[] = 'it.`name` LIKE :search';
            }
            $params['search'] = $searchLike;
        }

        if ($filters['quality'] !== '') {
            $where[] = 'it.`Quality` = :quality';
            $params['quality'] = (int)$filters['quality'];
        }

        if ($filters['class'] !== '') {
            $where[] = 'it.`class` = :item_class';
            $params['item_class'] = (int)$filters['class'];
        }

        if ($filters['slot'] !== '') {
            $where[] = 'it.`InventoryType` = :slot';
            $params['slot'] = (int)$filters['slot'];
        }

        if ($filters['min_level'] !== '') {
            $where[] = 'it.`ItemLevel` >= :min_level';
            $params['min_level'] = (int)$filters['min_level'];
        }

        if ($filters['max_level'] !== '') {
            $where[] = 'it.`ItemLevel` <= :max_level';
            $params['max_level'] = (int)$filters['max_level'];
        }

        return [
            'sql' => $where ? (' WHERE ' . implode(' AND ', $where)) : '',
            'params' => $params,
        ];
    }
}

if (!function_exists('spp_item_database_cache_source')) {
    function spp_item_database_cache_source(PDO $worldPdo, PDO $armoryPdo, $itemId, $isPvpReward)
    {
        $itemId = (int)$itemId;

        $checks = [
            ['SELECT `entry` FROM `quest_template` WHERE `SrcItemId` = ? LIMIT 1', 'Quest Item'],
            ['SELECT `entry` FROM `npc_vendor` WHERE `item` = ? LIMIT 1', $isPvpReward ? 'PvP Reward' : 'Vendor'],
            ['SELECT `entry` FROM `npc_vendor_template` WHERE `item` = ? LIMIT 1', $isPvpReward ? 'PvP Reward' : 'Vendor'],
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
                    return trim((string)$instanceLoot['name_en_gb'] . ' - ' . (string)$instanceInfo['name_en_gb']);
                }
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
                    return trim((string)$instanceLoot['name_en_gb'] . ' - ' . (string)$instanceInfo['name_en_gb']);
                }
            }
            return 'Dropped Item';
        }

        $questRewardStmt = $worldPdo->prepare('SELECT `entry` FROM `quest_template` WHERE `RewItemId1` = ? OR `RewItemId2` = ? OR `RewItemId3` = ? OR `RewItemId4` = ? LIMIT 1');
        $questRewardStmt->execute([$itemId, $itemId, $itemId, $itemId]);
        if ($questRewardStmt->fetchColumn()) {
            return 'Quest Reward';
        }

        return 'World / Crafted';
    }
}

if (!function_exists('spp_item_database_fetch')) {
    function spp_item_database_fetch($realmId, array $filters, array $config)
    {
        if (($filters['type'] ?? 'all') === 'sets') {
            return spp_item_database_sets_fetch($realmId, $filters, $config);
        }

        $counts = spp_item_database_search_counts($realmId, $filters['search'], $config);
        if (($filters['type'] ?? 'all') !== 'items') {
            return spp_item_database_generic_search($realmId, (string)$filters['type'], $filters, $config);
        }

        $realmMap = $GLOBALS['realmDbMap'] ?? null;
        if (!is_array($realmMap) || empty($realmMap[$realmId])) {
            return [
                'error' => 'The requested realm could not be loaded.',
                'filters' => $filters,
                'rows' => [],
                'total' => 0,
                'page_count' => 1,
                'per_page' => (int)$filters['per_page'],
                'summary' => [],
                'counts' => $counts,
                'sections' => [],
            ];
        }

        $minSearchLength = SPP_ITEM_DATABASE_MIN_SEARCH_LENGTH;
        $normalizedSearch = spp_item_database_normalize_search($filters['search']);
        if ($filters['search'] !== '' && ($normalizedSearch === '' || strlen($normalizedSearch) < $minSearchLength)) {
            return [
                'error' => 'Search must be at least ' . $minSearchLength . ' characters and use letters, numbers, spaces, apostrophes, or dashes only.',
                'filters' => $filters,
                'rows' => [],
                'total' => 0,
                'page_count' => 1,
                'per_page' => (int)$filters['per_page'],
                'summary' => [],
                'counts' => $counts,
                'sections' => [],
            ];
        }
        $filters['search'] = $normalizedSearch;

        try {
            $worldPdo = spp_get_pdo('world', $realmId);
            $armoryPdo = spp_get_pdo('armory', $realmId);
            $localeId = isset($config['locales']) ? (int)$config['locales'] : 0;
            $localeField = $localeId > 0 ? 'name_loc' . $localeId : null;
            $joinSql = $localeField ? ' LEFT JOIN `locales_item` li ON li.`entry` = it.`entry`' : '';
            $displayNameSql = $localeField ? 'COALESCE(li.`' . $localeField . '`, it.`name`)' : 'it.`name`';

            $whereData = spp_item_database_build_where($filters, $localeField);
            $orderSql = spp_item_database_sort_sql($filters['sort'], $filters['dir'], $filters['search'] !== '');

            $countSql = 'SELECT COUNT(*) FROM `item_template` it' . $joinSql . $whereData['sql'];
            $countStmt = $worldPdo->prepare($countSql);
            foreach ($whereData['params'] as $name => $value) {
                $countStmt->bindValue(':' . $name, $value);
            }
            $countStmt->execute();
            $total = (int)$countStmt->fetchColumn();

            $perPage = (int)$filters['per_page'];
            $pageCount = max(1, (int)ceil($total / max(1, $perPage)));
            $page = min(max(1, (int)$filters['p']), $pageCount);
            $offset = ($page - 1) * $perPage;
            $filters['p'] = $page;

            $sql = 'SELECT it.`entry`, it.`name`, it.`ItemLevel`, it.`RequiredLevel`, it.`Quality`, it.`Flags`, it.`displayid`, it.`InventoryType`, it.`class`, it.`description`, ' . $displayNameSql . ' AS `display_name` FROM `item_template` it' . $joinSql . $whereData['sql'] . ' ORDER BY ' . $orderSql . ' LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset;
            $stmt = $worldPdo->prepare($sql);
            foreach ($whereData['params'] as $name => $value) {
                $stmt->bindValue(':' . $name, $value);
            }
            $stmt->execute();
            $rawRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $displayIds = [];
            foreach ($rawRows as $row) {
                $displayId = (int)($row['displayid'] ?? 0);
                if ($displayId > 0) {
                    $displayIds[$displayId] = $displayId;
                }
            }

            $iconMap = [];
            if ($displayIds) {
                $placeholders = implode(',', array_fill(0, count($displayIds), '?'));
                $iconStmt = $armoryPdo->prepare('SELECT `id`, `name` FROM `dbc_itemdisplayinfo` WHERE `id` IN (' . $placeholders . ')');
                $iconStmt->execute(array_values($displayIds));
                foreach ($iconStmt->fetchAll(PDO::FETCH_ASSOC) as $iconRow) {
                    $iconMap[(int)$iconRow['id']] = (string)$iconRow['name'];
                }
            }

            $rows = [];
            $summary = [
                'epic' => 0,
                'rare' => 0,
                'weapon' => 0,
                'armor' => 0,
            ];

            foreach ($rawRows as $row) {
                $quality = (int)($row['Quality'] ?? 0);
                $classId = (int)($row['class'] ?? 0);
                if ($quality >= 4) {
                    $summary['epic']++;
                } elseif ($quality === 3) {
                    $summary['rare']++;
                }
                if ($classId === 2) {
                    $summary['weapon']++;
                } elseif ($classId === 4) {
                    $summary['armor']++;
                }

                $rows[] = [
                    'id' => (int)$row['entry'],
                    'name' => (string)($row['display_name'] ?? $row['name']),
                    'quality' => $quality,
                    'quality_label' => spp_item_database_quality_label($quality),
                    'quality_color' => spp_item_database_quality_color($quality),
                    'level' => (int)($row['ItemLevel'] ?? 0),
                    'required_level' => (int)($row['RequiredLevel'] ?? 0),
                    'slot_name' => spp_item_database_inventory_type_name((int)($row['InventoryType'] ?? 0)),
                    'class_name' => spp_item_database_class_label($classId),
                    'description' => trim((string)($row['description'] ?? '')),
                    'source' => spp_item_database_cache_source($worldPdo, $armoryPdo, (int)$row['entry'], (((int)($row['Flags'] ?? 0) & 32768) === 32768)),
                    'icon' => spp_item_database_icon_url($iconMap[(int)($row['displayid'] ?? 0)] ?? ''),
                ];
            }

            return [
                'error' => '',
                'filters' => $filters,
                'rows' => $rows,
                'total' => $total,
                'page_count' => $pageCount,
                'per_page' => $perPage,
                'summary' => $summary,
                'counts' => $counts,
                'sections' => [],
            ];
        } catch (Throwable $e) {
            return [
                'error' => 'The item database could not be loaded from the realm databases.',
                'filters' => $filters,
                'rows' => [],
                'total' => 0,
                'page_count' => 1,
                'per_page' => (int)$filters['per_page'],
                'summary' => [],
                'counts' => $counts,
                'sections' => [],
            ];
        }
    }
}
