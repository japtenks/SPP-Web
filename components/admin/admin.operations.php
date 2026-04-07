<?php
if (INCLUDED !== true) {
    exit;
}

$siteRoot = dirname(__DIR__, 2);
require_once $siteRoot . '/app/admin/admin-operations-page.php';

$operationsState = spp_admin_operations_load_page_state(array(
    'realm_db_map' => $realmDbMap ?? array(),
));

extract($operationsState, EXTR_SKIP);
?>
