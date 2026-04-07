<?php
if (INCLUDED !== true) {
    exit;
}

require_once dirname(__DIR__, 2) . '/app/server/realmstatus-page.php';

$realmstatusPageState = spp_realmstatus_load_page_state(array(
    'realm_map' => $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? array()),
    'get' => $_GET,
));

extract($realmstatusPageState, EXTR_OVERWRITE);

$pathway_info[] = array('title' => 'Realm Status', 'link' => '');
?>
