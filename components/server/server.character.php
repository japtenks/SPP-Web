<?php
if (INCLUDED !== true) exit;

require_once dirname(__DIR__, 2) . '/app/server/character-page.php';

$characterPageState = spp_character_load_page_state(array(
    'realm_map' => $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? array()),
    'user' => $user ?? ($GLOBALS['user'] ?? array()),
    'get' => $_GET,
    'post' => $_POST,
    'server_method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
));

extract($characterPageState, EXTR_OVERWRITE);

$pathway_info[] = array('title' => 'Characters', 'link' => spp_route_url('server', 'chars'));
$pathway_info[] = array('title' => 'Character Profile', 'link' => '');
?>
