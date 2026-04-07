<?php

require_once __DIR__ . '/admin-identities-helpers.php';
require_once __DIR__ . '/admin-identities-read.php';
require_once __DIR__ . '/admin-identities-actions.php';

if (!function_exists('spp_admin_identity_health_load_page_state')) {
    function spp_admin_identity_health_load_page_state(array $context = array()): array
    {
        global $pathway_info;

        if (!isset($pathway_info) || !is_array($pathway_info)) {
            $pathway_info = array();
        }
        $pathway_info[] = array('title' => 'Identity & Data Health', 'link' => '');

        $realmDbMap = (array)($context['realm_db_map'] ?? ($GLOBALS['realmDbMap'] ?? array()));
        $siteRoot = (string)($context['site_root'] ?? ($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__, 2)));
        $masterPdo = $context['master_pdo'] ?? spp_get_pdo('realmd', 1);
        if (!$masterPdo instanceof PDO) {
            throw new InvalidArgumentException('A valid master PDO is required for identity health.');
        }

        $requestedRealmId = isset($context['requested_realm_id'])
            ? (int)$context['requested_realm_id']
            : (int)($_REQUEST['identity_realm_id'] ?? ($_REQUEST['cleanup_realm_id'] ?? 0));

        $phpBin = spp_admin_identity_health_resolve_php_cli_binary();
        $isWindowsHost = DIRECTORY_SEPARATOR === '\\';
        $backfillState = spp_admin_identity_health_handle_backfill_action($siteRoot, $phpBin, $isWindowsHost, $realmDbMap);

        $view = spp_admin_identity_health_build_view($masterPdo, $requestedRealmId, $siteRoot, $backfillState);
        $csrfToken = spp_admin_identity_health_csrf_token('admin_identity_health');

        spp_admin_identity_health_handle_repair_action($masterPdo, (int)($view['selected_realm_id'] ?? 0));
        $view = spp_admin_identity_health_build_view($masterPdo, (int)($view['selected_realm_id'] ?? 0), $siteRoot, $backfillState);
        $view['csrf_token'] = $csrfToken;
        $view['is_windows_host'] = $isWindowsHost;

        return array(
            'identityHealthView' => $view,
        );
    }
}
