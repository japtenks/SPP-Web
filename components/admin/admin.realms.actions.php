<?php
if (INCLUDED !== true) {
    exit;
}

require_once dirname(__DIR__, 2) . '/app/support/db-schema.php';
require_once __DIR__ . '/admin.realms.helpers.php';

if (!function_exists('spp_admin_realms_runtime_definition_defaults')) {
    function spp_admin_realms_runtime_definition_defaults(int $realmId = 0): array
    {
        return array(
            'id' => $realmId,
            'name' => '',
            'address' => '',
            'port' => 8085,
            'realmd' => '',
            'world' => '',
            'chars' => '',
            'armory' => '',
            'bots' => '',
            'icon' => 0,
            'realmflags' => 0,
            'timezone' => 1,
            'allowedSecurityLevel' => 0,
            'population' => '0',
            'realmbuilds' => '',
            'enabled' => 1,
            'make_default' => 0,
            'delete_realmlist_row' => 0,
        );
    }
}

if (!function_exists('spp_admin_realms_runtime_slot_form')) {
    function spp_admin_realms_runtime_slot_form(array $payload, array $existingDefinition = array()): array
    {
        $defaults = spp_admin_realms_runtime_definition_defaults((int)($existingDefinition['id'] ?? 0));
        $form = array_merge($defaults, $existingDefinition, $payload);

        $form['id'] = (int)($form['id'] ?? 0);
        $form['name'] = trim((string)($form['name'] ?? ''));
        $form['address'] = trim((string)($form['address'] ?? ''));
        $form['port'] = (int)($form['port'] ?? 0);
        $form['realmd'] = trim((string)($form['realmd'] ?? ''));
        $form['world'] = trim((string)($form['world'] ?? ''));
        $form['chars'] = trim((string)($form['chars'] ?? ''));
        $form['armory'] = trim((string)($form['armory'] ?? ''));
        $form['bots'] = trim((string)($form['bots'] ?? ''));
        $form['icon'] = (int)($form['icon'] ?? 0);
        $form['realmflags'] = (int)($form['realmflags'] ?? 0);
        $form['timezone'] = (int)($form['timezone'] ?? 0);
        $form['allowedSecurityLevel'] = (int)($form['allowedSecurityLevel'] ?? 0);
        $form['population'] = trim((string)($form['population'] ?? '0'));
        if ($form['population'] === '') {
            $form['population'] = '0';
        }
        $form['realmbuilds'] = trim((string)($form['realmbuilds'] ?? ''));
        $form['enabled'] = (int)($form['enabled'] ?? 0) === 1 ? 1 : 0;
        $form['make_default'] = (int)($form['make_default'] ?? 0) === 1 ? 1 : 0;
        $form['delete_realmlist_row'] = (int)($form['delete_realmlist_row'] ?? 0) === 1 ? 1 : 0;

        return $form;
    }
}

if (!function_exists('spp_admin_realms_validate_slot_form')) {
    function spp_admin_realms_validate_slot_form(array $form, array $existingDefinitions = array(), bool $isUpdate = false): array
    {
        $errors = array();
        $realmId = (int)($form['id'] ?? 0);
        if ($realmId <= 0) {
            $errors[] = 'Realm ID must be a positive number.';
        } elseif (!$isUpdate && isset($existingDefinitions[$realmId])) {
            $errors[] = 'That realm ID already exists in runtime definitions.';
        }

        if (trim((string)($form['name'] ?? '')) === '') {
            $errors[] = 'Realm name is required.';
        }
        if (trim((string)($form['address'] ?? '')) === '') {
            $errors[] = 'Realm address is required.';
        }
        if ((int)($form['port'] ?? 0) <= 0) {
            $errors[] = 'Realm port must be a positive number.';
        }
        foreach (array('realmd', 'world', 'chars') as $dbField) {
            if (trim((string)($form[$dbField] ?? '')) === '') {
                $errors[] = strtoupper($dbField) . ' database name is required.';
            }
        }

        return array(
            'valid' => empty($errors),
            'errors' => $errors,
        );
    }
}

if (!function_exists('spp_admin_realms_preferred_slot_form')) {
    function spp_admin_realms_preferred_slot_form(array $runtimeOptions = array(), array $catalog = array()): array
    {
        foreach ($runtimeOptions as $option) {
            if (!empty($option['is_config_only'])) {
                return spp_admin_realms_runtime_slot_form(array(), (array)$option);
            }
        }

        foreach ((array)($catalog['config_only_definitions'] ?? array()) as $definition) {
            return spp_admin_realms_runtime_slot_form(array(), (array)$definition);
        }

        return spp_admin_realms_runtime_definition_defaults();
    }
}

if (!function_exists('spp_admin_realms_definition_storage_map')) {
    function spp_admin_realms_definition_storage_map(array $definitions): array
    {
        $storage = array();
        foreach ($definitions as $realmId => $definition) {
            $realmId = (int)$realmId;
            if ($realmId <= 0 || !is_array($definition)) {
                continue;
            }

            $storage[(string)$realmId] = array(
                'id' => $realmId,
                'name' => trim((string)($definition['name'] ?? '')),
                'address' => trim((string)($definition['address'] ?? '')),
                'port' => (int)($definition['port'] ?? 0),
                'realmd' => trim((string)($definition['realmd'] ?? '')),
                'world' => trim((string)($definition['world'] ?? '')),
                'chars' => trim((string)($definition['chars'] ?? '')),
                'armory' => trim((string)($definition['armory'] ?? '')),
                'bots' => trim((string)($definition['bots'] ?? '')),
                'icon' => (int)($definition['icon'] ?? 0),
                'realmflags' => (int)($definition['realmflags'] ?? 0),
                'timezone' => (int)($definition['timezone'] ?? 0),
                'allowedSecurityLevel' => (int)($definition['allowedSecurityLevel'] ?? 0),
                'population' => trim((string)($definition['population'] ?? '0')),
                'realmbuilds' => trim((string)($definition['realmbuilds'] ?? '')),
            );
        }

        ksort($storage, SORT_NATURAL);

        return $storage;
    }
}

if (!function_exists('spp_admin_realms_save_runtime_definitions')) {
    function spp_admin_realms_save_runtime_definitions(array $definitions, ?string $updatedBy = null): bool
    {
        return spp_set_website_setting(
            'realm_runtime.realm_definitions',
            json_encode(spp_admin_realms_definition_storage_map($definitions)),
            $updatedBy
        );
    }
}

if (!function_exists('spp_admin_realms_realmlist_connection')) {
    function spp_admin_realms_realmlist_connection(string $databaseName): ?PDO
    {
        $databaseName = trim($databaseName);
        if ($databaseName === '') {
            return null;
        }

        $db = $GLOBALS['db'] ?? array();
        if (empty($db['host']) || empty($db['user'])) {
            return null;
        }

        try {
            return new PDO(
                'mysql:host=' . (string)$db['host'] . ';port=' . (int)($db['port'] ?? 3306) . ';dbname=' . $databaseName . ';charset=utf8mb4',
                (string)$db['user'],
                (string)($db['pass'] ?? ''),
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                )
            );
        } catch (Throwable $e) {
            error_log('[admin.realms] Failed connecting to realmd "' . $databaseName . '": ' . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('spp_admin_realms_upsert_realmlist_row')) {
    function spp_admin_realms_upsert_realmlist_row(array $definition): bool
    {
        $pdo = spp_admin_realms_realmlist_connection((string)($definition['realmd'] ?? ''));
        if (!$pdo instanceof PDO) {
            return false;
        }

        $realmId = (int)($definition['id'] ?? 0);
        if ($realmId <= 0) {
            return false;
        }

        $columns = array('id', 'name', 'address', 'port', 'icon', 'realmflags', 'timezone', 'allowedSecurityLevel', 'population', 'realmbuilds');
        $insertColumns = array();
        $placeholders = array();
        $updateAssignments = array();
        $values = array();

        foreach ($columns as $column) {
            if (!spp_db_column_exists($pdo, 'realmlist', $column)) {
                continue;
            }

            $insertColumns[] = '`' . $column . '`';
            $placeholders[] = '?';
            $values[] = $definition[$column];
            if ($column !== 'id') {
                $updateAssignments[] = '`' . $column . '` = VALUES(`' . $column . '`)';
            }
        }

        if (empty($insertColumns)) {
            return false;
        }

        $sql = 'INSERT INTO `realmlist` (' . implode(', ', $insertColumns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        if (!empty($updateAssignments)) {
            $sql .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updateAssignments);
        }

        $stmt = $pdo->prepare($sql);
        return $stmt->execute($values);
    }
}

if (!function_exists('spp_admin_realms_delete_realmlist_row')) {
    function spp_admin_realms_delete_realmlist_row(string $realmdDbName, int $realmId): bool
    {
        $pdo = spp_admin_realms_realmlist_connection($realmdDbName);
        if (!$pdo instanceof PDO || $realmId <= 0) {
            return false;
        }

        $stmt = $pdo->prepare('DELETE FROM `realmlist` WHERE `id` = ? LIMIT 1');
        return $stmt->execute(array($realmId));
    }
}

if (!function_exists('spp_admin_realms_normalize_runtime_state_after_slot_change')) {
    function spp_admin_realms_normalize_runtime_state_after_slot_change(array $realmRecords, array $existingRuntimeState, ?int $preferredDefaultRealmId = null, ?int $toggleRealmId = null, ?bool $enabled = null): array
    {
        $realmDbMap = array();
        foreach ($realmRecords as $realmId => $definition) {
            $realmId = (int)$realmId;
            if ($realmId > 0 && is_array($definition)) {
                $realmDbMap[$realmId] = $definition;
            }
        }

        ksort($realmDbMap, SORT_NUMERIC);
        $realmIds = array_values(array_map('intval', array_keys($realmDbMap)));
        $enabledRealmIds = spp_realm_runtime_normalize_enabled_ids((array)($existingRuntimeState['enabled_realm_ids'] ?? array()), $realmDbMap);

        if ($toggleRealmId !== null && $toggleRealmId > 0) {
            $enabledLookup = array_fill_keys($enabledRealmIds, true);
            if ($enabled === true) {
                $enabledLookup[$toggleRealmId] = true;
            } elseif ($enabled === false) {
                unset($enabledLookup[$toggleRealmId]);
            }
            $enabledRealmIds = array_values(array_map('intval', array_keys($enabledLookup)));
            sort($enabledRealmIds, SORT_NUMERIC);
        }

        if (empty($enabledRealmIds)) {
            $enabledRealmIds = $realmIds;
        }

        $defaultRealmId = (int)($existingRuntimeState['default_realm_id'] ?? 0);
        if ($preferredDefaultRealmId !== null && $preferredDefaultRealmId > 0 && isset($realmDbMap[$preferredDefaultRealmId])) {
            $defaultRealmId = $preferredDefaultRealmId;
        }
        if (!in_array($defaultRealmId, $enabledRealmIds, true)) {
            $defaultRealmId = !empty($enabledRealmIds) ? (int)$enabledRealmIds[0] : 0;
        }

        return array(
            'multirealm' => (int)($existingRuntimeState['multirealm'] ?? 0) === 1 ? 1 : 0,
            'selection_mode' => (string)($existingRuntimeState['selection_mode'] ?? 'manual'),
            'default_realm_id' => $defaultRealmId,
            'enabled_realm_ids' => $enabledRealmIds,
        );
    }
}

function spp_admin_realms_handle_action(PDO $realmsPdo, array $realmDbMap = array()): array
{
    $action = (string)($_GET['action'] ?? '');
    $realmId = (int)($_GET['id'] ?? 0);
    $requestMethod = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    $configuredRealmDbMap = (array)($GLOBALS['allConfiguredRealmDbMap'] ?? $realmDbMap);
    $runtimeView = spp_admin_realms_runtime_state($realmsPdo, $configuredRealmDbMap);
    $runtimeSettings = (array)($runtimeView['runtime_settings'] ?? array());
    $runtimeOptions = (array)($runtimeView['runtime_realm_options'] ?? array());
    $catalog = (array)($runtimeView['runtime_catalog'] ?? array());
    $existingDefinitions = (array)($catalog['realm_definitions'] ?? array());
    $updatedBy = (string)($GLOBALS['user']['username'] ?? $GLOBALS['user']['name'] ?? 'admin');
    $state = array();

    if ($action === '' || $action === '0' || ($action === 'edit' && $requestMethod !== 'POST')) {
        return $state;
    }

    if ($requestMethod !== 'POST') {
        return $state;
    }

    if ($action === 'runtime-save') {
        spp_require_csrf('admin_realms');
        $runtimeForm = spp_admin_realms_runtime_form_state($runtimeOptions, $_POST);
        $result = spp_admin_realms_save_runtime_settings(
            $runtimeOptions,
            $runtimeForm,
            $updatedBy
        );

        if (empty($result['valid'])) {
            $state['runtime_form'] = $runtimeForm;
            $state['runtime_errors'] = array_values(array_filter((array)($result['errors'] ?? array())));
            return $state;
        }

        redirect('index.php?n=admin&sub=realms', 1);
        exit;
    }

    if ($action === 'create-slot') {
        spp_require_csrf('admin_realms');
        $slotForm = spp_admin_realms_runtime_slot_form($_POST);
        $validation = spp_admin_realms_validate_slot_form($slotForm, $existingDefinitions, false);
        if (empty($validation['valid'])) {
            $state['slot_form'] = $slotForm;
            $state['slot_errors'] = (array)$validation['errors'];
            return $state;
        }

        if (!spp_admin_realms_upsert_realmlist_row($slotForm)) {
            $state['slot_form'] = $slotForm;
            $state['slot_errors'] = array('The matching realmlist row could not be created or updated.');
            return $state;
        }

        $existingDefinitions[$slotForm['id']] = $slotForm;
        ksort($existingDefinitions, SORT_NUMERIC);
        if (!spp_admin_realms_save_runtime_definitions($existingDefinitions, $updatedBy)) {
            $state['slot_form'] = $slotForm;
            $state['slot_errors'] = array('The runtime realm definition could not be saved.');
            return $state;
        }

        $runtimeRealmRecords = $runtimeOptions;
        $runtimeRealmRecords[(int)$slotForm['id']] = $slotForm;
        $normalizedRuntime = spp_admin_realms_normalize_runtime_state_after_slot_change(
            $runtimeRealmRecords,
            $runtimeSettings,
            $slotForm['make_default'] === 1 ? (int)$slotForm['id'] : null,
            (int)$slotForm['id'],
            $slotForm['enabled'] === 1
        );
        spp_admin_realms_save_runtime_settings($existingDefinitions, $normalizedRuntime, $updatedBy);

        redirect('index.php?n=admin&sub=realms', 1);
        exit;
    }

    if ($action === 'sync-slot' && $realmId > 0) {
        spp_require_csrf('admin_realms');
        $existingDefinition = (array)($runtimeOptions[$realmId] ?? $existingDefinitions[$realmId] ?? array());
        if (empty($existingDefinition)) {
            $state['slot_errors'] = array('That runtime slot could not be found.');
            return $state;
        }

        if (!spp_admin_realms_upsert_realmlist_row($existingDefinition)) {
            $state['slot_errors'] = array('The matching realmlist row could not be created or updated for this slot.');
            return $state;
        }

        redirect('index.php?n=admin&sub=realms', 1);
        exit;
    }

    if ($action === 'update-slot' && $realmId > 0) {
        spp_require_csrf('admin_realms');
        $existingDefinition = (array)($runtimeOptions[$realmId] ?? $existingDefinitions[$realmId] ?? array('id' => $realmId));
        $slotForm = spp_admin_realms_runtime_slot_form($_POST, $existingDefinition);
        $slotForm['id'] = $realmId;
        $validation = spp_admin_realms_validate_slot_form($slotForm, $existingDefinitions, true);
        if (empty($validation['valid'])) {
            $state['slot_form'] = $slotForm;
            $state['slot_errors'] = (array)$validation['errors'];
            return $state;
        }

        if (!spp_admin_realms_upsert_realmlist_row($slotForm)) {
            $state['slot_form'] = $slotForm;
            $state['slot_errors'] = array('The matching realmlist row could not be created or updated.');
            return $state;
        }

        $existingDefinitions[$realmId] = $slotForm;
        ksort($existingDefinitions, SORT_NUMERIC);
        if (!spp_admin_realms_save_runtime_definitions($existingDefinitions, $updatedBy)) {
            $state['slot_form'] = $slotForm;
            $state['slot_errors'] = array('The runtime realm definition could not be updated.');
            return $state;
        }

        $runtimeRealmRecords = $runtimeOptions;
        $runtimeRealmRecords[$realmId] = $slotForm;
        $normalizedRuntime = spp_admin_realms_normalize_runtime_state_after_slot_change(
            $runtimeRealmRecords,
            $runtimeSettings,
            $slotForm['make_default'] === 1 ? $realmId : null,
            $realmId,
            $slotForm['enabled'] === 1
        );
        spp_admin_realms_save_runtime_settings($existingDefinitions, $normalizedRuntime, $updatedBy);

        redirect('index.php?n=admin&sub=realms', 1);
        exit;
    }

    if ($action === 'remove-slot' && $realmId > 0) {
        spp_require_csrf('admin_realms');
        $existingDefinition = (array)($runtimeOptions[$realmId] ?? $existingDefinitions[$realmId] ?? array());
        $runtimeRealmRecords = $runtimeOptions;
        unset($runtimeRealmRecords[$realmId]);
        if (!empty($existingDefinition['is_config_only'])) {
            $normalizedRuntime = spp_admin_realms_normalize_runtime_state_after_slot_change(
                $runtimeRealmRecords,
                $runtimeSettings,
                null,
                $realmId,
                false
            );
            $saveResult = spp_admin_realms_save_runtime_settings($runtimeOptions, $normalizedRuntime, $updatedBy);
            if (empty($saveResult['valid'])) {
                $state['slot_errors'] = array('The config-only slot could not be removed from active runtime use.');
                return $state;
            }
        } else {
            unset($existingDefinitions[$realmId]);
            if (!spp_admin_realms_save_runtime_definitions($existingDefinitions, $updatedBy)) {
                $state['slot_errors'] = array('The runtime slot could not be removed.');
                return $state;
            }
            $normalizedRuntime = spp_admin_realms_normalize_runtime_state_after_slot_change(
                $runtimeRealmRecords,
                $runtimeSettings,
                null,
                $realmId,
                false
            );
            spp_admin_realms_save_runtime_settings($existingDefinitions, $normalizedRuntime, $updatedBy);
        }

        if ((int)($_POST['delete_realmlist_row'] ?? 0) === 1) {
            $realmdDbName = trim((string)($existingDefinition['realmd'] ?? ''));
            if ($realmdDbName !== '') {
                spp_admin_realms_delete_realmlist_row($realmdDbName, $realmId);
            }
        }

        redirect('index.php?n=admin&sub=realms', 1);
        exit;
    }

    return $state;
}
