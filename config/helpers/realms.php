<?php
// Realm selection and database connection helpers.

if (!function_exists('spp_default_realm_id')) {
    function spp_default_realm_id(array $realmDbMap) {
        if (isset($realmDbMap[1])) {
            return 1;
        }

        foreach (array_keys($realmDbMap) as $realmId) {
            $realmId = (int)$realmId;
            if ($realmId > 0) {
                return $realmId;
            }
        }

        return 1;
    }
}

if (!function_exists('spp_resolve_realm_id')) {
    function spp_resolve_realm_id(array $realmDbMap, $fallback = null) {
        $enabledRealmMap = $GLOBALS['allEnabledRealmDbMap'] ?? array();
        $candidates = [];

        // When a caller passes a realm explicitly, keep that intent instead of
        // letting query-string state redirect the connection to another realm.
        if ($fallback !== null) {
            $candidates[] = $fallback;
        }

        $candidates[] = $_GET['realm'] ?? null;
        $candidates[] = $GLOBALS['activeRealmId'] ?? null;
        $candidates[] = $_COOKIE['cur_selected_realmd'] ?? null;
        $candidates[] = $_COOKIE['cur_selected_realm'] ?? null;
        $candidates[] = $GLOBALS['user']['cur_selected_realmd'] ?? null;

        foreach ($candidates as $candidate) {
            $realmId = (int)$candidate;
            if ($realmId <= 0 || !isset($realmDbMap[$realmId])) {
                continue;
            }
            if (is_array($enabledRealmMap) && !empty($enabledRealmMap) && !isset($enabledRealmMap[$realmId])) {
                continue;
            }
            if ($realmId > 0) {
                return $realmId;
            }
        }

        if (is_array($enabledRealmMap) && !empty($enabledRealmMap)) {
            return spp_default_realm_id($enabledRealmMap);
        }

        return spp_default_realm_id($realmDbMap);
    }
}

if (!function_exists('spp_realm_to_expansion_key')) {
    function spp_realm_to_expansion_key(int $realmId): string
    {
        $map = array(
            1 => 'classic',
            2 => 'tbc',
            3 => 'wotlk',
            4 => 'vmangos',
        );

        return (string)($map[$realmId] ?? '');
    }
}

if (!function_exists('spp_realm_display_name_is_placeholder')) {
    function spp_realm_display_name_is_placeholder(string $name): bool
    {
        $name = trim($name);
        if ($name === '') {
            return true;
        }

        return preg_match('/^Realm\s*#?\s*\d+$/i', $name) === 1;
    }
}

if (!function_exists('spp_realm_display_name_from_info')) {
    function spp_realm_display_name_from_info(array $realmInfo): string
    {
        foreach (array('display_name', 'realm_name', 'name', 'label') as $field) {
            $value = trim((string)($realmInfo[$field] ?? ''));
            if (!spp_realm_display_name_is_placeholder($value)) {
                return $value;
            }
        }

        return '';
    }
}

if (!function_exists('spp_realm_display_name_from_realmlist')) {
    function spp_realm_display_name_from_realmlist(int $realmId): string
    {
        static $cache = array();

        if ($realmId <= 0) {
            return '';
        }

        if (array_key_exists($realmId, $cache)) {
            return $cache[$realmId];
        }

        $name = '';
        if (function_exists('spp_get_pdo')) {
            try {
                $pdo = spp_get_pdo('realmd', $realmId);
                $stmt = $pdo->prepare('SELECT `name` FROM `realmlist` WHERE `id` = ? LIMIT 1');
                $stmt->execute(array($realmId));
                $name = trim((string)($stmt->fetchColumn() ?: ''));
            } catch (Throwable $e) {
                $name = '';
            }
        }

        $cache[$realmId] = spp_realm_display_name_is_placeholder($name) ? '' : $name;
        return $cache[$realmId];
    }
}

if (!function_exists('spp_realm_display_name_from_armory')) {
    function spp_realm_display_name_from_armory(int $realmId): string
    {
        if (!function_exists('spp_get_armory_realm_name') || $realmId <= 0) {
            return '';
        }

        $name = trim((string)(spp_get_armory_realm_name($realmId) ?? ''));
        return spp_realm_display_name_is_placeholder($name) ? '' : $name;
    }
}

if (!function_exists('spp_realm_display_name_from_derived')) {
    function spp_realm_display_name_from_derived(int $realmId, array $realmInfo = array()): string
    {
        $expansionKey = strtolower(trim((string)(function_exists('spp_realm_to_expansion_key') ? spp_realm_to_expansion_key($realmId) : '')));
        $haystack = strtolower(trim(implode(' ', array_filter(array(
            (string)($realmInfo['world'] ?? ''),
            (string)($realmInfo['armory'] ?? ''),
            (string)($realmInfo['realmd'] ?? ''),
            (string)($realmInfo['chars'] ?? ''),
            (string)($realmInfo['bots'] ?? ''),
            $expansionKey,
        )))));

        if ($haystack === '') {
            return '';
        }

        if (strpos($haystack, 'vmangos') !== false) {
            return 'vMaNGOS';
        }
        if (strpos($haystack, 'wrath of the lich king') !== false || strpos($haystack, 'wotlk') !== false) {
            return 'Wrath of the Lich King';
        }
        if (strpos($haystack, 'burning crusade') !== false || strpos($haystack, 'tbc') !== false) {
            return 'The Burning Crusade';
        }
        if (strpos($haystack, 'classic') !== false || strpos($haystack, 'vanilla') !== false || strpos($haystack, 'mangos') !== false) {
            return 'Classic';
        }

        return '';
    }
}

if (!function_exists('spp_realm_display_name')) {
    function spp_realm_display_name(int $realmId, ?array $realmMap = null, string $fallbackPattern = 'Realm %d'): string
    {
        $realmMap = is_array($realmMap) ? $realmMap : (array)($GLOBALS['realmDbMap'] ?? array());
        $realmInfo = (array)($realmMap[$realmId] ?? array());

        $name = spp_realm_display_name_from_info($realmInfo);
        if ($name === '') {
            $name = spp_realm_display_name_from_realmlist($realmId);
        }
        if ($name === '') {
            $name = spp_realm_display_name_from_armory($realmId);
        }
        if ($name === '') {
            $name = spp_realm_display_name_from_derived($realmId, $realmInfo);
        }

        if ($name !== '') {
            return $name;
        }

        return sprintf($fallbackPattern, max(0, $realmId));
    }
}

if (!function_exists('spp_forum_detect_realm_hint')) {
    function spp_forum_detect_realm_hint(array $forum, int $fallbackRealmId = 0): int
    {
        $haystack = strtolower(trim(
            (string)($forum['forum_name'] ?? '') . ' ' . (string)($forum['forum_desc'] ?? '')
        ));

        if ($haystack === '') {
            return $fallbackRealmId;
        }

        $patterns = array(
            3 => array('wrath of the lich king', 'wrath', 'wotlk'),
            2 => array('the burning crusade', 'burning crusade', 'tbc'),
            1 => array('classic', 'vanilla'),
        );

        foreach ($patterns as $realmId => $terms) {
            foreach ($terms as $term) {
                if (strpos($haystack, $term) !== false) {
                    return (int)$realmId;
                }
            }
        }

        return $fallbackRealmId;
    }
}

if (!function_exists('spp_selected_realm_id')) {
    function spp_selected_realm_id(?int $fallbackRealmId = null): int
    {
        $realmDbMap = $GLOBALS['realmDbMap'] ?? array();
        if (!is_array($realmDbMap) || empty($realmDbMap)) {
            return max(1, (int)$fallbackRealmId);
        }

        if (!empty($GLOBALS['activeRealmId']) && isset($realmDbMap[(int)$GLOBALS['activeRealmId']])) {
            return (int)$GLOBALS['activeRealmId'];
        }

        return (int)spp_resolve_realm_id($realmDbMap, $fallbackRealmId);
    }
}

if (!function_exists('spp_website_settings_table_exists')) {
    function spp_website_settings_table_exists(): bool
    {
        static $exists = null;

        if ($exists !== null) {
            return $exists;
        }

        try {
            $pdo = spp_get_pdo('realmd', 1);
            $stmt = $pdo->query("SHOW TABLES LIKE 'website_settings'");
            $exists = $stmt && (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            $exists = false;
        }

        return $exists;
    }
}

if (!function_exists('spp_website_settings_rows')) {
    function spp_website_settings_rows(): array
    {
        static $rows = null;

        if ($rows !== null) {
            return $rows;
        }

        $rows = array();
        if (!spp_website_settings_table_exists()) {
            return $rows;
        }

        try {
            $pdo = spp_get_pdo('realmd', 1);
            $stmt = $pdo->query("SELECT `setting_key`, `setting_value` FROM `website_settings`");
            foreach ((array)$stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $key = trim((string)($row['setting_key'] ?? ''));
                if ($key !== '') {
                    $rows[$key] = (string)($row['setting_value'] ?? '');
                }
            }
        } catch (Throwable $e) {
            $rows = array();
        }

        return $rows;
    }
}

if (!function_exists('spp_realm_runtime_setting_value')) {
    function spp_realm_runtime_setting_value(string $key, $default = null, $config = null)
    {
        $rows = spp_website_settings_rows();
        if (array_key_exists($key, $rows)) {
            return $rows[$key];
        }

        $legacyKeyMap = array(
            'realm_runtime.multirealm' => array('generic_values', 'realm_info', 'multirealm'),
            'realm_runtime.default_realm_id' => array('generic_values', 'realm_info', 'default_realm_id'),
            'realm_runtime.enabled_realm_ids' => array('generic_values', 'realm_runtime', 'enabled_realm_ids'),
            'realm_runtime.selection_mode' => array('generic_values', 'realm_runtime', 'selection_mode'),
        );
        if (isset($legacyKeyMap[$key])) {
            $value = spp_config_path($legacyKeyMap[$key], null, $config);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return $default;
    }
}

if (!function_exists('spp_realm_runtime_selection_mode')) {
    function spp_realm_runtime_selection_mode($config = null): string
    {
        $mode = trim((string)spp_realm_runtime_setting_value('realm_runtime.selection_mode', '', $config));
        if ($mode === '') {
            $mode = 'manual';
        }

        if ($mode !== 'manual') {
            $mode = 'manual';
        }

        return $mode;
    }
}

if (!function_exists('spp_realm_runtime_multirealm_enabled')) {
    function spp_realm_runtime_multirealm_enabled($config = null): bool
    {
        return (int)spp_realm_runtime_setting_value('realm_runtime.multirealm', spp_config_realm_info('multirealm', 0, $config), $config) === 1;
    }
}

if (!function_exists('spp_realm_runtime_default_realm_id')) {
    function spp_realm_runtime_default_realm_id(array $realmDbMap, $config = null): int
    {
        $defaultRealmId = (int)spp_realm_runtime_setting_value('realm_runtime.default_realm_id', spp_config_realm_info('default_realm_id', 0, $config), $config);
        if ($defaultRealmId > 0 && isset($realmDbMap[$defaultRealmId])) {
            return $defaultRealmId;
        }

        return (int)spp_default_realm_id($realmDbMap);
    }
}

if (!function_exists('spp_parse_realm_id_list')) {
    function spp_parse_realm_id_list($value): array
    {
        if (is_array($value)) {
            $items = $value;
        } else {
            $value = trim((string)$value);
            if ($value === '') {
                return array();
            }

            if ($value[0] === '[' || $value[0] === '{') {
                $decoded = json_decode($value, true);
                $items = is_array($decoded) ? $decoded : preg_split('/[\s,;]+/', $value);
            } else {
                $items = preg_split('/[\s,;]+/', $value);
            }
        }

        $ids = array();
        foreach ((array)$items as $item) {
            if (!is_scalar($item)) {
                continue;
            }
            $id = (int)$item;
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }
}

if (!function_exists('spp_realm_runtime_enabled_realm_ids')) {
    function spp_realm_runtime_enabled_realm_ids(array $realmDbMap, $config = null): array
    {
        $savedValue = spp_realm_runtime_setting_value('realm_runtime.enabled_realm_ids', null, $config);
        $enabledIds = spp_parse_realm_id_list($savedValue);

        if (empty($enabledIds)) {
            return array_values(array_map('intval', array_keys($realmDbMap)));
        }

        $filtered = array();
        foreach ($enabledIds as $realmId) {
            if (isset($realmDbMap[$realmId])) {
                $filtered[] = $realmId;
            }
        }

        return !empty($filtered)
            ? array_values(array_unique($filtered))
            : array_values(array_map('intval', array_keys($realmDbMap)));
    }
}

if (!function_exists('spp_realm_runtime_enabled_realm_map')) {
    function spp_realm_runtime_enabled_realm_map(array $realmDbMap, $config = null): array
    {
        if (empty($realmDbMap)) {
            return array();
        }

        $enabledRealmMap = array();
        foreach (spp_realm_runtime_enabled_realm_ids($realmDbMap, $config) as $realmId) {
            if (isset($realmDbMap[$realmId])) {
                $enabledRealmMap[$realmId] = $realmDbMap[$realmId];
            }
        }

        return $enabledRealmMap;
    }
}

if (!function_exists('spp_realm_runtime_session_cache')) {
    function spp_realm_runtime_session_cache(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return array();
        }

        return array(
            'resolved_active_realm_id' => (int)($_SESSION['resolved_active_realm_id'] ?? 0),
            'resolved_active_realm_source' => trim((string)($_SESSION['resolved_active_realm_source'] ?? '')),
            'resolved_active_realm_at' => (int)($_SESSION['resolved_active_realm_at'] ?? 0),
        );
    }
}

if (!function_exists('spp_realm_runtime_store_session_cache')) {
    function spp_realm_runtime_store_session_cache(int $realmId, string $source): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE || $realmId <= 0) {
            return;
        }

        $_SESSION['resolved_active_realm_id'] = $realmId;
        $_SESSION['resolved_active_realm_source'] = $source;
        $_SESSION['resolved_active_realm_at'] = time();
    }
}

if (!function_exists('spp_realm_runtime_probe_enabled_realm_ids')) {
    function spp_realm_runtime_probe_enabled_realm_ids(array $enabledRealmMap, $config = null): array
    {
        $reachable = array();
        $useLocalIpPortTest = (int)spp_config_generic('use_local_ip_port_test', 0, $config) === 1;
        foreach ($enabledRealmMap as $realmId => $realmInfo) {
            $realmId = (int)$realmId;
            if ($realmId <= 0) {
                continue;
            }

            $realmHost = '';
            $realmPort = 0;
            try {
                $realmPdo = spp_get_pdo('realmd', $realmId);
                $stmt = $realmPdo->prepare("SELECT `address`, `port` FROM `realmlist` WHERE `id` = ? LIMIT 1");
                $stmt->execute(array($realmId));
                $realmRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: array();
                $realmHost = trim((string)($realmRow['address'] ?? ''));
                $realmPort = (int)($realmRow['port'] ?? 0);
            } catch (Throwable $e) {
                $realmHost = '';
                $realmPort = 0;
            }

            if ($realmHost === '' || $realmPort <= 0) {
                $realmHost = trim((string)($realmInfo['address'] ?? ''));
                $realmPort = (int)($realmInfo['port'] ?? 0);
            }

            if ($realmHost === '' || $realmPort <= 0) {
                continue;
            }

            if ($useLocalIpPortTest) {
                $realmHost = '127.0.0.1';
            }

            $timeout = 0.2;
            $isReachable = false;
            if (function_exists('spp_realmstatus_probe_realm')) {
                $isReachable = spp_realmstatus_probe_realm($realmHost, $realmPort, $timeout);
            } else {
                $errno = 0;
                $errstr = '';
                $socket = @fsockopen($realmHost, $realmPort, $errno, $errstr, (float)$timeout);
                $isReachable = (bool)$socket;
                if ($socket) {
                    fclose($socket);
                }
            }

            if ($isReachable) {
                $reachable[] = (int)$realmId;
            }
        }

        return array_values(array_unique($reachable));
    }
}

if (!function_exists('spp_realm_runtime_resolve_active_realm_id')) {
    function spp_realm_runtime_resolve_active_realm_id(array $realmDbMap, $config = null): array
    {
        $enabledRealmMap = spp_realm_runtime_enabled_realm_map($realmDbMap, $config);
        $defaultRealmId = spp_realm_runtime_default_realm_id($realmDbMap, $config);
        $selectionMode = spp_realm_runtime_selection_mode($config);
        $multirealmEnabled = spp_realm_runtime_multirealm_enabled($config);

        if (empty($enabledRealmMap)) {
            return array(
                'active_realm_id' => $defaultRealmId,
                'active_realm_source' => 'default',
                'active_realm_at' => 0,
                'enabled_realm_map' => array(),
                'enabled_realm_ids' => array(),
                'selection_mode' => $selectionMode,
                'multirealm' => $multirealmEnabled ? 1 : 0,
                'default_realm_id' => $defaultRealmId,
            );
        }

        $activeRealmId = 0;
        $activeRealmSource = 'default';
        $activeRealmAt = 0;

        $requestRealmId = 0;
        if (isset($_REQUEST['changerealm_to']) && ctype_digit((string)$_REQUEST['changerealm_to'])) {
            $requestRealmId = (int)$_REQUEST['changerealm_to'];
        } elseif (isset($_GET['realm']) && ctype_digit((string)$_GET['realm'])) {
            $requestRealmId = (int)$_GET['realm'];
        } elseif (isset($_COOKIE['cur_selected_realmd']) && ctype_digit((string)$_COOKIE['cur_selected_realmd'])) {
            $requestRealmId = (int)$_COOKIE['cur_selected_realmd'];
        } elseif (isset($_COOKIE['cur_selected_realm']) && ctype_digit((string)$_COOKIE['cur_selected_realm'])) {
            $requestRealmId = (int)$_COOKIE['cur_selected_realm'];
        } elseif (!empty($GLOBALS['user']['cur_selected_realmd'])) {
            $requestRealmId = (int)$GLOBALS['user']['cur_selected_realmd'];
        }

        if ($requestRealmId > 0 && isset($enabledRealmMap[$requestRealmId])) {
            $activeRealmId = $requestRealmId;
            $activeRealmSource = 'manual';
        } elseif (isset($enabledRealmMap[$defaultRealmId])) {
            $activeRealmId = $defaultRealmId;
        } else {
            $activeRealmId = (int)array_key_first($enabledRealmMap);
        }

        if ($activeRealmId <= 0 || !isset($enabledRealmMap[$activeRealmId])) {
            $activeRealmId = (int)array_key_first($enabledRealmMap);
        }

        return array(
            'active_realm_id' => $activeRealmId,
            'active_realm_source' => $activeRealmSource,
            'active_realm_at' => $activeRealmAt,
            'enabled_realm_map' => $enabledRealmMap,
            'enabled_realm_ids' => array_keys($enabledRealmMap),
            'selection_mode' => $selectionMode,
            'multirealm' => $multirealmEnabled ? 1 : 0,
            'default_realm_id' => $defaultRealmId,
        );
    }
}

if (!function_exists('spp_realm_runtime_sync_selected_realm_cookie')) {
    function spp_realm_runtime_sync_selected_realm_cookie(int $realmId): void
    {
        if ($realmId <= 0) {
            return;
        }

        setcookie('cur_selected_realmd', $realmId, time() + 86400, '/');
        setcookie('cur_selected_realm', $realmId, time() + 86400, '/');
        $_COOKIE['cur_selected_realmd'] = (string)$realmId;
        $_COOKIE['cur_selected_realm'] = (string)$realmId;
    }
}

if (!function_exists('spp_realm_runtime_apply_bootstrap')) {
    function spp_realm_runtime_apply_bootstrap(array $configuredRealmDbMap, ?string $component = null, ?string $subpage = null, $config = null): array
    {
        $runtimeCatalog = function_exists('spp_realm_runtime_catalog')
            ? spp_realm_runtime_catalog($configuredRealmDbMap)
            : array(
                'realm_db_map' => $configuredRealmDbMap,
                'runtime_realm_db_map' => $configuredRealmDbMap,
                'fallback_realm_db_map' => $configuredRealmDbMap,
                'config_only_realm_db_map' => array(),
                'source' => 'config',
                'diagnostics' => array(),
            );
        $runtimeRealmDbMap = (array)($runtimeCatalog['runtime_realm_db_map'] ?? $runtimeCatalog['realm_db_map'] ?? $configuredRealmDbMap);
        if (empty($runtimeRealmDbMap)) {
            $runtimeRealmDbMap = $configuredRealmDbMap;
        }

        $runtime = spp_realm_runtime_resolve_active_realm_id($runtimeRealmDbMap, $config);
        $enabledRealmMap = (array)($runtime['enabled_realm_map'] ?? array());
        $activeRealmId = (int)($runtime['active_realm_id'] ?? 0);
        $component = strtolower(trim((string)$component));
        $subpage = strtolower(trim((string)$subpage));
        $allowAllEnabled = (
            $component === 'admin'
            || ($component === 'server' && in_array($subpage, array('realmstatus', 'realmlist'), true))
        );

        $requestRealmMap = $enabledRealmMap;
        if ((int)($runtime['multirealm'] ?? 0) !== 1 && !$allowAllEnabled) {
            $requestRealmMap = ($activeRealmId > 0 && isset($enabledRealmMap[$activeRealmId]))
                ? array($activeRealmId => $enabledRealmMap[$activeRealmId])
                : array();
        }

        $db = $GLOBALS['db'] ?? array();
        $activeRealm = ($activeRealmId > 0 && isset($runtimeRealmDbMap[$activeRealmId]))
            ? $runtimeRealmDbMap[$activeRealmId]
            : array();

        $GLOBALS['spp_realm_runtime'] = $runtime;
        $GLOBALS['realmRuntimeCatalog'] = $runtimeCatalog;
        $GLOBALS['fallbackConfiguredRealmDbMap'] = (array)($runtimeCatalog['fallback_realm_db_map'] ?? $configuredRealmDbMap);
        $GLOBALS['configOnlyRealmDbMap'] = (array)($runtimeCatalog['config_only_realm_db_map'] ?? array());
        $GLOBALS['allConfiguredRealmDbMap'] = $runtimeRealmDbMap;
        $GLOBALS['dbBackedRealmDbMap'] = (array)($runtimeCatalog['realm_db_map'] ?? array());
        $GLOBALS['allEnabledRealmDbMap'] = $enabledRealmMap;
        $GLOBALS['allRealmDbMap'] = $enabledRealmMap;
        $GLOBALS['realmDbMap'] = $requestRealmMap;
        $GLOBALS['activeRealmId'] = $activeRealmId;
        $GLOBALS['activeRealm'] = $activeRealm;

        if (!empty($db) && !empty($activeRealm)) {
            $GLOBALS['realmd'] = array(
                'db_type' => 'mysql',
                'db_host' => $db['host'],
                'db_port' => $db['port'],
                'db_username' => $db['user'],
                'db_password' => $db['pass'],
                'db_name' => (string)($activeRealm['realmd'] ?? ''),
                'db_encoding' => 'utf8',
                'req_reg_invite' => (int)spp_config_generic('req_reg_invite', 0, $config),
            );
            $GLOBALS['worlddb'] = array(
                'db_type' => 'mysql',
                'db_host' => $db['host'],
                'db_port' => $db['port'],
                'db_username' => $db['user'],
                'db_password' => $db['pass'],
                'db_name' => (string)($activeRealm['world'] ?? ''),
                'db_encoding' => 'utf8',
            );
            $GLOBALS['DB'] = $GLOBALS['worlddb'];
        }

        $bootstrapState = $GLOBALS['spp_bootstrap_state'] ?? array();
        if (is_array($bootstrapState)) {
            $bootstrapState['default_realm_id'] = (int)($runtime['default_realm_id'] ?? 0);
            $bootstrapState['multirealm'] = (int)($runtime['multirealm'] ?? 0);
            $bootstrapState['selection_mode'] = (string)($runtime['selection_mode'] ?? 'manual');
            $bootstrapState['enabled_realm_ids'] = array_values(array_map('intval', array_keys($enabledRealmMap)));
            $bootstrapState['active_realm_id'] = $activeRealmId;
            $bootstrapState['active_realm_source'] = (string)($runtime['active_realm_source'] ?? 'default');
            $bootstrapState['realm_definitions_source'] = (string)($runtimeCatalog['source'] ?? 'config');
            $bootstrapState['realm_definition_diagnostics'] = array_values((array)($runtimeCatalog['diagnostics'] ?? array()));
            $GLOBALS['spp_bootstrap_state'] = $bootstrapState;
        }

        spp_realm_runtime_sync_selected_realm_cookie($activeRealmId);

        return $runtime;
    }
}

if (!function_exists('spp_current_realm_id')) {
    function spp_current_realm_id(?array $realmDbMap = null, $config = null): int
    {
        $realmDbMap = is_array($realmDbMap) ? $realmDbMap : ($GLOBALS['realmDbMap'] ?? array());
        if (empty($realmDbMap)) {
            return max(1, (int)spp_default_realm_id(array()));
        }

        $runtime = spp_realm_runtime_resolve_active_realm_id($realmDbMap, $config);
        return (int)($runtime['active_realm_id'] ?? spp_default_realm_id($realmDbMap));
    }
}

if (!function_exists('spp_get_db_config')) {
    function spp_get_db_config($target = 'realmd', $realmId = null) {
        $db = $GLOBALS['db'] ?? null;
        $realmDbMap = $GLOBALS['realmDbMap'] ?? null;

        if (!is_array($db) || !is_array($realmDbMap) || empty($realmDbMap)) {
            throw new RuntimeException('Database configuration is not loaded.');
        }

        $resolvedRealmId = spp_resolve_realm_id($realmDbMap, $realmId);
        if (!isset($realmDbMap[$resolvedRealmId])) {
            throw new RuntimeException('Invalid realm selected.');
        }

        $realm = $realmDbMap[$resolvedRealmId];
        $dbKey = $target === 'world' ? 'world' : $target;

        if (!isset($realm[$dbKey])) {
            throw new RuntimeException('Unknown database target: ' . $target);
        }

        return [
            'host' => $db['host'],
            'port' => $db['port'],
            'user' => $db['user'],
            'pass' => $db['pass'],
            'name' => $realm[$dbKey],
            'realm_id' => $resolvedRealmId,
            'charset' => 'utf8mb4',
        ];
    }
}

if (!function_exists('spp_get_pdo')) {
    function spp_get_pdo($target = 'realmd', $realmId = null) {
        static $connections = [];

        $config = spp_get_db_config($target, $realmId);
        $cacheKey = $target . ':' . $config['realm_id'] . ':' . $config['name'];

        if (!isset($connections[$cacheKey])) {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];

            try {
                $connections[$cacheKey] = new PDO(
                    "mysql:host={$config['host']};port={$config['port']};dbname={$config['name']};charset={$config['charset']}",
                    $config['user'],
                    $config['pass'],
                    $options
                );
            } catch (Throwable $e) {
                if ($target !== 'armory') {
                    throw $e;
                }

                $realmDbMap = $GLOBALS['realmDbMap'] ?? [];
                $tried = [$config['name'] => true];
                foreach ($realmDbMap as $fallbackRealm) {
                    $fallbackName = $fallbackRealm['armory'] ?? null;
                    if (!$fallbackName || isset($tried[$fallbackName])) {
                        continue;
                    }
                    $tried[$fallbackName] = true;
                    try {
                        $fallbackCacheKey = $target . ':' . $config['realm_id'] . ':' . $fallbackName;
                        if (!isset($connections[$fallbackCacheKey])) {
                            $connections[$fallbackCacheKey] = new PDO(
                                "mysql:host={$config['host']};port={$config['port']};dbname={$fallbackName};charset={$config['charset']}",
                                $config['user'],
                                $config['pass'],
                                $options
                            );
                        }
                        error_log('[config] armory fallback: using ' . $fallbackName . ' for realm ' . (int)$config['realm_id']);
                        return $connections[$fallbackCacheKey];
                    } catch (Throwable $fallbackError) {
                        continue;
                    }
                }

                throw $e;
            }
        }

        return $connections[$cacheKey];
    }
}

if (!function_exists('spp_get_realm_service_config')) {
    function spp_get_realm_service_config($service, $realmId = null) {
        $realmDbMap = $GLOBALS['realmDbMap'] ?? null;
        $serviceDefaults = $GLOBALS['serviceDefaults'] ?? [];
        if (!is_array($realmDbMap) || empty($realmDbMap)) {
            return null;
        }

        $resolvedRealmId = spp_resolve_realm_id($realmDbMap, $realmId);
        if (!isset($realmDbMap[$resolvedRealmId]) || !is_array($realmDbMap[$resolvedRealmId])) {
            return null;
        }

        $service = strtolower(trim((string)$service));
        $defaultConfig = isset($serviceDefaults[$service]) && is_array($serviceDefaults[$service]) ? $serviceDefaults[$service] : [];
        $realmConfig = $realmDbMap[$resolvedRealmId][$service] ?? [];
        if (!is_array($realmConfig)) {
            $realmConfig = [];
        }

        $mergedConfig = array_merge($defaultConfig, $realmConfig);
        return !empty($mergedConfig) ? $mergedConfig : null;
    }
}

if (!function_exists('spp_get_armory_realm_name')) {
    function spp_get_armory_realm_name($realmId = null) {
        static $cache = [];

        $db = $GLOBALS['db'] ?? null;
        $realmDbMap = $GLOBALS['realmDbMap'] ?? null;

        if (!is_array($db) || !is_array($realmDbMap) || empty($realmDbMap)) {
            return null;
        }

        $resolvedRealmId = spp_resolve_realm_id($realmDbMap, $realmId);
        if (isset($cache[$resolvedRealmId])) {
            return $cache[$resolvedRealmId];
        }

        $fallback = null;
        if (!empty($realmDbMap[$resolvedRealmId]['name'])) {
            $fallback = (string)$realmDbMap[$resolvedRealmId]['name'];
        }
        $realmdDb = $realmDbMap[$resolvedRealmId]['realmd'] ?? null;
        if (!$realmdDb) {
            return $cache[$resolvedRealmId] = $fallback;
        }

        try {
            $pdo = new PDO(
                "mysql:host={$db['host']};port={$db['port']};dbname={$realmdDb};charset=utf8mb4",
                $db['user'],
                $db['pass'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );

            $stmt = $pdo->prepare("SELECT `name` FROM `realmlist` WHERE `id` = ? LIMIT 1");
            $stmt->execute([(int)$resolvedRealmId]);
            $row = $stmt->fetch();

            $cache[$resolvedRealmId] = !empty($row['name']) ? $row['name'] : $fallback;
            return $cache[$resolvedRealmId];
        } catch (Throwable $e) {
            error_log('[config] Failed resolving armory realm name: ' . $e->getMessage());
            return $cache[$resolvedRealmId] = $fallback;
        }
    }
}
