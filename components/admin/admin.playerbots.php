<?php
if (INCLUDED !== true) {
    exit;
}

$siteRoot = dirname(__DIR__, 2);
require_once $siteRoot . '/app/admin/admin-playerbots-page.php';

$playerbotsState = spp_admin_playerbots_load_page_state(array(
    'realm_db_map' => $realmDbMap ?? array(),
));

extract($playerbotsState, EXTR_OVERWRITE);
