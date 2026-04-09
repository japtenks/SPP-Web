<?php

require_once __DIR__ . '/../../components/admin/admin.bots.helpers.php';
require_once __DIR__ . '/../../components/admin/admin.bots.actions.php';
require_once __DIR__ . '/../../components/admin/admin.bots.read.php';

if (!function_exists('spp_admin_bots_load_page_state')) {
    function spp_admin_bots_load_page_state(array $context = array()): array
    {
        $realmDbMap = (array)($context['realm_db_map'] ?? ($GLOBALS['realmDbMap'] ?? array()));
        $masterPdo = $context['master_pdo'] ?? spp_get_pdo('realmd', 1);

        if (!isset($GLOBALS['pathway_info']) || !is_array($GLOBALS['pathway_info'])) {
            $GLOBALS['pathway_info'] = array();
        }
        $GLOBALS['pathway_info'][] = array('title' => 'Bot Maintenance', 'link' => 'index.php?n=admin&sub=bots');

        $actionState = spp_admin_bots_handle_action($masterPdo);
        $viewState = spp_admin_bots_build_view($masterPdo, $realmDbMap, $actionState);

        return array_merge($viewState, array(
            'csrf_token' => spp_csrf_token('admin_bots'),
            'is_windows_host' => DIRECTORY_SEPARATOR === '\\',
        ));
    }
}
