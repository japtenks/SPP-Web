<?php

if (!function_exists('spp_route_registry')) {
    function spp_route_registry(): array
    {
        static $registry = null;

        if ($registry !== null) {
            return $registry;
        }

        $talentParams = array();
        if (!empty($GLOBALS['user']['character_name'])) {
            $talentParams['character'] = $GLOBALS['user']['character_name'];
        }
        if (!empty($GLOBALS['user']['cur_selected_realmd'])) {
            $talentParams['realm'] = (int)$GLOBALS['user']['cur_selected_realmd'];
        }

        $registry = array(
            'account' => array(
                'index' => array('', 'Account', 'index.php?n=account', '', 0),
                'pms' => array('g_use_pm', 'Messages', 'index.php?n=account&sub=pms', '', 1),
                'manage' => array('', 'Manage Account', 'index.php?n=account&sub=manage', '2-Account', 0),
                'view' => array('', '', 'index.php?n=account&sub=view', '', 0),
                'register' => array('', 'Create Account', 'index.php?n=account&sub=register', '2-Account', 0),
                'userlist' => array('g_view_profile', 'User List', 'index.php?n=account&sub=userlist', '2-Account', 0),
                'login' => array('', 'Login', 'index.php?n=account&sub=login', '', 0),
            ),
            'admin' => array(
                'index' => array('g_is_admin', 'admin_panel', 'index.php?n=admin', '', 0),
                'members' => array('g_is_admin', 'users_manage', 'index.php?n=admin&sub=members', '', 1),
                'realms' => array('g_is_admin', 'realms_manage', 'index.php?n=admin&sub=realms', '', 1),
                'forum' => array('g_is_admin', 'forums_manage', 'index.php?n=admin&sub=forum', '', 1),
                'news' => array('g_is_admin', 'News Editor', 'index.php?n=admin&sub=news', '', 1),
                'backup' => array('g_is_admin', 'backup', 'index.php?n=admin&sub=backup', '', 1),
                'operations' => array('g_is_admin', 'Operations', 'index.php?n=admin&sub=operations', '', 1),
                'identities' => array('g_is_admin', 'Identity & Data Health', 'index.php?n=admin&sub=identities', '', 1),
                'botevents' => array('g_is_admin', 'Bot Events', 'index.php?n=admin&sub=botevents', '', 1),
                'playerbots' => array('g_is_admin', 'Playerbots Control', 'index.php?n=admin&sub=playerbots', '', 1),
                'chartools' => array('g_is_admin', 'chartransfer', 'index.php?n=admin&sub=chartools', '', 0),
                'chartransfer' => array('g_is_admin', 'chartransfer', 'index.php?n=admin&sub=chartransfer', '', 0),
            ),
            'forum' => array(
                'index' => array('', 'Forums', 'index.php?n=forum', '6-Forums', 0),
                'post' => array('', 'Post', 'index.php?n=forum&sub=post', '', 0),
                'viewforum' => array('', 'View Forum', 'index.php?n=forum&sub=viewforum', '', 0),
                'viewcategory' => array('', 'View Category', 'index.php?n=forum&sub=viewcategory', '', 0),
                'viewtopic' => array('', 'View Topic', 'index.php?n=forum&sub=viewtopic', '', 1),
            ),
            'frontpage' => array(
                'index' => array('', 'Home', 'index.php', '1-News', 0),
            ),
            'html' => array(
                'index' => array('', 'static_docs', 'index.php?n=html', '', 0),
            ),
            'news' => array(
                'index' => array('', 'News', 'index.php?n=news', '1-News', 1),
                'view' => array('', 'News', 'index.php?n=news&sub=view', '1-News', 0),
            ),
            'server' => array(
                'index' => array('', 'Server Info', 'index.php?n=server', '', 0),
                'connect' => array('', 'How to Play', spp_route_url('server', 'connect'), '3-Game Guide', 0),
                'botcommands' => array('', 'Bot Guide', spp_route_url('server', 'botcommands'), '3-Game Guide', 0),
                'wbuffbuilder' => array('', 'World Buff Builder', spp_route_url('server', 'wbuffbuilder'), '', 0),
                'chars' => array('', 'Characters on the server', spp_route_url('server', 'chars'), '7-Armory', 0),
                'character' => array('', 'Character', spp_route_url('server', 'character'), '', 0),
                'guilds' => array('', 'Guilds on the server', spp_route_url('server', 'guilds'), '7-Armory', 0),
                'guild' => array('', 'Guild', spp_route_url('server', 'guild'), '', 0),
                'realmstatus' => array('', 'Realm Status', spp_route_url('server', 'realmstatus'), '4-Workshop', 0),
                'honor' => array('', 'Honor', spp_route_url('server', 'honor'), '7-Armory', 0),
                'playermap' => array('', 'Player Map', spp_route_url('server', 'playermap'), '4-Workshop', 0),
                'talents' => array('', 'Talents', spp_route_url('server', 'talents', $talentParams), '7-Armory', 0),
                'items' => array('', 'Armory Database', spp_route_url('server', 'items'), '7-Armory', 0),
                'marketplace' => array('', 'Market Place', spp_route_url('server', 'marketplace'), '7-Armory', 0),
                'marketplaceapi' => array('', 'Market Place Data', spp_route_url('server', 'marketplaceapi'), '', 0),
                'item' => array('', 'Item Entry', spp_route_url('server', 'item'), '', 0),
                'statistic' => array('', 'Statistics', spp_route_url('server', 'statistic'), '4-Workshop', 0),
                'ah' => array('', 'Auction House', spp_route_url('server', 'ah'), '4-Workshop', 0),
                'downloads' => array('', 'Downloads', spp_route_url('server', 'downloads'), '4-Workshop', 0),
                'realmlist' => array('', 'Realm List Download', spp_route_url('server', 'realmlist'), '', 0),
                'itemtooltip' => array('', 'Item Tooltip', spp_route_url('server', 'itemtooltip'), '', 0),
            ),
        );

        return $registry;
    }
}

if (!function_exists('spp_active_components')) {
    function spp_active_components(): array
    {
        return array('account', 'forum', 'news', 'server', 'frontpage', 'admin', 'html');
    }
}

if (!function_exists('spp_deprecated_component_families')) {
    function spp_deprecated_component_families(): array
    {
        return array('community', 'gameguide', 'media', 'statistic', 'whoisonline');
    }
}

if (!function_exists('spp_register_component_routes')) {
    function spp_register_component_routes(string $component, array &$comContent): void
    {
        $registry = spp_route_registry();
        if (isset($registry[$component])) {
            $comContent[$component] = $registry[$component];
        }
    }
}

if (!function_exists('spp_seed_public_router_metadata')) {
    function spp_seed_public_router_metadata(): array
    {
        $registry = spp_route_registry();
        $seed = array();

        foreach (spp_active_components() as $component) {
            if (isset($registry[$component])) {
                $seed[$component] = $registry[$component];
            }
        }

        return $seed;
    }
}

if (!function_exists('spp_route_page_contracts')) {
    function spp_route_page_contracts(): array
    {
        return array(
            'account' => array(
                'login' => array(
                    'loader' => 'app/account/account-login-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                ),
                'register' => array(
                    'loader' => 'app/account/account-register-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                    'assets' => array(
                        'scripts' => array('account.register.js'),
                    ),
                ),
                'manage' => array(
                    'loader' => 'app/account/account-manage-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                    'assets' => array(
                        'scripts' => array('account.manage.js'),
                    ),
                ),
                'view' => array(
                    'loader' => 'app/account/account-view-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                ),
                'pms' => array(
                    'loader' => 'app/account/account-pms-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                    'assets' => array(
                        'scripts' => array('account.pms.js'),
                    ),
                ),
                'userlist' => array(
                    'loader' => 'app/account/account-userlist-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                    'assets' => array(
                        'scripts' => array('account.userlist.js'),
                    ),
                ),
            ),
            'admin' => array(
                'index' => array(
                    'loader' => 'app/admin/admin-index-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                ),
                'members' => array(
                    'loader' => 'app/admin/admin-members-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                    'assets' => array(
                        'scripts' => array('admin.members.js'),
                    ),
                ),
                'forum' => array(
                    'loader' => 'app/admin/admin-forum-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                ),
                'realms' => array(
                    'loader' => 'app/admin/admin-realms-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                ),
                'botevents' => array(
                    'loader' => 'app/admin/admin-botevents-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                    'assets' => array(
                        'scripts' => array('admin.botevents.js'),
                    ),
                ),
                'playerbots' => array(
                    'loader' => 'app/admin/admin-playerbots-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                    'assets' => array(
                        'scripts' => array('admin.playerbots.js'),
                    ),
                ),
                'chartools' => array(
                    'loader' => 'app/admin/admin-chartools-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                ),
                'chartransfer' => array(
                    'loader' => 'app/admin/admin-chartransfer-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                ),
                'backup' => array(
                    'loader' => 'app/admin/admin-backup-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                    'assets' => array(
                        'scripts' => array('admin.backup.js'),
                    ),
                ),
                'operations' => array(
                    'loader' => 'app/admin/admin-operations-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                ),
                'identities' => array(
                    'loader' => 'app/admin/admin-identities-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                    'assets' => array(
                        'scripts' => array('admin.identities.js'),
                    ),
                ),
            ),
            'frontpage' => array(
                'index' => array(
                    'loader' => 'app/frontpage/frontpage-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                    'assets' => array(
                        'scripts' => array('frontpage.js'),
                    ),
                ),
            ),
            'news' => array(
                'index' => array(
                    'loader' => 'app/news/news-index-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                ),
                'view' => array(
                    'loader' => 'app/news/news-view-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                ),
            ),
            'forum' => array(
                'index' => array(
                    'loader' => 'app/forum/forum-index-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                ),
                'post' => array(
                    'loader' => 'app/forum/forum-post-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                    'assets' => array(
                        'scripts' => array('forum.post.js'),
                    ),
                ),
                'viewforum' => array(
                    'loader' => 'app/forum/forum-viewforum-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                ),
                'viewcategory' => array(
                    'loader' => 'app/forum/forum-viewcategory-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                ),
                'viewtopic' => array(
                    'loader' => 'app/forum/forum-topic-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                ),
            ),
            'server' => array(
                'connect' => array(
                    'loader' => 'app/server/connect-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                ),
                'botcommands' => array(
                    'loader' => 'app/server/botcommands-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                    'assets' => array(
                        'scripts' => array('server.botcommands.js', 'server.wbuffbuilder.js'),
                    ),
                ),
                'wbuffbuilder' => array(
                    'loader' => 'app/server/wbuffbuilder-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                    'assets' => array(
                        'scripts' => array('server.wbuffbuilder.js'),
                    ),
                ),
                'chars' => array(
                    'loader' => 'app/server/chars-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                    'assets' => array(
                        'scripts' => array('server.chars.js'),
                    ),
                ),
                'character' => array(
                    'loader' => 'app/server/character-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                    'assets' => array(
                        'style_paths' => array('css/armory-tooltips.css', 'css/paperdoll.css'),
                        'scripts' => array('item-tooltips.js', 'server.character.js'),
                    ),
                ),
                'guilds' => array(
                    'loader' => 'app/server/guilds-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                ),
                'honor' => array(
                    'loader' => 'app/server/honor-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                    'assets' => array(
                        'scripts' => array('server.honor.js'),
                    ),
                ),
                'playermap' => array(
                    'loader' => 'app/server/playermap-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                    'assets' => array(
                        'scripts' => array('server.playermap.js'),
                    ),
                ),
                'guild' => array(
                    'loader' => 'app/server/guild-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                    'assets' => array(
                        'style_paths' => array('server/server.guild.css'),
                        'script_paths' => array('server/server.guild.js'),
                    ),
                ),
                'items' => array(
                    'loader' => 'app/server/items-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                    'assets' => array(
                        'scripts' => array('item-tooltips.js'),
                        'script_paths' => array('server/server.items.js'),
                    ),
                ),
                'realmstatus' => array(
                    'loader' => 'app/server/realmstatus-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                    'assets' => array(
                        'scripts' => array('server.realmstatus.js'),
                    ),
                ),
                'statistic' => array(
                    'loader' => 'app/server/statistic-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                ),
                'ah' => array(
                    'loader' => 'app/server/ah-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                    'assets' => array(
                        'scripts' => array('item-tooltips.js', 'server.ah.js'),
                    ),
                ),
                'downloads' => array(
                    'loader' => 'app/server/downloads-page.php',
                    'component_role' => 'adapter',
                    'template_role' => 'render',
                ),
            ),
        );
    }
}

if (!function_exists('spp_route_page_contract')) {
    function spp_route_page_contract(string $component, string $subpage): array
    {
        $contracts = spp_route_page_contracts();
        return (array)($contracts[$component][$subpage] ?? array());
    }
}

if (!function_exists('spp_route_utility_contracts')) {
    function spp_route_utility_contracts(): array
    {
        return array(
            'server' => array(
                'realmlist' => array(
                    'contract_type' => 'utility',
                    'kind' => 'download',
                    'utility_kind' => 'download',
                    'loader' => 'app/server/realmlist-endpoint.php',
                    'component_role' => 'adapter',
                    'response' => array(
                        'content_type' => 'text/plain; charset=UTF-8',
                        'disposition' => 'attachment',
                    ),
                    'nobody' => true,
                ),
            ),
        );
    }
}

if (!function_exists('spp_route_utility_contract')) {
    function spp_route_utility_contract(string $component, string $subpage): array
    {
        $contracts = spp_route_utility_contracts();
        return (array)($contracts[$component][$subpage] ?? array());
    }
}

if (!function_exists('spp_route_contract')) {
    function spp_route_contract(string $component, string $subpage): array
    {
        $pageContract = spp_route_page_contract($component, $subpage);
        if (!empty($pageContract)) {
            return array_merge(
                array(
                    'contract_type' => 'page',
                ),
                $pageContract
            );
        }

        return spp_route_utility_contract($component, $subpage);
    }
}

if (!function_exists('spp_bootstrap_route_page_contract')) {
    function spp_bootstrap_route_page_contract(string $component, string $subpage, $config = null): array
    {
        $contract = spp_route_page_contract($component, $subpage);
        if (empty($contract)) {
            return array();
        }

        return spp_page_contract_bootstrap(
            $component,
            $subpage,
            (array)($contract['assets'] ?? array()),
            $config
        );
    }
}

if (!function_exists('spp_bootstrap_route_contract')) {
    function spp_bootstrap_route_contract(string $component, string $subpage, $config = null): array
    {
        $contract = spp_route_contract($component, $subpage);
        if (empty($contract)) {
            return array();
        }

        if (($contract['contract_type'] ?? '') === 'utility') {
            return spp_utility_contract_bootstrap($component, $subpage, $contract);
        }

        return spp_page_contract_bootstrap(
            $component,
            $subpage,
            (array)($contract['assets'] ?? array()),
            $config
        );
    }
}

if (!function_exists('spp_main_navigation_registry')) {
    function spp_main_navigation_registry(): array
    {
        $menu = array(
            '1-News' => array(
                0 => array('News', 'index.php', ''),
                1 => array('News Archive', 'index.php?n=news', ''),
            ),
            '2-Account' => array(
                0 => array('Manage Account', spp_route_url('account', 'manage'), 'g_is_supadmin'),
                1 => array('Messages', spp_route_url('account', 'pms'), 'g_view_profile'),
                2 => array('Create Account', spp_route_url('account', 'register'), '!g_view_profile'),
                7 => array('User List', spp_route_url('account', 'userlist'), 'g_view_profile'),
                9 => array('Admin Panel', 'index.php?n=admin', 'g_is_supadmin'),
            ),
            '3-Game Guide' => array(
                0 => array('How to Play', spp_route_url('server', 'connect'), ''),
                1 => array('Bot Guide', spp_route_url('server', 'botcommands'), ''),
            ),
            '4-Workshop' => array(
                0 => array('Realm Status', spp_route_url('server', 'realmstatus'), ''),
                2 => array('Player Map', spp_route_url('server', 'playermap'), ''),
                3 => array('Statistics', spp_route_url('server', 'statistic'), ''),
                4 => array('Auction House', spp_route_url('server', 'ah'), ''),
                8 => array('Downloads', 'index.php?n=server&sub=downloads', ''),
            ),
            '6-Forums' => array(
                0 => array('Forums', 'index.php?n=forum', ''),
                1 => array('General', spp_forum_general_menu_url(), ''),
                2 => array('Guild Recruitment', spp_forum_guild_menu_url(), ''),
                3 => array('Help', spp_forum_help_menu_url(), ''),
            ),
            '7-Armory' => array(
                0 => array('Characters', 'index.php?n=server&sub=chars', ''),
                1 => array('Guilds', 'index.php?n=server&sub=guilds', ''),
                2 => array('Honor', 'index.php?n=server&sub=honor', ''),
                3 => array('Talent Calculator', 'index.php?n=server&sub=talents', ''),
                4 => array('Database', 'index.php?n=server&sub=items', ''),
                5 => array('Market Place', 'index.php?n=server&sub=marketplace', ''),
            ),
            '8-Support' => array(
                0 => array('Bug Reports Forum', 'index.php?n=forum&sub=viewforum&fid=2', ''),
                1 => array('SPP Community Discord', 'https://discord.gg/TpxqWWT', ''),
                2 => array('Playerbots Discord', 'https://discord.gg/s4JGKG2BUW', ''),
                3 => array('Proxmox GitHub Issues', 'https://github.com/japtenks/spp-cmangos-prox/issues', ''),
                4 => array('MaNGOS Bots Repository', 'https://github.com/celguar/mangos-classic/tree/ike3-bots', ''),
                5 => array('Website GitHub Issues', 'https://github.com/japtenks/SPP-Armory-Website/issues', ''),
            ),
        );

        if (isset($menu['7-Armory'][3])) {
            $talentLink = 'index.php?n=server&sub=talents';
            if (!empty($GLOBALS['user']['cur_selected_realmd'])) {
                $talentLink .= '&realm=' . (int)$GLOBALS['user']['cur_selected_realmd'];
            }
            $menu['7-Armory'][3][1] = $talentLink;
        }

        if ((int)spp_config_forum('frame_forum', 0) || (int)spp_config_forum('externalforum', 0)) {
            $menu['6-Forums'][0][1] = (string)spp_config_forum('forum_external_link', '');
        }

        $menu['8-Support'][0][1] = spp_route_url('forum', 'viewforum', array('fid' => (int)spp_config_forum('bugs_forum_id', 0)));

        if ((int)spp_config_forum('frame_bugstracker', 0) || (int)spp_config_forum('externalbugstracker', 0)) {
            $menu['8-Support'][0][1] = (string)spp_config_forum('bugstracker_external_link', '');
        }

        return $menu;
    }
}
