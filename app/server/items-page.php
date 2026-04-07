<?php
$serviceRoot = dirname(__DIR__, 2);
require_once($serviceRoot . '/components/server/server.items.helpers.php');

if (!function_exists('spp_item_vault_sort_link')) {
    function spp_item_vault_sort_link($realmId, array $filters, $sortKey, $label)
    {
        $currentSort = (string)($filters['sort'] ?? 'featured');
        $currentDir = strtoupper((string)($filters['dir'] ?? 'DESC'));
        $nextDir = ($currentSort === $sortKey && $currentDir === 'ASC') ? 'DESC' : 'ASC';
        $arrow = '';
        if ($currentSort === $sortKey) {
            $arrow = $currentDir === 'ASC' ? ' â–²' : ' â–¼';
        }

        $url = spp_item_database_url($realmId, $filters, [
            'sort' => $sortKey,
            'dir' => $nextDir,
            'p' => 1,
        ]);

        return '<a class="item-vault__sort-link' . ($currentSort === $sortKey ? ' is-active' : '') . '" href="' . htmlspecialchars($url) . '">' . htmlspecialchars($label) . $arrow . '</a>';
    }
}

if (!function_exists('spp_item_vault_realm_type')) {
    function spp_item_vault_realm_type($realmId)
    {
        static $cache = [];
        $realmId = (int)$realmId;
        if (isset($cache[$realmId])) {
            return $cache[$realmId];
        }

        try {
            require_once(dirname(__DIR__, 2) . '/components/admin/admin.realms.helpers.php');
            $realmdPdo = spp_get_pdo('realmd', $realmId);
            $stmt = $realmdPdo->prepare('SELECT `icon` FROM `realmlist` WHERE `id` = ? LIMIT 1');
            $stmt->execute([$realmId]);
            $icon = (int)$stmt->fetchColumn();
            $defs = function_exists('spp_admin_realms_type_definitions') ? spp_admin_realms_type_definitions() : [];
            $cache[$realmId] = strtoupper((string)($defs[$icon] ?? 'NORMAL'));
        } catch (Throwable $e) {
            $cache[$realmId] = 'NORMAL';
        }

        return $cache[$realmId];
    }
}

if (!function_exists('spp_item_database_build_page_state')) {
    function spp_item_database_build_page_state(array $config): array
    {
        $siteDatabaseHandle = $GLOBALS['DB'] ?? null;
        if ($siteDatabaseHandle !== null) {
            $GLOBALS['DB'] = $siteDatabaseHandle;
            $DB = $siteDatabaseHandle;
        }

        $realmMap = $GLOBALS['realmDbMap'] ?? null;
        $realmId = (is_array($realmMap) && !empty($realmMap)) ? spp_resolve_realm_id($realmMap) : 1;
        $realmLabel = spp_get_armory_realm_name($realmId) ?? 'Default Realm';

        $filters = spp_item_database_parse_request($config);
        $data = spp_item_database_fetch($realmId, $filters, $config);
        $filters = $data['filters'];
        $rows = $data['rows'];
        $pageCount = (int)$data['page_count'];
        $totalResults = (int)$data['total'];
        $page = (int)$filters['p'];
        $perPage = (int)$filters['per_page'];
        $offset = ($page - 1) * $perPage;
        $resultStart = $totalResults > 0 ? $offset + 1 : 0;
        $resultEnd = min($offset + $perPage, $totalResults);
        $hasFilters = spp_item_database_has_filters($filters);
        $isSearchMode = $filters['search'] !== '';
        $summary = $data['summary'];
        $counts = $data['counts'] ?? [];
        $sections = $data['sections'] ?? [];
        $sortOptions = spp_item_database_sort_options();
        $typeOptions = spp_item_database_type_options();
        $qualityOptions = spp_item_database_quality_options();
        $classOptions = spp_item_database_class_options();
        $slotOptions = spp_item_database_slot_options();
        $setSectionOptions = $data['set_section_options'] ?? spp_item_database_set_section_options();
        $setClassOptions = $data['set_class_options'] ?? ['Mage'];

        $quickLinkItemIcon = spp_item_database_icon_url('inv_sword_04');
        $quickLinkNpcIcon = spp_item_database_icon_url('ability_hunter_pet_gorilla');
        $quickLinkQuestIcon = spp_item_database_icon_url('inv_misc_questionmark');
        $quickLinkFeaturedIcon = spp_item_database_icon_url('inv_misc_gem_pearl_04');
        $quickLinkSetIcon = spp_item_database_icon_url('inv_chest_chain_10');

        $realmType = spp_item_vault_realm_type($realmId);
        $npcIconPools = [
            'PVP' => ['achievement_pvp_a_01', 'achievement_pvp_h_01', 'ability_warrior_battleshout', 'ability_rogue_ambush', 'ability_dualwield', 'ability_mount_netherdrakepurple'],
            'RPPVP' => ['achievement_pvp_a_01', 'achievement_pvp_h_01', 'spell_misc_hellifrepvphonorholdfavor', 'spell_misc_hellifrepvpthrallmarfavor', 'inv_bannerpvp_02', 'inv_bannerpvp_01'],
            'FFA_PVP' => ['achievement_pvp_a_01', 'achievement_pvp_h_01', 'spell_misc_hellifrepvphonorholdfavor', 'spell_misc_hellifrepvpthrallmarfavor', 'ability_warrior_battleshout', 'ability_rogue_ambush'],
            'NORMAL' => ['achievement_character_human_male', 'achievement_character_orc_male', 'achievement_character_nightelf_female', 'achievement_character_tauren_male', 'achievement_character_dwarf_male', 'achievement_character_troll_male'],
            'RP' => ['achievement_character_human_male', 'achievement_character_bloodelf_female', 'achievement_character_nightelf_female', 'achievement_character_undead_female', 'achievement_character_draenei_female', 'achievement_character_tauren_male'],
        ];
        $npcIconCandidates = $npcIconPools[$realmType] ?? $npcIconPools['NORMAL'];
        $quickLinkNpcIcon = spp_item_database_icon_url($npcIconCandidates[array_rand($npcIconCandidates)]);

        try {
            $armoryPdo = spp_get_pdo('armory', $realmId);

            $itemIconStmt = $armoryPdo->query('SELECT `name` FROM `dbc_itemdisplayinfo` WHERE `name` <> \'\' ORDER BY RAND() LIMIT 8');
            $itemIconRows = $itemIconStmt->fetchAll(PDO::FETCH_COLUMN);
            $itemIconRows = array_values(array_unique(array_filter(array_map('strval', $itemIconRows))));
            if (!empty($itemIconRows[0])) {
                $quickLinkFeaturedIcon = spp_item_database_icon_url($itemIconRows[0]);
            }
            if (!empty($itemIconRows[1])) {
                $quickLinkItemIcon = spp_item_database_icon_url($itemIconRows[1]);
            }

            $npcIconPlaceholders = implode(',', array_fill(0, count($npcIconCandidates), '?'));
            $npcIconStmt = $armoryPdo->prepare('SELECT `name` FROM `dbc_spellicon` WHERE LOWER(`name`) IN (' . $npcIconPlaceholders . ') ORDER BY RAND() LIMIT 1');
            $npcIconStmt->execute(array_map('strtolower', $npcIconCandidates));
            $npcIconName = (string)$npcIconStmt->fetchColumn();
            if ($npcIconName !== '') {
                $quickLinkNpcIcon = spp_item_database_icon_url($npcIconName);
            }
        } catch (Throwable $e) {
            // Leave fallback icons in place if the icon tables are unavailable.
        }

        $featuredVaultLinks = [
            [
                'label' => 'Raid Epics',
                'copy' => 'Browse the top-end epic drops in your realm index.',
                'url' => spp_item_database_url($realmId, $filters, ['type' => 'items', 'quality' => '4', 'item_class' => '', 'slot' => '', 'search' => '', 'p' => 1]),
                'active' => $filters['type'] === 'items' && $filters['quality'] === '4' && $filters['class'] === '' && $filters['slot'] === '' && $filters['search'] === '',
            ],
            [
                'label' => 'Weapons Locker',
                'copy' => 'Jump straight into weapons across the live world database.',
                'url' => spp_item_database_url($realmId, $filters, ['type' => 'items', 'item_class' => '2', 'quality' => '', 'slot' => '', 'search' => '', 'p' => 1]),
                'active' => $filters['type'] === 'items' && $filters['class'] === '2' && $filters['search'] === '',
            ],
            [
                'label' => 'Armor Archive',
                'copy' => 'Scan armor pieces, trinkets, shields, and set-ready gear.',
                'url' => spp_item_database_url($realmId, $filters, ['type' => 'items', 'item_class' => '4', 'quality' => '', 'slot' => '', 'search' => '', 'p' => 1]),
                'active' => $filters['type'] === 'items' && $filters['class'] === '4' && $filters['search'] === '',
            ],
            [
                'label' => 'Quest Rewards',
                'copy' => 'Surface questable items with a quick quality and level pass.',
                'url' => spp_item_database_url($realmId, $filters, ['type' => 'items', 'item_class' => '12', 'quality' => '', 'slot' => '', 'search' => '', 'p' => 1]),
                'active' => $filters['type'] === 'items' && $filters['class'] === '12' && $filters['search'] === '',
            ],
        ];

        $randomQuickLink = $featuredVaultLinks[array_rand($featuredVaultLinks)];
        $quickLinks = [
            array_merge($randomQuickLink, ['icon' => $quickLinkFeaturedIcon]),
            [
                'label' => 'Items',
                'copy' => 'Open the item database and browse gear, recipes, and quest rewards.',
                'url' => spp_item_database_url($realmId, $filters, ['type' => 'items', 'search' => '', 'quality' => '', 'item_class' => '', 'slot' => '', 'min_level' => '', 'max_level' => '', 'p' => 1]),
                'active' => $filters['type'] === 'items' && $filters['search'] === '' && $filters['quality'] === '' && $filters['class'] === '' && $filters['slot'] === '',
                'icon' => $quickLinkItemIcon,
            ],
            [
                'label' => 'Item Sets',
                'copy' => 'Browse class, world, and PvP armor sets from the same database flow.',
                'url' => spp_item_database_url($realmId, $filters, ['type' => 'sets', 'set_section' => 'all', 'set_class' => 'all', 'search' => '', 'quality' => '', 'item_class' => '', 'slot' => '', 'min_level' => '', 'max_level' => '', 'p' => 1]),
                'active' => $filters['type'] === 'sets',
                'icon' => $quickLinkSetIcon,
            ],
            [
                'label' => 'Quests',
                'copy' => 'Search quest titles, rewards, and later map-linked objective pages.',
                'url' => spp_item_database_url($realmId, $filters, ['type' => 'quests', 'search' => 'quest', 'icon' => '', 'quality' => '', 'item_class' => '', 'slot' => '', 'min_level' => '', 'max_level' => '', 'p' => 1]),
                'active' => $filters['type'] === 'quests',
                'icon' => $quickLinkQuestIcon,
            ],
            [
                'label' => 'NPCs',
                'copy' => 'Jump into creature records for bosses, vendors, and quest targets.',
                'url' => spp_item_database_url($realmId, $filters, ['type' => 'npcs', 'search' => 'guard', 'icon' => '', 'quality' => '', 'item_class' => '', 'slot' => '', 'min_level' => '', 'max_level' => '', 'p' => 1]),
                'active' => $filters['type'] === 'npcs',
                'icon' => $quickLinkNpcIcon,
            ],
        ];

        return [
            'realmId' => $realmId,
            'realmLabel' => $realmLabel,
            'filters' => $filters,
            'data' => $data,
            'rows' => $rows,
            'pageCount' => $pageCount,
            'totalResults' => $totalResults,
            'page' => $page,
            'perPage' => $perPage,
            'offset' => $offset,
            'resultStart' => $resultStart,
            'resultEnd' => $resultEnd,
            'hasFilters' => $hasFilters,
            'isSearchMode' => $isSearchMode,
            'summary' => $summary,
            'counts' => $counts,
            'sections' => $sections,
            'sortOptions' => $sortOptions,
            'typeOptions' => $typeOptions,
            'qualityOptions' => $qualityOptions,
            'classOptions' => $classOptions,
            'slotOptions' => $slotOptions,
            'setSectionOptions' => $setSectionOptions,
            'setClassOptions' => $setClassOptions,
            'realmType' => $realmType,
            'quickLinkItemIcon' => $quickLinkItemIcon,
            'quickLinkNpcIcon' => $quickLinkNpcIcon,
            'quickLinkQuestIcon' => $quickLinkQuestIcon,
            'quickLinkFeaturedIcon' => $quickLinkFeaturedIcon,
            'quickLinkSetIcon' => $quickLinkSetIcon,
            'featuredVaultLinks' => $featuredVaultLinks,
            'quickLinks' => $quickLinks,
        ];
    }
}
