<?php

require_once __DIR__ . '/realm-capabilities.php';

if (!function_exists('spp_personality_table_exists')) {
    function spp_personality_table_exists(PDO $pdo, string $tableName): bool
    {
        return spp_db_table_exists($pdo, $tableName);
    }
}

if (!function_exists('spp_personality_format_timestamp')) {
    function spp_personality_format_timestamp($timestamp): string
    {
        $timestamp = (int)$timestamp;
        if ($timestamp <= 0) {
            return '-';
        }

        return date('Y-m-d H:i:s', $timestamp);
    }
}

if (!function_exists('spp_personality_format_minutes_of_day')) {
    function spp_personality_format_minutes_of_day($minutes): string
    {
        $minutes = max(0, (int)$minutes);
        $hours = (int)floor($minutes / 60) % 24;
        $remainder = $minutes % 60;

        return sprintf('%02d:%02d', $hours, $remainder);
    }
}

if (!function_exists('spp_personality_format_seconds')) {
    function spp_personality_format_seconds($seconds): string
    {
        $seconds = max(0, (int)$seconds);
        if ($seconds <= 0) {
            return '-';
        }

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

if (!function_exists('spp_personality_timezone_region_label')) {
    function spp_personality_timezone_region_label($offsetMinutes): string
    {
        $offsetMinutes = (int)$offsetMinutes;

        $labels = array(
            0 => 'US Central',
            60 => 'US East',
            -60 => 'US Mountain',
            -120 => 'US Pacific',
            420 => 'EU',
            900 => 'Oceania',
            1020 => 'New Zealand',
            1080 => 'New Zealand',
        );

        return $labels[$offsetMinutes] ?? 'Custom / Other';
    }
}

if (!function_exists('spp_personality_status_badge')) {
    function spp_personality_status_badge(bool $ok, string $okLabel = 'Evidenced', string $badLabel = 'Missing'): array
    {
        return array(
            'class' => $ok ? 'ok' : 'warn',
            'label' => $ok ? $okLabel : $badLabel,
        );
    }
}

if (!function_exists('spp_personality_window_contains_minutes')) {
    function spp_personality_window_contains_minutes(int $minutesOfDay, int $windowStart, int $windowEnd): bool
    {
        $minutesOfDay = (($minutesOfDay % 1440) + 1440) % 1440;
        $windowStart = (($windowStart % 1440) + 1440) % 1440;
        $windowEnd = (($windowEnd % 1440) + 1440) % 1440;

        if ($windowStart === $windowEnd) {
            return true;
        }

        if ($windowStart < $windowEnd) {
            return $minutesOfDay >= $windowStart && $minutesOfDay < $windowEnd;
        }

        return $minutesOfDay >= $windowStart || $minutesOfDay < $windowEnd;
    }
}

if (!function_exists('spp_personality_server_minutes_of_day')) {
    function spp_personality_server_minutes_of_day(int $now = 0): int
    {
        $now = $now > 0 ? $now : time();
        return ((int)date('G', $now) * 60) + (int)date('i', $now);
    }
}

if (!function_exists('spp_personality_bot_local_minutes_of_day')) {
    function spp_personality_bot_local_minutes_of_day(int $serverMinutesOfDay, int $tzOffsetMinutes): int
    {
        return (($serverMinutesOfDay + $tzOffsetMinutes) % 1440 + 1440) % 1440;
    }
}

if (!function_exists('spp_personality_sample_status')) {
    function spp_personality_sample_status(array $row, int $serverMinutesOfDay): array
    {
        $online = !empty($row['online']);
        $windowStart = (int)($row['play_window_start'] ?? 0);
        $windowEnd = (int)($row['play_window_end'] ?? 0);
        $tzOffsetMinutes = (int)($row['tz_offset_minutes'] ?? 0);
        $localMinutesOfDay = spp_personality_bot_local_minutes_of_day($serverMinutesOfDay, $tzOffsetMinutes);
        $inWindow = spp_personality_window_contains_minutes($localMinutesOfDay, $windowStart, $windowEnd);

        if ($online && $inWindow) {
            $label = 'On schedule';
            $class = 'ok';
        } elseif ($online) {
            $label = 'Online off-window';
            $class = 'warn';
        } elseif ($inWindow) {
            $label = 'Should be online';
            $class = 'warn';
        } else {
            $label = 'Off window';
            $class = 'dim';
        }

        return array(
            'server_minutes' => $serverMinutesOfDay,
            'local_minutes' => $localMinutesOfDay,
            'local_label' => spp_personality_format_minutes_of_day($localMinutesOfDay),
            'in_window' => $inWindow,
            'status_label' => $label,
            'status_class' => $class,
        );
    }
}

if (!function_exists('spp_personality_sample_sort_value')) {
    function spp_personality_sample_sort_value(array $row, string $sortBy)
    {
        switch ($sortBy) {
            case 'type':
                return strtoupper((string)($row['archetype'] ?? '') . '|' . (string)($row['affiliation'] ?? ''));
            case 'window':
                return (int)($row['play_window_start'] ?? 0);
            case 'local_now':
                return (int)($row['computed_local_minutes'] ?? 0);
            case 'plan_status':
                return strtoupper((string)($row['computed_status_label'] ?? ''));
            case 'last_logout':
                return (int)($row['last_logout_at'] ?? 0);
            case 'group_time':
                return (int)($row['group_time_seconds'] ?? 0);
            case 'bot':
                return strtoupper((string)($row['name'] ?? ''));
            case 'last_login':
            default:
                return (int)($row['last_login_at'] ?? 0);
        }
    }
}

if (!function_exists('spp_personality_sample_matches_search')) {
    function spp_personality_sample_matches_search(array $row, string $searchNeedle): bool
    {
        if ($searchNeedle === '') {
            return true;
        }

        $haystack = strtoupper(implode(' ', array(
            (string)($row['name'] ?? ''),
            (string)($row['guid'] ?? ''),
            (string)($row['archetype'] ?? ''),
            (string)($row['affiliation'] ?? ''),
            (string)($row['computed_region_label'] ?? ''),
            (string)($row['computed_status_label'] ?? ''),
            (string)($row['computed_local_label'] ?? ''),
        )));

        return strpos($haystack, $searchNeedle) !== false;
    }
}

if (!function_exists('spp_personality_load_page_state')) {
    function spp_personality_load_page_state(array $args = array()): array
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

        $sampleLimit = isset($get['limit']) ? (int)$get['limit'] : 20;
        $sampleLimit = max(5, min(50, $sampleLimit));
        $sampleSearch = trim((string)($get['sample_search'] ?? ''));
        $sampleSearchNeedle = strtoupper($sampleSearch);
        $sampleSortBy = strtolower(trim((string)($get['sample_sort'] ?? 'last_login')));
        $sampleSortDir = strtoupper(trim((string)($get['sample_dir'] ?? 'DESC')));
        $allowedSampleSorts = array('bot', 'type', 'window', 'local_now', 'plan_status', 'last_login', 'last_logout', 'group_time');
        if (!in_array($sampleSortBy, $allowedSampleSorts, true)) {
            $sampleSortBy = 'last_login';
        }
        if ($sampleSortDir !== 'ASC' && $sampleSortDir !== 'DESC') {
            $sampleSortDir = 'DESC';
        }
        $sampleScanLimit = max(200, min(500, $sampleLimit * 12));
        $databaseError = '';
        $realmCapabilities = spp_realm_capabilities($realmMap, $realmId);
        $summary = array();
        $phaseCards = array();
        $archetypeRows = array();
        $timezoneRows = array();
        $weightRows = array();
        $sampleRows = array();
        $guildCultureRows = array();
        $historySummary = array(
            'available' => 0,
            'total_snapshots' => 0,
            'latest_capture' => null,
            'latest_batch' => '',
            'latest_batch_rows' => 0,
        );
        $serverNowTimestamp = time();
        $serverNowLabel = date('Y-m-d H:i:s', $serverNowTimestamp);
        $serverTimezoneLabel = (string)($GLOBALS['appTimezone'] ?? date_default_timezone_get());
        $serverMinutesOfDay = spp_personality_server_minutes_of_day($serverNowTimestamp);

        try {
            $charPdo = spp_get_pdo('chars', $realmId);
            $realmdDbName = (string)($realmMap[$realmId]['realmd'] ?? 'classicrealmd');

            if (!spp_personality_table_exists($charPdo, 'ai_playerbot_personality')) {
                throw new RuntimeException('The ai_playerbot_personality table is not present on the selected realm.');
            }

            $summarySql = "
                SELECT
                    COUNT(*) AS personality_rows,
                    SUM(c.guid IS NOT NULL) AS linked_characters,
                    SUM(c.online = 1) AS online_now,
                    SUM(p.last_login_at > 0) AS login_written,
                    SUM(p.last_logout_at > 0) AS logout_written,
                    SUM(p.first_eligible_login_at > 0) AS eligibility_written,
                    SUM(p.affiliation = 'GUILD') AS guilded_rows,
                    SUM(p.is_leader = 1) AS leaders,
                    SUM(p.is_tourist = 1) AS tourists,
                    SUM(p.is_dormant = 1) AS dormant,
                    SUM(p.pvp_lean = 1) AS pvp_lean_rows,
                    SUM(p.group_time_seconds) AS total_group_time_seconds,
                    AVG(p.session_minutes) AS avg_session_minutes,
                    AVG(p.weekend_bonus) AS avg_weekend_bonus,
                    AVG(p.tz_offset_minutes) AS avg_tz_offset_minutes
                FROM ai_playerbot_personality p
                LEFT JOIN characters c ON c.guid = p.guid
            ";
            $summary = $charPdo->query($summarySql)->fetch(PDO::FETCH_ASSOC) ?: array();

            $characterSummarySql = "
                SELECT
                    COUNT(*) AS total_characters,
                    SUM(online = 1) AS total_online_characters,
                    SUM(account IN (SELECT id FROM `{$realmdDbName}`.`account` WHERE LOWER(username) LIKE 'rndbot%')) AS total_bot_characters
                FROM characters
            ";
            $characterSummary = $charPdo->query($characterSummarySql)->fetch(PDO::FETCH_ASSOC) ?: array();
            $summary = array_merge($summary, $characterSummary);

            $historyPdo = function_exists('spp_canonical_auth_pdo')
                ? spp_canonical_auth_pdo()
                : spp_get_pdo('realmd', $realmId);
            if (spp_personality_table_exists($historyPdo, 'website_personality_history')) {
                $historyStmt = $historyPdo->prepare("
                    SELECT
                        COUNT(*) AS total_snapshots,
                        MAX(captured_at) AS latest_capture,
                        MAX(snapshot_batch) AS latest_batch
                    FROM website_personality_history
                    WHERE realm_id = :realm_id
                ");
                $historyStmt->execute(array('realm_id' => $realmId));
                $historySummary = array_merge($historySummary, $historyStmt->fetch(PDO::FETCH_ASSOC) ?: array());
                $historySummary['available'] = 1;

                if (!empty($historySummary['latest_batch'])) {
                    $historyBatchStmt = $historyPdo->prepare("
                        SELECT COUNT(*) AS latest_batch_rows
                        FROM website_personality_history
                        WHERE realm_id = :realm_id AND snapshot_batch = :snapshot_batch
                    ");
                    $historyBatchStmt->execute(array(
                        'realm_id' => $realmId,
                        'snapshot_batch' => (string)$historySummary['latest_batch'],
                    ));
                    $historyBatchRow = $historyBatchStmt->fetch(PDO::FETCH_ASSOC) ?: array();
                    $historySummary['latest_batch_rows'] = (int)($historyBatchRow['latest_batch_rows'] ?? 0);
                }
            }

            $guildCultureExists = spp_personality_table_exists($charPdo, 'ai_guild_culture');
            $guildCultureSummary = array(
                'culture_rows' => 0,
                'culture_recent_rows' => 0,
            );
            if ($guildCultureExists) {
                $guildCultureSummary = $charPdo->query("
                    SELECT
                        COUNT(*) AS culture_rows,
                        SUM(last_event_time > 0) AS culture_recent_rows
                    FROM ai_guild_culture
                ")->fetch(PDO::FETCH_ASSOC) ?: $guildCultureSummary;

                $guildCultureRows = $charPdo->query("
                    SELECT guildid, archetype_dominant, cohesion, leader_guid, last_event_time
                    FROM ai_guild_culture
                    ORDER BY last_event_time DESC, guildid DESC
                    LIMIT 8
                ")->fetchAll(PDO::FETCH_ASSOC) ?: array();
            }
            $summary = array_merge($summary, $guildCultureSummary);

            $archetypeRows = $charPdo->query("
                SELECT
                    p.archetype,
                    p.affiliation,
                    COUNT(*) AS row_count,
                    SUM(c.online = 1) AS online_now,
                    SUM(p.is_leader = 1) AS leaders,
                    SUM(p.pvp_lean = 1) AS pvp_lean_rows
                FROM ai_playerbot_personality p
                LEFT JOIN characters c ON c.guid = p.guid
                GROUP BY p.archetype, p.affiliation
                ORDER BY FIELD(p.archetype, 'CASUAL', 'RAIDER', 'RPG', 'PVP'), FIELD(p.affiliation, 'GUILD', 'SOLO')
            ")->fetchAll(PDO::FETCH_ASSOC) ?: array();

            $timezoneRows = $charPdo->query("
                SELECT
                    tz_offset_minutes,
                    COUNT(*) AS row_count,
                    SUM(c.online = 1) AS online_now,
                    ROUND(AVG(session_minutes), 1) AS avg_session_minutes
                FROM ai_playerbot_personality p
                LEFT JOIN characters c ON c.guid = p.guid
                GROUP BY tz_offset_minutes
                ORDER BY row_count DESC, tz_offset_minutes ASC
                LIMIT 10
            ")->fetchAll(PDO::FETCH_ASSOC) ?: array();

            $weightRows = $charPdo->query("
                SELECT
                    archetype,
                    ROUND(AVG(w_quest), 1) AS avg_w_quest,
                    ROUND(AVG(w_grind), 1) AS avg_w_grind,
                    ROUND(AVG(w_pvp), 1) AS avg_w_pvp,
                    ROUND(AVG(w_dungeon), 1) AS avg_w_dungeon,
                    ROUND(AVG(w_farm), 1) AS avg_w_farm,
                    ROUND(AVG(sociability), 1) AS avg_sociability,
                    ROUND(AVG(leader_charisma), 1) AS avg_leader_charisma
                FROM ai_playerbot_personality
                GROUP BY archetype
                ORDER BY FIELD(archetype, 'CASUAL', 'RAIDER', 'RPG', 'PVP')
            ")->fetchAll(PDO::FETCH_ASSOC) ?: array();

            $sampleStmt = $charPdo->prepare("
                SELECT
                    p.guid,
                    c.name,
                    c.level,
                    c.online,
                    p.affiliation,
                    p.archetype,
                    p.is_leader,
                    p.pvp_lean,
                    p.is_tourist,
                    p.is_dormant,
                    p.tz_offset_minutes,
                    p.play_window_start,
                    p.play_window_end,
                    p.session_minutes,
                    p.last_login_at,
                    p.last_logout_at,
                    p.first_eligible_login_at,
                    p.group_time_seconds
                FROM ai_playerbot_personality p
                LEFT JOIN characters c ON c.guid = p.guid
                ORDER BY p.last_login_at DESC, p.guid DESC
                LIMIT {$sampleScanLimit}
            ");
            $sampleStmt->execute();
            $sampleRows = $sampleStmt->fetchAll(PDO::FETCH_ASSOC) ?: array();

            foreach ($sampleRows as &$sampleRow) {
                $computedStatus = spp_personality_sample_status($sampleRow, $serverMinutesOfDay);
                $sampleRow['computed_local_minutes'] = $computedStatus['local_minutes'];
                $sampleRow['computed_local_label'] = $computedStatus['local_label'];
                $sampleRow['computed_status_label'] = $computedStatus['status_label'];
                $sampleRow['computed_status_class'] = $computedStatus['status_class'];
                $sampleRow['computed_in_window'] = $computedStatus['in_window'] ? 1 : 0;
                $sampleRow['computed_region_label'] = spp_personality_timezone_region_label((int)($sampleRow['tz_offset_minutes'] ?? 0));
            }
            unset($sampleRow);

            if ($sampleSearchNeedle !== '') {
                $sampleRows = array_values(array_filter($sampleRows, static function (array $row) use ($sampleSearchNeedle): bool {
                    return spp_personality_sample_matches_search($row, $sampleSearchNeedle);
                }));
            }

            usort($sampleRows, static function (array $left, array $right) use ($sampleSortBy, $sampleSortDir): int {
                $leftValue = spp_personality_sample_sort_value($left, $sampleSortBy);
                $rightValue = spp_personality_sample_sort_value($right, $sampleSortBy);
                $comparison = $leftValue <=> $rightValue;

                if (is_string($leftValue) || is_string($rightValue)) {
                    $comparison = strcasecmp((string)$leftValue, (string)$rightValue);
                }

                if ($comparison === 0) {
                    $comparison = ((int)($right['last_login_at'] ?? 0)) <=> ((int)($left['last_login_at'] ?? 0));
                }

                return $sampleSortDir === 'ASC' ? $comparison : -$comparison;
            });

            $sampleRows = array_slice($sampleRows, 0, $sampleLimit);

            $phaseCards = array(
                array(
                    'title' => 'Phase 1 Foundation',
                    'detail' => ((int)($summary['personality_rows'] ?? 0)) . ' persisted personality rows.',
                    'badge' => spp_personality_status_badge((int)($summary['personality_rows'] ?? 0) > 0),
                ),
                array(
                    'title' => 'Phase 2 Schedule Writes',
                    'detail' => (int)($summary['login_written'] ?? 0) . ' login writes and ' . (int)($summary['eligibility_written'] ?? 0) . ' eligibility writes.',
                    'badge' => spp_personality_status_badge(
                        (int)($summary['login_written'] ?? 0) > 0 && (int)($summary['eligibility_written'] ?? 0) > 0
                    ),
                ),
                array(
                    'title' => 'Logout Persistence',
                    'detail' => (int)($summary['logout_written'] ?? 0) . ' rows with `last_logout_at > 0`.',
                    'badge' => spp_personality_status_badge((int)($summary['logout_written'] ?? 0) > 0, 'Evidenced', 'Not Yet Seen'),
                ),
                array(
                    'title' => 'Guild / Culture Evidence',
                    'detail' => (int)($summary['guilded_rows'] ?? 0) . ' guild-affiliated rows and ' . (int)($summary['culture_rows'] ?? 0) . ' culture rows.',
                    'badge' => spp_personality_status_badge(
                        (int)($summary['guilded_rows'] ?? 0) > 0 || (int)($summary['culture_rows'] ?? 0) > 0,
                        'Active',
                        'Still Empty'
                    ),
                ),
            );
        } catch (Throwable $e) {
            $databaseError = $e->getMessage();
        }

        return array(
            'realmId' => $realmId,
            'realmCapabilities' => $realmCapabilities,
            'sampleLimit' => $sampleLimit,
            'sampleSearch' => $sampleSearch,
            'sampleSortBy' => $sampleSortBy,
            'sampleSortDir' => $sampleSortDir,
            'databaseError' => $databaseError,
            'summary' => $summary,
            'phaseCards' => $phaseCards,
            'archetypeRows' => $archetypeRows,
            'timezoneRows' => $timezoneRows,
            'weightRows' => $weightRows,
            'sampleRows' => $sampleRows,
            'guildCultureRows' => $guildCultureRows,
            'historySummary' => $historySummary,
            'serverNowTimestamp' => $serverNowTimestamp,
            'serverNowLabel' => $serverNowLabel,
            'serverTimezoneLabel' => $serverTimezoneLabel,
            'pathway_info' => array(
                array('title' => 'Personality Feed', 'link' => ''),
            ),
        );
    }
}
