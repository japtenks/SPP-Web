<?php

require_once dirname(__DIR__, 2) . '/app/server/realm-capabilities.php';

if (!function_exists('spp_shared_item_set_realm_id')) {
    function spp_shared_item_set_realm_id($realmId = null): int
    {
        $resolved = (int)$realmId;
        if ($resolved > 0) {
            return $resolved;
        }

        $globalRealmId = (int)($GLOBALS['realmId'] ?? 0);
        if ($globalRealmId > 0) {
            return $globalRealmId;
        }

        $requestRealmId = isset($_GET['realm']) ? (int)$_GET['realm'] : 0;
        return $requestRealmId > 0 ? $requestRealmId : 1;
    }
}

if (!function_exists('spp_shared_item_set_slot_order')) {
    function spp_shared_item_set_slot_order(int $inventoryType): int
    {
        $map = [
            1 => 1,
            2 => 2,
            3 => 3,
            5 => 4,
            6 => 5,
            7 => 6,
            8 => 7,
            9 => 8,
            10 => 9,
            11 => 10,
            12 => 11,
            16 => 12,
            13 => 13,
            21 => 14,
            22 => 15,
        ];

        return $map[$inventoryType] ?? 99;
    }
}

if (!function_exists('spp_shared_item_set_fetch_all')) {
    function spp_shared_item_set_fetch_all(string $database, string $sql, array $params, $realmId = null): array
    {
        $realmId = spp_shared_item_set_realm_id($realmId);

        if (function_exists('spp_get_pdo')) {
            try {
                $pdo = spp_get_pdo($database, $realmId);
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable $e) {
            }
        }

        if (!empty($params)) {
            return [];
        }

        if ($database === 'world' && function_exists('world_query')) {
            return (array)(world_query($sql, 0) ?: []);
        }
        if ($database === 'armory' && function_exists('armory_query')) {
            return (array)(armory_query($sql, 0) ?: []);
        }

        return [];
    }
}

if (!function_exists('spp_shared_item_set_fetch_one')) {
    function spp_shared_item_set_fetch_one(string $database, string $sql, array $params, $realmId = null): ?array
    {
        $rows = spp_shared_item_set_fetch_all($database, $sql, $params, $realmId);
        return isset($rows[0]) && is_array($rows[0]) ? $rows[0] : null;
    }
}

if (!function_exists('spp_shared_item_set_fetch_one_from_candidates')) {
    function spp_shared_item_set_fetch_one_from_candidates(array $databases, string $tableName, string $sql, array $params, $realmId = null): ?array
    {
        $realmId = spp_shared_item_set_realm_id($realmId);
        foreach ($databases as $database) {
            try {
                if (function_exists('spp_get_pdo')) {
                    $pdo = spp_get_pdo((string)$database, $realmId);
                    if ($pdo instanceof PDO && spp_realm_capability_table_exists($pdo, $tableName)) {
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        if (is_array($row)) {
                            return $row;
                        }
                    }
                }
            } catch (Throwable $e) {
            }
        }

        return null;
    }
}

if (!function_exists('spp_shared_item_set_fetch_all_from_candidates')) {
    function spp_shared_item_set_fetch_all_from_candidates(array $databases, string $tableName, string $sql, array $params, $realmId = null): array
    {
        $realmId = spp_shared_item_set_realm_id($realmId);
        foreach ($databases as $database) {
            try {
                if (function_exists('spp_get_pdo')) {
                    $pdo = spp_get_pdo((string)$database, $realmId);
                    if ($pdo instanceof PDO && spp_realm_capability_table_exists($pdo, $tableName)) {
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute($params);
                        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if (is_array($rows) && !empty($rows)) {
                            return $rows;
                        }
                        return [];
                    }
                }
            } catch (Throwable $e) {
            }
        }

        return [];
    }
}

if (!function_exists('spp_shared_item_set_duration_seconds')) {
    function spp_shared_item_set_duration_seconds(int $durationId, $realmId = null): int
    {
        $durationId = (int)$durationId;
        if ($durationId <= 0) {
            return 0;
        }

        $row = spp_shared_item_set_fetch_one_from_candidates(
            ['armory', 'world'],
            'dbc_spellduration',
            'SELECT `duration1`, `duration2` FROM `dbc_spellduration` WHERE `id` = ? LIMIT 1',
            [$durationId],
            $realmId
        );
        if (!$row) {
            return 0;
        }

        return (int)(max((int)($row['duration1'] ?? 0), (int)($row['duration2'] ?? 0)) / 1000);
    }
}

if (!function_exists('spp_shared_item_set_radius_text')) {
    function spp_shared_item_set_radius_text(int $radiusId, $realmId = null): string
    {
        $radiusId = (int)$radiusId;
        if ($radiusId <= 0) {
            return '0';
        }

        $row = spp_shared_item_set_fetch_one_from_candidates(
            ['armory', 'world'],
            'dbc_spellradius',
            'SELECT `radius1` FROM `dbc_spellradius` WHERE `id` = ? LIMIT 1',
            [$radiusId],
            $realmId
        );
        if (!$row) {
            return '0';
        }

        $formatted = number_format((float)($row['radius1'] ?? 0), 1, '.', '');
        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }
}

if (!function_exists('spp_shared_item_set_bonus_description')) {
    function spp_shared_item_set_bonus_description(array $spell, $realmId = null): string
    {
        $description = trim((string)($spell['description'] ?? ''));
        if ($description === '') {
            return '';
        }

        if (function_exists('spp_item_tooltip_replace_spell_tokens')) {
            return trim((string)spp_item_tooltip_replace_spell_tokens($description, $spell));
        }

        $realmId = spp_shared_item_set_realm_id($realmId);

        $format = static function ($value): string {
            $formatted = number_format((float)$value, 1, '.', '');
            return rtrim(rtrim($formatted, '0'), '.') ?: '0';
        };

        $formatEffect = static function (array $spellRow, int $index): array {
            $basePoints = (int)($spellRow['effect_basepoints_' . $index] ?? 0);
            $dieSides = (int)($spellRow['effect_die_sides_' . $index] ?? 0);
            $min = $basePoints + 1;
            if ($dieSides <= 1) {
                return [$min, $min, (string)abs($min)];
            }

            $max = $basePoints + $dieSides;
            if ($max < $min) {
                $swap = $min;
                $min = $max;
                $max = $swap;
            }

            return [$min, $max, $min . ' to ' . $max];
        };

        $formatDuration = static function (int $seconds): string {
            if ($seconds <= 0) {
                return '0 sec';
            }
            if ($seconds >= 60) {
                $minutes = (int)floor($seconds / 60);
                $remainder = $seconds % 60;
                return $minutes . ' min' . ($remainder > 0 ? ' ' . $remainder . ' sec' : '');
            }
            return $seconds . ' sec';
        };

        $dotValue = static function (array $spellRow, int $index, int $durationSeconds): int {
            $basePoints = abs((int)($spellRow['effect_basepoints_' . $index] ?? 0) + 1);
            $amplitude = (int)($spellRow['effect_amplitude_' . $index] ?? 0);
            $ticks = ($amplitude > 0 && $durationSeconds > 0) ? (int)floor(($durationSeconds * 1000) / $amplitude) : 0;
            return $ticks > 0 ? ($basePoints * $ticks) : $basePoints;
        };

        [$s1Min, $s1Max, $s1Text] = $formatEffect($spell, 1);
        [$s2Min, $s2Max, $s2Text] = $formatEffect($spell, 2);
        [$s3Min, $s3Max, $s3Text] = $formatEffect($spell, 3);

        $durationSeconds = spp_shared_item_set_duration_seconds((int)($spell['ref_spellduration'] ?? 0), $realmId);
        $durationText = $formatDuration($durationSeconds);
        $radiusText = spp_shared_item_set_radius_text((int)($spell['effect_radius_index_1'] ?? 0), $realmId);

        $o1 = $dotValue($spell, 1, $durationSeconds);
        $o2 = $dotValue($spell, 2, $durationSeconds);
        $o3 = $dotValue($spell, 3, $durationSeconds);

        $headline = (int)($spell['proc_chance'] ?? 0);
        if ($headline <= 0) {
            $headline = (int)$s1Min;
        }

        $stacks = (int)($spell['stack_amount'] ?? 0);
        if ($stacks <= 0) {
            $stacks = max(1, abs((int)($spell['effect_basepoints_1'] ?? 0) + 1));
        }

        $t1 = $format(((int)($spell['effect_amplitude_1'] ?? 0)) / 1000);
        $t2 = $format(((int)($spell['effect_amplitude_2'] ?? 0)) / 1000);
        $t3 = $format(((int)($spell['effect_amplitude_3'] ?? 0)) / 1000);

        $externalSpellRow = static function (int $sourceRealmId, int $spellId): ?array {
            if ($spellId <= 0) {
                return null;
            }

            return spp_shared_item_set_fetch_one_from_candidates(
                ['armory', 'world'],
                'dbc_spell',
                'SELECT * FROM `dbc_spell` WHERE `id` = ? LIMIT 1',
                [$spellId],
                $sourceRealmId
            );
        };

        $externalEffectValues = static function (int $sourceRealmId, int $spellId, int $index) use ($formatEffect, $externalSpellRow): array {
            $row = $externalSpellRow($sourceRealmId, $spellId);
            return $row ? $formatEffect($row, $index) : [0, 0, '0'];
        };

        $externalDotValue = static function (int $sourceRealmId, int $spellId, int $index) use ($dotValue, $externalSpellRow): int {
            $row = $externalSpellRow($sourceRealmId, $spellId);
            if (!$row) {
                return 0;
            }

            $durationSeconds = spp_shared_item_set_duration_seconds((int)($row['ref_spellduration'] ?? 0), $sourceRealmId);
            return $dotValue($row, $index, $durationSeconds);
        };

        $formatDividedEffect = static function (float $min, float $max) use ($format): string {
            $min = abs($min);
            $max = abs($max);
            if ($max < $min) {
                $swap = $min;
                $min = $max;
                $max = $swap;
            }

            if ($max > $min) {
                return (string)((int)floor($min)) . ' to ' . (string)((int)ceil($max));
            }

            return $format($min);
        };

        $description = strtr($description, [
            '$s1' => $s1Text,
            '$s2' => $s2Text,
            '$s3' => $s3Text,
            '$m1' => (string)$s1Min,
            '$m2' => (string)$s2Min,
            '$m3' => (string)$s3Min,
            '$m' => (string)$s1Min,
            '$o1' => (string)$o1,
            '$o2' => (string)$o2,
            '$o3' => (string)$o3,
            '$t1' => $t1,
            '$t2' => $t2,
            '$t3' => $t3,
            '$a1' => $radiusText,
            '$d' => $durationText,
            '$D' => $durationText,
            '$h1' => (string)$headline,
            '$h' => (string)$headline,
            '$u' => (string)$stacks,
        ]);

        $description = preg_replace_callback(
            '/\$\s*\/\s*(-?\d+(?:\.\d+)?)\s*;\s*(?:\$?(\d+))?(s|o)([1-3])/i',
            static function (array $matches) use (
                $realmId,
                $s1Min,
                $s1Max,
                $s2Min,
                $s2Max,
                $s3Min,
                $s3Max,
                $o1,
                $o2,
                $o3,
                $format,
                $formatDividedEffect,
                $externalEffectValues,
                $externalDotValue
            ): string {
                $divisor = (float)($matches[1] ?? 0);
                if ($divisor == 0.0) {
                    return '0';
                }

                $spellId = isset($matches[2]) ? (int)$matches[2] : 0;
                $tokenType = strtolower((string)($matches[3] ?? 's'));
                $index = (int)($matches[4] ?? 1);

                if ($tokenType === 's') {
                    if ($spellId > 0) {
                        [$min, $max] = $externalEffectValues($realmId, $spellId, $index);
                    } else {
                        $minMap = [1 => $s1Min, 2 => $s2Min, 3 => $s3Min];
                        $maxMap = [1 => $s1Max, 2 => $s2Max, 3 => $s3Max];
                        $min = (float)($minMap[$index] ?? 0);
                        $max = (float)($maxMap[$index] ?? $min);
                    }

                    return $formatDividedEffect($min / $divisor, $max / $divisor);
                }

                if ($spellId > 0) {
                    $value = $externalDotValue($realmId, $spellId, $index);
                } else {
                    $valueMap = [1 => $o1, 2 => $o2, 3 => $o3];
                    $value = (float)($valueMap[$index] ?? 0);
                }

                return $format(abs((float)$value / $divisor));
            },
            $description
        );

        $description = preg_replace_callback(
            '/\$\{\s*\$m([1-3])\s*\/\s*(-?\d+(?:\.\d+)?)\s*\}/i',
            static function (array $matches) use ($s1Min, $s2Min, $s3Min, $format): string {
                $divisor = (float)($matches[2] ?? 0);
                if ($divisor == 0.0) {
                    return '0';
                }

                $valueMap = [1 => $s1Min, 2 => $s2Min, 3 => $s3Min];
                $index = (int)($matches[1] ?? 1);
                return $format(abs((float)($valueMap[$index] ?? 0) / $divisor));
            },
            $description
        );

        while (preg_match('/\$l([^:;]+):([^;]+);/', $description, $matches, PREG_OFFSET_CAPTURE)) {
            $full = $matches[0][0];
            $offset = $matches[0][1];
            $singular = $matches[1][0];
            $plural = $matches[2][0];
            $before = substr($description, 0, $offset);
            $value = 2.0;
            if (preg_match('/(\d+(?:\.\d+)?)(?!.*\d)/', $before, $numberMatches)) {
                $value = (float)$numberMatches[1];
            }
            $word = (abs($value - 1.0) < 0.000001) ? $singular : $plural;
            $description = substr($description, 0, $offset) . $word . substr($description, $offset + strlen($full));
        }

        $description = preg_replace('/\s+%/', '%', $description);
        $description = preg_replace('/\$(?=\d)(\d+(?:\.\d+)?)\b/', '$1', $description);
        $description = preg_replace('/\s{2,}/', ' ', $description);

        return trim((string)$description);
    }
}

if (!function_exists('spp_shared_get_itemset_data')) {
    function spp_shared_get_itemset_data(int $setId, $realmId = null): array
    {
        $setId = (int)$setId;
        $realmId = spp_shared_item_set_realm_id($realmId);
        if ($setId <= 0) {
            return ['id' => $setId, 'name' => 'Unknown Set', 'items' => [], 'bonuses' => []];
        }

        $row = spp_shared_item_set_fetch_one_from_candidates(
            ['armory', 'world'],
            'dbc_itemset',
            'SELECT * FROM `dbc_itemset` WHERE `id` = ? LIMIT 1',
            [$setId],
            $realmId
        );
        if (!$row) {
            return ['id' => $setId, 'name' => 'Unknown Set', 'items' => [], 'bonuses' => []];
        }

        $itemIds = [];
        for ($i = 1; $i <= 10; $i++) {
            $itemId = (int)($row['item_' . $i] ?? 0);
            if ($itemId > 0) {
                $itemIds[] = $itemId;
            }
        }

        $itemRows = [];
        if ($itemIds) {
            $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
            $worldPdo = null;
            if (function_exists('spp_get_pdo')) {
                try {
                    $worldPdo = spp_get_pdo('world', $realmId);
                } catch (Throwable $e) {
                    $worldPdo = null;
                }
            }
            $inventoryColumn = $worldPdo instanceof PDO ? (spp_realm_capability_pick_column($worldPdo, 'item_template', ['InventoryType', 'inventory_type']) ?? 'InventoryType') : 'InventoryType';
            $displayColumn = $worldPdo instanceof PDO ? (spp_realm_capability_pick_column($worldPdo, 'item_template', ['displayid', 'display_id']) ?? 'displayid') : 'displayid';
            $qualityColumn = $worldPdo instanceof PDO ? (spp_realm_capability_pick_column($worldPdo, 'item_template', ['Quality', 'quality']) ?? 'Quality') : 'Quality';
            $rows = spp_shared_item_set_fetch_all(
                'world',
                "SELECT `entry`, `name`, `{$inventoryColumn}` AS `InventoryType`, `{$displayColumn}` AS `displayid`, `{$qualityColumn}` AS `Quality` FROM `item_template` WHERE `entry` IN ({$placeholders})",
                $itemIds,
                $realmId
            );
            foreach ($rows as $itemRow) {
                $itemRows[(int)$itemRow['entry']] = $itemRow;
            }
        }

        $displayIds = [];
        foreach ($itemRows as $itemRow) {
            $displayId = (int)($itemRow['displayid'] ?? 0);
            if ($displayId > 0) {
                $displayIds[$displayId] = $displayId;
            }
        }

        $iconByDisplayId = [];
        if ($displayIds) {
            $displayIdList = array_keys($displayIds);
            $placeholders = implode(',', array_fill(0, count($displayIdList), '?'));
            $rows = spp_shared_item_set_fetch_all_from_candidates(
                ['armory', 'world'],
                'dbc_itemdisplayinfo',
                "SELECT `id`, `name` FROM `dbc_itemdisplayinfo` WHERE `id` IN ({$placeholders})",
                $displayIdList,
                $realmId
            );
            foreach ($rows as $iconRow) {
                $iconByDisplayId[(int)$iconRow['id']] = !empty($iconRow['name'])
                    ? strtolower(pathinfo((string)$iconRow['name'], PATHINFO_FILENAME))
                    : 'inv_misc_questionmark';
            }
        }

        $items = [];
        for ($i = 1; $i <= 10; $i++) {
            $itemId = (int)($row['item_' . $i] ?? 0);
            if ($itemId <= 0 || !isset($itemRows[$itemId])) {
                continue;
            }

            $itemRow = $itemRows[$itemId];
            $displayId = (int)($itemRow['displayid'] ?? 0);
            $items[] = [
                'entry' => (int)$itemRow['entry'],
                'slot' => (int)($itemRow['InventoryType'] ?? 0),
                'name' => (string)($itemRow['name'] ?? ''),
                'icon' => $iconByDisplayId[$displayId] ?? 'inv_misc_questionmark',
                'q' => (int)($itemRow['Quality'] ?? 0),
            ];
        }

        usort($items, static function (array $left, array $right): int {
            return spp_shared_item_set_slot_order((int)($left['slot'] ?? 0))
                <=> spp_shared_item_set_slot_order((int)($right['slot'] ?? 0));
        });

        $bonusMeta = [];
        $bonusSpellIds = [];
        for ($b = 1; $b <= 8; $b++) {
            $bonusId = (int)($row['bonus_' . $b] ?? 0);
            $pieces = (int)($row['pieces_' . $b] ?? 0);
            if ($bonusId > 0 && $pieces > 0) {
                $bonusMeta[] = ['id' => $bonusId, 'pieces' => $pieces];
                $bonusSpellIds[] = $bonusId;
            }
        }

        $spellRows = [];
        if ($bonusSpellIds) {
            $placeholders = implode(',', array_fill(0, count($bonusSpellIds), '?'));
            $rows = spp_shared_item_set_fetch_all_from_candidates(
                ['armory', 'world'],
                'dbc_spell',
                "SELECT * FROM `dbc_spell` WHERE `id` IN ({$placeholders})",
                $bonusSpellIds,
                $realmId
            );
            foreach ($rows as $spellRow) {
                $spellRows[(int)$spellRow['id']] = $spellRow;
            }
        }

        $spellIconIds = [];
        foreach ($spellRows as $spellRow) {
            $iconId = (int)($spellRow['ref_spellicon'] ?? 0);
            if ($iconId > 0) {
                $spellIconIds[$iconId] = $iconId;
            }
        }

        $spellIconMap = [];
        if ($spellIconIds) {
            $spellIconIdList = array_keys($spellIconIds);
            $placeholders = implode(',', array_fill(0, count($spellIconIdList), '?'));
            $rows = spp_shared_item_set_fetch_all_from_candidates(
                ['armory', 'world'],
                'dbc_spellicon',
                "SELECT `id`, `name` FROM `dbc_spellicon` WHERE `id` IN ({$placeholders})",
                $spellIconIdList,
                $realmId
            );
            foreach ($rows as $iconRow) {
                $spellIconMap[(int)$iconRow['id']] = !empty($iconRow['name'])
                    ? strtolower(preg_replace('/[^a-z0-9_]/i', '', (string)$iconRow['name']))
                    : 'inv_misc_key_01';
            }
        }

        $bonuses = [];
        foreach ($bonusMeta as $meta) {
            $spellId = (int)$meta['id'];
            if (!isset($spellRows[$spellId])) {
                continue;
            }

            $spell = $spellRows[$spellId];
            $iconId = (int)($spell['ref_spellicon'] ?? 0);
            $bonuses[] = [
                'pieces' => (int)$meta['pieces'],
                'name' => (string)($spell['name'] ?? ''),
                'raw_desc' => (string)($spell['description'] ?? ''),
                'resolved_desc' => spp_shared_item_set_bonus_description($spell, $realmId),
                'icon' => $iconId > 0 ? ($spellIconMap[$iconId] ?? 'inv_misc_key_01') : 'inv_misc_key_01',
                'spell' => $spell,
            ];
        }

        return [
            'id' => $setId,
            'name' => (string)($row['name'] ?? 'Unknown Set'),
            'items' => $items,
            'bonuses' => $bonuses,
        ];
    }
}
