<?php

if (!function_exists('spp_frontpage_fetch_realm_rows')) {
    function spp_frontpage_fetch_realm_rows(PDO $realmPdo): array
    {
        try {
            return $realmPdo->query("SELECT * FROM `realmlist` ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC) ?: array();
        } catch (Throwable $e) {
            error_log('[frontpage] Failed loading realmlist: ' . $e->getMessage());
            return array();
        }
    }
}

if (!function_exists('spp_frontpage_server_stats_enabled')) {
    function spp_frontpage_server_stats_enabled($config): bool
    {
        return (bool)($config && (int)$config->components->right_section->server_information);
    }
}

if (!function_exists('spp_frontpage_build_server_record')) {
    function spp_frontpage_build_server_record(array $realmRow, PDO $realmPdo, array $realmDbMap, $config): ?array
    {
        if (!spp_frontpage_server_stats_enabled($config)) {
            return null;
        }

        $realmId = (int)($realmRow['id'] ?? 0);
        if ($realmId <= 0) {
            return null;
        }

        $stmtData = $realmPdo->prepare("SELECT address, port, timezone, icon, name FROM realmlist WHERE id = ? LIMIT 1");
        $stmtData->execute(array($realmId));
        $data = $stmtData->fetch(PDO::FETCH_ASSOC);
        if (!$data) {
            return null;
        }

        $server = array(
            'name' => $data['name'],
        );
        $changeRealmParams = array('changerealm_to' => $realmId);

        if ((int)$config->components->server_information->realm_status) {
            $checkAddress = (int)$config->generic->use_local_ip_port_test ? '127.0.0.1' : $data['address'];
            $server['realm_status'] = check_port_status($checkAddress, $data['port']);
        }

        $_fpRealmdDb = $realmDbMap[$realmId]['realmd'] ?? 'classicrealmd';
        try {
            $charPdo = spp_get_pdo('chars', $realmId);
            if ((int)$config->components->server_information->online) {
                $server['realplayersonline'] = (int)$charPdo->query("SELECT count(1) FROM `characters` WHERE online=1 AND account NOT IN (SELECT id FROM `{$_fpRealmdDb}`.`account` WHERE LOWER(username) LIKE 'rndbot%')")->fetchColumn();
            }
            if ((int)$config->components->server_information->population) {
                $server['population'] = (int)$charPdo->query("SELECT count(1) FROM `characters` WHERE online=1")->fetchColumn();
            }
            if ((int)$config->components->server_information->characters) {
                $server['characters'] = (int)$charPdo->query("SELECT count(1) FROM `characters` WHERE account NOT IN (SELECT id FROM `{$_fpRealmdDb}`.`account` WHERE LOWER(username) LIKE 'rndbot%')")->fetchColumn();
            }
        } catch (PDOException $e) {
            error_log('[frontpage] Failed loading realm char stats: ' . $e->getMessage());
        }

        if ((int)$config->components->left_section->Playermap) {
            $server['playermapurl'] = spp_route_url('server', 'playermap', $changeRealmParams);
        }
        if ((int)$config->components->server_information->server_ip) {
            $server['server_ip'] = $data['address'];
        }
        if ((int)$config->components->server_information->type) {
            $server['type'] = $GLOBALS['realm_type_def'][$data['icon']];
        }
        if ((int)$config->components->server_information->language) {
            $server['language'] = $GLOBALS['realm_timezone_def'][$data['timezone']];
        }
        if ((int)$config->components->server_information->accounts) {
            $server['accounts'] = (int)$realmPdo->query("SELECT count(1) FROM `account` WHERE LOWER(username) NOT LIKE 'rndbot%'")->fetchColumn();
        }
        if ((int)$config->components->server_information->active_accounts) {
            $activeDate = date("Y-m-d H:i:s", strtotime("-2 week")) . " 00:00:00";
            $stmtAA = $realmPdo->prepare("SELECT count(DISTINCT accountId) FROM `account_logons` WHERE `loginTime` > ?");
            $stmtAA->execute(array($activeDate));
            $server['active_accounts'] = (int)$stmtAA->fetchColumn();
            $stmtAL = $realmPdo->prepare("SELECT count(*) FROM `account_logons` WHERE `loginTime` > ?");
            $stmtAL->execute(array($activeDate));
            $server['active_login'] = (int)$stmtAL->fetchColumn();
        }

        $realmConfigKey = 'id_' . $realmId;
        if ((int)$config->components->right_section->server_rates && (string)$config->mangos_conf_external->$realmConfigKey->mangos_world_conf != '') {
            $server['rates'] = getMangosConfig($config->mangos_conf_external->$realmConfigKey->mangos_world_conf);
        }
        $server['moreinfo'] = (int)$config->components->server_information->more_info && (string)$config->mangos_conf_external->$realmConfigKey->mangos_world_conf != '';

        return $server;
    }
}

if (!function_exists('spp_frontpage_build_server_list')) {
    function spp_frontpage_build_server_list(PDO $realmPdo, array $realmDbMap, $config): array
    {
        if (!spp_frontpage_server_stats_enabled($config)) {
            return array();
        }

        $servers = array();
        foreach (spp_frontpage_fetch_realm_rows($realmPdo) as $realmRow) {
            $server = spp_frontpage_build_server_record($realmRow, $realmPdo, $realmDbMap, $config);
            if ($server !== null) {
                $servers[] = $server;
            }
        }

        return $servers;
    }
}

if (!function_exists('spp_frontpage_count_users_on_homepage')) {
    function spp_frontpage_count_users_on_homepage(PDO $realmPdo, $config): int
    {
        if (!$config || !(int)$config->components->right_section->users_on_homepage) {
            return 0;
        }

        return (int)$realmPdo->query("SELECT count(1) FROM `online`")->fetchColumn();
    }
}
