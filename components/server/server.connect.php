<?php
if (INCLUDED !== true) {
    exit;
}

require_once dirname(__DIR__, 2) . '/app/server/connect-page.php';

$serverConnectState = spp_server_load_connect_page_state(array(
    'realm_map' => $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? array()),
    'user' => $user ?? ($GLOBALS['user'] ?? array()),
    'server' => $_SERVER,
));

extract($serverConnectState, EXTR_OVERWRITE);

$pathway_info[] = array('title' => 'How to Play', 'link' => '');
?>
