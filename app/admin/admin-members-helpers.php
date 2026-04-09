<?php

require_once __DIR__ . '/../support/db-schema.php';

if (!function_exists('spp_admin_members_action_url')) {
    function spp_admin_members_action_url(array $params)
    {
        return spp_action_url('index.php', $params, 'admin_members');
    }
}

if (!function_exists('spp_admin_members_resolve_realm_id')) {
    function spp_admin_members_resolve_realm_id(array $realmDbMap, int $preferredRealmId = 0): int
    {
        if ($preferredRealmId > 0 && isset($realmDbMap[$preferredRealmId])) {
            return $preferredRealmId;
        }

        $candidate = (int)($_POST['character_realm_id'] ?? ($_GET['character_realm_id'] ?? 0));
        if ($candidate > 0 && isset($realmDbMap[$candidate])) {
            return $candidate;
        }

        if (!empty($GLOBALS['activeRealmId']) && isset($realmDbMap[(int)$GLOBALS['activeRealmId']])) {
            return (int)$GLOBALS['activeRealmId'];
        }

        return (int)spp_resolve_realm_id($realmDbMap);
    }
}

if (!function_exists('spp_ensure_website_account_row')) {
    function spp_ensure_website_account_row(PDO $pdo, $accountId)
    {
        $accountId = (int)$accountId;
        if ($accountId <= 0) {
            return;
        }

        $stmtEnsure = $pdo->prepare("
            INSERT INTO website_accounts (account_id)
            SELECT ?
            WHERE NOT EXISTS (
                SELECT 1 FROM website_accounts WHERE account_id = ?
            )
        ");
        $stmtEnsure->execute(array($accountId, $accountId));
    }
}

if (!function_exists('spp_admin_members_account_profile')) {
    function spp_admin_members_account_profile(PDO $pdo, int $accountId): ?array
    {
        if ($accountId <= 0) {
            return null;
        }

        $stmt = $pdo->prepare("
            SELECT *
            FROM account
            LEFT JOIN website_accounts ON account.id = website_accounts.account_id
            LEFT JOIN website_account_groups ON website_accounts.g_id = website_account_groups.g_id
            WHERE account.id = ?
            LIMIT 1
        ");
        $stmt->execute(array($accountId));
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        return $profile ?: null;
    }
}

if (!function_exists('spp_admin_members_realm_name')) {
    function spp_admin_members_realm_name(int $realmId): string
    {
        $name = function_exists('spp_get_armory_realm_name')
            ? (string)(spp_get_armory_realm_name($realmId) ?? '')
            : '';

        return $name !== '' ? $name : ('Realm ' . $realmId);
    }
}

if (!function_exists('spp_admin_members_sync_account_access')) {
    function spp_admin_members_sync_account_access(PDO $pdo, int $accountId, int $gmLevel, int $realmId): void
    {
        if ($accountId <= 0 || $realmId <= 0 || !spp_db_table_exists($pdo, 'account_access')) {
            return;
        }

        $stmtDelete = $pdo->prepare("DELETE FROM account_access WHERE id = ? AND RealmID = ?");
        $stmtDelete->execute(array($accountId, $realmId));

        if ($gmLevel <= 0) {
            return;
        }

        $stmtInsert = $pdo->prepare("INSERT INTO account_access (id, gmlevel, RealmID) VALUES (?, ?, ?)");
        $stmtInsert->execute(array($accountId, $gmLevel, $realmId));
    }
}

if (!function_exists('spp_admin_members_apply_game_account_updates')) {
    function spp_admin_members_apply_game_account_updates(PDO $pdo, int $accountId, int $realmId, array $profile): void
    {
        if ($accountId <= 0 || empty($profile)) {
            return;
        }

        $currentGmLevel = null;
        if (array_key_exists('gmlevel', $profile)) {
            $currentGmLevel = (int)$profile['gmlevel'];
        }

        if (spp_db_column_exists($pdo, 'account', 'current_realm') && !array_key_exists('current_realm', $profile) && $realmId > 0) {
            $profile['current_realm'] = $realmId;
        }

        $setClause = implode(',', array_map(function ($k) {
            return '`' . preg_replace('/[^a-zA-Z0-9_]/', '', $k) . '`=?';
        }, array_keys($profile)));
        $values = array_values($profile);
        $values[] = $accountId;

        $stmt = $pdo->prepare("UPDATE account SET $setClause WHERE id=? LIMIT 1");
        $stmt->execute($values);

        if ($currentGmLevel !== null) {
            spp_admin_members_sync_account_access($pdo, $accountId, $currentGmLevel, $realmId);
        }
    }
}

if (!function_exists('spp_admin_character_delete_tables')) {
    function spp_admin_character_delete_tables()
    {
        return array(
            'characters' => 'guid',
            'character_inventory' => 'guid',
            'character_action' => 'guid',
            'character_aura' => 'guid',
            'character_gifts' => 'guid',
            'character_homebind' => 'guid',
            'character_instance' => 'guid',
            'character_queststatus_daily' => 'guid',
            'character_kill' => 'guid',
            'character_pet' => 'owner',
            'character_queststatus' => 'guid',
            'character_reputation' => 'guid',
            'character_social' => 'guid',
            'character_spell' => 'guid',
            'character_spell_cooldown' => 'guid',
            'character_ticket' => 'guid',
            'character_tutorial' => 'guid',
            'corpse' => 'guid',
            'item_instance' => 'owner_guid',
            'petition' => 'ownerguid',
            'petition_sign' => 'ownerguid',
        );
    }
}

if (!function_exists('spp_admin_members_table_exists')) {
    function spp_admin_members_table_exists(PDO $pdo, string $tableName): bool
    {
        return spp_db_table_exists($pdo, $tableName);
    }
}

if (!function_exists('spp_admin_account_is_online')) {
    function spp_admin_account_is_online(PDO $realmPdo, $accountId)
    {
        try {
            $stmt = $realmPdo->prepare("SELECT online FROM account WHERE id=? LIMIT 1");
            $stmt->execute(array((int)$accountId));
            return (int)$stmt->fetchColumn() === 1;
        } catch (Throwable $e) {
            error_log('[admin.members] account online lookup unavailable: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('spp_admin_character_is_online')) {
    function spp_admin_character_is_online(PDO $charsPdo, $characterGuid, $accountId = 0)
    {
        if ((int)$accountId > 0) {
            $stmt = $charsPdo->prepare("SELECT online FROM characters WHERE guid=? AND account=? LIMIT 1");
            $stmt->execute(array((int)$characterGuid, (int)$accountId));
        } else {
            $stmt = $charsPdo->prepare("SELECT online FROM characters WHERE guid=? LIMIT 1");
            $stmt->execute(array((int)$characterGuid));
        }
        return (int)$stmt->fetchColumn() === 1;
    }
}

if (!function_exists('spp_admin_online_characters_for_account')) {
    function spp_admin_online_characters_for_account(PDO $charsPdo, $accountId)
    {
        $stmt = $charsPdo->prepare("
            SELECT guid, name
            FROM characters
            WHERE account=? AND online=1
            ORDER BY name ASC, guid ASC
        ");
        $stmt->execute(array((int)$accountId));
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: array();
    }
}

if (!function_exists('spp_admin_force_characters_offline')) {
    function spp_admin_force_characters_offline($realmId, array $characters, &$errorMessage = '')
    {
        if (empty($characters)) {
            $errorMessage = '';
            return true;
        }
        if (!function_exists('spp_mangos_soap_execute_command')) {
            $errorMessage = 'SOAP helper is unavailable.';
            return false;
        }

        $errors = array();
        $attemptedKick = false;
        foreach ($characters as $character) {
            $characterName = trim((string)($character['name'] ?? ''));
            if ($characterName === '') {
                continue;
            }
            $attemptedKick = true;
            $soapError = '';
            $soapResult = spp_mangos_soap_execute_command((int)$realmId, 'kick ' . $characterName, $soapError);
            if ($soapResult !== false) {
                continue;
            }
            if ($soapError !== '') {
                $errors[] = $characterName . ': ' . $soapError;
            }
        }

        if (!$attemptedKick) {
            $errorMessage = '';
            return true;
        }

        if (!empty($errors)) {
            $errorMessage = implode(' | ', array_unique($errors));
            return false;
        }

        $errorMessage = '';
        return true;
    }
}

if (!function_exists('spp_admin_members_account_fields')) {
    function spp_admin_members_account_fields($isSuperAdmin)
    {
        $allowed = array('expansion');
        if ($isSuperAdmin) {
            $allowed[] = 'gmlevel';
        }
        return spp_allowed_field_map($allowed);
    }
}

if (!function_exists('spp_admin_members_website_fields')) {
    function spp_admin_members_website_fields($allowThemeChange)
    {
        $allowed = array('g_id', 'hideprofile', 'signature');
        if ($allowThemeChange) {
            $allowed[] = 'theme';
        }
        return spp_allowed_field_map($allowed);
    }
}

if (!function_exists('spp_admin_members_highest_installed_expansion')) {
    function spp_admin_members_highest_installed_expansion(array $realmDbMap)
    {
        $highestExpansion = 0;
        foreach (array_keys($realmDbMap) as $realmId) {
            $realmId = (int)$realmId;
            if ($realmId >= 3) {
                $highestExpansion = max($highestExpansion, 2);
            } elseif ($realmId >= 2) {
                $highestExpansion = max($highestExpansion, 1);
            }
        }
        return $highestExpansion;
    }
}

if (!function_exists('spp_admin_members_expansion_label')) {
    function spp_admin_members_expansion_label($expansionId)
    {
        $expansionMap = array(
            0 => 'Classic',
            1 => 'TBC',
            2 => 'WotLK',
        );
        $expansionId = (int)$expansionId;
        return $expansionMap[$expansionId] ?? 'Classic';
    }
}

if (!function_exists('spp_admin_members_expansion_slug_to_id')) {
    function spp_admin_members_expansion_slug_to_id($slug)
    {
        $slug = strtolower(trim((string)$slug));
        if ($slug === 'wotlk') {
            return 2;
        }
        if ($slug === 'tbc') {
            return 1;
        }
        return 0;
    }
}
