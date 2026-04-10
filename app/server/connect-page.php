<?php

require_once __DIR__ . '/realm-capabilities.php';
require_once __DIR__ . '/realmlist-endpoint.php';

if (!function_exists('spp_server_load_connect_page_state')) {
    function spp_server_load_connect_page_state(array $args = array()): array
    {
        $realmMap = (array)($args['realm_map'] ?? ($GLOBALS['realmDbMap'] ?? array()));
        $get = (array)($args['get'] ?? $_GET);
        $user = (array)($args['user'] ?? ($GLOBALS['user'] ?? array()));
        $requestedChoiceId = isset($get['realm']) ? (int)$get['realm'] : 0;
        $choice = spp_server_realmlist_choice($realmMap, $requestedChoiceId);
        $realmId = (int)($choice['public_choice_id'] ?? 0);
        $sourceSlotId = (int)($choice['source_slot_id'] ?? $realmId);
        $realmCapabilities = $sourceSlotId > 0 ? spp_realm_capabilities($realmMap, $sourceSlotId) : array();
        $realmName = trim((string)($choice['label'] ?? 'This Server'));
        if ($realmName === '') {
            $realmName = 'This Server';
        }
        $realmlistHost = trim((string)($choice['host'] ?? ''));
        $downloadUrl = 'index.php?n=server&sub=realmlist&nobody=1&realm=' . max(1, $realmId);

        return array(
            'realmId' => $realmId,
            'connectRealmName' => $realmName,
            'connectRealmlistHost' => $realmlistHost,
            'connectRealmlistDownloadAvailable' => $realmlistHost !== '',
            'connectRealmlistMetadataState' => (string)($choice['metadata_state'] ?? 'incomplete'),
            'connectRealmlistMissingReasons' => (array)($choice['missing_reasons'] ?? array()),
            'createAccountUrl' => spp_route_url('account', 'register', array(), false),
            'downloadRealmlistUrl' => $downloadUrl,
            'downloadRealmlistOptions' => spp_server_realmlist_download_options($realmMap, $realmId),
            'isLoggedIn' => !empty($user['id']) && (int)$user['id'] > 0,
            'realmCapabilities' => $realmCapabilities,
        );
    }
}
