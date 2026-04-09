<?php
if (INCLUDED !== true) {
    exit;
}

function spp_admin_backup_state_defaults(): array
{
    return array(
        'notice' => '',
        'error' => '',
        'download_url' => '',
        'filename' => '',
        'downloads' => array(),
    );
}

function spp_admin_backup_result_with_files(string $message, array $writeSet): array
{
    if (empty($writeSet['ok'])) {
        return $writeSet;
    }

    return array(
        'ok' => true,
        'message' => $message,
        'paths' => (array)($writeSet['paths'] ?? array()),
        'files' => (array)($writeSet['files'] ?? array()),
    );
}

function spp_admin_backup_realmd_companion_lines(string $title, array $details = array(), string $tailNote = ''): array
{
    $lines = array(
        spp_admin_backup_comment($title),
        spp_admin_backup_comment('Generated: ' . date('Y-m-d H:i:s')),
    );

    foreach ($details as $detail) {
        $detail = trim((string)$detail);
        if ($detail !== '') {
            $lines[] = spp_admin_backup_comment($detail);
        }
    }

    if ($tailNote !== '') {
        $lines[] = spp_admin_backup_comment($tailNote);
    }

    return array_merge($lines, array('', 'SELECT 1;'));
}

function spp_admin_backup_realmcharacters_sync_lines(PDO $targetRealmdPdo, int $targetRealmId, string $targetAccountExpr, string $targetCharsDbName): array
{
    $targetColumns = spp_admin_backup_target_columns($targetRealmdPdo, 'realmcharacters');
    if (empty($targetColumns)) {
        return array();
    }

    $safeCharsDbName = str_replace('`', '', trim($targetCharsDbName));
    if ($safeCharsDbName === '') {
        return array();
    }

    $realmcharactersRow = array();
    $rawColumns = array();
    foreach ($targetColumns as $column) {
        if ($column === 'acctid') {
            $realmcharactersRow[$column] = $targetAccountExpr;
            $rawColumns[$column] = true;
        } elseif ($column === 'realmid') {
            $realmcharactersRow[$column] = $targetRealmId;
        } elseif ($column === 'numchars') {
            $realmcharactersRow[$column] = '(SELECT COUNT(*) FROM `' . $safeCharsDbName . '`.`characters` WHERE `account` = ' . $targetAccountExpr . ')';
            $rawColumns[$column] = true;
        }
    }

    if (empty($realmcharactersRow)) {
        return array();
    }

    return array(
        'DELETE FROM `realmcharacters` WHERE `acctid` = ' . $targetAccountExpr . ' AND `realmid` = ' . $targetRealmId . ';',
        spp_admin_backup_insert_sql_raw('realmcharacters', $realmcharactersRow, $rawColumns),
        '',
    );
}

function spp_admin_backup_fetch_character_bundle(PDO $charsPdo, int $characterGuid, int $accountId = 0): ?array
{
    $characterRow = spp_admin_backup_fetch_character_row($charsPdo, $characterGuid, $accountId);
    if (empty($characterRow)) {
        return null;
    }

    $bundle = array('character' => $characterRow);
    $petIds = array();
    $mailIds = array();
    $itemIds = array();
    $textIds = array();

    foreach (spp_admin_backup_character_tables() as $table => $meta) {
        switch ($meta['mode']) {
            case 'guid':
            case 'owner':
            case 'receiver':
                $stmt = $charsPdo->prepare("SELECT * FROM `$table` WHERE `{$meta['key']}`=?");
                $stmt->execute(array($characterGuid));
                $bundle[$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
            case 'mail':
                $stmt = $charsPdo->prepare("SELECT * FROM `$table` WHERE `receiver`=?");
                $stmt->execute(array($characterGuid));
                $bundle[$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($bundle[$table] as $row) {
                    $mailIds[] = (int)($row['id'] ?? 0);
                    if (!empty($row['itemTextId'])) {
                        $textIds[] = (int)$row['itemTextId'];
                    }
                }
                break;
            case 'pet':
                $bundle[$table] = array();
                break;
            case 'item':
            case 'item_text':
                $bundle[$table] = array();
                break;
        }

        if ($table === 'character_inventory') {
            foreach ($bundle[$table] as $row) {
                $itemIds[] = (int)($row['item'] ?? 0);
                if (!empty($row['bag'])) {
                    $itemIds[] = (int)$row['bag'];
                }
            }
        } elseif ($table === 'character_pet') {
            foreach ($bundle[$table] as $row) {
                $petIds[] = (int)($row['id'] ?? 0);
            }
        } elseif ($table === 'character_gifts') {
            foreach ($bundle[$table] as $row) {
                $itemIds[] = (int)($row['item_guid'] ?? 0);
            }
        }
    }

    $petIds = array_values(array_filter(array_unique(array_map('intval', $petIds))));
    $mailIds = array_values(array_filter(array_unique(array_map('intval', $mailIds))));
    $itemIds = array_values(array_filter(array_unique(array_map('intval', $itemIds))));
    $textIds = array_values(array_filter(array_unique(array_map('intval', $textIds))));

    if (!empty($petIds)) {
        $placeholders = implode(',', array_fill(0, count($petIds), '?'));
        foreach (array('pet_aura', 'pet_spell', 'pet_spell_cooldown') as $table) {
            $stmt = $charsPdo->prepare("SELECT * FROM `$table` WHERE guid IN ($placeholders)");
            $stmt->execute($petIds);
            $bundle[$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    if (!empty($mailIds)) {
        $placeholders = implode(',', array_fill(0, count($mailIds), '?'));
        $stmt = $charsPdo->prepare("SELECT * FROM mail_items WHERE mail_id IN ($placeholders)");
        $stmt->execute($mailIds);
        $bundle['mail_items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($bundle['mail_items'] as $row) {
            $itemIds[] = (int)($row['item_guid'] ?? 0);
        }
        $itemIds = array_values(array_filter(array_unique(array_map('intval', $itemIds))));
    }

    if (!empty($itemIds)) {
        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        foreach (array('item_instance' => 'guid', 'item_loot' => 'guid') as $table => $column) {
            $stmt = $charsPdo->prepare("SELECT * FROM `$table` WHERE `$column` IN ($placeholders)");
            $stmt->execute($itemIds);
            $bundle[$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        foreach ((array)($bundle['item_instance'] ?? array()) as $row) {
            if (!empty($row['itemTextId'])) {
                $textIds[] = (int)$row['itemTextId'];
            }
        }
        $textIds = array_values(array_filter(array_unique(array_map('intval', $textIds))));
    }

    if (!empty($textIds)) {
        $placeholders = implode(',', array_fill(0, count($textIds), '?'));
        $stmt = $charsPdo->prepare("SELECT * FROM item_text WHERE id IN ($placeholders)");
        $stmt->execute($textIds);
        $bundle['item_text'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    return $bundle;
}

function spp_admin_backup_character_bundle_lines(array $bundle, ?PDO $targetCharsPdo = null): array
{
    $lines = array();
    $tables = array_merge(array('characters' => array()), spp_admin_backup_character_tables());

    foreach ($tables as $table => $_meta) {
        $rows = $table === 'characters' ? (array)($bundle['characters'] ?? array($bundle['character'] ?? array())) : (array)($bundle[$table] ?? array());
        if (empty($rows)) {
            continue;
        }

        $targetColumns = $targetCharsPdo ? spp_admin_backup_target_columns($targetCharsPdo, $table) : array();
        foreach ($rows as $row) {
            if (empty($row)) {
                continue;
            }
            $filtered = $targetCharsPdo ? spp_admin_backup_filter_row_to_target_columns($row, $targetColumns) : $row;
            if (!empty($filtered)) {
                $lines[] = spp_admin_backup_insert_sql($table, $filtered);
            }
        }
        $lines[] = '';
    }

    return $lines;
}

function spp_admin_backup_xfer_expression(string $variableName, int $sourceValue, bool $allowZero = true): string
{
    if ($allowZero && $sourceValue <= 0) {
        return '0';
    }

    return '(@' . $variableName . '_delta + ' . $sourceValue . ')';
}

function spp_admin_backup_xfer_insert_line(string $table, array $row, array $rawColumns, array $targetColumns): string
{
    $filteredRow = spp_admin_backup_filter_row_to_target_columns($row, $targetColumns);
    if (empty($filteredRow)) {
        return '';
    }

    $filteredRaw = array();
    foreach ($rawColumns as $column => $enabled) {
        if ($enabled && array_key_exists($column, $filteredRow)) {
            $filteredRaw[$column] = true;
        }
    }

    return spp_admin_backup_insert_sql_raw($table, $filteredRow, $filteredRaw);
}

function spp_admin_backup_character_bundle_xfer_lines(array $bundle, PDO $targetCharsPdo, string $targetAccountExpr, string $newName = ''): array
{
    $characterRow = $bundle['character'] ?? array();
    if (empty($characterRow)) {
        return array();
    }

    $sourceCharacterGuid = (int)($characterRow['guid'] ?? 0);
    $itemRows = (array)($bundle['item_instance'] ?? array());
    $mailRows = (array)($bundle['mail'] ?? array());
    $petRows = (array)($bundle['character_pet'] ?? array());
    $textRows = (array)($bundle['item_text'] ?? array());

    $lines = array(
        'SET @target_character_guid := (SELECT COALESCE(MAX(`guid`), 0) + 1 FROM `characters`);',
        'SET @character_guid_delta := (@target_character_guid - ' . $sourceCharacterGuid . ');',
    );

    $minItemGuid = 0;
    if (!empty($itemRows)) {
        $itemGuids = array_map(static function ($row) { return (int)($row['guid'] ?? 0); }, $itemRows);
        $itemGuids = array_values(array_filter($itemGuids));
        if (!empty($itemGuids)) {
            $minItemGuid = min($itemGuids);
            $lines[] = 'SET @target_item_guid_base := (SELECT COALESCE(MAX(`guid`), 0) + 1 FROM `item_instance`);';
            $lines[] = 'SET @item_guid_delta := (@target_item_guid_base - ' . $minItemGuid . ');';
        }
    }

    $minMailId = 0;
    if (!empty($mailRows)) {
        $mailIds = array_map(static function ($row) { return (int)($row['id'] ?? 0); }, $mailRows);
        $mailIds = array_values(array_filter($mailIds));
        if (!empty($mailIds)) {
            $minMailId = min($mailIds);
            $lines[] = 'SET @target_mail_id_base := (SELECT COALESCE(MAX(`id`), 0) + 1 FROM `mail`);';
            $lines[] = 'SET @mail_id_delta := (@target_mail_id_base - ' . $minMailId . ');';
        }
    }

    $minPetId = 0;
    if (!empty($petRows)) {
        $petIds = array_map(static function ($row) { return (int)($row['id'] ?? 0); }, $petRows);
        $petIds = array_values(array_filter($petIds));
        if (!empty($petIds)) {
            $minPetId = min($petIds);
            $lines[] = 'SET @target_pet_id_base := (SELECT COALESCE(MAX(`id`), 0) + 1 FROM `character_pet`);';
            $lines[] = 'SET @pet_id_delta := (@target_pet_id_base - ' . $minPetId . ');';
        }
    }

    $minTextId = 0;
    if (!empty($textRows)) {
        $textIds = array_map(static function ($row) { return (int)($row['id'] ?? 0); }, $textRows);
        $textIds = array_values(array_filter($textIds));
        if (!empty($textIds)) {
            $minTextId = min($textIds);
            $lines[] = 'SET @target_text_id_base := (SELECT COALESCE(MAX(`id`), 0) + 1 FROM `item_text`);';
            $lines[] = 'SET @text_id_delta := (@target_text_id_base - ' . $minTextId . ');';
        }
    }

    $lines[] = '';

    $characterRow['guid'] = '@target_character_guid';
    $characterRow['account'] = $targetAccountExpr;
    $characterRow['online'] = 0;
    $characterRow['xp'] = 0;
    if ($newName !== '') {
        $characterRow['name'] = $newName;
    }
    $line = spp_admin_backup_xfer_insert_line(
        'characters',
        $characterRow,
        array('guid' => true, 'account' => true),
        spp_admin_backup_target_columns($targetCharsPdo, 'characters')
    );
    if ($line !== '') {
        $lines[] = $line;
        $lines[] = '';
    }

    $tables = spp_admin_backup_character_tables();
    foreach ($tables as $table => $meta) {
        $rows = (array)($bundle[$table] ?? array());
        if (empty($rows)) {
            continue;
        }

        $targetColumns = spp_admin_backup_target_columns($targetCharsPdo, $table);
        foreach ($rows as $row) {
            $rawColumns = array();
            switch ($meta['mode']) {
                case 'guid':
                case 'owner':
                case 'receiver':
                    if (isset($row[$meta['key']])) {
                        $row[$meta['key']] = '@target_character_guid';
                        $rawColumns[$meta['key']] = true;
                    }
                    if (isset($row['friend']) && (int)$row['friend'] === $sourceCharacterGuid) {
                        $row['friend'] = '@target_character_guid';
                        $rawColumns['friend'] = true;
                    }
                    if (isset($row['sender']) && (int)$row['sender'] === $sourceCharacterGuid) {
                        $row['sender'] = '@target_character_guid';
                        $rawColumns['sender'] = true;
                    }
                    break;
                case 'mail':
                    if (isset($row['id']) && $minMailId > 0) {
                        $row['id'] = spp_admin_backup_xfer_expression('mail_id', (int)$row['id'], false);
                        $rawColumns['id'] = true;
                    }
                    if (isset($row['receiver'])) {
                        $row['receiver'] = '@target_character_guid';
                        $rawColumns['receiver'] = true;
                    }
                    if (isset($row['sender']) && (int)$row['sender'] === $sourceCharacterGuid) {
                        $row['sender'] = '@target_character_guid';
                        $rawColumns['sender'] = true;
                    }
                    if (isset($row['itemTextId']) && $minTextId > 0 && (int)$row['itemTextId'] > 0) {
                        $row['itemTextId'] = spp_admin_backup_xfer_expression('text_id', (int)$row['itemTextId'], false);
                        $rawColumns['itemTextId'] = true;
                    }
                    break;
                case 'pet':
                    if (isset($row['guid']) && $minPetId > 0) {
                        $row['guid'] = spp_admin_backup_xfer_expression('pet_id', (int)$row['guid'], false);
                        $rawColumns['guid'] = true;
                    }
                    break;
                case 'item':
                    if (isset($row['guid']) && $minItemGuid > 0) {
                        $row['guid'] = spp_admin_backup_xfer_expression('item_guid', (int)$row['guid'], false);
                        $rawColumns['guid'] = true;
                    }
                    if (isset($row['owner_guid'])) {
                        $row['owner_guid'] = '@target_character_guid';
                        $rawColumns['owner_guid'] = true;
                    }
                    if (isset($row['itemTextId']) && $minTextId > 0 && (int)$row['itemTextId'] > 0) {
                        $row['itemTextId'] = spp_admin_backup_xfer_expression('text_id', (int)$row['itemTextId'], false);
                        $rawColumns['itemTextId'] = true;
                    }
                    break;
                case 'item_text':
                    if (isset($row['id']) && $minTextId > 0) {
                        $row['id'] = spp_admin_backup_xfer_expression('text_id', (int)$row['id'], false);
                        $rawColumns['id'] = true;
                    }
                    break;
            }

            if ($table === 'character_inventory') {
                $row['guid'] = '@target_character_guid';
                $rawColumns['guid'] = true;
                if (isset($row['item']) && $minItemGuid > 0 && (int)$row['item'] > 0) {
                    $row['item'] = spp_admin_backup_xfer_expression('item_guid', (int)$row['item'], false);
                    $rawColumns['item'] = true;
                }
                if (!empty($row['bag']) && $minItemGuid > 0) {
                    $row['bag'] = spp_admin_backup_xfer_expression('item_guid', (int)$row['bag'], false);
                    $rawColumns['bag'] = true;
                }
            } elseif ($table === 'mail_items') {
                if (isset($row['mail_id']) && $minMailId > 0) {
                    $row['mail_id'] = spp_admin_backup_xfer_expression('mail_id', (int)$row['mail_id'], false);
                    $rawColumns['mail_id'] = true;
                }
                if (isset($row['item_guid']) && $minItemGuid > 0) {
                    $row['item_guid'] = spp_admin_backup_xfer_expression('item_guid', (int)$row['item_guid'], false);
                    $rawColumns['item_guid'] = true;
                }
                if (isset($row['receiver'])) {
                    $row['receiver'] = '@target_character_guid';
                    $rawColumns['receiver'] = true;
                }
            } elseif ($table === 'character_gifts') {
                if (isset($row['guid'])) {
                    $row['guid'] = '@target_character_guid';
                    $rawColumns['guid'] = true;
                }
                if (isset($row['item_guid']) && $minItemGuid > 0) {
                    $row['item_guid'] = spp_admin_backup_xfer_expression('item_guid', (int)$row['item_guid'], false);
                    $rawColumns['item_guid'] = true;
                }
            } elseif ($table === 'character_pet') {
                if (isset($row['owner'])) {
                    $row['owner'] = '@target_character_guid';
                    $rawColumns['owner'] = true;
                }
                if (isset($row['id']) && $minPetId > 0) {
                    $row['id'] = spp_admin_backup_xfer_expression('pet_id', (int)$row['id'], false);
                    $rawColumns['id'] = true;
                }
            }

            $line = spp_admin_backup_xfer_insert_line($table, $row, $rawColumns, $targetColumns);
            if ($line !== '') {
                $lines[] = $line;
            }
        }
        $lines[] = '';
    }

    return $lines;
}

function spp_admin_backup_export_character(PDO $sourceRealmdPdo, PDO $sourceCharsPdo, array $view): array
{
    $characterGuid = (int)($view['selected_character_guid'] ?? 0);
    $accountId = (int)($view['selected_account_id'] ?? 0);
    $characterRow = $view['selected_character_row'] ?? null;
    if ($characterGuid <= 0 || empty($characterRow)) {
        return array('ok' => false, 'message' => 'Select a character first.');
    }

    $bundle = spp_admin_backup_fetch_character_bundle($sourceCharsPdo, $characterGuid, $accountId);
    if (empty($bundle)) {
        return array('ok' => false, 'message' => 'That character could not be exported.');
    }

    $accountRow = spp_admin_backup_fetch_account_row($sourceRealmdPdo, $accountId);
    $accountRows = $accountId > 0 ? spp_admin_backup_fetch_account_related_rows($sourceRealmdPdo, $accountId) : array();

    $realmdLines = array(
        spp_admin_backup_comment('Character backup export (realmd companion)'),
        spp_admin_backup_comment('Realm: ' . (string)$view['source_realm_name']),
        spp_admin_backup_comment('Character: ' . (string)($characterRow['name'] ?? ('GUID ' . $characterGuid))),
        spp_admin_backup_comment('Account context is included so the character package can be paired with its auth record.'),
        spp_admin_backup_comment('Generated: ' . date('Y-m-d H:i:s')),
        '',
    );
    if (!empty($accountRow)) {
        $realmdLines[] = spp_admin_backup_insert_sql('account', $accountRow);
        foreach ($accountRows as $table => $rows) {
            foreach ($rows as $row) {
                $realmdLines[] = spp_admin_backup_insert_sql($table, $row);
            }
            if (!empty($rows)) {
                $realmdLines[] = '';
            }
        }
    } else {
        $realmdLines[] = 'SELECT 1;';
        $realmdLines[] = '';
    }

    $charsLines = array(
        spp_admin_backup_comment('Character backup export'),
        spp_admin_backup_comment('Realm: ' . (string)$view['source_realm_name']),
        spp_admin_backup_comment('Character: ' . (string)($characterRow['name'] ?? ('GUID ' . $characterGuid))),
        spp_admin_backup_comment('Generated: ' . date('Y-m-d H:i:s')),
        '',
    );
    $charsLines = array_merge($charsLines, spp_admin_backup_character_bundle_lines($bundle));

    return spp_admin_backup_result_with_files(
        'Character backup created.',
        spp_admin_backup_write_output_set(
            'backup',
            'character',
            (string)($characterRow['name'] ?? ('guid_' . $characterGuid)),
            array(
                'realmd' => $realmdLines,
                'chars' => $charsLines,
            )
        )
    );
}

function spp_admin_backup_export_account(PDO $sourceRealmdPdo, PDO $sourceCharsPdo, array $view): array
{
    $accountId = (int)($view['selected_account_id'] ?? 0);
    $accountRow = spp_admin_backup_fetch_account_row($sourceRealmdPdo, $accountId);
    if ($accountId <= 0 || empty($accountRow)) {
        return array('ok' => false, 'message' => 'Select an account first.');
    }

    $accountRows = spp_admin_backup_fetch_account_related_rows($sourceRealmdPdo, $accountId);
    $characterRows = spp_admin_backup_fetch_characters($sourceCharsPdo, $accountId);

    $realmdLines = array(
        spp_admin_backup_comment('Account backup export'),
        spp_admin_backup_comment('Realm: ' . (string)$view['source_realm_name']),
        spp_admin_backup_comment('Account: ' . (string)($accountRow['username'] ?? ('ID ' . $accountId))),
        spp_admin_backup_comment('Generated: ' . date('Y-m-d H:i:s')),
        '',
        spp_admin_backup_insert_sql('account', $accountRow),
    );

    foreach ($accountRows as $table => $rows) {
        foreach ($rows as $row) {
            $realmdLines[] = spp_admin_backup_insert_sql($table, $row);
        }
        if (!empty($rows)) {
            $realmdLines[] = '';
        }
    }

    $charsLines = array(
        spp_admin_backup_comment('Account backup export (characters)'),
        spp_admin_backup_comment('Realm: ' . (string)$view['source_realm_name']),
        spp_admin_backup_comment('Account: ' . (string)($accountRow['username'] ?? ('ID ' . $accountId))),
        spp_admin_backup_comment('Generated: ' . date('Y-m-d H:i:s')),
        '',
    );
    foreach ($characterRows as $characterRow) {
        $bundle = spp_admin_backup_fetch_character_bundle($sourceCharsPdo, (int)$characterRow['guid'], $accountId);
        if (!empty($bundle)) {
            $charsLines[] = spp_admin_backup_comment('Character: ' . (string)$characterRow['name']);
            $charsLines = array_merge($charsLines, spp_admin_backup_character_bundle_lines($bundle));
        }
    }

    return spp_admin_backup_result_with_files(
        'Account backup created.',
        spp_admin_backup_write_output_set(
            'backup',
            'account',
            (string)($accountRow['username'] ?? ('account_' . $accountId)),
            array(
                'realmd' => $realmdLines,
                'chars' => $charsLines,
            )
        )
    );
}

function spp_admin_backup_export_guild(PDO $sourceCharsPdo, array $view): array
{
    $guildId = (int)($view['selected_guild_id'] ?? 0);
    $guildRow = spp_admin_backup_fetch_guild_row($sourceCharsPdo, $guildId);
    if ($guildId <= 0 || empty($guildRow)) {
        return array('ok' => false, 'message' => 'Select a guild first.');
    }

    $realmdLines = spp_admin_backup_realmd_companion_lines(
        'Guild backup export (realmd companion)',
        array(
            'Realm: ' . (string)$view['source_realm_name'],
            'Guild: ' . (string)($guildRow['name'] ?? ('ID ' . $guildId)),
        ),
        'Guild exports operate on the characters database. No realmd rows are required for this package.'
    );

    $charsLines = array(
        spp_admin_backup_comment('Guild backup export'),
        spp_admin_backup_comment('Realm: ' . (string)$view['source_realm_name']),
        spp_admin_backup_comment('Guild: ' . (string)($guildRow['name'] ?? ('ID ' . $guildId))),
        spp_admin_backup_comment('Generated: ' . date('Y-m-d H:i:s')),
        '',
        spp_admin_backup_insert_sql('guild', $guildRow),
    );

    foreach (array('guild_rank' => 'guildid', 'guild_member' => 'guildid', 'guild_eventlog' => 'guildid') as $table => $column) {
        $stmt = $sourceCharsPdo->prepare("SELECT * FROM `$table` WHERE `$column`=? ORDER BY 1 ASC");
        $stmt->execute(array($guildId));
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $charsLines[] = spp_admin_backup_insert_sql($table, $row);
        }
        if (!empty($rows)) {
            $charsLines[] = '';
        }
    }

    return spp_admin_backup_result_with_files(
        'Guild backup created.',
        spp_admin_backup_write_output_set(
            'backup',
            'guild',
            (string)($guildRow['name'] ?? ('guild_' . $guildId)),
            array(
                'realmd' => $realmdLines,
                'chars' => $charsLines,
            )
        )
    );
}

function spp_admin_backup_create_backup_package(array $view): array
{
    $sourceRealmId = (int)($view['source_realm_id'] ?? 0);
    if ($sourceRealmId <= 0) {
        return array('ok' => false, 'message' => 'Select a source realm first.');
    }

    $entityType = (string)($view['backup_entity_type'] ?? 'character');
    $sourceRealmdPdo = spp_get_pdo('realmd', $sourceRealmId);
    $sourceCharsPdo = spp_get_pdo('chars', $sourceRealmId);

    if ($entityType === 'account') {
        return spp_admin_backup_export_account($sourceRealmdPdo, $sourceCharsPdo, $view);
    }
    if ($entityType === 'guild') {
        return spp_admin_backup_export_guild($sourceCharsPdo, $view);
    }

    return spp_admin_backup_export_character($sourceRealmdPdo, $sourceCharsPdo, $view);
}

function spp_admin_backup_xfer_character(array $view): array
{
    $sourceRealmId = (int)($view['source_realm_id'] ?? 0);
    $targetRealmId = (int)($view['target_realm_id'] ?? 0);
    $targetAccountId = (int)($view['selected_target_account_id'] ?? 0);
    $characterGuid = (int)($view['selected_character_guid'] ?? 0);
    $accountId = (int)($view['selected_account_id'] ?? 0);
    $newName = trim((string)($_POST['target_character_name'] ?? ''));

    if ($sourceRealmId <= 0 || $targetRealmId <= 0 || $sourceRealmId === $targetRealmId) {
        return array('ok' => false, 'message' => 'Choose a valid source and target realm.');
    }
    if ($targetAccountId <= 0) {
        return array('ok' => false, 'message' => 'Choose the target account for the transferred character.');
    }

    $sourceCharsPdo = spp_get_pdo('chars', $sourceRealmId);
    $targetCharsPdo = spp_get_pdo('chars', $targetRealmId);
    $targetRealmdPdo = spp_get_pdo('realmd', $targetRealmId);
    $sourceIsVmangos = function_exists('spp_admin_backup_is_vmangos_realm')
        ? spp_admin_backup_is_vmangos_realm((array)($view['realm_options'] ?? array()), $sourceRealmId)
        : false;
    $targetIsVmangos = function_exists('spp_admin_backup_is_vmangos_realm')
        ? spp_admin_backup_is_vmangos_realm((array)($view['realm_options'] ?? array()), $targetRealmId)
        : false;
    if ($sourceIsVmangos || $targetIsVmangos) {
        $validation = spp_admin_backup_vmangos_character_validation($sourceCharsPdo, $targetCharsPdo);
        if (empty($validation['ok'])) {
            return $validation;
        }
    }
    $bundle = spp_admin_backup_fetch_character_bundle($sourceCharsPdo, $characterGuid, $accountId);
    if (empty($bundle)) {
        return array('ok' => false, 'message' => 'That character could not be packaged.');
    }

    $characterLabel = $newName !== '' ? $newName : (string)($bundle['character']['name'] ?? ('guid_' . $characterGuid));
    $charsLines = array(
        spp_admin_backup_comment('Character xfer package'),
        spp_admin_backup_comment('Source realm: ' . (string)$view['source_realm_name']),
        spp_admin_backup_comment('Target realm: ' . (string)$view['target_realm_name']),
        spp_admin_backup_comment('Target account id: ' . $targetAccountId),
        spp_admin_backup_comment('Generated: ' . date('Y-m-d H:i:s')),
        '',
        'SET @target_account_id := ' . $targetAccountId . ';',
        '',
    );
    $charsLines = array_merge($charsLines, spp_admin_backup_character_bundle_xfer_lines($bundle, $targetCharsPdo, '@target_account_id', $newName));

    $realmDbMap = (array)($GLOBALS['realmDbMap'] ?? array());
    $targetCharsDbName = (string)($realmDbMap[$targetRealmId]['chars'] ?? '');
    $realmdLines = array(
        spp_admin_backup_comment('Character xfer package (realmd companion)'),
        spp_admin_backup_comment('Source realm: ' . (string)$view['source_realm_name']),
        spp_admin_backup_comment('Target realm: ' . (string)$view['target_realm_name']),
        spp_admin_backup_comment('Target account id: ' . $targetAccountId),
        spp_admin_backup_comment('Run this after the chars package so realmcharacters reflects the imported character count.'),
        spp_admin_backup_comment('Generated: ' . date('Y-m-d H:i:s')),
        '',
        'SET @target_account_id := ' . $targetAccountId . ';',
        '',
    );
    $realmdLines = array_merge($realmdLines, spp_admin_backup_realmcharacters_sync_lines($targetRealmdPdo, $targetRealmId, '@target_account_id', $targetCharsDbName));
    if (count($realmdLines) <= 9) {
        $realmdLines[] = 'SELECT 1;';
        $realmdLines[] = '';
    }

    return spp_admin_backup_result_with_files(
        'Character xfer package created.',
        spp_admin_backup_write_output_set(
            'xfer',
            'character',
            $characterLabel,
            array(
                'realmd' => $realmdLines,
                'chars' => $charsLines,
            )
        )
    );
}

function spp_admin_backup_xfer_account(array $view): array
{
    $sourceRealmId = (int)($view['source_realm_id'] ?? 0);
    $targetRealmId = (int)($view['target_realm_id'] ?? 0);
    $accountId = (int)($view['selected_account_id'] ?? 0);
    $realmOptions = (array)($view['realm_options'] ?? array());
    if ($sourceRealmId <= 0 || $targetRealmId <= 0 || $sourceRealmId === $targetRealmId) {
        return array('ok' => false, 'message' => 'Choose a valid source and target realm.');
    }

    $sourceRealmdPdo = spp_get_pdo('realmd', $sourceRealmId);
    $sourceCharsPdo = spp_get_pdo('chars', $sourceRealmId);
    $targetRealmdPdo = spp_get_pdo('realmd', $targetRealmId);
    $targetCharsPdo = spp_get_pdo('chars', $targetRealmId);

    $accountRow = spp_admin_backup_fetch_account_row($sourceRealmdPdo, $accountId);
    if (empty($accountRow)) {
        return array('ok' => false, 'message' => 'Select an account first.');
    }

    $existingTargetStmt = $targetRealmdPdo->prepare("SELECT id FROM account WHERE username=? LIMIT 1");
    $existingTargetStmt->execute(array((string)($accountRow['username'] ?? '')));
    $targetAccountId = (int)$existingTargetStmt->fetchColumn();
    $creatingAccount = $targetAccountId <= 0;
    $targetIsVmangos = function_exists('spp_admin_backup_is_vmangos_realm')
        ? spp_admin_backup_is_vmangos_realm($realmOptions, $targetRealmId)
        : false;
    $targetAccountColumns = spp_admin_backup_target_columns($targetRealmdPdo, 'account');
    $characterRows = spp_admin_backup_fetch_characters($sourceCharsPdo, $accountId);

    $realmdLines = array(
        spp_admin_backup_comment('Account xfer package'),
        spp_admin_backup_comment('Source realm: ' . (string)$view['source_realm_name']),
        spp_admin_backup_comment('Target realm: ' . (string)$view['target_realm_name']),
        spp_admin_backup_comment('Target account: ' . (string)($accountRow['username'] ?? ('ID ' . $accountId))),
        spp_admin_backup_comment('Generated: ' . date('Y-m-d H:i:s')),
        '',
    );
    $charsLines = array(
        spp_admin_backup_comment('Account xfer package (chars)'),
        spp_admin_backup_comment('Source realm: ' . (string)$view['source_realm_name']),
        spp_admin_backup_comment('Target realm: ' . (string)$view['target_realm_name']),
        spp_admin_backup_comment('Target account: ' . (string)($accountRow['username'] ?? ('ID ' . $accountId))),
        spp_admin_backup_comment('Generated: ' . date('Y-m-d H:i:s')),
        '',
    );
    $realmDbMap = (array)($GLOBALS['realmDbMap'] ?? array());
    $targetCharsDbName = (string)($realmDbMap[$targetRealmId]['chars'] ?? '');

    if ($creatingAccount) {
        $realmdLines[] = 'SET @target_account_id := (SELECT COALESCE(MAX(`id`), 0) + 1 FROM `account`);';
        $realmdLines[] = '';
        $targetAccountRow = $targetIsVmangos
            ? spp_admin_backup_vmangos_target_account_row($accountRow, $targetRealmId, $targetAccountColumns)
            : $accountRow;
        if (!$targetIsVmangos) {
            $targetAccountRow['id'] = '@target_account_id';
            $targetAccountRow['online'] = 0;
        }
        $realmdLines[] = spp_admin_backup_insert_sql_raw(
            'account',
            spp_admin_backup_filter_row_to_target_columns($targetAccountRow, $targetAccountColumns),
            array('id' => true)
        );

        foreach (spp_admin_backup_fetch_account_related_rows($sourceRealmdPdo, $accountId) as $table => $rows) {
            $targetColumns = spp_admin_backup_target_columns($targetRealmdPdo, $table);
            if ($table === 'realmcharacters') {
                continue;
            }

            foreach ($rows as $row) {
                if (isset($row['id'])) {
                    $row['id'] = '@target_account_id';
                }
                if (isset($row['acctid'])) {
                    $row['acctid'] = '@target_account_id';
                }
                $filtered = spp_admin_backup_filter_row_to_target_columns($row, $targetColumns);
                if (!empty($filtered)) {
                    $realmdLines[] = spp_admin_backup_insert_sql_raw($table, $filtered, array('id' => true, 'acctid' => true));
                }
            }
            if (!empty($rows)) {
                $realmdLines[] = '';
            }
        }
    } else {
        $realmdLines[] = 'SET @target_account_id := ' . $targetAccountId . ';';
        $realmdLines[] = spp_admin_backup_comment('Target realm already has username "' . (string)$accountRow['username'] . '". Reusing account id ' . $targetAccountId . '.');
        if ($targetIsVmangos) {
            if (in_array('current_realm', $targetAccountColumns, true)) {
                $realmdLines[] = 'UPDATE `account` SET `current_realm` = ' . $targetRealmId . ' WHERE `id` = @target_account_id LIMIT 1;';
            } elseif (in_array('active_realm_id', $targetAccountColumns, true)) {
                $realmdLines[] = 'UPDATE `account` SET `active_realm_id` = ' . $targetRealmId . ' WHERE `id` = @target_account_id LIMIT 1;';
            }
        }
        $realmdLines[] = '';
    }

    $realmdLines = array_merge($realmdLines, spp_admin_backup_realmcharacters_sync_lines($targetRealmdPdo, $targetRealmId, '@target_account_id', $targetCharsDbName));

    if ($targetIsVmangos) {
        $charsLines[] = spp_admin_backup_comment('vMaNGOS keeps character transfer separate from account xfer.');
        $charsLines[] = spp_admin_backup_comment('Use the character xfer action to generate the companion chars SQL package.');
        $charsLines[] = '';
        $charsLines[] = 'SELECT 1;';
        $charsLines[] = '';
        return spp_admin_backup_result_with_files(
            'vMaNGOS account xfer package created. Character transfer remains a separate step.',
            spp_admin_backup_write_output_set(
                'xfer',
                'account',
                (string)($accountRow['username'] ?? ('account_' . $accountId)),
                array(
                    'realmd' => $realmdLines,
                    'chars' => $charsLines,
                )
            )
        );
    }

    foreach ($characterRows as $characterRow) {
        $bundle = spp_admin_backup_fetch_character_bundle($sourceCharsPdo, (int)$characterRow['guid'], $accountId);
        if (empty($bundle)) {
            continue;
        }

        $charsLines[] = spp_admin_backup_comment('Character: ' . (string)$characterRow['name']);
        $charsLines = array_merge($charsLines, spp_admin_backup_character_bundle_xfer_lines($bundle, $targetCharsPdo, '@target_account_id', ''));
    }

    return spp_admin_backup_result_with_files(
        'Account xfer package created.',
        spp_admin_backup_write_output_set(
            'xfer',
            'account',
            (string)($accountRow['username'] ?? ('account_' . $accountId)),
            array(
                'realmd' => $realmdLines,
                'chars' => $charsLines,
            )
        )
    );
}

function spp_admin_backup_xfer_guild(array $view): array
{
    $sourceRealmId = (int)($view['source_realm_id'] ?? 0);
    $targetRealmId = (int)($view['target_realm_id'] ?? 0);
    $guildId = (int)($view['selected_guild_id'] ?? 0);
    if ($sourceRealmId <= 0 || $targetRealmId <= 0 || $sourceRealmId === $targetRealmId) {
        return array('ok' => false, 'message' => 'Choose a valid source and target realm.');
    }

    $sourceCharsPdo = spp_get_pdo('chars', $sourceRealmId);
    $targetCharsPdo = spp_get_pdo('chars', $targetRealmId);
    $guildRow = spp_admin_backup_fetch_guild_row($sourceCharsPdo, $guildId);
    if (empty($guildRow)) {
        return array('ok' => false, 'message' => 'Select a guild first.');
    }

    $leaderStmt = $sourceCharsPdo->prepare("SELECT name FROM characters WHERE guid=? LIMIT 1");
    $leaderStmt->execute(array((int)$guildRow['leaderguid']));
    $leaderName = (string)$leaderStmt->fetchColumn();

    $realmdLines = spp_admin_backup_realmd_companion_lines(
        'Guild xfer package (realmd companion)',
        array(
            'Source realm: ' . (string)$view['source_realm_name'],
            'Target realm: ' . (string)$view['target_realm_name'],
            'Guild: ' . (string)($guildRow['name'] ?? ('ID ' . $guildId)),
        ),
        'Guild xfer operates on the characters database. No realmd updates are required for this package.'
    );

    $charsLines = array(
        spp_admin_backup_comment('Guild xfer package'),
        spp_admin_backup_comment('Source realm: ' . (string)$view['source_realm_name']),
        spp_admin_backup_comment('Target realm: ' . (string)$view['target_realm_name']),
        spp_admin_backup_comment('This package expects the member characters to already exist on the target realm with the same names.'),
        spp_admin_backup_comment('Generated: ' . date('Y-m-d H:i:s')),
        '',
    );

    $charsLines[] = 'SET @target_guild_id := (SELECT COALESCE(MAX(`guildid`), 0) + 1 FROM `guild`);';
    $charsLines[] = '';
    if ($leaderName !== '') {
        $charsLines[] = 'INSERT INTO `guild` (`guildid`, `name`, `leaderguid`, `EmblemStyle`, `EmblemColor`, `BorderStyle`, `BorderColor`, `BackgroundColor`, `info`, `motd`, `createdate`, `BankMoney`) '
            . 'SELECT '
            . '@target_guild_id, '
            . spp_admin_backup_sql_literal((string)$guildRow['name']) . ', '
            . 'c.guid, '
            . spp_admin_backup_sql_literal($guildRow['EmblemStyle'] ?? 0) . ', '
            . spp_admin_backup_sql_literal($guildRow['EmblemColor'] ?? 0) . ', '
            . spp_admin_backup_sql_literal($guildRow['BorderStyle'] ?? 0) . ', '
            . spp_admin_backup_sql_literal($guildRow['BorderColor'] ?? 0) . ', '
            . spp_admin_backup_sql_literal($guildRow['BackgroundColor'] ?? 0) . ', '
            . spp_admin_backup_sql_literal((string)($guildRow['info'] ?? '')) . ', '
            . spp_admin_backup_sql_literal((string)($guildRow['motd'] ?? '')) . ', '
            . spp_admin_backup_sql_literal($guildRow['createdate'] ?? 0) . ', '
            . spp_admin_backup_sql_literal($guildRow['BankMoney'] ?? 0)
            . ' FROM `characters` c WHERE c.name = ' . spp_admin_backup_sql_literal($leaderName) . ' LIMIT 1;';
    } else {
        $charsLines[] = spp_admin_backup_comment('Leader character name could not be resolved. Adjust guild insert manually if needed.');
    }
    $charsLines[] = '';

    $rankStmt = $sourceCharsPdo->prepare("SELECT * FROM guild_rank WHERE guildid=? ORDER BY rid ASC");
    $rankStmt->execute(array($guildId));
    foreach ($rankStmt->fetchAll(PDO::FETCH_ASSOC) as $rankRow) {
        $rankRow['guildid'] = '@target_guild_id';
        $filtered = spp_admin_backup_filter_row_to_target_columns($rankRow, spp_admin_backup_target_columns($targetCharsPdo, 'guild_rank'));
        if (!empty($filtered)) {
            $charsLines[] = spp_admin_backup_insert_sql_raw('guild_rank', $filtered, array('guildid' => true));
        }
    }
    $charsLines[] = '';

    $memberStmt = $sourceCharsPdo->prepare("
        SELECT gm.*, c.name AS member_name
        FROM guild_member gm
        LEFT JOIN characters c ON c.guid = gm.guid
        WHERE gm.guildid=?
        ORDER BY gm.rank ASC, gm.guid ASC
    ");
    $memberStmt->execute(array($guildId));
    foreach ($memberStmt->fetchAll(PDO::FETCH_ASSOC) as $memberRow) {
        $memberName = trim((string)($memberRow['member_name'] ?? ''));
        if ($memberName === '') {
            continue;
        }
        $charsLines[] = 'INSERT INTO `guild_member` (`guildid`, `guid`, `rank`, `pnote`, `offnote`) '
            . 'SELECT '
            . '@target_guild_id, '
            . 'c.guid, '
            . spp_admin_backup_sql_literal($memberRow['rank'] ?? 0) . ', '
            . spp_admin_backup_sql_literal((string)($memberRow['pnote'] ?? '')) . ', '
            . spp_admin_backup_sql_literal((string)($memberRow['offnote'] ?? ''))
            . ' FROM `characters` c WHERE c.name = ' . spp_admin_backup_sql_literal($memberName) . ' LIMIT 1;';
    }

    return spp_admin_backup_result_with_files(
        'Guild xfer package created.',
        spp_admin_backup_write_output_set(
            'xfer',
            'guild',
            (string)($guildRow['name'] ?? ('guild_' . $guildId)),
            array(
                'realmd' => $realmdLines,
                'chars' => $charsLines,
            )
        )
    );
}

function spp_admin_backup_create_xfer_package(array $view): array
{
    $entityType = (string)($view['xfer_entity_type'] ?? 'character');
    $selectedRoute = (array)($view['selected_xfer_route'] ?? array());
    $supportedEntities = array_values((array)($selectedRoute['supported_entities'] ?? array()));
    if (!empty($supportedEntities) && !in_array($entityType, $supportedEntities, true)) {
        return array('ok' => false, 'message' => 'That transfer type is not enabled for the selected realm route.');
    }
    if ($entityType === 'account') {
        return spp_admin_backup_xfer_account($view);
    }
    if ($entityType === 'guild') {
        return spp_admin_backup_xfer_guild($view);
    }

    return spp_admin_backup_xfer_character($view);
}

function spp_admin_backup_handle_action(array $view): array
{
    $state = spp_admin_backup_state_defaults();
    $action = (string)($_POST['backup_action'] ?? '');
    if ($action === '') {
        return $state;
    }

    spp_require_csrf('admin_backup');

    if ($action === 'create_backup_package') {
        $result = spp_admin_backup_create_backup_package($view);
    } elseif ($action === 'create_xfer_package') {
        $result = spp_admin_backup_create_xfer_package($view);
    } else {
        $result = array('ok' => false, 'message' => 'Unknown backup action.');
    }

    if (!empty($result['ok'])) {
        $files = array_values((array)($result['files'] ?? array()));
        $filename = !empty($result['path'])
            ? spp_admin_backup_basename((string)$result['path'])
            : (!empty($files[0]['filename']) ? (string)$files[0]['filename'] : '');
        $state['notice'] = (string)$result['message'];
        $state['filename'] = $filename;
        $state['download_url'] = $filename !== '' ? spp_admin_backup_download_url($filename) : '';
        $state['downloads'] = $files;
    } else {
        $state['error'] = (string)($result['message'] ?? 'The requested package could not be created.');
    }

    return $state;
}
