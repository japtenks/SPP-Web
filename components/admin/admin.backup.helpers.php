<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_backup_output_dir(): string
{
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'core' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'sql_backups';
}

function spp_admin_backup_ensure_output_dir(): bool
{
    $dir = spp_admin_backup_output_dir();
    if (is_dir($dir)) {
        return is_writable($dir);
    }

    return @mkdir($dir, 0775, true) && is_writable($dir);
}

function spp_admin_backup_entity_options(): array
{
    return array(
        'character' => 'Character',
        'account' => 'Account',
        'guild' => 'Guild',
    );
}

function spp_admin_backup_sql_literal($value): string
{
    if ($value === null) {
        return 'NULL';
    }
    if (is_int($value) || is_float($value)) {
        return (string)$value;
    }

    return "'" . str_replace(
        array("\\", "'", "\0", "\n", "\r"),
        array("\\\\", "\\'", "\\0", "\\n", "\\r"),
        (string)$value
    ) . "'";
}

function spp_admin_backup_insert_sql(string $table, array $row): string
{
    $columns = array_map(function ($column) {
        return '`' . str_replace('`', '', (string)$column) . '`';
    }, array_keys($row));
    $values = array_map('spp_admin_backup_sql_literal', array_values($row));

    return 'INSERT INTO `' . str_replace('`', '', $table) . '` (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ');';
}

function spp_admin_backup_insert_sql_raw(string $table, array $row, array $rawColumns = array()): string
{
    $columns = array();
    $values = array();
    foreach ($row as $column => $value) {
        $columnName = (string)$column;
        $columns[] = '`' . str_replace('`', '', $columnName) . '`';
        if (isset($rawColumns[$columnName]) && $rawColumns[$columnName]) {
            $values[] = (string)$value;
        } else {
            $values[] = spp_admin_backup_sql_literal($value);
        }
    }

    return 'INSERT INTO `' . str_replace('`', '', $table) . '` (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ');';
}

function spp_admin_backup_realm_options(array $realmDbMap): array
{
    $options = array();
    foreach ($realmDbMap as $realmId => $realmInfo) {
        $resolvedName = function_exists('spp_get_armory_realm_name')
            ? (spp_get_armory_realm_name((int)$realmId) ?? '')
            : '';
        $expansionKey = function_exists('spp_realm_to_expansion_key')
            ? spp_realm_to_expansion_key((int)$realmId)
            : '';
        $options[] = array(
            'id' => (int)$realmId,
            'name' => (string)($resolvedName !== '' ? $resolvedName : ($realmInfo['name'] ?? ('Realm ' . $realmId))),
            'expansion_key' => (string)$expansionKey,
        );
    }
    usort($options, function ($a, $b) {
        return ($a['id'] <=> $b['id']);
    });

    return $options;
}

function spp_admin_backup_entity_label(string $entityType): string
{
    $options = spp_admin_backup_entity_options();
    return $options[$entityType] ?? ucfirst($entityType);
}

function spp_admin_backup_xfer_route_options(array $realmOptions): array
{
    $routes = array();
    $count = count($realmOptions);
    for ($i = 0; $i < $count; $i++) {
        for ($j = 0; $j < $count; $j++) {
            if ($i === $j) {
                continue;
            }

            $source = $realmOptions[$i];
            $target = $realmOptions[$j];
            $routes[] = array(
                'id' => (int)$source['id'] . ':' . (int)$target['id'],
                'source_realm_id' => (int)$source['id'],
                'target_realm_id' => (int)$target['id'],
                'label' => (string)$source['name'] . ' -> ' . (string)$target['name'],
            );
        }
    }

    return $routes;
}

function spp_admin_backup_is_vmangos_realm(array $realmOptions, int $realmId): bool
{
    foreach ($realmOptions as $realmOption) {
        if ((int)($realmOption['id'] ?? 0) !== $realmId) {
            continue;
        }

        return (string)($realmOption['expansion_key'] ?? '') === 'vmangos';
    }

    return false;
}

function spp_admin_backup_vmangos_target_account_row(array $sourceAccountRow, int $targetRealmId, array $targetColumns): array
{
    $row = array();
    foreach ($targetColumns as $column) {
        switch ($column) {
            case 'id':
                $row[$column] = '@target_account_id';
                break;
            case 'username':
                $row[$column] = (string)($sourceAccountRow['username'] ?? '');
                break;
            case 'sha_pass_hash':
                $row[$column] = (string)($sourceAccountRow['sha_pass_hash'] ?? '');
                break;
            case 'gmlevel':
                $row[$column] = (int)($sourceAccountRow['gmlevel'] ?? 0);
                break;
            case 'sessionkey':
                $row[$column] = (string)($sourceAccountRow['sessionkey'] ?? '');
                break;
            case 'v':
                $row[$column] = (string)($sourceAccountRow['v'] ?? '');
                break;
            case 's':
                $row[$column] = (string)($sourceAccountRow['s'] ?? '');
                break;
            case 'email':
                $row[$column] = (string)($sourceAccountRow['email'] ?? '');
                break;
            case 'joindate':
                $row[$column] = $sourceAccountRow['joindate'] ?? date('Y-m-d H:i:s');
                break;
            case 'last_ip':
                $row[$column] = (string)($sourceAccountRow['last_ip'] ?? '');
                break;
            case 'failed_logins':
                $row[$column] = (int)($sourceAccountRow['failed_logins'] ?? 0);
                break;
            case 'locked':
                $row[$column] = (int)($sourceAccountRow['locked'] ?? 0);
                break;
            case 'last_login':
                $row[$column] = $sourceAccountRow['last_login'] ?? null;
                break;
            case 'current_realm':
            case 'active_realm_id':
                $row[$column] = $targetRealmId;
                break;
            case 'expansion':
                $row[$column] = (int)($sourceAccountRow['expansion'] ?? 0);
                break;
            case 'mutetime':
                $row[$column] = (int)($sourceAccountRow['mutetime'] ?? 0);
                break;
            case 'locale':
                $row[$column] = (int)($sourceAccountRow['locale'] ?? 0);
                break;
            case 'os':
                $row[$column] = (string)($sourceAccountRow['os'] ?? '');
                break;
            case 'recruiter':
                $row[$column] = (int)($sourceAccountRow['recruiter'] ?? 0);
                break;
            case 'totaltime':
                $row[$column] = (int)($sourceAccountRow['totaltime'] ?? 0);
                break;
            case 'online':
                $row[$column] = 0;
                break;
            default:
                if (array_key_exists($column, $sourceAccountRow)) {
                    $row[$column] = $sourceAccountRow[$column];
                }
                break;
        }
    }

    return $row;
}

function spp_admin_backup_target_columns(PDO $pdo, string $table): array
{
    static $cache = array();
    $cacheKey = spl_object_hash($pdo) . ':' . $table;
    if (isset($cache[$cacheKey])) {
        return $cache[$cacheKey];
    }

    $stmt = $pdo->query("DESCRIBE `$table`");
    $columns = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN, 0) : array();

    return $cache[$cacheKey] = array_values(array_map('strval', $columns));
}

function spp_admin_backup_filter_row_to_target_columns(array $row, array $targetColumns): array
{
    if (empty($targetColumns)) {
        return $row;
    }

    $filtered = array();
    foreach ($targetColumns as $column) {
        if (array_key_exists($column, $row)) {
            $filtered[$column] = $row[$column];
        }
    }

    return $filtered;
}

function spp_admin_backup_next_numeric_id(PDO $pdo, string $table, string $column, int $minimum = 1): int
{
    $stmt = $pdo->query("SELECT MAX(`$column`) FROM `$table`");
    $value = $stmt ? (int)$stmt->fetchColumn() : 0;
    return max($minimum, $value + 1);
}

function spp_admin_backup_character_tables(): array
{
    return array(
        'character_action' => array('mode' => 'guid', 'key' => 'guid'),
        'character_aura' => array('mode' => 'guid', 'key' => 'guid'),
        'character_gifts' => array('mode' => 'guid', 'key' => 'guid'),
        'character_homebind' => array('mode' => 'guid', 'key' => 'guid'),
        'character_honor_cp' => array('mode' => 'guid', 'key' => 'guid'),
        'character_inventory' => array('mode' => 'guid', 'key' => 'guid'),
        'character_pet' => array('mode' => 'owner', 'key' => 'owner'),
        'character_queststatus' => array('mode' => 'guid', 'key' => 'guid'),
        'character_reputation' => array('mode' => 'guid', 'key' => 'guid'),
        'character_skills' => array('mode' => 'guid', 'key' => 'guid'),
        'character_social' => array('mode' => 'guid', 'key' => 'guid'),
        'character_spell' => array('mode' => 'guid', 'key' => 'guid'),
        'character_spell_cooldown' => array('mode' => 'guid', 'key' => 'guid'),
        'mail' => array('mode' => 'receiver', 'key' => 'receiver'),
        'mail_items' => array('mode' => 'mail', 'key' => 'mail_id'),
        'pet_aura' => array('mode' => 'pet', 'key' => 'guid'),
        'pet_spell' => array('mode' => 'pet', 'key' => 'guid'),
        'pet_spell_cooldown' => array('mode' => 'pet', 'key' => 'guid'),
        'item_instance' => array('mode' => 'item', 'key' => 'guid'),
        'item_loot' => array('mode' => 'item', 'key' => 'guid'),
        'item_text' => array('mode' => 'item_text', 'key' => 'id'),
    );
}

function spp_admin_backup_fetch_accounts(PDO $realmdPdo): array
{
    $stmt = $realmdPdo->query("
        SELECT id, username
        FROM account
        WHERE username NOT LIKE 'RNDBOT%'
          AND username NOT LIKE 'AIBOT%'
          AND username NOT LIKE 'NPC%'
        ORDER BY username ASC, id ASC
    ");
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array();
}

function spp_admin_backup_fetch_characters(PDO $charsPdo, int $accountId): array
{
    if ($accountId <= 0) {
        return array();
    }

    $stmt = $charsPdo->prepare("SELECT guid, name, race, class, level FROM characters WHERE account=? ORDER BY name ASC, guid ASC");
    $stmt->execute(array($accountId));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function spp_admin_backup_fetch_guilds(PDO $charsPdo): array
{
    $stmt = $charsPdo->query("
        SELECT g.guildid, g.name, g.leaderguid, c.name AS leader_name
        FROM guild g
        LEFT JOIN characters c ON c.guid = g.leaderguid
        ORDER BY g.name ASC, g.guildid ASC
    ");
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array();
}

function spp_admin_backup_fetch_account_row(PDO $realmdPdo, int $accountId): ?array
{
    if ($accountId <= 0) {
        return null;
    }

    $stmt = $realmdPdo->prepare("SELECT * FROM account WHERE id=? LIMIT 1");
    $stmt->execute(array($accountId));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function spp_admin_backup_fetch_character_row(PDO $charsPdo, int $characterGuid, int $accountId = 0): ?array
{
    if ($characterGuid <= 0) {
        return null;
    }

    if ($accountId > 0) {
        $stmt = $charsPdo->prepare("SELECT * FROM characters WHERE guid=? AND account=? LIMIT 1");
        $stmt->execute(array($characterGuid, $accountId));
    } else {
        $stmt = $charsPdo->prepare("SELECT * FROM characters WHERE guid=? LIMIT 1");
        $stmt->execute(array($characterGuid));
    }

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function spp_admin_backup_fetch_guild_row(PDO $charsPdo, int $guildId): ?array
{
    if ($guildId <= 0) {
        return null;
    }

    $stmt = $charsPdo->prepare("SELECT * FROM guild WHERE guildid=? LIMIT 1");
    $stmt->execute(array($guildId));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function spp_admin_backup_fetch_account_related_rows(PDO $realmdPdo, int $accountId): array
{
    $rows = array();
    if ($accountId <= 0) {
        return $rows;
    }

    foreach (array('account_access' => 'id', 'account_banned' => 'id', 'realmcharacters' => 'acctid') as $table => $column) {
        if (function_exists('spp_db_table_exists') && !spp_db_table_exists($realmdPdo, $table)) {
            $rows[$table] = array();
            continue;
        }
        $stmt = $realmdPdo->prepare("SELECT * FROM `$table` WHERE `$column`=?");
        $stmt->execute(array($accountId));
        $rows[$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    return $rows;
}

function spp_admin_backup_build_filename(string $prefix, string $entityType, string $label): string
{
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim($label)));
    $slug = trim($slug, '_');
    if ($slug === '') {
        $slug = $entityType;
    }

    return $prefix . '_' . $entityType . '_' . $slug . '_' . date('Ymd_His') . '.sql';
}

function spp_admin_backup_write_output(string $filename, array $lines): array
{
    if (!spp_admin_backup_ensure_output_dir()) {
        return array('ok' => false, 'message' => 'The backup output directory is not writable: ' . spp_admin_backup_output_dir());
    }

    $path = spp_admin_backup_output_dir() . DIRECTORY_SEPARATOR . $filename;
    $ok = @file_put_contents($path, implode(PHP_EOL, $lines) . PHP_EOL);
    if ($ok === false) {
        return array('ok' => false, 'message' => 'The SQL package could not be written.');
    }

    return array('ok' => true, 'path' => $path);
}

function spp_admin_backup_basename(string $path): string
{
    return basename(str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path));
}

function spp_admin_backup_download_url(string $filename): string
{
    return 'components/admin/admin.backup.download.php?file=' . rawurlencode($filename);
}

function spp_admin_backup_list_files(int $limit = 20): array
{
    $dir = spp_admin_backup_output_dir();
    if (!is_dir($dir)) {
        return array();
    }

    $items = glob($dir . DIRECTORY_SEPARATOR . '*.sql');
    if (!is_array($items)) {
        return array();
    }

    usort($items, function ($a, $b) {
        return filemtime($b) <=> filemtime($a);
    });

    $files = array();
    foreach (array_slice($items, 0, max(1, $limit)) as $path) {
        $filename = spp_admin_backup_basename((string)$path);
        $files[] = array(
            'filename' => $filename,
            'path' => (string)$path,
            'download_url' => spp_admin_backup_download_url($filename),
            'mtime' => (int)@filemtime($path),
            'size' => (int)@filesize($path),
        );
    }

    return $files;
}

function spp_admin_backup_map_character_rows_for_transfer(array $bundle, PDO $targetCharsPdo, int $targetAccountId, string $newName = ''): array
{
    $characterRow = $bundle['character'] ?? array();
    if (empty($characterRow)) {
        return array('ok' => false, 'message' => 'Character data is missing from the export bundle.');
    }

    $oldCharacterGuid = (int)$characterRow['guid'];
    $newCharacterGuid = spp_admin_backup_next_numeric_id($targetCharsPdo, 'characters', 'guid', 1);
    $itemGuidMap = array();
    $mailGuidMap = array();
    $petGuidMap = array();
    $textIdMap = array();

    $nextItemGuid = spp_admin_backup_next_numeric_id($targetCharsPdo, 'item_instance', 'guid', 1);
    foreach ((array)($bundle['item_instance'] ?? array()) as $itemRow) {
        $itemGuidMap[(int)$itemRow['guid']] = $nextItemGuid++;
    }

    $nextMailGuid = spp_admin_backup_next_numeric_id($targetCharsPdo, 'mail', 'id', 1);
    foreach ((array)($bundle['mail'] ?? array()) as $mailRow) {
        $mailGuidMap[(int)$mailRow['id']] = $nextMailGuid++;
    }

    $nextPetGuid = spp_admin_backup_next_numeric_id($targetCharsPdo, 'character_pet', 'id', 1);
    foreach ((array)($bundle['character_pet'] ?? array()) as $petRow) {
        $petGuidMap[(int)$petRow['id']] = $nextPetGuid++;
    }

    $nextTextId = spp_admin_backup_next_numeric_id($targetCharsPdo, 'item_text', 'id', 1);
    foreach ((array)($bundle['item_text'] ?? array()) as $textRow) {
        $textIdMap[(int)$textRow['id']] = $nextTextId++;
    }

    $mapped = array();
    $characterRow['guid'] = $newCharacterGuid;
    $characterRow['account'] = $targetAccountId;
    $characterRow['online'] = 0;
    $characterRow['xp'] = 0;
    if ($newName !== '') {
        $characterRow['name'] = $newName;
    }
    $mapped['characters'] = array($characterRow);

    foreach (spp_admin_backup_character_tables() as $table => $meta) {
        $rows = (array)($bundle[$table] ?? array());
        $mapped[$table] = array();

        foreach ($rows as $row) {
            switch ($meta['mode']) {
                case 'guid':
                case 'owner':
                case 'receiver':
                    if (isset($row[$meta['key']])) {
                        $row[$meta['key']] = $newCharacterGuid;
                    }
                    if (isset($row['friend']) && (int)$row['friend'] === $oldCharacterGuid) {
                        $row['friend'] = $newCharacterGuid;
                    }
                    if (isset($row['sender']) && (int)$row['sender'] === $oldCharacterGuid) {
                        $row['sender'] = $newCharacterGuid;
                    }
                    break;
                case 'mail':
                    if (isset($row['id']) && isset($mailGuidMap[(int)$row['id']])) {
                        $row['id'] = $mailGuidMap[(int)$row['id']];
                    }
                    if (isset($row['receiver'])) {
                        $row['receiver'] = $newCharacterGuid;
                    }
                    if (isset($row['sender']) && (int)$row['sender'] === $oldCharacterGuid) {
                        $row['sender'] = $newCharacterGuid;
                    }
                    if (isset($row['itemTextId']) && isset($textIdMap[(int)$row['itemTextId']])) {
                        $row['itemTextId'] = $textIdMap[(int)$row['itemTextId']];
                    }
                    break;
                case 'pet':
                    if (isset($row['guid']) && isset($petGuidMap[(int)$row['guid']])) {
                        $row['guid'] = $petGuidMap[(int)$row['guid']];
                    }
                    break;
                case 'item':
                    if (isset($row['guid']) && isset($itemGuidMap[(int)$row['guid']])) {
                        $row['guid'] = $itemGuidMap[(int)$row['guid']];
                    }
                    if (isset($row['owner_guid'])) {
                        $row['owner_guid'] = $newCharacterGuid;
                    }
                    if (isset($row['itemTextId']) && isset($textIdMap[(int)$row['itemTextId']])) {
                        $row['itemTextId'] = $textIdMap[(int)$row['itemTextId']];
                    }
                    break;
                case 'item_text':
                    if (isset($row['id']) && isset($textIdMap[(int)$row['id']])) {
                        $row['id'] = $textIdMap[(int)$row['id']];
                    }
                    break;
            }

            if ($table === 'character_inventory') {
                $row['guid'] = $newCharacterGuid;
                if (isset($row['item']) && isset($itemGuidMap[(int)$row['item']])) {
                    $row['item'] = $itemGuidMap[(int)$row['item']];
                }
                if (!empty($row['bag']) && isset($itemGuidMap[(int)$row['bag']])) {
                    $row['bag'] = $itemGuidMap[(int)$row['bag']];
                }
            } elseif ($table === 'mail_items') {
                if (isset($row['mail_id']) && isset($mailGuidMap[(int)$row['mail_id']])) {
                    $row['mail_id'] = $mailGuidMap[(int)$row['mail_id']];
                }
                if (isset($row['item_guid']) && isset($itemGuidMap[(int)$row['item_guid']])) {
                    $row['item_guid'] = $itemGuidMap[(int)$row['item_guid']];
                }
                if (isset($row['receiver'])) {
                    $row['receiver'] = $newCharacterGuid;
                }
            } elseif ($table === 'character_gifts') {
                if (isset($row['guid'])) {
                    $row['guid'] = $newCharacterGuid;
                }
                if (isset($row['item_guid']) && isset($itemGuidMap[(int)$row['item_guid']])) {
                    $row['item_guid'] = $itemGuidMap[(int)$row['item_guid']];
                }
            } elseif ($table === 'character_pet') {
                if (isset($row['owner'])) {
                    $row['owner'] = $newCharacterGuid;
                }
                if (isset($row['id']) && isset($petGuidMap[(int)$row['id']])) {
                    $row['id'] = $petGuidMap[(int)$row['id']];
                }
            }

            $mapped[$table][] = $row;
        }
    }

    return array(
        'ok' => true,
        'character_guid' => $newCharacterGuid,
        'rows' => $mapped,
    );
}

function spp_admin_backup_comment(string $text): string
{
    return '-- ' . str_replace(array("\r", "\n"), ' ', $text);
}

function spp_admin_backup_vmangos_character_validation(PDO $sourceCharsPdo, PDO $targetCharsPdo): array
{
    $requiredTables = array_merge(array('characters'), array_keys(spp_admin_backup_character_tables()));
    $requiredColumns = array(
        'characters' => array('guid', 'account', 'name'),
        'character_inventory' => array('guid'),
        'mail' => array('id'),
        'item_instance' => array('guid'),
        'guild' => array('guildid'),
    );

    foreach ($requiredTables as $table) {
        if (!function_exists('spp_db_table_exists') || !spp_db_table_exists($sourceCharsPdo, $table) || !spp_db_table_exists($targetCharsPdo, $table)) {
            return array(
                'ok' => false,
                'message' => 'vMaNGOS character transfer requires matching live schemas. Missing table validation failed for `' . $table . '`.',
            );
        }
    }

    foreach ($requiredColumns as $table => $columns) {
        foreach ($columns as $column) {
            if (!function_exists('spp_db_column_exists')
                || !spp_db_column_exists($sourceCharsPdo, $table, $column)
                || !spp_db_column_exists($targetCharsPdo, $table, $column)) {
                return array(
                    'ok' => false,
                    'message' => 'vMaNGOS character transfer requires matching live schemas. Missing column validation failed for `' . $table . '.' . $column . '`.',
                );
            }
        }
    }

    return array('ok' => true, 'message' => '');
}
