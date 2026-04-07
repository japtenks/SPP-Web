<?php

if (!function_exists('sets_normalize_name')) {
    function sets_normalize_name(string $name): string
    {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '', trim($name)));
    }
}

if (!function_exists('sets_find_entry_by_name')) {
    function sets_find_entry_by_name(array $setDataMap, string $requestedName): ?array
    {
        $needle = sets_normalize_name($requestedName);
        if ($needle === '') {
            return null;
        }

        foreach ($setDataMap as $key => $entry) {
            $candidateName = trim((string)($entry['data']['name'] ?? $key));
            if ($candidateName === '') {
                continue;
            }
            if (sets_normalize_name($candidateName) === $needle || sets_normalize_name((string)$key) === $needle) {
                return $entry;
            }
        }

        return null;
    }
}

if (!function_exists('sets_build_focus_href')) {
    function sets_build_focus_href(string $setName, ?string $sectionName = null, ?string $className = null, ?int $targetRealmId = null): string
    {
        $sectionName = strtolower(trim((string)($sectionName ?? ($_GET['section'] ?? 'misc'))));
        if (!in_array($sectionName, array('misc', 'world', 'pvp'), true)) {
            $sectionName = 'misc';
        }

        $className = trim((string)($className ?? ($_GET['class'] ?? '')));
        $targetRealmId = (int)($targetRealmId ?: ($_GET['realm'] ?? 1));

        return function_exists('spp_sets_detail_url')
            ? spp_sets_detail_url($targetRealmId, $setName, array('section' => $sectionName, 'class' => $className))
            : ('index.php?n=server&sub=item&type=sets&set_section=' . urlencode($sectionName) . '&set_class=' . urlencode($className) . '&realm=' . $targetRealmId . '&set=' . urlencode($setName));
    }
}

if (!function_exists('sets_build_data_map')) {
    function sets_build_data_map(array $setNames): array
    {
        $result = [];
        $setNames = array_values(array_unique(array_filter(array_map('trim', $setNames), 'strlen')));
        if (empty($setNames)) {
            return $result;
        }

        $allSetRows = armory_query("SELECT * FROM dbc_itemset", 0);
        if (!is_array($allSetRows)) {
            $allSetRows = [];
        }

        $exactRows = [];
        $normalizedRows = [];
        foreach ($allSetRows as $row) {
            $rowName = trim((string)($row['name'] ?? ''));
            if ($rowName === '') {
                continue;
            }
            $normalized = sets_normalize_name($rowName);
            $exactRows[$normalized] = $row;
            $normalizedRows[] = ['norm' => $normalized, 'row' => $row];
        }

        $matchedRows = [];
        foreach ($setNames as $name) {
            $normalized = sets_normalize_name($name);
            $matched = $exactRows[$normalized] ?? null;
            if ($matched === null) {
                foreach ($normalizedRows as $candidate) {
                    if (strpos($candidate['norm'], $normalized) !== false) {
                        $matched = $candidate['row'];
                        break;
                    }
                }
            }
            if ($matched !== null) {
                $matchedRows[$name] = $matched;
            }
        }

        $itemIds = [];
        $spellIds = [];
        foreach ($matchedRows as $row) {
            for ($i = 1; $i <= 10; $i++) {
                $itemId = (int)($row["item_$i"] ?? 0);
                if ($itemId > 0) {
                    $itemIds[$itemId] = $itemId;
                }
            }
            for ($i = 1; $i <= 8; $i++) {
                $spellId = (int)($row["bonus_$i"] ?? 0);
                $pieces = (int)($row["pieces_$i"] ?? 0);
                if ($spellId > 0 && $pieces > 0) {
                    $spellIds[$spellId] = $spellId;
                }
            }
        }

        $itemRows = [];
        if (!empty($itemIds)) {
            $batch = world_query(
                "SELECT entry,name,InventoryType,displayid,Quality FROM item_template WHERE entry IN (" . implode(',', $itemIds) . ")",
                0
            );
            if (is_array($batch)) {
                foreach ($batch as $row) {
                    $itemRows[(int)$row['entry']] = $row;
                }
            }
        }

        $displayIds = [];
        foreach ($itemRows as $row) {
            $displayId = (int)($row['displayid'] ?? 0);
            if ($displayId > 0) {
                $displayIds[$displayId] = $displayId;
            }
        }

        $displayIcons = [];
        if (!empty($displayIds)) {
            $batch = armory_query(
                "SELECT id,name FROM dbc_itemdisplayinfo WHERE id IN (" . implode(',', $displayIds) . ")",
                0
            );
            if (is_array($batch)) {
                foreach ($batch as $row) {
                    $displayIcons[(int)$row['id']] = !empty($row['name'])
                        ? strtolower(pathinfo($row['name'], PATHINFO_FILENAME))
                        : 'inv_misc_key_02';
                }
            }
        }

        $spellRows = [];
        if (!empty($spellIds)) {
            $batch = armory_query(
                "SELECT * FROM dbc_spell WHERE id IN (" . implode(',', $spellIds) . ")",
                0
            );
            if (is_array($batch)) {
                foreach ($batch as $row) {
                    $spellRows[(int)$row['id']] = $row;
                }
            }
        }

        $spellIconIds = [];
        foreach ($spellRows as $row) {
            $spellIconId = (int)($row['ref_spellicon'] ?? 0);
            if ($spellIconId > 0) {
                $spellIconIds[$spellIconId] = $spellIconId;
            }
        }

        $spellIcons = [];
        if (!empty($spellIconIds)) {
            $batch = armory_query(
                "SELECT id,name FROM dbc_spellicon WHERE id IN (" . implode(',', $spellIconIds) . ")",
                0
            );
            if (is_array($batch)) {
                foreach ($batch as $row) {
                    $spellIcons[(int)$row['id']] = !empty($row['name'])
                        ? strtolower(preg_replace('/[^a-z0-9_]/i', '', $row['name']))
                        : 'inv_misc_key_01';
                }
            }
        }

        foreach ($setNames as $name) {
            $row = $matchedRows[$name] ?? null;
            if (!is_array($row)) {
                $result[$name] = [
                    'id' => 0,
                    'data' => [],
                    'tipHtml' => '',
                ];
                continue;
            }

            $items = [];
            for ($i = 1; $i <= 10; $i++) {
                $itemId = (int)($row["item_$i"] ?? 0);
                if ($itemId <= 0 || !isset($itemRows[$itemId])) {
                    continue;
                }
                $item = $itemRows[$itemId];
                $displayId = (int)($item['displayid'] ?? 0);
                $items[] = [
                    'entry' => (int)$item['entry'],
                    'slot' => (int)$item['InventoryType'],
                    'name' => (string)$item['name'],
                    'icon' => $displayIcons[$displayId] ?? 'inv_misc_questionmark',
                    'q' => (int)$item['Quality'],
                ];
            }
            usort($items, function ($left, $right) {
                return slot_order($left['slot']) <=> slot_order($right['slot']);
            });

            $bonuses = [];
            for ($i = 1; $i <= 8; $i++) {
                $spellId = (int)($row["bonus_$i"] ?? 0);
                $pieces = (int)($row["pieces_$i"] ?? 0);
                if ($spellId <= 0 || $pieces <= 0 || !isset($spellRows[$spellId])) {
                    continue;
                }
                $spell = $spellRows[$spellId];
                $spellIconId = (int)($spell['ref_spellicon'] ?? 0);
                $bonuses[] = [
                    'pieces' => $pieces,
                    'name' => (string)($spell['name'] ?? ''),
                    'desc' => (string)($spell['description'] ?? ''),
                    'icon' => $spellIcons[$spellIconId] ?? 'inv_misc_key_01',
                    'spell' => $spell,
                ];
            }

            $data = [
                'id' => (int)$row['id'],
                'name' => (string)($row['name'] ?? $name),
                'items' => $items,
                'bonuses' => $bonuses,
            ];

            $result[$name] = [
                'id' => (int)$row['id'],
                'data' => $data,
                'tipHtml' => !empty($data) ? render_set_bonus_tip_html($data) : '',
            ];
        }

        return $result;
    }
}

if (!function_exists('sets_colorize_desc')) {
    function sets_colorize_desc($text)
    {
        $text = str_replace(['<b>', '</b>'], '', $text);
        $classesToCSS = [
            'Warrior' => 'is-warrior',
            'Paladin' => 'is-paladin',
            'Hunter' => 'is-hunter',
            'Rogue' => 'is-rogue',
            'Priest' => 'is-priest',
            'Shaman' => 'is-shaman',
            'Mage' => 'is-mage',
            'Warlock' => 'is-warlock',
            'Druid' => 'is-druid',
            'Death Knight' => 'is-dk',
        ];

        return preg_replace_callback(
            '/\b(' . implode('|', array_map('preg_quote', array_keys($classesToCSS))) . ')(s)?\b/i',
            function ($m) use ($classesToCSS) {
                $name = ucwords(strtolower($m[1]));
                if (strtolower($m[1]) === 'death knight') {
                    $name = 'Death Knight';
                }
                $css = $classesToCSS[$name] ?? '';
                $suffix = $m[2] ?? '';
                return "<span class='{$css}'><b>{$name}{$suffix}</b></span>";
            },
            $text
        );
    }
}
