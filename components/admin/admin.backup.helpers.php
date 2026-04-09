<?php
if (INCLUDED !== true) {
    exit;
}

require_once dirname(__DIR__, 2) . '/app/support/db-schema.php';

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

function spp_admin_backup_xfer_entity_options(): array
{
    return array(
        'character' => 'Character',
        'account' => 'Account',
        'guild' => 'Guild',
    );
}

function spp_admin_backup_route_option(array $source, array $target): array
{
    $sourceExpansion = (string)($source['expansion_key'] ?? '');
    $targetExpansion = (string)($target['expansion_key'] ?? '');
    $targetIsVmangos = ($targetExpansion === 'vmangos');
    $sourceIsVmangos = ($sourceExpansion === 'vmangos');
    $supportedEntities = array('character', 'account', 'guild');

    if ($targetIsVmangos && !$sourceIsVmangos) {
        $supportedEntities = array('account', 'guild');
    } elseif ($targetIsVmangos || $sourceIsVmangos) {
        $supportedEntities = array('character', 'account');
    }

    $label = (string)$source['name'] . ' -> ' . (string)$target['name'];
    if ($targetIsVmangos && !$sourceIsVmangos) {
        $label .= ' (CMaNGOS -> vMaNGOS)';
    }

    return array(
        'id' => (int)$source['id'] . ':' . (int)$target['id'],
        'source_realm_id' => (int)$source['id'],
        'target_realm_id' => (int)$target['id'],
        'label' => $label,
        'source_expansion_key' => $sourceExpansion,
        'target_expansion_key' => $targetExpansion,
        'source_is_vmangos' => $sourceIsVmangos,
        'target_is_vmangos' => $targetIsVmangos,
        'supported_entities' => $supportedEntities,
    );
}

function spp_admin_backup_route_entity_options(?array $routeOption): array
{
    $entityOptions = spp_admin_backup_xfer_entity_options();
    if (empty($routeOption['supported_entities']) || !is_array($routeOption['supported_entities'])) {
        return $entityOptions;
    }

    $filtered = array();
    foreach ($routeOption['supported_entities'] as $entityKey) {
        $entityKey = (string)$entityKey;
        if (isset($entityOptions[$entityKey])) {
            $filtered[$entityKey] = $entityOptions[$entityKey];
        }
    }

    return !empty($filtered) ? $filtered : $entityOptions;
}

function spp_admin_backup_route_help(?array $routeOption): string
{
    if (empty($routeOption) || !is_array($routeOption)) {
        return '';
    }

    if (!empty($routeOption['target_is_vmangos']) && empty($routeOption['source_is_vmangos'])) {
        return 'For CMaNGOS -> vMaNGOS, Xfer builds one manual transform-export package per job. Account and guild resolve into reviewable realmd.sql plus chars.sql, with a manifest and mirrored converter helpers for the operator workflow. Individual character export is intentionally not offered on this route.';
    }

    if (!empty($routeOption['target_is_vmangos']) || !empty($routeOption['source_is_vmangos'])) {
        return 'vMaNGOS routes only offer account and character transform-export packages. realmd.sql and chars.sql stay separate, manual apply is expected, and character export remains schema-validated.';
    }

    return 'Account xfer includes character SQL on standard CMaNGOS routes. Guild xfer assumes member characters already exist on the target realm.';
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
    $options = array_merge(spp_admin_backup_entity_options(), spp_admin_backup_xfer_entity_options());
    return $options[$entityType] ?? ucfirst($entityType);
}

function spp_admin_backup_first_array_key(array $items, string $fallback = ''): string
{
    foreach ($items as $key => $_value) {
        return (string)$key;
    }

    return $fallback;
}

function spp_admin_backup_is_random_bot_account_name(?string $username): bool
{
    $username = strtolower(trim((string)$username));
    return $username !== '' && strpos($username, 'rndbot') === 0;
}

function spp_admin_backup_is_bot_account_name(?string $username): bool
{
    $username = strtolower(trim((string)$username));
    if ($username === '') {
        return false;
    }

    return strpos($username, 'rndbot') === 0
        || strpos($username, 'aibot') === 0
        || strpos($username, 'npc') === 0;
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
            $routes[] = spp_admin_backup_route_option($source, $target);
        }
    }

    usort($routes, static function ($a, $b) {
        $aPriority = (!empty($a['target_is_vmangos']) && empty($a['source_is_vmangos'])) ? 0 : (!empty($a['target_is_vmangos']) ? 1 : 2);
        $bPriority = (!empty($b['target_is_vmangos']) && empty($b['source_is_vmangos'])) ? 0 : (!empty($b['target_is_vmangos']) ? 1 : 2);
        if ($aPriority !== $bPriority) {
            return $aPriority <=> $bPriority;
        }

        return strcmp((string)($a['label'] ?? ''), (string)($b['label'] ?? ''));
    });

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

function spp_admin_backup_vmangos_target_account_row(array $sourceAccountRow, int $targetRealmId, array $targetColumns, string $targetAccountExpr = '@target_account_id'): array
{
    $row = array();
    foreach ($targetColumns as $column) {
        switch ($column) {
            case 'id':
                $row[$column] = $targetAccountExpr;
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
                $row[$column] = (string)($sourceAccountRow['last_ip'] ?? ($sourceAccountRow['lockedIp'] ?? ''));
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
            case 'token_key':
                $row[$column] = (string)($sourceAccountRow['token_key'] ?? ($sourceAccountRow['token'] ?? ''));
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

function spp_admin_backup_fetch_accounts(PDO $realmdPdo, string $mode = 'human', bool $includeRandomBots = false): array
{
    $stmt = $realmdPdo->query("
        SELECT id, username
        FROM account
        ORDER BY username ASC, id ASC
    ");
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array();
    if ($mode === 'all') {
        return $rows;
    }

    $filtered = array();
    foreach ($rows as $row) {
        $isBot = spp_admin_backup_is_bot_account_name((string)($row['username'] ?? ''));
        $isRandomBot = spp_admin_backup_is_random_bot_account_name((string)($row['username'] ?? ''));

        if ($mode === 'bot') {
            if (!$isBot) {
                continue;
            }
            if (!$includeRandomBots && $isRandomBot) {
                continue;
            }
            $filtered[] = $row;
            continue;
        }

        if (!$isBot) {
            $filtered[] = $row;
        }
    }

    return $filtered;
}

function spp_admin_backup_vmangos_character_flags(array $sourceRow): int
{
    $playerFlags = (int)($sourceRow['playerFlags'] ?? 0);
    $atLoginFlags = (int)($sourceRow['at_login'] ?? 0);
    $characterFlags = 0;

    if (($playerFlags & 0x00000008) !== 0) {
        $characterFlags |= 0x00020000;
    }
    if (($playerFlags & 0x00000010) !== 0) {
        $characterFlags |= 0x00002000;
    }
    if (($playerFlags & 0x00000020) !== 0) {
        $characterFlags |= 0x00000002;
    }
    if (($playerFlags & 0x00000200) !== 0) {
        $characterFlags |= 0x00010000;
    }
    if (($playerFlags & 0x00000400) !== 0) {
        $characterFlags |= 0x00000400;
    }
    if (($playerFlags & 0x00000800) !== 0) {
        $characterFlags |= 0x00000800;
    }
    if (($atLoginFlags & 0x01) !== 0) {
        $characterFlags |= 0x00004000;
    }
    if (($atLoginFlags & 0x04) !== 0) {
        $characterFlags |= 0x00000100;
    }

    return $characterFlags;
}

function spp_admin_backup_vmangos_character_appearance(array $sourceRow): array
{
    $playerBytes = (int)($sourceRow['playerBytes'] ?? 0);
    $playerBytes2 = (int)($sourceRow['playerBytes2'] ?? 0);

    return array(
        'skin' => ($playerBytes & 0xFF),
        'face' => (($playerBytes >> 8) & 0xFF),
        'hair_style' => (($playerBytes >> 16) & 0xFF),
        'hair_color' => (($playerBytes >> 24) & 0xFF),
        'facial_hair' => ($playerBytes2 & 0xFF),
    );
}

function spp_admin_backup_vmangos_table_mappings(): array
{
    return array(
        'account' => array(
            'database' => 'realmd',
            'source_required' => array('id', 'username', 'lockedIp', 'token'),
            'target_required' => array('id', 'username', 'last_ip', 'token_key', 'current_realm'),
        ),
        'account_access' => array(
            'database' => 'realmd',
            'optional_source' => true,
            'source_required' => array('id', 'gmlevel'),
            'target_required' => array('id', 'gmlevel'),
        ),
        'account_banned' => array(
            'database' => 'realmd',
            'optional_source' => true,
            'optional_target' => true,
            'rename' => array(
                'account_id' => 'id',
                'banned_at' => 'bandate',
                'expires_at' => 'unbandate',
                'banned_by' => 'bannedby',
                'reason' => 'banreason',
            ),
            'source_required' => array('account_id', 'banned_at', 'expires_at', 'banned_by', 'reason', 'active'),
            'target_required' => array('id', 'bandate', 'unbandate', 'bannedby', 'banreason', 'active'),
        ),
        'realmcharacters' => array(
            'database' => 'realmd',
            'source_required' => array('realmid', 'acctid', 'numchars'),
            'target_required' => array('realmid', 'acctid', 'numchars'),
        ),
        'guild' => array(
            'database' => 'chars',
            'rename' => array(
                'guildid' => 'guild_id',
                'leaderguid' => 'leader_guid',
                'EmblemStyle' => 'emblem_style',
                'EmblemColor' => 'emblem_color',
                'BorderStyle' => 'border_style',
                'BorderColor' => 'border_color',
                'BackgroundColor' => 'background_color',
                'createdate' => 'create_date',
            ),
            'source_required' => array('guildid', 'name', 'leaderguid', 'EmblemStyle', 'EmblemColor', 'BorderStyle', 'BorderColor', 'BackgroundColor', 'info', 'motd', 'createdate'),
            'target_required' => array('guild_id', 'name', 'leader_guid', 'emblem_style', 'emblem_color', 'border_style', 'border_color', 'background_color', 'info', 'motd', 'create_date'),
        ),
        'guild_rank' => array(
            'database' => 'chars',
            'rename' => array(
                'guildid' => 'guild_id',
                'rid' => 'id',
                'rname' => 'name',
            ),
            'source_required' => array('guildid', 'rid', 'rname', 'rights'),
            'target_required' => array('guild_id', 'id', 'name', 'rights'),
        ),
        'guild_member' => array(
            'database' => 'chars',
            'rename' => array(
                'guildid' => 'guild_id',
                'pnote' => 'player_note',
                'offnote' => 'officer_note',
            ),
            'source_required' => array('guildid', 'guid', 'rank', 'pnote', 'offnote'),
            'target_required' => array('guild_id', 'guid', 'rank', 'player_note', 'officer_note'),
        ),
        'guild_eventlog' => array(
            'database' => 'chars',
            'rename' => array(
                'guildid' => 'guild_id',
                'LogGuid' => 'log_guid',
                'EventType' => 'event_type',
                'PlayerGuid1' => 'player_guid1',
                'PlayerGuid2' => 'player_guid2',
                'NewRank' => 'new_rank',
                'TimeStamp' => 'timestamp',
            ),
            'source_required' => array('guildid', 'LogGuid', 'EventType', 'PlayerGuid1', 'PlayerGuid2', 'NewRank', 'TimeStamp'),
            'target_required' => array('guild_id', 'log_guid', 'event_type', 'player_guid1', 'player_guid2', 'new_rank', 'timestamp'),
        ),
        'characters' => array(
            'database' => 'chars',
            'rename' => array(
                'playerFlags' => 'character_flags',
                'taximask' => 'known_taxi_mask',
                'taxi_path' => 'current_taxi_path',
                'totaltime' => 'played_time_total',
                'leveltime' => 'played_time_level',
                'watchedFaction' => 'watched_faction',
                'exploredZones' => 'explored_zones',
                'equipmentCache' => 'equipment_cache',
                'ammoId' => 'ammo_id',
                'actionBars' => 'action_bars',
                'deleteInfos_Account' => 'deleted_account',
                'deleteInfos_Name' => 'deleted_name',
                'deleteDate' => 'deleted_time',
                'transguid' => 'transport_guid',
                'trans_x' => 'transport_x',
                'trans_y' => 'transport_y',
                'trans_z' => 'transport_z',
                'trans_o' => 'transport_o',
                'resettalents_cost' => 'reset_talents_multiplier',
                'resettalents_time' => 'reset_talents_time',
                'stored_honor_rating' => 'honor_rank_points',
                'stored_honorable_kills' => 'honor_stored_hk',
                'stored_dishonorable_kills' => 'honor_stored_dk',
            ),
            'source_required' => array(
                'guid', 'account', 'name', 'playerFlags', 'at_login', 'taximask', 'taxi_path', 'totaltime', 'leveltime',
                'watchedFaction', 'exploredZones', 'equipmentCache', 'ammoId', 'actionBars', 'deleteInfos_Account',
                'deleteInfos_Name', 'deleteDate', 'transguid', 'trans_x', 'trans_y', 'trans_z', 'trans_o',
                'resettalents_cost', 'resettalents_time', 'stored_honor_rating', 'stored_honorable_kills',
                'stored_dishonorable_kills', 'playerBytes', 'playerBytes2'
            ),
            'target_required' => array(
                'guid', 'account', 'name', 'character_flags', 'known_taxi_mask', 'current_taxi_path', 'played_time_total',
                'played_time_level', 'watched_faction', 'explored_zones', 'equipment_cache', 'ammo_id', 'action_bars',
                'deleted_account', 'deleted_name', 'deleted_time', 'transport_guid', 'transport_x', 'transport_y',
                'transport_z', 'transport_o', 'reset_talents_multiplier', 'reset_talents_time', 'honor_rank_points',
                'honor_stored_hk', 'honor_stored_dk', 'skin', 'face', 'hair_style', 'hair_color', 'facial_hair'
            ),
        ),
        'character_inventory' => array(
            'database' => 'chars',
            'rename' => array(
                'item' => 'item_guid',
                'item_template' => 'item_id',
            ),
            'source_required' => array('guid', 'bag', 'slot', 'item', 'item_template'),
            'target_required' => array('guid', 'bag', 'slot', 'item_guid', 'item_id'),
        ),
        'mail' => array(
            'database' => 'chars',
            'rename' => array(
                'messageType' => 'message_type',
                'mailTemplateId' => 'mail_template_id',
                'sender' => 'sender_guid',
                'receiver' => 'receiver_guid',
                'itemTextId' => 'item_text_id',
            ),
            'source_required' => array('id', 'messageType', 'mailTemplateId', 'sender', 'receiver', 'itemTextId'),
            'target_required' => array('id', 'message_type', 'mail_template_id', 'sender_guid', 'receiver_guid', 'item_text_id'),
        ),
        'mail_items' => array(
            'database' => 'chars',
            'rename' => array(
                'item_template' => 'item_id',
                'receiver' => 'receiver_guid',
            ),
            'source_required' => array('mail_id', 'item_guid', 'item_template', 'receiver'),
            'target_required' => array('mail_id', 'item_guid', 'item_id', 'receiver_guid'),
        ),
        'item_instance' => array(
            'database' => 'chars',
            'rename' => array(
                'itemEntry' => 'item_id',
                'creatorGuid' => 'creator_guid',
                'giftCreatorGuid' => 'gift_creator_guid',
                'randomPropertyId' => 'random_property_id',
                'itemTextId' => 'text',
            ),
            'source_required' => array('guid', 'itemEntry', 'creatorGuid', 'giftCreatorGuid', 'randomPropertyId', 'itemTextId'),
            'target_required' => array('guid', 'item_id', 'creator_guid', 'gift_creator_guid', 'random_property_id', 'text'),
        ),
        'item_loot' => array(
            'database' => 'chars',
            'rename' => array(
                'itemid' => 'item_id',
            ),
            'source_required' => array('guid', 'owner_guid', 'itemid', 'amount', 'property'),
            'target_required' => array('guid', 'owner_guid', 'item_id', 'amount', 'property'),
        ),
        'character_pet' => array(
            'database' => 'chars',
            'rename' => array(
                'owner' => 'owner_guid',
                'modelid' => 'display_id',
                'CreatedBySpell' => 'created_by_spell',
                'PetType' => 'pet_type',
                'Reactstate' => 'react_state',
                'loyaltypoints' => 'loyalty_points',
                'trainpoint' => 'training_points',
                'curhealth' => 'current_health',
                'curmana' => 'current_mana',
                'curhappiness' => 'current_happiness',
                'savetime' => 'save_time',
                'resettalents_cost' => 'reset_talents_cost',
                'resettalents_time' => 'reset_talents_time',
                'abdata' => 'action_bar_data',
                'teachspelldata' => 'teach_spell_data',
            ),
            'source_required' => array(
                'id', 'entry', 'owner', 'modelid', 'CreatedBySpell', 'PetType', 'Reactstate', 'loyaltypoints',
                'trainpoint', 'curhealth', 'curmana', 'curhappiness', 'savetime', 'resettalents_cost',
                'resettalents_time', 'abdata', 'teachspelldata'
            ),
            'target_required' => array(
                'id', 'entry', 'owner_guid', 'display_id', 'created_by_spell', 'pet_type', 'react_state',
                'loyalty_points', 'training_points', 'current_health', 'current_mana', 'current_happiness',
                'save_time', 'reset_talents_cost', 'reset_talents_time', 'action_bar_data', 'teach_spell_data'
            ),
        ),
        'character_aura' => array(
            'database' => 'chars',
            'rename' => array(
                'stackcount' => 'stacks',
                'remaincharges' => 'charges',
                'basepoints0' => 'base_points0',
                'basepoints1' => 'base_points1',
                'basepoints2' => 'base_points2',
                'periodictime0' => 'periodic_time0',
                'periodictime1' => 'periodic_time1',
                'periodictime2' => 'periodic_time2',
                'maxduration' => 'max_duration',
                'remaintime' => 'duration',
                'effIndexMask' => 'effect_index_mask',
            ),
            'source_required' => array('guid', 'stackcount', 'remaincharges', 'basepoints0', 'basepoints1', 'basepoints2', 'periodictime0', 'periodictime1', 'periodictime2', 'maxduration', 'remaintime', 'effIndexMask'),
            'target_required' => array('guid', 'stacks', 'charges', 'base_points0', 'base_points1', 'base_points2', 'periodic_time0', 'periodic_time1', 'periodic_time2', 'max_duration', 'duration', 'effect_index_mask'),
        ),
        'pet_aura' => array(
            'database' => 'chars',
            'rename' => array(
                'stackcount' => 'stacks',
                'remaincharges' => 'charges',
                'basepoints0' => 'base_points0',
                'basepoints1' => 'base_points1',
                'basepoints2' => 'base_points2',
                'periodictime0' => 'periodic_time0',
                'periodictime1' => 'periodic_time1',
                'periodictime2' => 'periodic_time2',
                'maxduration' => 'max_duration',
                'remaintime' => 'duration',
                'effIndexMask' => 'effect_index_mask',
            ),
            'source_required' => array('guid', 'stackcount', 'remaincharges', 'basepoints0', 'basepoints1', 'basepoints2', 'periodictime0', 'periodictime1', 'periodictime2', 'maxduration', 'remaintime', 'effIndexMask'),
            'target_required' => array('guid', 'stacks', 'charges', 'base_points0', 'base_points1', 'base_points2', 'periodic_time0', 'periodic_time1', 'periodic_time2', 'max_duration', 'duration', 'effect_index_mask'),
        ),
        'character_queststatus' => array(
            'database' => 'chars',
            'rename' => array(
                'mobcount1' => 'mob_count1',
                'mobcount2' => 'mob_count2',
                'mobcount3' => 'mob_count3',
                'mobcount4' => 'mob_count4',
                'itemcount1' => 'item_count1',
                'itemcount2' => 'item_count2',
                'itemcount3' => 'item_count3',
                'itemcount4' => 'item_count4',
            ),
            'source_required' => array('guid', 'quest', 'mobcount1', 'mobcount2', 'mobcount3', 'mobcount4', 'itemcount1', 'itemcount2', 'itemcount3', 'itemcount4'),
            'target_required' => array('guid', 'quest', 'mob_count1', 'mob_count2', 'mob_count3', 'mob_count4', 'item_count1', 'item_count2', 'item_count3', 'item_count4'),
        ),
        'character_spell_cooldown' => array(
            'database' => 'chars',
            'rename' => array(
                'SpellId' => 'spell',
                'SpellExpireTime' => 'spell_expire_time',
                'Category' => 'category',
                'CategoryExpireTime' => 'category_expire_time',
                'ItemId' => 'item_id',
            ),
            'source_required' => array('guid', 'SpellId', 'SpellExpireTime', 'Category', 'CategoryExpireTime', 'ItemId'),
            'target_required' => array('guid', 'spell', 'spell_expire_time', 'category', 'category_expire_time', 'item_id'),
        ),
        'character_gifts' => array(
            'database' => 'chars',
            'rename' => array(
                'entry' => 'item_id',
            ),
            'source_required' => array('guid', 'item_guid', 'entry', 'flags'),
            'target_required' => array('guid', 'item_guid', 'item_id', 'flags'),
        ),
        'character_honor_cp' => array(
            'database' => 'chars',
            'rename' => array(
                'victim' => 'victim_id',
                'honor' => 'cp',
            ),
            'source_required' => array('guid', 'victim_type', 'victim', 'honor', 'date', 'type'),
            'target_required' => array('guid', 'victim_type', 'victim_id', 'cp', 'date', 'type'),
        ),
    );
}

function spp_admin_backup_vmangos_map_row(string $table, array $sourceRow): array
{
    $definitions = spp_admin_backup_vmangos_table_mappings();
    $definition = (array)($definitions[$table] ?? array());
    $rename = (array)($definition['rename'] ?? array());
    $mapped = array();

    foreach ($sourceRow as $column => $value) {
        $targetColumn = isset($rename[$column]) ? (string)$rename[$column] : (string)$column;
        $mapped[$targetColumn] = $value;
    }

    if ($table === 'characters') {
        $mapped['character_flags'] = spp_admin_backup_vmangos_character_flags($sourceRow);
        foreach (spp_admin_backup_vmangos_character_appearance($sourceRow) as $column => $value) {
            $mapped[$column] = $value;
        }
    }

    return $mapped;
}

function spp_admin_backup_vmangos_transform_validation(PDO $sourceRealmdPdo, PDO $sourceCharsPdo, PDO $targetRealmdPdo, PDO $targetCharsPdo, array $schemaNames = array()): array
{
    $definitions = spp_admin_backup_vmangos_table_mappings();
    $defaultSchemas = array(
        'source_realmd' => 'source_realmd',
        'source_chars' => 'source_chars',
        'target_realmd' => 'target_realmd',
        'target_chars' => 'target_chars',
    );
    $schemaNames = array_merge($defaultSchemas, $schemaNames);
    $requiredCharsTables = array_merge(array('characters'), array_keys(spp_admin_backup_character_tables()), array('guild', 'guild_rank', 'guild_member', 'guild_eventlog'));
    $requiredCharsTables = array_values(array_unique(array_map('strval', $requiredCharsTables)));
    foreach ($requiredCharsTables as $table) {
        if (!function_exists('spp_db_table_exists') || !spp_db_table_exists($sourceCharsPdo, $table)) {
            return array('ok' => false, 'message' => 'Missing source table ' . $schemaNames['source_chars'] . '.' . $table);
        }
        if (!function_exists('spp_db_table_exists') || !spp_db_table_exists($targetCharsPdo, $table)) {
            return array('ok' => false, 'message' => 'Missing target table ' . $schemaNames['target_chars'] . '.' . $table);
        }
    }

    foreach (array('account', 'realmcharacters') as $table) {
        if (!function_exists('spp_db_table_exists') || !spp_db_table_exists($sourceRealmdPdo, $table)) {
            return array('ok' => false, 'message' => 'Missing source table ' . $schemaNames['source_realmd'] . '.' . $table);
        }
        if (!function_exists('spp_db_table_exists') || !spp_db_table_exists($targetRealmdPdo, $table)) {
            return array('ok' => false, 'message' => 'Missing target table ' . $schemaNames['target_realmd'] . '.' . $table);
        }
    }

    foreach ($definitions as $table => $definition) {
        $database = (string)($definition['database'] ?? 'chars');
        $sourcePdo = $database === 'realmd' ? $sourceRealmdPdo : $sourceCharsPdo;
        $targetPdo = $database === 'realmd' ? $targetRealmdPdo : $targetCharsPdo;
        $sourceSchema = $database === 'realmd' ? (string)$schemaNames['source_realmd'] : (string)$schemaNames['source_chars'];
        $targetSchema = $database === 'realmd' ? (string)$schemaNames['target_realmd'] : (string)$schemaNames['target_chars'];
        $sourceTable = (string)($definition['source_table'] ?? $table);
        $targetTable = (string)($definition['target_table'] ?? $table);
        $optionalSource = !empty($definition['optional_source']);
        $optionalTarget = !empty($definition['optional_target']);

        $sourceExists = function_exists('spp_db_table_exists') ? spp_db_table_exists($sourcePdo, $sourceTable) : false;
        $targetExists = function_exists('spp_db_table_exists') ? spp_db_table_exists($targetPdo, $targetTable) : false;

        if (!$optionalSource && !$sourceExists) {
            return array('ok' => false, 'message' => 'Missing source table ' . $sourceSchema . '.' . $sourceTable);
        }
        if (!$optionalTarget && !$targetExists) {
            return array('ok' => false, 'message' => 'Missing target table ' . $targetSchema . '.' . $targetTable);
        }

        if ($sourceExists) {
            foreach ((array)($definition['source_required'] ?? array()) as $column) {
                if (!function_exists('spp_db_column_exists') || !spp_db_column_exists($sourcePdo, $sourceTable, (string)$column)) {
                    return array('ok' => false, 'message' => 'Missing source column ' . $sourceSchema . '.' . $sourceTable . '.' . $column);
                }
            }
        }

        if ($targetExists) {
            foreach ((array)($definition['target_required'] ?? array()) as $column) {
                if (!function_exists('spp_db_column_exists') || !spp_db_column_exists($targetPdo, $targetTable, (string)$column)) {
                    return array('ok' => false, 'message' => 'Missing target column ' . $targetSchema . '.' . $targetTable . '.' . $column);
                }
            }
        }
    }

    return array('ok' => true, 'message' => '');
}

function spp_admin_backup_fetch_characters(PDO $charsPdo, int $accountId): array
{
    if ($accountId <= 0) {
        return array();
    }

    $stmt = $charsPdo->prepare("SELECT guid, account, name, race, class, level FROM characters WHERE account=? ORDER BY name ASC, guid ASC");
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
        try {
            if (function_exists('spp_db_table_exists') && !spp_db_table_exists($realmdPdo, $table)) {
                $rows[$table] = array();
                continue;
            }

            $stmt = $realmdPdo->prepare("SELECT * FROM `$table` WHERE `$column`=?");
            $stmt->execute(array($accountId));
            $rows[$table] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('[admin.backup] Skipping optional auth table `' . $table . '`: ' . $e->getMessage());
            $rows[$table] = array();
        }
    }

    return $rows;
}

function spp_admin_backup_fetch_guild_members(PDO $charsPdo, int $guildId): array
{
    if ($guildId <= 0) {
        return array();
    }

    $stmt = $charsPdo->prepare("
        SELECT gm.*, c.name AS member_name, c.account, c.level
        FROM guild_member gm
        LEFT JOIN characters c ON c.guid = gm.guid
        WHERE gm.guildid=?
        ORDER BY gm.rank ASC, gm.guid ASC
    ");
    $stmt->execute(array($guildId));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function spp_admin_backup_fetch_account_rows_by_ids(PDO $realmdPdo, array $accountIds): array
{
    $accountIds = array_values(array_filter(array_unique(array_map('intval', $accountIds))));
    if (empty($accountIds)) {
        return array();
    }

    $placeholders = implode(',', array_fill(0, count($accountIds), '?'));
    $stmt = $realmdPdo->prepare("SELECT * FROM account WHERE id IN ($placeholders) ORDER BY username ASC, id ASC");
    $stmt->execute($accountIds);

    $rows = array();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $rows[(int)($row['id'] ?? 0)] = $row;
    }

    return $rows;
}

function spp_admin_backup_fetch_guild_summary(PDO $realmdPdo, PDO $charsPdo, int $guildId): array
{
    $members = spp_admin_backup_fetch_guild_members($charsPdo, $guildId);
    $accountIds = array();
    foreach ($members as $member) {
        $accountId = (int)($member['account'] ?? 0);
        if ($accountId > 0) {
            $accountIds[] = $accountId;
        }
    }
    $accountIds = array_values(array_filter(array_unique($accountIds)));
    $accountRows = spp_admin_backup_fetch_account_rows_by_ids($realmdPdo, $accountIds);

    $botAccounts = 0;
    $humanAccounts = 0;
    foreach ($accountIds as $accountId) {
        $accountRow = $accountRows[$accountId] ?? array();
        $username = (string)($accountRow['username'] ?? '');
        if (spp_admin_backup_is_bot_account_name($username)) {
            $botAccounts++;
        } else {
            $humanAccounts++;
        }
    }

    return array(
        'member_count' => count($members),
        'account_count' => count($accountIds),
        'bot_account_count' => $botAccounts,
        'human_account_count' => $humanAccounts,
        'member_rows' => $members,
        'account_ids' => $accountIds,
    );
}

function spp_admin_backup_resolve_xfer_scope(PDO $sourceRealmdPdo, PDO $sourceCharsPdo, array $view): array
{
    $entityType = (string)($view['xfer_entity_type'] ?? 'character');
    $characterRows = array();
    $accountIds = array();
    $meta = array();
    $label = '';

    if ($entityType === 'character') {
        $characterGuid = (int)($view['selected_character_guid'] ?? 0);
        $accountId = (int)($view['selected_account_id'] ?? 0);
        $characterRow = spp_admin_backup_fetch_character_row($sourceCharsPdo, $characterGuid, $accountId);
        if (empty($characterRow)) {
            return array('ok' => false, 'message' => 'Select a character first.');
        }

        $characterRows[] = $characterRow;
        $accountIds[] = (int)($characterRow['account'] ?? $accountId);
        $label = (string)($characterRow['name'] ?? ('guid_' . $characterGuid));
    } elseif ($entityType === 'account' || $entityType === 'bot') {
        $accountId = (int)($entityType === 'bot'
            ? ($view['selected_bot_account_id'] ?? 0)
            : ($view['selected_account_id'] ?? 0));
        $accountRow = spp_admin_backup_fetch_account_row($sourceRealmdPdo, $accountId);
        if (empty($accountRow)) {
            return array('ok' => false, 'message' => 'Select a ' . ($entityType === 'bot' ? 'bot account' : 'source account') . ' first.');
        }

        $characterRows = spp_admin_backup_fetch_characters($sourceCharsPdo, $accountId);
        $accountIds[] = $accountId;
        $label = (string)($accountRow['username'] ?? ($entityType . '_' . $accountId));
    } elseif ($entityType === 'guild') {
        $guildId = (int)($view['selected_guild_id'] ?? 0);
        $guildRow = spp_admin_backup_fetch_guild_row($sourceCharsPdo, $guildId);
        if (empty($guildRow)) {
            return array('ok' => false, 'message' => 'Select a guild first.');
        }

        $guildSummary = spp_admin_backup_fetch_guild_summary($sourceRealmdPdo, $sourceCharsPdo, $guildId);
        foreach ((array)($guildSummary['member_rows'] ?? array()) as $memberRow) {
            $guid = (int)($memberRow['guid'] ?? 0);
            $accountId = (int)($memberRow['account'] ?? 0);
            if ($guid <= 0 || $accountId <= 0) {
                continue;
            }

            $characterRow = spp_admin_backup_fetch_character_row($sourceCharsPdo, $guid, $accountId);
            if (!empty($characterRow)) {
                $characterRows[] = $characterRow;
                $accountIds[] = $accountId;
            }
        }

        $label = (string)($guildRow['name'] ?? ('guild_' . $guildId));
        $meta['guild_row'] = $guildRow;
        $meta['guild_summary'] = $guildSummary;
    }

    $accountIds = array_values(array_filter(array_unique(array_map('intval', $accountIds))));
    $characterRowsByGuid = array();
    foreach ($characterRows as $characterRow) {
        $guid = (int)($characterRow['guid'] ?? 0);
        if ($guid > 0) {
            $characterRowsByGuid[$guid] = $characterRow;
        }
    }
    ksort($characterRowsByGuid);

    $accountRowsById = spp_admin_backup_fetch_account_rows_by_ids($sourceRealmdPdo, $accountIds);

    return array(
        'ok' => true,
        'entity_type' => $entityType,
        'label' => $label !== '' ? $label : $entityType,
        'character_rows' => array_values($characterRowsByGuid),
        'character_guids' => array_keys($characterRowsByGuid),
        'account_ids' => $accountIds,
        'account_rows_by_id' => $accountRowsById,
        'meta' => $meta,
    );
}

function spp_admin_backup_build_filename(string $prefix, string $entityType, string $label, string $lane = '', ?string $stamp = null, string $extension = 'sql'): string
{
    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '_', trim($label)));
    $slug = trim($slug, '_');
    if ($slug === '') {
        $slug = $entityType;
    }

    $stamp = $stamp !== null && $stamp !== '' ? $stamp : date('Ymd_His');
    $lane = strtolower(trim($lane));

    $extension = strtolower(trim($extension));
    if ($extension === '') {
        $extension = 'sql';
    }

    return $prefix . '_' . $entityType . '_' . $slug . ($lane !== '' ? '_' . $lane : '') . '_' . $stamp . '.' . $extension;
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

function spp_admin_backup_write_output_set(string $prefix, string $entityType, string $label, array $parts): array
{
    $stamp = date('Ymd_His');
    $files = array();

    foreach ($parts as $lane => $lines) {
        $lane = trim((string)$lane);
        if ($lane === '') {
            continue;
        }

        $extension = 'sql';
        if (is_array($lines) && array_key_exists('lines', $lines)) {
            $extension = (string)($lines['extension'] ?? 'sql');
            $lines = (array)$lines['lines'];
        }

        $filename = spp_admin_backup_build_filename($prefix, $entityType, $label, $lane, $stamp, $extension);
        $writeResult = spp_admin_backup_write_output($filename, (array)$lines);
        if (empty($writeResult['ok'])) {
            return $writeResult;
        }

        $path = (string)$writeResult['path'];
        $basename = spp_admin_backup_basename($path);
        $files[] = array(
            'lane' => $lane,
            'filename' => $basename,
            'path' => $path,
            'download_url' => spp_admin_backup_download_url($basename),
        );
    }

    return array(
        'ok' => true,
        'paths' => array_values(array_map(static function ($file) { return (string)$file['path']; }, $files)),
        'files' => $files,
    );
}

function spp_admin_backup_basename(string $path): string
{
    return basename(str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path));
}

function spp_admin_backup_download_url(string $filename): string
{
    $siteHref = '/';
    if (function_exists('spp_config_temp_string')) {
        $siteHref = (string)spp_config_temp_string('site_href', '/');
    }
    $siteHref = trim(str_replace('\\', '/', $siteHref));
    if ($siteHref === '' || strpos($siteHref, ':') !== false || $siteHref[0] !== '/') {
        $siteHref = '/';
    }
    if (substr($siteHref, -1) !== '/') {
        $siteHref .= '/';
    }

    return $siteHref . 'components/admin/admin.backup.download.php?file=' . rawurlencode($filename);
}

function spp_admin_backup_list_files(int $limit = 20): array
{
    $dir = spp_admin_backup_output_dir();
    if (!is_dir($dir)) {
        return array();
    }

    $items = array();
    foreach (array('sql', 'txt', 'bat', 'vbs') as $extension) {
        $matched = glob($dir . DIRECTORY_SEPARATOR . '*.' . $extension);
        if (is_array($matched)) {
            $items = array_merge($items, $matched);
        }
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
