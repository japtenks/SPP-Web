<?php

require_once __DIR__ . '/admin-forum-helpers.php';
require_once __DIR__ . '/admin-forum-actions.php';
require_once __DIR__ . '/admin-forum-read.php';

if (!function_exists('spp_admin_forum_load_page_state')) {
    function spp_admin_forum_load_page_state(array $args = array()): array
    {
        $realmDbMap = (array)($args['realm_db_map'] ?? ($GLOBALS['realmDbMap'] ?? array()));
        $forumPdo = spp_get_pdo('realmd', spp_resolve_realm_id($realmDbMap));
        spp_admin_forum_handle_action($forumPdo);

        return array_merge(
            spp_admin_forum_build_view($forumPdo),
            array(
                'forum_admin_csrf_token' => spp_csrf_token('admin_forum'),
            )
        );
    }
}
