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

if (!function_exists('spp_admin_operations_family_order')) {
    function spp_admin_operations_family_order(): array
    {
        return array(
            'realm_character_maintenance',
            'realm_world_maintenance',
            'realm_economy_maintenance',
            'realm_guid_repairs',
            'realm_integrity_cleanup',
            'site_account_maintenance',
        );
    }
}

if (!function_exists('spp_admin_operations_family_labels')) {
    function spp_admin_operations_family_labels(): array
    {
        return array(
            'realm_character_maintenance' => 'Realm Character Maintenance',
            'realm_world_maintenance' => 'Realm World Maintenance',
            'realm_economy_maintenance' => 'Realm Economy Maintenance',
            'realm_guid_repairs' => 'Realm GUID / Sequence Repairs',
            'realm_integrity_cleanup' => 'Realm Integrity / Orphan Cleanup',
            'site_account_maintenance' => 'Site Account Maintenance',
        );
    }
}

if (!function_exists('spp_admin_operations_family_descriptions')) {
    function spp_admin_operations_family_descriptions(): array
    {
        return array(
            'realm_character_maintenance' => 'Heavy realm-scoped character maintenance that stays in the reviewed website queue.',
            'realm_world_maintenance' => 'Planned realm world maintenance wrappers. Visible here, but not executable in v1.',
            'realm_economy_maintenance' => 'Separate destructive resets for high-growth realm economy systems.',
            'realm_guid_repairs' => 'Reviewed reseed and identifier repair work without promising dense rewrites.',
            'realm_integrity_cleanup' => 'Narrow cleanup and recount packages for provably invalid realm data.',
            'site_account_maintenance' => 'Website-wide account resets with shared-impact warnings.',
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
            $name = spp_realm_display_name($realmId, $realmDbMap);
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

if (!function_exists('spp_admin_operations_descriptor_defaults')) {
    function spp_admin_operations_descriptor_defaults(): array
    {
        return array(
            'required_inputs' => array(),
            'verification_rules' => array(),
            'affected_tables' => array(),
            'supports_dry_run' => true,
            'blocking_rules' => array(),
            'native_href' => '',
            'asset_path' => '',
            'prechecks' => array(),
            'v1_status_note' => '',
            'deferred_note' => '',
            'ui_cta' => '',
            'is_link_only' => false,
            'is_deferred' => false,
        );
    }
}

if (!function_exists('spp_admin_operations_descriptor_build')) {
    function spp_admin_operations_descriptor_build(array $descriptor): array
    {
        $descriptor = array_merge(spp_admin_operations_descriptor_defaults(), $descriptor);
        if ((string)$descriptor['family_label'] === '') {
            $familyLabels = spp_admin_operations_family_labels();
            $descriptor['family_label'] = (string)($familyLabels[$descriptor['family_id']] ?? $descriptor['family_id']);
        }
        if ((string)$descriptor['ui_cta'] === '') {
            if (!empty($descriptor['is_link_only'])) {
                $descriptor['ui_cta'] = 'Open native tool';
            } elseif (!empty($descriptor['is_deferred'])) {
                $descriptor['ui_cta'] = 'Planned for later';
            } else {
                $descriptor['ui_cta'] = 'Queue job';
            }
        }

        return $descriptor;
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
            'character_reset_scoped' => spp_admin_operations_descriptor_build(array(
                'id' => 'character_reset_scoped',
                'label' => 'Scoped Character Reset',
                'family_id' => 'realm_character_maintenance',
                'risk_level' => 'destructive',
                'ownership_scope' => 'realm-specific',
                'delivery_mode' => 'SQL package',
                'scope' => 'single_realm',
                'execution_mode' => 'sql_job',
                'v1_status' => 'yes',
                'summary' => 'Delete selected character-layer data for one realm while preserving auth accounts.',
                'required_inputs' => array('realm_id', 'scope_profile', 'confirmation_phrase'),
                'verification_rules' => array(
                    'Keep the target realm offline for the full destructive pass.',
                    'Validate dense character GUID remap coverage before execution.',
                    'Recount realmcharacters and repair realm-local identity pointers after the rewrite.',
                ),
                'affected_tables' => array(
                    'characters',
                    'character_inventory',
                    'character_spell',
                    'mail',
                    'mail_items',
                    'item_instance',
                    'realmcharacters',
                    'website_identities.character_guid',
                ),
                'blocking_rules' => array(
                    'Fail closed if a dependent character GUID table is present but unsupported for deterministic remap.',
                    'Fail closed unless the target realm is offline before maintenance starts.',
                ),
                'prechecks' => array('server_offline', 'confirmation_phrase'),
                'scope_profiles' => array(
                    'bots' => 'Delete only accounts where username matches `rndbot%`.',
                    'admin' => 'Delete only non-bot accounts with `gmlevel >= 3`.',
                    'all' => 'Delete all characters in the selected realm.',
                ),
            )),
            'world_reinstall' => spp_admin_operations_descriptor_build(array(
                'id' => 'world_reinstall',
                'label' => 'World Reinstall',
                'family_id' => 'realm_world_maintenance',
                'risk_level' => 'destructive',
                'ownership_scope' => 'realm-specific',
                'delivery_mode' => 'external tool wrapper',
                'scope' => 'single_realm',
                'execution_mode' => 'external_tool',
                'v1_status' => 'deferred',
                'is_deferred' => true,
                'summary' => 'Planned wrapper for reviewed install/update assets. Not executable from Operations in v1.',
                'required_inputs' => array('realm_id'),
                'verification_rules' => array(
                    'Review the approved install/update asset set before implementation work begins.',
                ),
                'affected_tables' => array('world schema', 'realm install assets'),
                'deferred_note' => 'Execution stays deferred until the website wraps reviewed launcher or DB-install assets instead of inventing a fake world-reset workflow.',
                'v1_status_note' => 'Visible in taxonomy only. Do not queue in v1.',
            )),
            'auction_house_reset' => spp_admin_operations_descriptor_build(array(
                'id' => 'auction_house_reset',
                'label' => 'Auction House Reset',
                'family_id' => 'realm_economy_maintenance',
                'risk_level' => 'destructive',
                'ownership_scope' => 'realm-specific',
                'delivery_mode' => 'SQL package',
                'scope' => 'single_realm',
                'execution_mode' => 'sql_job',
                'v1_status' => 'yes',
                'summary' => 'Clear auction rows and auction-linked residue for the selected realm.',
                'required_inputs' => array('realm_id', 'confirmation_phrase'),
                'verification_rules' => array(
                    'Confirm the selected realm stayed offline during the destructive reset.',
                    'Verify `auctionhouse` row count is zero after the package completes.',
                    'Review auction-linked `item_instance` cleanup for unreferenced rows only.',
                ),
                'affected_tables' => array('auctionhouse', 'item_instance'),
                'blocking_rules' => array(
                    'Fail closed if item cleanup cannot prove the remaining references are safe.',
                ),
                'prechecks' => array('server_offline', 'confirmation_phrase'),
            )),
            'mail_reset' => spp_admin_operations_descriptor_build(array(
                'id' => 'mail_reset',
                'label' => 'Mail Reset',
                'family_id' => 'realm_economy_maintenance',
                'risk_level' => 'destructive',
                'ownership_scope' => 'realm-specific',
                'delivery_mode' => 'SQL package',
                'scope' => 'single_realm',
                'execution_mode' => 'sql_job',
                'v1_status' => 'yes',
                'summary' => 'Clear realm mail state and mail-linked residue for the selected realm.',
                'required_inputs' => array('realm_id', 'confirmation_phrase'),
                'verification_rules' => array(
                    'Confirm `mail` and `mail_items` are empty after the reset.',
                    'Cleanup orphaned `item_text` and mail-linked `item_instance` rows only when unreferenced elsewhere.',
                ),
                'affected_tables' => array('mail', 'mail_items', 'item_text', 'item_instance'),
                'blocking_rules' => array(
                    'Fail closed if mail-linked item cleanup cannot validate its dependency checks.',
                ),
                'prechecks' => array('server_offline', 'confirmation_phrase'),
            )),
            'auction_house_guid_reseed' => spp_admin_operations_descriptor_build(array(
                'id' => 'auction_house_guid_reseed',
                'label' => 'Auction House GUID Reseed',
                'family_id' => 'realm_guid_repairs',
                'risk_level' => 'reviewed',
                'ownership_scope' => 'realm-specific',
                'delivery_mode' => 'SQL package',
                'scope' => 'single_realm',
                'execution_mode' => 'sql_job',
                'v1_status' => 'yes',
                'summary' => 'Review current max auction identifiers and reseed only when the engine supports it safely.',
                'required_inputs' => array('realm_id'),
                'verification_rules' => array(
                    'Inspect current max auction identifier before generating the package.',
                    'Verify there are no collisions after the reseed.',
                ),
                'affected_tables' => array('auctionhouse'),
                'blocking_rules' => array(
                    'Fail closed unless the realm schema exposes a supported auction identifier strategy.',
                ),
            )),
            'mail_guid_reseed' => spp_admin_operations_descriptor_build(array(
                'id' => 'mail_guid_reseed',
                'label' => 'Mail GUID Reseed',
                'family_id' => 'realm_guid_repairs',
                'risk_level' => 'reviewed',
                'ownership_scope' => 'realm-specific',
                'delivery_mode' => 'SQL package',
                'scope' => 'single_realm',
                'execution_mode' => 'sql_job',
                'v1_status' => 'yes',
                'summary' => 'Review current max mail id and reseed to the next safe value.',
                'required_inputs' => array('realm_id'),
                'verification_rules' => array(
                    'Inspect `MAX(id)` from `mail` before reseeding.',
                    'Verify subsequent inserts allocate above the existing high-water mark.',
                ),
                'affected_tables' => array('mail'),
                'blocking_rules' => array(
                    'Fail closed if the engine family does not support deterministic reseed behavior.',
                ),
            )),
            'character_reference_cleanup' => spp_admin_operations_descriptor_build(array(
                'id' => 'character_reference_cleanup',
                'label' => 'Character Reference Cleanup',
                'family_id' => 'realm_integrity_cleanup',
                'risk_level' => 'reviewed',
                'ownership_scope' => 'realm-specific',
                'delivery_mode' => 'SQL package',
                'scope' => 'single_realm',
                'execution_mode' => 'sql_job',
                'v1_status' => 'yes',
                'summary' => 'Delete rows in character-linked tables whose owning character no longer exists.',
                'required_inputs' => array('realm_id'),
                'verification_rules' => array(
                    'Review each supported character-linked table predicate before execution.',
                    'Confirm only rows keyed by missing `characters.guid` are removed.',
                ),
                'affected_tables' => array(
                    'character_action',
                    'character_aura',
                    'character_inventory',
                    'character_social',
                    'character_spell',
                ),
            )),
            'mail_orphan_cleanup' => spp_admin_operations_descriptor_build(array(
                'id' => 'mail_orphan_cleanup',
                'label' => 'Mail Orphan Cleanup',
                'family_id' => 'realm_integrity_cleanup',
                'risk_level' => 'reviewed',
                'ownership_scope' => 'realm-specific',
                'delivery_mode' => 'SQL package',
                'scope' => 'single_realm',
                'execution_mode' => 'sql_job',
                'v1_status' => 'yes',
                'summary' => 'Delete mail-linked rows that no longer map to a live mail record.',
                'required_inputs' => array('realm_id'),
                'verification_rules' => array(
                    'Delete only orphaned `mail_items` and `item_text` rows.',
                    'Recheck remaining `mail` row counts after cleanup.',
                ),
                'affected_tables' => array('mail_items', 'item_text', 'mail'),
            )),
            'item_reference_cleanup' => spp_admin_operations_descriptor_build(array(
                'id' => 'item_reference_cleanup',
                'label' => 'Item Reference Cleanup',
                'family_id' => 'realm_integrity_cleanup',
                'risk_level' => 'reviewed',
                'ownership_scope' => 'realm-specific',
                'delivery_mode' => 'SQL package',
                'scope' => 'single_realm',
                'execution_mode' => 'sql_job',
                'v1_status' => 'yes',
                'summary' => 'Delete item rows that are no longer referenced by live inventory, AH, guild bank, mail, or gifts.',
                'required_inputs' => array('realm_id'),
                'verification_rules' => array(
                    'Confirm item cleanup predicates cover all supported live references before execution.',
                ),
                'affected_tables' => array('item_instance', 'item_loot', 'item_text'),
                'blocking_rules' => array(
                    'Fail closed if the realm family exposes unsupported item reference paths.',
                ),
            )),
            'realmcharacters_recount_repair' => spp_admin_operations_descriptor_build(array(
                'id' => 'realmcharacters_recount_repair',
                'label' => 'Realmcharacters Recount Repair',
                'family_id' => 'realm_integrity_cleanup',
                'risk_level' => 'reviewed',
                'ownership_scope' => 'realm-specific',
                'delivery_mode' => 'SQL package',
                'scope' => 'single_realm',
                'execution_mode' => 'sql_job',
                'v1_status' => 'yes',
                'summary' => 'Recount `realmcharacters.numchars` from the selected realm characters table.',
                'required_inputs' => array('realm_id'),
                'verification_rules' => array(
                    'Compare the recount against the selected realm `characters` table after execution.',
                ),
                'affected_tables' => array('realmcharacters', 'characters'),
            )),
            'account_reset_global' => spp_admin_operations_descriptor_build(array(
                'id' => 'account_reset_global',
                'label' => 'Global Account Reset',
                'family_id' => 'site_account_maintenance',
                'risk_level' => 'destructive',
                'ownership_scope' => 'website-wide',
                'delivery_mode' => 'SQL package',
                'scope' => 'global',
                'execution_mode' => 'sql_job',
                'v1_status' => 'yes',
                'summary' => 'Remove auth and website account data together. Clearly separated from realm-scoped character resets.',
                'required_inputs' => array('confirmation_phrase'),
                'verification_rules' => array(
                    'Review shared-impact warnings before execution.',
                    'Verify auth, realmcharacters, and website account data were intentionally selected together.',
                ),
                'affected_tables' => array(
                    'account',
                    'account_access',
                    'account_banned',
                    'realmcharacters',
                    'website_accounts',
                ),
                'blocking_rules' => array(
                    'Fail closed unless the operator confirms the shared website-wide impact.',
                ),
                'prechecks' => array('confirmation_phrase'),
            )),
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

if (!function_exists('spp_admin_operations_scope_label')) {
    function spp_admin_operations_scope_label(array $descriptor): string
    {
        return (string)($descriptor['ownership_scope'] ?? ($descriptor['scope'] ?? 'realm-specific'));
    }
}

if (!function_exists('spp_admin_operations_delivery_label')) {
    function spp_admin_operations_delivery_label(array $descriptor): string
    {
        return (string)($descriptor['delivery_mode'] ?? ($descriptor['execution_mode'] ?? 'SQL package'));
    }
}

if (!function_exists('spp_admin_operations_status_label')) {
    function spp_admin_operations_status_label(array $descriptor): string
    {
        $status = (string)($descriptor['v1_status'] ?? 'yes');
        if ($status === 'deferred') {
            return 'Deferred in v1';
        }
        if ($status === 'link_out') {
            return 'Native link-out';
        }
        return 'Queueable in v1';
    }
}

if (!function_exists('spp_admin_operations_is_queueable')) {
    function spp_admin_operations_is_queueable(array $descriptor): bool
    {
        return empty($descriptor['is_link_only']) && empty($descriptor['is_deferred']);
    }
}

if (!function_exists('spp_admin_operations_format_realm_line')) {
    function spp_admin_operations_format_realm_line(string $label, array $realm): string
    {
        $parts = array((string)($realm['label'] ?? $label));
        if ((string)($realm['chars_schema'] ?? '') !== '') {
            $parts[] = 'chars: ' . (string)$realm['chars_schema'];
        }
        if ((string)($realm['world_schema'] ?? '') !== '') {
            $parts[] = 'world: ' . (string)$realm['world_schema'];
        }
        return $label . ': ' . implode(' | ', $parts);
    }
}

if (!function_exists('spp_admin_operations_render_preview')) {
    function spp_admin_operations_render_preview(array $descriptor, array $inputs, array $realmMap): string
    {
        $realmId = (int)($inputs['realm_id'] ?? 0);
        $sourceRealmId = (int)($inputs['source_realm_id'] ?? 0);
        $targetRealmId = (int)($inputs['target_realm_id'] ?? 0);
        $scopeProfile = trim((string)($inputs['scope_profile'] ?? ''));
        $lines = array();

        $lines[] = 'Operation: ' . (string)($descriptor['label'] ?? 'Unknown operation');
        $lines[] = 'Family: ' . (string)($descriptor['family_label'] ?? 'Operations');
        $lines[] = 'Risk: ' . (string)($descriptor['risk_level'] ?? 'safe');
        $lines[] = 'Ownership: ' . spp_admin_operations_scope_label($descriptor);
        $lines[] = 'Delivery: ' . spp_admin_operations_delivery_label($descriptor);
        $lines[] = 'V1 status: ' . spp_admin_operations_status_label($descriptor);

        if ($realmId > 0 && isset($realmMap[$realmId])) {
            $lines[] = spp_admin_operations_format_realm_line('Target realm', (array)$realmMap[$realmId]);
        }
        if ($sourceRealmId > 0 && isset($realmMap[$sourceRealmId])) {
            $lines[] = spp_admin_operations_format_realm_line('Source realm', (array)$realmMap[$sourceRealmId]);
        }
        if ($targetRealmId > 0 && isset($realmMap[$targetRealmId])) {
            $lines[] = spp_admin_operations_format_realm_line('Target realm', (array)$realmMap[$targetRealmId]);
        }

        if ($scopeProfile !== '') {
            $scopeProfiles = (array)($descriptor['scope_profiles'] ?? array());
            $lines[] = 'Selected scope: ' . $scopeProfile . (isset($scopeProfiles[$scopeProfile]) ? ' - ' . (string)$scopeProfiles[$scopeProfile] : '');
        }

        $affectedTables = array_filter(array_map('strval', (array)($descriptor['affected_tables'] ?? array())));
        if (!empty($affectedTables)) {
            $lines[] = '';
            $lines[] = 'Affected tables / subsystem scope:';
            foreach ($affectedTables as $affectedTable) {
                $lines[] = '- ' . $affectedTable;
            }
        }

        $blockingRules = array_filter(array_map('strval', (array)($descriptor['blocking_rules'] ?? array())));
        if (!empty($blockingRules)) {
            $lines[] = '';
            $lines[] = 'Blocking conditions:';
            foreach ($blockingRules as $blockingRule) {
                $lines[] = '- ' . $blockingRule;
            }
        }

        if (!empty($descriptor['is_link_only'])) {
            $lines[] = '';
            $lines[] = 'Native link-out: ' . (string)($descriptor['native_href'] ?? '');
            if ((string)($descriptor['v1_status_note'] ?? '') !== '') {
                $lines[] = 'Note: ' . (string)$descriptor['v1_status_note'];
            }
        } elseif (!empty($descriptor['is_deferred'])) {
            $lines[] = '';
            $lines[] = 'Deferred note: ' . (string)($descriptor['deferred_note'] ?? 'This workflow is intentionally deferred from v1 execution.');
        } elseif (($descriptor['execution_mode'] ?? '') === 'external_tool') {
            $lines[] = '';
            $lines[] = 'Wrapper preview: website-managed external tool wrapper will resolve the reviewed command or asset set at execution time.';
        } else {
            $assetPath = (string)($descriptor['asset_path'] ?? '');
            if ($assetPath !== '') {
                $lines[] = '';
                $lines[] = 'SQL asset: ' . $assetPath;
            } else {
                $lines[] = '';
                $lines[] = 'SQL package: reviewed package text is generated from the selected operation metadata and prechecks.';
            }
        }

        $verificationRules = array_filter(array_map('strval', (array)($descriptor['verification_rules'] ?? array())));
        if (!empty($verificationRules)) {
            $lines[] = '';
            $lines[] = 'Verification checklist:';
            foreach ($verificationRules as $rule) {
                $lines[] = '- ' . $rule;
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
