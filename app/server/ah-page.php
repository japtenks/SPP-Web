<?php

require_once __DIR__ . '/realm-capabilities.php';

if (!function_exists('spp_server_ah_quality_options')) {
    function spp_server_ah_quality_options(): array
    {
        return array(
            -1 => 'Any Quality',
            0 => 'Poor',
            1 => 'Common',
            2 => 'Uncommon',
            3 => 'Rare',
            4 => 'Epic',
        );
    }
}

if (!function_exists('spp_server_ah_item_class_options')) {
    function spp_server_ah_item_class_options(): array
    {
        return array(
            -1 => 'Any Type',
            0 => 'Consumable',
            1 => 'Container',
            2 => 'Weapon',
            3 => 'Gem',
            4 => 'Armor',
            5 => 'Reagent',
            6 => 'Projectile',
            7 => 'Trade Goods',
            8 => 'Generic',
            9 => 'Recipe',
            10 => 'Money',
            11 => 'Quiver',
            12 => 'Quest Item',
            13 => 'Key',
            14 => 'Permanent',
            15 => 'Miscellaneous',
        );
    }
}

if (!function_exists('spp_server_ah_sort_columns')) {
    function spp_server_ah_sort_columns(array $mapping = array()): array
    {
        $quantitySort = (string)($mapping['quantity'] ?? 'a.item_count');
        $timeSort = (string)($mapping['time'] ?? 'a.time');
        $bidSort = (string)($mapping['bid'] ?? '(CASE WHEN a.lastbid > 0 THEN a.lastbid ELSE a.startbid END)');
        $buyoutSort = (string)($mapping['buyout'] ?? 'a.buyoutprice');

        return array(
            'type' => 'i.class',
            'item' => 'i.name',
            'qty' => $quantitySort,
            'time' => $timeSort,
            'bid' => $bidSort,
            'buyout' => $buyoutSort,
            'req' => 'i.RequiredLevel',
            'ilvl' => 'i.ItemLevel',
        );
    }
}

if (!function_exists('spp_server_ah_filter_map')) {
    function spp_server_ah_filter_map(): array
    {
        return array(
            'ally' => array('label' => 'Alliance', 'class' => 'faction-alliance', 'houses' => array(6)),
            'horde' => array('label' => 'Horde', 'class' => 'faction-horde', 'houses' => array(7)),
            'black' => array('label' => 'Blackwater', 'class' => 'faction-blackwater', 'houses' => array(1)),
            'all' => array('label' => 'All', 'class' => 'faction-neutral', 'houses' => array(1, 6, 7)),
        );
    }
}

if (!function_exists('spp_server_ah_bind_params')) {
    function spp_server_ah_bind_params(PDOStatement $statement, array $params): void
    {
        foreach ($params as $key => $value) {
            $statement->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
    }
}

if (!function_exists('spp_server_ah_money_parts')) {
    function spp_server_ah_money_parts($value): array
    {
        $value = max(0, (int)$value);

        return array(
            'gold' => (int)floor($value / 10000),
            'silver' => (int)floor(($value % 10000) / 100),
            'copper' => (int)($value % 100),
        );
    }
}

if (!function_exists('spp_server_ah_money_view')) {
    function spp_server_ah_money_view($value): array
    {
        $parts = spp_server_ah_money_parts($value);
        $numericValue = (int)$value;

        return array(
            'is_empty' => $numericValue <= 0,
            'gold' => $parts['gold'],
            'silver' => $parts['silver'],
            'copper' => $parts['copper'],
        );
    }
}

if (!function_exists('spp_server_ah_time_left_label')) {
    function spp_server_ah_time_left_label($expiresAt, ?int $currentTime = null): string
    {
        $expiresAt = (int)$expiresAt;
        $currentTime = $currentTime ?? time();
        $secondsLeft = $expiresAt - $currentTime;

        if ($secondsLeft <= 0) {
            return 'Expired';
        }

        $hours = (int)floor($secondsLeft / 3600);
        $minutes = (int)floor(($secondsLeft % 3600) / 60);
        return $hours > 0 ? ($hours . 'h ' . $minutes . 'm') : ($minutes . 'm');
    }
}

if (!function_exists('spp_server_ah_base_params')) {
    function spp_server_ah_base_params(array $state): array
    {
        $params = array(
            'realm' => (int)($state['realmId'] ?? 1),
            'filter' => (string)($state['filter'] ?? 'all'),
        );

        if (!empty($state['search'])) {
            $params['search'] = (string)$state['search'];
        }
        if ((int)($state['qualityFilter'] ?? -1) >= 0) {
            $params['quality'] = (int)$state['qualityFilter'];
        }
        if ((int)($state['itemClassFilter'] ?? -1) >= 0) {
            $params['item_class'] = (int)$state['itemClassFilter'];
        }
        if (($state['minReqLevel'] ?? null) !== null) {
            $params['min_level'] = (int)$state['minReqLevel'];
        }
        if (($state['maxReqLevel'] ?? null) !== null) {
            $params['max_level'] = (int)$state['maxReqLevel'];
        }

        return $params;
    }
}

if (!function_exists('spp_server_ah_route_url')) {
    function spp_server_ah_route_url(array $params = array()): string
    {
        return 'index.php?' . http_build_query(array('n' => 'server', 'sub' => 'ah') + $params);
    }
}

if (!function_exists('spp_server_ah_sort_link')) {
    function spp_server_ah_sort_link(string $key, string $label, array $state): string
    {
        $currentSort = (string)($state['sort'] ?? 'time');
        $currentDir = (string)($state['dir'] ?? 'desc');
        $nextDir = ($currentSort === $key && $currentDir === 'asc') ? 'desc' : 'asc';
        $params = spp_server_ah_base_params($state);
        $params['sort'] = $key;
        $params['dir'] = $nextDir;

        if ((int)($state['page'] ?? 1) > 1) {
            $params['p'] = (int)$state['page'];
        }

        $arrow = '';
        if ($currentSort === $key) {
            $arrow = $currentDir === 'asc' ? ' &#9650;' : ' &#9660;';
        }

        $url = htmlspecialchars(spp_server_ah_route_url($params), ENT_QUOTES, 'UTF-8');
        $text = htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . $arrow;
        return '<a href="' . $url . '">' . $text . '</a>';
    }
}

if (!function_exists('spp_server_ah_load_page_state')) {
    function spp_server_ah_load_page_state(array $args = array()): array
    {
        $realmMap = (array)($args['realm_map'] ?? ($GLOBALS['realmDbMap'] ?? array()));
        $get = (array)($args['get'] ?? $_GET);
        $limitConfig = (int)spp_config_generic('ahitems_per_page', 100);
        $requestedRealmId = isset($get['realm']) ? (int)$get['realm'] : 0;
        $realmId = !empty($realmMap)
            ? (int)spp_resolve_realm_id($realmMap, $requestedRealmId > 0 ? $requestedRealmId : null)
            : max(1, $requestedRealmId);
        if ($realmId <= 0) {
            $realmId = 1;
        }

        $realmCapabilities = spp_realm_capabilities($realmMap, $realmId);

        $filterMap = spp_server_ah_filter_map();
        $filter = strtolower(trim((string)($get['filter'] ?? 'all')));
        if (!isset($filterMap[$filter])) {
            $filter = 'all';
        }

        $sort = strtolower(trim((string)($get['sort'] ?? 'time')));

        $dir = strtolower(trim((string)($get['dir'] ?? 'desc')));
        if (!in_array($dir, array('asc', 'desc'), true)) {
            $dir = 'desc';
        }

        $page = isset($get['p']) ? (int)$get['p'] : (isset($get['pid']) ? (int)$get['pid'] : 1);
        if ($page < 1) {
            $page = 1;
        }

        $limit = $limitConfig > 0 ? $limitConfig : 100;
        $offset = ($page - 1) * $limit;
        $search = trim((string)($get['search'] ?? ''));
        $qualityOptions = spp_server_ah_quality_options();
        $qualityFilter = isset($get['quality']) ? (int)$get['quality'] : -1;
        if (!array_key_exists($qualityFilter, $qualityOptions)) {
            $qualityFilter = -1;
        }

        $itemClassOptions = spp_server_ah_item_class_options();
        $itemClassFilter = isset($get['item_class']) ? (int)$get['item_class'] : -1;
        if (!array_key_exists($itemClassFilter, $itemClassOptions)) {
            $itemClassFilter = -1;
        }

        $minReqLevel = isset($get['min_level']) && $get['min_level'] !== '' ? max(0, (int)$get['min_level']) : null;
        $maxReqLevel = isset($get['max_level']) && $get['max_level'] !== '' ? max(0, (int)$get['max_level']) : null;
        $currentTime = time();

        $state = array(
            'realmId' => $realmId,
            'realmName' => (string)(function_exists('spp_get_armory_realm_name') ? (spp_get_armory_realm_name($realmId) ?? '') : ''),
            'filter' => $filter,
            'sort' => $sort,
            'dir' => $dir,
            'page' => $page,
            'limit' => $limit,
            'search' => $search,
            'qualityFilter' => $qualityFilter,
            'qualityOptions' => $qualityOptions,
            'itemClassFilter' => $itemClassFilter,
            'itemClassOptions' => $itemClassOptions,
            'minReqLevel' => $minReqLevel,
            'maxReqLevel' => $maxReqLevel,
            'useItemsiteUrl' => 'index.php?n=server&sub=item&realm=' . $realmId . '&item=',
            'supportsItemDetail' => !empty($realmCapabilities['supports_item_detail']),
            'ahFilterLinks' => array(),
            'ah_entry' => array(),
            'total' => 0,
            'numofpgs' => 1,
            'baseUrl' => '',
            'pageError' => '',
            'iconPath' => spp_modern_image_url('auction-house'),
            'pathway_info' => array(array('title' => 'Auction House', 'link' => '')),
            'realmCapabilities' => $realmCapabilities,
        );

        if (empty($realmCapabilities['supports_auction'])) {
            $state['pageError'] = 'Auction House data is not available for the selected realm.';
        } else {
            $dbChars = (string)$realmMap[$realmId]['chars'];
            $dbWorld = (string)$realmMap[$realmId]['world'];
            $charsPdo = spp_get_pdo('chars', $realmId);
            $worldPdo = spp_get_pdo('world', $realmId);
            $auctionHouseIdColumn = spp_realm_capability_pick_column($charsPdo, 'auction', array('houseid', 'house_id'));
            $auctionItemIdColumn = spp_realm_capability_pick_column($charsPdo, 'auction', array('item_template', 'item_id'));
            $auctionItemGuidColumn = spp_realm_capability_pick_column($charsPdo, 'auction', array('itemguid', 'item_guid'));
            $auctionItemCountColumn = spp_realm_capability_pick_column($charsPdo, 'auction', array('item_count'));
            $auctionBuyoutColumn = spp_realm_capability_pick_column($charsPdo, 'auction', array('buyoutprice', 'buyout_price'));
            $auctionLastBidColumn = spp_realm_capability_pick_column($charsPdo, 'auction', array('lastbid', 'last_bid'));
            $auctionStartBidColumn = spp_realm_capability_pick_column($charsPdo, 'auction', array('startbid', 'start_bid'));
            $auctionExpireColumn = spp_realm_capability_pick_column($charsPdo, 'auction', array('time', 'expire_time'));
            $worldQualityColumn = spp_realm_capability_pick_column($worldPdo, 'item_template', array('Quality', 'quality'), 'Quality');
            $worldRequiredLevelColumn = spp_realm_capability_pick_column($worldPdo, 'item_template', array('RequiredLevel', 'required_level'), 'RequiredLevel');
            $worldItemLevelColumn = spp_realm_capability_pick_column($worldPdo, 'item_template', array('ItemLevel', 'item_level'), 'ItemLevel');
            $worldAllowableClassColumn = spp_realm_capability_pick_column($worldPdo, 'item_template', array('AllowableClass', 'allowable_class'), 'AllowableClass');
            $worldInventoryTypeColumn = spp_realm_capability_pick_column($worldPdo, 'item_template', array('InventoryType', 'inventory_type'), 'InventoryType');
            $quantitySql = $auctionItemCountColumn !== null
                ? 'a.`' . $auctionItemCountColumn . '`'
                : 'COALESCE(ii.`count`, 1)';
            $auctionJoinSql = ($auctionItemCountColumn === null && $auctionItemGuidColumn !== null)
                ? 'LEFT JOIN `item_instance` AS ii ON ii.`guid` = a.`' . $auctionItemGuidColumn . '`'
                : '';
            $sortColumns = spp_server_ah_sort_columns(array(
                'quantity' => $quantitySql,
                'time' => 'a.`' . $auctionExpireColumn . '`',
                'bid' => '(CASE WHEN a.`' . $auctionLastBidColumn . '` > 0 THEN a.`' . $auctionLastBidColumn . '` ELSE a.`' . $auctionStartBidColumn . '` END)',
                'buyout' => 'a.`' . $auctionBuyoutColumn . '`',
                'req' => 'i.`' . $worldRequiredLevelColumn . '`',
                'ilvl' => 'i.`' . $worldItemLevelColumn . '`',
            ));
            if (!isset($sortColumns[$sort])) {
                $sort = 'time';
            }
            $params = array();
            $whereParts = array();
            $whereParts[] = 'a.`' . $auctionHouseIdColumn . '` IN (' . implode(',', array_map('intval', (array)$filterMap[$filter]['houses'])) . ')';

            if ($search !== '') {
                $whereParts[] = 'i.name LIKE :search';
                $params[':search'] = '%' . $search . '%';
            }
            if ($qualityFilter >= 0) {
                $whereParts[] = 'i.`' . $worldQualityColumn . '` = :quality';
                $params[':quality'] = $qualityFilter;
            }
            if ($itemClassFilter >= 0) {
                $whereParts[] = 'i.class = :item_class';
                $params[':item_class'] = $itemClassFilter;
            }
            if ($minReqLevel !== null) {
                $whereParts[] = 'i.`' . $worldRequiredLevelColumn . '` >= :min_level';
                $params[':min_level'] = $minReqLevel;
            }
            if ($maxReqLevel !== null) {
                $whereParts[] = 'i.`' . $worldRequiredLevelColumn . '` <= :max_level';
                $params[':max_level'] = $maxReqLevel;
            }

            $whereClause = 'WHERE ' . implode(' AND ', $whereParts) . ' AND a.`' . $auctionExpireColumn . '` > UNIX_TIMESTAMP(NOW())';
            $orderBy = 'ORDER BY ' . $sortColumns[$sort] . ' ' . strtoupper($dir);

            try {
                $sql = "
SELECT
  a.id,
  a.`{$auctionHouseIdColumn}` AS houseid,
  a.`{$auctionItemIdColumn}` AS item_template,
  {$quantitySql} AS quantity,
  a.`{$auctionBuyoutColumn}` AS buyout,
  CASE WHEN a.`{$auctionLastBidColumn}` > 0 THEN a.`{$auctionLastBidColumn}` ELSE a.`{$auctionStartBidColumn}` END AS currentbid,
  a.`{$auctionExpireColumn}` AS time,
  i.class,
  i.subclass,
  i.`{$worldInventoryTypeColumn}` AS InventoryType,
  i.`{$worldQualityColumn}` AS quality,
  i.`{$worldRequiredLevelColumn}` AS RequiredLevel,
  i.`{$worldItemLevelColumn}` AS ItemLevel,
  i.`{$worldAllowableClassColumn}` AS AllowableClass,
  i.name AS itemname
FROM `{$dbChars}`.`auction` AS a
LEFT JOIN `{$dbWorld}`.`item_template` AS i ON i.entry = a.`{$auctionItemIdColumn}`
{$auctionJoinSql}
{$whereClause}
{$orderBy}
LIMIT :limit OFFSET :offset";
                $statement = $charsPdo->prepare($sql);
                spp_server_ah_bind_params($statement, $params);
                $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
                $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
                $statement->execute();
                $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

                $countSql = "
SELECT COUNT(*)
FROM `{$dbChars}`.`auction` AS a
LEFT JOIN `{$dbWorld}`.`item_template` AS i ON i.entry = a.`{$auctionItemIdColumn}`
{$auctionJoinSql}
{$whereClause}";
                $countStatement = $charsPdo->prepare($countSql);
                spp_server_ah_bind_params($countStatement, $params);
                $countStatement->execute();
                $state['total'] = (int)$countStatement->fetchColumn();
                $state['numofpgs'] = max(1, (int)ceil($state['total'] / $limit));

                foreach ($rows as $row) {
                    $itemId = (int)($row['item_template'] ?? 0);
                    $quality = (int)($row['quality'] ?? 0);
                    $state['ah_entry'][] = array(
                        'itemname' => (string)($row['itemname'] ?? 'Unknown Item'),
                        'quantity' => (int)($row['quantity'] ?? 0),
                        'required_level' => (int)($row['RequiredLevel'] ?? 0),
                        'item_level' => (int)($row['ItemLevel'] ?? 0),
                        'quality_class' => 'iqual' . $quality,
                        'item_class_label' => (string)($itemClassOptions[(int)($row['class'] ?? -1)] ?? 'Unknown'),
                        'current_bid' => spp_server_ah_money_view($row['currentbid'] ?? 0),
                        'buyout' => spp_server_ah_money_view($row['buyout'] ?? 0),
                        'time_left_label' => spp_server_ah_time_left_label($row['time'] ?? 0, $currentTime),
                        'is_expired' => ((int)($row['time'] ?? 0) - $currentTime) <= 0,
                        'item_url' => !empty($realmCapabilities['supports_item_detail']) ? 'index.php?n=server&sub=item&realm=' . $realmId . '&item=' . $itemId : '',
                        'tooltip_item_id' => $itemId,
                        'tooltip_realm_id' => $realmId,
                    );
                }
            } catch (Throwable $throwable) {
                $state['pageError'] = 'Unable to load Auction House data right now.';
            }
        }

        $baseParams = spp_server_ah_base_params($state);
        $state['baseUrl'] = spp_server_ah_route_url($baseParams);

        foreach ($filterMap as $filterKey => $filterConfig) {
            $filterParams = $baseParams;
            $filterParams['filter'] = $filterKey;
            $state['ahFilterLinks'][$filterKey] = array(
                'label' => $filterConfig['label'],
                'class' => $filterConfig['class'],
                'active' => $filterKey === $filter,
                'url' => spp_server_ah_route_url($filterParams),
            );
        }

        return $state;
    }
}
