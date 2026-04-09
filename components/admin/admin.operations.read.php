<?php
if (INCLUDED !== true) {
    exit;
}

require_once __DIR__ . '/admin.operations.helpers.php';

if (!function_exists('spp_admin_operations_default_selection')) {
    function spp_admin_operations_default_selection(array $descriptors): array
    {
        foreach ($descriptors as $descriptor) {
            if (spp_admin_operations_is_queueable($descriptor)) {
                return $descriptor;
            }
        }

        return !empty($descriptors) ? (array)reset($descriptors) : array();
    }
}

if (!function_exists('spp_admin_operations_build_view')) {
    function spp_admin_operations_build_view(PDO $masterPdo, array $realmDbMap, array $actionState = array()): array
    {
        $descriptors = spp_admin_operations_descriptors($realmDbMap);
        $familyLabels = spp_admin_operations_family_labels();
        $familyDescriptions = spp_admin_operations_family_descriptions();
        $familyOrder = spp_admin_operations_family_order();
        $realmOptions = spp_admin_operations_realm_options($realmDbMap);
        $realmMap = spp_admin_operations_realm_option_map($realmOptions);
        $grouped = array();

        foreach ($familyOrder as $familyId) {
            $grouped[$familyId] = array(
                'id' => $familyId,
                'label' => (string)($familyLabels[$familyId] ?? $familyId),
                'description' => (string)($familyDescriptions[$familyId] ?? ''),
                'items' => array(),
            );
        }

        foreach ($descriptors as $descriptor) {
            $familyId = (string)($descriptor['family_id'] ?? 'conversion_import_export');
            if (!isset($grouped[$familyId])) {
                $grouped[$familyId] = array(
                    'id' => $familyId,
                    'label' => (string)($familyLabels[$familyId] ?? $familyId),
                    'description' => (string)($familyDescriptions[$familyId] ?? ''),
                    'items' => array(),
                );
            }
            $grouped[$familyId]['items'][] = $descriptor;
        }

        $requestedOperationId = trim((string)($_GET['operation'] ?? ''));
        $selectedOperation = $requestedOperationId !== '' ? (array)($descriptors[$requestedOperationId] ?? array()) : array();
        if (empty($selectedOperation)) {
            $selectedOperation = spp_admin_operations_default_selection($descriptors);
        }

        $jobHistory = array();
        $jobDetail = null;
        $jobId = (int)($_GET['job'] ?? 0);

        $stmt = $masterPdo->query(
            'SELECT * FROM `' . spp_admin_operations_queue_table_name() . '` ORDER BY `created_at` DESC, `id` DESC LIMIT 20'
        );
        if ($stmt) {
            $jobHistory = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: array();
        }

        if ($jobId > 0) {
            $detailStmt = $masterPdo->prepare(
                'SELECT * FROM `' . spp_admin_operations_queue_table_name() . '` WHERE `id` = ? LIMIT 1'
            );
            $detailStmt->execute(array($jobId));
            $jobDetail = $detailStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        $selectedOperationPreview = array();
        if (!empty($selectedOperation)) {
            $defaultRealmId = !empty($realmOptions[0]['id']) ? (int)$realmOptions[0]['id'] : 0;
            $secondRealmId = !empty($realmOptions[1]['id']) ? (int)$realmOptions[1]['id'] : $defaultRealmId;
            $defaultInputs = array(
                'realm_id' => $defaultRealmId,
                'source_realm_id' => $defaultRealmId,
                'target_realm_id' => $secondRealmId,
                'scope_profile' => 'bots',
                'value' => '',
                'confirmation_phrase' => '',
            );
            $selectedOperationPreview = array(
                'preview_text' => spp_admin_operations_render_preview($selectedOperation, $defaultInputs, $realmMap),
                'confirmation_phrase' => spp_admin_operations_confirmation_phrase((string)($selectedOperation['id'] ?? '')),
            );
        }

        $GLOBALS['pathway_info'][] = array('title' => 'Operations', 'link' => 'index.php?n=admin&sub=operations');

        return array(
            'operationsIntro' => array(
                'eyebrow' => 'Operations',
                'title' => 'Database Maintenance Operations',
                'body' => 'Use Operations as the reviewed control plane for destructive and cleanup database work. Native admin pages stay linked here when they remain the source of truth, and deferred families stay visible without pretending to be executable v1 workflows.',
            ),
            'operationGroups' => array_values($grouped),
            'selectedOperation' => $selectedOperation,
            'selectedOperationPreview' => $selectedOperationPreview,
            'operationRealmOptions' => $realmOptions,
            'operationsNotice' => (string)($actionState['notice'] ?? ''),
            'queuedOperationJobId' => (int)($actionState['queued_job_id'] ?? 0),
            'operationJobHistory' => $jobHistory,
            'operationJobDetail' => $jobDetail,
        );
    }
}
