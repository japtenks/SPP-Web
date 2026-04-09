<?php
if (INCLUDED !== true) {
    exit;
}

if (!function_exists('spp_admin_backup_safe_pdo')) {
    function spp_admin_backup_safe_pdo(string $type, int $realmId, array &$warnings = array(), bool $recordWarning = true): ?PDO
    {
        if ($realmId <= 0) {
            return null;
        }

        try {
            return spp_get_pdo($type, $realmId);
        } catch (Throwable $e) {
            if ($recordWarning) {
                $warnings[] = sprintf('Realm %d %s DB is unavailable: %s', $realmId, $type, $e->getMessage());
                error_log('[admin.backup] ' . end($warnings));
            }
            return null;
        }
    }
}

if (!function_exists('spp_admin_backup_route_is_healthy')) {
    function spp_admin_backup_route_is_healthy(array $routeOption): bool
    {
        $probeWarnings = array();
        $sourceRealmId = (int)($routeOption['source_realm_id'] ?? 0);
        $targetRealmId = (int)($routeOption['target_realm_id'] ?? 0);

        $sourceRealmdPdo = spp_admin_backup_safe_pdo('realmd', $sourceRealmId, $probeWarnings, false);
        $sourceCharsPdo = spp_admin_backup_safe_pdo('chars', $sourceRealmId, $probeWarnings, false);
        $targetRealmdPdo = spp_admin_backup_safe_pdo('realmd', $targetRealmId, $probeWarnings, false);
        $targetCharsPdo = spp_admin_backup_safe_pdo('chars', $targetRealmId, $probeWarnings, false);

        return $sourceRealmdPdo instanceof PDO
            && $sourceCharsPdo instanceof PDO
            && $targetRealmdPdo instanceof PDO
            && $targetCharsPdo instanceof PDO;
    }
}

function spp_admin_backup_build_view(array $realmDbMap, ?array $request = null): array
{
    $request = is_array($request) ? $request : $_POST;
    $viewWarnings = array();
    $realmOptions = spp_admin_backup_realm_options($realmDbMap);
    $xferRouteOptions = spp_admin_backup_xfer_route_options($realmOptions);
    $defaultSourceRealmId = !empty($realmOptions[0]['id']) ? (int)$realmOptions[0]['id'] : 0;
    $sourceRealmId = (int)($request['source_realm_id'] ?? $defaultSourceRealmId);
    if (!isset($realmDbMap[$sourceRealmId])) {
        $sourceRealmId = $defaultSourceRealmId;
    }

    $selectedXferRouteId = (string)($request['xfer_route'] ?? '');
    $selectedXferRoute = null;
    foreach ($xferRouteOptions as $routeOption) {
        if ((string)$routeOption['id'] === $selectedXferRouteId) {
            $selectedXferRoute = $routeOption;
            break;
        }
    }
    if ($selectedXferRoute === null && !empty($xferRouteOptions)) {
        foreach ($xferRouteOptions as $routeOption) {
            if (spp_admin_backup_route_is_healthy((array)$routeOption)) {
                $selectedXferRoute = $routeOption;
                $selectedXferRouteId = (string)$selectedXferRoute['id'];
                break;
            }
        }
        if ($selectedXferRoute === null && !empty($xferRouteOptions[0])) {
            $selectedXferRoute = $xferRouteOptions[0];
            $selectedXferRouteId = (string)$selectedXferRoute['id'];
        }
    }
    if ($selectedXferRoute !== null) {
        $sourceRealmId = (int)$selectedXferRoute['source_realm_id'];
        $targetRealmId = (int)$selectedXferRoute['target_realm_id'];
    } else {
        $targetRealmId = 0;
    }

    $backupEntityType = (string)($request['backup_entity_type'] ?? 'character');
    $xferEntityType = (string)($request['xfer_entity_type'] ?? 'character');
    $entityOptions = spp_admin_backup_entity_options();
    $xferEntityOptions = spp_admin_backup_route_entity_options($selectedXferRoute);
    if (!isset($entityOptions[$backupEntityType])) {
        $backupEntityType = 'character';
    }
    if (!isset($xferEntityOptions[$xferEntityType])) {
        $xferEntityType = spp_admin_backup_first_array_key($xferEntityOptions, 'character');
    }

    $sourceRealmdPdo = spp_admin_backup_safe_pdo('realmd', $sourceRealmId, $viewWarnings);
    $sourceCharsPdo = spp_admin_backup_safe_pdo('chars', $sourceRealmId, $viewWarnings);
    $targetRealmdPdo = spp_admin_backup_safe_pdo('realmd', $targetRealmId, $viewWarnings);
    $targetCharsPdo = spp_admin_backup_safe_pdo('chars', $targetRealmId, $viewWarnings);

    $sourceAccountOptions = $sourceRealmdPdo ? spp_admin_backup_fetch_accounts($sourceRealmdPdo, 'human') : array();
    $sourceBotOptions = $sourceRealmdPdo ? spp_admin_backup_fetch_accounts($sourceRealmdPdo, 'bot', false) : array();
    $selectedAccountId = (int)($request['source_account_id'] ?? 0);
    $hasSelectedAccount = false;
    foreach ($sourceAccountOptions as $accountOption) {
        if ((int)($accountOption['id'] ?? 0) === $selectedAccountId) {
            $hasSelectedAccount = true;
            break;
        }
    }
    if ((!$hasSelectedAccount || $selectedAccountId <= 0) && !empty($sourceAccountOptions[0]['id'])) {
        $selectedAccountId = (int)$sourceAccountOptions[0]['id'];
    }

    $selectedBotAccountId = (int)($request['source_bot_account_id'] ?? 0);
    $hasSelectedBotAccount = false;
    foreach ($sourceBotOptions as $accountOption) {
        if ((int)($accountOption['id'] ?? 0) === $selectedBotAccountId) {
            $hasSelectedBotAccount = true;
            break;
        }
    }
    if ((!$hasSelectedBotAccount || $selectedBotAccountId <= 0) && !empty($sourceBotOptions[0]['id'])) {
        $selectedBotAccountId = (int)$sourceBotOptions[0]['id'];
    }

    $sourceCharacterOptions = $sourceCharsPdo ? spp_admin_backup_fetch_characters($sourceCharsPdo, $selectedAccountId) : array();
    $sourceBotCharacterOptions = $sourceCharsPdo ? spp_admin_backup_fetch_characters($sourceCharsPdo, $selectedBotAccountId) : array();
    $selectedCharacterGuid = (int)($request['source_character_guid'] ?? 0);
    $hasSelectedCharacter = false;
    foreach ($sourceCharacterOptions as $characterOption) {
        if ((int)($characterOption['guid'] ?? 0) === $selectedCharacterGuid) {
            $hasSelectedCharacter = true;
            break;
        }
    }
    if ((!$hasSelectedCharacter || $selectedCharacterGuid <= 0) && !empty($sourceCharacterOptions[0]['guid'])) {
        $selectedCharacterGuid = (int)$sourceCharacterOptions[0]['guid'];
    }

    $sourceGuildOptions = $sourceCharsPdo ? spp_admin_backup_fetch_guilds($sourceCharsPdo) : array();
    $selectedGuildId = (int)($request['source_guild_id'] ?? 0);
    $hasSelectedGuild = false;
    foreach ($sourceGuildOptions as $guildOption) {
        if ((int)($guildOption['guildid'] ?? 0) === $selectedGuildId) {
            $hasSelectedGuild = true;
            break;
        }
    }
    if ((!$hasSelectedGuild || $selectedGuildId <= 0) && !empty($sourceGuildOptions[0]['guildid'])) {
        $selectedGuildId = (int)$sourceGuildOptions[0]['guildid'];
    }

    $targetAccountOptions = $targetRealmdPdo ? spp_admin_backup_fetch_accounts($targetRealmdPdo, 'human') : array();
    $targetAccountId = (int)($request['target_account_id'] ?? 0);
    $hasTargetAccount = false;
    foreach ($targetAccountOptions as $accountOption) {
        if ((int)($accountOption['id'] ?? 0) === $targetAccountId) {
            $hasTargetAccount = true;
            break;
        }
    }
    if ((!$hasTargetAccount || $targetAccountId <= 0) && !empty($targetAccountOptions[0]['id'])) {
        $targetAccountId = (int)$targetAccountOptions[0]['id'];
    }

    $selectedAccountRow = null;
    foreach ($sourceAccountOptions as $accountOption) {
        if ((int)$accountOption['id'] === $selectedAccountId) {
            $selectedAccountRow = $accountOption;
            break;
        }
    }

    $selectedBotAccountRow = null;
    foreach ($sourceBotOptions as $accountOption) {
        if ((int)$accountOption['id'] === $selectedBotAccountId) {
            $selectedBotAccountRow = $accountOption;
            break;
        }
    }

    $selectedCharacterRow = null;
    foreach ($sourceCharacterOptions as $characterOption) {
        if ((int)$characterOption['guid'] === $selectedCharacterGuid) {
            $selectedCharacterRow = $characterOption;
            break;
        }
    }

    $selectedGuildRow = null;
    foreach ($sourceGuildOptions as $guildOption) {
        if ((int)$guildOption['guildid'] === $selectedGuildId) {
            $selectedGuildRow = $guildOption;
            break;
        }
    }

    $selectedGuildSummary = ($sourceRealmdPdo && $sourceCharsPdo && $selectedGuildId > 0)
        ? spp_admin_backup_fetch_guild_summary($sourceRealmdPdo, $sourceCharsPdo, $selectedGuildId)
        : array(
            'member_count' => 0,
            'account_count' => 0,
            'bot_account_count' => 0,
            'human_account_count' => 0,
        );

    return array(
        'realm_options' => $realmOptions,
        'xfer_route_options' => $xferRouteOptions,
        'selected_xfer_route_id' => $selectedXferRouteId,
        'entity_options' => $entityOptions,
        'xfer_entity_options' => $xferEntityOptions,
        'source_realm_id' => $sourceRealmId,
        'target_realm_id' => $targetRealmId,
        'backup_entity_type' => $backupEntityType,
        'xfer_entity_type' => $xferEntityType,
        'selected_xfer_entity_type' => $xferEntityType,
        'selected_xfer_route' => $selectedXferRoute,
        'xfer_route_help' => function_exists('spp_admin_backup_route_help') ? spp_admin_backup_route_help($selectedXferRoute) : '',
        'source_account_options' => $sourceAccountOptions,
        'source_bot_options' => $sourceBotOptions,
        'source_character_options' => $sourceCharacterOptions,
        'source_bot_character_options' => $sourceBotCharacterOptions,
        'source_guild_options' => $sourceGuildOptions,
        'target_account_options' => $targetAccountOptions,
        'selected_account_id' => $selectedAccountId,
        'selected_bot_account_id' => $selectedBotAccountId,
        'selected_character_guid' => $selectedCharacterGuid,
        'selected_guild_id' => $selectedGuildId,
        'selected_target_account_id' => $targetAccountId,
        'selected_account_row' => $selectedAccountRow,
        'selected_bot_account_row' => $selectedBotAccountRow,
        'selected_character_row' => $selectedCharacterRow,
        'selected_guild_row' => $selectedGuildRow,
        'selected_guild_summary' => $selectedGuildSummary,
        'output_dir' => spp_admin_backup_output_dir(),
        'output_dir_writable' => spp_admin_backup_ensure_output_dir(),
        'recent_files' => spp_admin_backup_list_files(15),
        'target_realm_name' => $targetRealmId > 0 ? (string)(spp_get_armory_realm_name($targetRealmId) ?? ('Realm ' . $targetRealmId)) : '',
        'source_realm_name' => $sourceRealmId > 0 ? (string)(spp_get_armory_realm_name($sourceRealmId) ?? ('Realm ' . $sourceRealmId)) : '',
        'has_target_realm' => $targetRealmId > 0 && isset($realmDbMap[$targetRealmId]),
        'target_chars_pdo' => $targetCharsPdo,
        'warnings' => $viewWarnings,
    );
}
