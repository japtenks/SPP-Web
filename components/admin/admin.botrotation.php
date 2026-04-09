<?php
if (INCLUDED !== true) {
    exit;
}

$siteRoot = dirname(__DIR__, 2);
require_once $siteRoot . '/app/admin/admin-botrotation-page.php';

$botRotationState = spp_admin_botrotation_load_page_state(array(
    'realm_db_map' => $realmDbMap ?? array(),
));

extract($botRotationState, EXTR_SKIP);
