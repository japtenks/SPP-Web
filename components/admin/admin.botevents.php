<?php
if (INCLUDED !== true) {
    exit;
}

require_once dirname(__DIR__, 2) . '/app/admin/admin-botevents-page.php';

$boteventsState = spp_admin_botevents_load_page_state(array(
    'realm_db_map' => $realmDbMap ?? array(),
    'master_pdo' => spp_get_pdo('realmd', 1),
));

extract($boteventsState, EXTR_SKIP);
?>
