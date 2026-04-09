<?php

require_once __DIR__ . '/realm-capabilities.php';

if (!function_exists('spp_server_realmlist_lookup_host')) {
    function spp_server_realmlist_lookup_host(int $realmId): string
    {
        $host = '';

        if (function_exists('spp_get_pdo')) {
            try {
                $pdo = spp_get_pdo('realmd', $realmId);
                $stmt = $pdo->prepare('SELECT `address` FROM `realmlist` WHERE `id` = ? LIMIT 1');
                $stmt->execute(array($realmId));
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!empty($row['address'])) {
                    $host = trim((string)$row['address']);
                }
            } catch (Throwable $e) {
                $host = '';
            }
        }

        return $host;
    }
}

if (!function_exists('spp_server_realmlist_lookup_name')) {
    function spp_server_realmlist_lookup_name(array $realmMap, int $realmId): string
    {
        return spp_realm_display_name($realmId, $realmMap, 'Realm #%d');
    }
}

if (!function_exists('spp_server_realmlist_enabled_realm_map')) {
    function spp_server_realmlist_enabled_realm_map(array $realmMap): array
    {
        $configuredRealmMap = (array)($GLOBALS['allConfiguredRealmDbMap'] ?? $GLOBALS['fallbackConfiguredRealmDbMap'] ?? array());
        if (empty($configuredRealmMap)) {
            $configuredRealmMap = $realmMap;
        }

        $dbBackedRealmMap = (array)($GLOBALS['dbBackedRealmDbMap'] ?? array());
        if (empty($dbBackedRealmMap) && function_exists('spp_realm_runtime_catalog') && !empty($configuredRealmMap)) {
            $runtimeCatalog = (array)spp_realm_runtime_catalog($configuredRealmMap);
            $dbBackedRealmMap = (array)($runtimeCatalog['realm_db_map'] ?? array());
        }
        if (empty($dbBackedRealmMap)) {
            $dbBackedRealmMap = $realmMap;
        }

        $enabledRealmIds = array();
        if (function_exists('spp_realm_runtime_state') && !empty($dbBackedRealmMap)) {
            $runtimeState = (array)spp_realm_runtime_state($dbBackedRealmMap);
            $enabledRealmIds = array_values(array_map('intval', (array)($runtimeState['enabled_realm_ids'] ?? array())));
        }

        $enabledRealmMap = array();
        if (!empty($enabledRealmIds)) {
            foreach ($enabledRealmIds as $realmId) {
                if (isset($dbBackedRealmMap[$realmId])) {
                    $enabledRealmMap[$realmId] = $dbBackedRealmMap[$realmId];
                }
            }
        }

        if (empty($enabledRealmMap)) {
            $enabledRealmMap = (array)($GLOBALS['allEnabledRealmDbMap'] ?? array());
        }

        if (empty($enabledRealmMap)) {
            $enabledRealmMap = $dbBackedRealmMap;
        }

        return $enabledRealmMap;
    }
}

if (!function_exists('spp_server_realmlist_download_options')) {
    function spp_server_realmlist_download_options(array $realmMap, ?int $selectedRealmId = null): array
    {
        $options = array();
        $realmMap = spp_server_realmlist_enabled_realm_map($realmMap);
        $realmIds = array_keys($realmMap);
        sort($realmIds, SORT_NUMERIC);

        foreach ($realmIds as $realmId) {
            $realmId = (int)$realmId;
            if ($realmId <= 0) {
                continue;
            }

            $host = spp_server_realmlist_lookup_host($realmId);
            $options[] = array(
                'realm_id' => $realmId,
                'realm_name' => spp_server_realmlist_lookup_name($realmMap, $realmId),
                'host' => $host,
                'href' => 'index.php?n=server&sub=realmlist&nobody=1&realm=' . $realmId,
                'filename' => $realmId === 1 ? 'realmlist.wtf' : ('realmlist-' . $realmId . '.wtf'),
                'is_selected' => $selectedRealmId !== null && $realmId === $selectedRealmId,
            );
        }

        return $options;
    }
}

if (!function_exists('spp_server_realmlist_endpoint_state')) {
    function spp_server_realmlist_endpoint_state(array $args = array()): array
    {
        $query = is_array($args['query'] ?? null) ? $args['query'] : $_GET;
        $server = is_array($args['server'] ?? null) ? $args['server'] : $_SERVER;
        $realmMap = spp_server_realmlist_enabled_realm_map((array)($GLOBALS['realmDbMap'] ?? array()));
        $realmId = isset($query['realm']) ? (int)$query['realm'] : 1;
        if ($realmId <= 0 || !isset($realmMap[$realmId])) {
            $realmId = !empty($realmMap) ? (int)spp_resolve_realm_id($realmMap) : 1;
        }
        $host = '';
        $clientConnectionHost = '';
        $realmCapabilities = spp_realm_capabilities($realmMap, $realmId);

        if (!empty($args['client_connection_host'])) {
            $clientConnectionHost = trim((string)$args['client_connection_host']);
        } elseif (!empty($GLOBALS['clientConnectionHost'])) {
            $clientConnectionHost = trim((string)$GLOBALS['clientConnectionHost']);
        } else {
            $configProtected = dirname(__DIR__, 2) . '/config/config-protected.php';
            if (!empty($_SERVER['DOCUMENT_ROOT']) && is_file($configProtected)) {
                require_once $configProtected;
            }

            if (isset($clientConnectionHost) && is_string($clientConnectionHost)) {
                $clientConnectionHost = trim($clientConnectionHost);
            }
        }

        $host = spp_server_realmlist_lookup_host($realmId);

        if ($host === '' && $clientConnectionHost !== '') {
            $host = $clientConnectionHost;
        }

        if ($host === '' && !empty($server['HTTP_HOST'])) {
            $host = preg_replace('/:\d+$/', '', (string)$server['HTTP_HOST']);
        }

        if ($host === '') {
            $host = (string)($server['SERVER_ADDR'] ?? '127.0.0.1');
        }

        $safeRealmId = max(1, $realmId);
        $filename = ($safeRealmId === 1) ? 'realmlist.wtf' : ('realmlist-' . $safeRealmId . '.wtf');

        return array(
            'realmId' => $realmId,
            'filename' => $filename,
            'contentType' => 'text/plain; charset=UTF-8',
            'contentDisposition' => 'attachment; filename="' . $filename . '"',
            'body' => '# autogenerated by website' . "\r\n" . 'set realmlist ' . $host . "\r\n",
            'realmCapabilities' => $realmCapabilities,
        );
    }
}

if (!function_exists('spp_server_emit_realmlist_endpoint')) {
    function spp_server_emit_realmlist_endpoint(array $args = array()): void
    {
        $state = spp_server_realmlist_endpoint_state($args);

        header('Content-Type: ' . $state['contentType']);
        header('Content-Disposition: ' . $state['contentDisposition']);

        echo $state['body'];
        exit;
    }
}
