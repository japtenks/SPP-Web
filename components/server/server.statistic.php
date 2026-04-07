<?php
if (!defined('INCLUDED') || INCLUDED !== true) {
    exit;
}

require_once dirname(__DIR__, 2) . '/app/server/statistic-page.php';

$statisticPageState = spp_stat_load_page_state(array(
    'realm_map' => $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? array()),
    'get' => $_GET,
));

extract($statisticPageState, EXTR_OVERWRITE);
