<?php
if (INCLUDED !== true) {
    exit;
}

require_once dirname(__DIR__, 2) . '/app/support/db-schema.php';

if (!function_exists('spp_admin_operations_root_path')) {
    function spp_admin_operations_root_path(): string
    {
        return 'C:\\Git\\SPP-core\\SPP_Server\\sql';
    }
}

if (!function_exists('spp_admin_operations_category_labels')) {
    function spp_admin_operations_category_labels(): array
    {
        return array(
            'export_import' => 'Export / Import',
            'bot_maintenance' => 'Bot Maintenance',
            'database_reset' => 'Database Reset',
            'realm_settings' => 'Realm Settings',
            'db_update_install' => 'DB Update / Install',
            'conversion_tools' => 'Conversion Tools',
            'member_security' => 'Member Security',
        );
    }
}

if (!function_exists('spp_admin_operations_realm_options')) {
    function spp_admin_operations_realm_options(array $realmDbMap): array
    {
        $options = array();
        $realmlistMap = array();

        try {
            $realmdPdo = spp_get_pdo('realmd', 1);
            $stmt = $realmdPdo->query('SELECT `id`, `name`, `address`, `port` FROM `realmlist` ORDER BY `id` ASC');
            $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array();
            foreach ($rows as $row) {
                $realmlistMap[(int)($row['id'] ?? 0)] = $row;
            }
        } catch (Throwable $e) {
            $realmlistMap = array();
        }

        foreach ($realmDbMap as $realmId => $realmConfig) {
            $realmId = (int)$realmId;
            if ($realmId <= 0) {
                continue;
            }
            $realmRow = (array)($realmlistMap[$realmId] ?? array());
            $name = (string)($realmRow['name'] ?? ($realmConfig['name'] ?? ('Realm ' . $realmId)));
            $options[] = array(
                'id' => $realmId,
                'label' => $name . ' (#' . $realmId . ')',
                'name' => $name,
                'address' => (string)($realmRow['address'] ?? ''),
                'port' => (int)($realmRow['port'] ?? 0),
                'chars_schema' => (string)($realmConfig['chars'] ?? ''),
                'world_schema' => (string)($realmConfig['db'] ?? ''),
            );
        }

        return $options;
    }
}

if (!function_exists('spp_admin_operations_descriptors')) {
    function spp_admin_operations_descriptors(array $realmDbMap): array
    {
        static $cache = array();
        $cacheKey = md5(json_encode($realmDbMap));
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $assetRoot = spp_admin_operations_root_path();
        return $cache[$cacheKey] = array(
            'backup_export' => array(
                'id' => 'backup_export',
                'label' => 'Character Backup Export',
                'category' => 'export_import',
                'risk_level' => 'safe',
                'scope' => 'single_realm',
                'execution_mode' => 'native_php',
                'summary' => 'Existing website-native export flow for backup bundles and migration prep.',
                'required_inputs' => array('source_realm_id'),
                'supports_dry_run' => true,
                'verification_rules' => array('Review generated SQL bundle before import.'),
                'native_href' => 'index.php?n=admin&sub=backup',
            ),
            'same_realm_chartransfer' => array(
                'id' => 'same_realm_chartransfer',
                'label' => 'Character Transfer Dry-Run',
                'category' => 'export_import',
                'risk_level' => 'reviewed',
                'scope' => 'cross_realm',
                'execution_mode' => 'native_php',
                'summary' => 'Schema-aware dry-run transfer probe with clear source/target realm selection.',
                'required_inputs' => array('source_realm_id', 'target_realm_id'),
                'supports_dry_run' => true,
                'verification_rules' => array('Confirm source/target schemas and exact character name before running a live move later.'),
                'native_href' => 'index.php?n=admin&sub=chartransfer',
            ),
            'password_reset' => array(
                'id' => 'password_reset',
                'label' => 'Account Password Reset',
                'category' => 'member_security',
                'risk_level' => 'safe',
                'scope' => 'global',
                'execution_mode' => 'native_php',
                'summary' => 'Existing website-managed member security action.',
                'required_inputs' => array('account_id'),
                'supports_dry_run' => false,
                'verification_rules' => array('Confirm account id and communicate the reset securely.'),
                'native_href' => 'index.php?n=admin&sub=members',
            ),
            'apply_realm_name' => array(
                'id' => 'apply_realm_name',
                'label' => 'Apply Realm Name',
                'category' => 'realm_settings',
                'risk_level' => 'reviewed',
                'scope' => 'single_realm',
                'execution_mode' => 'sql_job',
                'summary' => 'Queue a reviewed `realmlist` rename instead of editing the realm directory directly.',
                'required_inputs' => array('realm_id', 'value'),
                'supports_dry_run' => true,
                'verification_rules' => array('Verify the matching `realmlist.name` row after maintenance.'),
            ),
            'apply_realm_address' => array(
                'id' => 'apply_realm_address',
                'label' => 'Apply Realm Address',
                'category' => 'realm_settings',
                'risk_level' => 'reviewed',
                'scope' => 'single_realm',
                'execution_mode' => 'sql_job',
                'summary' => 'Queue a reviewed `realmlist` address update and keep shared-impact changes visible.',
                'required_inputs' => array('realm_id', 'value'),
                'supports_dry_run' => true,
                'verification_rules' => array('Verify the matching `realmlist.address` row before reopening the realm.'),
            ),
            'reset_randombots' => array(
                'id' => 'reset_randombots',
                'label' => 'Reset Random Bots',
                'category' => 'bot_maintenance',
                'risk_level' => 'reviewed',
                'scope' => 'single_realm',
                'execution_mode' => 'sql_job',
                'summary' => 'Launcher-parity bot reset job backed by reviewed SQL assets.',
                'required_inputs' => array('realm_id'),
                'supports_dry_run' => true,
                'verification_rules' => array('Verify bot counts after reset and confirm the target characters DB.'),
                'asset_path' => $assetRoot . '\\reset_randombots.sql',
                'prechecks' => array('asset_exists'),
            ),
            'delete_randombots' => array(
                'id' => 'delete_randombots',
                'label' => 'Delete Random Bots',
                'category' => 'bot_maintenance',
                'risk_level' => 'destructive',
                'scope' => 'single_realm',
                'execution_mode' => 'sql_job',
                'summary' => 'Delete realm random bots through the reviewed launcher SQL flow.',
                'required_inputs' => array('realm_id', 'confirmation_phrase'),
                'supports_dry_run' => true,
                'verification_rules' => array('Confirm bot rows are gone and the realm stayed offline during maintenance.'),
                'prechecks' => array('server_offline', 'confirmation_phrase', 'asset_exists'),
                'asset_path' => $assetRoot . '\\delete_randombots.sql',
            ),
            'delete_all_randombots' => array(
                'id' => 'delete_all_randombots',
                'label' => 'Delete All Random Bots',
                'category' => 'bot_maintenance',
                'risk_level' => 'destructive',
                'scope' => 'global',
                'execution_mode' => 'sql_job',
                'summary' => 'Shared-impact realm maintenance that can affect more than one configured realm.',
                'required_inputs' => array('realm_id', 'confirmation_phrase'),
                'supports_dry_run' => true,
                'verification_rules' => array('Review shared-impact messaging and validate each targeted realm after cleanup.'),
                'prechecks' => array('server_offline', 'confirmation_phrase', 'asset_exists'),
                'asset_path' => $assetRoot . '\\delete_all_randombots.sql',
            ),
            'chars_wipe' => array(
                'id' => 'chars_wipe',
                'label' => 'Wipe Characters DB',
                'category' => 'database_reset',
                'risk_level' => 'destructive',
                'scope' => 'single_realm',
                'execution_mode' => 'sql_job',
                'summary' => 'Guarded character wipe flow requiring an offline realm and explicit confirmation.',
                'required_inputs' => array('realm_id', 'confirmation_phrase'),
                'supports_dry_run' => true,
                'verification_rules' => array('Verify the characters schema and empty-row counts before reopening service.'),
                'prechecks' => array('server_offline', 'confirmation_phrase'),
            ),
            'chars_accounts_wipe' => array(
                'id' => 'chars_accounts_wipe',
                'label' => 'Wipe Characters + Accounts',
                'category' => 'database_reset',
                'risk_level' => 'destructive',
                'scope' => 'global',
                'execution_mode' => 'sql_job',
                'summary' => 'High-risk reset flow that should stay in the reviewed job queue.',
                'required_inputs' => array('realm_id', 'confirmation_phrase'),
                'supports_dry_run' => true,
                'verification_rules' => array('Confirm both account and character stores were intentionally selected.'),
                'prechecks' => array('server_offline', 'confirmation_phrase'),
            ),
            'world_reinstall' => array(
                'id' => 'world_reinstall',
                'label' => 'World Reinstall',
                'category' => 'db_update_install',
                'risk_level' => 'destructive',
                'scope' => 'single_realm',
                'execution_mode' => 'sql_job',
                'summary' => 'Website-driven wrapper for reviewed world install/reinstall workflows.',
                'required_inputs' => array('realm_id', 'confirmation_phrase'),
                'supports_dry_run' => true,
                'verification_rules' => array('Confirm the selected world DB and patch set before execution.'),
                'prechecks' => array('server_offline', 'confirmation_phrase'),
            ),
            'db_update_install' => array(
                'id' => 'db_update_install',
                'label' => 'DB Update / Install',
                'category' => 'db_update_install',
                'risk_level' => 'reviewed',
                'scope' => 'single_realm',
                'execution_mode' => 'external_tool',
                'summary' => 'Run the reviewed DB patch/install pipeline from the website control plane.',
                'required_inputs' => array('realm_id'),
                'supports_dry_run' => true,
                'verification_rules' => array('Confirm patch inventory and tool output before marking complete.'),
            ),
            'transfer_conversion' => array(
                'id' => 'transfer_conversion',
                'label' => 'Transfer Conversion',
                'category' => 'conversion_tools',
                'risk_level' => 'reviewed',
                'scope' => 'cross_realm',
                'execution_mode' => 'sql_job',
                'summary' => 'Prepare launcher-reviewed conversion SQL between supported expansion targets.',
                'required_inputs' => array('source_realm_id', 'target_realm_id'),
                'supports_dry_run' => true,
                'verification_rules' => array('Review the exact source and target DB names before execution.'),
                'prechecks' => array('asset_exists'),
                'asset_path' => $assetRoot . '\\convert_*.sql',
            ),
            'save_export_import' => array(
                'id' => 'save_export_import',
                'label' => 'Save Export / Import',
                'category' => 'export_import',
                'risk_level' => 'reviewed',
                'scope' => 'single_realm',
                'execution_mode' => 'external_tool',
                'summary' => 'Queue `mysqldump`-style save export/import jobs with previewed paths.',
                'required_inputs' => array('realm_id'),
                'supports_dry_run' => true,
                'verification_rules' => array('Confirm save slot path readability and expected output artifact.'),
            ),
        );
    }
}

if (!function_exists('spp_admin_operations_descriptor')) {
    function spp_admin_operations_descriptor(string $operationId, array $realmDbMap): array
    {
        $descriptors = spp_admin_operations_descriptors($realmDbMap);
        return (array)($descriptors[$operationId] ?? array());
    }
}

if (!function_exists('spp_admin_operations_filter_descriptors_by_category')) {
    function spp_admin_operations_filter_descriptors_by_category(array $descriptors, string $category): array
    {
        $filtered = array();
        foreach ($descriptors as $descriptor) {
            if ((string)($descriptor['category'] ?? '') === $category) {
                $filtered[] = $descriptor;
            }
        }
        return $filtered;
    }
}

if (!function_exists('spp_admin_operations_queue_table_name')) {
    function spp_admin_operations_queue_table_name(): string
    {
        return 'website_admin_operation_jobs';
    }
}

if (!function_exists('spp_admin_operations_ensure_jobs_table')) {
    function spp_admin_operations_ensure_jobs_table(PDO $pdo): void
    {
        if (spp_db_table_exists($pdo, spp_admin_operations_queue_table_name())) {
            return;
        }

        $pdo->exec(
            "CREATE TABLE `" . spp_admin_operations_queue_table_name() . "` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `operation_id` VARCHAR(64) NOT NULL,
                `operation_label` VARCHAR(255) NOT NULL,
                `operation_category` VARCHAR(64) NOT NULL,
                `risk_level` VARCHAR(32) NOT NULL,
                `realm_scope` VARCHAR(32) NOT NULL,
                `realm_id` INT NOT NULL DEFAULT 0,
                `source_realm_id` INT NOT NULL DEFAULT 0,
                `target_realm_id` INT NOT NULL DEFAULT 0,
                `execution_mode` VARCHAR(32) NOT NULL,
                `submitted_by` INT NOT NULL DEFAULT 0,
                `submitted_inputs_json` MEDIUMTEXT NULL,
                `preview_text` MEDIUMTEXT NULL,
                `execution_log` MEDIUMTEXT NULL,
                `verification_summary` MEDIUMTEXT NULL,
                `status` VARCHAR(32) NOT NULL DEFAULT 'queued',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `started_at` DATETIME NULL DEFAULT NULL,
                `finished_at` DATETIME NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_operation_status` (`operation_id`, `status`),
                KEY `idx_operation_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }
}

if (!function_exists('spp_admin_operations_confirmation_phrase')) {
    function spp_admin_operations_confirmation_phrase(string $operationId): string
    {
        return 'QUEUE ' . strtoupper(str_replace('_', ' ', $operationId));
    }
}

if (!function_exists('spp_admin_operations_realm_option_map')) {
    function spp_admin_operations_realm_option_map(array $realmOptions): array
    {
        $map = array();
        foreach ($realmOptions as $realmOption) {
            $map[(int)($realmOption['id'] ?? 0)] = $realmOption;
        }
        return $map;
    }
}

if (!function_exists('spp_admin_operations_render_preview')) {
    function spp_admin_operations_render_preview(array $descriptor, array $inputs, array $realmMap): string
    {
        $operationId = (string)($descriptor['id'] ?? '');
        $realmId = (int)($inputs['realm_id'] ?? 0);
        $sourceRealmId = (int)($inputs['source_realm_id'] ?? 0);
        $targetRealmId = (int)($inputs['target_realm_id'] ?? 0);
        $value = trim((string)($inputs['value'] ?? ''));
        $lines = array();

        if ($realmId > 0 && isset($realmMap[$realmId])) {
            $lines[] = 'Target realm: ' . (string)$realmMap[$realmId]['label'];
            $lines[] = 'Target chars DB: ' . (string)($realmMap[$realmId]['chars_schema'] ?? '(unknown)');
        }
        if ($sourceRealmId > 0 && isset($realmMap[$sourceRealmId])) {
            $lines[] = 'Source realm: ' . (string)$realmMap[$sourceRealmId]['label'];
            $lines[] = 'Source chars DB: ' . (string)($realmMap[$sourceRealmId]['chars_schema'] ?? '(unknown)');
        }
        if ($targetRealmId > 0 && isset($realmMap[$targetRealmId])) {
            $lines[] = 'Target realm: ' . (string)$realmMap[$targetRealmId]['label'];
            $lines[] = 'Target chars DB: ' . (string)($realmMap[$targetRealmId]['chars_schema'] ?? '(unknown)');
        }

        if ($operationId === 'apply_realm_name') {
            $lines[] = '';
            $lines[] = 'SQL Preview:';
            $lines[] = "UPDATE `realmlist` SET `name` = '" . str_replace("'", "\\'", $value) . "' WHERE `id` = " . $realmId . ' LIMIT 1;';
        } elseif ($operationId === 'apply_realm_address') {
            $lines[] = '';
            $lines[] = 'SQL Preview:';
            $lines[] = "UPDATE `realmlist` SET `address` = '" . str_replace("'", "\\'", $value) . "' WHERE `id` = " . $realmId . ' LIMIT 1;';
        } elseif (($descriptor['execution_mode'] ?? '') === 'sql_job') {
            $assetPath = (string)($descriptor['asset_path'] ?? '');
            if ($assetPath !== '') {
                $lines[] = '';
                $lines[] = 'SQL Asset: ' . $assetPath;
            }
        } elseif (($descriptor['execution_mode'] ?? '') === 'external_tool') {
            $lines[] = '';
            $lines[] = 'Tool preview: website-managed wrapper will resolve command template at execution time.';
        }

        $verificationRules = (array)($descriptor['verification_rules'] ?? array());
        if (!empty($verificationRules)) {
            $lines[] = '';
            $lines[] = 'Verification:';
            foreach ($verificationRules as $rule) {
                $lines[] = '- ' . (string)$rule;
            }
        }

        return trim(implode("\n", $lines));
    }
}

if (!function_exists('spp_admin_operations_precheck_results')) {
    function spp_admin_operations_precheck_results(array $descriptor, array $inputs, array $realmMap): array
    {
        $prechecks = (array)($descriptor['prechecks'] ?? array());
        $results = array();
        $realmId = (int)($inputs['realm_id'] ?? 0);
        $confirmationPhrase = trim((string)($inputs['confirmation_phrase'] ?? ''));
        $expectedPhrase = spp_admin_operations_confirmation_phrase((string)($descriptor['id'] ?? ''));

        foreach ($prechecks as $precheck) {
            if ($precheck === 'asset_exists') {
                $assetPath = (string)($descriptor['asset_path'] ?? '');
                $exists = $assetPath !== '' && (strpos($assetPath, '*') !== false ? !empty(glob($assetPath)) : is_file($assetPath));
                $results[] = array(
                    'label' => 'SQL / tool asset present',
                    'ok' => $exists,
                    'detail' => $assetPath !== '' ? $assetPath : 'No asset path defined.',
                );
            } elseif ($precheck === 'server_offline') {
                $realm = (array)($realmMap[$realmId] ?? array());
                $host = (string)($realm['address'] ?? '');
                $port = (int)($realm['port'] ?? 0);
                $reachable = false;
                if ($host !== '' && $port > 0) {
                    $connection = @fsockopen($host, $port, $errno, $errstr, 1.5);
                    if (is_resource($connection)) {
                        $reachable = true;
                        fclose($connection);
                    }
                }
                $results[] = array(
                    'label' => 'Realm offline precheck',
                    'ok' => !$reachable,
                    'detail' => $host !== '' && $port > 0 ? ($host . ':' . $port) : 'Missing realm address/port.',
                );
            } elseif ($precheck === 'confirmation_phrase') {
                $results[] = array(
                    'label' => 'Confirmation phrase matched',
                    'ok' => $confirmationPhrase === $expectedPhrase,
                    'detail' => 'Expected: ' . $expectedPhrase,
                );
            }
        }

        return $results;
    }
}
