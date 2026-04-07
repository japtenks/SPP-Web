<?php

if (!function_exists('spp_server_load_connect_page_state')) {
    function spp_server_load_connect_page_state(array $args = array()): array
    {
        $realmMap = (array)($args['realm_map'] ?? ($GLOBALS['realmDbMap'] ?? array()));
        $user = (array)($args['user'] ?? ($GLOBALS['user'] ?? array()));
        $server = (array)($args['server'] ?? $_SERVER);

        $realmId = 1;
        if (!empty($realmMap)) {
            $realmId = (int)spp_resolve_realm_id($realmMap);
        }
        if ($realmId <= 0) {
            $realmId = 1;
        }

        $realmName = 'This Server';
        $realmlistHost = '';

        try {
            $realmPdo = spp_get_pdo('realmd', $realmId);
            $realmStmt = $realmPdo->prepare('SELECT `name`, `address` FROM `realmlist` WHERE `id` = ? LIMIT 1');
            $realmStmt->execute(array($realmId));
            $realmRow = $realmStmt->fetch(PDO::FETCH_ASSOC);

            if (!empty($realmRow['name'])) {
                $realmName = (string)$realmRow['name'];
            }
            if (!empty($realmRow['address'])) {
                $realmlistHost = trim((string)$realmRow['address']);
            }
        } catch (Throwable $e) {
            $realmName = 'This Server';
        }

        if ($realmlistHost === '' && !empty($server['HTTP_HOST'])) {
            $realmlistHost = preg_replace('/:\d+$/', '', (string)$server['HTTP_HOST']);
        }
        if ($realmlistHost === '') {
            $realmlistHost = (string)($server['SERVER_ADDR'] ?? '127.0.0.1');
        }

        return array(
            'realmId' => $realmId,
            'connectRealmName' => $realmName,
            'connectRealmlistHost' => $realmlistHost,
            'createAccountUrl' => spp_route_url('account', 'register', array(), false),
            'downloadRealmlistUrl' => 'index.php?n=server&sub=realmlist&nobody=1&realm=' . $realmId,
            'isLoggedIn' => !empty($user['id']) && (int)$user['id'] > 0,
        );
    }
}
