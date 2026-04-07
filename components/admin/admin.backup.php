<?php
if (INCLUDED !== true) {
    exit;
}

$siteRoot = dirname(__DIR__, 2);
require_once $siteRoot . '/app/admin/admin-backup-page.php';

$backupPageState = spp_admin_backup_load_page_state(array(
    'realm_db_map' => $realmDbMap ?? array(),
));

extract($backupPageState, EXTR_SKIP);
