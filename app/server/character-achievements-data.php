<?php

if (!function_exists('spp_character_load_achievement_state')) {
    function spp_character_load_achievement_state(array $args): array
    {
        $characterGuid = (int)($args['character_guid'] ?? 0);
        $charsPdo = $args['chars_pdo'] ?? null;
        $worldPdo = $args['world_pdo'] ?? null;
        $armoryPdo = $args['armory_pdo'] ?? null;
        $achievementSummary = (array)($args['achievement_summary'] ?? []);

        if (
            !$charsPdo instanceof PDO ||
            !$worldPdo instanceof PDO ||
            !$armoryPdo instanceof PDO ||
            $characterGuid <= 0 ||
            !spp_character_table_exists($charsPdo, 'character_achievement')
        ) {
            return array(
                'achievement_summary' => $achievementSummary,
            );
        }

        $achievementSourcePdo = null;
        $achievementQuerySql = '';
        $achievementIconIdField = '';
        $achievementIdField = 'id';
        $achievementNameField = 'name';
        $achievementDescriptionField = 'description';
        $achievementPointsField = 'points';
        $achievementCategoryField = 'category_id';
        $achievementCategoryMap = array();

        if (spp_character_table_exists($worldPdo, 'achievement_dbc')) {
            $achievementSourcePdo = $worldPdo;
            $achievementQuerySql =
                'SELECT a.`ID` AS `id`, a.`Title_Lang_enUS` AS `name`, a.`Description_Lang_enUS` AS `description`, ' .
                'a.`Points` AS `points`, a.`Category` AS `category_id`, a.`IconID` AS `icon_id` ' .
                'FROM `achievement_dbc` a WHERE a.`ID` IN (%s)';
            $achievementIconIdField = 'icon_id';

            if (spp_character_table_exists($worldPdo, 'achievement_category_dbc')) {
                foreach ($worldPdo->query('SELECT `ID`, `Name_Lang_enUS`, `Parent` FROM `achievement_category_dbc`')->fetchAll(PDO::FETCH_ASSOC) as $categoryRow) {
                    $achievementCategoryMap[(int)$categoryRow['ID']] = array(
                        'name' => trim((string)($categoryRow['Name_Lang_enUS'] ?? '')),
                        'parent' => (int)($categoryRow['Parent'] ?? -1),
                    );
                }
            }
        } elseif (spp_character_table_exists($armoryPdo, 'dbc_achievement')) {
            $achievementSourcePdo = $armoryPdo;
            $achievementQuerySql =
                'SELECT a.`id`, a.`name`, a.`description`, a.`points`, a.`ref_achievement_category` AS `category_id`, ' .
                'a.`ref_spellicon` AS `icon_id` FROM `dbc_achievement` a WHERE a.`id` IN (%s)';
            $achievementIconIdField = 'icon_id';

            if (spp_character_table_exists($armoryPdo, 'dbc_achievement_category')) {
                foreach ($armoryPdo->query('SELECT `id`, `name`, `ref_achievement_category` FROM `dbc_achievement_category`')->fetchAll(PDO::FETCH_ASSOC) as $categoryRow) {
                    $achievementCategoryMap[(int)$categoryRow['id']] = array(
                        'name' => trim((string)($categoryRow['name'] ?? '')),
                        'parent' => (int)($categoryRow['ref_achievement_category'] ?? -1),
                    );
                }
            }
        }

        if (!$achievementSourcePdo instanceof PDO) {
            return array(
                'achievement_summary' => $achievementSummary,
            );
        }

        $achievementSummary['supported'] = true;
        $stmt = $charsPdo->prepare('SELECT `achievement`, `date` FROM `character_achievement` WHERE `guid` = ? ORDER BY `date` DESC');
        $stmt->execute(array($characterGuid));
        $achievementRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $achievementSummary['count'] = count($achievementRows);
        $achievementIds = array();
        foreach ($achievementRows as $row) {
            $achievementIds[(int)$row['achievement']] = true;
        }

        $achievementMap = array();
        if (!empty($achievementIds)) {
            $placeholders = implode(',', array_fill(0, count($achievementIds), '?'));
            $stmt = $achievementSourcePdo->prepare(sprintf($achievementQuerySql, $placeholders));
            $stmt->execute(array_keys($achievementIds));
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $achievementMap[(int)$row[$achievementIdField]] = $row;
            }
        }

        $achievementIconMap = array();
        $achievementIconIds = array();
        foreach ($achievementMap as $achievementRow) {
            $iconId = (int)($achievementRow[$achievementIconIdField] ?? 0);
            if ($iconId > 0) {
                $achievementIconIds[$iconId] = true;
            }
        }
        $achievementIconPdo = spp_character_pick_dbc_pdo(array($achievementSourcePdo, $worldPdo, $armoryPdo), 'dbc_spellicon') ?: $armoryPdo;
        if (!empty($achievementIconIds) && $achievementIconPdo && spp_character_table_exists($achievementIconPdo, 'dbc_spellicon')) {
            $spellIconFields = spp_character_spellicon_fields($achievementIconPdo);
            if ($spellIconFields['id'] && $spellIconFields['name']) {
                $placeholders = implode(',', array_fill(0, count($achievementIconIds), '?'));
                $stmt = $achievementIconPdo->prepare('SELECT `' . $spellIconFields['id'] . '` AS `id`, `' . $spellIconFields['name'] . '` AS `name` FROM `dbc_spellicon` WHERE `' . $spellIconFields['id'] . '` IN (' . $placeholders . ')');
                $stmt->execute(array_keys($achievementIconIds));
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $iconRow) {
                    $achievementIconMap[(int)$iconRow['id']] = (string)$iconRow['name'];
                }
            }
        }

        foreach ($achievementRows as $index => $row) {
            $achievement = $achievementMap[(int)$row['achievement']] ?? array(
                'id' => (int)$row['achievement'],
                'name' => 'Achievement #' . (int)$row['achievement'],
                'description' => '',
                'points' => 0,
                'icon_id' => 0,
                'category_id' => 0,
            );
            $categoryId = (int)($achievement[$achievementCategoryField] ?? 0);
            $categoryName = trim((string)($achievementCategoryMap[$categoryId]['name'] ?? ''));
            $parentCategoryId = (int)($achievementCategoryMap[$categoryId]['parent'] ?? -1);
            $parentCategoryName = trim((string)($achievementCategoryMap[$parentCategoryId]['name'] ?? ''));
            if ($categoryName === '') {
                $categoryName = 'Other';
            }
            $groupName = $parentCategoryName !== '' ? $parentCategoryName : $categoryName;
            $subgroupName = $parentCategoryName !== '' ? $categoryName : '';
            $iconId = (int)($achievement[$achievementIconIdField] ?? 0);
            $achievementEntry = array(
                'id' => (int)($achievement[$achievementIdField] ?? $row['achievement']),
                'name' => (string)($achievement[$achievementNameField] ?? ('Achievement #' . (int)$row['achievement'])),
                'description' => trim((string)($achievement[$achievementDescriptionField] ?? '')),
                'points' => (int)($achievement[$achievementPointsField] ?? 0),
                'date' => (int)($row['date'] ?? 0),
                'date_label' => !empty($row['date']) ? gmdate('M j, Y', (int)$row['date']) : '',
                'icon' => spp_character_icon_url($achievementIconMap[$iconId] ?? 'INV_Misc_QuestionMark'),
                'category' => $categoryName,
                'group' => $groupName,
                'subgroup' => $subgroupName,
            );
            $achievementSummary['points'] += (int)($achievement[$achievementPointsField] ?? 0);
            if ($index < 5) {
                $achievementSummary['recent'][] = $achievementEntry;
            }
            if (!isset($achievementSummary['groups'][$groupName])) {
                $achievementSummary['groups'][$groupName] = array();
            }
            if (!isset($achievementSummary['groups'][$groupName][$subgroupName])) {
                $achievementSummary['groups'][$groupName][$subgroupName] = array();
            }
            $achievementSummary['groups'][$groupName][$subgroupName][] = $achievementEntry;
        }

        return array(
            'achievement_summary' => $achievementSummary,
        );
    }
}
