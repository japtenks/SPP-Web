<?php

require_once __DIR__ . '/realm-capabilities.php';

if (!function_exists('spp_realmstatus_parse_time')) {
    function spp_realmstatus_parse_time($seconds): array
    {
        $seconds = max(0, (int)$seconds);

        return array(
            'd' => (int)($seconds / 86400),
            'h' => (int)(($seconds % 86400) / 3600),
            'm' => (int)(($seconds % 3600) / 60),
            's' => (int)($seconds % 60),
        );
    }
}

if (!function_exists('spp_realmstatus_format_time')) {
    function spp_realmstatus_format_time($seconds): string
    {
        if (!is_numeric($seconds) || (int)$seconds <= 0) {
            return '-';
        }

        $parts = array();
        $time = spp_realmstatus_parse_time((int)$seconds);

        foreach (array('d', 'h', 'm', 's') as $key) {
            if ((int)$time[$key] > 0) {
                $parts[] = $time[$key] . $key;
            }
        }

        return empty($parts) ? '-' : implode(', ', $parts);
    }
}

if (!function_exists('spp_realmstatus_connect_realm_db')) {
    function spp_realmstatus_connect_realm_db(string $target, int $realmId): ?PDO
    {
        if (!function_exists('spp_get_db_config')) {
            return null;
        }

        try {
            $cfg = spp_get_db_config($target, $realmId);
        } catch (Throwable $e) {
            return null;
        }

        try {
            return new PDO(
                'mysql:host=' . $cfg['host'] . ';port=' . (int)$cfg['port'] . ';dbname=' . $cfg['name'] . ';charset=utf8mb4',
                $cfg['user'],
                $cfg['pass'],
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                )
            );
        } catch (PDOException $e) {
            return null;
        }
    }
}

if (!function_exists('spp_realmstatus_cache_folder')) {
    function spp_realmstatus_cache_folder(): string
    {
        return function_exists('spp_storage_path')
            ? spp_storage_path('cache/sites')
            : dirname(__DIR__, 2) . '/storage/cache/sites';
    }
}

if (!function_exists('spp_realmstatus_cache_ttl_minutes')) {
    function spp_realmstatus_cache_ttl_minutes(): int
    {
        return 1;
    }
}

if (!function_exists('spp_realmstatus_cache_key')) {
    function spp_realmstatus_cache_key(int $selectedRealmId, bool $debugMode, bool $useLocalIpPortTest, array $targetRealmIds = array()): string
    {
        return 'realmstatus:' . $selectedRealmId . ':ids:' . implode('-', array_map('intval', $targetRealmIds)) . ':debug:' . (int)$debugMode . ':local:' . (int)$useLocalIpPortTest;
    }
}

if (!function_exists('spp_realmstatus_target_realm_ids')) {
    function spp_realmstatus_target_realm_ids(array $realmMap, array $get = array()): array
    {
        if (function_exists('spp_bootstrap_enabled_realm_map')) {
            $enabledRealmMap = spp_bootstrap_enabled_realm_map($realmMap);
            if (is_array($enabledRealmMap) && !empty($enabledRealmMap)) {
                $realmMap = $enabledRealmMap;
            }
        }

        $requestedIds = array();
        $rawIds = trim((string)($get['realm_ids'] ?? ''));
        if ($rawIds !== '') {
            foreach (preg_split('/[\s,;]+/', $rawIds) as $candidate) {
                $realmId = (int)$candidate;
                if ($realmId > 0 && isset($realmMap[$realmId])) {
                    $requestedIds[] = $realmId;
                }
            }
        }

        if (!empty($requestedIds)) {
            return array_values(array_unique($requestedIds));
        }

        $realmIds = array_values(array_filter(array_map('intval', array_keys($realmMap))));
        sort($realmIds, SORT_NUMERIC);
        if (!empty($realmIds)) {
            return $realmIds;
        }

        return !empty($realmMap) ? array((int)spp_default_realm_id($realmMap)) : array(1);
    }
}

if (!function_exists('spp_realmstatus_fetch_realmlist_row')) {
    function spp_realmstatus_fetch_realmlist_row(PDO $realmPdo, int $configRealmId): array
    {
        $stmt = $realmPdo->prepare("SELECT * FROM `realmlist` WHERE `id` = ? LIMIT 1");
        $stmt->execute(array($configRealmId));
        $realm = $stmt->fetch(PDO::FETCH_ASSOC);
        if (is_array($realm)) {
            return $realm;
        }

        $fallback = $realmPdo->query("SELECT * FROM `realmlist` ORDER BY `id` ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        return is_array($fallback) ? $fallback : array();
    }
}

if (!function_exists('spp_realmstatus_realm_name')) {
    function spp_realmstatus_realm_name(int $configRealmId, array $realmRow): string
    {
        $name = trim((string)($realmRow['name'] ?? ''));
        if ($name !== '') {
            return $name;
        }

        if (function_exists('spp_get_armory_realm_name')) {
            $fallback = trim((string)spp_get_armory_realm_name($configRealmId));
            if ($fallback !== '') {
                return $fallback;
            }
        }

        return 'Realm #' . $configRealmId;
    }
}

if (!function_exists('spp_realmstatus_cache_read')) {
    function spp_realmstatus_cache_read(string $key): ?array
    {
        if (!class_exists('gCache')) {
            return null;
        }

        $cache = new gCache();
        $cache->folder = spp_realmstatus_cache_folder();
        $cache->contentId = md5($key);
        $cache->timeout = spp_realmstatus_cache_ttl_minutes();

        if (!$cache->Valid()) {
            return null;
        }

        $payload = @unserialize((string)$cache->content);
        return is_array($payload) ? $payload : null;
    }
}

if (!function_exists('spp_realmstatus_cache_write')) {
    function spp_realmstatus_cache_write(string $key, array $payload): bool
    {
        if (!class_exists('gCache')) {
            return false;
        }

        $cache = new gCache();
        $cache->folder = spp_realmstatus_cache_folder();
        $cache->contentId = md5($key);

        return $cache->cacheWrite(serialize($payload)) !== '';
    }
}

if (!function_exists('spp_realmstatus_probe_realm')) {
    function spp_realmstatus_probe_realm($host, $port, $timeout = 0.75): bool
    {
        $host = trim((string)$host);
        $port = (int)$port;
        if ($host === '' || $port <= 0) {
            return false;
        }

        $errno = 0;
        $errstr = '';
        $socket = @fsockopen($host, $port, $errno, $errstr, (float)$timeout);
        if (!$socket) {
            return false;
        }

        fclose($socket);
        return true;
    }
}

if (!function_exists('spp_realmstatus_stat_windows')) {
    function spp_realmstatus_stat_windows(): array
    {
        return array(7, 5, 3, 1);
    }
}

if (!function_exists('spp_realmstatus_collect_uptime_metrics')) {
    function spp_realmstatus_collect_uptime_metrics(array $runRows, int $windowDays, string $source): ?array
    {
        if (count($runRows) < 3) {
            return null;
        }

        $allRuns = array();
        $stableRuns = array();
        $shortRestarts = 0;

        foreach ($runRows as $uptimeValue) {
            $uptimeSec = (int)$uptimeValue;
            if ($uptimeSec <= 0) {
                $shortRestarts++;
                continue;
            }

            $allRuns[] = $uptimeSec;
            if ($uptimeSec < 900) {
                $shortRestarts++;
            } else {
                $stableRuns[] = $uptimeSec;
            }
        }

        $metrics = array(
            'avg_uptime' => 0,
            'median_uptime' => 0,
            'stable_avg_uptime' => 0,
            'stable_runs' => 0,
            'short_restarts' => $shortRestarts,
            'stats_window_days' => $windowDays,
            'stats_source' => $source,
            'sample_runs' => count($runRows),
        );

        if (!empty($allRuns)) {
            $metrics['avg_uptime'] = round((array_sum($allRuns) / count($allRuns)) / 3600, 2);

            sort($allRuns, SORT_NUMERIC);
            $mid = (int)floor(count($allRuns) / 2);
            $metrics['median_uptime'] = count($allRuns) % 2 === 0
                ? (int)round(($allRuns[$mid - 1] + $allRuns[$mid]) / 2)
                : (int)$allRuns[$mid];
        }

        if (!empty($stableRuns)) {
            $metrics['stable_runs'] = count($stableRuns);
            $metrics['stable_avg_uptime'] = round((array_sum($stableRuns) / count($stableRuns)) / 3600, 2);
        }

        return $metrics;
    }
}

if (!function_exists('spp_realmstatus_fetch_uptime_window_rows')) {
    function spp_realmstatus_fetch_uptime_window_rows(PDO $realmPdo, int $realmId, int $windowDays): array
    {
        $windowDays = max(1, (int)$windowDays);
        $stmtRuns = $realmPdo->prepare("\n            SELECT `uptime`\n            FROM `uptime`\n            WHERE `realmid` = ?\n              AND `starttime` > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL {$windowDays} DAY))\n            ORDER BY `starttime` DESC\n        ");
        $stmtRuns->execute(array($realmId));

        return $stmtRuns->fetchAll(PDO::FETCH_COLUMN) ?: array();
    }
}

if (!function_exists('spp_realmstatus_log_directory')) {
    function spp_realmstatus_log_directory(): string
    {
        return trim((string)spp_config_generic('realm_status_log_dir', ''));
    }
}

if (!function_exists('spp_realmstatus_parse_log_timestamp')) {
    function spp_realmstatus_parse_log_timestamp(string $line): int
    {
        if (!preg_match('/(?:^|\[)(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2})/', $line, $matches)) {
            return 0;
        }

        $timestamp = strtotime(str_replace('T', ' ', $matches[1]));
        return $timestamp !== false ? (int)$timestamp : 0;
    }
}

if (!function_exists('spp_realmstatus_log_matches_realm')) {
    function spp_realmstatus_log_matches_realm(string $path, int $realmId): bool
    {
        $name = strtolower(basename($path));
        return strpos($name, 'realm' . $realmId) !== false
            || strpos($name, 'realmid' . $realmId) !== false
            || strpos($name, 'worldserver') !== false
            || strpos($name, 'mangosd') !== false;
    }
}

if (!function_exists('spp_realmstatus_log_candidates')) {
    function spp_realmstatus_log_candidates(int $realmId): array
    {
        $logDir = spp_realmstatus_log_directory();
        if ($logDir === '' || !is_dir($logDir) || !is_readable($logDir)) {
            return array();
        }

        $patterns = array(
            $logDir . DIRECTORY_SEPARATOR . '*.log',
            $logDir . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*.log',
        );

        $paths = array();
        foreach ($patterns as $pattern) {
            $matches = glob($pattern);
            if (is_array($matches)) {
                foreach ($matches as $path) {
                    if (is_file($path) && is_readable($path) && spp_realmstatus_log_matches_realm($path, $realmId)) {
                        $paths[$path] = $path;
                    }
                }
            }
        }

        return array_values($paths);
    }
}

if (!function_exists('spp_realmstatus_fetch_log_runs')) {
    function spp_realmstatus_fetch_log_runs(int $realmId, int $windowDays): array
    {
        $paths = spp_realmstatus_log_candidates($realmId);
        if (empty($paths)) {
            return array();
        }

        $cutoff = time() - ($windowDays * 86400);
        $startPattern = '/\b(?:server\s+started|server\s+starting|world\s+started|world\s+starting|mangosd\s+started|daemon\s+started)\b/i';
        $endPattern = '/\b(?:shutdown|stopping|stopped|crash|fatal\s+error|exiting)\b/i';
        $events = array();

        foreach ($paths as $path) {
            $handle = @fopen($path, 'rb');
            if (!$handle) {
                continue;
            }

            while (($line = fgets($handle)) !== false) {
                $timestamp = spp_realmstatus_parse_log_timestamp($line);
                if ($timestamp <= 0 || $timestamp < $cutoff) {
                    continue;
                }

                if (preg_match($startPattern, $line)) {
                    $events[] = array('time' => $timestamp, 'type' => 'start');
                } elseif (preg_match($endPattern, $line)) {
                    $events[] = array('time' => $timestamp, 'type' => 'end');
                }
            }

            fclose($handle);
        }

        if (empty($events)) {
            return array();
        }

        usort($events, static function (array $left, array $right): int {
            return $left['time'] <=> $right['time'];
        });

        $runs = array();
        $currentStart = 0;
        foreach ($events as $event) {
            if ($event['type'] === 'start') {
                if ($currentStart > 0 && $event['time'] > $currentStart) {
                    $runs[] = $event['time'] - $currentStart;
                }
                $currentStart = (int)$event['time'];
                continue;
            }

            if ($currentStart > 0 && $event['time'] > $currentStart) {
                $runs[] = $event['time'] - $currentStart;
                $currentStart = 0;
            }
        }

        if ($currentStart > 0) {
            $runs[] = max(0, time() - $currentStart);
        }

        return $runs;
    }
}

if (!function_exists('spp_realmstatus_fetch_uptime_stats')) {
    function spp_realmstatus_fetch_uptime_stats(PDO $realmPdo, int $realmId): array
    {
        $stats = array(
            'starttime' => 0,
            'uptime' => 0,
            'avg_uptime' => 0,
            'median_uptime' => 0,
            'stable_avg_uptime' => 0,
            'stable_runs' => 0,
            'restart_count' => 0,
            'short_restarts' => 0,
            'recent' => false,
            'stats_window_days' => 0,
            'stats_source' => '',
            'sample_runs' => 0,
        );

        $stmtLatest = $realmPdo->prepare("\n            SELECT `starttime`, `uptime`\n            FROM `uptime`\n            WHERE `realmid` = ?\n            ORDER BY `starttime` DESC\n            LIMIT 1\n        ");
        $stmtLatest->execute(array($realmId));
        $latest = $stmtLatest->fetch(PDO::FETCH_ASSOC);

        if (is_array($latest) && !empty($latest['starttime'])) {
            $stats['starttime'] = (int)$latest['starttime'];
            $storedUptime = isset($latest['uptime']) ? (int)$latest['uptime'] : 0;
            $calculatedUptime = max(0, time() - $stats['starttime']);
            $stats['uptime'] = max($storedUptime, $calculatedUptime);
            $stats['recent'] = ($calculatedUptime <= 300);
        }

        foreach (spp_realmstatus_stat_windows() as $windowDays) {
            $runRows = spp_realmstatus_fetch_uptime_window_rows($realmPdo, $realmId, $windowDays);
            $metrics = spp_realmstatus_collect_uptime_metrics($runRows, $windowDays, 'uptime_table');
            if (is_array($metrics)) {
                $stats = array_merge($stats, $metrics);
                break;
            }
        }

        if ((int)$stats['stats_window_days'] === 0) {
            foreach (spp_realmstatus_stat_windows() as $windowDays) {
                $logRuns = spp_realmstatus_fetch_log_runs($realmId, $windowDays);
                $metrics = spp_realmstatus_collect_uptime_metrics($logRuns, $windowDays, 'logs');
                if (is_array($metrics)) {
                    $stats = array_merge($stats, $metrics);
                    break;
                }
            }
        }

        $stmtRestarts = $realmPdo->prepare("\n            SELECT COUNT(*)\n            FROM `uptime`\n            WHERE `realmid` = ?\n              AND `starttime` >= UNIX_TIMESTAMP(CURDATE())\n        ");
        $stmtRestarts->execute(array($realmId));
        $restartCount = $stmtRestarts->fetchColumn();
        $stats['restart_count'] = $restartCount !== false ? (int)$restartCount : 0;

        return $stats;
    }
}

if (!function_exists('spp_realmstatus_fetch_character_stats')) {
    function spp_realmstatus_fetch_character_stats(PDO $charPdo, string $worldDbName): array
    {
        $stats = array(
            'pop' => 0,
            'online' => 0,
            'alli' => 0,
            'horde' => 0,
            'avg_lvl_online' => 0,
            'avg_lvl_total' => 0,
            'max_lvl' => 0,
            'avg_ilvl_online' => 0,
            'avg_ilvl_total' => 0,
        );

        $summary = $charPdo->query("\n            SELECT\n              SUM(CASE WHEN xp > 0 AND level > 0 THEN 1 ELSE 0 END) AS pop_total,\n              SUM(CASE WHEN xp > 0 AND level > 0 AND online = 1 THEN 1 ELSE 0 END) AS online_total,\n              SUM(CASE WHEN xp > 0 AND level > 0 AND online = 1 AND race IN (1,3,4,7,11) THEN 1 ELSE 0 END) AS alliance_total,\n              SUM(CASE WHEN xp > 0 AND level > 0 AND online = 1 AND race IN (2,5,6,8,10) THEN 1 ELSE 0 END) AS horde_total,\n              ROUND(AVG(CASE WHEN xp > 0 AND level > 0 AND online = 1 THEN level END), 1) AS online_avg_level,\n              ROUND(AVG(CASE WHEN xp > 0 AND level > 0 THEN level END), 1) AS total_avg_level,\n              MAX(CASE WHEN xp > 0 AND level > 0 THEN level END) AS max_level\n            FROM `characters`\n        ")->fetch(PDO::FETCH_ASSOC);

        if (is_array($summary)) {
            $stats['pop'] = (int)($summary['pop_total'] ?? 0);
            $stats['online'] = (int)($summary['online_total'] ?? 0);
            $stats['alli'] = (int)($summary['alliance_total'] ?? 0);
            $stats['horde'] = (int)($summary['horde_total'] ?? 0);
            $stats['avg_lvl_online'] = $summary['online_avg_level'] !== null ? (float)$summary['online_avg_level'] : 0;
            $stats['avg_lvl_total'] = $summary['total_avg_level'] !== null ? (float)$summary['total_avg_level'] : 0;
            $stats['max_lvl'] = (int)($summary['max_level'] ?? 0);
        }

        if ($worldDbName !== '') {
            try {
                $avgItemLevels = $charPdo->query("\n                    SELECT\n                      ROUND(AVG(CASE WHEN char_avg.online = 1 THEN char_avg.avg_item_level END), 1) AS online_avg_item_level,\n                      ROUND(AVG(char_avg.avg_item_level), 1) AS total_avg_item_level\n                    FROM (\n                      SELECT\n                        ci.guid,\n                        c.online,\n                        AVG(it.ItemLevel) AS avg_item_level\n                      FROM `character_inventory` ci\n                      INNER JOIN `characters` c ON c.guid = ci.guid\n                      INNER JOIN `" . $worldDbName . "`.`item_template` it ON it.entry = ci.item_template\n                      WHERE c.xp > 0\n                        AND c.level > 0\n                        AND ci.bag = 0\n                        AND ci.slot BETWEEN 0 AND 18\n                        AND ci.slot NOT IN (3, 18)\n                        AND ci.item_template > 0\n                      GROUP BY ci.guid, c.online\n                    ) AS char_avg\n                ")->fetch(PDO::FETCH_ASSOC);
                if (is_array($avgItemLevels)) {
                    $stats['avg_ilvl_online'] = $avgItemLevels['online_avg_item_level'] !== null ? (float)$avgItemLevels['online_avg_item_level'] : 0;
                    $stats['avg_ilvl_total'] = $avgItemLevels['total_avg_item_level'] !== null ? (float)$avgItemLevels['total_avg_item_level'] : 0;
                }
            } catch (Throwable $e) {
                $stats['avg_ilvl_online'] = 0;
                $stats['avg_ilvl_total'] = 0;
            }
        }

        return $stats;
    }
}

if (!function_exists('spp_realmstatus_progression_state')) {
    function spp_realmstatus_progression_state($count, $threshold, string $successState): string
    {
        return (int)$count > (int)$threshold ? $successState : 'uncleared';
    }
}

if (!function_exists('spp_realmstatus_fetch_progression_states')) {
    function spp_realmstatus_fetch_progression_states(PDO $charPdo, string $exp): array
    {
        $state = array(
            'MC' => 'uncleared', 'Ony' => 'uncleared', 'BWL' => 'uncleared', 'ZG' => 'uncleared', 'AQ' => 'uncleared', 'Naxx' => 'uncleared',
            'Kara' => 'uncleared', 'SSC' => 'uncleared', 'TK' => 'uncleared', 'BT' => 'uncleared', 'SWP' => 'uncleared',
            'Naxx25' => 'uncleared', 'Ulduar' => 'uncleared', 'ICC' => 'uncleared',
        );

        if (!spp_realm_capability_table_exists($charPdo, 'item_instance') || !spp_db_column_exists($charPdo, 'item_instance', 'itemEntry')) {
            return $state;
        }

        try {
            if ($exp === 'classic') {
                $counts = $charPdo->query("\n                SELECT\n                  SUM(itemEntry IN (16866,16854,16867,16868,16865,16863,16861,16862)) AS mc_count,\n                  SUM(itemEntry IN (16963,16964,16965,16966,16967,16968,16969,16970)) AS ony_count,\n                  SUM(itemEntry IN (16911,16924,16932,16940,16945,16953,16961,16968)) AS bwl_count,\n                  SUM(itemEntry IN (19802,19854,19822,19862,19848,19910)) AS zg_count,\n                  SUM(itemEntry IN (21329,21330,21331,21332,21333,21220)) AS aq_count,\n                  SUM(itemEntry IN (22416,22417,22418,22419,22420,22421,22422,22423)) AS naxx_count\n                FROM `item_instance`\n            ")->fetch(PDO::FETCH_ASSOC);
                if (is_array($counts)) {
                    $state['MC'] = spp_realmstatus_progression_state($counts['mc_count'] ?? 0, 3, 'cleared');
                    $state['Ony'] = spp_realmstatus_progression_state($counts['ony_count'] ?? 0, 3, 'cleared');
                    $state['BWL'] = spp_realmstatus_progression_state($counts['bwl_count'] ?? 0, 3, 'partial');
                    $state['ZG'] = spp_realmstatus_progression_state($counts['zg_count'] ?? 0, 3, 'cleared');
                    $state['AQ'] = spp_realmstatus_progression_state($counts['aq_count'] ?? 0, 3, 'partial');
                    $state['Naxx'] = spp_realmstatus_progression_state($counts['naxx_count'] ?? 0, 2, 'partial');
                }
            } elseif ($exp === 'tbc') {
                $counts = $charPdo->query("\n                SELECT\n                  SUM(itemEntry IN (29066,29067,29068,29069,29070)) AS kara_count,\n                  SUM(itemEntry IN (30245,30246,30247,30248,30249)) AS ssc_count,\n                  SUM(itemEntry IN (30233,30234,30235,30236,30237)) AS tk_count,\n                  SUM(itemEntry IN (30969,30970,30971,30972,30974)) AS bt_count,\n                  SUM(itemEntry IN (34332,34333,34334,34335,34336)) AS swp_count\n                FROM `item_instance`\n            ")->fetch(PDO::FETCH_ASSOC);
                if (is_array($counts)) {
                    $state['Kara'] = spp_realmstatus_progression_state($counts['kara_count'] ?? 0, 3, 'cleared');
                    $state['SSC'] = spp_realmstatus_progression_state($counts['ssc_count'] ?? 0, 3, 'partial');
                    $state['TK'] = spp_realmstatus_progression_state($counts['tk_count'] ?? 0, 3, 'partial');
                    $state['BT'] = spp_realmstatus_progression_state($counts['bt_count'] ?? 0, 3, 'partial');
                    $state['SWP'] = spp_realmstatus_progression_state($counts['swp_count'] ?? 0, 2, 'partial');
                }
            } elseif ($exp === 'wotlk') {
                $counts = $charPdo->query("\n                SELECT\n                  SUM(itemEntry IN (40554,40557,40559,40560,40562)) AS naxx25_count,\n                  SUM(itemEntry IN (45340,45341,45342,45343,45344)) AS ulduar_count,\n                  SUM(itemEntry IN (51155,51156,51157,51158,51159)) AS icc_count\n                FROM `item_instance`\n            ")->fetch(PDO::FETCH_ASSOC);
                if (is_array($counts)) {
                    $state['Naxx25'] = spp_realmstatus_progression_state($counts['naxx25_count'] ?? 0, 3, 'cleared');
                    $state['Ulduar'] = spp_realmstatus_progression_state($counts['ulduar_count'] ?? 0, 3, 'partial');
                    $state['ICC'] = spp_realmstatus_progression_state($counts['icc_count'] ?? 0, 3, 'partial');
                }
            }
        } catch (Throwable $e) {
            error_log('[realmstatus] progression lookup skipped for ' . $exp . ': ' . $e->getMessage());
        }

        return $state;
    }
}

if (!function_exists('spp_realmstatus_expansion_for_build')) {
    function spp_realmstatus_expansion_for_build(string $buildVersion): string
    {
        if (preg_match('/8[6-9][0-9]{2}/', $buildVersion)) {
            return 'tbc';
        }
        if (preg_match('/[12][0-9]{4}/', $buildVersion)) {
            return 'wotlk';
        }
        return 'classic';
    }
}

if (!function_exists('spp_realmstatus_type_label')) {
    function spp_realmstatus_type_label(array $realm): string
    {
        $realmFlags = (int)($realm['realmflags'] ?? 0);
        $realmIcon = (int)($realm['icon'] ?? 0);
        $map = array(0 => 'Normal', 1 => 'PvP', 4 => 'RP', 8 => 'RPPvP');
        if (isset($map[$realmIcon])) {
            return $map[$realmIcon];
        }
        return $map[$realmFlags & 0x0F] ?? 'Normal';
    }
}

if (!function_exists('spp_realmstatus_population_label')) {
    function spp_realmstatus_population_label(array $item): string
    {
        if (empty($item['has_char_data'])) {
            return '-';
        }
        return (string)(int)$item['pop'];
    }
}

if (!function_exists('spp_realmstatus_population_badge')) {
    function spp_realmstatus_population_badge(int $population): string
    {
        if (function_exists('population_view')) {
            return (string)population_view($population);
        }

        if ($population <= 500) {
            return '<span class="population-status population-status--low">Low</span>';
        }
        if ($population <= 700) {
            return '<span class="population-status population-status--medium">Medium</span>';
        }
        if ($population <= 2000) {
            return '<span class="population-status population-status--high">High</span>';
        }

        return '<span class="population-status population-status--full">Full</span>';
    }
}

if (!function_exists('spp_realmstatus_progression_badges')) {
    function spp_realmstatus_progression_badges(array $item): array
    {
        $labels = array(
            'classic' => array('MC', 'Ony', 'BWL', 'ZG', 'AQ', 'Naxx'),
            'tbc' => array('Kara', 'SSC', 'TK', 'BT', 'SWP'),
            'wotlk' => array('Naxx25' => 'Naxx', 'Ulduar' => 'Uld', 'ICC'),
        );
        $badges = array();
        $exp = (string)($item['exp'] ?? 'classic');
        foreach (($labels[$exp] ?? array()) as $key => $label) {
            if (is_int($key)) {
                $key = $label;
            }
            $badges[] = array('label' => (string)$label, 'class' => (string)($item['state'][$key] ?? 'uncleared'));
        }
        return $badges;
    }
}

if (!function_exists('spp_realmstatus_balance_state')) {
    function spp_realmstatus_balance_state(array $item): array
    {
        $alli = (int)($item['alli'] ?? 0);
        $horde = (int)($item['horde'] ?? 0);
        if (empty($item['has_char_data']) || ($alli + $horde) <= 0) {
            return array();
        }
        $allianceWidth = (int)round(($alli / max(1, ($alli + $horde))) * 100);
        $hordeWidth = 100 - $allianceWidth;
        $balanceClass = 'balanced';
        $balanceText = 'Balanced Population';
        if ($allianceWidth > 60) {
            $balanceClass = 'alliance';
            $balanceText = 'Alliance Favored';
        } elseif ($hordeWidth > 60) {
            $balanceClass = 'horde';
            $balanceText = 'Horde Favored';
        }
        return array(
            'alliance_width' => $allianceWidth,
            'horde_width' => $hordeWidth,
            'balance_class' => $balanceClass,
            'balance_text' => $balanceText,
        );
    }
}

if (!function_exists('spp_realmstatus_debug_summary')) {
    function spp_realmstatus_debug_summary(array $debug): string
    {
        return 'host=' . (($debug['realm_host'] ?? '') !== '' ? $debug['realm_host'] : '(empty)')
            . ' | port=' . (int)($debug['realm_port'] ?? 0)
            . ' | socket=' . (($debug['realm_socket_host'] ?? '') !== '' ? $debug['realm_socket_host'] : '(empty)')
            . ':' . (int)($debug['realm_socket_port'] ?? 0)
            . ' (' . (!empty($debug['realm_reachable']) ? 'up' : 'down') . ')'
            . ' | recent-uptime=' . (!empty($debug['recent_uptime']) ? 'yes' : 'no')
            . ' | chars=' . (($debug['char_db'] ?? '') !== '' ? $debug['char_db'] : '(empty)')
            . ' (' . (!empty($debug['char_ok']) ? 'ok' : 'fail') . ')'
            . ' | world=' . (($debug['world_db'] ?? '') !== '' ? $debug['world_db'] : '(empty)')
            . ' (' . (!empty($debug['world_ok']) ? 'ok' : 'fail') . ')';
    }
}

if (!function_exists('spp_realmstatus_prepare_item')) {
    function spp_realmstatus_prepare_item(array $item, int $selectedRealmId, bool $debugMode): array
    {
        $item['is_selected_realm'] = false;
        $item['is_offline_realm'] = (int)$item['res_color'] !== 1;
        $item['uptime_label'] = spp_realmstatus_format_time((int)($item['uptime'] ?? 0));
        $item['stable_avg_up_label'] = !empty($item['stable_avg_up']) ? $item['stable_avg_up'] . ' hrs' : '-';
        $item['median_up_label'] = spp_realmstatus_format_time((int)($item['median_up'] ?? 0));
        $item['stats_window_label'] = !empty($item['stats_window_days'])
            ? ((int)$item['stats_window_days']) . 'd'
            : '-';
        $item['population_label'] = spp_realmstatus_population_label($item);
        $item['online_label'] = !empty($item['has_char_data']) ? (string)(int)($item['online'] ?? 0) : '-';
        $item['progression_badges'] = spp_realmstatus_progression_badges($item);
        $item['player_count_label'] = !empty($item['has_char_data']) && (int)($item['online'] ?? 0) > 0
            ? (string)(int)($item['online'] ?? 0)
            : '-';
        $item['avg_online_total_max_level_label'] = (($item['avg_lvl_online'] ?? 0) ? number_format((float)$item['avg_lvl_online'], 1) : '-')
            . ' / '
            . (($item['avg_lvl_total'] ?? 0) ? number_format((float)$item['avg_lvl_total'], 1) : '-')
            . ' / '
            . (($item['max_lvl'] ?? 0) ? (string)(int)$item['max_lvl'] : '-');
        $item['avg_ilvl_online_total_label'] = (($item['avg_ilvl_online'] ?? 0) ? number_format((float)$item['avg_ilvl_online'], 1) : '-')
            . ' / '
            . (($item['avg_ilvl_total'] ?? 0) ? number_format((float)$item['avg_ilvl_total'], 1) : '-');
        $item['balance'] = spp_realmstatus_balance_state($item);
        $item['debug_summary'] = $debugMode ? spp_realmstatus_debug_summary((array)($item['debug'] ?? array())) : '';
        return $item;
    }
}

if (!function_exists('spp_realmstatus_render_cards')) {
    function spp_realmstatus_render_cards(array $items, bool $debugMode): string
    {
        ob_start();
        foreach ($items as $realmItem):
?>
    <div class="realm-card <?php echo (int)$realmItem['res_color'] === 1 ? 'online' : 'offline'; ?><?php echo !empty($realmItem['is_offline_realm']) ? ' is-collapsed' : ''; ?>"<?php if (!empty($realmItem['is_offline_realm'])): ?> data-realm-collapse="card"<?php endif; ?>>
      <div class="realm-card__header">
        <img src="<?php echo htmlspecialchars($realmItem['img']); ?>" alt="<?php echo htmlspecialchars($realmItem['status_label']); ?>" class="realm-card__icon"/>
        <span class="realm-card__name"><?php echo htmlspecialchars($realmItem['name']); ?></span>
        <span class="realm-card__header-meta">
          <?php if (!empty($realmItem['is_offline_realm'])): ?>
            <button type="button" class="realm-card__collapse-toggle" data-realm-collapse="toggle" aria-expanded="false">Show Details</button>
          <?php endif; ?>
          <span class="realm-card__build">(Build: <?php echo htmlspecialchars($realmItem['build']); ?>)</span>
        </span>
      </div>

      <div class="realm-card__body">
        <div><strong>Uptime:</strong> <?php echo htmlspecialchars($realmItem['uptime_label']); ?></div>
        <div><strong>Stable Avg Uptime (<?php echo htmlspecialchars($realmItem['stats_window_label']); ?>):</strong> <?php echo htmlspecialchars($realmItem['stable_avg_up_label']); ?></div>
        <div><strong>Median Uptime (<?php echo htmlspecialchars($realmItem['stats_window_label']); ?>):</strong> <?php echo htmlspecialchars($realmItem['median_up_label']); ?></div>
        <div><strong>Short Restarts (<?php echo htmlspecialchars($realmItem['stats_window_label']); ?>):</strong> <?php echo (int)$realmItem['short_restarts']; ?><?php echo !empty($realmItem['stable_runs']) ? ' (' . (int)$realmItem['stable_runs'] . ' stable runs kept)' : ''; ?></div>
        <div><strong>Restarts Today:</strong> <?php echo (int)$realmItem['restarts']; ?></div>
        <div><strong>Type:</strong> <?php echo htmlspecialchars($realmItem['type']); ?></div>
        <div><strong>Population:</strong> <?php if (!empty($realmItem['has_char_data'])): ?><?php echo htmlspecialchars($realmItem['population_label']); ?> (<?php echo spp_realmstatus_population_badge((int)$realmItem['pop']); ?>)<?php else: ?>-<?php endif; ?></div>
        <div><strong>Online:</strong> <?php echo htmlspecialchars($realmItem['online_label']); ?></div>
        <div><strong>Players Online:</strong> <?php echo htmlspecialchars($realmItem['player_count_label']); ?></div>

          <?php if (!empty($realmItem['balance'])): ?>
            <div class="faction-labels">
              <span class="alliance">Alliance (<?php echo (int)$realmItem['alli']; ?>)</span>
              <span class="horde">Horde (<?php echo (int)$realmItem['horde']; ?>)</span>
            </div>
            <div class="faction-bar">
              <div class="alliance" style="width:<?php echo (int)$realmItem['balance']['alliance_width']; ?>%"></div>
              <div class="horde" style="width:<?php echo (int)$realmItem['balance']['horde_width']; ?>%"></div>
            </div>
            <div class="faction-balance <?php echo htmlspecialchars($realmItem['balance']['balance_class']); ?>">
              <?php echo htmlspecialchars($realmItem['balance']['balance_text']); ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="realm-card__meta"><strong>Avg Online / Avg Total / Max Level:</strong> <?php echo htmlspecialchars($realmItem['avg_online_total_max_level_label']); ?></div>

        <div class="realm-card__progression">
          <strong>Progression:</strong>
          <?php foreach ($realmItem['progression_badges'] as $badge): ?>
            <span class="<?php echo htmlspecialchars($badge['class']); ?>"><?php echo htmlspecialchars($badge['label']); ?></span>
          <?php endforeach; ?>
          <span class="realm-card__avg-ilvl"><strong>Avg iLvl Online / Total:</strong> <?php echo htmlspecialchars($realmItem['avg_ilvl_online_total_label']); ?></span>
        </div>

        <?php if ($debugMode): ?>
        <div class="realm-card__progression realm-card__debug" style="margin-top:10px;">
          <strong>Debug:</strong>
          <div style="margin-top:6px;color:#cdb88a;font-size:.92rem;line-height:1.55;">
            <?php echo htmlspecialchars($realmItem['debug_summary']); ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
<?php
        endforeach;

        return (string)ob_get_clean();
    }
}

if (!function_exists('spp_realmstatus_apply_status_state')) {
    function spp_realmstatus_apply_status_state(array $item, bool $isOnline): array
    {
        $item['res_color'] = $isOnline ? 1 : 0;
        $item['status_label'] = $isOnline ? 'up' : 'down';
        $item['img'] = $isOnline
            ? spp_modern_status_image_url('uparrow2.gif')
            : spp_modern_status_image_url('downarrow2.gif');

        return $item;
    }
}

if (!function_exists('spp_realmstatus_load_page_state')) {
    function spp_realmstatus_load_page_state(array $args = array()): array
    {
        $realmMap = (array)($args['realm_map'] ?? ($GLOBALS['realmDbMap'] ?? array()));
        $get = (array)($args['get'] ?? $_GET);
        $skipCache = !empty($args['skip_cache']);
        $debugMode = !empty($get['debug']);
        $useLocalIpPortTest = spp_config_generic_bool('use_local_ip_port_test', false);
        $selectedRealmId = !empty($realmMap) ? (int)spp_resolve_realm_id($realmMap) : 1;
        $targetRealmIds = spp_realmstatus_target_realm_ids($realmMap, $get);
        $cacheKey = spp_realmstatus_cache_key($selectedRealmId, $debugMode, $useLocalIpPortTest, $targetRealmIds);
        if (!$skipCache) {
            $cachedState = spp_realmstatus_cache_read($cacheKey);
            if (is_array($cachedState)) {
                return $cachedState;
            }
        }

        $items = array();

        foreach ($targetRealmIds as $configRealmId) {
            $realmPdo = spp_get_pdo('realmd', $configRealmId);
            $realm = spp_realmstatus_fetch_realmlist_row($realmPdo, $configRealmId);
            if (empty($realm)) {
                continue;
            }

            if ($useLocalIpPortTest) {
                $realm['address'] = '127.0.0.1';
            }

            $realmId = (int)($realm['id'] ?? 0);
            $buildVersion = trim((string)($realm['realmbuilds'] ?? ''));
            $exp = spp_realmstatus_expansion_for_build($buildVersion);
            $realmHost = trim((string)($realm['address'] ?? ''));
            $realmPort = (int)($realm['port'] ?? 0);
            $realmCapabilities = spp_realm_capabilities($realmMap, $configRealmId);

            $charCfg = null;
            $worldCfg = null;
            try { $charCfg = spp_get_db_config('chars', $configRealmId); } catch (Throwable $e) { $charCfg = null; }
            try { $worldCfg = spp_get_db_config('world', $configRealmId); } catch (Throwable $e) { $worldCfg = null; }

            $charDbName = $charCfg['name'] ?? '';
            $worldDbName = $worldCfg['name'] ?? '';
            $dbHost = $charCfg['host'] ?? ($worldCfg['host'] ?? '');
            $dbPort = (int)($charCfg['port'] ?? ($worldCfg['port'] ?? 0));

            $charPdo = null;
            $hasCharData = 0;
            $hasWorldData = 0;
            try {
                $charPdo = spp_get_pdo('chars', $configRealmId);
            } catch (Throwable $e) {
                $charPdo = null;
            }
            $hasCharData = $charPdo instanceof PDO ? 1 : 0;
            $hasWorldData = $worldDbName !== '' ? 1 : 0;

            $realmReachable = spp_realmstatus_probe_realm($realmHost, $realmPort, 0.2);
            $uptimeStats = spp_realmstatus_fetch_uptime_stats($realmPdo, $realmId);
            $hasRecentUptime = !empty($uptimeStats['recent']);
            $isOnline = $realmReachable;
            $restartCount = (int)($uptimeStats['restart_count'] ?? 0);
            if ($restartCount === 0 && ($isOnline || $hasRecentUptime)) {
                $restartCount = 1;
            }

            $item = array(
                'id' => $configRealmId,
                'realmlist_id' => $realmId,
                'name' => spp_realmstatus_realm_name($configRealmId, $realm),
                'type' => spp_realmstatus_type_label($realm),
                'build' => $buildVersion,
                'exp' => $exp,
                'pop' => 0,
                'online' => 0,
                'alli' => 0,
                'horde' => 0,
                'uptime' => (int)($uptimeStats['uptime'] ?? 0),
                'avg_up' => (float)($uptimeStats['avg_uptime'] ?? 0),
                'median_up' => (int)($uptimeStats['median_uptime'] ?? 0),
                'stable_avg_up' => (float)($uptimeStats['stable_avg_uptime'] ?? 0),
                'stable_runs' => (int)($uptimeStats['stable_runs'] ?? 0),
                'short_restarts' => (int)($uptimeStats['short_restarts'] ?? 0),
                'stats_window_days' => (int)($uptimeStats['stats_window_days'] ?? 0),
                'stats_source' => (string)($uptimeStats['stats_source'] ?? ''),
                'restarts' => $restartCount,
                'avg_lvl_online' => 0,
                'avg_lvl_total' => 0,
                'max_lvl' => 0,
                'avg_ilvl_online' => 0,
                'avg_ilvl_total' => 0,
                'state' => array(),
                'res_color' => 0,
                'status_label' => 'down',
                'img' => spp_modern_status_image_url('downarrow2.gif'),
                'debug' => array(
                    'realm_host' => (string)$dbHost,
                    'realm_port' => $dbPort,
                    'realm_socket_host' => $realmHost,
                    'realm_socket_port' => $realmPort,
                    'realm_reachable' => $realmReachable ? 1 : 0,
                    'recent_uptime' => $hasRecentUptime ? 1 : 0,
                    'char_db' => (string)$charDbName,
                    'world_db' => (string)$worldDbName,
                    'char_ok' => $hasCharData,
                    'world_ok' => $hasWorldData,
                ),
                'has_char_data' => $hasCharData,
            );

            if ($charPdo instanceof PDO && !empty($realmCapabilities['supports_progression'])) {
                $item['state'] = spp_realmstatus_fetch_progression_states($charPdo, $exp);
            } else {
                $item['state'] = spp_realmstatus_fetch_progression_states($realmPdo, 'none');
            }

            if ($charPdo instanceof PDO) {
                $characterStats = spp_realmstatus_fetch_character_stats($charPdo, $worldDbName);
                $item['pop'] = (int)$characterStats['pop'];
                $item['online'] = (int)$characterStats['online'];
                $item['alli'] = (int)$characterStats['alli'];
                $item['horde'] = (int)$characterStats['horde'];
                $item['avg_lvl_online'] = (float)$characterStats['avg_lvl_online'];
                $item['avg_lvl_total'] = (float)$characterStats['avg_lvl_total'];
                $item['max_lvl'] = (int)$characterStats['max_lvl'];
                $item['avg_ilvl_online'] = (float)$characterStats['avg_ilvl_online'];
                $item['avg_ilvl_total'] = (float)$characterStats['avg_ilvl_total'];

                // Socket checks can be misleading on local/dev layouts. If the selected realm DB is live
                // and is actively reporting online characters, treat the realm as up.
                if (!$isOnline && (int)$item['online'] > 0) {
                    $isOnline = true;
                }
            }

            $item = spp_realmstatus_apply_status_state($item, $isOnline);
            $items[] = spp_realmstatus_prepare_item($item, $selectedRealmId, $debugMode);
        }

        $pageState = array(
            'selectedRealmId' => $selectedRealmId,
            'realmstatusTargetRealmIds' => $targetRealmIds,
            'realmstatusDebug' => $debugMode,
            'realmstatusItems' => $items,
            'realmstatusListHtml' => spp_realmstatus_render_cards($items, $debugMode),
            'realmstatusPollUrl' => 'index.php?n=server&sub=realmstatus&ajax=1&realm_ids=' . implode(',', $targetRealmIds) . ($debugMode ? '&debug=1' : ''),
            'realmstatusPolledAtLabel' => date('Y-m-d H:i:s'),
        );

        if (!$skipCache) {
            spp_realmstatus_cache_write($cacheKey, $pageState);
        }

        return $pageState;
    }
}
