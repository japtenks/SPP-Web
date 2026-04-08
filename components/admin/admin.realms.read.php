<?php
if (INCLUDED !== true) {
    exit;
}

if (!function_exists('spp_admin_realms_schema_requirements')) {
    function spp_admin_realms_schema_requirements(): array
    {
        return array(
            'site_realmd' => array(
                'label' => 'Website Realmd',
                'checks' => array(
                    array(
                        'label' => 'Website settings table',
                        'type' => 'table',
                        'table' => 'website_settings',
                        'notes' => 'Stores runtime overrides that survive config-file edits and restarts.',
                    ),
                    array(
                        'label' => 'Website accounts table',
                        'type' => 'table',
                        'table' => 'website_accounts',
                        'notes' => 'Backs account settings, avatars, signature state, and per-account site preferences.',
                    ),
                    array(
                        'label' => 'Website account preference columns',
                        'type' => 'columns',
                        'table' => 'website_accounts',
                        'columns' => array('background_mode', 'background_image', 'character_realm_id', 'show_hidden_forums'),
                        'notes' => 'Needed for background preferences, cross-realm character selection, and hidden-forum visibility toggles.',
                    ),
                    array(
                        'label' => 'Website account profile table',
                        'type' => 'table',
                        'table' => spp_account_profile_table_name(),
                        'notes' => 'Stores selected-character pointers and related account-profile state.',
                    ),
                    array(
                        'label' => 'Identity table',
                        'type' => 'table',
                        'table' => 'website_identities',
                        'notes' => 'Required for account, character, and bot identity backfills.',
                    ),
                    array(
                        'label' => 'Identity table core columns',
                        'type' => 'columns',
                        'table' => 'website_identities',
                        'columns' => array('realm_id', 'owner_account_id', 'character_guid', 'identity_type', 'is_bot'),
                        'notes' => 'Needed for identity coverage, cleanup, and speaker ownership linking.',
                    ),
                    array(
                        'label' => 'Identity profile table',
                        'type' => 'table',
                        'table' => spp_identity_profile_table_name(),
                        'notes' => 'Stores per-identity signatures and profile metadata.',
                    ),
                    array(
                        'label' => 'Identity profile columns',
                        'type' => 'columns',
                        'table' => spp_identity_profile_table_name(),
                        'columns' => array('identity_id', 'signature'),
                        'notes' => 'Needed to render and update forum signatures through identity-aware posting.',
                    ),
                ),
            ),
            'realm_realmd' => array(
                'label' => 'Realm Realmd',
                'checks' => array(
                    array(
                        'label' => 'Accounts table',
                        'type' => 'table',
                        'table' => 'account',
                        'notes' => 'Core realm account storage.',
                    ),
                    array(
                        'label' => 'Account columns used by admin and bot filters',
                        'type' => 'columns',
                        'table' => 'account',
                        'columns' => array('username', 'gmlevel'),
                        'notes' => 'Used across members, identities, and bot-specific account screens.',
                    ),
                    array(
                        'label' => 'Forum post identity column',
                        'type' => 'columns',
                        'table' => 'f_posts',
                        'columns' => array('poster_identity_id'),
                        'notes' => 'Enables forum posts to resolve speaking identities instead of only raw character ids.',
                    ),
                    array(
                        'label' => 'Forum topic identity column',
                        'type' => 'columns',
                        'table' => 'f_topics',
                        'columns' => array('topic_poster_identity_id'),
                        'notes' => 'Allows topic headers to stay aligned with identity-aware forum posting.',
                    ),
                    array(
                        'label' => 'PM identity columns',
                        'type' => 'columns',
                        'table' => 'website_pms',
                        'columns' => array('sender_identity_id', 'receiver_identity_id'),
                        'notes' => 'Needed for PM identity backfills and sender/receiver rendering.',
                    ),
                ),
            ),
            'realm_chars' => array(
                'label' => 'Realm Chars',
                'checks' => array(
                    array(
                        'label' => 'Characters table',
                        'type' => 'table',
                        'table' => 'characters',
                        'notes' => 'Required for character ownership, signatures, and roster-driven account views.',
                    ),
                    array(
                        'label' => 'Characters columns used by the website',
                        'type' => 'columns',
                        'table' => 'characters',
                        'columns' => array('guid', 'account', 'name'),
                        'notes' => 'Minimum columns needed for ownership checks and character-linked website features.',
                    ),
                ),
            ),
        );
    }
}

if (!function_exists('spp_admin_realms_schema_slot_meta')) {
    function spp_admin_realms_schema_slot_meta(PDO $siteRealmdPdo, array $realmDbMap): array
    {
        $meta = array();

        foreach ($realmDbMap as $realmId => $realmInfo) {
            $realmId = (int)$realmId;
            if ($realmId <= 0) {
                continue;
            }

            $meta[$realmId] = array(
                'id' => $realmId,
                'name' => '',
                'address' => '',
                'port' => 0,
                'realmd_db' => (string)($realmInfo['realmd'] ?? ''),
                'chars_db' => (string)($realmInfo['chars'] ?? ''),
            );
        }

        if (empty($meta)) {
            return $meta;
        }

        try {
            $placeholders = implode(',', array_fill(0, count($meta), '?'));
            $stmt = $siteRealmdPdo->prepare(
                "SELECT `id`, `name`, `address`, `port`
                 FROM `realmlist`
                 WHERE `id` IN ({$placeholders})
                 ORDER BY `id` ASC"
            );
            $stmt->execute(array_keys($meta));
            foreach (($stmt->fetchAll(PDO::FETCH_ASSOC) ?: array()) as $row) {
                $realmId = (int)($row['id'] ?? 0);
                if ($realmId <= 0 || !isset($meta[$realmId])) {
                    continue;
                }
                $meta[$realmId]['name'] = trim((string)($row['name'] ?? ''));
                $meta[$realmId]['address'] = trim((string)($row['address'] ?? ''));
                $meta[$realmId]['port'] = (int)($row['port'] ?? 0);
            }
        } catch (Throwable $e) {
            // Fall back to configured slot labels if realmlist metadata is unavailable.
        }

        return $meta;
    }
}

if (!function_exists('spp_admin_realms_schema_scope_label')) {
    function spp_admin_realms_schema_scope_label(array $slotMeta, string $dbKind): string
    {
        $realmId = (int)($slotMeta['id'] ?? 0);
        $realmName = trim((string)($slotMeta['name'] ?? ''));
        $dbName = $dbKind === 'chars'
            ? trim((string)($slotMeta['chars_db'] ?? ''))
            : trim((string)($slotMeta['realmd_db'] ?? ''));

        if ($realmName !== '') {
            $label = $realmName . ' ' . strtoupper($dbKind);
            if ($realmId > 0) {
                $label .= ' (ID ' . $realmId . ')';
            }
        } else {
            $label = 'Configured Realm Slot ' . $realmId . ' ' . strtoupper($dbKind);
        }

        if ($dbName !== '') {
            $label .= ' [' . $dbName . ']';
        }

        return $label;
    }
}

if (!function_exists('spp_admin_realms_schema_realmd_groups')) {
    function spp_admin_realms_schema_realmd_groups(array $realmDbMap, array $slotMetaMap): array
    {
        $groups = array();
        foreach ($realmDbMap as $realmId => $realmInfo) {
            $realmId = (int)$realmId;
            if ($realmId <= 0) {
                continue;
            }

            $slotMeta = (array)($slotMetaMap[$realmId] ?? array());
            $realmdDbName = trim((string)($slotMeta['realmd_db'] ?? ($realmInfo['realmd'] ?? '')));
            if ($realmdDbName === '') {
                $realmdDbName = 'realmd-slot-' . $realmId;
            }

            if (!isset($groups[$realmdDbName])) {
                $groups[$realmdDbName] = array(
                    'db_name' => $realmdDbName,
                    'slot_ids' => array(),
                    'slot_meta' => array(),
                );
            }

            $groups[$realmdDbName]['slot_ids'][] = $realmId;
            $groups[$realmdDbName]['slot_meta'][$realmId] = $slotMeta;
        }

        return $groups;
    }
}

if (!function_exists('spp_admin_realms_schema_realmd_topology')) {
    function spp_admin_realms_schema_realmd_topology(PDO $pdo): array
    {
        $result = array(
            'mode' => 'unknown',
            'summary' => '',
            'columns' => array(),
        );

        if (!spp_db_table_exists($pdo, 'realmd_db_version')) {
            $result['summary'] = 'No `realmd_db_version` table found.';
            return $result;
        }

        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM `realmd_db_version`");
            $columns = array();
            foreach (($stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array()) as $row) {
                $columnName = trim((string)($row['Field'] ?? ''));
                if ($columnName !== '' && preg_match('/^required_.*_realmd_joindate_datetime$/i', $columnName)) {
                    $columns[] = $columnName;
                }
            }

            $result['columns'] = $columns;
            $count = count($columns);
            if ($count > 1) {
                $result['mode'] = 'shared_realmd';
                $result['summary'] = 'Shared realmd / multi-world signature via `realmd_db_version`: ' . implode(', ', $columns);
            } elseif ($count === 1) {
                $result['mode'] = 'dedicated_realmd';
                $result['summary'] = 'Dedicated per-expansion realmd signature via `realmd_db_version`: ' . $columns[0];
            } else {
                $result['summary'] = 'No expansion marker columns found on `realmd_db_version`.';
            }
        } catch (Throwable $e) {
            $result['summary'] = 'Failed reading `realmd_db_version`: ' . $e->getMessage();
        }

        return $result;
    }
}

if (!function_exists('spp_admin_realms_schema_evaluate_check')) {
    function spp_admin_realms_schema_evaluate_check(PDO $pdo, array $check): array
    {
        $tableName = (string)($check['table'] ?? '');
        $exists = $tableName !== '' && spp_db_table_exists($pdo, $tableName);
        $missing = array();
        $ok = false;

        if (($check['type'] ?? 'table') === 'table') {
            $ok = $exists;
        } else {
            foreach ((array)($check['columns'] ?? array()) as $columnName) {
                if (!$exists || !spp_db_column_exists($pdo, $tableName, (string)$columnName)) {
                    $missing[] = (string)$columnName;
                }
            }
            $ok = $exists && empty($missing);
        }

        return array(
            'label' => (string)($check['label'] ?? $tableName),
            'type' => (string)($check['type'] ?? 'table'),
            'table' => $tableName,
            'columns' => array_values((array)($check['columns'] ?? array())),
            'notes' => (string)($check['notes'] ?? ''),
            'ok' => $ok,
            'missing' => $missing,
        );
    }
}

if (!function_exists('spp_admin_realms_schema_scan_db')) {
    function spp_admin_realms_schema_scan_db(string $key, string $label, ?PDO $pdo, array $checks, string $detail = ''): array
    {
        $items = array();
        $missingCount = 0;
        $status = 'ok';
        $error = '';

        if (!$pdo instanceof PDO) {
            foreach ($checks as $check) {
                $items[] = array(
                    'label' => (string)($check['label'] ?? ''),
                    'type' => (string)($check['type'] ?? 'table'),
                    'table' => (string)($check['table'] ?? ''),
                    'columns' => array_values((array)($check['columns'] ?? array())),
                    'notes' => (string)($check['notes'] ?? ''),
                    'ok' => false,
                    'missing' => (string)($check['type'] ?? 'table') === 'table'
                        ? array((string)($check['table'] ?? ''))
                        : array_values((array)($check['columns'] ?? array())),
                );
            }

            return array(
                'id' => $key,
                'label' => $label,
                'detail' => $detail,
                'status' => 'error',
                'error' => 'Database connection unavailable for this scope.',
                'items' => $items,
                'missing_count' => count($items),
            );
        }

        try {
            foreach ($checks as $check) {
                $item = spp_admin_realms_schema_evaluate_check($pdo, $check);
                if (empty($item['ok'])) {
                    $missingCount++;
                    $status = 'warn';
                }
                $items[] = $item;
            }
        } catch (Throwable $e) {
            $status = 'error';
            $error = $e->getMessage();
        }

        return array(
            'id' => $key,
            'label' => $label,
            'detail' => $detail,
            'status' => $status,
            'error' => $error,
            'items' => $items,
            'missing_count' => $missingCount,
        );
    }
}

if (!function_exists('spp_admin_realms_schema_scan_view')) {
    function spp_admin_realms_schema_scan_view(PDO $siteRealmdPdo, array $realmDbMap): array
    {
        $requirements = spp_admin_realms_schema_requirements();
        $slotMetaMap = spp_admin_realms_schema_slot_meta($siteRealmdPdo, $realmDbMap);
        $realmdGroups = spp_admin_realms_schema_realmd_groups($realmDbMap, $slotMetaMap);
        $databases = array();
        $summary = array(
            'database_count' => 0,
            'check_count' => 0,
            'missing_count' => 0,
            'healthy_count' => 0,
        );

        $databases[] = spp_admin_realms_schema_scan_db(
            'site-realmd',
            (string)($requirements['site_realmd']['label'] ?? 'Website Realmd'),
            $siteRealmdPdo,
            (array)($requirements['site_realmd']['checks'] ?? array())
        );

        foreach ($realmdGroups as $realmdDbName => $group) {
            $slotIds = array_values(array_map('intval', (array)($group['slot_ids'] ?? array())));
            sort($slotIds, SORT_NUMERIC);
            $firstRealmId = !empty($slotIds) ? (int)$slotIds[0] : 0;
            $realmdPdo = null;

            try {
                $realmdPdo = $firstRealmId > 0 ? spp_get_pdo('realmd', $firstRealmId) : null;
            } catch (Throwable $e) {
                $realmdPdo = null;
            }

            $topology = $realmdPdo instanceof PDO
                ? spp_admin_realms_schema_realmd_topology($realmdPdo)
                : array('summary' => '');
            $detail = 'Configured slots: ' . implode(', ', $slotIds);
            if ($realmdDbName !== '') {
                $detail .= ' | DB: ' . $realmdDbName;
            }
            if (!empty($topology['summary'])) {
                $detail .= ' | ' . (string)$topology['summary'];
            }

            $label = 'REALMD [' . $realmdDbName . ']';
            if (count($slotIds) > 1) {
                $label = 'Shared ' . $label;
            }

            $databases[] = spp_admin_realms_schema_scan_db(
                'realmd-group-' . md5((string)$realmdDbName),
                $label,
                $realmdPdo,
                (array)($requirements['realm_realmd']['checks'] ?? array()),
                $detail
            );
        }

        foreach ($realmDbMap as $realmId => $realmInfo) {
            $realmId = (int)$realmId;
            if ($realmId <= 0) {
                continue;
            }

            $slotMeta = (array)($slotMetaMap[$realmId] ?? array(
                'id' => $realmId,
                'name' => '',
                'realmd_db' => (string)($realmInfo['realmd'] ?? ''),
                'chars_db' => (string)($realmInfo['chars'] ?? ''),
            ));

            $realmCharsPdo = null;

            try {
                $realmCharsPdo = spp_get_pdo('chars', $realmId);
            } catch (Throwable $e) {
                $realmCharsPdo = null;
            }

            $databases[] = spp_admin_realms_schema_scan_db(
                'realm-chars-' . $realmId,
                spp_admin_realms_schema_scope_label($slotMeta, 'chars'),
                $realmCharsPdo,
                (array)($requirements['realm_chars']['checks'] ?? array()),
                'Configured slot: ' . $realmId
            );
        }

        foreach ($databases as $database) {
            $summary['database_count']++;
            $summary['check_count'] += count((array)($database['items'] ?? array()));
            $summary['missing_count'] += (int)($database['missing_count'] ?? 0);
            if ((string)($database['status'] ?? 'warn') === 'ok') {
                $summary['healthy_count']++;
            }
        }

        return array(
            'summary' => $summary,
            'databases' => $databases,
        );
    }
}

function spp_admin_realms_build_view(PDO $realmsPdo, array $realmDbMap = array())
{
    $view = array(
        'view_mode' => 'list',
        'pathway_info' => array(
            array('title' => 'Realm Management', 'link' => 'index.php?n=admin&sub=realms'),
        ),
        'items' => array(),
        'item' => null,
        'schema_scan' => spp_admin_realms_schema_scan_view($realmsPdo, $realmDbMap),
    );

    $action = (string)($_GET['action'] ?? '');
    $realmId = (int)($_GET['id'] ?? 0);

    if ($action === 'edit' && $realmId > 0) {
        $view['view_mode'] = 'edit';
        $view['pathway_info'][] = array('title' => 'Editing', 'link' => '');
        $stmt = $realmsPdo->prepare("SELECT * FROM realmlist WHERE `id`=?");
        $stmt->execute([$realmId]);
        $view['item'] = $stmt->fetch(PDO::FETCH_ASSOC);
        return $view;
    }

    $stmt = $realmsPdo->query("SELECT * FROM realmlist ORDER BY `name`");
    $view['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $view;
}
