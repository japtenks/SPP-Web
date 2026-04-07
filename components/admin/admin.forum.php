<?php
if (INCLUDED !== true) {
    exit;
}

$siteRoot = dirname(__DIR__, 2);
require_once $siteRoot . '/app/admin/admin-forum-page.php';

$forumView = spp_admin_forum_load_page_state(array(
    'realm_db_map' => $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? array()),
));

extract($forumView, EXTR_OVERWRITE);
?>
