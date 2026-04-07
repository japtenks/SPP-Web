<?php
if (INCLUDED !== true) exit;

$siteRoot = dirname(__DIR__, 2);
require_once $siteRoot . '/app/account/account-pms-page.php';

$forumPmsState = spp_account_pms_load_page_state(array(
    'user' => $user,
    'realmDbMap' => $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null),
    'get' => $_GET,
    'post' => $_POST,
    'cookie' => $_COOKIE,
));

$forumPmsStop = !empty($forumPmsState['__stop']);
unset($forumPmsState['__stop']);
extract($forumPmsState, EXTR_SKIP);
if ($forumPmsStop) {
    return;
}
