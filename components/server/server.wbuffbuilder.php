<?php
if (INCLUDED !== true) {
    exit;
}

require_once dirname(__DIR__, 2) . '/app/server/wbuffbuilder-page.php';

$wbuffbuilderPageState = spp_wbuffbuilder_load_page_state(array(
    'realm_map' => $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? array()),
    'get' => $_GET,
));

extract($wbuffbuilderPageState, EXTR_OVERWRITE);
?>
