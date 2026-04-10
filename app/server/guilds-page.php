<?php

require_once __DIR__ . '/realm-capabilities.php';

if (!function_exists('spp_guilds_sort_compare')) {
    function spp_guilds_sort_compare(array $left, array $right, $sortBy, $sortDir): int
    {
        $direction = strtoupper((string)$sortDir) === 'ASC' ? 1 : -1;

        switch ($sortBy) {
            case 'guild':
                $comparison = strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
                if ($comparison === 0) {
                    $comparison = ((int)($left['guildid'] ?? 0) <=> (int)($right['guildid'] ?? 0));
                }
                break;
            case 'faction':
                $comparison = strcasecmp((string)($left['faction_name'] ?? ''), (string)($right['faction_name'] ?? ''));
                if ($comparison === 0) {
                    $comparison = strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
                }
                break;
            case 'leader':
                $comparison = strcasecmp((string)($left['leader_name'] ?? ''), (string)($right['leader_name'] ?? ''));
                if ($comparison === 0) {
                    $comparison = strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
                }
                break;
            case 'members':
                $comparison = ((int)($left['member_count'] ?? 0) <=> (int)($right['member_count'] ?? 0));
                if ($comparison === 0) {
                    $comparison = strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
                }
                break;
            case 'avg':
                $comparison = ((float)($left['avg_level'] ?? 0) <=> (float)($right['avg_level'] ?? 0));
                if ($comparison === 0) {
                    $comparison = ((int)($left['member_count'] ?? 0) <=> (int)($right['member_count'] ?? 0));
                }
                break;
            case 'max':
                $comparison = ((int)($left['max_level'] ?? 0) <=> (int)($right['max_level'] ?? 0));
                if ($comparison === 0) {
                    $comparison = ((float)($left['avg_level'] ?? 0) <=> (float)($right['avg_level'] ?? 0));
                }
                break;
            case 'avgilvl':
                $comparison = ((float)($left['avg_item_level'] ?? 0) <=> (float)($right['avg_item_level'] ?? 0));
                if ($comparison === 0) {
                    $comparison = ((int)($left['member_count'] ?? 0) <=> (int)($right['member_count'] ?? 0));
                }
                break;
            case 'maxilvl':
                $comparison = ((float)($left['max_item_level'] ?? 0) <=> (float)($right['max_item_level'] ?? 0));
                if ($comparison === 0) {
                    $comparison = ((float)($left['avg_item_level'] ?? 0) <=> (float)($right['avg_item_level'] ?? 0));
                }
                break;
            default:
                $comparison = ((int)($left['member_count'] ?? 0) <=> (int)($right['member_count'] ?? 0));
                if ($comparison === 0) {
                    $comparison = strcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
                }
                break;
        }

        return $comparison * $direction;
    }
}

if (!function_exists('spp_guilds_sort_url')) {
    function spp_guilds_sort_url(int $realmId, int $perPage, string $search, string $sortBy, string $currentSortBy, string $currentSortDir, bool $showGm = false, string $factionFilter = 'all'): string
    {
        $nextSortDir = ($currentSortBy === $sortBy && strtoupper($currentSortDir) === 'ASC') ? 'DESC' : 'ASC';
        $url = 'index.php?n=server&sub=guilds&realm=' . $realmId
            . '&per_page=' . $perPage
            . '&sort=' . rawurlencode($sortBy)
            . '&dir=' . rawurlencode($nextSortDir)
            . '&faction=' . rawurlencode($factionFilter);

        if ($search !== '') {
            $url .= '&search=' . rawurlencode($search);
        }

        if ($showGm) {
            $url .= '&show_gm=1';
        }

        return $url;
    }
}

if (!function_exists('spp_guilds_build_sort_urls')) {
    function spp_guilds_build_sort_urls(int $realmId, int $itemsPerPage, string $search, string $sortBy, string $sortDir, bool $showGmGuilds, string $factionFilter): array
    {
        $sortUrls = array();
        foreach (array('guild', 'leader', 'members', 'avg', 'max', 'avgilvl', 'maxilvl') as $sortKey) {
            $sortUrls[$sortKey] = spp_guilds_sort_url($realmId, $itemsPerPage, $search, $sortKey, $sortBy, $sortDir, $showGmGuilds, $factionFilter);
        }

        return $sortUrls;
    }
}

if (!function_exists('spp_guilds_prepare_rows')) {
    function spp_guilds_prepare_rows(array $guilds, int $realmId, array $allianceRaces, array $classNames, bool $supportsCharacterDetail): array
    {
        $rows = array();

        foreach ($guilds as $guild) {
            $factionName = in_array((int)($guild['leader_race'] ?? 0), $allianceRaces, true) ? 'Alliance' : 'Horde';
            $factionSlug = strtolower($factionName);
            $leaderClassSlug = strtolower(str_replace(' ', '', $classNames[(int)($guild['leader_class'] ?? 0)] ?? 'unknown'));

            $guild['faction_name'] = $factionName;
            $guild['faction_slug'] = $factionSlug;
            $guild['leader_class_slug'] = $leaderClassSlug;
            $guild['faction_icon_url'] = spp_modern_faction_logo_url($factionSlug);
            $guild['guild_url'] = 'index.php?n=server&sub=guild&guildid=' . (int)($guild['guildid'] ?? 0) . '&realm=' . $realmId;
            $guild['leader_url'] = $supportsCharacterDetail
                ? 'index.php?n=server&sub=character&realm=' . $realmId . '&character=' . urlencode((string)($guild['leader_name'] ?? ''))
                : '';
            $rows[] = $guild;
        }

        return $rows;
    }
}

if (!function_exists('spp_guilds_load_page_state')) {
    function spp_guilds_load_page_state(array $args): array
    {
        $realmMap = (array)($args['realm_map'] ?? []);
        $user = (array)($args['user'] ?? []);
        $get = (array)($args['get'] ?? $_GET);
        $post = (array)($args['post'] ?? $_POST);
        $serverMethod = strtoupper((string)($args['server_method'] ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET')));

        if (empty($realmMap)) {
            die('Realm DB map not loaded');
        }

        $requestedRealmId = isset($get['realm']) ? (int)$get['realm'] : 0;
        $realmId = spp_resolve_realm_id($realmMap, $requestedRealmId > 0 ? $requestedRealmId : null);
        $realmCapabilities = spp_realm_capabilities($realmMap, $realmId);
        $realmWorldDB = $realmMap[$realmId]['world'];
        $classNames = array(
            1 => 'Warrior', 2 => 'Paladin', 3 => 'Hunter', 4 => 'Rogue', 5 => 'Priest',
            6 => 'Death Knight', 7 => 'Shaman', 8 => 'Mage', 9 => 'Warlock', 11 => 'Druid',
        );
        $allianceRaces = array(1, 3, 4, 7, 11, 22, 25, 29);

        $p = isset($get['p']) ? max(1, (int)$get['p']) : 1;
        $itemsPerPage = isset($get['per_page']) ? max(1, (int)$get['per_page']) : 25;
        $search = trim((string)($get['search'] ?? ''));
        $factionFilter = strtolower(trim((string)($get['faction'] ?? 'all')));
        $sortBy = strtolower(trim((string)($get['sort'] ?? 'members')));
        $sortDir = strtoupper(trim((string)($get['dir'] ?? 'DESC')));
        $isGm = (int)($user['gmlevel'] ?? 0) >= 3;
        $showGmGuilds = $isGm && isset($get['show_gm']) && (string)$get['show_gm'] === '1';
        $guildsCsrfToken = spp_csrf_token('guilds_page');
        $guildMotdFeedback = '';
        $guildMotdError = '';

        $allowedSorts = array('guild', 'faction', 'leader', 'members', 'avg', 'max', 'avgilvl', 'maxilvl');
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'members';
        }
        if (!in_array($factionFilter, array('all', 'alliance', 'horde'), true)) {
            $factionFilter = 'all';
        }
        if ($sortDir !== 'ASC' && $sortDir !== 'DESC') {
            $sortDir = 'DESC';
        }

        $charPdo = spp_get_pdo('chars', $realmId);
        $worldPdo = spp_get_pdo('world', $realmId);
        $guildIdColumn = spp_realm_capability_pick_column($charPdo, 'guild', array('guildid', 'guild_id'), 'guildid');
        $guildMemberGuildIdColumn = spp_realm_capability_pick_column($charPdo, 'guild_member', array('guildid', 'guild_id'), $guildIdColumn);
        $leaderGuidColumn = spp_realm_capability_pick_column($charPdo, 'guild', array('leaderguid', 'leader_guid'), 'leaderguid');
        $itemLevelColumn = spp_realm_capability_pick_column($worldPdo, 'item_template', array('ItemLevel', 'item_level'), 'ItemLevel');
        $inventoryItemColumn = spp_realm_capability_pick_column($charPdo, 'character_inventory', array('item_template', 'item_id'), 'item_template');

        if ($serverMethod === 'POST' && isset($post['guilds_form_action']) && $post['guilds_form_action'] === 'save_gm_motds') {
            spp_require_csrf('guilds_page');
            if (!$isGm) {
                $guildMotdError = 'GM permissions are required to update guild MOTDs from this view.';
            } else {
                $submittedMotds = isset($post['motd']) && is_array($post['motd']) ? $post['motd'] : array();
                $updatedGuilds = 0;
                $soapGuilds = 0;
                $sqlFallbackGuilds = 0;
                $updateStmt = $charPdo->prepare('UPDATE guild SET motd=? WHERE `' . $guildIdColumn . '`=?');
                $guildNameStmt = $charPdo->prepare('SELECT name FROM guild WHERE `' . $guildIdColumn . '` = ? LIMIT 1');

                foreach ($submittedMotds as $guildId => $motd) {
                    $guildId = (int)$guildId;
                    if ($guildId <= 0) {
                        continue;
                    }

                    $sanitizedMotd = substr(trim((string)$motd), 0, 128);
                    $guildNameStmt->execute(array($guildId));
                    $guildName = trim((string)$guildNameStmt->fetchColumn());
                    if ($guildName === '') {
                        continue;
                    }

                    $soapCommand = '.guild motd ' . spp_mangos_soap_quote_argument($guildName) . ' ' . spp_mangos_soap_format_trailing_argument($sanitizedMotd);
                    $writeResult = spp_mangos_execute_or_sql_fallback(
                        $realmId,
                        $soapCommand,
                        static function () use ($updateStmt, $sanitizedMotd, $guildId): void {
                            $updateStmt->execute(array($sanitizedMotd, $guildId));
                        },
                        array(
                            'sql_fallback_message' => 'Guild MOTDs were written directly to the characters DB because SOAP was unavailable. This is less safe while the world server is offline.',
                        )
                    );

                    if (empty($writeResult['ok'])) {
                        $guildMotdError = (string)($writeResult['message'] ?? 'A guild MOTD update failed.');
                        break;
                    }

                    if (($writeResult['mode'] ?? '') === 'soap') {
                        $soapGuilds++;
                    } else {
                        $sqlFallbackGuilds++;
                    }

                    $updatedGuilds++;
                }

                if ($guildMotdError === '') {
                    if ($updatedGuilds <= 0) {
                        $guildMotdFeedback = 'No MOTD changes were submitted.';
                    } elseif ($sqlFallbackGuilds === 0) {
                        $guildMotdFeedback = 'Updated MOTDs for ' . $updatedGuilds . ' guild' . ($updatedGuilds === 1 ? '' : 's') . ' via SOAP.';
                    } else {
                        $guildMotdFeedback = 'Updated MOTDs for ' . $updatedGuilds . ' guild' . ($updatedGuilds === 1 ? '' : 's') . '. SOAP was unavailable for ' . $sqlFallbackGuilds . ' guild' . ($sqlFallbackGuilds === 1 ? '' : 's') . ', so direct SQL fallback was used. This is less safe while the world server is offline.';
                    }
                }
            }
        }

        $guilds = $charPdo->query("
          SELECT
            g.`{$guildIdColumn}` AS guildid,
            g.name,
            g.motd,
            leader.guid AS leader_guid,
            leader.name AS leader_name,
            leader.race AS leader_race,
            leader.class AS leader_class,
            leader.account AS leader_account,
            COUNT(gm.guid) AS member_count,
            COALESCE(AVG(c.level), 0) AS avg_level,
            COALESCE(MAX(c.level), 0) AS max_level
          FROM guild g
          LEFT JOIN guild_member gm ON g.`{$guildIdColumn}` = gm.`{$guildMemberGuildIdColumn}`
          LEFT JOIN characters c ON gm.guid = c.guid
          LEFT JOIN characters leader ON g.`{$leaderGuidColumn}` = leader.guid
          GROUP BY g.`{$guildIdColumn}`, g.name, g.motd, leader.guid, leader.name, leader.race, leader.class, leader.account
          ORDER BY member_count DESC, g.name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $guildGearStats = array();
        if (!$showGmGuilds && !empty($realmCapabilities['supports_item_template'])) {
            $guildIds = array_values(array_filter(array_map(static function ($g) {
                return (int)($g['guildid'] ?? 0);
            }, is_array($guilds) ? $guilds : array())));

            if (!empty($guildIds)) {
                $guildIdSql = implode(',', $guildIds);
                try {
                    $gearRows = $charPdo->query("
                      SELECT
                        gm.`{$guildMemberGuildIdColumn}` AS guildid,
                        c.guid,
                        ROUND(AVG(it.`{$itemLevelColumn}`), 1) AS avg_item_level
                      FROM guild_member gm
                      INNER JOIN characters c ON c.guid = gm.guid
                      INNER JOIN character_inventory ci ON ci.guid = c.guid
                      INNER JOIN {$realmWorldDB}.item_template it ON it.entry = ci.`{$inventoryItemColumn}`
                      WHERE gm.`{$guildMemberGuildIdColumn}` IN ({$guildIdSql})
                        AND ci.bag = 0
                        AND ci.slot BETWEEN 0 AND 18
                        AND ci.slot NOT IN (3, 18)
                        AND ci.`{$inventoryItemColumn}` > 0
                      GROUP BY gm.`{$guildMemberGuildIdColumn}`, c.guid
                    ")->fetchAll(PDO::FETCH_ASSOC);

                    if (is_array($gearRows)) {
                        foreach ($gearRows as $gearRow) {
                            $gid = (int)($gearRow['guildid'] ?? 0);
                            $memberAvg = (float)($gearRow['avg_item_level'] ?? 0);
                            if ($gid <= 0 || $memberAvg <= 0) {
                                continue;
                            }
                            if (!isset($guildGearStats[$gid])) {
                                $guildGearStats[$gid] = array('total' => 0.0, 'count' => 0, 'max' => 0.0);
                            }
                            $guildGearStats[$gid]['total'] += $memberAvg;
                            $guildGearStats[$gid]['count']++;
                            if ($memberAvg > $guildGearStats[$gid]['max']) {
                                $guildGearStats[$gid]['max'] = $memberAvg;
                            }
                        }
                    }
                } catch (Throwable $e) {
                    error_log('[guilds] Failed loading guild gear stats: ' . $e->getMessage());
                }
            }
        }

        if (is_array($guilds)) {
            foreach ($guilds as &$guild) {
                $gid = (int)($guild['guildid'] ?? 0);
                $gs = $guildGearStats[$gid] ?? null;
                $guild['avg_item_level'] = (!empty($gs['count'])) ? round($gs['total'] / $gs['count'], 1) : 0;
                $guild['max_item_level'] = (!empty($gs['max'])) ? round($gs['max'], 1) : 0;
                $guild['faction_name'] = in_array((int)($guild['leader_race'] ?? 0), $allianceRaces, true) ? 'Alliance' : 'Horde';
            }
            unset($guild);
        }

        if ($search !== '') {
            $needle = strtolower($search);
            $guilds = array_values(array_filter($guilds, function ($guild) use ($needle, $allianceRaces, $classNames) {
                $factionName = $guild['faction_name'] ?? (in_array((int)($guild['leader_race'] ?? 0), $allianceRaces, true) ? 'Alliance' : 'Horde');
                $leaderClass = $classNames[(int)($guild['leader_class'] ?? 0)] ?? 'Unknown';
                $haystack = strtolower(implode(' ', array(
                    $guild['name'] ?? '',
                    $guild['leader_name'] ?? '',
                    $guild['motd'] ?? '',
                    (string)($guild['member_count'] ?? ''),
                    (string)round((float)($guild['avg_level'] ?? 0)),
                    (string)($guild['max_level'] ?? ''),
                    (string)($guild['avg_item_level'] ?? ''),
                    (string)($guild['max_item_level'] ?? ''),
                    $factionName,
                    $leaderClass,
                )));
                return strpos($haystack, $needle) !== false;
            }));
        }

        if ($factionFilter !== 'all') {
            $guilds = array_values(array_filter($guilds, function ($guild) use ($factionFilter, $allianceRaces) {
                $factionName = strtolower((string)($guild['faction_name'] ?? (in_array((int)($guild['leader_race'] ?? 0), $allianceRaces, true) ? 'Alliance' : 'Horde')));
                return $factionName === $factionFilter;
            }));
        }

        if (is_array($guilds) && !empty($guilds)) {
            usort($guilds, function ($left, $right) use ($sortBy, $sortDir) {
                return spp_guilds_sort_compare($left, $right, $sortBy, $sortDir);
            });
        }

        $count = count($guilds);
        $pnum = max(1, (int)ceil($count / $itemsPerPage));
        if ($p > $pnum) {
            $p = $pnum;
        }
        if ($p < 1) {
            $p = 1;
        }
        $offset = ($p - 1) * $itemsPerPage;
        $guildsPage = spp_guilds_prepare_rows(array_slice($guilds, $offset, $itemsPerPage), $realmId, $allianceRaces, $classNames, !empty($realmCapabilities['supports_character_detail']));
        $resultStart = $count > 0 ? $offset + 1 : 0;
        $resultEnd = min($offset + $itemsPerPage, $count);
        $baseUrl = 'index.php?n=server&sub=guilds&realm=' . (int)$realmId . '&per_page=' . (int)$itemsPerPage;
        if ($search !== '') {
            $baseUrl .= '&search=' . urlencode($search);
        }
        if ($factionFilter !== 'all') {
            $baseUrl .= '&faction=' . urlencode($factionFilter);
        }
        if ($sortBy !== '') {
            $baseUrl .= '&sort=' . urlencode($sortBy) . '&dir=' . urlencode($sortDir);
        }

        $paginationRouteUrl = 'index.php?n=server&sub=guilds';
        if ($showGmGuilds) {
            $paginationRouteUrl .= '&show_gm=1';
        }

        return array(
            'realm_id' => $realmId,
            'realm_capabilities' => $realmCapabilities,
            'realm_world_db' => $realmWorldDB,
            'class_names' => $classNames,
            'alliance_races' => $allianceRaces,
            'p' => $p,
            'items_per_page' => $itemsPerPage,
            'search' => $search,
            'faction_filter' => $factionFilter,
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir,
            'is_gm' => $isGm,
            'show_gm_guilds' => $showGmGuilds,
            'guilds_csrf_token' => $guildsCsrfToken,
            'guild_motd_feedback' => $guildMotdFeedback,
            'guild_motd_error' => $guildMotdError,
            'guilds_page' => $guildsPage,
            'count' => $count,
            'pnum' => $pnum,
            'result_start' => $resultStart,
            'result_end' => $resultEnd,
            'base_url' => $baseUrl,
            'pagination_route_url' => $paginationRouteUrl,
            'sort_urls' => spp_guilds_build_sort_urls($realmId, $itemsPerPage, $search, $sortBy, $sortDir, $showGmGuilds, $factionFilter),
            'realmId' => $realmId,
            'realmCapabilities' => $realmCapabilities,
            'realmWorldDb' => $realmWorldDB,
            'classNames' => $classNames,
            'allianceRaces' => $allianceRaces,
            'itemsPerPage' => $itemsPerPage,
            'factionFilter' => $factionFilter,
            'sortBy' => $sortBy,
            'sortDir' => $sortDir,
            'isGm' => $isGm,
            'showGmGuilds' => $showGmGuilds,
            'guildsCsrfToken' => $guildsCsrfToken,
            'guildMotdFeedback' => $guildMotdFeedback,
            'guildMotdError' => $guildMotdError,
            'guildsPage' => $guildsPage,
            'resultStart' => $resultStart,
            'resultEnd' => $resultEnd,
            'baseUrl' => $baseUrl,
            'sortUrls' => spp_guilds_build_sort_urls($realmId, $itemsPerPage, $search, $sortBy, $sortDir, $showGmGuilds, $factionFilter),
        );
    }
}
