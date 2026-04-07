<?php
if (!defined('INCLUDED') || INCLUDED !== true) exit;

$siteRoot = dirname(__DIR__, 2);
require_once $siteRoot . '/app/forum/forum-viewforum-page.php';

$forumViewforumState = spp_forum_load_viewforum_page_state(array(
    'realm_map' => $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null),
    'user' => $user ?? ($GLOBALS['user'] ?? array()),
    'page' => $p ?? 1,
    'get' => $_GET,
));
$forumViewforumStop = !empty($forumViewforumState['__stop']);
unset($forumViewforumState['__stop']);
extract($forumViewforumState, EXTR_SKIP);

if ($forumViewforumStop) return;
?>
