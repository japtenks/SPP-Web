<?php
if (INCLUDED !== true) exit;

$siteRoot = dirname(__DIR__, 2);
require_once $siteRoot . '/app/account/account-userlist-page.php';

$userlistState = spp_account_userlist_load_page_state(array(
    'get' => $_GET,
    'page' => $p ?? 1,
    'auth' => $auth,
    'realmDbMap' => $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? array()),
));

$userlistStop = !empty($userlistState['__stop']);
unset($userlistState['__stop']);
extract($userlistState, EXTR_SKIP);
if ($userlistStop) {
    return;
}
?>
