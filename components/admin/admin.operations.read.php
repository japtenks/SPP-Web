<?php
if (INCLUDED !== true) {
    exit;
}

require_once __DIR__ . '/admin.operations.helpers.php';

if (!function_exists('spp_admin_operations_build_view')) {
    function spp_admin_operations_build_view(PDO $masterPdo, array $realmDbMap, array $actionState = array()): array
    {
        $descriptors = spp_admin_operations_descriptors($realmDbMap);
        $categoryLabels = spp_admin_operations_category_labels();
        $realmOptions = spp_admin_operations_realm_options($realmDbMap);
        $realmMap = spp_admin_operations_realm_option_map($realmOptions);
        $grouped = array();

        foreach ($descriptors as $descriptor) {
            $category = (string)($descriptor['category'] ?? 'misc');
            if (!isset($grouped[$category])) {
                $grouped[$category] = array(
                    'id' => $category,
                    'label' => (string)($categoryLabels[$category] ?? $category),
                    'items' => array(),
                );
            }
            $grouped[$category]['items'][] = $descriptor;
        }

        $requestedOperationId = trim((string)($_GET['operation'] ?? ''));
        $selectedOperation = $requestedOperationId !== '' ? (array)($descriptors[$requestedOperationId] ?? array()) : array();
        if (empty($selectedOperation) && !empty($descriptors)) {
            $selectedOperation = reset($descriptors);
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
            $defaultInputs = array(
                'realm_id' => !empty($realmOptions[0]['id']) ? (int)$realmOptions[0]['id'] : 0,
                'source_realm_id' => !empty($realmOptions[0]['id']) ? (int)$realmOptions[0]['id'] : 0,
                'target_realm_id' => !empty($realmOptions[1]['id']) ? (int)$realmOptions[1]['id'] : (!empty($realmOptions[0]['id']) ? (int)$realmOptions[0]['id'] : 0),
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
                'title' => 'Launcher-Parity Operations Catalog',
                'body' => 'Use the website as the control plane for reviewed jobs. Safe native tools stay linked here, while SQL-backed and external-tool workflows queue with previews, scope, and verification notes.',
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
