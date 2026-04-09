<?php
if (INCLUDED !== true) {
    exit;
}

require_once dirname(__DIR__, 2) . '/app/admin/admin-bots-page.php';

$botMaintenanceView = spp_admin_bots_load_page_state(array(
    'realm_db_map' => $realmDbMap ?? array(),
    'master_pdo' => spp_get_pdo('realmd', 1),
));
