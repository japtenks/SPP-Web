<?php

require_once __DIR__ . '/../support/db-schema.php';

if (!function_exists('spp_admin_chartransfer_supported_core_tables')) {
    function spp_admin_chartransfer_supported_core_tables(): array
    {
        return array(
            'auctionhouse',
            'character_action',
            'character_gifts',
            'character_homebind',
            'character_inventory',
            'character_pet',
            'character_queststatus',
            'character_reputation',
            'character_spell',
            'characters',
            'corpse',
            'item_instance',
            'item_text',
            'mail',
            'mail_items',
            'petition',
            'pet_spell',
        );
    }
}

if (!function_exists('spp_admin_chartransfer_deferred_exact_tables')) {
    function spp_admin_chartransfer_deferred_exact_tables(): array
    {
        return array(
            'character_declinedname',
            'character_forgotten_skills',
            'character_honor_cp',
            'character_pet_declinedname',
            'character_queststatus_daily',
            'character_queststatus_monthly',
            'event_group_chosen',
        );
    }
}

if (!function_exists('spp_admin_chartransfer_deferred_prefixes')) {
    function spp_admin_chartransfer_deferred_prefixes(): array
    {
        return array(
            'arena_',
            'guild_bank_',
        );
    }
}

if (!function_exists('spp_admin_chartransfer_is_deferred_table')) {
    function spp_admin_chartransfer_is_deferred_table(string $tableName): bool
    {
        if (in_array($tableName, spp_admin_chartransfer_deferred_exact_tables(), true)) {
            return true;
        }

        foreach (spp_admin_chartransfer_deferred_prefixes() as $prefix) {
            if (strpos($tableName, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('spp_admin_chartransfer_expansion_label')) {
    function spp_admin_chartransfer_expansion_label(string $expansionKey): string
    {
        $labels = array(
            'classic' => 'Classic',
            'tbc' => 'The Burning Crusade',
            'wotlk' => 'Wrath of the Lich King',
        );

        return (string)($labels[$expansionKey] ?? strtoupper($expansionKey));
    }
}

if (!function_exists('spp_admin_chartransfer_default_realms')) {
    function spp_admin_chartransfer_default_realms(array $realmOptions): array
    {
        $sourceRealmId = 0;
        $targetRealmId = 0;

        foreach ($realmOptions as $realmOption) {
            if ((string)($realmOption['expansion_key'] ?? '') === 'classic' && $sourceRealmId === 0) {
                $sourceRealmId = (int)$realmOption['id'];
            }
            if ((string)($realmOption['expansion_key'] ?? '') === 'tbc' && $targetRealmId === 0) {
                $targetRealmId = (int)$realmOption['id'];
            }
        }

        if ($sourceRealmId === 0 && !empty($realmOptions[0]['id'])) {
            $sourceRealmId = (int)$realmOptions[0]['id'];
        }
        if ($targetRealmId === 0) {
            foreach ($realmOptions as $realmOption) {
                $candidateId = (int)($realmOption['id'] ?? 0);
                if ($candidateId > 0 && $candidateId !== $sourceRealmId) {
                    $targetRealmId = $candidateId;
                    break;
                }
            }
        }
        if ($targetRealmId === 0) {
            $targetRealmId = $sourceRealmId;
        }

        return array(
            'source_realm_id' => $sourceRealmId,
            'target_realm_id' => $targetRealmId,
        );
    }
}

if (!function_exists('spp_admin_chartransfer_realm_options')) {
    function spp_admin_chartransfer_realm_options(array $realmDbMap): array
    {
        $realmlistRows = array();

        try {
            $realmdPdo = spp_get_pdo('realmd', 1);
            $stmt = $realmdPdo->query("SELECT `id`, `name`, `address`, `port` FROM `realmlist` ORDER BY `id` ASC");
            $realmlistRows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array();
        } catch (Throwable $e) {
            $realmlistRows = array();
        }

        $realmlistMap = array();
        foreach ($realmlistRows as $row) {
            $realmlistMap[(int)($row['id'] ?? 0)] = $row;
        }

        $options = array();
        foreach ($realmDbMap as $realmId => $realmConfig) {
            $realmId = (int)$realmId;
            $realmlistRow = $realmlistMap[$realmId] ?? array();
            $expansionKey = spp_realm_to_expansion_key($realmId);

            $options[] = array(
                'id' => $realmId,
                'name' => (string)($realmlistRow['name'] ?? (spp_get_armory_realm_name($realmId) ?? ('Realm ' . $realmId))),
                'address' => (string)($realmlistRow['address'] ?? ''),
                'port' => (int)($realmlistRow['port'] ?? 0),
                'chars_schema' => (string)($realmConfig['chars'] ?? ''),
                'expansion_key' => $expansionKey,
                'expansion_label' => spp_admin_chartransfer_expansion_label($expansionKey),
            );
        }

        usort($options, static function (array $left, array $right): int {
            return (int)($left['id'] ?? 0) <=> (int)($right['id'] ?? 0);
        });

        return $options;
    }
}

if (!function_exists('spp_admin_chartransfer_schema_presence_map')) {
    function spp_admin_chartransfer_schema_presence_map(PDO $pdo, array $realmOptions): array
    {
        $schemas = array_values(array_unique(array_filter(array_map(static function (array $realmOption): string {
            return (string)($realmOption['chars_schema'] ?? '');
        }, $realmOptions))));

        if (empty($schemas)) {
            return array();
        }

        $placeholders = implode(',', array_fill(0, count($schemas), '?'));
        $stmt = $pdo->prepare("SELECT `SCHEMA_NAME` FROM information_schema.SCHEMATA WHERE `SCHEMA_NAME` IN ({$placeholders})");
        $stmt->execute($schemas);

        $presence = array_fill_keys($schemas, false);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $schemaName) {
            $presence[(string)$schemaName] = true;
        }

        return $presence;
    }
}

if (!function_exists('spp_admin_chartransfer_attach_schema_presence')) {
    function spp_admin_chartransfer_attach_schema_presence(array $realmOptions, array $schemaPresence): array
    {
        foreach ($realmOptions as $index => $realmOption) {
            $schemaName = (string)($realmOption['chars_schema'] ?? '');
            $realmOptions[$index]['schema_present'] = !empty($schemaPresence[$schemaName]);
        }

        return $realmOptions;
    }
}

if (!function_exists('spp_admin_chartransfer_realm_by_id')) {
    function spp_admin_chartransfer_realm_by_id(array $realmOptions, int $realmId): array
    {
        foreach ($realmOptions as $realmOption) {
            if ((int)($realmOption['id'] ?? 0) === $realmId) {
                return $realmOption;
            }
        }

        return array();
    }
}

if (!function_exists('spp_admin_chartransfer_socket_reachable')) {
    function spp_admin_chartransfer_socket_reachable(string $host, int $port): bool
    {
        if ($host === '' || $port <= 0) {
            return false;
        }

        $socket = @fsockopen($host, $port, $errorNo, $errorString, 0.5);
        if ($socket) {
            @fclose($socket);
            return true;
        }

        return false;
    }
}

if (!function_exists('spp_admin_chartransfer_fetch_schema_inventory')) {
    function spp_admin_chartransfer_fetch_schema_inventory(PDO $pdo, string $schemaName): array
    {
        $stmt = $pdo->prepare("
            SELECT `TABLE_NAME`, `COLUMN_NAME`
            FROM information_schema.COLUMNS
            WHERE `TABLE_SCHEMA` = ?
            ORDER BY `TABLE_NAME` ASC, `ORDINAL_POSITION` ASC
        ");
        $stmt->execute(array($schemaName));

        $coreTables = array_fill_keys(spp_admin_chartransfer_supported_core_tables(), array());
        $deferredTables = array();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $tableName = (string)($row['TABLE_NAME'] ?? '');
            $columnName = (string)($row['COLUMN_NAME'] ?? '');
            if ($tableName === '' || $columnName === '') {
                continue;
            }

            if (isset($coreTables[$tableName])) {
                $coreTables[$tableName][] = $columnName;
                continue;
            }

            if (spp_admin_chartransfer_is_deferred_table($tableName)) {
                if (!isset($deferredTables[$tableName])) {
                    $deferredTables[$tableName] = array();
                }
                $deferredTables[$tableName][] = $columnName;
            }
        }

        return array(
            'core_tables' => $coreTables,
            'deferred_tables' => $deferredTables,
        );
    }
}

if (!function_exists('spp_admin_chartransfer_compare_inventory')) {
    function spp_admin_chartransfer_compare_inventory(array $sourceInventory, array $targetInventory): array
    {
        $coreRows = array();
        $deferredRows = array();
        $blockingIssues = array();
        $deferredNotes = array();

        foreach (spp_admin_chartransfer_supported_core_tables() as $tableName) {
            $sourceColumns = array_values(array_unique($sourceInventory['core_tables'][$tableName] ?? array()));
            $targetColumns = array_values(array_unique($targetInventory['core_tables'][$tableName] ?? array()));
            sort($sourceColumns);
            sort($targetColumns);

            $sourceMissing = empty($sourceColumns);
            $targetMissing = empty($targetColumns);
            $sourceOnlyColumns = array_values(array_diff($sourceColumns, $targetColumns));
            $targetOnlyColumns = array_values(array_diff($targetColumns, $sourceColumns));

            $status = 'safe';
            if ($sourceMissing || $targetMissing || !empty($sourceOnlyColumns) || !empty($targetOnlyColumns)) {
                $status = 'mismatched';
                if ($sourceMissing || $targetMissing) {
                    $blockingIssues[] = sprintf('Core transfer table `%s` is not available in both schemas.', $tableName);
                } else {
                    $blockingIssues[] = sprintf('Core transfer table `%s` has column differences between source and target.', $tableName);
                }
            }

            $coreRows[] = array(
                'table' => $tableName,
                'status' => $status,
                'source_present' => !$sourceMissing,
                'target_present' => !$targetMissing,
                'source_only_columns' => $sourceOnlyColumns,
                'target_only_columns' => $targetOnlyColumns,
            );
        }

        $deferredTableNames = array_values(array_unique(array_merge(
            array_keys((array)($sourceInventory['deferred_tables'] ?? array())),
            array_keys((array)($targetInventory['deferred_tables'] ?? array()))
        )));
        sort($deferredTableNames);

        foreach ($deferredTableNames as $tableName) {
            $sourceColumns = array_values(array_unique($sourceInventory['deferred_tables'][$tableName] ?? array()));
            $targetColumns = array_values(array_unique($targetInventory['deferred_tables'][$tableName] ?? array()));
            sort($sourceColumns);
            sort($targetColumns);

            $sourcePresent = !empty($sourceColumns);
            $targetPresent = !empty($targetColumns);
            $sourceOnlyColumns = array_values(array_diff($sourceColumns, $targetColumns));
            $targetOnlyColumns = array_values(array_diff($targetColumns, $sourceColumns));

            $status = (!$sourcePresent || !$targetPresent || !empty($sourceOnlyColumns) || !empty($targetOnlyColumns))
                ? 'deferred'
                : 'review';

            $deferredRows[] = array(
                'table' => $tableName,
                'status' => $status,
                'source_present' => $sourcePresent,
                'target_present' => $targetPresent,
                'source_only_columns' => $sourceOnlyColumns,
                'target_only_columns' => $targetOnlyColumns,
            );
        }

        if (!empty($deferredRows)) {
            $deferredNotes[] = 'Expansion-specific side tables still need explicit transfer rules before any real move action can be enabled.';
            $blockingIssues[] = 'Expansion-specific side tables are present and are intentionally deferred in this dry-run-only release.';
        }

        return array(
            'core_rows' => $coreRows,
            'deferred_rows' => $deferredRows,
            'blocking_issues' => array_values(array_unique($blockingIssues)),
            'deferred_notes' => $deferredNotes,
        );
    }
}

if (!function_exists('spp_admin_chartransfer_find_character')) {
    function spp_admin_chartransfer_find_character(PDO $charsPdo, string $characterName): array
    {
        $characterName = trim($characterName);
        if ($characterName === '' || !spp_db_table_exists($charsPdo, 'characters')) {
            return array();
        }

        $fields = array('guid', 'account', 'name');
        if (spp_db_column_exists($charsPdo, 'characters', 'level')) {
            $fields[] = 'level';
        }
        if (spp_db_column_exists($charsPdo, 'characters', 'online')) {
            $fields[] = 'online';
        }

        $sql = 'SELECT ' . implode(', ', array_map(static function (string $field): string {
            return '`' . $field . '`';
        }, $fields)) . ' FROM `characters` WHERE `name` = ? LIMIT 1';

        $stmt = $charsPdo->prepare($sql);
        $stmt->execute(array($characterName));

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $row : array();
    }
}

if (!function_exists('spp_admin_chartransfer_supported_pair')) {
    function spp_admin_chartransfer_supported_pair(array $sourceRealm, array $targetRealm): bool
    {
        $sourceExpansion = (string)($sourceRealm['expansion_key'] ?? '');
        $targetExpansion = (string)($targetRealm['expansion_key'] ?? '');

        return array($sourceExpansion, $targetExpansion) === array('classic', 'tbc')
            || array($sourceExpansion, $targetExpansion) === array('tbc', 'classic');
    }
}

if (!function_exists('spp_admin_chartransfer_status_copy')) {
    function spp_admin_chartransfer_status_copy(string $statusKey): array
    {
        $map = array(
            'ready' => array(
                'label' => 'Ready For Future Transfer Support',
                'summary' => 'The selected pair passed the dry-run checks currently implemented here. Real transfer execution is still intentionally disabled in this wave.',
                'message_type' => 'success',
            ),
            'unsupported' => array(
                'label' => 'Unsupported Pair',
                'summary' => 'The selected source and target realms are outside the supported dry-run surface for this release.',
                'message_type' => 'error',
            ),
            'blocked' => array(
                'label' => 'Blocked',
                'summary' => 'The dry-run found character readiness and/or schema compatibility issues that must be resolved before real transfer support can be added.',
                'message_type' => 'error',
            ),
        );

        return $map[$statusKey] ?? $map['blocked'];
    }
}

if (!function_exists('spp_admin_chartransfer_probe')) {
    function spp_admin_chartransfer_probe(array $sourceRealm, array $targetRealm, string $characterName): array
    {
        $characterName = trim($characterName);
        $messages = array();
        $checkRows = array();
        $validationMessages = array();
        $coreRows = array();
        $deferredRows = array();
        $statusKey = 'blocked';

        $sourceSchema = (string)($sourceRealm['chars_schema'] ?? '');
        $targetSchema = (string)($targetRealm['chars_schema'] ?? '');

        $checkRows[] = array(
            'label' => 'Source realm',
            'status' => !empty($sourceRealm['schema_present']) ? 'Available' : 'Unavailable',
            'detail' => sprintf('%s uses `%s`.', (string)($sourceRealm['name'] ?? 'Source realm'), $sourceSchema !== '' ? $sourceSchema : 'unknown schema'),
        );
        $checkRows[] = array(
            'label' => 'Target realm',
            'status' => !empty($targetRealm['schema_present']) ? 'Available' : 'Unavailable',
            'detail' => sprintf('%s uses `%s`.', (string)($targetRealm['name'] ?? 'Target realm'), $targetSchema !== '' ? $targetSchema : 'unknown schema'),
        );

        $sourceReachable = spp_admin_chartransfer_socket_reachable((string)($sourceRealm['address'] ?? ''), (int)($sourceRealm['port'] ?? 0));
        $targetReachable = spp_admin_chartransfer_socket_reachable((string)($targetRealm['address'] ?? ''), (int)($targetRealm['port'] ?? 0));
        $checkRows[] = array(
            'label' => 'Source host reachability',
            'status' => $sourceReachable ? 'Reachable' : 'Offline / unreachable',
            'detail' => sprintf('%s:%d', (string)($sourceRealm['address'] ?? 'unknown-host'), (int)($sourceRealm['port'] ?? 0)),
        );
        $checkRows[] = array(
            'label' => 'Target host reachability',
            'status' => $targetReachable ? 'Reachable' : 'Offline / unreachable',
            'detail' => sprintf('%s:%d', (string)($targetRealm['address'] ?? 'unknown-host'), (int)($targetRealm['port'] ?? 0)),
        );

        if ($sourceReachable || $targetReachable) {
            $messages[] = array(
                'type' => 'error',
                'text' => 'One or both realm hosts are currently reachable. That is useful for dry-run visibility, but any future real transfer workflow should still require an offline maintenance window.',
            );
        }

        if ($characterName === '') {
            $validationMessages[] = 'Enter a character name before running the dry-run.';
        }

        if ((int)($sourceRealm['id'] ?? 0) === (int)($targetRealm['id'] ?? 0)) {
            $validationMessages[] = 'Choose different source and target realms for the transfer dry-run.';
        }

        if (!spp_admin_chartransfer_supported_pair($sourceRealm, $targetRealm)) {
            $statusKey = 'unsupported';
            $validationMessages[] = 'This dry-run release only supports Classic <-> TBC planning pairs.';
        }

        if (empty($sourceRealm['schema_present']) || empty($targetRealm['schema_present'])) {
            $statusKey = 'unsupported';
            $validationMessages[] = 'One or more selected character schemas are not live on the database server yet.';
        }

        if (!empty($validationMessages)) {
            return array(
                'status_key' => $statusKey,
                'messages' => $messages,
                'validation_messages' => $validationMessages,
                'check_rows' => $checkRows,
                'core_rows' => $coreRows,
                'deferred_rows' => $deferredRows,
            );
        }

        try {
            $infoPdo = spp_get_pdo('realmd', 1);
            $sourceCharsPdo = spp_get_pdo('chars', (int)$sourceRealm['id']);
            $targetCharsPdo = spp_get_pdo('chars', (int)$targetRealm['id']);
        } catch (Throwable $e) {
            return array(
                'status_key' => 'blocked',
                'messages' => $messages,
                'validation_messages' => array('Database connection failed while preparing the dry-run: ' . $e->getMessage()),
                'check_rows' => $checkRows,
                'core_rows' => $coreRows,
                'deferred_rows' => $deferredRows,
            );
        }

        $sourceCharacter = spp_admin_chartransfer_find_character($sourceCharsPdo, $characterName);
        $targetCharacter = spp_admin_chartransfer_find_character($targetCharsPdo, $characterName);

        $sourceFound = !empty($sourceCharacter);
        $targetCollision = !empty($targetCharacter);
        $hasOnlineState = $sourceFound && array_key_exists('online', $sourceCharacter);
        $characterOffline = $sourceFound && $hasOnlineState && (int)($sourceCharacter['online'] ?? 0) === 0;

        $checkRows[] = array(
            'label' => 'Source character',
            'status' => $sourceFound ? 'Found' : 'Missing',
            'detail' => $sourceFound
                ? sprintf(
                    '%s exists on %s as GUID %d%s.',
                    (string)($sourceCharacter['name'] ?? $characterName),
                    (string)($sourceRealm['name'] ?? 'the source realm'),
                    (int)($sourceCharacter['guid'] ?? 0),
                    isset($sourceCharacter['level']) ? ' (level ' . (int)$sourceCharacter['level'] . ')' : ''
                )
                : 'No character with that exact name was found on the source realm.',
        );
        $checkRows[] = array(
            'label' => 'Target name collision',
            'status' => $targetCollision ? 'Collision found' : 'No collision',
            'detail' => $targetCollision
                ? 'The target realm already has a character with that name.'
                : 'No matching character name was found on the target realm.',
        );
        $checkRows[] = array(
            'label' => 'Offline requirement',
            'status' => $characterOffline ? 'Offline' : 'Online / unknown',
            'detail' => $sourceFound
                ? (
                    array_key_exists('online', $sourceCharacter)
                        ? ((int)($sourceCharacter['online'] ?? 0) === 0 ? 'The source character is currently offline.' : 'The source character is currently online.')
                        : 'The source schema does not expose an `online` column for readiness checks.'
                )
                : 'Offline status cannot be checked until the source character is found.',
        );

        if (!$sourceFound) {
            $validationMessages[] = 'The selected source realm does not currently have a character with that name.';
        }
        if ($targetCollision) {
            $validationMessages[] = 'The selected target realm already has a character with that name.';
        }
        if (!$characterOffline) {
            $validationMessages[] = $hasOnlineState
                ? 'The source character must be offline before any future transfer can be considered.'
                : 'The source schema could not prove the character is offline, so the dry-run remains blocked.';
        }

        $comparison = spp_admin_chartransfer_compare_inventory(
            spp_admin_chartransfer_fetch_schema_inventory($infoPdo, $sourceSchema),
            spp_admin_chartransfer_fetch_schema_inventory($infoPdo, $targetSchema)
        );

        $coreRows = (array)($comparison['core_rows'] ?? array());
        $deferredRows = (array)($comparison['deferred_rows'] ?? array());
        foreach ((array)($comparison['blocking_issues'] ?? array()) as $blockingIssue) {
            $validationMessages[] = (string)$blockingIssue;
        }
        foreach ((array)($comparison['deferred_notes'] ?? array()) as $deferredNote) {
            $messages[] = array(
                'type' => 'error',
                'text' => (string)$deferredNote,
            );
        }

        if (empty($validationMessages)) {
            $statusKey = 'ready';
            $messages[] = array(
                'type' => 'success',
                'text' => 'The selected Classic/TBC pair passed the current dry-run checks. Real transfer execution is still intentionally disabled in this wave.',
            );
        }

        return array(
            'status_key' => $statusKey,
            'messages' => $messages,
            'validation_messages' => array_values(array_unique($validationMessages)),
            'check_rows' => $checkRows,
            'core_rows' => $coreRows,
            'deferred_rows' => $deferredRows,
        );
    }
}

if (!function_exists('spp_admin_chartransfer_load_page_state')) {
    function spp_admin_chartransfer_load_page_state(array $context = array()): array
    {
        $realmDbMap = (array)($context['realm_db_map'] ?? ($GLOBALS['realmDbMap'] ?? array()));
        $realmOptions = spp_admin_chartransfer_realm_options($realmDbMap);
        $defaultRealms = spp_admin_chartransfer_default_realms($realmOptions);

        $sourceRealmId = isset($_REQUEST['source_realm']) ? (int)$_REQUEST['source_realm'] : (int)$defaultRealms['source_realm_id'];
        $targetRealmId = isset($_REQUEST['target_realm']) ? (int)$_REQUEST['target_realm'] : (int)$defaultRealms['target_realm_id'];
        $characterName = trim((string)($_REQUEST['character_name'] ?? ''));
        $submitted = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST'
            && (string)($_POST['chartransfer_action'] ?? '') === 'probe';

        $infoPdo = spp_get_pdo('realmd', 1);
        $schemaPresence = spp_admin_chartransfer_schema_presence_map($infoPdo, $realmOptions);
        $realmOptions = spp_admin_chartransfer_attach_schema_presence($realmOptions, $schemaPresence);

        if (spp_admin_chartransfer_realm_by_id($realmOptions, $sourceRealmId) === array()) {
            $sourceRealmId = (int)$defaultRealms['source_realm_id'];
        }
        if (spp_admin_chartransfer_realm_by_id($realmOptions, $targetRealmId) === array()) {
            $targetRealmId = (int)$defaultRealms['target_realm_id'];
        }

        $sourceRealm = spp_admin_chartransfer_realm_by_id($realmOptions, $sourceRealmId);
        $targetRealm = spp_admin_chartransfer_realm_by_id($realmOptions, $targetRealmId);
        $probeState = array(
            'status_key' => '',
            'messages' => array(),
            'validation_messages' => array(),
            'check_rows' => array(),
            'core_rows' => array(),
            'deferred_rows' => array(),
        );

        if ($submitted) {
            spp_require_csrf('admin_chartransfer', 'The character transfer dry-run form expired. Please refresh the page and try again.');
            $probeState = spp_admin_chartransfer_probe($sourceRealm, $targetRealm, $characterName);
        }

        $statusCopy = !empty($probeState['status_key'])
            ? spp_admin_chartransfer_status_copy((string)$probeState['status_key'])
            : array(
                'label' => 'Dry-Run Only',
                'summary' => 'This page validates schema compatibility and character readiness. It does not execute any transfer writes.',
                'message_type' => 'success',
            );

        if (!isset($GLOBALS['pathway_info']) || !is_array($GLOBALS['pathway_info'])) {
            $GLOBALS['pathway_info'] = array();
        }
        $GLOBALS['pathway_info'][] = array('title' => 'Character Transfer', 'link' => 'index.php?n=admin&sub=chartransfer');

        return array(
            'chartransferActionUrl' => 'index.php?n=admin&sub=chartransfer',
            'adminChartransferCsrfToken' => spp_csrf_token('admin_chartransfer'),
            'realmOptions' => $realmOptions,
            'selectedSourceRealmId' => $sourceRealmId,
            'selectedTargetRealmId' => $targetRealmId,
            'characterName' => $characterName,
            'sourceRealm' => $sourceRealm,
            'targetRealm' => $targetRealm,
            'submitted' => $submitted,
            'statusKey' => (string)($probeState['status_key'] ?? ''),
            'statusLabel' => (string)($statusCopy['label'] ?? ''),
            'statusSummary' => (string)($statusCopy['summary'] ?? ''),
            'statusMessageType' => (string)($statusCopy['message_type'] ?? 'success'),
            'messages' => (array)($probeState['messages'] ?? array()),
            'validationMessages' => (array)($probeState['validation_messages'] ?? array()),
            'checkRows' => (array)($probeState['check_rows'] ?? array()),
            'coreTableRows' => (array)($probeState['core_rows'] ?? array()),
            'deferredTableRows' => (array)($probeState['deferred_rows'] ?? array()),
        );
    }
}
