<?php

require_once dirname(__DIR__, 2) . '/components/forum/forum.func.php';

if (!function_exists('spp_server_honor_rank_blurbs')) {
    function spp_server_honor_rank_blurbs(): array
    {
        return array(
            1 => 'The very first PvP rank. Earned by achieving 15 Honorable Kills in a week and rewarded with the faction tabard.',
            2 => 'The first meaningful PvP reward. Unlocks the faction insignia trinket that removes movement impairing effects.',
            3 => 'Grants access to rare-quality cloaks and a permanent 10% discount on goods and repairs from faction vendors.',
            4 => 'Unlocks the blue-quality neckpiece for your faction, marking a clear step up in prestige.',
            5 => 'Grants access to blue-quality bracers tailored to your class archetype, often useful in pre-raid gearing.',
            6 => 'A social milestone rank that opened the officer barracks and heraldry rewards, plus combat consumables for PvP use.',
            7 => 'The first class-specific PvP gear tier. Unlocks rare-quality gloves and boots for each class.',
            8 => 'Adds the rare-quality chest and legs, letting players start completing the 4-piece PvP set look.',
            9 => 'Grants access to faction battle standards, a notable prestige reward in the Classic honor ladder.',
            10 => 'The final upgrade for the rare-quality PvP set. Unlocks helm and shoulders to complete the 6-piece blue set.',
            11 => 'Grants access to epic PvP mounts and the WorldDefense channel, making it a major honor milestone.',
            12 => 'The first epic-quality class PvP set rank. Unlocks gloves, legs, and boots with stronger battleground-ready stats.',
            13 => 'Unlocks the epic helm, chest, and shoulders, completing the full epic PvP armor set.',
            14 => 'The ultimate PvP achievement of Classic WoW. Grants access to the legendary epic-quality PvP weapons and unmatched prestige.',
        );
    }
}

if (!function_exists('spp_server_honor_prepare_rows')) {
    function spp_server_honor_prepare_rows(array $rows, int $realmId, array $allianceRaces, array $classNames, array $raceNames): array
    {
        $mangos = new Mangos();
        $rankBlurbById = spp_server_honor_rank_blurbs();
        $characters = array();

        foreach ($rows as $row) {
            $factionName = in_array((int)($row['race'] ?? 0), $allianceRaces, true) ? 'Alliance' : 'Horde';
            $factionKey = strtolower($factionName);
            $rankId = max(0, min(14, (int)($row['rank_id'] ?? 0)));
            $rankName = $mangos->characterInfoByID['character_rank'][$factionKey][$rankId] ?? ('Rank ' . $rankId);
            $className = $classNames[(int)($row['class'] ?? 0)] ?? 'Unknown';
            $raceName = $raceNames[(int)($row['race'] ?? 0)] ?? 'Unknown';

            $row['faction_name'] = $factionName;
        $row['faction_icon'] = spp_modern_faction_logo_url($factionKey);
            $row['rank_name'] = $rankName;
            $row['rank_icon'] = spp_modern_image_url(sprintf('icons/64x64/pvprank%02d.png', $rankId));
            $row['rank_blurb'] = $rankBlurbById[$rankId] ?? '';
            $row['class_name'] = $className;
            $row['race_name'] = $raceName;
            $row['class_slug'] = strtolower(str_replace(' ', '', $className));
            $row['portrait_url'] = get_character_portrait_path((int)$row['guid'], (int)$row['gender'], (int)$row['race'], (int)$row['class']);
            $row['character_url'] = 'index.php?n=server&sub=character&realm=' . $realmId . '&character=' . urlencode((string)$row['name']);
            $characters[] = $row;
        }

        return $characters;
    }
}

if (!function_exists('spp_server_honor_matches_search')) {
    function spp_server_honor_matches_search(array $row, string $searchNeedle): bool
    {
        if ($searchNeedle === '') {
            return true;
        }

        $haystack = strtolower(implode(' ', array(
            (string)($row['name'] ?? ''),
            (string)($row['class_name'] ?? ''),
            (string)($row['race_name'] ?? ''),
            (string)($row['faction_name'] ?? ''),
            (string)($row['rank_name'] ?? ''),
            (string)($row['level'] ?? ''),
            (string)($row['honorable_kills'] ?? ''),
            (string)($row['dishonorable_kills'] ?? ''),
            (string)round((float)($row['honor_points'] ?? 0)),
        )));

        return strpos($haystack, $searchNeedle) !== false;
    }
}

if (!function_exists('spp_server_honor_load_page_state')) {
    function spp_server_honor_load_page_state(array $args): array
    {
        $realmMap = (array)($args['realm_map'] ?? array());
        $get = (array)($args['get'] ?? $_GET);

        if (empty($realmMap)) {
            die('Realm DB map not loaded');
        }

        $realmId = spp_resolve_realm_id($realmMap);
        $p = isset($get['p']) ? max(1, (int)$get['p']) : 1;
        $itemsPerPage = isset($get['per_page']) ? max(1, (int)$get['per_page']) : 25;
        $search = trim((string)($get['search'] ?? ''));
        $searchNeedle = strtolower($search);
        $factionFilter = strtolower(trim((string)($get['faction'] ?? 'all')));
        if (!in_array($factionFilter, array('all', 'alliance', 'horde'), true)) {
            $factionFilter = 'all';
        }

        $classNames = array(
            1 => 'Warrior', 2 => 'Paladin', 3 => 'Hunter', 4 => 'Rogue', 5 => 'Priest',
            6 => 'Death Knight', 7 => 'Shaman', 8 => 'Mage', 9 => 'Warlock', 11 => 'Druid',
        );
        $raceNames = array(
            1 => 'Human', 2 => 'Orc', 3 => 'Dwarf', 4 => 'Night Elf', 5 => 'Undead',
            6 => 'Tauren', 7 => 'Gnome', 8 => 'Troll', 10 => 'Blood Elf', 11 => 'Draenei',
        );
        $allianceRaces = array(1, 3, 4, 7, 11, 22, 25, 29);

        $charPdo = spp_get_pdo('chars', $realmId);
        $rows = $charPdo->query("
          SELECT
            c.guid,
            c.name,
            c.race,
            c.class,
            c.gender,
            c.level,
            COALESCE(c.stored_honorable_kills, 0) AS honorable_kills,
            COALESCE(c.stored_dishonorable_kills, 0) AS dishonorable_kills,
            COALESCE(c.stored_honor_rating, 0) AS honor_points,
            COALESCE(c.honor_highest_rank, 0) AS rank_id
          FROM characters c
          WHERE COALESCE(c.stored_honorable_kills, 0) > 0
          ORDER BY honor_points DESC, honorable_kills DESC, level DESC, name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $characters = spp_server_honor_prepare_rows(is_array($rows) ? $rows : array(), $realmId, $allianceRaces, $classNames, $raceNames);
        $characters = array_values(array_filter($characters, function (array $row) use ($factionFilter, $searchNeedle, $allianceRaces) {
            if ($factionFilter === 'alliance' && !in_array((int)($row['race'] ?? 0), $allianceRaces, true)) {
                return false;
            }

            if ($factionFilter === 'horde' && in_array((int)($row['race'] ?? 0), $allianceRaces, true)) {
                return false;
            }

            return spp_server_honor_matches_search($row, $searchNeedle);
        }));

        $count = count($characters);
        $pnum = max(1, (int)ceil($count / $itemsPerPage));
        if ($p > $pnum) {
            $p = $pnum;
        }
        if ($p < 1) {
            $p = 1;
        }

        $offset = ($p - 1) * $itemsPerPage;
        $charactersPage = array_slice($characters, $offset, $itemsPerPage);
        $resultStart = $count > 0 ? $offset + 1 : 0;
        $resultEnd = min($offset + $itemsPerPage, $count);

        $paginationRouteUrl = 'index.php?n=server&sub=honor';

        return array(
            'realmId' => $realmId,
            'p' => $p,
            'pnum' => $pnum,
            'itemsPerPage' => $itemsPerPage,
            'search' => $search,
            'factionFilter' => $factionFilter,
            'charactersPage' => $charactersPage,
            'count' => $count,
            'resultStart' => $resultStart,
            'resultEnd' => $resultEnd,
            'pagination_route_url' => $paginationRouteUrl,
        );
    }
}
