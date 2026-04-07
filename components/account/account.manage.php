<?php
if (INCLUDED !== true) exit;

$siteRoot = dirname(__DIR__, 2);
require_once $siteRoot . '/app/account/account-manage-page.php';

$manageState = spp_account_manage_load_page_state(array(
    'user' => $user,
    'auth' => $auth,
    'realm_map' => $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? array()),
));

$manageStop = !empty($manageState['__stop']);
unset($manageState['__stop']);
extract($manageState, EXTR_SKIP);
if ($manageStop) {
    return;
}
?>
