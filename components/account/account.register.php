<?php
if (INCLUDED !== true) exit;

$siteRoot = dirname(__DIR__, 2);
require_once $siteRoot . '/app/account/account-register-page.php';

$registerPageState = spp_account_register_load_page_state(array(
    'user' => $user,
    'realmDbMap' => $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? array()),
    'server' => $_SERVER,
));

$registerStop = !empty($registerPageState['__stop']);
unset($registerPageState['__stop']);
extract($registerPageState, EXTR_SKIP);
if ($registerStop) {
    return;
}
