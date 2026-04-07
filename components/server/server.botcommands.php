<?php
if (INCLUDED !== true) {
    exit;
}

require_once dirname(__DIR__, 2) . '/app/server/botcommands-page.php';

$botcommandsPageState = spp_server_load_botcommands_page_state(array(
    'pdo' => $pdo ?? ($GLOBALS['pdo'] ?? null),
    'world_db' => $world_db ?? ($GLOBALS['world_db'] ?? ''),
    'user' => $user ?? ($GLOBALS['user'] ?? array()),
    'get' => $_GET,
    'sub' => $sub ?? ($GLOBALS['sub'] ?? ''),
));

extract($botcommandsPageState, EXTR_OVERWRITE);

$pathway_info[] = array('title' => $lang['bot_commands'] ?? 'Bot Guide', 'link' => '');
?>
