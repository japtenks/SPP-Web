<?php

if (!function_exists('spp_account_userlist_load_page_state')) {
    function spp_account_userlist_load_page_state(array $ctx = array()): array
    {
        $get = $ctx['get'] ?? $_GET;
        $p = isset($ctx['page']) ? (int)$ctx['page'] : (isset($GLOBALS['p']) ? (int)$GLOBALS['p'] : 1);
        $auth = $ctx['auth'] ?? ($GLOBALS['auth'] ?? null);
        $realmDbMap = $ctx['realmDbMap'] ?? ($GLOBALS['realmDbMap'] ?? array());

        $pathwayInfo = $GLOBALS['pathway_info'] ?? array();
        $state = array(
            '__stop' => false,
            'items' => array(),
            'itemnum' => 0,
            'pages_str' => '',
            'profile' => null,
            'allgroups' => array(),
            'txt' => array(),
            'oldInactiveTime' => 3600 * 24 * 7,
            'activeLetter' => isset($get['char']) && strlen((string)$get['char']) === 1 ? strtolower((string)$get['char']) : '',
        );

        if ((int)($get['id'] ?? 0) > 0) {
            if (empty($get['action'])) {
                $profile = $auth ? $auth->getprofile((int)$get['id']) : null;
                $realmPdo = spp_get_pdo('realmd', function_exists('spp_current_realm_id') ? spp_current_realm_id(is_array($realmDbMap) ? $realmDbMap : array()) : spp_resolve_realm_id($realmDbMap));
                $allgroups = $realmPdo->query("SELECT g_id, g_title FROM website_account_groups")->fetchAll(PDO::FETCH_KEY_PAIR);

                $pathwayInfo[] = array('title' => 'Member Management', 'link' => $GLOBALS['com_links']['sub_members'] ?? '');
                $pathwayInfo[] = array('title' => (string)($profile['username'] ?? ''), 'link' => '');

                $txt = array(
                    'yearlist' => "\n",
                    'monthlist' => "\n",
                    'daylist' => "\n",
                );

                for ($i = 1; $i <= 31; $i++) {
                    $txt['daylist'] .= "<option value='$i'" . ($i == ($profile['bd_day'] ?? null) ? ' selected' : '') . "> $i </option>\n";
                }
                for ($i = 1; $i <= 12; $i++) {
                    $txt['monthlist'] .= "<option value='$i'" . ($i == ($profile['bd_month'] ?? null) ? ' selected' : '') . "> $i </option>\n";
                }
                for ($i = 1950; $i <= (int)date('Y'); $i++) {
                    $txt['yearlist'] .= "<option value='$i'" . ($i == ($profile['bd_year'] ?? null) ? ' selected' : '') . "> $i </option>\n";
                }
                if (is_array($profile)) {
                    $profile['signature'] = str_replace('<br />', '', (string)($profile['signature'] ?? ''));
                }

                $state['profile'] = $profile;
                $state['allgroups'] = $allgroups;
                $state['txt'] = $txt;
            }
        } else {
            $pathwayInfo[] = array('title' => 'User List', 'link' => '');

            $filterParams = array();
            $filters = array(
                "LOWER(account.`username`) NOT LIKE 'rndbot%'",
                "(website_accounts.hideprofile IS NULL OR website_accounts.hideprofile = 0)",
            );

            if (!empty($get['char']) && preg_match('/[a-z]/', (string)$get['char'])) {
                $filters[] = "account.`username` LIKE ?";
                $filterParams[] = (string)$get['char'] . '%';
            } elseif (($get['char'] ?? '') == 1) {
                $filters[] = "account.`username` REGEXP '^[^A-Za-z]'";
            }

            $filter = 'WHERE ' . implode(' AND ', $filters);
            $itemsPerPage = (int)spp_config_generic('users_per_page', 25);
            $realmPdo = spp_get_pdo('realmd', function_exists('spp_current_realm_id') ? spp_current_realm_id(is_array($realmDbMap) ? $realmDbMap : array()) : spp_resolve_realm_id($realmDbMap));

            $stmtCount = $realmPdo->prepare("
                SELECT count(*)
                FROM account
                LEFT JOIN website_accounts ON account.id=website_accounts.account_id
                $filter");
            $stmtCount->execute($filterParams);
            $itemnum = (int)$stmtCount->fetchColumn();

            $pnum = (int)ceil($itemnum / max(1, $itemsPerPage));
            $pagesStr = default_paginate($pnum, $p, 'index.php?n=account&sub=userlist&char=' . ($get['char'] ?? ''));
            $limitStart = (int)(($p - 1) * $itemsPerPage);

            $stmtItems = $realmPdo->prepare("
                SELECT * FROM account
                LEFT JOIN website_accounts ON account.id=website_accounts.account_id
                $filter
                ORDER BY username
                LIMIT $limitStart,$itemsPerPage");
            $stmtItems->execute($filterParams);

            $state['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC) ?: array();
            $state['itemnum'] = $itemnum;
            $state['pages_str'] = $pagesStr;
        }

        $GLOBALS['pathway_info'] = $pathwayInfo;

        return $state;
    }
}
