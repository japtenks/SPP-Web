<?php

require_once dirname(__DIR__, 2) . '/app/support/db-schema.php';
require_once dirname(__DIR__) . '/support/terminology.php';
require_once dirname(__DIR__, 2) . '/templates/offlike/server/text_helpers.php';

if (!function_exists('spp_stat_median')) {
    function spp_stat_median(array $values): int
    {
        $values = array_values(array_filter($values, static function ($value) {
            return is_numeric($value);
        }));

        if (empty($values)) {
            return 0;
        }

        sort($values, SORT_NUMERIC);
        $count = count($values);
        $middle = (int)floor(($count - 1) / 2);

        if ($count % 2 === 0) {
            return (int)round(($values[$middle] + $values[$middle + 1]) / 2);
        }

        return (int)$values[$middle];
    }
}

if (!function_exists('spp_stat_format_playtime')) {
    function spp_stat_format_playtime($seconds): string
    {
        if (!is_numeric($seconds) || (int)$seconds <= 0) {
            return '-';
        }

        $seconds = (int)$seconds;
        if ($seconds >= 86400) {
            return round($seconds / 86400, 1) . 'd';
        }
        if ($seconds >= 3600) {
            return round($seconds / 3600, 1) . 'h';
        }
        if ($seconds >= 60) {
            return round($seconds / 60, 1) . 'm';
        }

        return $seconds . 's';
    }
}

if (!function_exists('spp_stat_table_exists')) {
    function spp_stat_table_exists(PDO $pdo, $tableName): bool
    {
        return spp_db_table_exists($pdo, (string)$tableName);
    }
}

if (!function_exists('spp_stat_columns')) {
    function spp_stat_columns(PDO $pdo, $tableName): array
    {
        static $cache = array();

        $key = spl_object_hash($pdo) . ':' . $tableName;
        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $columns = array();
        if (!spp_stat_table_exists($pdo, $tableName)) {
            $cache[$key] = $columns;
            return $columns;
        }

        foreach ($pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $tableName) . '`')->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $columns[$row['Field']] = true;
        }

        $cache[$key] = $columns;
        return $columns;
    }
}

if (!function_exists('spp_stat_scope_includes_character')) {
    function spp_stat_scope_includes_character($scope, $accountId, $guildId, array $botAccountIds, array $humanGuildIds): bool
    {
        $accountId = (int)$accountId;
        $guildId = (int)$guildId;
        $isBot = $accountId > 0 && isset($botAccountIds[$accountId]);

        if ($scope === 'humans') {
            return !$isBot;
        }

        if ($scope === 'human_guilds') {
            return !$isBot || ($guildId > 0 && isset($humanGuildIds[$guildId]));
        }

        return true;
    }
}

if (!function_exists('spp_stat_world_db_name')) {
    function spp_stat_world_db_name(int $realmId): string
    {
        if ($realmId === 2) {
            return 'tbcmangos';
        }
        if ($realmId === 3) {
            return 'wotlkmangos';
        }

        return 'classicmangos';
    }
}

if (!function_exists('spp_stat_empty_summary')) {
    function spp_stat_empty_summary(): array
    {
        return array(
            'max' => 0,
            'avg' => 0,
            'median' => 0,
        );
    }
}

if (!function_exists('spp_stat_load_page_state')) {
    function spp_stat_load_page_state(array $args = array()): array
    {
        $realmMap = (array)($args['realm_map'] ?? ($GLOBALS['realmDbMap'] ?? array()));
        $get = (array)($args['get'] ?? $_GET);
        $requestedRealmId = isset($get['realm']) ? (int)$get['realm'] : 0;
        $realmId = !empty($realmMap)
            ? (int)spp_resolve_realm_id($realmMap, $requestedRealmId > 0 ? $requestedRealmId : null)
            : max(1, $requestedRealmId);
        if ($realmId <= 0) {
            $realmId = 1;
        }

        $statScope = strtolower(trim((string)($get['scope'] ?? 'all')));
        if (!in_array($statScope, array('all', 'human_guilds', 'humans'), true)) {
            $statScope = 'all';
        }

        $publicTerms = spp_terminology_public();
        $scopeLinks = array(
            'all' => $publicTerms['all'],
            'human_guilds' => $publicTerms['humans_plus_guild_characters'],
            'humans' => $publicTerms['humans_only'],
        );

        $availableClassOrder = array(1, 2, 3, 4, 5, 8, 9, 11);
        if ($realmId >= 2) {
            $availableClassOrder[] = 7;
        }
        if ($realmId >= 3) {
            $availableClassOrder[] = 6;
        }

        $state = array(
            'realmId' => $realmId,
            'statScope' => $statScope,
            'scopeLinks' => $scopeLinks,
            'databaseError' => '',
            'num_chars' => 0,
            'num_ally' => 0,
            'num_horde' => 0,
            'pc_ally' => 0,
            'pc_horde' => 0,
            'pc_12' => 0,
            'rc' => array_fill(1, 12, 0),
            'allianceRaces' => array(),
            'hordeRaces' => array(),
            'hasDK' => false,
            'availableClassOrder' => $availableClassOrder,
            'classCards' => array(),
            'classCountMax' => 0,
            'classMedianMax' => 0,
            'classPlaytimeMedianMax' => 0,
            'classGearMedianMax' => 0,
            'classOnlineShareMax' => 0,
            'classGuildedShareMax' => 0,
            'classHonorMedianMax' => 0,
            'playtimeBuckets' => array(
                'Under 2h' => 0,
                '2h - 10h' => 0,
                '10h - 24h' => 0,
                '1d - 3d' => 0,
                '3d+' => 0,
            ),
            'playtimeBucketMax' => 0,
            'totalBots' => 0,
            'totalPlayers' => 0,
            'accountSplitTotal' => 0,
            'playtimeSummary' => array(
                'realm' => spp_stat_empty_summary(),
                'bots' => spp_stat_empty_summary(),
                'players' => spp_stat_empty_summary(),
            ),
            'questOverview' => array(
                'realm' => spp_stat_empty_summary(),
                'bots' => spp_stat_empty_summary(),
                'players' => spp_stat_empty_summary(),
            ),
            'pathway_info' => array(
                array('title' => 'Statistics', 'link' => ''),
            ),
        );

        $classMeta = spp_class_palette();
        $realmdDbName = $realmMap[$realmId]['realmd'] ?? 'classicrealmd';
        $botAccountIds = array();
        $humanGuildIds = array();
        $classCounts = array();
        $classLevels = array();
        $classPlaytimes = array();
        $classItemLevels = array();
        $classOnlineCounts = array();
        $classGuildedCounts = array();
        $classHonorableKills = array();
        $realmQuestCompletions = array();
        $botQuestCompletions = array();
        $playerQuestCompletions = array();
        $realmPlaytimes = array();
        $botPlaytimes = array();
        $playerPlaytimes = array();
        $statCharPdo = null;

        try {
            $statCharPdo = spp_get_pdo('chars', $realmId);

            $botAccountRows = $statCharPdo->query("SELECT `id` FROM `{$realmdDbName}`.`account` WHERE LOWER(`username`) LIKE 'rndbot%'");
            while ($botAccountRow = $botAccountRows->fetch(PDO::FETCH_NUM)) {
                $botAccountIds[(int)$botAccountRow[0]] = true;
            }

            if (spp_stat_table_exists($statCharPdo, 'guild_member')) {
                if (!empty($botAccountIds)) {
                    $botAccountIdSql = implode(',', array_keys($botAccountIds));
                    $guildRows = $statCharPdo->query("
                        SELECT DISTINCT gm.guildid
                        FROM guild_member gm
                        INNER JOIN characters c ON c.guid = gm.guid
                        WHERE gm.guildid > 0
                          AND NOT (c.level = 1 AND c.xp = 0)
                          AND c.account NOT IN ({$botAccountIdSql})
                    ")->fetchAll(PDO::FETCH_COLUMN);
                } else {
                    $guildRows = $statCharPdo->query("
                        SELECT DISTINCT gm.guildid
                        FROM guild_member gm
                        INNER JOIN characters c ON c.guid = gm.guid
                        WHERE gm.guildid > 0
                          AND NOT (c.level = 1 AND c.xp = 0)
                    ")->fetchAll(PDO::FETCH_COLUMN);
                }

                foreach (($guildRows ?: array()) as $guildId) {
                    $humanGuildIds[(int)$guildId] = true;
                }
            }

            $characterColumns = spp_stat_columns($statCharPdo, 'characters');
            $honorableKillsSql = '0';
            if (isset($characterColumns['stored_honorable_kills'])) {
                $honorableKillsSql = 'COALESCE(c.stored_honorable_kills, 0)';
            } elseif (isset($characterColumns['totalKills'])) {
                $honorableKillsSql = 'COALESCE(c.totalKills, 0)';
            }

            $characterRows = $statCharPdo->query("
                SELECT c.race, c.class, c.level, c.totaltime, c.account, c.online,
                       {$honorableKillsSql} AS honorable_kills,
                       gm.guildid
                FROM characters c
                LEFT JOIN guild_member gm ON gm.guid = c.guid
                WHERE NOT (c.level = 1 AND c.xp = 0)
                  AND c.level > 0
            ");

            while ($row = $characterRows->fetch(PDO::FETCH_ASSOC)) {
                $accountId = (int)($row['account'] ?? 0);
                $guildId = (int)($row['guildid'] ?? 0);
                if (!spp_stat_scope_includes_character($statScope, $accountId, $guildId, $botAccountIds, $humanGuildIds)) {
                    continue;
                }

                $raceId = (int)($row['race'] ?? 0);
                if ($raceId > 0) {
                    $state['rc'][$raceId] = (int)($state['rc'][$raceId] ?? 0) + 1;
                    $state['num_chars']++;
                }

                $classId = (int)($row['class'] ?? 0);
                $level = (int)($row['level'] ?? 0);
                $playtime = max(0, (int)($row['totaltime'] ?? 0));
                $online = !empty($row['online']) ? 1 : 0;
                $honorableKills = max(0, (int)($row['honorable_kills'] ?? 0));
                if (!isset($classMeta[$classId]) || $level <= 0) {
                    continue;
                }

                if (!isset($classCounts[$classId])) {
                    $classCounts[$classId] = 0;
                    $classLevels[$classId] = array();
                    $classPlaytimes[$classId] = array();
                    $classOnlineCounts[$classId] = 0;
                    $classGuildedCounts[$classId] = 0;
                    $classHonorableKills[$classId] = array();
                }

                $classCounts[$classId]++;
                $classLevels[$classId][] = $level;
                $classPlaytimes[$classId][] = $playtime;
                $realmPlaytimes[] = $playtime;
                $classOnlineCounts[$classId] += $online;
                if ($guildId > 0) {
                    $classGuildedCounts[$classId]++;
                }
                $classHonorableKills[$classId][] = $honorableKills;

                if ($playtime < 7200) {
                    $state['playtimeBuckets']['Under 2h']++;
                } elseif ($playtime < 36000) {
                    $state['playtimeBuckets']['2h - 10h']++;
                } elseif ($playtime < 86400) {
                    $state['playtimeBuckets']['10h - 24h']++;
                } elseif ($playtime < 259200) {
                    $state['playtimeBuckets']['1d - 3d']++;
                } else {
                    $state['playtimeBuckets']['3d+']++;
                }

                if ($accountId > 0) {
                    if (isset($botAccountIds[$accountId])) {
                        $state['totalBots']++;
                        $botPlaytimes[] = $playtime;
                    } else {
                        $state['totalPlayers']++;
                        $playerPlaytimes[] = $playtime;
                    }
                }
            }
        } catch (Exception $e) {
            $state['databaseError'] = $e->getMessage();
        }

        $state['num_ally'] = (int)($state['rc'][1] + $state['rc'][3] + $state['rc'][4] + $state['rc'][7] + $state['rc'][11]);
        $state['num_horde'] = (int)($state['rc'][2] + $state['rc'][5] + $state['rc'][6] + $state['rc'][8] + $state['rc'][10]);
        if ($state['num_chars'] > 0) {
            $state['pc_ally'] = round(($state['num_ally'] / $state['num_chars']) * 100, 1);
            $state['pc_horde'] = round(($state['num_horde'] / $state['num_chars']) * 100, 1);
            $state['pc_12'] = round(($state['rc'][12] / $state['num_chars']) * 100, 2);
        }

        $racePercentages = array();
        foreach ($state['rc'] as $raceId => $count) {
            $racePercentages[$raceId] = $state['num_chars'] > 0 ? round(($count / $state['num_chars']) * 100, 1) : 0;
        }

        try {
            if ($statCharPdo instanceof PDO && spp_stat_table_exists($statCharPdo, 'character_queststatus')) {
                $questRows = $statCharPdo->query("
                    SELECT c.class, c.account, gm.guildid, COUNT(*) AS completed_quests
                    FROM character_queststatus qs
                    INNER JOIN characters c ON c.guid = qs.guid
                    LEFT JOIN guild_member gm ON gm.guid = c.guid
                    WHERE NOT (c.level = 1 AND c.xp = 0)
                      AND c.class IS NOT NULL
                      AND c.level > 0
                      AND qs.rewarded <> 0
                    GROUP BY qs.guid, c.class, c.account, gm.guildid
                ")->fetchAll(PDO::FETCH_ASSOC);

                foreach ($questRows as $row) {
                    $classId = (int)($row['class'] ?? 0);
                    $accountId = (int)($row['account'] ?? 0);
                    $guildId = (int)($row['guildid'] ?? 0);
                    $completed = (int)($row['completed_quests'] ?? 0);
                    if (!spp_stat_scope_includes_character($statScope, $accountId, $guildId, $botAccountIds, $humanGuildIds)) {
                        continue;
                    }
                    if (!isset($classMeta[$classId])) {
                        continue;
                    }
                    $realmQuestCompletions[] = $completed;
                    if ($accountId > 0 && isset($botAccountIds[$accountId])) {
                        $botQuestCompletions[] = $completed;
                    } else {
                        $playerQuestCompletions[] = $completed;
                    }
                }
            }
        } catch (Exception $e) {
            $realmQuestCompletions = array();
            $botQuestCompletions = array();
            $playerQuestCompletions = array();
        }

        try {
            $itemLevelRows = $statCharPdo instanceof PDO ? $statCharPdo->query("
                SELECT c.class, c.account, gm.guildid, ROUND(AVG(it.ItemLevel), 1) AS avg_item_level
                FROM characters c
                LEFT JOIN guild_member gm ON gm.guid = c.guid
                INNER JOIN character_inventory ci ON ci.guid = c.guid
                INNER JOIN " . spp_stat_world_db_name($realmId) . ".item_template it ON it.entry = ci.item_template
                WHERE NOT (c.level = 1 AND c.xp = 0)
                  AND c.class IS NOT NULL
                  AND c.level > 0
                  AND ci.bag = 0
                  AND ci.slot BETWEEN 0 AND 18
                  AND ci.slot NOT IN (3, 18)
                  AND ci.item_template > 0
                  AND it.ItemLevel > 0
                GROUP BY c.guid, c.class, c.account, gm.guildid
            ")->fetchAll(PDO::FETCH_ASSOC) : array();

            foreach ($itemLevelRows as $row) {
                $classId = (int)($row['class'] ?? 0);
                $accountId = (int)($row['account'] ?? 0);
                $guildId = (int)($row['guildid'] ?? 0);
                $avgItemLevel = (float)($row['avg_item_level'] ?? 0);
                if (!spp_stat_scope_includes_character($statScope, $accountId, $guildId, $botAccountIds, $humanGuildIds)) {
                    continue;
                }
                if (!isset($classMeta[$classId]) || $avgItemLevel <= 0) {
                    continue;
                }
                if (!isset($classItemLevels[$classId])) {
                    $classItemLevels[$classId] = array();
                }
                $classItemLevels[$classId][] = $avgItemLevel;
            }
        } catch (Exception $e) {
            $classItemLevels = array();
        }

        foreach ($availableClassOrder as $classId) {
            $count = (int)($classCounts[$classId] ?? 0);
            $medianLevel = spp_stat_median($classLevels[$classId] ?? array());
            $medianPlaytime = spp_stat_median($classPlaytimes[$classId] ?? array());
            $avgPlaytime = !empty($classPlaytimes[$classId]) ? (int)round(array_sum($classPlaytimes[$classId]) / count($classPlaytimes[$classId])) : 0;
            $medianGear = spp_stat_median($classItemLevels[$classId] ?? array());
            $avgGear = !empty($classItemLevels[$classId]) ? round(array_sum($classItemLevels[$classId]) / count($classItemLevels[$classId]), 1) : 0;
            $onlineShare = $count > 0 ? round(((int)($classOnlineCounts[$classId] ?? 0) / $count) * 100, 1) : 0;
            $guildedShare = $count > 0 ? round(((int)($classGuildedCounts[$classId] ?? 0) / $count) * 100, 1) : 0;
            $honorMedian = spp_stat_median($classHonorableKills[$classId] ?? array());

            $state['classCards'][$classId] = array(
                'name' => $classMeta[$classId]['name'],
                'color' => $classMeta[$classId]['color'],
                'rgb' => $classMeta[$classId]['rgb'],
                'count' => $count,
                'median_level' => $medianLevel,
                'median_playtime' => $medianPlaytime,
                'avg_playtime' => $avgPlaytime,
                'median_gear' => $medianGear,
                'avg_gear' => $avgGear,
                'online_share' => $onlineShare,
                'guilded_share' => $guildedShare,
                'median_honorable_kills' => $honorMedian,
            );

            $state['classCountMax'] = max($state['classCountMax'], $count);
            $state['classMedianMax'] = max($state['classMedianMax'], $medianLevel);
            $state['classPlaytimeMedianMax'] = max($state['classPlaytimeMedianMax'], $medianPlaytime);
            $state['classGearMedianMax'] = max($state['classGearMedianMax'], $medianGear);
            $state['classOnlineShareMax'] = max($state['classOnlineShareMax'], $onlineShare);
            $state['classGuildedShareMax'] = max($state['classGuildedShareMax'], $guildedShare);
            $state['classHonorMedianMax'] = max($state['classHonorMedianMax'], $honorMedian);
        }

        $state['playtimeBucketMax'] = !empty($state['playtimeBuckets']) ? max($state['playtimeBuckets']) : 0;
        $state['accountSplitTotal'] = $state['totalBots'] + $state['totalPlayers'];
        $state['playtimeSummary'] = array(
            'realm' => array(
                'max' => !empty($realmPlaytimes) ? max($realmPlaytimes) : 0,
                'avg' => !empty($realmPlaytimes) ? (int)round(array_sum($realmPlaytimes) / count($realmPlaytimes)) : 0,
                'median' => spp_stat_median($realmPlaytimes),
            ),
            'bots' => array(
                'max' => !empty($botPlaytimes) ? max($botPlaytimes) : 0,
                'avg' => !empty($botPlaytimes) ? (int)round(array_sum($botPlaytimes) / count($botPlaytimes)) : 0,
                'median' => spp_stat_median($botPlaytimes),
            ),
            'players' => array(
                'max' => !empty($playerPlaytimes) ? max($playerPlaytimes) : 0,
                'avg' => !empty($playerPlaytimes) ? (int)round(array_sum($playerPlaytimes) / count($playerPlaytimes)) : 0,
                'median' => spp_stat_median($playerPlaytimes),
            ),
        );
        $state['questOverview'] = array(
            'realm' => array(
                'max' => !empty($realmQuestCompletions) ? max($realmQuestCompletions) : 0,
                'avg' => !empty($realmQuestCompletions) ? round(array_sum($realmQuestCompletions) / count($realmQuestCompletions), 1) : 0,
                'median' => spp_stat_median($realmQuestCompletions),
            ),
            'bots' => array(
                'max' => !empty($botQuestCompletions) ? max($botQuestCompletions) : 0,
                'avg' => !empty($botQuestCompletions) ? round(array_sum($botQuestCompletions) / count($botQuestCompletions), 1) : 0,
                'median' => spp_stat_median($botQuestCompletions),
            ),
            'players' => array(
                'max' => !empty($playerQuestCompletions) ? max($playerQuestCompletions) : 0,
                'avg' => !empty($playerQuestCompletions) ? round(array_sum($playerQuestCompletions) / count($playerQuestCompletions), 1) : 0,
                'median' => spp_stat_median($playerQuestCompletions),
            ),
        );

        $allianceMap = array(1 => 'human', 3 => 'dwarf', 4 => 'nightelf', 7 => 'gnome');
        $hordeMap = array(2 => 'orc', 5 => 'undead', 6 => 'tauren', 8 => 'troll');
        if ($realmId >= 2) {
            $allianceMap[11] = 'draenei';
            $hordeMap[10] = 'be';
        }

        foreach ($allianceMap as $raceId => $key) {
            $state['allianceRaces'][$raceId] = array(
                'count' => $state['rc'][$raceId] ?? 0,
                'pc' => $racePercentages[$raceId] ?? 0,
            );
        }
        foreach ($hordeMap as $raceId => $key) {
            $state['hordeRaces'][$raceId] = array(
                'count' => $state['rc'][$raceId] ?? 0,
                'pc' => $racePercentages[$raceId] ?? 0,
            );
        }

        $state['hasDK'] = !empty($state['rc'][12]);

        return $state;
    }
}
