<?php
if (INCLUDED !== true) {
    exit;
}

$siteRoot = dirname(__DIR__, 2);
require_once $siteRoot . '/app/admin/admin-realms-page.php';

$realmsState = spp_admin_realms_load_page_state(array(
    'realm_db_map' => $realmDbMap ?? array(),
));

extract($realmsState, EXTR_SKIP);
?>
