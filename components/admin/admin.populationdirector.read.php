<?php
if (INCLUDED !== true) {
    exit;
}

require_once __DIR__ . '/admin.populationdirector.helpers.php';

if (!function_exists('spp_admin_populationdirector_build_view')) {
    function spp_admin_populationdirector_build_view(PDO $masterPdo, array $realmDbMap, array $actionState = array()): array
    {
        spp_admin_populationdirector_ensure_runtime_tables($masterPdo);

        $selectedRealmId = 0;
        if (function_exists('spp_resolve_realm_id')) {
            $selectedRealmId = (int)spp_resolve_realm_id($realmDbMap);
        }
        if ($selectedRealmId <= 0 && !empty($realmDbMap)) {
            $selectedRealmId = (int)array_keys($realmDbMap)[0];
        }
        if (isset($_GET['realm'])) {
            $requestedRealmId = (int)$_GET['realm'];
            if ($requestedRealmId > 0 && isset($realmDbMap[$requestedRealmId])) {
                $selectedRealmId = $requestedRealmId;
            }
        }

        $bandCatalog = spp_admin_populationdirector_load_band_catalog($masterPdo);
        $activeBand = spp_admin_populationdirector_resolve_active_band($masterPdo, $bandCatalog);
        $realmContext = spp_admin_populationdirector_load_realm_context($masterPdo, $realmDbMap, $selectedRealmId);
        $overrideRow = spp_admin_populationdirector_load_override_row($masterPdo, $selectedRealmId, (string)($activeBand['band_key'] ?? ''));

        $charsPdo = null;
        try {
            $charsPdo = spp_get_pdo('chars', $selectedRealmId);
        } catch (Throwable $e) {
            $charsPdo = null;
        }

        $recommendationData = spp_admin_populationdirector_build_recommendations($masterPdo, $charsPdo, $selectedRealmId, $activeBand, $overrideRow, $realmContext);
        $candidates = (array)($recommendationData['candidates'] ?? array());
        $topCandidates = array_slice($candidates, 0, 5);

        $snapshots = array();
        if (spp_db_table_exists($masterPdo, spp_admin_populationdirector_snapshot_table_name())) {
            try {
                $stmt = $masterPdo->prepare(
                    'SELECT * FROM `' . spp_admin_populationdirector_snapshot_table_name() . '`
                     WHERE `realm_id` = ?
                     ORDER BY `created_at` DESC, `snapshot_id` DESC
                     LIMIT 10'
                );
                $stmt->execute(array($selectedRealmId));
                $snapshots = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: array();
            } catch (Throwable $e) {
                $snapshots = array();
            }
        }

        $auditHistory = array();
        if (spp_db_table_exists($masterPdo, spp_admin_populationdirector_audit_table_name())) {
            try {
                $stmt = $masterPdo->prepare(
                    'SELECT * FROM `' . spp_admin_populationdirector_audit_table_name() . '`
                     WHERE `realm_id` = ?
                     ORDER BY `created_at` DESC, `audit_id` DESC
                     LIMIT 20'
                );
                $stmt->execute(array($selectedRealmId));
                $auditHistory = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: array();
            } catch (Throwable $e) {
                $auditHistory = array();
            }
        }

        if (!isset($GLOBALS['pathway_info']) || !is_array($GLOBALS['pathway_info'])) {
            $GLOBALS['pathway_info'] = array();
        }
        $GLOBALS['pathway_info'][] = array('title' => 'Population Director', 'link' => 'index.php?n=admin&sub=populationdirector');

        $realmOptions = array();
        foreach ($realmDbMap as $realmId => $realmInfo) {
            $realmId = (int)$realmId;
            if ($realmId <= 0) {
                continue;
            }
            $realmOptions[] = array(
                'id' => $realmId,
                'label' => spp_admin_populationdirector_realmd_name((array)$realmInfo, $realmId),
            );
        }

        $support = array(
            'website_identities' => spp_db_table_exists($masterPdo, 'website_identities'),
            'website_identity_profiles' => spp_db_table_exists($masterPdo, function_exists('spp_identity_profile_table_name') ? spp_identity_profile_table_name() : 'website_identity_profiles'),
            'characters' => false,
            'online_column' => false,
            'overrides_table' => spp_db_table_exists($masterPdo, spp_admin_populationdirector_override_table_name()),
            'snapshots_table' => spp_db_table_exists($masterPdo, spp_admin_populationdirector_snapshot_table_name()),
            'audit_table' => spp_db_table_exists($masterPdo, spp_admin_populationdirector_audit_table_name()),
        );
        if ($charsPdo instanceof PDO) {
            $support['characters'] = spp_db_table_exists($charsPdo, 'characters');
            $support['online_column'] = $support['characters'] && spp_db_column_exists($charsPdo, 'characters', 'online');
        }

        return array(
            'page_title' => 'Population Director',
            'selected_realm_id' => $selectedRealmId,
            'realm_options' => $realmOptions,
            'realm_context' => $realmContext,
            'band_catalog' => $bandCatalog,
            'active_band' => $activeBand,
            'override_row' => $overrideRow,
            'recommendations' => $recommendationData,
            'top_candidates' => $topCandidates,
            'snapshot_history' => $snapshots,
            'audit_history' => $auditHistory,
            'support' => $support,
            'warnings' => array_values(array_unique(array_merge(
                (array)($recommendationData['warnings'] ?? array()),
                array_values((array)($actionState['warnings'] ?? array()))
            ))),
            'notice' => (string)($actionState['notice'] ?? ''),
            'errors' => (array)($actionState['errors'] ?? array()),
            'saved_snapshot_id' => (int)($actionState['saved_snapshot_id'] ?? 0),
            'admin_populationdirector_csrf_token' => spp_csrf_token('admin_populationdirector'),
        );
    }
}
