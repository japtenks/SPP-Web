<?php

require_once __DIR__ . '/chars-helpers.php';

if (!function_exists('spp_chars_load_page_state')) {
    function spp_chars_load_page_state(array $args): array
    {
        $realmMap = (array)($args['realm_map'] ?? []);
        $get = (array)($args['get'] ?? $_GET);
        $page = isset($args['page']) ? max(1, (int)$args['page']) : (isset($get['p']) ? max(1, (int)$get['p']) : 1);

        if (empty($realmMap)) {
            die('Realm DB map not loaded');
        }

        $requestedRealmId = isset($get['realm']) ? (int)$get['realm'] : 0;
        $realmId = spp_resolve_realm_id($realmMap, $requestedRealmId > 0 ? $requestedRealmId : null);
        $armoryRealm = spp_get_armory_realm_name($realmId) ?? '';

        $itemsPerPage = isset($get['per_page']) ? max(1, (int)$get['per_page']) : 25;
        $search = trim((string)($get['search'] ?? ''));
        $includeBots = !isset($get['show_bots']) || (string)$get['show_bots'] === '1';
        $onlineOnly = isset($get['online']) && (string)$get['online'] === '1';
        $factionFilter = strtolower(trim((string)($get['faction'] ?? 'all')));
        if (!in_array($factionFilter, array('all', 'alliance', 'horde'), true)) {
            $factionFilter = 'all';
        }

        $searchTerms = spp_server_chars_parse_search($search);
        $baseWhere = array();
        $_realmdDb = $realmMap[(int)$realmId]['realmd'] ?? 'classicrealmd';
        $baseWhere[] = $includeBots ? 'c.account >= 1' : "c.account NOT IN (SELECT id FROM `{$_realmdDb}`.`account` WHERE LOWER(username) LIKE 'rndbot%')";
        $baseWhere[] = '(xp > 0 OR level > 1)';
        if ($onlineOnly) {
            $baseWhere[] = 'online = 1';
        }
        $baseWhereSql = implode(' AND ', $baseWhere);

        $charPdo = spp_get_pdo('chars', $realmId);
        $rawCharacters = $charPdo->query("
          SELECT c.guid, c.account, c.name, c.race, c.class, c.gender, c.level, c.zone, c.online,
                 g.guildid AS guild_id, g.name AS guild_name,
                 IF(LOWER(a.username) LIKE 'rndbot%', 1, 0) AS is_bot
          FROM characters c
          INNER JOIN `{$_realmdDb}`.`account` a ON a.id = c.account
          LEFT JOIN guild_member gm ON c.guid = gm.guid
          LEFT JOIN guild g ON gm.guildid = g.guildid
          WHERE {$baseWhereSql}
          ORDER BY c.level DESC, c.name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $classNames = array(
            1 => 'Warrior', 2 => 'Paladin', 3 => 'Hunter', 4 => 'Rogue', 5 => 'Priest',
            6 => 'Death Knight', 7 => 'Shaman', 8 => 'Mage', 9 => 'Warlock', 11 => 'Druid',
        );
        $raceNames = array(
            1 => 'Human', 2 => 'Orc', 3 => 'Dwarf', 4 => 'Night Elf', 5 => 'Undead',
            6 => 'Tauren', 7 => 'Gnome', 8 => 'Troll', 10 => 'Blood Elf', 11 => 'Draenei',
        );
        $allianceRaces = array(1, 3, 4, 7, 11, 22, 25, 29);
        $hordeRaces = array(2, 5, 6, 8, 10, 9, 26, 27, 28);

        global $MANG;
        if (!isset($MANG)) {
            $MANG = new Mangos();
        }

        $filteredCharacters = array();
        foreach ($rawCharacters as $item) {
            $raceId = (int)($item['race'] ?? 0);
            $location = $MANG->get_zone_name($item['zone']);
            $faction = in_array($raceId, $allianceRaces, true) ? 'Alliance' : 'Horde';
            $className = $classNames[(int)($item['class'] ?? 0)] ?? 'Unknown';
            $raceName = $raceNames[$raceId] ?? 'Unknown';
            $guildName = $item['guild_name'] ?? '';
            $levelText = (string)$item['level'];
            $zoneText = $location . ' ' . (string)$item['zone'];

            $genericHaystack = implode(' ', array(
                $item['name'],
                $guildName,
                $className,
                $raceName,
                $faction,
                $levelText,
                $zoneText,
            ));

            if (!spp_server_chars_search_terms_match($searchTerms['name'], $item['name'])) continue;
            if (!spp_server_chars_search_terms_match($searchTerms['guild'], $guildName)) continue;
            if (!spp_server_chars_search_terms_match($searchTerms['zone'], $zoneText)) continue;
            if (!spp_server_chars_search_terms_match($searchTerms['class'], $className)) continue;
            if (!spp_server_chars_search_terms_match($searchTerms['race'], $raceName)) continue;
            if (!spp_server_chars_search_terms_match($searchTerms['faction'], $faction)) continue;
            if (!spp_server_chars_search_terms_match($searchTerms['level'], $levelText)) continue;
            if (!spp_server_chars_search_terms_match($searchTerms['generic'], $genericHaystack)) continue;
            if ($factionFilter === 'alliance' && !in_array($raceId, $allianceRaces, true)) continue;
            if ($factionFilter === 'horde' && !in_array($raceId, $hordeRaces, true)) continue;

            $item['guild_name'] = $guildName;
            $item['location_name'] = $location;
            $item['faction_name'] = $faction;
            $filteredCharacters[] = $item;
        }

        $count = count($filteredCharacters);
        $filteredBotCount = 0;
        foreach ($filteredCharacters as $filteredCharacter) {
            if ((int)$filteredCharacter['is_bot']) {
                $filteredBotCount++;
            }
        }

        $pnum = max(1, (int)ceil($count / $itemsPerPage));
        if ($page > $pnum && $count > 0) {
            header('Location: ' . spp_server_chars_page_url(array(
                'realm' => $realmId,
                'search' => $search,
                'show_bots' => $includeBots ? '1' : '0',
                'online' => $onlineOnly ? '1' : '0',
                'faction' => $factionFilter,
                'p' => 1,
                'per_page' => $itemsPerPage,
            )));
            exit;
        }
        if ($page < 1) {
            $page = 1;
        }
        $offset = ($page - 1) * $itemsPerPage;
        $characterRows = array_slice($filteredCharacters, $offset, $itemsPerPage);

        return array(
            'realm_id' => $realmId,
            'armory_realm' => $armoryRealm,
            'p' => $page,
            'items_per_page' => $itemsPerPage,
            'search' => $search,
            'include_bots' => $includeBots,
            'online_only' => $onlineOnly,
            'faction_filter' => $factionFilter,
            'search_terms' => $searchTerms,
            'class_names' => $classNames,
            'race_names' => $raceNames,
            'alliance_races' => $allianceRaces,
            'horde_races' => $hordeRaces,
            'characters' => $characterRows,
            'character_rows' => $characterRows,
            'characterRows' => $characterRows,
            'count' => $count,
            'pnum' => $pnum,
            'filtered_bot_count' => $filteredBotCount,
            'realmId' => $realmId,
            'armoryRealm' => $armoryRealm,
            'includeBots' => $includeBots,
            'onlineOnly' => $onlineOnly,
            'factionFilter' => $factionFilter,
            'searchTerms' => $searchTerms,
            'classNames' => $classNames,
            'raceNames' => $raceNames,
            'allianceRaces' => $allianceRaces,
            'hordeRaces' => $hordeRaces,
            'filteredBotCount' => $filteredBotCount,
            'pathway_info' => array(
                array('title' => 'Characters', 'link' => ''),
            ),
        );
    }
}
