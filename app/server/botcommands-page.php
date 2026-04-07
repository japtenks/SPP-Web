<?php

require_once __DIR__ . '/botcommands-data.php';
require_once __DIR__ . '/wbuffbuilder-page.php';

if (!function_exists('spp_server_normalize_botcommands_tab')) {
    function spp_server_normalize_botcommands_tab(array $get, string $sub = ''): string
    {
        $allowedTabs = array('strategies', 'vanilla', 'macros', 'filters', 'bot', 'commands', 'builder', 'wbuffs');
        $requestedTab = strtolower(trim((string)($get['tab'] ?? ($sub === 'commands' ? 'commands' : 'strategies'))));

        if (!in_array($requestedTab, $allowedTabs, true)) {
            return 'strategies';
        }

        return $requestedTab;
    }
}

if (!function_exists('spp_server_load_botcommands_page_state')) {
    function spp_server_load_botcommands_page_state(array $args = array()): array
    {
        $get = (array)($args['get'] ?? $_GET);
        $sub = (string)($args['sub'] ?? ($GLOBALS['sub'] ?? ''));
        $args['active_tab'] = spp_server_normalize_botcommands_tab($get, $sub);
        $botcommandsState = spp_server_build_botcommands_page_state($args);
        $wbuffbuilderState = spp_wbuffbuilder_load_page_state($args);

        $botcommandsState['worldBuffClasses'] = $wbuffbuilderState['worldBuffClasses'] ?? array();
        $botcommandsState['worldBuffSpellCatalog'] = $wbuffbuilderState['worldBuffSpellCatalog'] ?? array();

        return $botcommandsState;
    }
}
