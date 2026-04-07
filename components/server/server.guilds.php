<?php
if (INCLUDED !== true) {
    exit;
}

require_once dirname(__DIR__, 2) . '/app/server/guilds-page.php';

$guildsPageState = spp_guilds_load_page_state(array(
    'realm_map' => $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? array()),
    'user' => $user ?? array(),
    'get' => $_GET,
    'post' => $_POST,
    'server_method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
));

extract($guildsPageState, EXTR_OVERWRITE);

$pathway_info[] = array('title' => 'Guilds', 'link' => '');
?>
