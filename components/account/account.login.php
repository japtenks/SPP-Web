<?php
if (INCLUDED !== true) exit;

$siteRoot = dirname(__DIR__, 2);
require_once $siteRoot . '/app/account/account-login-page.php';

$loginState = spp_account_login_load_page_state(array(
    'request' => $_REQUEST,
    'server' => $_SERVER,
    'user' => $user,
    'auth' => $auth,
));

$loginStop = !empty($loginState['__stop']);
unset($loginState['__stop']);
extract($loginState, EXTR_SKIP);
if ($loginStop) {
    return;
}
?>
