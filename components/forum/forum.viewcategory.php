<?php
if (!defined('INCLUDED') || INCLUDED !== true) exit;

$siteRoot = dirname(__DIR__, 2);
require_once $siteRoot . '/app/forum/forum-viewcategory-page.php';

$forumViewcategoryState = spp_forum_load_viewcategory_page_state(array(
    'realm_map' => $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null),
    'user' => $user ?? ($GLOBALS['user'] ?? array()),
    'get' => $_GET,
));
$forumViewcategoryStop = !empty($forumViewcategoryState['__stop']);
unset($forumViewcategoryState['__stop']);
extract($forumViewcategoryState, EXTR_SKIP);

if ($forumViewcategoryStop) return;
?>
