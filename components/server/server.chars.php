<?php
if (INCLUDED !== true) {
    exit;
}

$siteRoot = dirname(__DIR__, 2);
require_once $siteRoot . '/app/server/chars-page.php';

$charsPageState = spp_chars_load_page_state(array(
    'realm_map' => $GLOBALS['allEnabledRealmDbMap'] ?? $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? array()),
    'get' => $_GET,
    'page' => $p ?? 1,
));

extract($charsPageState, EXTR_OVERWRITE);




