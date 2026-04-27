<?php
if (!defined('INCLUDED') || INCLUDED !== true) {
    exit;
}

require_once dirname(__DIR__, 2) . '/app/server/personality-page.php';

$personalityPageState = spp_personality_load_page_state(array(
    'realm_map' => $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? array()),
    'get' => $_GET,
));

extract($personalityPageState, EXTR_OVERWRITE);

$pathway_info[] = array('title' => 'Personality Feed', 'link' => '');
?>
