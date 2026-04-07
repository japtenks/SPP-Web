<?php
if (INCLUDED !== true) {
    exit;
}

$siteRoot = dirname(__DIR__, 2);
require_once $siteRoot . '/app/admin/admin-index-page.php';

$adminIndexState = spp_admin_load_index_page_state();

extract($adminIndexState, EXTR_SKIP);
?>
