<?php
if (!defined('INCLUDED') || INCLUDED !== true) exit;

$siteRoot = dirname(__DIR__, 2);
require_once $siteRoot . '/app/forum/forum-topic-page.php';

$forumTopicState = spp_forum_load_topic_page_state(array(
    'user' => $user,
    'realm_map' => $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null),
    'page' => $p ?? 1,
    'get_vars' => $_GETVARS ?? array(),
    'site_href' => spp_config_temp_string('site_href', ''),
));
 $forumTopicStop = !empty($forumTopicState['__stop']);
unset($forumTopicState['__stop']);

extract($forumTopicState, EXTR_SKIP);

if ($forumTopicStop) return;
?>
