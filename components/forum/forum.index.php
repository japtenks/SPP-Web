<?php
if (!defined('INCLUDED') || INCLUDED !== true) exit;

$siteRoot = dirname(__DIR__, 2);
require_once $siteRoot . '/app/forum/forum-index-page.php';

$forumIndexState = spp_forum_load_index_page_state(array(
    'realm_map' => $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null),
    'user' => $user ?? ($GLOBALS['user'] ?? array()),
));

extract($forumIndexState, EXTR_SKIP);
?>
