<?php
if (INCLUDED !== true) {
    exit;
}

require_once dirname(__DIR__, 2) . '/app/support/db-schema.php';
require_once __DIR__ . '/admin.realms.helpers.php';

function spp_admin_realms_handle_action(PDO $realmsPdo, array $realmDbMap = array()): array
{
    $action = (string)($_GET['action'] ?? '');
    $realmId = (int)($_GET['id'] ?? 0);
    $requestMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $state = array();

    if ($action === '' || $action === '0' || $action === 'edit') {
        return $state;
    }

    if ($requestMethod !== 'POST') {
        return $state;
    }

    if ($action === 'runtime-save') {
        spp_require_csrf('admin_realms');
        $runtimeForm = spp_admin_realms_runtime_form_state($realmDbMap, $_POST);
        $result = spp_admin_realms_save_runtime_settings(
            $realmDbMap,
            $runtimeForm,
            (string)($GLOBALS['user']['username'] ?? $GLOBALS['user']['name'] ?? 'admin')
        );

        if (empty($result['valid'])) {
            $errors = array_values(array_filter((array)($result['errors'] ?? array())));
            $state['runtime_form'] = $runtimeForm;
            $state['runtime_errors'] = $errors;
            return $state;
        }

        redirect('index.php?n=admin&sub=realms', 1);
        exit;
    }

    if ($action === 'update' && $realmId > 0) {
        spp_require_csrf('admin_realms');
        $data = spp_admin_realms_normalize_fields($_POST);
        $columns = array('port', 'icon', 'realmflags', 'timezone', 'allowedSecurityLevel', 'population', 'realmbuilds');
        $assignments = array();
        $values = array();
        foreach ($columns as $column) {
            if (spp_db_column_exists($realmsPdo, 'realmlist', $column)) {
                $assignments[] = '`' . $column . '` = ?';
                $values[] = $data[$column];
            }
        }
        if (!empty($assignments)) {
            $values[] = $realmId;
            $stmt = $realmsPdo->prepare('UPDATE `realmlist` SET ' . implode(', ', $assignments) . ' WHERE `id` = ? LIMIT 1');
            $stmt->execute($values);
        }
        redirect('index.php?n=admin&sub=realms', 1);
        exit;
    }

    if ($action === 'create') {
        spp_require_csrf('admin_realms');
        $data = spp_admin_realms_normalize_fields($_POST);
        $columns = array('name', 'address', 'port', 'icon', 'realmflags', 'timezone', 'allowedSecurityLevel', 'population', 'realmbuilds');
        $insertColumns = array();
        $placeholders = array();
        $values = array();
        foreach ($columns as $column) {
            if (spp_db_column_exists($realmsPdo, 'realmlist', $column)) {
                $insertColumns[] = '`' . $column . '`';
                $placeholders[] = '?';
                $values[] = $data[$column];
            }
        }
        if (!empty($insertColumns)) {
            $stmt = $realmsPdo->prepare(
                'INSERT INTO `realmlist` (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $placeholders) . ')'
            );
            $stmt->execute($values);
        }
        redirect('index.php?n=admin&sub=realms', 1);
        exit;
    }

    if ($action === 'delete' && $realmId > 0) {
        spp_require_csrf('admin_realms');
        $stmt = $realmsPdo->prepare("DELETE FROM realmlist WHERE id=? LIMIT 1");
        $stmt->execute([$realmId]);
        redirect('index.php?n=admin&sub=realms', 1);
        exit;
    }

    return $state;
}
