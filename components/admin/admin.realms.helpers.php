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
        );
    }
}

if (!function_exists('spp_admin_realms_runtime_catalog')) {
    function spp_admin_realms_runtime_catalog(array $realmDbMap = array()): array
    {
        $fallbackRealmDbMap = $GLOBALS['fallbackConfiguredRealmDbMap'] ?? $realmDbMap;
        $catalog = $GLOBALS['realmRuntimeCatalog'] ?? null;
        if (is_array($catalog) && !empty($catalog)) {
            return $catalog;
        }

        if (function_exists('spp_realm_runtime_catalog')) {
            return spp_realm_runtime_catalog(is_array($fallbackRealmDbMap) ? $fallbackRealmDbMap : array());
        }

        return array(
            'source' => 'config',
            'realm_db_map' => $realmDbMap,
            'runtime_realm_db_map' => $realmDbMap,
            'fallback_realm_db_map' => $fallbackRealmDbMap,
            'config_only_realm_db_map' => array(),
            'realm_definitions' => array(),
            'fallback_definitions' => array(),
            'config_only_definitions' => array(),
            'diagnostics' => array(),
        );
    }
}

if (!function_exists('spp_admin_realms_definition_from_row')) {
    function spp_admin_realms_definition_from_row(int $realmId, array $definition = array(), array $realmlistRow = array()): array
    {
        return array(
            'id' => $realmId,
            'name' => trim((string)($definition['name'] ?? $realmlistRow['name'] ?? '')),
            'address' => trim((string)($definition['address'] ?? $realmlistRow['address'] ?? '')),
            'port' => (int)($definition['port'] ?? $realmlistRow['port'] ?? 0),
            'realmd' => trim((string)($definition['realmd'] ?? '')),
            'world' => trim((string)($definition['world'] ?? '')),
            'chars' => trim((string)($definition['chars'] ?? '')),
            'armory' => trim((string)($definition['armory'] ?? '')),
            'bots' => trim((string)($definition['bots'] ?? '')),
            'icon' => (int)($definition['icon'] ?? $realmlistRow['icon'] ?? 0),
            'realmflags' => (int)($definition['realmflags'] ?? $realmlistRow['realmflags'] ?? 0),
            'timezone' => (int)($definition['timezone'] ?? $realmlistRow['timezone'] ?? 0),
            'allowedSecurityLevel' => (int)($definition['allowedSecurityLevel'] ?? $realmlistRow['allowedSecurityLevel'] ?? 0),
            'population' => trim((string)($definition['population'] ?? $realmlistRow['population'] ?? '0')),
            'realmbuilds' => trim((string)($definition['realmbuilds'] ?? $realmlistRow['realmbuilds'] ?? '')),
        );
    }
}

if (!function_exists('spp_admin_realms_runtime_realmlist_rows')) {
    function spp_admin_realms_runtime_realmlist_rows(PDO $realmsPdo): array
    {
        $rows = array();

        try {
            $stmt = $realmsPdo->query("SELECT * FROM `realmlist` ORDER BY `id` ASC");
            foreach (($stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: array()) : array()) as $row) {
                $realmId = (int)($row['id'] ?? 0);
                if ($realmId > 0) {
                    $rows[$realmId] = $row;
                }
            }
        } catch (Throwable $e) {
            error_log('[admin.realms] Failed loading realmlist rows: ' . $e->getMessage());
        }

        ksort($rows, SORT_NUMERIC);

        return $rows;
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
        $catalog = spp_admin_realms_runtime_catalog($realmDbMap);
        $effectiveDefinitions = (array)($catalog['realm_definitions'] ?? array());
        $configOnlyDefinitions = (array)($catalog['config_only_definitions'] ?? array());
        $realmlistRows = spp_admin_realms_runtime_realmlist_rows($realmsPdo);

        foreach ($effectiveDefinitions as $realmId => $definition) {
            $realmId = (int)$realmId;
            if ($realmId <= 0) {
                continue;
            }

            $realmlistRow = (array)($realmlistRows[$realmId] ?? array());
            if (function_exists('spp_admin_realms_runtime_realmlist_row')) {
                $realmlistRow = (array)spp_admin_realms_runtime_realmlist_row((string)($definition['realmd'] ?? ''), $realmId);
            }
            $item = spp_admin_realms_definition_from_row($realmId, (array)$definition, $realmlistRow);
            $realmName = $item['name'] !== '' ? $item['name'] : ('Realm ' . $realmId);
            $summary = $realmName;
            if ($item['address'] !== '') {
                $summary .= ' (' . $item['address'];
                if ((int)$item['port'] > 0) {
                    $summary .= ':' . (int)$item['port'];
                }
                $summary .= ')';
            }

            $item['label'] = $summary;
            $item['is_config_only'] = false;
            $item['is_runtime_definition'] = true;
            $item['has_realmlist_row'] = !empty($realmlistRow);
            $options[$realmId] = $item;
        }

        foreach ($configOnlyDefinitions as $realmId => $definition) {
            $realmId = (int)$realmId;
            if ($realmId <= 0 || isset($options[$realmId])) {
                continue;
            }

            $realmlistRow = (array)($realmlistRows[$realmId] ?? array());
            if (function_exists('spp_admin_realms_runtime_realmlist_row')) {
                $realmlistRow = (array)spp_admin_realms_runtime_realmlist_row((string)($definition['realmd'] ?? ''), $realmId);
            }
            $item = spp_admin_realms_definition_from_row($realmId, (array)$definition, $realmlistRow);
            $summary = 'Config-Only Slot ' . $realmId;
            $detailBits = array();
            if ($item['realmd'] !== '') {
                $detailBits[] = 'realmd=' . $item['realmd'];
            }
            if ($item['chars'] !== '') {
                $detailBits[] = 'chars=' . $item['chars'];
            }
            if (!empty($detailBits)) {
                $summary .= ' (' . implode(', ', $detailBits) . ')';
            }

            $item['label'] = $summary;
            $item['is_config_only'] = true;
            $item['is_runtime_definition'] = false;
            $item['has_realmlist_row'] = !empty($realmlistRow);
            $options[$realmId] = $item;
        }

        ksort($options, SORT_NUMERIC);

        return $options;
    }
}

if (!function_exists('spp_admin_realms_runtime_state')) {
    function spp_admin_realms_runtime_state(PDO $realmsPdo, array $realmDbMap): array
    {
        $configuredRealmDbMap = (array)($GLOBALS['allConfiguredRealmDbMap'] ?? $realmDbMap);
        $options = spp_admin_realms_runtime_realm_options($realmsPdo, $realmDbMap);
        $allRealmIds = array_map('intval', array_keys($options));
        $runtimeState = function_exists('spp_realm_runtime_state')
            ? spp_realm_runtime_state($configuredRealmDbMap)
            : array(
                'multirealm' => 0,
                'default_realm_id' => !empty($allRealmIds) ? (int)$allRealmIds[0] : 0,
                'enabled_realm_ids' => $allRealmIds,
                'selection_mode' => 'manual',
            );

        $runtimeState['enabled_realm_ids'] = function_exists('spp_realm_runtime_normalize_enabled_ids')
            ? spp_realm_runtime_normalize_enabled_ids($runtimeState['enabled_realm_ids'], $realmDbMap)
            : $runtimeState['enabled_realm_ids'];
        $runtimeState['enabled_realm_ids'] = array_values(array_values(array_intersect($runtimeState['enabled_realm_ids'], $allRealmIds)));
        if (empty($runtimeState['enabled_realm_ids'])) {
            $runtimeState['enabled_realm_ids'] = $allRealmIds;
        }
        if (!in_array((int)$runtimeState['default_realm_id'], $runtimeState['enabled_realm_ids'], true)) {
            $runtimeState['default_realm_id'] = !empty($runtimeState['enabled_realm_ids'])
                ? (int)$runtimeState['enabled_realm_ids'][0]
                : 0;
        }
        if (!array_key_exists((string)$runtimeState['selection_mode'], spp_admin_realms_runtime_selection_modes())) {
            $runtimeState['selection_mode'] = 'manual';
        }

        $visibleOptions = $options;

        return array(
            'runtime_settings' => $runtimeState,
            'runtime_realm_options' => $visibleOptions,
            'runtime_selection_modes' => spp_admin_realms_runtime_selection_modes(),
            'runtime_catalog' => spp_admin_realms_runtime_catalog($configuredRealmDbMap),
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
