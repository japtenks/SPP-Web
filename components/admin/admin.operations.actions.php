<?php
if (INCLUDED !== true) {
    exit;
}

require_once __DIR__ . '/admin.operations.helpers.php';

if (!function_exists('spp_admin_operations_handle_action')) {
    function spp_admin_operations_handle_action(PDO $masterPdo, array $realmDbMap): array
    {
        $state = array(
            'notice' => '',
            'queued_job_id' => 0,
        );

        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            return $state;
        }

        if ((string)($_POST['operations_action'] ?? '') !== 'queue_job') {
            return $state;
        }

        spp_require_csrf('admin_operations');

        $operationId = trim((string)($_POST['operation_id'] ?? ''));
        $descriptor = spp_admin_operations_descriptor($operationId, $realmDbMap);
        if (empty($descriptor)) {
            $state['notice'] = 'Unknown operation requested.';
            return $state;
        }
        if (!spp_admin_operations_is_queueable($descriptor)) {
            $state['notice'] = !empty($descriptor['is_link_only'])
                ? 'This operation is a native link-out. Open the linked tool instead of queueing a job.'
                : 'This operation is intentionally deferred from v1 execution and cannot be queued yet.';
            return $state;
        }

        $realmMap = spp_admin_operations_realm_option_map(spp_admin_operations_realm_options($realmDbMap));
        $inputs = array(
            'realm_id' => (int)($_POST['realm_id'] ?? 0),
            'source_realm_id' => (int)($_POST['source_realm_id'] ?? 0),
            'target_realm_id' => (int)($_POST['target_realm_id'] ?? 0),
            'scope_profile' => trim((string)($_POST['scope_profile'] ?? '')),
            'value' => trim((string)($_POST['value'] ?? '')),
            'confirmation_phrase' => trim((string)($_POST['confirmation_phrase'] ?? '')),
        );

        $preview = spp_admin_operations_render_preview($descriptor, $inputs, $realmMap);
        $prechecks = spp_admin_operations_precheck_results($descriptor, $inputs, $realmMap);
        $allChecksPass = true;
        $verificationSummary = array();
        foreach ($prechecks as $check) {
            $allChecksPass = $allChecksPass && !empty($check['ok']);
            $verificationSummary[] = sprintf(
                '[%s] %s - %s',
                !empty($check['ok']) ? 'pass' : 'fail',
                (string)$check['label'],
                (string)$check['detail']
            );
        }

        $status = $allChecksPass ? 'queued' : 'blocked';
        $stmt = $masterPdo->prepare(
            'INSERT INTO `' . spp_admin_operations_queue_table_name() . '` (
                operation_id, operation_label, operation_category, risk_level, realm_scope,
                realm_id, source_realm_id, target_realm_id, execution_mode, submitted_by,
                submitted_inputs_json, preview_text, execution_log, verification_summary, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute(array(
            (string)$descriptor['id'],
            (string)$descriptor['label'],
            (string)$descriptor['family_id'],
            (string)$descriptor['risk_level'],
            (string)$descriptor['ownership_scope'],
            (int)$inputs['realm_id'],
            (int)$inputs['source_realm_id'],
            (int)$inputs['target_realm_id'],
            (string)$descriptor['execution_mode'],
            (int)($GLOBALS['user']['id'] ?? 0),
            json_encode($inputs),
            $preview,
            '',
            implode("\n", $verificationSummary),
            $status,
        ));

        $state['queued_job_id'] = (int)$masterPdo->lastInsertId();
        $state['notice'] = $allChecksPass
            ? 'Operation queued with preview and verification notes.'
            : 'Operation recorded as blocked. Review the failed prechecks before retrying.';

        return $state;
    }
}
