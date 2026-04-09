<?php

require_once __DIR__ . '/../../components/admin/admin.botrotation.read.php';

if (!function_exists('spp_admin_botrotation_load_page_state')) {
    function spp_admin_botrotation_load_page_state(array $context = array()): array
    {
        $realmDbMap = (array)($context['realm_db_map'] ?? ($GLOBALS['realmDbMap'] ?? array()));
        $botRotationView = spp_admin_botrotation_build_view($realmDbMap);

        if (!isset($GLOBALS['pathway_info']) || !is_array($GLOBALS['pathway_info'])) {
            $GLOBALS['pathway_info'] = array();
        }
        $GLOBALS['pathway_info'][] = array('title' => 'Bot Rotation Health', 'link' => 'index.php?n=admin&sub=botrotation');

        return array(
            'botRotationView' => $botRotationView,
            'realmId' => (int)($botRotationView['realmId'] ?? 0),
            'realmName' => (string)($botRotationView['realmName'] ?? ('Realm #' . (int)($botRotationView['realmId'] ?? 0))),
            'rotationData' => $botRotationView['rotationData'] ?? null,
            'rotationError' => $botRotationView['rotationError'] ?? null,
            'rotationConfig' => $botRotationView['rotationConfig'] ?? null,
            'latestHistory' => $botRotationView['latestHistory'] ?? null,
            'topBotData' => $botRotationView['topBotData'] ?? null,
            'totalServerUptime' => $botRotationView['totalServerUptime'] ?? 'N/A',
            'currentRunSec' => $botRotationView['currentRunSec'] ?? null,
            'restartsToday' => $botRotationView['restartsToday'] ?? null,
            'historyRows' => (array)($botRotationView['historyRows'] ?? array()),
            'hasHistory' => !empty($botRotationView['hasHistory']),
            'liveOnlineAvg' => $botRotationView['liveOnlineAvg'] ?? null,
            'liveOnlineMax' => $botRotationView['liveOnlineMax'] ?? null,
            'uptimeSummary' => (array)($botRotationView['uptimeSummary'] ?? array()),
            'cleanHistory' => (array)($botRotationView['cleanHistory'] ?? array()),
            'longestOnlineBot' => $botRotationView['longestOnlineBot'] ?? null,
            'longestOfflineBot' => $botRotationView['longestOfflineBot'] ?? null,
            'rotationCommands' => (array)($botRotationView['commands'] ?? array()),
            'rotationCommandAvailability' => (array)($botRotationView['commandAvailability'] ?? array()),
            'isWindowsHost' => !empty($botRotationView['isWindowsHost']),
        );
    }
}
