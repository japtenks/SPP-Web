<?php
if (!defined('INCLUDED') || INCLUDED !== true) exit;

$siteRoot = dirname(__DIR__, 2);
require_once $siteRoot . '/app/forum/forum-post-page.php';

$forum_post_state = spp_forum_load_post_page_state(array(
    'user' => $user,
    'realm_map' => $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null),
    'get' => $_GET,
    'post' => $_POST,
    'cookie' => $_COOKIE,
));
$forum_post_stop = !empty($forum_post_state['__stop']);
unset($forum_post_state['__stop']);
extract($forum_post_state, EXTR_SKIP);

if ($forum_post_stop) return;
?>
