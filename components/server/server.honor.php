<?php
if (INCLUDED !== true) {
    exit;
}

require_once dirname(__DIR__, 2) . '/app/server/honor-page.php';

$honorPageState = spp_server_honor_load_page_state(array(
    'realm_map' => $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? array()),
    'get' => $_GET,
));

extract($honorPageState, EXTR_OVERWRITE);

$pathway_info[] = array('title' => 'Honor', 'link' => 'index.php?n=server&sub=honor');
?>

