<?php

require_once __DIR__ . '/../../components/admin/admin.playerbots.helpers.php';
require_once __DIR__ . '/../../components/admin/admin.operations.helpers.php';
require_once __DIR__ . '/admin-playerbots-actions.php';
require_once __DIR__ . '/admin-playerbots-read.php';

if (!function_exists('spp_admin_playerbots_load_page_state')) {
    function spp_admin_playerbots_load_page_state(array $context = array()): array
    {
        $realmDbMap = (array)($context['realm_db_map'] ?? ($GLOBALS['realmDbMap'] ?? array()));
        $realmId = spp_resolve_realm_id($realmDbMap);
        if ($realmId <= 0 && !empty($realmDbMap)) {
            $realmKeys = array_keys($realmDbMap);
            $realmId = (int)$realmKeys[0];
        }

        $charsPdo = spp_get_pdo('chars', $realmId);
        spp_admin_playerbots_handle_action($charsPdo, $realmId);

        $view = spp_admin_playerbots_build_view($realmDbMap);
        $allowedModes = array('soap', 'sql', 'sql_fallback');
        $readMode = static function (string $key) use ($allowedModes): string {
            $mode = trim((string)($_GET[$key] ?? ''));
            return in_array($mode, $allowedModes, true) ? $mode : '';
        };

        return array_merge($view, array(
            'admin_playerbots_csrf_token' => spp_csrf_token('admin_playerbots'),
            'playerbotOperationsHref' => 'index.php?n=admin&sub=operations#bot-maintenance',
            'meetingSaved' => isset($_GET['meeting_saved']) && (string)$_GET['meeting_saved'] === '1',
            'meetingWriteMode' => $readMode('meeting_mode'),
            'shareSaved' => isset($_GET['share_saved']) && (string)$_GET['share_saved'] === '1',
            'shareWriteMode' => $readMode('share_mode'),
            'notesSaved' => isset($_GET['notes_saved']) && (string)$_GET['notes_saved'] === '1',
            'notesWriteMode' => $readMode('notes_mode'),
            'personalitySaved' => isset($_GET['personality_saved']) && (string)$_GET['personality_saved'] === '1',
            'personalityWriteMode' => $readMode('personality_mode'),
            'forumToneSaved' => isset($_GET['forum_tone_saved']) && (string)$_GET['forum_tone_saved'] === '1',
            'forumToneWriteMode' => $readMode('forum_tone_mode'),
            'botStrategySaved' => isset($_GET['bot_strategy_saved']) && (string)$_GET['bot_strategy_saved'] === '1',
            'botStrategyWriteMode' => $readMode('bot_strategy_mode'),
            'strategySaved' => isset($_GET['strategy_saved']) && (string)$_GET['strategy_saved'] === '1',
            'strategyWriteMode' => $readMode('strategy_mode'),
            'seededMembers' => isset($_GET['seeded_members']) ? max(0, (int)$_GET['seeded_members']) : 0,
            'seedFailedMembers' => isset($_GET['seed_failed_members']) ? max(0, (int)$_GET['seed_failed_members']) : 0,
        ));
    }
}
