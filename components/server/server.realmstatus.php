<?php
if (INCLUDED !== true) {
    exit;
}

require_once dirname(__DIR__, 2) . '/app/server/realmstatus-page.php';

$realmstatusPageState = spp_realmstatus_load_page_state(array(
    'realm_map' => $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? array()),
    'get' => $_GET,
    'skip_cache' => !empty($_GET['ajax']),
));

if (!empty($_GET['ajax'])) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(array(
        'ok' => true,
        'sourceRealmId' => (int)($realmstatusPageState['realmstatusSourceRealmId'] ?? 0),
        'polledAt' => (string)($realmstatusPageState['realmstatusPolledAtLabel'] ?? ''),
        'html' => (string)($realmstatusPageState['realmstatusListHtml'] ?? ''),
    ));
    exit;
}

extract($realmstatusPageState, EXTR_OVERWRITE);

$pathway_info[] = array('title' => 'Realm Status', 'link' => '');
?>
