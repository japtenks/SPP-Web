<?php

require_once __DIR__ . '/../../components/admin/admin.backup.helpers.php';
require_once __DIR__ . '/../../components/admin/admin.backup.read.php';
require_once __DIR__ . '/../../components/admin/admin.backup.actions.php';

if (!function_exists('spp_admin_backup_load_page_state')) {
    function spp_admin_backup_load_page_state(array $context = array()): array
    {
        $realmDbMap = (array)($context['realm_db_map'] ?? ($GLOBALS['realmDbMap'] ?? array()));
        $request = is_array($context['request'] ?? null) ? (array)$context['request'] : null;
        if ($request === null) {
            $requestMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
            $request = $requestMethod === 'POST' ? $_POST : $_GET;
        }
        if (!is_array($request)) {
            $request = array();
        }
        $backupView = spp_admin_backup_build_view($realmDbMap, $request);
        $backupActionState = spp_admin_backup_handle_action($backupView);

        if (!empty($_GET['backup_lookup'])) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array(
                'realm_options' => array_values((array)$backupView['realm_options']),
                'xfer_route_options' => array_values((array)$backupView['xfer_route_options']),
                'xfer_entity_options' => (array)($backupView['xfer_entity_options'] ?? array()),
                'source_account_options' => array_values((array)$backupView['source_account_options']),
                'source_bot_options' => array_values((array)($backupView['source_bot_options'] ?? array())),
                'source_character_options' => array_values((array)$backupView['source_character_options']),
                'source_bot_character_options' => array_values((array)($backupView['source_bot_character_options'] ?? array())),
                'source_guild_options' => array_values((array)$backupView['source_guild_options']),
                'target_account_options' => array_values((array)$backupView['target_account_options']),
                'selected_account_id' => (int)$backupView['selected_account_id'],
                'selected_bot_account_id' => (int)($backupView['selected_bot_account_id'] ?? 0),
                'selected_character_guid' => (int)$backupView['selected_character_guid'],
                'selected_guild_id' => (int)$backupView['selected_guild_id'],
                'selected_target_account_id' => (int)$backupView['selected_target_account_id'],
                'selected_xfer_route_id' => (string)$backupView['selected_xfer_route_id'],
                'selected_xfer_entity_type' => (string)($backupView['selected_xfer_entity_type'] ?? $backupView['xfer_entity_type'] ?? 'character'),
                'target_realm_id' => (int)$backupView['target_realm_id'],
                'source_realm_id' => (int)$backupView['source_realm_id'],
                'xfer_route_help' => (string)($backupView['xfer_route_help'] ?? ''),
                'source_character_count' => count((array)($backupView['source_character_options'] ?? array())),
                'source_bot_character_count' => count((array)($backupView['source_bot_character_options'] ?? array())),
                'selected_account_username' => (string)(($backupView['selected_account_row']['username'] ?? '') ?: ''),
                'selected_bot_username' => (string)(($backupView['selected_bot_account_row']['username'] ?? '') ?: ''),
                'selected_guild_summary' => (array)($backupView['selected_guild_summary'] ?? array()),
            ));
            exit;
        }

        $downloadFile = spp_admin_backup_basename((string)($_GET['download_file'] ?? ''));
        if ($downloadFile !== '') {
            if (!preg_match('/\.(sql|txt|bat|vbs)$/i', $downloadFile)) {
                http_response_code(404);
                exit('File not found');
            }

            $path = spp_admin_backup_output_dir() . DIRECTORY_SEPARATOR . $downloadFile;
            if (!is_file($path) || !is_readable($path)) {
                http_response_code(404);
                exit('File not found');
            }

            $extension = strtolower((string)pathinfo($path, PATHINFO_EXTENSION));
            $contentType = $extension === 'sql'
                ? 'application/sql; charset=utf-8'
                : 'text/plain; charset=utf-8';
            header('Content-Type: ' . $contentType);
            header('Content-Length: ' . (string)filesize($path));
            header('Content-Disposition: attachment; filename="' . str_replace('"', '', $downloadFile) . '"');
            readfile($path);
            exit;
        }

        if (!isset($GLOBALS['pathway_info']) || !is_array($GLOBALS['pathway_info'])) {
            $GLOBALS['pathway_info'] = array();
        }
        $GLOBALS['pathway_info'][] = array('title' => 'Backup', 'link' => spp_admin_backup_admin_url());

        return array(
            'backupView' => $backupView,
            'backupActionState' => $backupActionState,
            'admin_backup_csrf_token' => spp_csrf_token('admin_backup'),
            'backup_action_url' => spp_admin_backup_admin_url(),
            'backup_lookup_url' => spp_admin_backup_admin_url(array('backup_lookup' => 1)),
            'target_character_name' => trim((string)($_POST['target_character_name'] ?? '')),
        );
    }
}
