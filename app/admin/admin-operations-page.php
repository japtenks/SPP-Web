<?php

require_once __DIR__ . '/../../components/admin/admin.operations.helpers.php';
require_once __DIR__ . '/admin-operations-actions.php';
require_once __DIR__ . '/admin-operations-read.php';

if (!function_exists('spp_admin_operations_load_page_state')) {
    function spp_admin_operations_load_page_state(array $context = array()): array
    {
        $realmDbMap = (array)($context['realm_db_map'] ?? ($GLOBALS['realmDbMap'] ?? array()));
        $masterPdo = $context['master_pdo'] ?? spp_get_pdo('realmd', 1);

        spp_admin_operations_ensure_jobs_table($masterPdo);
        $actionState = spp_admin_operations_handle_action($masterPdo, $realmDbMap);
        $view = spp_admin_operations_build_view($masterPdo, $realmDbMap, $actionState);

        return array_merge($view, array(
            'admin_operations_csrf_token' => spp_csrf_token('admin_operations'),
        ));
    }
}
