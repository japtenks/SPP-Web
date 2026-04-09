<?php

require_once __DIR__ . '/../../components/forum/forum.func.php';

if (!function_exists('spp_forum_load_index_page_state')) {
    function spp_forum_load_index_page_state(array $args = array()): array
    {
        $realmMap = (array)($args['realm_map'] ?? ($GLOBALS['realmDbMap'] ?? array()));
        $user = (array)($args['user'] ?? ($GLOBALS['user'] ?? array()));

        $realmId = spp_resolve_realm_id($realmMap);
        $realmPdo = spp_get_pdo('realmd', $realmId);

        return array(
            'items' => spp_forum_build_index_items($realmPdo, $user, true, $realmId),
        );
    }
}
