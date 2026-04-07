<?php
if (INCLUDED !== true) exit;

$siteRoot = dirname(__DIR__, 2);
require_once $siteRoot . '/app/account/account-view-page.php';

$viewState = spp_account_view_load_page_state(array(
    'get' => $_GET,
    'auth' => $auth,
    'user' => $user,
    'realmDbMap' => $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? array()),
));

$viewStop = !empty($viewState['__stop']);
unset($viewState['__stop']);
extract($viewState, EXTR_SKIP);
if ($viewStop) {
    return;
}
?>
