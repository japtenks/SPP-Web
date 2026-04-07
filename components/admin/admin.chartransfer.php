<?php
if (INCLUDED !== true) {
    exit;
}

require_once dirname(__DIR__, 2) . '/app/admin/admin-chartransfer-page.php';

$chartransferState = spp_admin_chartransfer_load_page_state(array(
    'realm_db_map' => $realmDbMap ?? array(),
));

extract($chartransferState, EXTR_SKIP);
