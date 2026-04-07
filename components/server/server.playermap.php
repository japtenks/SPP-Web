<?php
if (INCLUDED !== true) {
    exit;
}

require_once dirname(__DIR__, 2) . '/app/server/playermap-page.php';

$playermapPageState = spp_server_playermap_load_page_state(array(
    'realm_map' => $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? array()),
    'get' => $_GET,
));

extract($playermapPageState, EXTR_OVERWRITE);
?>
