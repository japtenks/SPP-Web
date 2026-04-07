<?php
if (INCLUDED !== true) {
    exit;
}

$siteRoot = dirname(__DIR__, 2);
require_once $siteRoot . '/app/admin/admin-members-page.php';

$adminMembersState = spp_admin_members_load_page_state(array(
    'realm_db_map' => $realmDbMap ?? array(),
    'user' => $user,
    'auth' => $auth ?? null,
    'com_links' => $com_links ?? array(),
    'page' => $p ?? 1,
));

extract($adminMembersState, EXTR_OVERWRITE);
?>
