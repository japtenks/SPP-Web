<?php

require_once __DIR__ . '/../../components/admin/admin.botevents.helpers.php';
require_once __DIR__ . '/../../components/admin/admin.botevents.actions.php';
require_once __DIR__ . '/../../components/admin/admin.botevents.read.php';

if (!function_exists('spp_admin_botevents_normalize_selected_event_types')) {
    function spp_admin_botevents_normalize_selected_event_types($selectedEventTypes): array
    {
        if (!is_array($selectedEventTypes)) {
            $selectedEventTypes = array($selectedEventTypes);
        }

        return array_values(array_unique(array_filter(array_map('strval', $selectedEventTypes), static function ($value) {
            return $value !== '';
        })));
    }
}

if (!function_exists('spp_admin_botevents_load_page_state')) {
    function spp_admin_botevents_load_page_state(array $context = array()): array
    {
        $realmDbMap = (array)($context['realm_db_map'] ?? ($GLOBALS['realmDbMap'] ?? array()));
        $masterPdo = $context['master_pdo'] ?? spp_get_pdo('realmd', 1);
        $siteRoot = (string)($context['site_root'] ?? dirname(__DIR__, 2));
        $selectedEventTypes = spp_admin_botevents_normalize_selected_event_types($context['selected_event_types'] ?? ($_GET['event_types'] ?? array()));
        $processLimitValue = trim((string)($context['process_limit'] ?? ($_GET['process_limit'] ?? '')));
        $isWindowsHost = DIRECTORY_SEPARATOR === '\\';
        $activeTab = (string)($_REQUEST['tab'] ?? 'pipeline');
        $phpBin = spp_admin_botevents_resolve_php_cli_binary();

        $actionState = spp_admin_botevents_handle_action($siteRoot, $phpBin, $isWindowsHost, $selectedEventTypes, $processLimitValue, $realmDbMap);
        $viewState = spp_admin_botevents_build_view($masterPdo, $selectedEventTypes, $realmDbMap, $actionState['configDraft'] ?? null);

        if (!isset($GLOBALS['pathway_info']) || !is_array($GLOBALS['pathway_info'])) {
            $GLOBALS['pathway_info'] = array();
        }
        $GLOBALS['pathway_info'][] = array('title' => 'Bot Events', 'link' => 'index.php?n=admin&sub=botevents');

        return array(
            'botOutput' => (string)($actionState['botOutput'] ?? ''),
            'botError' => trim((string)($actionState['botError'] ?? '') . (string)($viewState['statsError'] ?? '')),
            'botNotice' => (string)($actionState['botNotice'] ?? ''),
            'botCommand' => (string)($actionState['botCommand'] ?? ''),
            'activeTab' => (string)($actionState['activeTab'] ?? $activeTab),
            'configError' => (string)($actionState['configError'] ?? ''),
            'botStats' => (array)($viewState['botStats'] ?? array()),
            'recentEvents' => (array)($viewState['recentEvents'] ?? array()),
            'availableEventTypes' => (array)($viewState['availableEventTypes'] ?? array()),
            'selectedEventTypes' => (array)($viewState['selectedEventTypes'] ?? array()),
            'pendingTypeBreakdown' => (array)($viewState['pendingTypeBreakdown'] ?? array()),
            'botConfig' => (array)($viewState['botConfig'] ?? array()),
            'realmOptions' => (array)($viewState['realmOptions'] ?? array()),
            'configPath' => (string)($viewState['configPath'] ?? ''),
            'configLoadError' => (string)($viewState['configLoadError'] ?? ''),
            'configWritable' => !empty($viewState['configWritable']),
            'achievementCatalog' => (array)($viewState['achievementCatalog'] ?? array()),
            'processLimitValue' => $processLimitValue,
            'isWindowsHost' => $isWindowsHost,
            'configSaved' => isset($_GET['saved']) && (string)$_GET['saved'] === '1',
            'admin_botevents_csrf_token' => spp_csrf_token('admin_botevents'),
        );
    }
}
