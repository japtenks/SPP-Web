<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_realms_type_definitions()
{
    return array(
        0 => 'Normal',
        1 => 'PVP',
        4 => 'Normal',
        6 => 'RP',
        8 => 'RPPVP',
        16 => 'FFA_PVP',
    );
}

function spp_admin_realms_timezone_definitions()
{
    return array(
         0 => 'Unknown',
         1 => 'Development',
         2 => 'United States',
         3 => 'Oceanic',
         4 => 'Latin America',
         5 => 'Tournament',
         6 => 'Korea',
         7 => 'Tournament',
         8 => 'English',
         9 => 'German',
        10 => 'French',
        11 => 'Spanish',
        12 => 'Russian',
        13 => 'Tournament',
        14 => 'Taiwan',
        15 => 'Tournament',
        16 => 'China',
        17 => 'CN1',
        18 => 'CN2',
        19 => 'CN3',
        20 => 'CN4',
        21 => 'CN5',
        22 => 'CN6',
        23 => 'CN7',
        24 => 'CN8',
        25 => 'Tournament',
        26 => 'Test Server',
        27 => 'Tournament',
        28 => 'QA Server',
        29 => 'CN9',
    );
}

function spp_admin_realms_filter_fields(array $data)
{
    $allowed = array(
        'name',
        'address',
        'port',
        'icon',
        'realmflags',
        'timezone',
        'allowedSecurityLevel',
        'population',
        'realmbuilds',
    );
    return spp_filter_allowed_fields($data, $allowed);
}

function spp_admin_realms_normalize_fields(array $data)
{
    $data = spp_admin_realms_filter_fields($data);
    $data['name'] = trim((string)($data['name'] ?? ''));
    $data['address'] = trim((string)($data['address'] ?? ''));
    $data['port'] = (int)($data['port'] ?? 0);
    $data['icon'] = (int)($data['icon'] ?? 0);
    $data['realmflags'] = (int)($data['realmflags'] ?? 0);
    $data['timezone'] = (int)($data['timezone'] ?? 0);
    $data['allowedSecurityLevel'] = (int)($data['allowedSecurityLevel'] ?? 0);
    $data['population'] = (float)($data['population'] ?? 0);
    $data['realmbuilds'] = trim((string)($data['realmbuilds'] ?? ''));
    return $data;
}

function spp_admin_realms_column_labels()
{
    return array(
        'id' => 'ID',
        'name' => 'Name',
        'address' => 'Address',
        'port' => 'Port',
        'icon' => 'Type',
        'realmflags' => 'Realm Flags',
        'timezone' => 'Timezone',
        'allowedSecurityLevel' => 'Allowed Security Level',
        'population' => 'Population',
        'realmbuilds' => 'Realm Builds',
    );
}

if (!function_exists('spp_admin_realms_runtime_selection_modes')) {
    function spp_admin_realms_runtime_selection_modes(): array
    {
        return array(
            'manual' => 'Manual',
            'session_probe' => 'Session Probe',
        );
    }
}

if (!function_exists('spp_admin_realms_runtime_mode_label')) {
    function spp_admin_realms_runtime_mode_label(string $mode): string
    {
        $modes = spp_admin_realms_runtime_selection_modes();
        $mode = trim($mode);

        return (string)($modes[$mode] ?? $mode);
    }
}

if (!function_exists('spp_admin_realms_runtime_realm_options')) {
    function spp_admin_realms_runtime_realm_options(PDO $realmsPdo, array $realmDbMap): array
    {
        $options = array();
        $allowedRealmIds = array_fill_keys(array_map('intval', array_keys($realmDbMap)), true);

        try {
            $stmt = $realmsPdo->query("SELECT `id`, `name`, `address`, `port` FROM `realmlist` ORDER BY `id` ASC");
            foreach (($stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array()) as $row) {
                $realmId = (int)($row['id'] ?? 0);
                if ($realmId <= 0 || !isset($allowedRealmIds[$realmId])) {
                    continue;
                }

                $realmName = trim((string)($row['name'] ?? ''));
                if ($realmName === '') {
                    $realmName = 'Realm ' . $realmId;
                }

                $address = trim((string)($row['address'] ?? ''));
                $port = (int)($row['port'] ?? 0);
                $summary = $realmName;
                if ($address !== '') {
                    $summary .= ' (' . $address;
                    if ($port > 0) {
                        $summary .= ':' . $port;
                    }
                    $summary .= ')';
                }

                $options[$realmId] = array(
                    'id' => $realmId,
                    'name' => $realmName,
                    'address' => $address,
                    'port' => $port,
                    'label' => $summary,
                );
            }
        } catch (Throwable $e) {
            error_log('[admin.realms] Failed loading runtime realm options: ' . $e->getMessage());
        }

        return $options;
    }
}

if (!function_exists('spp_admin_realms_runtime_state')) {
    function spp_admin_realms_runtime_state(PDO $realmsPdo, array $realmDbMap): array
    {
        $options = spp_admin_realms_runtime_realm_options($realmsPdo, $realmDbMap);
        $validRealmIds = array_map('intval', array_keys($options));
        $runtimeState = function_exists('spp_realm_runtime_state')
            ? spp_realm_runtime_state($realmDbMap)
            : array(
                'multirealm' => 0,
                'default_realm_id' => !empty($validRealmIds) ? (int)$validRealmIds[0] : 0,
                'enabled_realm_ids' => $validRealmIds,
                'selection_mode' => 'manual',
            );

        $runtimeState['enabled_realm_ids'] = function_exists('spp_realm_runtime_normalize_enabled_ids')
            ? spp_realm_runtime_normalize_enabled_ids($runtimeState['enabled_realm_ids'], $realmDbMap)
            : $runtimeState['enabled_realm_ids'];
        $runtimeState['enabled_realm_ids'] = array_values(array_values(array_intersect($runtimeState['enabled_realm_ids'], $validRealmIds)));
        if (empty($runtimeState['enabled_realm_ids'])) {
            $runtimeState['enabled_realm_ids'] = $validRealmIds;
        }
        if (!in_array((int)$runtimeState['default_realm_id'], $runtimeState['enabled_realm_ids'], true)) {
            $runtimeState['default_realm_id'] = !empty($runtimeState['enabled_realm_ids'])
                ? (int)$runtimeState['enabled_realm_ids'][0]
                : 0;
        }
        if (!array_key_exists((string)$runtimeState['selection_mode'], spp_admin_realms_runtime_selection_modes())) {
            $runtimeState['selection_mode'] = 'manual';
        }

        return array(
            'runtime_settings' => $runtimeState,
            'runtime_realm_options' => $options,
            'runtime_selection_modes' => spp_admin_realms_runtime_selection_modes(),
        );
    }
}

if (!function_exists('spp_admin_realms_runtime_form_state')) {
    function spp_admin_realms_runtime_form_state(array $realmDbMap, array $payload): array
    {
        $selectedModes = spp_admin_realms_runtime_selection_modes();
        $validRealmIds = array_map('intval', array_keys($realmDbMap));

        $enabledRealmIds = array();
        $submittedEnabledIds = $payload['enabled_realm_ids'] ?? array();
        if (is_array($submittedEnabledIds)) {
            foreach ($submittedEnabledIds as $candidateId) {
                $realmId = (int)$candidateId;
                if ($realmId > 0 && in_array($realmId, $validRealmIds, true) && !in_array($realmId, $enabledRealmIds, true)) {
                    $enabledRealmIds[] = $realmId;
                }
            }
        }

        $defaultRealmId = (int)($payload['default_realm_id'] ?? 0);
        $selectionMode = trim((string)($payload['selection_mode'] ?? 'manual'));
        if (!array_key_exists($selectionMode, $selectedModes)) {
            $selectionMode = 'manual';
        }

        return array(
            'multirealm' => (int)($payload['multirealm'] ?? 0) === 1 ? 1 : 0,
            'selection_mode' => $selectionMode,
            'default_realm_id' => $defaultRealmId,
            'enabled_realm_ids' => $enabledRealmIds,
        );
    }
}

if (!function_exists('spp_admin_realms_validate_runtime_form')) {
    function spp_admin_realms_validate_runtime_form(array $realmDbMap, array $runtimeForm): array
    {
        $errors = array();
        $validRealmIds = array_map('intval', array_keys($realmDbMap));
        $selectedModes = spp_admin_realms_runtime_selection_modes();

        $enabledRealmIds = array_values(array_filter(array_map('intval', (array)($runtimeForm['enabled_realm_ids'] ?? array())), static function ($realmId) use ($validRealmIds) {
            return $realmId > 0 && in_array($realmId, $validRealmIds, true);
        }));
        $enabledRealmIds = array_values(array_unique($enabledRealmIds));
        if (empty($enabledRealmIds)) {
            $errors[] = 'At least one enabled realm is required.';
        }

        $defaultRealmId = (int)($runtimeForm['default_realm_id'] ?? 0);
        if ($defaultRealmId <= 0 || !in_array($defaultRealmId, $validRealmIds, true)) {
            $errors[] = 'Choose a valid default realm.';
        } elseif (!in_array($defaultRealmId, $enabledRealmIds, true)) {
            $errors[] = 'The default realm must be enabled.';
        }

        $selectionMode = trim((string)($runtimeForm['selection_mode'] ?? 'manual'));
        if (!array_key_exists($selectionMode, $selectedModes)) {
            $errors[] = 'Choose a valid runtime selection mode.';
        }

        return array(
            'valid' => empty($errors),
            'errors' => $errors,
            'normalized' => array(
                'multirealm' => (int)($runtimeForm['multirealm'] ?? 0) === 1 ? 1 : 0,
                'selection_mode' => array_key_exists($selectionMode, $selectedModes) ? $selectionMode : 'manual',
                'default_realm_id' => $defaultRealmId,
                'enabled_realm_ids' => $enabledRealmIds,
            ),
        );
    }
}

if (!function_exists('spp_admin_realms_save_runtime_settings')) {
    function spp_admin_realms_save_runtime_settings(array $realmDbMap, array $runtimeForm, ?string $updatedBy = null): array
    {
        $validation = spp_admin_realms_validate_runtime_form($realmDbMap, $runtimeForm);
        if (empty($validation['valid'])) {
            return $validation;
        }

        $normalized = (array)$validation['normalized'];
        $prefix = 'realm_runtime.';
        $saved = array();

        try {
            $pdo = spp_website_settings_pdo();
            if (!spp_ensure_website_settings_table($pdo)) {
                return array(
                    'valid' => false,
                    'errors' => array('The website settings table is not available.'),
                    'normalized' => $normalized,
                );
            }

            $tableName = spp_website_settings_table_name();
            $stmt = $pdo->prepare("
                INSERT INTO `{$tableName}` (`setting_key`, `setting_value`, `updated_by`)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    `setting_value` = VALUES(`setting_value`),
                    `updated_by` = VALUES(`updated_by`),
                    `updated_at` = CURRENT_TIMESTAMP
            ");

            if (!$pdo->inTransaction()) {
                $pdo->beginTransaction();
            }

            $saved['multirealm'] = $stmt->execute(array($prefix . 'multirealm', (string)(int)$normalized['multirealm'], $updatedBy));
            $saved['default_realm_id'] = $stmt->execute(array($prefix . 'default_realm_id', (string)(int)$normalized['default_realm_id'], $updatedBy));
            $saved['enabled_realm_ids'] = $stmt->execute(array($prefix . 'enabled_realm_ids', json_encode(array_values((array)$normalized['enabled_realm_ids'])), $updatedBy));
            $saved['selection_mode'] = $stmt->execute(array($prefix . 'selection_mode', (string)$normalized['selection_mode'], $updatedBy));

            if ($pdo->inTransaction()) {
                $pdo->commit();
            }
            spp_website_settings_rows(true);
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[admin.realms] Failed saving runtime settings: ' . $e->getMessage());
            return array(
                'valid' => false,
                'errors' => array('One or more runtime settings could not be saved.'),
                'normalized' => $normalized,
            );
        }

        return array(
            'valid' => !in_array(false, $saved, true),
            'errors' => !in_array(false, $saved, true) ? array() : array('One or more runtime settings could not be saved.'),
            'normalized' => $normalized,
        );
    }
}
