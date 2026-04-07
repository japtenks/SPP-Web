<?php
if (INCLUDED !== true) {
    exit;
}

require_once dirname(__DIR__, 2) . '/app/server/ah-page.php';

$auctionHousePageState = spp_server_ah_load_page_state(array(
    'realm_map' => $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? array()),
    'get' => $_GET,
));

extract($auctionHousePageState, EXTR_OVERWRITE);
