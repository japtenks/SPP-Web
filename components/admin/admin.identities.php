<?php
if (INCLUDED !== true) {
    exit;
}

$siteRoot = dirname(__DIR__, 2);
require_once $siteRoot . '/app/admin/admin-identities-page.php';

$identityHealthState = spp_admin_identity_health_load_page_state(array(
    'realm_db_map' => $realmDbMap ?? array(),
    'site_root' => $_SERVER['DOCUMENT_ROOT'] ?? $siteRoot,
));

extract($identityHealthState, EXTR_OVERWRITE);
