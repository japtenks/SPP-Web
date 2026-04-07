<?php
if (INCLUDED !== true) {
    exit;
}

require_once dirname(__DIR__, 2) . '/app/server/downloads-page.php';

$downloadsPageState = spp_server_downloads_load_page_state(array(
    'realm_map' => $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? array()),
));

extract($downloadsPageState, EXTR_OVERWRITE);
?>
