<?php

require_once __DIR__ . '/../../components/admin/admin.backup.helpers.php';
require_once __DIR__ . '/../../components/admin/admin.backup.read.php';
require_once __DIR__ . '/../../components/admin/admin.backup.actions.php';

if (!function_exists('spp_admin_backup_load_page_state')) {
    function spp_admin_backup_load_page_state(array $context = array()): array
    {
        $realmDbMap = (array)($context['realm_db_map'] ?? ($GLOBALS['realmDbMap'] ?? array()));
        $request = is_array($context['request'] ?? null) ? (array)$context['request'] : null;
        $backupView = spp_admin_backup_build_view($realmDbMap, $request);
        $backupActionState = spp_admin_backup_handle_action($backupView);

        if (!isset($GLOBALS['pathway_info']) || !is_array($GLOBALS['pathway_info'])) {
            $GLOBALS['pathway_info'] = array();
        }
        $GLOBALS['pathway_info'][] = array('title' => 'Backup', 'link' => 'index.php?n=admin&sub=backup');

        return array(
            'backupView' => $backupView,
            'backupActionState' => $backupActionState,
            'admin_backup_csrf_token' => spp_csrf_token('admin_backup'),
            'backup_action_url' => 'index.php?n=admin&sub=backup',
            'backup_lookup_url' => 'components/admin/admin.backup.lookup.php',
            'target_character_name' => trim((string)($_POST['target_character_name'] ?? '')),
        );
    }
}
