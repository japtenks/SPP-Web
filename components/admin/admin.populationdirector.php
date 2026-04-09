<?php
if (INCLUDED !== true) {
    exit;
}

$siteRoot = dirname(__DIR__, 2);
require_once $siteRoot . '/app/admin/admin-populationdirector-page.php';

$populationDirectorState = spp_admin_populationdirector_load_page_state(array(
    'realm_db_map' => $realmDbMap ?? array(),
));

extract($populationDirectorState, EXTR_SKIP);
