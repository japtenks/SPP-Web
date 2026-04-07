<?php

if (!function_exists('spp_server_playermap_load_page_state')) {
    function spp_server_playermap_load_page_state(array $args = array()): array
    {
        $realmMap = (array)($args['realm_map'] ?? ($GLOBALS['realmDbMap'] ?? array()));
        $get = (array)($args['get'] ?? $_GET);
        $requestedRealmId = isset($get['realm']) ? (int)$get['realm'] : 0;
        $realmId = !empty($realmMap)
            ? (int)spp_resolve_realm_id($realmMap, $requestedRealmId > 0 ? $requestedRealmId : null)
            : max(1, $requestedRealmId);

        if ($realmId <= 0) {
            $realmId = 1;
        }

        return array(
            'playermapRealmId' => $realmId,
            'playermapFrameSrc' => './components/pomm/playermap.php?realm=' . $realmId,
            'pathway_info' => array(
                array('title' => 'Player Map', 'link' => ''),
            ),
        );
    }
}
