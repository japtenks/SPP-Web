<?php
// Runtime configuration, routing, forum, profile, and identity helpers.

if (!function_exists('spp_env')) {
    function spp_env(string $name, $default = null)
    {
        $value = getenv($name);
        if ($value !== false && $value !== '') {
            return $value;
        }

        if (isset($_ENV[$name]) && $_ENV[$name] !== '') {
            return $_ENV[$name];
        }

        if (isset($_SERVER[$name]) && $_SERVER[$name] !== '') {
            return $_SERVER[$name];
        }

        return $default;
    }
}

if (!function_exists('spp_load_config_array')) {
    function spp_load_config_array(string $path): array
    {
        if ($path === '' || !is_file($path)) {
            return array();
        }

        $config = include $path;

        return is_array($config) ? $config : array();
    }
}

if (!function_exists('spp_merge_config_arrays')) {
    function spp_merge_config_arrays(array ...$configSets): array
    {
        $merged = array();

        foreach ($configSets as $configSet) {
            foreach ($configSet as $key => $value) {
                if (isset($merged[$key]) && is_array($merged[$key]) && is_array($value)) {
                    $merged[$key] = spp_merge_config_arrays($merged[$key], $value);
                    continue;
                }

                $merged[$key] = $value;
            }
        }

        return $merged;
    }
}

if (!function_exists('spp_template_name')) {
    function spp_template_name($config = null): string {
        $configuredName = (string)spp_config_path(array('templates', 'selected'), '', $config);
        if ($configuredName !== '') {
            return $configuredName;
        }

        $configuredName = (string)spp_config_path(array('templates', 'default'), '', $config);
        if ($configuredName !== '') {
            return $configuredName;
        }

        $templateNode = spp_config_path(array('templates', 'template'), null, $config);
        if (is_array($templateNode)) {
            foreach ($templateNode as $templateName) {
                $templateName = trim((string)$templateName);
                if ($templateName !== '') {
                    return $templateName;
                }
            }
        } elseif (is_object($templateNode)) {
            foreach (get_object_vars($templateNode) as $templateName) {
                $templateName = trim((string)$templateName);
                if ($templateName !== '') {
                    return $templateName;
                }
            }
        } elseif (trim((string)$templateNode) !== '') {
            return trim((string)$templateNode);
        }

        $bootstrapState = spp_bootstrap_state();
        $configuredName = trim((string)($bootstrapState['template_name'] ?? ''));

        return $configuredName !== '' ? $configuredName : 'offlike';
    }
}

if (!function_exists('spp_template_root')) {
    function spp_template_root($config = null): string {
        return 'templates/' . spp_template_name($config);
    }
}

if (!function_exists('spp_runtime_config_source_path')) {
    function spp_runtime_config_source_path(): string
    {
        return dirname(__DIR__) . '/config-protected.php';
    }
}

if (!function_exists('spp_bootstrap_state')) {
    function spp_bootstrap_state(): array
    {
        spp_runtime_config_array();

        $state = $GLOBALS['spp_bootstrap_state'] ?? array();
        return is_array($state) ? $state : array();
    }
}

if (!function_exists('spp_runtime_config_array')) {
    function spp_runtime_config_array(): array
    {
        static $bootstrapping = false;

        if (isset($GLOBALS['runtimeConfig']) && is_array($GLOBALS['runtimeConfig'])) {
            return $GLOBALS['runtimeConfig'];
        }

        if ($bootstrapping) {
            return array();
        }

        $bootstrapping = true;
        require spp_runtime_config_source_path();
        $bootstrapping = false;

        $runtimeConfig = $GLOBALS['runtimeConfig'] ?? array();

        return is_array($runtimeConfig) ? $runtimeConfig : array();
    }
}

if (!function_exists('spp_config_objectify')) {
    function spp_config_objectify($value)
    {
        if (!is_array($value)) {
            return is_scalar($value) || $value === null ? $value : (string)$value;
        }

        $object = new stdClass();
        foreach ($value as $key => $childValue) {
            $object->{$key} = spp_config_objectify($childValue);
        }

        return $object;
    }
}

if (!function_exists('spp_config_segment_exists')) {
    function spp_config_segment_exists($config, string $segment): bool
    {
        if (is_array($config)) {
            return array_key_exists($segment, $config);
        }

        if (is_object($config)) {
            return isset($config->{$segment}) || property_exists($config, $segment);
        }

        return false;
    }
}

if (!function_exists('spp_config_segment_value')) {
    function spp_config_segment_value($config, string $segment)
    {
        if (is_array($config)) {
            return $config[$segment];
        }

        if (is_object($config)) {
            return $config->{$segment};
        }

        return null;
    }
}

if (!function_exists('spp_runtime_config')) {
    function spp_runtime_config()
    {
        static $runtimeConfigObject = null;

        if ($runtimeConfigObject !== null) {
            return $runtimeConfigObject;
        }

        $runtimeConfigObject = spp_config_objectify(spp_runtime_config_array());

        return $runtimeConfigObject;
    }
}

if (!function_exists('spp_bootstrap_request_scheme')) {
    function spp_bootstrap_request_scheme(): string
    {
        $bootstrapState = spp_bootstrap_state();
        $requestScheme = trim((string)($bootstrapState['request_scheme'] ?? ''));

        return $requestScheme !== '' ? $requestScheme : 'http';
    }
}

if (!function_exists('spp_bootstrap_session_cookie_options')) {
    function spp_bootstrap_session_cookie_options(): array
    {
        $bootstrapState = spp_bootstrap_state();
        $sessionCookieParams = session_get_cookie_params();
        $options = array(
            'lifetime' => (int)($sessionCookieParams['lifetime'] ?? 0),
            'path' => (string)($bootstrapState['site_href'] ?? '/'),
            'domain' => (string)($sessionCookieParams['domain'] ?? ''),
            'secure' => spp_bootstrap_request_scheme() === 'https',
            'httponly' => true,
        );

        if (defined('PHP_VERSION_ID') && PHP_VERSION_ID >= 70300) {
            $options['samesite'] = 'Lax';
        }

        return $options;
    }
}

if (!function_exists('spp_bootstrap_template_root')) {
    function spp_bootstrap_template_root($config = null): string
    {
        return spp_template_root($config);
    }
}

if (!function_exists('spp_website_settings_table_name')) {
    function spp_website_settings_table_name(): string
    {
        return 'website_settings';
    }
}

if (!function_exists('spp_website_settings_pdo')) {
    function spp_website_settings_pdo(): PDO
    {
        static $pdo = null;

        if ($pdo instanceof PDO) {
            return $pdo;
        }

        $realmDbMap = $GLOBALS['allConfiguredRealmDbMap'] ?? $GLOBALS['realmDbMap'] ?? array();
        if (!is_array($realmDbMap) || empty($realmDbMap)) {
            return spp_get_pdo('realmd', 1);
        }

        $db = $GLOBALS['db'] ?? array();
        $runtime = $GLOBALS['realmRuntime'] ?? array();
        $preferredRealmId = (int)($runtime['default_realm_id'] ?? 0);
        if ($preferredRealmId <= 0 || !isset($realmDbMap[$preferredRealmId])) {
            $preferredRealmId = (int)spp_default_realm_id($realmDbMap);
        }
        $realmdDbName = (string)($realmDbMap[$preferredRealmId]['realmd'] ?? '');

        if (!empty($db['host']) && !empty($db['user']) && $realmdDbName !== '') {
            $pdo = new PDO(
                'mysql:host=' . (string)$db['host'] . ';port=' . (int)$db['port'] . ';dbname=' . $realmdDbName . ';charset=utf8mb4',
                (string)$db['user'],
                (string)$db['pass'],
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                )
            );
            return $pdo;
        }

        return $pdo = spp_get_pdo('realmd', $preferredRealmId);
    }
}

if (!function_exists('spp_auth_candidate_realm_map')) {
    function spp_auth_candidate_realm_map(): array
    {
        $realmDbMap = $GLOBALS['allConfiguredRealmDbMap'] ?? $GLOBALS['realmDbMap'] ?? array();
        return is_array($realmDbMap) ? $realmDbMap : array();
    }
}

if (!function_exists('spp_auth_candidate_realm_ids')) {
    function spp_auth_candidate_realm_ids(?array $realmDbMap = null): array
    {
        $realmDbMap = is_array($realmDbMap) ? $realmDbMap : spp_auth_candidate_realm_map();
        if (empty($realmDbMap)) {
            return array(1);
        }

        $candidateIds = array();
        $defaultRealmId = function_exists('spp_realm_runtime_default_realm_id')
            ? (int)spp_realm_runtime_default_realm_id($realmDbMap)
            : 0;
        if ($defaultRealmId > 0 && isset($realmDbMap[$defaultRealmId])) {
            $candidateIds[] = $defaultRealmId;
        }

        if (isset($realmDbMap[1])) {
            $candidateIds[] = 1;
        }

        foreach (array_keys($realmDbMap) as $realmId) {
            $candidateIds[] = (int)$realmId;
        }

        $candidateIds = array_values(array_unique(array_filter($candidateIds)));
        return empty($candidateIds) ? array(1) : $candidateIds;
    }
}

if (!function_exists('spp_db_table_exists')) {
    function spp_db_table_exists(PDO $pdo, string $tableName): bool
    {
        $tableName = trim($tableName);
        if ($tableName === '') {
            return false;
        }

        try {
            $stmt = $pdo->prepare('SHOW TABLES LIKE ?');
            $stmt->execute(array($tableName));
            return (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('spp_db_table_columns')) {
    function spp_db_table_columns(PDO $pdo, string $tableName): array
    {
        static $cache = array();

        $tableName = trim($tableName);
        if ($tableName === '') {
            return array();
        }

        $cacheKey = spl_object_hash($pdo) . ':' . $tableName;
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $columns = array();
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM `{$tableName}`");
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array();
            foreach ($rows as $row) {
                $field = (string)($row['Field'] ?? '');
                if ($field !== '') {
                    $columns[$field] = true;
                }
            }
        } catch (Throwable $e) {
            $columns = array();
        }

        $cache[$cacheKey] = $columns;
        return $columns;
    }
}

if (!function_exists('spp_db_column_exists')) {
    function spp_db_column_exists(PDO $pdo, string $tableName, string $columnName): bool
    {
        $columns = spp_db_table_columns($pdo, $tableName);
        return !empty($columns[$columnName]);
    }
}

if (!function_exists('spp_auth_realm_capabilities')) {
    function spp_auth_realm_capabilities(int $realmId): array
    {
        static $cache = array();

        $realmId = max(1, (int)$realmId);
        if (isset($cache[$realmId])) {
            return $cache[$realmId];
        }

        $capabilities = array(
            'realm_id' => $realmId,
            'available' => false,
            'has_account_table' => false,
            'has_website_accounts' => false,
            'has_website_account_groups' => false,
            'has_website_account_keys' => false,
            'has_account_logons' => false,
            'has_ip_banned' => false,
            'has_account_pass' => false,
            'supports_canonical_website_tables' => false,
            'supports_account_provisioning' => false,
            'supports_ban_lookup' => false,
            'account_columns' => array(),
            'website_account_columns' => array(),
        );

        try {
            $pdo = spp_get_pdo('realmd', $realmId);
            $capabilities['available'] = true;
            $capabilities['has_account_table'] = spp_db_table_exists($pdo, 'account');
            $capabilities['has_website_accounts'] = spp_db_table_exists($pdo, 'website_accounts');
            $capabilities['has_website_account_groups'] = spp_db_table_exists($pdo, 'website_account_groups');
            $capabilities['has_website_account_keys'] = spp_db_table_exists($pdo, 'website_account_keys');
            $capabilities['has_account_logons'] = spp_db_table_exists($pdo, 'account_logons');
            $capabilities['has_ip_banned'] = spp_db_table_exists($pdo, 'ip_banned');
            $capabilities['has_account_pass'] = spp_db_table_exists($pdo, 'account_pass');
            $capabilities['account_columns'] = $capabilities['has_account_table'] ? spp_db_table_columns($pdo, 'account') : array();
            $capabilities['website_account_columns'] = $capabilities['has_website_accounts'] ? spp_db_table_columns($pdo, 'website_accounts') : array();
            $capabilities['supports_canonical_website_tables'] = $capabilities['has_website_accounts']
                && $capabilities['has_website_account_groups']
                && $capabilities['has_website_account_keys'];
            $capabilities['supports_account_provisioning'] = $capabilities['has_account_table']
                && !empty($capabilities['account_columns']['username'])
                && !empty($capabilities['account_columns']['s'])
                && !empty($capabilities['account_columns']['v']);
            $capabilities['supports_ban_lookup'] = $capabilities['has_ip_banned'] && $capabilities['has_account_logons'];
        } catch (Throwable $e) {
            $capabilities['error'] = $e->getMessage();
        }

        $cache[$realmId] = $capabilities;
        return $cache[$realmId];
    }
}

if (!function_exists('spp_canonical_auth_realm_id')) {
    function spp_canonical_auth_realm_id(?array $realmDbMap = null): int
    {
        static $resolvedId = null;

        if ($resolvedId !== null && $realmDbMap === null) {
            return $resolvedId;
        }

        $realmDbMap = is_array($realmDbMap) ? $realmDbMap : spp_auth_candidate_realm_map();
        $candidateIds = spp_auth_candidate_realm_ids($realmDbMap);

        foreach ($candidateIds as $realmId) {
            $capabilities = spp_auth_realm_capabilities((int)$realmId);
            if (!empty($capabilities['supports_canonical_website_tables'])) {
                if ($realmDbMap === spp_auth_candidate_realm_map()) {
                    $resolvedId = (int)$realmId;
                }
                return (int)$realmId;
            }
        }

        $fallbackRealmId = isset($realmDbMap[1]) ? 1 : (int)array_key_first($realmDbMap);
        if ($fallbackRealmId <= 0) {
            $fallbackRealmId = 1;
        }
        if ($realmDbMap === spp_auth_candidate_realm_map()) {
            $resolvedId = $fallbackRealmId;
        }
        return $fallbackRealmId;
    }
}

if (!function_exists('spp_canonical_auth_pdo')) {
    function spp_canonical_auth_pdo(): PDO
    {
        static $pdo = null;

        if ($pdo instanceof PDO) {
            return $pdo;
        }

        $pdo = spp_get_pdo('realmd', spp_canonical_auth_realm_id());
        return $pdo;
    }
}

if (!function_exists('spp_auth_managed_realm_map')) {
    function spp_auth_managed_realm_map(): array
    {
        $realmDbMap = $GLOBALS['allEnabledRealmDbMap'] ?? $GLOBALS['allConfiguredRealmDbMap'] ?? $GLOBALS['realmDbMap'] ?? array();
        return is_array($realmDbMap) ? $realmDbMap : array();
    }
}

if (!function_exists('spp_auth_realm_account_row')) {
    function spp_auth_realm_account_row(int $realmId, $accountId = 0, string $username = ''): ?array
    {
        $capabilities = spp_auth_realm_capabilities($realmId);
        if (empty($capabilities['supports_account_provisioning'])) {
            return null;
        }

        try {
            $pdo = spp_get_pdo('realmd', $realmId);
            if ((int)$accountId > 0) {
                $stmt = $pdo->prepare("SELECT * FROM `account` WHERE `id` = ? LIMIT 1");
                $stmt->execute(array((int)$accountId));
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    return $row;
                }
            }

            $username = strtoupper(trim($username));
            if ($username !== '') {
                $stmt = $pdo->prepare("SELECT * FROM `account` WHERE UPPER(`username`) = ? LIMIT 1");
                $stmt->execute(array($username));
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    return $row;
                }
            }
        } catch (Throwable $e) {
            error_log('[auth] Failed loading realm account row for realm ' . $realmId . ': ' . $e->getMessage());
        }

        return null;
    }
}

if (!function_exists('spp_auth_resolve_managed_account_map')) {
    function spp_auth_resolve_managed_account_map($canonicalAccountId, string $username): array
    {
        $canonicalAccountId = (int)$canonicalAccountId;
        $username = strtoupper(trim($username));
        $accountMap = array();

        foreach (spp_auth_managed_realm_map() as $realmId => $realmInfo) {
            $realmId = (int)$realmId;
            if ($realmId <= 0) {
                continue;
            }

            $row = spp_auth_realm_account_row($realmId, $canonicalAccountId, $username);
            if (!empty($row['id'])) {
                $accountMap[$realmId] = (int)$row['id'];
            }
        }

        return $accountMap;
    }
}

if (!function_exists('spp_auth_account_field_payload')) {
    function spp_auth_account_field_payload(PDO $pdo, array $accountData): array
    {
        $columns = spp_db_table_columns($pdo, 'account');
        $payload = array();
        foreach ($accountData as $field => $value) {
            if (!empty($columns[$field])) {
                $payload[$field] = $value;
            }
        }

        return $payload;
    }
}

if (!function_exists('spp_auth_sync_account_pass_table')) {
    function spp_auth_sync_account_pass_table(PDO $pdo, array $accountRow, ?string $plainPassword = null): void
    {
        if ((int)spp_config_generic('use_purepass_table', 0) !== 1 || !spp_db_table_exists($pdo, 'account_pass')) {
            return;
        }

        $accountId = (int)($accountRow['id'] ?? 0);
        if ($accountId <= 0) {
            return;
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `account_pass` WHERE `id` = ?");
        $stmt->execute(array($accountId));
        $exists = (int)$stmt->fetchColumn() > 0;

        $payload = array(
            'id' => $accountId,
            'username' => (string)($accountRow['username'] ?? ''),
            'email' => (string)($accountRow['email'] ?? ''),
        );
        if ($plainPassword !== null) {
            $payload['password'] = $plainPassword;
        }

        if ($exists) {
            $setClauses = array();
            $values = array();
            foreach ($payload as $field => $value) {
                if ($field === 'id' || !spp_db_column_exists($pdo, 'account_pass', $field)) {
                    continue;
                }
                $setClauses[] = "`{$field}` = ?";
                $values[] = $value;
            }
            if (!empty($setClauses)) {
                $values[] = $accountId;
                $stmt = $pdo->prepare("UPDATE `account_pass` SET " . implode(', ', $setClauses) . " WHERE `id` = ? LIMIT 1");
                $stmt->execute($values);
            }
            return;
        }

        $insertFields = array();
        $insertValues = array();
        $placeholders = array();
        foreach ($payload as $field => $value) {
            if (!spp_db_column_exists($pdo, 'account_pass', $field)) {
                continue;
            }
            $insertFields[] = "`{$field}`";
            $insertValues[] = $value;
            $placeholders[] = '?';
        }
        if (!empty($insertFields)) {
            $stmt = $pdo->prepare("INSERT INTO `account_pass` (" . implode(',', $insertFields) . ") VALUES (" . implode(',', $placeholders) . ")");
            $stmt->execute($insertValues);
        }
    }
}

if (!function_exists('spp_auth_sync_account_to_realm')) {
    function spp_auth_sync_account_to_realm(int $realmId, array $canonicalAccountRow, ?string $plainPassword = null): array
    {
        $realmId = max(1, (int)$realmId);
        $capabilities = spp_auth_realm_capabilities($realmId);
        $result = array(
            'realm_id' => $realmId,
            'status' => 'skipped',
            'account_id' => 0,
            'message' => '',
        );

        if (empty($capabilities['supports_account_provisioning'])) {
            $result['message'] = 'Realm does not support account provisioning.';
            return $result;
        }

        $canonicalAccountId = (int)($canonicalAccountRow['id'] ?? 0);
        $username = strtoupper(trim((string)($canonicalAccountRow['username'] ?? '')));
        if ($canonicalAccountId <= 0 || $username === '') {
            $result['message'] = 'Missing canonical account id or username.';
            return $result;
        }

        try {
            $pdo = spp_get_pdo('realmd', $realmId);
            $existingRow = spp_auth_realm_account_row($realmId, $canonicalAccountId, $username);
            $payload = array(
                'username' => $username,
                'email' => (string)($canonicalAccountRow['email'] ?? ''),
                's' => (string)($canonicalAccountRow['s'] ?? ''),
                'v' => (string)($canonicalAccountRow['v'] ?? ''),
                'locked' => isset($canonicalAccountRow['locked']) ? (int)$canonicalAccountRow['locked'] : 0,
                'last_ip' => (string)($canonicalAccountRow['last_ip'] ?? ''),
                'failed_logins' => isset($canonicalAccountRow['failed_logins']) ? (int)$canonicalAccountRow['failed_logins'] : 0,
                'expansion' => isset($canonicalAccountRow['expansion']) ? (int)$canonicalAccountRow['expansion'] : 0,
            );
            if (!empty($capabilities['account_columns']['sessionkey'])) {
                $payload['sessionkey'] = null;
            }
            if (!empty($capabilities['account_columns']['current_realm'])) {
                $payload['current_realm'] = isset($canonicalAccountRow['current_realm']) ? (int)$canonicalAccountRow['current_realm'] : 0;
            }

            $payload = spp_auth_account_field_payload($pdo, $payload);

            if ($existingRow) {
                $setClauses = array();
                $values = array();
                foreach ($payload as $field => $value) {
                    $setClauses[] = "`{$field}` = ?";
                    $values[] = $value;
                }
                if (!empty($setClauses)) {
                    $values[] = (int)$existingRow['id'];
                    $stmt = $pdo->prepare("UPDATE `account` SET " . implode(', ', $setClauses) . " WHERE `id` = ? LIMIT 1");
                    $stmt->execute($values);
                }
                $result['status'] = ((int)$existingRow['id'] === $canonicalAccountId) ? 'updated' : 'linked';
                $result['account_id'] = (int)$existingRow['id'];
                $result['message'] = $result['status'] === 'linked'
                    ? 'Updated existing realm account matched by username.'
                    : 'Updated existing realm account.';
            } else {
                $insertPayload = $payload;
                if (!empty($capabilities['account_columns']['id'])) {
                    $insertPayload = array('id' => $canonicalAccountId) + $insertPayload;
                }
                $fields = array();
                $placeholders = array();
                $values = array();
                foreach ($insertPayload as $field => $value) {
                    $fields[] = "`{$field}`";
                    $placeholders[] = '?';
                    $values[] = $value;
                }
                $stmt = $pdo->prepare("INSERT INTO `account` (" . implode(',', $fields) . ") VALUES (" . implode(',', $placeholders) . ")");
                $stmt->execute($values);
                $result['status'] = 'created';
                $result['account_id'] = (int)($pdo->lastInsertId() ?: $canonicalAccountId);
                $result['message'] = 'Created realm account.';
            }

            $resolvedAccountRow = spp_auth_realm_account_row($realmId, (int)$result['account_id'], $username) ?: array(
                'id' => (int)$result['account_id'],
                'username' => $username,
                'email' => (string)($canonicalAccountRow['email'] ?? ''),
            );
            spp_auth_sync_account_pass_table($pdo, $resolvedAccountRow, $plainPassword);
            if (function_exists('spp_ensure_account_identity')) {
                spp_ensure_account_identity($realmId, (int)($resolvedAccountRow['id'] ?? 0), $username);
            }
        } catch (Throwable $e) {
            $result['status'] = 'error';
            $result['message'] = $e->getMessage();
            error_log('[auth] Failed syncing account to realm ' . $realmId . ': ' . $e->getMessage());
        }

        return $result;
    }
}

if (!function_exists('spp_auth_sync_canonical_account')) {
    function spp_auth_sync_canonical_account($canonicalAccountId, ?string $plainPassword = null): array
    {
        $canonicalAccountId = (int)$canonicalAccountId;
        if ($canonicalAccountId <= 0) {
            return array();
        }

        $pdo = spp_canonical_auth_pdo();
        $stmt = $pdo->prepare("SELECT * FROM `account` WHERE `id` = ? LIMIT 1");
        $stmt->execute(array($canonicalAccountId));
        $canonicalAccountRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$canonicalAccountRow) {
            return array();
        }

        $results = array();
        foreach (spp_auth_managed_realm_map() as $realmId => $realmInfo) {
            $realmId = (int)$realmId;
            if ($realmId <= 0) {
                continue;
            }
            if ($realmId === (int)spp_canonical_auth_realm_id()) {
                if (function_exists('spp_ensure_account_identity')) {
                    spp_ensure_account_identity($realmId, $canonicalAccountId, (string)$canonicalAccountRow['username']);
                }
                $results[$realmId] = array(
                    'realm_id' => $realmId,
                    'status' => 'canonical',
                    'account_id' => $canonicalAccountId,
                    'message' => 'Canonical auth realm.',
                );
                continue;
            }
            $results[$realmId] = spp_auth_sync_account_to_realm($realmId, $canonicalAccountRow, $plainPassword);
        }

        return $results;
    }
}

if (!function_exists('spp_ensure_website_settings_table')) {
    function spp_ensure_website_settings_table(?PDO $pdo = null): bool
    {
        static $ensured = false;

        if ($ensured) {
            return true;
        }

        try {
            $pdo = $pdo ?: spp_website_settings_pdo();
            $tableName = spp_website_settings_table_name();
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `{$tableName}` (
                  `setting_key` VARCHAR(191) NOT NULL,
                  `setting_value` LONGTEXT DEFAULT NULL,
                  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  `updated_by` VARCHAR(64) DEFAULT NULL,
                  PRIMARY KEY (`setting_key`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            $ensured = true;
            return true;
        } catch (Throwable $e) {
            error_log('[config] Failed ensuring website settings table: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('spp_website_settings_rows')) {
    function spp_website_settings_rows(bool $refresh = false): array
    {
        static $cache = null;

        if ($cache !== null && !$refresh) {
            return $cache;
        }

        $cache = array();

        try {
            $pdo = spp_website_settings_pdo();
            if (!spp_ensure_website_settings_table($pdo)) {
                return $cache;
            }

            $tableName = spp_website_settings_table_name();
            $stmt = $pdo->query("SELECT `setting_key`, `setting_value`, `updated_at`, `updated_by` FROM `{$tableName}`");
            foreach (($stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array()) as $row) {
                $key = trim((string)($row['setting_key'] ?? ''));
                if ($key === '') {
                    continue;
                }
                $cache[$key] = array(
                    'setting_key' => $key,
                    'setting_value' => (string)($row['setting_value'] ?? ''),
                    'updated_at' => (string)($row['updated_at'] ?? ''),
                    'updated_by' => (string)($row['updated_by'] ?? ''),
                );
            }
        } catch (Throwable $e) {
            error_log('[config] Failed loading website settings: ' . $e->getMessage());
        }

        return $cache;
    }
}

if (!function_exists('spp_website_setting')) {
    function spp_website_setting(string $settingKey, $default = null)
    {
        $settingKey = trim($settingKey);
        if ($settingKey === '') {
            return $default;
        }

        $rows = spp_website_settings_rows();
        if (!isset($rows[$settingKey])) {
            return $default;
        }

        return $rows[$settingKey]['setting_value'];
    }
}

if (!function_exists('spp_set_website_setting')) {
    function spp_set_website_setting(string $settingKey, $settingValue, ?string $updatedBy = null): bool
    {
        $settingKey = trim($settingKey);
        if ($settingKey === '') {
            return false;
        }

        try {
            $pdo = spp_website_settings_pdo();
            if (!spp_ensure_website_settings_table($pdo)) {
                return false;
            }

            $tableName = spp_website_settings_table_name();
            $stmt = $pdo->prepare("
                INSERT INTO `{$tableName}` (`setting_key`, `setting_value`, `updated_by`)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    `setting_value` = VALUES(`setting_value`),
                    `updated_by` = VALUES(`updated_by`),
                    `updated_at` = CURRENT_TIMESTAMP
            ");
            $stmt->execute(array(
                $settingKey,
                is_scalar($settingValue) || $settingValue === null ? (string)$settingValue : json_encode($settingValue),
                $updatedBy !== null ? trim((string)$updatedBy) : null,
            ));

            spp_website_settings_rows(true);
            return true;
        } catch (Throwable $e) {
            error_log('[config] Failed saving website setting "' . $settingKey . '": ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('spp_realm_runtime_selection_modes')) {
    function spp_realm_runtime_selection_modes(): array
    {
        return array(
            'manual' => 'Manual',
        );
    }
}

if (!function_exists('spp_realm_runtime_definition_fields')) {
    function spp_realm_runtime_definition_fields(): array
    {
        return array(
            'id',
            'name',
            'address',
            'port',
            'realmd',
            'world',
            'chars',
            'armory',
            'bots',
            'icon',
            'realmflags',
            'timezone',
            'allowedSecurityLevel',
            'population',
            'realmbuilds',
        );
    }
}

if (!function_exists('spp_realm_runtime_definition_from_realm_map')) {
    function spp_realm_runtime_definition_from_realm_map(int $realmId, array $realmInfo): array
    {
        return array(
            'id' => $realmId,
            'name' => trim((string)($realmInfo['name'] ?? '')),
            'address' => trim((string)($realmInfo['address'] ?? '')),
            'port' => (int)($realmInfo['port'] ?? 0),
            'realmd' => trim((string)($realmInfo['realmd'] ?? '')),
            'world' => trim((string)($realmInfo['world'] ?? '')),
            'chars' => trim((string)($realmInfo['chars'] ?? '')),
            'armory' => trim((string)($realmInfo['armory'] ?? '')),
            'bots' => trim((string)($realmInfo['bots'] ?? '')),
            'icon' => (int)($realmInfo['icon'] ?? 0),
            'realmflags' => (int)($realmInfo['realmflags'] ?? 0),
            'timezone' => (int)($realmInfo['timezone'] ?? 0),
            'allowedSecurityLevel' => (int)($realmInfo['allowedSecurityLevel'] ?? 0),
            'population' => trim((string)($realmInfo['population'] ?? '0')),
            'realmbuilds' => trim((string)($realmInfo['realmbuilds'] ?? '')),
        );
    }
}

if (!function_exists('spp_realm_runtime_fallback_definitions')) {
    function spp_realm_runtime_fallback_definitions(array $realmDbMap): array
    {
        $definitions = array();

        foreach ($realmDbMap as $realmId => $realmInfo) {
            $realmId = (int)$realmId;
            if ($realmId <= 0 || !is_array($realmInfo)) {
                continue;
            }

            $definitions[$realmId] = spp_realm_runtime_definition_from_realm_map($realmId, $realmInfo);
        }

        ksort($definitions, SORT_NUMERIC);

        return $definitions;
    }
}

if (!function_exists('spp_realm_runtime_normalize_definitions')) {
    function spp_realm_runtime_normalize_definitions($definitions, array $fallbackDefinitions = array()): array
    {
        if (!is_array($definitions)) {
            return array();
        }

        $normalized = array();
        foreach ($definitions as $definitionKey => $definitionValue) {
            if (!is_array($definitionValue)) {
                continue;
            }

            $realmId = (int)($definitionValue['id'] ?? $definitionKey);
            if ($realmId <= 0) {
                continue;
            }

            $fallback = (array)($fallbackDefinitions[$realmId] ?? array());
            $definition = spp_realm_runtime_definition_from_realm_map($realmId, array_merge($fallback, $definitionValue));
            if ($definition['realmd'] === '' || $definition['world'] === '' || $definition['chars'] === '') {
                continue;
            }

            $normalized[$realmId] = $definition;
        }

        ksort($normalized, SORT_NUMERIC);

        return $normalized;
    }
}

if (!function_exists('spp_realm_runtime_realm_map_from_definitions')) {
    function spp_realm_runtime_realm_map_from_definitions(array $definitions): array
    {
        $realmDbMap = array();

        foreach ($definitions as $realmId => $definition) {
            $realmId = (int)$realmId;
            if ($realmId <= 0 || !is_array($definition)) {
                continue;
            }

            $realmDbMap[$realmId] = spp_realm_runtime_definition_from_realm_map($realmId, $definition);
        }

        ksort($realmDbMap, SORT_NUMERIC);

        return $realmDbMap;
    }
}

if (!function_exists('spp_realm_runtime_catalog')) {
    function spp_realm_runtime_catalog(array $fallbackRealmDbMap, bool $refresh = false): array
    {
        static $cache = null;

        if ($cache !== null && !$refresh) {
            return $cache;
        }

        $fallbackDefinitions = spp_realm_runtime_fallback_definitions($fallbackRealmDbMap);
        $configOnlyDefinitions = array();
        $diagnostics = array();
        $source = 'config';
        $dbDefinitions = array();

        $rawDefinitions = spp_website_setting('realm_runtime.realm_definitions', null);
        if ($rawDefinitions !== null) {
            $rawDefinitions = trim((string)$rawDefinitions);
            if ($rawDefinitions !== '') {
                $decodedDefinitions = json_decode($rawDefinitions, true);
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($decodedDefinitions)) {
                    $diagnostics[] = 'Stored runtime realm definitions are invalid JSON; using config fallback slots.';
                    error_log('[realm.runtime] Invalid JSON in realm_runtime.realm_definitions: ' . json_last_error_msg());
                } else {
                    $dbDefinitions = spp_realm_runtime_normalize_definitions($decodedDefinitions, $fallbackDefinitions);
                    if (!empty($dbDefinitions)) {
                        $source = 'db';
                    } else {
                        $diagnostics[] = 'Stored runtime realm definitions did not contain any usable realm slots; using config fallback slots.';
                        error_log('[realm.runtime] No usable runtime realm definitions found in realm_runtime.realm_definitions.');
                    }
                }
            }
        }

        $activeDefinitions = !empty($dbDefinitions) ? $dbDefinitions : $fallbackDefinitions;
        foreach ($fallbackDefinitions as $realmId => $definition) {
            if (!isset($activeDefinitions[$realmId])) {
                $configOnlyDefinitions[$realmId] = $definition;
            }
        }
        $runtimeDefinitions = $activeDefinitions;
        foreach ($configOnlyDefinitions as $realmId => $definition) {
            $runtimeDefinitions[$realmId] = $definition;
        }
        ksort($runtimeDefinitions, SORT_NUMERIC);

        $cache = array(
            'source' => $source,
            'realm_definitions' => $activeDefinitions,
            'realm_db_map' => spp_realm_runtime_realm_map_from_definitions($activeDefinitions),
            'runtime_realm_definitions' => $runtimeDefinitions,
            'runtime_realm_db_map' => spp_realm_runtime_realm_map_from_definitions($runtimeDefinitions),
            'fallback_definitions' => $fallbackDefinitions,
            'fallback_realm_db_map' => spp_realm_runtime_realm_map_from_definitions($fallbackDefinitions),
            'config_only_definitions' => $configOnlyDefinitions,
            'config_only_realm_db_map' => spp_realm_runtime_realm_map_from_definitions($configOnlyDefinitions),
            'has_db_definitions' => !empty($dbDefinitions),
            'diagnostics' => $diagnostics,
        );

        return $cache;
    }
}

if (!function_exists('spp_realm_runtime_normalize_enabled_ids')) {
    function spp_realm_runtime_normalize_enabled_ids($value, array $realmDbMap): array
    {
        $allowedIds = array_map('intval', array_keys($realmDbMap));
        $allowedLookup = array_fill_keys($allowedIds, true);

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '') {
                $candidateIds = array();
            } elseif ($trimmed !== '' && ($trimmed[0] === '[' || $trimmed[0] === '{')) {
                $decoded = json_decode($trimmed, true);
                $candidateIds = is_array($decoded) ? $decoded : array();
            } else {
                $candidateIds = preg_split('/\s*,\s*/', $trimmed, -1, PREG_SPLIT_NO_EMPTY);
            }
        } elseif (is_array($value)) {
            $candidateIds = $value;
        } else {
            $candidateIds = array();
        }

        $normalized = array();
        foreach ($candidateIds as $candidateId) {
            $realmId = (int)$candidateId;
            if ($realmId > 0 && isset($allowedLookup[$realmId]) && !isset($normalized[$realmId])) {
                $normalized[$realmId] = $realmId;
            }
        }

        return array_values($normalized);
    }
}

if (!function_exists('spp_realm_runtime_state')) {
    function spp_realm_runtime_state(array $realmDbMap, $config = null, bool $refresh = false): array
    {
        $realmIds = array_values(array_filter(array_map('intval', array_keys($realmDbMap))));
        sort($realmIds, SORT_NUMERIC);

        $configDefaultRealmId = (int)spp_config_realm_info('default_realm_id', 0, $config);
        if ($configDefaultRealmId <= 0 || !isset($realmDbMap[$configDefaultRealmId])) {
            $configDefaultRealmId = !empty($realmIds) ? (int)$realmIds[0] : 0;
        }

        $state = array(
            'multirealm' => (int)spp_config_realm_info('multirealm', 0, $config),
            'default_realm_id' => $configDefaultRealmId,
            'enabled_realm_ids' => $realmIds,
            'selection_mode' => 'manual',
            'source' => 'config',
        );

        $catalog = spp_realm_runtime_catalog($GLOBALS['fallbackConfiguredRealmDbMap'] ?? $realmDbMap, $refresh);
        $state['realm_definitions_source'] = (string)($catalog['source'] ?? 'config');
        $state['runtime_realm_ids'] = $realmIds;
        $state['config_only_realm_ids'] = array_values(array_map('intval', array_keys((array)($catalog['config_only_realm_db_map'] ?? array()))));
        $state['definition_diagnostics'] = array_values((array)($catalog['diagnostics'] ?? array()));

        $rows = spp_website_settings_rows($refresh);
        $prefix = 'realm_runtime.';
        $overrides = array();
        foreach ($rows as $key => $row) {
            if (strpos($key, $prefix) !== 0) {
                continue;
            }
            $overrides[substr($key, strlen($prefix))] = (string)($row['setting_value'] ?? '');
        }

        if (array_key_exists('multirealm', $overrides)) {
            $state['multirealm'] = ((int)$overrides['multirealm'] === 1) ? 1 : 0;
            $state['source'] = 'db';
        }

        if (array_key_exists('default_realm_id', $overrides)) {
            $candidate = (int)$overrides['default_realm_id'];
            if ($candidate > 0 && isset($realmDbMap[$candidate])) {
                $state['default_realm_id'] = $candidate;
                $state['source'] = 'db';
            }
        }

        if (array_key_exists('enabled_realm_ids', $overrides)) {
            $candidateIds = spp_realm_runtime_normalize_enabled_ids($overrides['enabled_realm_ids'], $realmDbMap);
            if (!empty($candidateIds)) {
                $state['enabled_realm_ids'] = $candidateIds;
                $state['source'] = 'db';
            }
        }

        if (array_key_exists('selection_mode', $overrides)) {
            $candidateMode = trim((string)$overrides['selection_mode']);
            if (array_key_exists($candidateMode, spp_realm_runtime_selection_modes())) {
                $state['selection_mode'] = $candidateMode;
                $state['source'] = 'db';
            }
        }

        $state['enabled_realm_ids'] = spp_realm_runtime_normalize_enabled_ids($state['enabled_realm_ids'], $realmDbMap);
        if (empty($state['enabled_realm_ids'])) {
            $state['enabled_realm_ids'] = $realmIds;
        }
        if (!in_array($state['default_realm_id'], $state['enabled_realm_ids'], true)) {
            $state['default_realm_id'] = !empty($state['enabled_realm_ids'])
                ? (int)$state['enabled_realm_ids'][0]
                : $configDefaultRealmId;
        }

        if (!array_key_exists($state['selection_mode'], spp_realm_runtime_selection_modes())) {
            $state['selection_mode'] = 'manual';
        }

        return $state;
    }
}

if (!function_exists('spp_bootstrap_default_component')) {
    function spp_bootstrap_default_component($config = null): string
    {
        return (string)spp_config_generic('default_component', 'frontpage', $config);
    }
}

if (!function_exists('spp_bootstrap_site_cookie_name')) {
    function spp_bootstrap_site_cookie_name($config = null): string
    {
        return (string)spp_config_generic('site_cookie', 'sppArmory', $config);
    }
}

if (!function_exists('spp_bootstrap_multirealm_enabled')) {
    function spp_bootstrap_multirealm_enabled($config = null): bool
    {
        $realmDbMap = $GLOBALS['realmDbMap'] ?? array();
        if (!is_array($realmDbMap) || empty($realmDbMap)) {
            return (int)spp_config_realm_info('multirealm', 0, $config) === 1;
        }

        return (int)spp_realm_runtime_state($realmDbMap, $config)['multirealm'] === 1;
    }
}

if (!function_exists('spp_bootstrap_default_realm_id')) {
    function spp_bootstrap_default_realm_id(array $realmDbMap, $config = null): int
    {
        return (int)(spp_realm_runtime_state($realmDbMap, $config)['default_realm_id'] ?? spp_default_realm_id($realmDbMap));
    }
}

if (!function_exists('spp_bootstrap_enabled_realm_ids')) {
    function spp_bootstrap_enabled_realm_ids(array $realmDbMap, $config = null): array
    {
        return (array)(spp_realm_runtime_state($realmDbMap, $config)['enabled_realm_ids'] ?? array());
    }
}

if (!function_exists('spp_bootstrap_realm_selection_mode')) {
    function spp_bootstrap_realm_selection_mode(array $realmDbMap, $config = null): string
    {
        return (string)(spp_realm_runtime_state($realmDbMap, $config)['selection_mode'] ?? 'manual');
    }
}

if (!function_exists('spp_bootstrap_enabled_realm_map')) {
    function spp_bootstrap_enabled_realm_map(array $realmDbMap, $config = null): array
    {
        $enabledRealmIds = spp_bootstrap_enabled_realm_ids($realmDbMap, $config);
        if (empty($enabledRealmIds)) {
            return $realmDbMap;
        }

        $enabledLookup = array_fill_keys(array_map('intval', $enabledRealmIds), true);
        return array_intersect_key($realmDbMap, $enabledLookup);
    }
}

if (!function_exists('spp_request_scoped_realm_map')) {
    function spp_request_scoped_realm_map(array $realmDbMap, ?string $component = null, ?string $subpage = null): array
    {
        if (empty($realmDbMap)) {
            return $realmDbMap;
        }

        $component = strtolower(trim((string)$component));
        $subpage = strtolower(trim((string)$subpage));
        $enabledRealmMap = spp_bootstrap_enabled_realm_map($realmDbMap);

        if (empty($enabledRealmMap)) {
            $enabledRealmMap = $realmDbMap;
        }

        $allowAllRealms = (
            $component === 'admin'
            || ($component === 'server' && in_array($subpage, array('realmstatus', 'realmlist'), true))
        );

        if ($allowAllRealms) {
            return $enabledRealmMap;
        }

        if (spp_bootstrap_multirealm_enabled()) {
            return $enabledRealmMap;
        }

        $activeRealmId = 0;
        if (isset($_GET['realm']) && ctype_digit((string)$_GET['realm'])) {
            $activeRealmId = (int)$_GET['realm'];
        }
        if ($activeRealmId <= 0 && isset($_COOKIE['cur_selected_realmd']) && ctype_digit((string)$_COOKIE['cur_selected_realmd'])) {
            $activeRealmId = (int)$_COOKIE['cur_selected_realmd'];
        }
        if ($activeRealmId <= 0 && isset($_COOKIE['cur_selected_realm']) && ctype_digit((string)$_COOKIE['cur_selected_realm'])) {
            $activeRealmId = (int)$_COOKIE['cur_selected_realm'];
        }
        if ($activeRealmId <= 0 && isset($GLOBALS['user']['cur_selected_realmd'])) {
            $activeRealmId = (int)$GLOBALS['user']['cur_selected_realmd'];
        }
        if ($activeRealmId <= 0 || !isset($enabledRealmMap[$activeRealmId])) {
            $activeRealmId = (int)spp_bootstrap_default_realm_id($enabledRealmMap);
        }

        if ($activeRealmId > 0 && isset($enabledRealmMap[$activeRealmId])) {
            return array($activeRealmId => $enabledRealmMap[$activeRealmId]);
        }

        return $enabledRealmMap;
    }
}

if (!function_exists('spp_query_url')) {
    function spp_query_url(array $params, bool $encodeEntities = true, string $path = 'index.php'): string
    {
        $path = trim($path) !== '' ? trim($path) : 'index.php';
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $url = $query !== '' ? ($path . '?' . $query) : $path;

        return $encodeEntities ? htmlentities($url) : $url;
    }
}

if (!function_exists('spp_route_url')) {
    function spp_route_url(string $component, string $subpage = 'index', array $params = array(), bool $encodeEntities = true): string
    {
        $component = trim($component);
        $subpage = trim($subpage);
        $query = array('n' => $component);

        if ($component === '' || $component === 'frontpage') {
            $query = array();
        }

        if ($subpage !== '' && $subpage !== 'index') {
            $query['sub'] = $subpage;
        }

        foreach ($params as $key => $value) {
            $query[$key] = $value;
        }

        return spp_query_url($query, $encodeEntities);
    }
}

if (!function_exists('spp_account_url')) {
    function spp_account_url(string $subpage = 'index', array $params = array(), bool $encodeEntities = true): string
    {
        return spp_route_url('account', $subpage, $params, $encodeEntities);
    }
}

if (!function_exists('spp_admin_url')) {
    function spp_admin_url(string $subpage = 'index', array $params = array(), bool $encodeEntities = true): string
    {
        return spp_route_url('admin', $subpage, $params, $encodeEntities);
    }
}

if (!function_exists('spp_news_url')) {
    function spp_news_url(string $subpage = 'index', array $params = array(), bool $encodeEntities = true): string
    {
        return spp_route_url('news', $subpage, $params, $encodeEntities);
    }
}

if (!function_exists('spp_server_url')) {
    function spp_server_url(string $subpage = 'index', array $params = array(), bool $encodeEntities = true): string
    {
        return spp_route_url('server', $subpage, $params, $encodeEntities);
    }
}

if (!function_exists('spp_html_url')) {
    function spp_html_url(string $subpage = 'index', array $params = array(), bool $encodeEntities = true): string
    {
        return spp_route_url('html', $subpage, $params, $encodeEntities);
    }
}

if (!function_exists('spp_config_value')) {
    function spp_config_value(string $section, string $key, $default = null, $config = null) {
        $config = $config ?: spp_runtime_config();
        if (spp_config_segment_exists($config, $section)) {
            $sectionValue = spp_config_segment_value($config, $section);
            if (spp_config_segment_exists($sectionValue, $key)) {
                return (string)spp_config_segment_value($sectionValue, $key);
            }
        }

        return $default;
    }
}

if (!function_exists('spp_config_generic')) {
    function spp_config_generic(string $key, $default = null, $config = null) {
        return spp_config_value('generic', $key, $default, $config);
    }
}

if (!function_exists('spp_config_generic_int')) {
    function spp_config_generic_int(string $key, int $default = 0, $config = null): int
    {
        return (int)spp_config_generic($key, $default, $config);
    }
}

if (!function_exists('spp_config_generic_bool')) {
    function spp_config_generic_bool(string $key, bool $default = false, $config = null): bool
    {
        return (int)spp_config_generic($key, $default ? 1 : 0, $config) === 1;
    }
}

if (!function_exists('spp_config_temp')) {
    function spp_config_temp(string $key, $default = null, $config = null) {
        return spp_config_value('temp', $key, $default, $config);
    }
}

if (!function_exists('spp_config_temp_string')) {
    function spp_config_temp_string(string $key, string $default = '', $config = null): string
    {
        return (string)spp_config_temp($key, $default, $config);
    }
}

if (!function_exists('spp_config_forum')) {
    function spp_config_forum(string $key, $default = null, $config = null) {
        return (string)spp_config_path(array('generic_values', 'forum', $key), $default, $config);
    }
}

if (!function_exists('spp_config_template_names')) {
    function spp_config_template_names($config = null): array
    {
        $templates = array();

        $templateNode = spp_config_path(array('templates', 'template'), null, $config);
        if (is_array($templateNode)) {
            foreach ($templateNode as $templateName) {
                $templates[] = (string)$templateName;
            }
        } elseif (is_object($templateNode)) {
            foreach (get_object_vars($templateNode) as $templateName) {
                $templates[] = (string)$templateName;
            }
        } elseif ((string)$templateNode !== '') {
            $templates[] = (string)$templateNode;
        }

        if (empty($templates)) {
            $templates[] = spp_template_name($config);
        }

        return array_values(array_unique(array_filter($templates, static function ($templateName) {
            return trim((string)$templateName) !== '';
        })));
    }
}

if (!function_exists('spp_config_path')) {
    function spp_config_path(array $segments, $default = null, $config = null)
    {
        $config = $config ?: spp_runtime_config();
        if (!$config || empty($segments)) {
            return $default;
        }

        $cursor = $config;
        foreach ($segments as $segment) {
            $segment = (string)$segment;
            if ($segment === '' || !spp_config_segment_exists($cursor, $segment)) {
                return $default;
            }
            $cursor = spp_config_segment_value($cursor, $segment);
        }

        return $cursor;
    }
}

if (!function_exists('spp_config_realm_info')) {
    function spp_config_realm_info(string $key, $default = null, $config = null) {
        return (string)spp_config_path(array('generic_values', 'realm_info', $key), $default, $config);
    }
}

if (!function_exists('spp_forum_general_forum_id')) {
    function spp_forum_general_forum_id(?int $realmId = null): int
    {
        $realmId = (int)($realmId ?: spp_selected_realm_id());
        if ($realmId <= 0) {
            return 0;
        }

        try {
            $pdo = spp_get_pdo('realmd', $realmId);
            $stmt = $pdo->query("SELECT f.`forum_id`, f.`forum_name`, f.`forum_desc`, f.`scope_type`, f.`scope_value`, c.`cat_name`
                                 FROM `f_forums` f
                                 LEFT JOIN `f_categories` c ON c.`cat_id` = f.`cat_id`
                                 ORDER BY f.`forum_id` ASC");
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array();
            $expansion = spp_realm_to_expansion_key($realmId);

            foreach ($rows as $forum) {
                $forumId = (int)($forum['forum_id'] ?? 0);
                $scopeType = strtolower(trim((string)($forum['scope_type'] ?? '')));
                $scopeValue = strtolower(trim((string)($forum['scope_value'] ?? '')));
                $categoryName = strtolower(trim((string)($forum['cat_name'] ?? '')));

                if ($forumId <= 0 || strpos($categoryName, 'general') === false) {
                    continue;
                }

                if ($scopeType === 'realm' && $scopeValue === (string)$realmId) {
                    return $forumId;
                }

                if ($scopeType === 'expansion' && $expansion !== '' && $scopeValue === $expansion) {
                    return $forumId;
                }

                if (($scopeType === '' || $scopeType === 'all') && spp_forum_detect_realm_hint($forum, 0) === $realmId) {
                    return $forumId;
                }
            }
        } catch (Throwable $e) {
            error_log('[config] Failed resolving general forum id: ' . $e->getMessage());
        }

        return 0;
    }
}

if (!function_exists('spp_forum_category_id_by_name')) {
    function spp_forum_category_id_by_name($needles, ?int $realmId = null): int
    {
        $realmId = (int)($realmId ?: spp_selected_realm_id());
        $needles = is_array($needles) ? $needles : array($needles);
        $needles = array_values(array_filter(array_map(static function ($value) {
            return strtolower(trim((string)$value));
        }, $needles), static function ($value) {
            return $value !== '';
        }));

        if (empty($needles) || $realmId <= 0) {
            return 0;
        }

        try {
            $pdo = spp_get_pdo('realmd', $realmId);
            $stmt = $pdo->query("SELECT `cat_id`, `cat_name` FROM `f_categories` ORDER BY `cat_disp_position` ASC, `cat_name` ASC");
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array();

            foreach ($rows as $row) {
                $catId = (int)($row['cat_id'] ?? 0);
                $catName = strtolower(trim((string)($row['cat_name'] ?? '')));
                if ($catId <= 0 || $catName === '') {
                    continue;
                }

                foreach ($needles as $needle) {
                    if ($catName === $needle || strpos($catName, $needle) !== false) {
                        return $catId;
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('[config] Failed resolving forum category id: ' . $e->getMessage());
        }

        return 0;
    }
}

if (!function_exists('spp_forum_general_category_id')) {
    function spp_forum_general_category_id(?int $realmId = null): int
    {
        return spp_forum_category_id_by_name(array('general'), $realmId);
    }
}

if (!function_exists('spp_forum_guild_category_id')) {
    function spp_forum_guild_category_id(?int $realmId = null): int
    {
        return spp_forum_category_id_by_name(array('guild', 'guild recruitment'), $realmId);
    }
}

if (!function_exists('spp_forum_comments_category_id')) {
    function spp_forum_comments_category_id(?int $realmId = null): int
    {
        return spp_forum_category_id_by_name(array('comments', 'comment'), $realmId);
    }
}

if (!function_exists('spp_forum_is_comments_context')) {
    function spp_forum_is_comments_context(array $forum): bool
    {
        $categoryName = strtolower(trim((string)($forum['cat_name'] ?? '')));
        return $categoryName !== '' && strpos($categoryName, 'comment') !== false;
    }
}

if (!function_exists('spp_forum_help_category_id')) {
    function spp_forum_help_category_id(?int $realmId = null): int
    {
        return spp_forum_category_id_by_name(array('help', 'faq'), $realmId);
    }
}

if (!function_exists('spp_forum_guild_forum_id')) {
    function spp_forum_guild_forum_id(?int $realmId = null): int
    {
        $realmId = (int)($realmId ?: spp_selected_realm_id());
        if ($realmId <= 0) {
            return 0;
        }

        try {
            $pdo = spp_get_pdo('realmd', $realmId);
            $stmt = $pdo->query("SELECT `forum_id`, `forum_name`, `forum_desc`, `scope_type`, `scope_value` FROM `f_forums` ORDER BY `forum_id` ASC");
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array();
            $expansion = spp_realm_to_expansion_key($realmId);

            foreach ($rows as $forum) {
                $forumId = (int)($forum['forum_id'] ?? 0);
                $scopeType = strtolower(trim((string)($forum['scope_type'] ?? '')));
                $scopeValue = strtolower(trim((string)($forum['scope_value'] ?? '')));

                if ($forumId <= 0) {
                    continue;
                }

                if ($scopeType === 'guild_recruitment') {
                    if ($scopeValue === (string)$realmId || ($expansion !== '' && $scopeValue === $expansion)) {
                        return $forumId;
                    }

                    $hintRealmId = spp_forum_detect_realm_hint($forum, 0);
                    if ($hintRealmId === $realmId) {
                        return $forumId;
                    }

                    if ($scopeValue === '') {
                        return $forumId;
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('[config] Failed resolving guild forum id: ' . $e->getMessage());
        }

        return 0;
    }
}

if (!function_exists('spp_forum_help_forum_id')) {
    function spp_forum_help_forum_id(?int $realmId = null): int
    {
        $realmId = (int)($realmId ?: spp_selected_realm_id());
        if ($realmId <= 0) {
            return 0;
        }

        try {
            $pdo = spp_get_pdo('realmd', $realmId);
            $stmt = $pdo->query("SELECT f.`forum_id`, f.`forum_name`, f.`hidden`, c.`cat_name`
                                 FROM `f_forums` f
                                 LEFT JOIN `f_categories` c ON c.`cat_id` = f.`cat_id`
                                 ORDER BY f.`disp_position` ASC, f.`forum_id` ASC");
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array();

            foreach ($rows as $forum) {
                $forumId = (int)($forum['forum_id'] ?? 0);
                $forumName = strtolower(trim((string)($forum['forum_name'] ?? '')));
                $categoryName = strtolower(trim((string)($forum['cat_name'] ?? '')));
                $hidden = (int)($forum['hidden'] ?? 0);

                if ($forumId <= 0 || $hidden === 1) {
                    continue;
                }

                if (strpos($categoryName, 'help') !== false && (strpos($forumName, 'help') !== false || strpos($forumName, 'faq') !== false)) {
                    return $forumId;
                }
            }
        } catch (Throwable $e) {
            error_log('[config] Failed resolving help forum id: ' . $e->getMessage());
        }

        return 0;
    }
}

if (!function_exists('spp_forum_comments_discussion_context')) {
    function spp_forum_comments_discussion_context(?int $realmId = null): array
    {
        $realmId = (int)($realmId ?: spp_selected_realm_id());
        $fallback = array(
            'realm_id' => $realmId > 0 ? $realmId : 1,
            'forum_id' => 0,
            'forum_name' => '',
            'cat_id' => 0,
            'hidden' => 0,
        );

        if ($realmId <= 0) {
            return $fallback;
        }

        try {
            $pdo = spp_get_pdo('realmd', $realmId);
            $stmt = $pdo->query(
                "SELECT f.`forum_id`, f.`forum_name`, f.`forum_desc`, f.`scope_type`, f.`scope_value`, f.`hidden`, f.`cat_id`, c.`cat_name`
                 FROM `f_forums` f
                 LEFT JOIN `f_categories` c ON c.`cat_id` = f.`cat_id`
                 ORDER BY f.`forum_id` ASC"
            );
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array();
            $expansion = spp_realm_to_expansion_key($realmId);
            $bestMatch = null;

            foreach ($rows as $forum) {
                $forumId = (int)($forum['forum_id'] ?? 0);
                $scopeType = strtolower(trim((string)($forum['scope_type'] ?? '')));
                $scopeValue = strtolower(trim((string)($forum['scope_value'] ?? '')));
                $hidden = (int)($forum['hidden'] ?? 0);

                if ($forumId <= 0 || !spp_forum_is_comments_context($forum) || $hidden !== 1) {
                    continue;
                }

                $matchesRealm = false;
                if ($scopeType === 'realm' && $scopeValue === (string)$realmId) {
                    $matchesRealm = true;
                } elseif ($scopeType === 'expansion' && $expansion !== '' && $scopeValue === $expansion) {
                    $matchesRealm = true;
                } elseif (($scopeType === '' || $scopeType === 'all') && spp_forum_detect_realm_hint($forum, 0) === $realmId) {
                    $matchesRealm = true;
                } elseif ($scopeType === '' || $scopeType === 'all') {
                    $matchesRealm = true;
                }

                if (!$matchesRealm) {
                    continue;
                }

                $candidate = array(
                    'realm_id' => $realmId,
                    'forum_id' => $forumId,
                    'forum_name' => (string)($forum['forum_name'] ?? ''),
                    'cat_id' => (int)($forum['cat_id'] ?? 0),
                    'hidden' => (int)($forum['hidden'] ?? 0),
                );

                if ($scopeType === 'realm' && $scopeValue === (string)$realmId) {
                    return $candidate;
                }

                if ($bestMatch === null) {
                    $bestMatch = $candidate;
                }
            }

            if ($bestMatch !== null) {
                return $bestMatch;
            }
        } catch (Throwable $e) {
            error_log('[config] Failed resolving comments forum context: ' . $e->getMessage());
        }

        return $fallback;
    }
}

if (!function_exists('spp_forum_item_discussion_context')) {
    function spp_forum_item_discussion_context(): array
    {
        return spp_forum_comments_discussion_context();
    }
}

if (!function_exists('spp_forum_set_discussion_context')) {
    function spp_forum_set_discussion_context(): array
    {
        return spp_forum_comments_discussion_context();
    }
}

if (!function_exists('spp_sets_detail_url')) {
    function spp_sets_detail_url(int $realmId, string $setName, array $context = array()): string
    {
        $params = array(
            'n' => 'server',
            'sub' => 'item',
            'realm' => max(1, $realmId),
            'type' => 'sets',
            'set' => $setName,
        );

        $section = strtolower(trim((string)($context['section'] ?? '')));
        if (in_array($section, array('misc', 'world', 'pvp'), true)) {
            $params['set_section'] = $section;
        }

        $className = trim((string)($context['class'] ?? ''));
        if ($className !== '') {
            $params['set_class'] = $className;
        }

        foreach (array('search', 'quality', 'item_class', 'slot', 'min_level', 'max_level', 'p', 'per_page', 'sort', 'dir') as $passthroughKey) {
            if (isset($context[$passthroughKey]) && $context[$passthroughKey] !== '') {
                $params[$passthroughKey] = $context[$passthroughKey];
            }
        }

        return 'index.php?' . http_build_query($params);
    }
}

if (!function_exists('spp_item_sets_browse_url')) {
    function spp_item_sets_browse_url(int $realmId = 1, array $context = array()): string
    {
        $params = array(
            'n' => 'server',
            'sub' => 'items',
            'type' => 'sets',
            'realm' => max(1, $realmId),
        );

        $section = strtolower(trim((string)($context['section'] ?? $context['set_section'] ?? '')));
        if (in_array($section, array('misc', 'world', 'pvp', 'all'), true)) {
            $params['set_section'] = $section;
        }

        $className = trim((string)($context['class'] ?? $context['set_class'] ?? ''));
        if ($className !== '') {
            $params['set_class'] = $className;
        }

        foreach (array('p', 'per_page', 'sort', 'dir') as $passthroughKey) {
            if (isset($context[$passthroughKey]) && $context[$passthroughKey] !== '') {
                $params[$passthroughKey] = $context[$passthroughKey];
            }
        }

        return 'index.php?' . http_build_query($params);
    }
}

if (!function_exists('spp_forum_general_menu_url')) {
    function spp_forum_general_menu_url(?int $realmId = null): string
    {
        $realmId = (int)($realmId ?: spp_selected_realm_id());
        $categoryId = spp_forum_general_category_id($realmId);
        if ($categoryId > 0) {
            return 'index.php?n=forum&sub=viewcategory&catid=' . $categoryId;
        }

        $forumId = spp_forum_general_forum_id($realmId);
        if ($forumId > 0) {
            return 'index.php?n=forum&sub=viewforum&fid=' . $forumId;
        }

        return 'index.php?n=forum';
    }
}

if (!function_exists('spp_forum_guild_menu_url')) {
    function spp_forum_guild_menu_url(?int $realmId = null): string
    {
        $realmId = (int)($realmId ?: spp_selected_realm_id());
        $categoryId = spp_forum_guild_category_id($realmId);
        if ($categoryId > 0) {
            return 'index.php?n=forum&sub=viewcategory&catid=' . $categoryId;
        }

        $forumId = spp_forum_guild_forum_id($realmId);
        if ($forumId > 0) {
            return 'index.php?n=forum&sub=viewforum&fid=' . $forumId;
        }

        return 'index.php?n=forum';
    }
}

if (!function_exists('spp_forum_help_menu_url')) {
    function spp_forum_help_menu_url(?int $realmId = null): string
    {
        $realmId = (int)($realmId ?: spp_selected_realm_id());
        $categoryId = spp_forum_help_category_id($realmId);
        if ($categoryId > 0) {
            return 'index.php?n=forum&sub=viewcategory&realm=' . $realmId . '&catid=' . $categoryId;
        }

        $forumId = spp_forum_help_forum_id($realmId);
        if ($forumId > 0) {
            return 'index.php?n=forum&sub=viewforum&realm=' . $realmId . '&fid=' . $forumId;
        }

        return 'index.php?n=forum';
    }
}

if (!function_exists('spp_itemset_note_lookup')) {
    function spp_itemset_note_lookup(int $setId, string $section = '', string $className = '', ?int $realmId = null): array
    {
        static $tableAvailable = [];

        $setId = (int)$setId;
        $section = strtolower(trim($section));
        $className = trim($className);
        $realmId = (int)($realmId ?: spp_selected_realm_id());

        if ($setId <= 0 || $realmId <= 0) {
            return [];
        }

        $tableCacheKey = $realmId;
        if (!array_key_exists($tableCacheKey, $tableAvailable)) {
            try {
                $pdo = spp_get_pdo('armory', $realmId);
                $stmt = $pdo->query("SHOW TABLES LIKE 'armory_itemset_notes'");
                $tableAvailable[$tableCacheKey] = (bool)$stmt->fetchColumn();
            } catch (Throwable $e) {
                $tableAvailable[$tableCacheKey] = false;
            }
        }

        if (!$tableAvailable[$tableCacheKey]) {
            return [];
        }

        try {
            $pdo = spp_get_pdo('armory', $realmId);
            $sql = "SELECT `set_id`, `set_name`, `section`, `class_name`, `note_title`, `note_body`, `piece_count`, `source_key`
                    FROM `armory_itemset_notes`
                    WHERE `set_id` = ?
                      AND `is_active` = 1";
            $params = [$setId];

            if ($section !== '') {
                $sql .= " AND `section` = ?";
                $params[] = $section;
            }

            if ($className !== '') {
                $sql .= " AND (`class_name` = ? OR `class_name` IS NULL OR `class_name` = '')";
                $params[] = $className;
                $sql .= " ORDER BY CASE WHEN `class_name` = ? THEN 0 ELSE 1 END, `sort_order` ASC, `id` ASC LIMIT 1";
                $params[] = $className;
            } else {
                $sql .= " ORDER BY `sort_order` ASC, `id` ASC LIMIT 1";
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return [];
            }

            return [
                'set_id' => (int)($row['set_id'] ?? 0),
                'set_name' => trim((string)($row['set_name'] ?? '')),
                'section' => strtolower(trim((string)($row['section'] ?? ''))),
                'class_name' => trim((string)($row['class_name'] ?? '')),
                'title' => trim((string)($row['note_title'] ?? '')),
                'text' => trim((string)($row['note_body'] ?? '')),
                'pieces' => (int)($row['piece_count'] ?? 0),
                'source_key' => trim((string)($row['source_key'] ?? '')),
            ];
        } catch (Throwable $e) {
            error_log('[itemset notes] lookup failed: ' . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('spp_itemset_catalog_normalize_name')) {
    function spp_itemset_catalog_normalize_name(string $name): string
    {
        return strtolower((string)preg_replace('/[^a-z0-9]+/i', '', trim($name)));
    }
}

if (!function_exists('spp_itemset_catalog_rows')) {
    function spp_itemset_catalog_rows(int $realmId): array
    {
        static $cache = [];

        $realmId = max(1, (int)$realmId);
        if (isset($cache[$realmId])) {
            return $cache[$realmId];
        }

        try {
            $pdo = spp_get_pdo('armory', $realmId);
            $setRows = $pdo->query("SELECT `id`, `name`, `item_1`, `item_2`, `item_3`, `item_4`, `item_5`, `item_6`, `item_7`, `item_8` FROM `dbc_itemset` ORDER BY `name` ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('[itemset catalog] failed loading dbc_itemset: ' . $e->getMessage());
            $cache[$realmId] = [];
            return $cache[$realmId];
        }

        $notesBySetId = [];
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'armory_itemset_notes'");
            if ((bool)$stmt->fetchColumn()) {
                $noteRows = $pdo->query("SELECT `set_id`, `set_name`, `section`, `class_name`, `note_title`, `note_body`, `piece_count`, `source_key`, `sort_order`
                                         FROM `armory_itemset_notes`
                                         WHERE `is_active` = 1
                                         ORDER BY `sort_order` ASC, `id` ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
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
        } catch (Throwable $e) {
            error_log('[itemset catalog] failed loading notes: ' . $e->getMessage());
        }

        $worldItemIds = [];
        foreach ($setRows as $row) {
            for ($i = 1; $i <= 8; $i++) {
                $itemId = (int)($row['item_' . $i] ?? 0);
                if ($itemId > 0) {
                    $worldItemIds[$itemId] = $itemId;
                }
            }
        }

        $worldAvailable = [];
        if (!empty($worldItemIds)) {
            try {
                $worldPdo = spp_get_pdo('world', $realmId);
                $chunks = array_chunk(array_values($worldItemIds), 400);
                foreach ($chunks as $chunk) {
                    if (empty($chunk)) {
                        continue;
                    }
                    $placeholders = implode(',', array_fill(0, count($chunk), '?'));
                    $stmt = $worldPdo->prepare("SELECT `entry` FROM `item_template` WHERE `entry` IN ($placeholders)");
                    $stmt->execute($chunk);
                    foreach (($stmt->fetchAll(PDO::FETCH_COLUMN) ?: []) as $entry) {
                        $entry = (int)$entry;
                        if ($entry > 0) {
                            $worldAvailable[$entry] = $entry;
                        }
                    }
                }
            } catch (Throwable $e) {
                error_log('[itemset catalog] failed loading world item_template availability: ' . $e->getMessage());
            }
        }

        $catalog = [];
        foreach ($setRows as $row) {
            $setId = (int)($row['id'] ?? 0);
            if ($setId <= 0) {
                continue;
            }

            $itemIds = [];
            for ($i = 1; $i <= 8; $i++) {
                $itemId = (int)($row['item_' . $i] ?? 0);
                if ($itemId > 0 && isset($worldAvailable[$itemId])) {
                    $itemIds[] = $itemId;
                }
            }

            if (empty($itemIds)) {
                continue;
            }

            $catalog[] = [
                'id' => $setId,
                'name' => trim((string)($row['name'] ?? '')),
                'normalized_name' => spp_itemset_catalog_normalize_name((string)($row['name'] ?? '')),
                'item_ids' => $itemIds,
                'notes' => $notesBySetId[$setId] ?? [],
            ];
        }

        $cache[$realmId] = $catalog;
        return $cache[$realmId];
    }
}

if (!function_exists('spp_itemset_catalog_select_note')) {
    function spp_itemset_catalog_select_note(array $notes, string $section = '', string $className = ''): array
    {
        $section = strtolower(trim($section));
        $className = trim($className);

        $filtered = array_values(array_filter($notes, static function (array $note) use ($section, $className) {
            if ($section !== '' && $section !== 'all' && strtolower((string)($note['section'] ?? '')) !== $section) {
                return false;
            }
            if ($className !== '' && $className !== 'all') {
                $noteClass = trim((string)($note['class_name'] ?? ''));
                if ($noteClass !== '' && strcasecmp($noteClass, $className) !== 0) {
                    return false;
                }
            }
            return true;
        }));

        if ($filtered) {
            usort($filtered, static function (array $left, array $right) use ($className) {
                $leftClass = trim((string)($left['class_name'] ?? ''));
                $rightClass = trim((string)($right['class_name'] ?? ''));
                $leftScore = ($className !== '' && $className !== 'all' && strcasecmp($leftClass, $className) === 0) ? 0 : ($leftClass === '' ? 2 : 1);
                $rightScore = ($className !== '' && $className !== 'all' && strcasecmp($rightClass, $className) === 0) ? 0 : ($rightClass === '' ? 2 : 1);
                if ($leftScore !== $rightScore) {
                    return $leftScore <=> $rightScore;
                }
                return ((int)($left['sort_order'] ?? 0)) <=> ((int)($right['sort_order'] ?? 0));
            });
            return $filtered[0];
        }

        return $notes[0] ?? [];
    }
}

if (!function_exists('spp_itemset_catalog_find')) {
    function spp_itemset_catalog_find(int $realmId, string $setName): array
    {
        $setName = trim($setName);
        if ($setName === '') {
            return [];
        }

        $normalizedTarget = spp_itemset_catalog_normalize_name($setName);
        $catalog = spp_itemset_catalog_rows($realmId);

        foreach ($catalog as $row) {
            if ((string)($row['normalized_name'] ?? '') === $normalizedTarget) {
                return $row;
            }
        }

        foreach ($catalog as $row) {
            if ($normalizedTarget !== '' && strpos((string)($row['normalized_name'] ?? ''), $normalizedTarget) !== false) {
                return $row;
            }
        }

        return [];
    }
}

if (!function_exists('spp_website_accounts_columns')) {
    function spp_website_accounts_columns() {
        static $columns = null;

        if ($columns !== null) {
            return $columns;
        }

        $columns = [];

        try {
            $pdo = spp_canonical_auth_pdo();
            $rows = $pdo->query("SHOW COLUMNS FROM website_accounts")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                if (!empty($row['Field'])) {
                    $columns[(string)$row['Field']] = true;
                }
            }
        } catch (Throwable $e) {
            error_log('[config] Failed loading website_accounts columns: ' . $e->getMessage());
        }

        return $columns;
    }
}

if (!function_exists('spp_website_accounts_has_columns')) {
    function spp_website_accounts_has_columns(array $requiredColumns) {
        $availableColumns = spp_website_accounts_columns();
        foreach ($requiredColumns as $column) {
            if (empty($availableColumns[$column])) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('spp_account_profile_table_name')) {
    function spp_account_profile_table_name() {
        return 'website_account_profiles';
    }
}

if (!function_exists('spp_account_profile_columns')) {
    function spp_account_profile_columns() {
        static $columns = null;

        if ($columns !== null) {
            return $columns;
        }

        $columns = [];

        try {
            $pdo = spp_canonical_auth_pdo();
            $tableName = spp_account_profile_table_name();
            $rows = $pdo->query("SHOW COLUMNS FROM `{$tableName}`")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                if (!empty($row['Field'])) {
                    $columns[(string)$row['Field']] = true;
                }
            }
        } catch (Throwable $e) {
            error_log('[config] Failed loading account profile columns: ' . $e->getMessage());
        }

        return $columns;
    }
}

if (!function_exists('spp_account_profile_has_columns')) {
    function spp_account_profile_has_columns(array $requiredColumns) {
        $availableColumns = spp_account_profile_columns();
        foreach ($requiredColumns as $column) {
            if (empty($availableColumns[$column])) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('spp_account_profile_field_list')) {
    function spp_account_profile_field_list() {
        return [
            'character_id',
            'character_name',
            'character_realm_id',
            'display_name',
            'avatar',
            'signature',
            'hideemail',
            'hideprofile',
            'hidelocation',
            'theme',
            'background_mode',
            'background_image',
            'secretq1',
            'secretq2',
            'secreta1',
            'secreta2',
        ];
    }
}

if (!function_exists('spp_filter_account_profile_fields')) {
    function spp_filter_account_profile_fields(array $fields) {
        $allowed = array_fill_keys(spp_account_profile_field_list(), true);
        $filtered = [];
        foreach ($fields as $key => $value) {
            if (isset($allowed[$key])) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }
}

if (!function_exists('spp_ensure_account_profile_row')) {
    function spp_ensure_account_profile_row(PDO $pdo, $accountId) {
        $accountId = (int)$accountId;
        if ($accountId <= 0 || !spp_account_profile_has_columns(['account_id'])) {
            return;
        }

        $tableName = spp_account_profile_table_name();
        $stmt = $pdo->prepare("
            INSERT INTO `{$tableName}` (`account_id`)
            SELECT ?
            WHERE NOT EXISTS (
                SELECT 1 FROM `{$tableName}` WHERE `account_id` = ?
            )
        ");
        $stmt->execute([$accountId, $accountId]);
    }
}

if (!function_exists('spp_update_account_profile_fields')) {
    function spp_update_account_profile_fields(PDO $pdo, $accountId, array $fields) {
        $accountId = (int)$accountId;
        if ($accountId <= 0) {
            return false;
        }

        $fields = spp_filter_account_profile_fields($fields);
        if (empty($fields)) {
            return false;
        }

        spp_ensure_account_profile_row($pdo, $accountId);

        $setClause = implode(',', array_map(
            function ($key) {
                return '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $key) . '`=?';
            },
            array_keys($fields)
        ));

        $values = array_values($fields);
        $values[] = $accountId;
        $tableName = spp_account_profile_table_name();
        $stmt = $pdo->prepare("UPDATE `{$tableName}` SET {$setClause} WHERE account_id=? LIMIT 1");
        return $stmt->execute($values);
    }
}

if (!function_exists('spp_identity_pdo')) {
    function spp_identity_pdo() {
        return spp_canonical_auth_pdo();
    }
}
if (!function_exists('spp_get_account_identity')) {
    function spp_get_account_identity($realmId, $accountId) {
        $pdo  = spp_identity_pdo();
        $stmt = $pdo->prepare("
            SELECT * FROM `website_identities`
            WHERE identity_key = ?
            LIMIT 1
        ");
        $stmt->execute(["account:{$realmId}:{$accountId}"]);
        return $stmt->fetch() ?: null;
    }
}
if (!function_exists('spp_get_char_identity')) {
    function spp_get_char_identity($realmId, $charGuid) {
        $pdo  = spp_identity_pdo();
        $stmt = $pdo->prepare("
            SELECT * FROM `website_identities`
            WHERE identity_key = ?
            LIMIT 1
        ");
        $stmt->execute(["char:{$realmId}:{$charGuid}"]);
        return $stmt->fetch() ?: null;
    }
}
if (!function_exists('spp_ensure_account_identity')) {
    function spp_ensure_account_identity($realmId, $accountId, $displayName) {
        $pdo  = spp_identity_pdo();
        $key  = "account:{$realmId}:{$accountId}";
        $pdo->prepare("
            INSERT IGNORE INTO `website_identities`
              (identity_type, owner_account_id, realm_id, display_name, identity_key, is_bot, is_active)
            VALUES ('account', ?, ?, ?, ?, 0, 1)
        ")->execute([(int)$accountId, (int)$realmId, (string)$displayName, $key]);

        $stmt = $pdo->prepare("SELECT identity_id FROM `website_identities` WHERE identity_key = ? LIMIT 1");
        $stmt->execute([$key]);
        return (int)$stmt->fetchColumn();
    }
}

if (!function_exists('spp_ensure_named_identity')) {
    function spp_ensure_named_identity($realmId, $displayName, $identityNamespace = 'staff') {
        $realmId = (int)$realmId;
        $displayName = trim((string)$displayName);
        $identityNamespace = trim((string)$identityNamespace);
        if ($realmId <= 0 || $displayName === '') {
            return 0;
        }

        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $displayName));
        $slug = trim((string)$slug, '-');
        if ($slug === '') {
            return 0;
        }

        $pdo = spp_identity_pdo();
        $key = $identityNamespace . ':' . $realmId . ':' . $slug;
        $pdo->prepare("
            INSERT INTO `website_identities`
              (identity_type, owner_account_id, realm_id, display_name, identity_key, is_bot, is_active)
            VALUES ('account', NULL, ?, ?, ?, 0, 1)
            ON DUPLICATE KEY UPDATE
              `display_name` = VALUES(`display_name`),
              `is_active` = 1
        ")->execute([$realmId, $displayName, $key]);

        $stmt = $pdo->prepare("SELECT identity_id FROM `website_identities` WHERE identity_key = ? LIMIT 1");
        $stmt->execute([$key]);
        return (int)$stmt->fetchColumn();
    }
}
if (!function_exists('spp_ensure_char_identity')) {
    function spp_ensure_char_identity($realmId, $charGuid, $accountId, $charName, $isBot = 0, $guildId = null) {
        $pdo  = spp_identity_pdo();
        $key  = "char:{$realmId}:{$charGuid}";
        $type = $isBot ? 'bot_character' : 'character';
        $pdo->prepare("
            INSERT IGNORE INTO `website_identities`
              (identity_type, owner_account_id, realm_id, character_guid,
               display_name, identity_key, guild_id, is_bot, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
        ")->execute([$type, (int)$accountId, (int)$realmId, (int)$charGuid,
                     (string)$charName, $key, $guildId ? (int)$guildId : null, (int)$isBot]);

        $stmt = $pdo->prepare("SELECT identity_id FROM `website_identities` WHERE identity_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $identityId = (int)$stmt->fetchColumn();
        if ($identityId > 0 && function_exists('spp_seed_identity_signature_defaults')) {
            spp_seed_identity_signature_defaults($identityId, [
                'realm_id' => (int)$realmId,
                'character_guid' => (int)$charGuid,
                'owner_account_id' => (int)$accountId,
                'display_name' => (string)$charName,
                'identity_type' => $type,
                'guild_id' => $guildId ? (int)$guildId : null,
                'is_bot' => (int)$isBot,
            ]);
        }
        if ($identityId > 0 && (int)$isBot === 1 && function_exists('spp_seed_identity_play_habit_defaults')) {
            spp_seed_identity_play_habit_defaults($identityId, [
                'realm_id' => (int)$realmId,
                'character_guid' => (int)$charGuid,
                'owner_account_id' => (int)$accountId,
                'display_name' => (string)$charName,
                'identity_type' => $type,
                'guild_id' => $guildId ? (int)$guildId : null,
                'is_bot' => (int)$isBot,
            ]);
        }
        return $identityId;
    }
}
if (!function_exists('spp_deactivate_account_identities')) {
    function spp_deactivate_account_identities($realmId, $accountId) {
        $pdo = spp_identity_pdo();
        $pdo->prepare("
            UPDATE `website_identities`
            SET is_active = 0, updated_at = NOW()
            WHERE owner_account_id = ? AND realm_id = ?
        ")->execute([(int)$accountId, (int)$realmId]);
    }
}
if (!function_exists('spp_resolve_identity_names')) {
    function spp_resolve_identity_names(array $identityIds): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $identityIds))));
        if (empty($ids)) {
            return [];
        }
        try {
            $pdo = spp_identity_pdo();
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("
                SELECT identity_id, display_name FROM `website_identities`
                WHERE identity_id IN ({$placeholders})
            ");
            $stmt->execute($ids);
            $map = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $map[(int)$row['identity_id']] = (string)$row['display_name'];
            }
            return $map;
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('spp_identity_profile_table_name')) {
    function spp_identity_profile_table_name() {
        return 'website_identity_profiles';
    }
}

if (!function_exists('spp_identity_profile_trait_column_sql')) {
    function spp_identity_profile_trait_column_sql(string $columnName): string
    {
        switch (trim($columnName)) {
            case 'play_style_key':
                return '`play_style_key` VARCHAR(32) DEFAULT NULL';
            case 'weekly_frequency_hint':
                return '`weekly_frequency_hint` VARCHAR(40) DEFAULT NULL';
            case 'session_duration_hint_min':
                return '`session_duration_hint_min` SMALLINT(5) UNSIGNED DEFAULT NULL';
            case 'session_duration_hint_max':
                return '`session_duration_hint_max` SMALLINT(5) UNSIGNED DEFAULT NULL';
            case 'preferred_days':
                return '`preferred_days` VARCHAR(96) DEFAULT NULL';
            case 'preferred_hours':
                return '`preferred_hours` VARCHAR(96) DEFAULT NULL';
            case 'cohort_key':
                return '`cohort_key` VARCHAR(32) DEFAULT NULL';
            case 'life_stage_hint':
                return '`life_stage_hint` VARCHAR(32) DEFAULT NULL';
        }

        return '';
    }
}

if (!function_exists('spp_identity_profile_bot_trait_keys')) {
    function spp_identity_profile_bot_trait_keys(): array
    {
        return array(
            'play_style_key',
            'weekly_frequency_hint',
            'session_duration_hint_min',
            'session_duration_hint_max',
            'preferred_days',
            'preferred_hours',
            'cohort_key',
            'life_stage_hint',
        );
    }
}

if (!function_exists('spp_identity_profile_is_bot_identity')) {
    function spp_identity_profile_is_bot_identity(array $identityRow): bool
    {
        return (int)($identityRow['is_bot'] ?? 0) === 1 || (string)($identityRow['identity_type'] ?? '') === 'bot_character';
    }
}

if (!function_exists('spp_identity_profile_default_bot_traits')) {
    function spp_identity_profile_default_bot_traits(array $identityRow = []): array
    {
        $defaults = array(
            'play_style_key' => null,
            'weekly_frequency_hint' => null,
            'session_duration_hint_min' => null,
            'session_duration_hint_max' => null,
            'preferred_days' => null,
            'preferred_hours' => null,
            'cohort_key' => null,
            'life_stage_hint' => null,
        );

        if (!spp_identity_profile_is_bot_identity($identityRow)) {
            return $defaults;
        }

        $seedSource = trim(implode(':', array(
            (string)(int)($identityRow['realm_id'] ?? 0),
            (string)(int)($identityRow['character_guid'] ?? 0),
            (string)(int)($identityRow['owner_account_id'] ?? 0),
            strtolower(trim((string)($identityRow['display_name'] ?? ''))),
            strtolower(trim((string)($identityRow['identity_key'] ?? ''))),
        )), ':');
        if ($seedSource === '') {
            $seedSource = 'bot:default';
        }

        $seed = (int)sprintf('%u', crc32('habit:' . $seedSource));
        $playStyles = array('balanced', 'social', 'grinder', 'night_owl', 'weekend', 'support', 'pvp', 'crafting');
        $weeklyHints = array('daily', 'weekdays', 'weekends', '3-4 days/week', 'evenings', 'flexible');
        $durationPairs = array(
            array(30, 60),
            array(45, 90),
            array(60, 120),
            array(90, 180),
        );
        $dayHints = array('Mon-Fri', 'Mon/Wed/Fri', 'Tue/Thu/Sat', 'Fri-Sun', 'Weekends', 'Daily');
        $hourHints = array('18:00-22:00', '19:00-23:00', '20:00-00:00', '09:00-13:00', '12:00-16:00', 'Flexible');
        $cohortKeys = array('general', 'dungeon', 'raid', 'pvp', 'crafting', 'social');
        $lifeStageHints = array('leveling', 'midgame', 'endgame', 'maintenance', 'roaming');
        $durationPair = $durationPairs[$seed % count($durationPairs)];

        $defaults['play_style_key'] = $playStyles[$seed % count($playStyles)];
        $defaults['weekly_frequency_hint'] = $weeklyHints[($seed >> 3) % count($weeklyHints)];
        $defaults['session_duration_hint_min'] = (int)$durationPair[0];
        $defaults['session_duration_hint_max'] = (int)$durationPair[1];
        $defaults['preferred_days'] = $dayHints[($seed >> 5) % count($dayHints)];
        $defaults['preferred_hours'] = $hourHints[($seed >> 7) % count($hourHints)];
        $defaults['cohort_key'] = $cohortKeys[($seed >> 9) % count($cohortKeys)];
        $defaults['life_stage_hint'] = $lifeStageHints[($seed >> 11) % count($lifeStageHints)];

        return $defaults;
    }
}

if (!function_exists('spp_identity_profile_normalize_bot_trait_value')) {
    function spp_identity_profile_normalize_bot_trait_value(string $columnName, $value)
    {
        $value = is_scalar($value) || $value === null ? trim((string)$value) : '';
        switch ($columnName) {
            case 'session_duration_hint_min':
            case 'session_duration_hint_max':
                $intValue = (int)$value;
                return $intValue > 0 ? $intValue : null;
            case 'preferred_days':
            case 'preferred_hours':
                $value = preg_replace('/\s+/', ' ', $value);
                return $value !== '' ? $value : null;
            case 'play_style_key':
            case 'weekly_frequency_hint':
            case 'cohort_key':
            case 'life_stage_hint':
                $value = strtolower(preg_replace('/\s+/', ' ', $value));
                return $value !== '' ? $value : null;
        }

        return $value !== '' ? $value : null;
    }
}

if (!function_exists('spp_identity_profile_effective_bot_traits')) {
    function spp_identity_profile_effective_bot_traits(array $identityRow = [], array $storedTraits = []): array
    {
        $effectiveTraits = spp_identity_profile_default_bot_traits($identityRow);

        foreach (spp_identity_profile_bot_trait_keys() as $traitKey) {
            if (!array_key_exists($traitKey, $storedTraits)) {
                continue;
            }

            $normalizedValue = spp_identity_profile_normalize_bot_trait_value($traitKey, $storedTraits[$traitKey]);
            if ($normalizedValue !== null) {
                $effectiveTraits[$traitKey] = $normalizedValue;
            }
        }

        $minValue = (int)($effectiveTraits['session_duration_hint_min'] ?? 0);
        $maxValue = (int)($effectiveTraits['session_duration_hint_max'] ?? 0);
        if ($minValue > 0 && $maxValue > 0 && $maxValue < $minValue) {
            $effectiveTraits['session_duration_hint_max'] = $minValue;
        }

        return $effectiveTraits;
    }
}

if (!function_exists('spp_ensure_identity_profile_table')) {
    function spp_ensure_identity_profile_table() {
        static $ensured = false;

        if ($ensured) {
            return true;
        }

        try {
            $pdo = spp_identity_pdo();
            $tableName = spp_identity_profile_table_name();
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `{$tableName}` (
                  `identity_id` INT(11) UNSIGNED NOT NULL,
                  `signature` TEXT DEFAULT NULL,
                  `play_style_key` VARCHAR(32) DEFAULT NULL,
                  `weekly_frequency_hint` VARCHAR(40) DEFAULT NULL,
                  `session_duration_hint_min` SMALLINT(5) UNSIGNED DEFAULT NULL,
                  `session_duration_hint_max` SMALLINT(5) UNSIGNED DEFAULT NULL,
                  `preferred_days` VARCHAR(96) DEFAULT NULL,
                  `preferred_hours` VARCHAR(96) DEFAULT NULL,
                  `cohort_key` VARCHAR(32) DEFAULT NULL,
                  `life_stage_hint` VARCHAR(32) DEFAULT NULL,
                  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`identity_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
            try {
                $existingColumns = spp_db_table_columns($pdo, $tableName);
                $orderedColumns = array(
                    'play_style_key',
                    'weekly_frequency_hint',
                    'session_duration_hint_min',
                    'session_duration_hint_max',
                    'preferred_days',
                    'preferred_hours',
                    'cohort_key',
                    'life_stage_hint',
                );
                $previousColumn = 'signature';
                foreach ($orderedColumns as $columnName) {
                    if (isset($existingColumns[$columnName])) {
                        $previousColumn = $columnName;
                        continue;
                    }

                    $columnSql = spp_identity_profile_trait_column_sql($columnName);
                    if ($columnSql === '') {
                        continue;
                    }

                    $afterClause = $previousColumn !== '' ? ' AFTER `' . $previousColumn . '`' : '';
                    $pdo->exec('ALTER TABLE `' . $tableName . '` ADD COLUMN ' . $columnSql . $afterClause);
                    $existingColumns[$columnName] = true;
                    $previousColumn = $columnName;
                }
            } catch (Throwable $columnError) {
                error_log('[config] Failed ensuring identity trait columns: ' . $columnError->getMessage());
            }
            $ensured = true;
            return true;
        } catch (Throwable $e) {
            error_log('[config] Failed ensuring identity profile table: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('spp_get_identity_signature')) {
    function spp_get_identity_signature($identityId) {
        $identityId = (int)$identityId;
        if ($identityId <= 0 || !spp_ensure_identity_profile_table()) {
            return '';
        }

        try {
            $pdo = spp_identity_pdo();
            $tableName = spp_identity_profile_table_name();
            $stmt = $pdo->prepare("SELECT signature FROM `{$tableName}` WHERE identity_id = ? LIMIT 1");
            $stmt->execute([$identityId]);
            $signature = $stmt->fetchColumn();
            $signature = $signature !== false ? (string)$signature : '';
            if (!spp_identity_signature_has_value($signature) && function_exists('spp_seed_identity_signature_defaults')) {
                $seededSignature = spp_seed_identity_signature_defaults($identityId);
                if (spp_identity_signature_has_value($seededSignature)) {
                    return $seededSignature;
                }
            }
            return $signature;
        } catch (Throwable $e) {
            error_log('[config] Failed loading identity signature: ' . $e->getMessage());
            return '';
        }
    }
}

if (!function_exists('spp_update_identity_signature')) {
    function spp_update_identity_signature($identityId, $signature) {
        $identityId = (int)$identityId;
        if ($identityId <= 0 || !spp_ensure_identity_profile_table()) {
            return false;
        }

        try {
            $pdo = spp_identity_pdo();
            $tableName = spp_identity_profile_table_name();
            $stmt = $pdo->prepare("
                INSERT INTO `{$tableName}` (`identity_id`, `signature`)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE `signature` = VALUES(`signature`)
            ");
            return $stmt->execute([$identityId, (string)$signature]);
        } catch (Throwable $e) {
            error_log('[config] Failed saving identity signature: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('spp_get_identity_play_habit_traits')) {
    function spp_get_identity_play_habit_traits($identityId, array $identityRow = []): array
    {
        $identityId = (int)$identityId;
        $emptyTraits = array(
            'play_style_key' => null,
            'weekly_frequency_hint' => null,
            'session_duration_hint_min' => null,
            'session_duration_hint_max' => null,
            'preferred_days' => null,
            'preferred_hours' => null,
            'cohort_key' => null,
            'life_stage_hint' => null,
        );

        if ($identityId <= 0) {
            return $emptyTraits;
        }

        if (empty($identityRow)) {
            try {
                $pdo = spp_identity_pdo();
                $stmt = $pdo->prepare("
                    SELECT `identity_id`, `identity_type`, `owner_account_id`, `realm_id`,
                           `character_guid`, `display_name`, `identity_key`, `guild_id`, `is_bot`
                    FROM `website_identities`
                    WHERE `identity_id` = ?
                    LIMIT 1
                ");
                $stmt->execute(array($identityId));
                $identityRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: array();
            } catch (Throwable $e) {
                error_log('[config] Failed loading identity row for trait read: ' . $e->getMessage());
                return $emptyTraits;
            }
        }

        if (!spp_identity_profile_is_bot_identity($identityRow)) {
            return $emptyTraits;
        }

        if (!spp_ensure_identity_profile_table()) {
            return spp_identity_profile_default_bot_traits($identityRow);
        }

        try {
            $pdo = spp_identity_pdo();
            $tableName = spp_identity_profile_table_name();
            $stmt = $pdo->prepare("
                SELECT `play_style_key`, `weekly_frequency_hint`, `session_duration_hint_min`,
                       `session_duration_hint_max`, `preferred_days`, `preferred_hours`,
                       `cohort_key`, `life_stage_hint`
                FROM `{$tableName}`
                WHERE `identity_id` = ?
                LIMIT 1
            ");
            $stmt->execute(array($identityId));
            $storedTraits = $stmt->fetch(PDO::FETCH_ASSOC) ?: array();

            return spp_identity_profile_effective_bot_traits($identityRow, $storedTraits);
        } catch (Throwable $e) {
            error_log('[config] Failed loading identity play habits: ' . $e->getMessage());
            return spp_identity_profile_default_bot_traits($identityRow);
        }
    }
}

if (!function_exists('spp_update_identity_play_habit_traits')) {
    function spp_update_identity_play_habit_traits($identityId, array $traits) {
        $identityId = (int)$identityId;
        if ($identityId <= 0 || !spp_ensure_identity_profile_table()) {
            return false;
        }

        try {
            $pdo = spp_identity_pdo();
            $stmt = $pdo->prepare("
                SELECT `identity_id`, `identity_type`, `owner_account_id`, `realm_id`,
                       `character_guid`, `display_name`, `identity_key`, `guild_id`, `is_bot`
                FROM `website_identities`
                WHERE `identity_id` = ?
                LIMIT 1
            ");
            $stmt->execute(array($identityId));
            $identityRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: array();
            if (!spp_identity_profile_is_bot_identity($identityRow)) {
                return false;
            }

            $normalizedTraits = array();
            foreach (spp_identity_profile_bot_trait_keys() as $traitKey) {
                $normalizedTraits[$traitKey] = spp_identity_profile_normalize_bot_trait_value($traitKey, $traits[$traitKey] ?? null);
            }

            $tableName = spp_identity_profile_table_name();
            $stmt = $pdo->prepare("
                INSERT INTO `{$tableName}` (
                    `identity_id`, `play_style_key`, `weekly_frequency_hint`,
                    `session_duration_hint_min`, `session_duration_hint_max`,
                    `preferred_days`, `preferred_hours`, `cohort_key`, `life_stage_hint`
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    `play_style_key` = VALUES(`play_style_key`),
                    `weekly_frequency_hint` = VALUES(`weekly_frequency_hint`),
                    `session_duration_hint_min` = VALUES(`session_duration_hint_min`),
                    `session_duration_hint_max` = VALUES(`session_duration_hint_max`),
                    `preferred_days` = VALUES(`preferred_days`),
                    `preferred_hours` = VALUES(`preferred_hours`),
                    `cohort_key` = VALUES(`cohort_key`),
                    `life_stage_hint` = VALUES(`life_stage_hint`)
            ");
            return $stmt->execute(array(
                $identityId,
                $normalizedTraits['play_style_key'],
                $normalizedTraits['weekly_frequency_hint'],
                $normalizedTraits['session_duration_hint_min'],
                $normalizedTraits['session_duration_hint_max'],
                $normalizedTraits['preferred_days'],
                $normalizedTraits['preferred_hours'],
                $normalizedTraits['cohort_key'],
                $normalizedTraits['life_stage_hint'],
            ));
        } catch (Throwable $e) {
            error_log('[config] Failed saving identity play habits: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('spp_seed_identity_play_habit_defaults')) {
    function spp_seed_identity_play_habit_defaults($identityId, array $identityRow = []) {
        $identityId = (int)$identityId;
        if ($identityId <= 0) {
            return array();
        }

        if (empty($identityRow)) {
            try {
                $pdo = spp_identity_pdo();
                $stmt = $pdo->prepare("
                    SELECT `identity_id`, `identity_type`, `owner_account_id`, `realm_id`,
                           `character_guid`, `display_name`, `identity_key`, `guild_id`, `is_bot`
                    FROM `website_identities`
                    WHERE `identity_id` = ?
                    LIMIT 1
                ");
                $stmt->execute(array($identityId));
                $identityRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: array();
            } catch (Throwable $e) {
                error_log('[config] Failed loading identity row for trait seeding: ' . $e->getMessage());
                return array();
            }
        }

        if (!spp_identity_profile_is_bot_identity($identityRow)) {
            return array();
        }

        if (!spp_ensure_identity_profile_table()) {
            return spp_identity_profile_default_bot_traits($identityRow);
        }

        try {
            $pdo = spp_identity_pdo();
            $tableName = spp_identity_profile_table_name();
            $stmt = $pdo->prepare("SELECT 1 FROM `{$tableName}` WHERE `identity_id` = ? LIMIT 1");
            $stmt->execute(array($identityId));
            $hasRow = (bool)$stmt->fetchColumn();
            if ($hasRow) {
                return spp_get_identity_play_habit_traits($identityId, $identityRow);
            }

            $defaultTraits = spp_identity_profile_default_bot_traits($identityRow);
            return spp_update_identity_play_habit_traits($identityId, $defaultTraits) ? $defaultTraits : $defaultTraits;
        } catch (Throwable $e) {
            error_log('[config] Failed seeding identity play habits: ' . $e->getMessage());
            return spp_identity_profile_default_bot_traits($identityRow);
        }
    }
}

if (!function_exists('spp_identity_signature_has_value')) {
    function spp_identity_signature_has_value($signature) {
        return trim((string)$signature) !== '';
    }
}

if (!function_exists('spp_get_identity_account_username')) {
    function spp_get_identity_account_username($realmId, $accountId) {
        $realmId = (int)$realmId;
        $accountId = (int)$accountId;
        if ($realmId <= 0 || $accountId <= 0) {
            return '';
        }

        try {
            $pdo = spp_get_pdo('realmd', $realmId);
            $stmt = $pdo->prepare("SELECT `username` FROM `account` WHERE `id` = ? LIMIT 1");
            $stmt->execute([$accountId]);
            $username = $stmt->fetchColumn();
            return $username !== false ? (string)$username : '';
        } catch (Throwable $e) {
            error_log('[config] Failed loading identity account username: ' . $e->getMessage());
            return '';
        }
    }
}

if (!function_exists('spp_get_identity_guild_context')) {
    function spp_get_identity_guild_context($realmId, $charGuid, $guildId = null) {
        $realmId = (int)$realmId;
        $charGuid = (int)$charGuid;
        $guildId = $guildId !== null ? (int)$guildId : 0;

        if ($realmId <= 0 || $charGuid <= 0) {
            return ['guild_id' => 0, 'guild_name' => '', 'leader_guid' => 0, 'is_leader' => false];
        }

        try {
            $pdo = spp_get_pdo('chars', $realmId);
            if ($guildId <= 0) {
                $stmt = $pdo->prepare("SELECT `guildid` FROM `guild_member` WHERE `guid` = ? LIMIT 1");
                $stmt->execute([$charGuid]);
                $guildId = (int)($stmt->fetchColumn() ?: 0);
            }

            if ($guildId <= 0) {
                return ['guild_id' => 0, 'guild_name' => '', 'leader_guid' => 0, 'is_leader' => false];
            }

            $stmt = $pdo->prepare("SELECT `name`, `leaderguid` FROM `guild` WHERE `guildid` = ? LIMIT 1");
            $stmt->execute([$guildId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            $leaderGuid = (int)($row['leaderguid'] ?? 0);

            return [
                'guild_id' => $guildId,
                'guild_name' => (string)($row['name'] ?? ''),
                'leader_guid' => $leaderGuid,
                'is_leader' => $leaderGuid > 0 && $leaderGuid === $charGuid,
            ];
        } catch (Throwable $e) {
            error_log('[config] Failed loading identity guild context: ' . $e->getMessage());
            return ['guild_id' => 0, 'guild_name' => '', 'leader_guid' => 0, 'is_leader' => false];
        }
    }
}

if (!function_exists('spp_build_default_identity_signature')) {
    function spp_build_default_identity_signature(array $identityRow) {
        $realmId = (int)($identityRow['realm_id'] ?? 0);
        $charGuid = (int)($identityRow['character_guid'] ?? 0);
        $accountId = (int)($identityRow['owner_account_id'] ?? 0);
        $charName = trim((string)($identityRow['display_name'] ?? ''));
        $guildId = isset($identityRow['guild_id']) ? (int)$identityRow['guild_id'] : 0;
        $isBot = (int)($identityRow['is_bot'] ?? 0) === 1 || (string)($identityRow['identity_type'] ?? '') === 'bot_character';
        $accountUsername = spp_get_identity_account_username($realmId, $accountId);
        $guildContext = spp_get_identity_guild_context($realmId, $charGuid, $guildId);

        if (!empty($guildContext['is_leader']) && !empty($guildContext['guild_name'])) {
            return "[b]Guild Master[/b] of <{$guildContext['guild_name']}>\nRecruiting strong players for the road ahead.";
        }

        if ($isBot && $charGuid > 0) {
            $seed = sprintf('%u', crc32('sig:' . $realmId . ':' . $charGuid . ':' . strtolower($accountUsername ?: $charName)));
            if (((int)$seed % 100) >= 45) {
                return '';
            }

            $botSignatures = [
                "Watching the roads for the next adventure.",
                "Training hard. Posting harder.",
                "Always one pull away from greatness.",
                "Keeping the blades sharp and the bags full.",
                "Questing, crafting, and causing a little trouble.",
                "One more run, then one more after that.",
                "Built by chaos, held together by loot.",
                "Marching toward better gear and bad decisions.",
                "Ready for dungeons, danger, and detours.",
                "Some bots idle. This one has plans.",
            ];

            return $botSignatures[((int)$seed) % count($botSignatures)];
        }

        return '';
    }
}

if (!function_exists('spp_seed_identity_signature_defaults')) {
    function spp_seed_identity_signature_defaults($identityId, array $identityRow = []) {
        $identityId = (int)$identityId;
        if ($identityId <= 0 || !spp_ensure_identity_profile_table()) {
            return '';
        }

        try {
            $pdo = spp_identity_pdo();
            $tableName = spp_identity_profile_table_name();
            $stmt = $pdo->prepare("SELECT signature FROM `{$tableName}` WHERE identity_id = ? LIMIT 1");
            $stmt->execute([$identityId]);
            $existingSignature = $stmt->fetchColumn();
            $existingSignature = $existingSignature !== false ? (string)$existingSignature : '';
            if (spp_identity_signature_has_value($existingSignature)) {
                return $existingSignature;
            }
        } catch (Throwable $e) {
            error_log('[config] Failed checking existing identity signature: ' . $e->getMessage());
            return '';
        }

        if (empty($identityRow)) {
            try {
                $stmt = $pdo->prepare("
                    SELECT `identity_id`, `identity_type`, `owner_account_id`, `realm_id`,
                           `character_guid`, `display_name`, `guild_id`, `is_bot`
                    FROM `website_identities`
                    WHERE `identity_id` = ?
                    LIMIT 1
                ");
                $stmt->execute([$identityId]);
                $identityRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable $e) {
                error_log('[config] Failed loading identity row for signature seeding: ' . $e->getMessage());
                return '';
            }
        }

        if (empty($identityRow)) {
            return '';
        }

        $signature = spp_build_default_identity_signature($identityRow);
        if (!spp_identity_signature_has_value($signature)) {
            return '';
        }

        $savedSignature = spp_update_identity_signature($identityId, $signature) ? $signature : '';
        if ($savedSignature !== '' && function_exists('spp_seed_identity_play_habit_defaults')) {
            spp_seed_identity_play_habit_defaults($identityId, $identityRow);
        }

        return $savedSignature;
    }
}
