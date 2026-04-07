<?php
if (INCLUDED !== true) {
    exit();
}

$siteRoot = dirname(__DIR__, 2);
require_once $siteRoot . '/app/frontpage/frontpage-page.php';

$frontpageState = spp_frontpage_load_page_state(array(
    'realm_map' => $realmDbMap ?? array(),
    'config' => spp_runtime_config(),
));

extract($frontpageState);
