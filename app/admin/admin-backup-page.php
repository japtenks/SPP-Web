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
            'target_character_name' => trim((string)($request['target_character_name'] ?? '')),
        );
    }
}
