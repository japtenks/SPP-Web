<?php

if (!function_exists('spp_admin_members_handle_maintenance_action')) {
    function spp_admin_members_handle_maintenance_action(array $context): bool
    {
        $action = (string)($context['action'] ?? '');
        $membersPdo = $context['members_pdo'];
        $membersCharsPdo = $context['members_chars_pdo'];
        $oldInactiveTime = (int)($context['old_inactive_time'] ?? 0);
        $deleteInactiveAccountsEnabled = !empty($context['delete_inactive_accounts_enabled']);
        $deleteInactiveCharactersEnabled = !empty($context['delete_inactive_characters_enabled']);
        $realmDbMap = (array)($context['realm_db_map'] ?? array());

        if ($action === 'deleteinactive') {
            spp_require_csrf('admin_members');
            if (!$deleteInactiveAccountsEnabled) {
                output_message('alert', 'Inactive account deletion is currently disabled until this maintenance workflow is reviewed.');
                return true;
            }

            $curTimestamp = date('YmdHis', time() - $oldInactiveTime);
            $stmt = $membersPdo->prepare("
                SELECT account_id FROM website_accounts
                JOIN account ON account.id=website_accounts.account_id
                WHERE activation_code IS NOT NULL AND joindate < ?
            ");
            $stmt->execute([$curTimestamp]);
            $accountIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            if (!empty($accountIds)) {
                $placeholders = implode(',', array_fill(0, count($accountIds), '?'));
                $accountInts = array_map('intval', $accountIds);
                $stmt = $membersPdo->prepare("DELETE FROM account WHERE id IN($placeholders)");
                $stmt->execute($accountInts);
                $stmt = $membersPdo->prepare("DELETE FROM website_accounts WHERE account_id IN($placeholders)");
                $stmt->execute($accountInts);
            }
            redirect('index.php?n=admin&sub=members', 1);
            exit;
        }

        if ($action === 'normalizebotexpansion') {
            spp_require_csrf('admin_members');
            $maxInstalledExpansion = spp_admin_members_highest_installed_expansion($realmDbMap);
            $requestedExpansion = spp_admin_members_expansion_slug_to_id($_POST['switch_wow_type'] ?? 'classic');
            $targetExpansion = min($requestedExpansion, $maxInstalledExpansion);
            $stmt = $membersPdo->prepare("
                UPDATE account
                SET expansion=?
                WHERE LOWER(username) LIKE 'rndbot%'
                  AND expansion<>?
            ");
            $stmt->execute([$targetExpansion, $targetExpansion]);
            $updatedCount = (int)$stmt->rowCount();
            redirect('index.php?n=admin&sub=members&botexp=normalized&count=' . $updatedCount . '&to=' . $targetExpansion, 1);
            exit;
        }

        if ($action === 'deleteinactive_characters') {
            spp_require_csrf('admin_members');
            if (!$deleteInactiveCharactersEnabled) {
                output_message('alert', 'Inactive character deletion is currently disabled until this maintenance workflow is reviewed.');
                return true;
            }

            $deleteInDays = 90;
            $curTimestamp = date('Y-m-d H:i:s', mktime(date('H'), date('i'), date('s'), date('m'), date('d') - $deleteInDays, date('Y')));
            $stmt = $membersPdo->prepare("SELECT id FROM account LEFT JOIN website_accounts ON account.id=website_accounts.account_id WHERE ? >= last_login AND website_accounts.vip=0");
            $stmt->execute([$curTimestamp]);
            $accountIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            $characterGuids = array();
            if (count($accountIds)) {
                $acctPlaceholders = implode(',', array_fill(0, count($accountIds), '?'));
                $acctInts = array_map('intval', $accountIds);
                $stmt = $membersCharsPdo->prepare("SELECT guid FROM `characters` WHERE account IN ($acctPlaceholders)");
                $stmt->execute($acctInts);
                $characterGuids = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
            }
            if (count($characterGuids)) {
                $guidPlaceholders = implode(',', array_fill(0, count($characterGuids), '?'));
                $guidInts = array_map('intval', $characterGuids);
                foreach (spp_admin_character_delete_tables() as $table => $col) {
                    if (function_exists('spp_admin_members_table_exists') && !spp_admin_members_table_exists($membersCharsPdo, $table)) {
                        continue;
                    }
                    $stmt = $membersCharsPdo->prepare("DELETE FROM `$table` WHERE `$col` IN ($guidPlaceholders)");
                    $stmt->execute($guidInts);
                }
            }
            output_message('alert', 'Accounts checked: ' . count($accountIds) . '. Characters deleted: ' . count($characterGuids) . '.');
            return true;
        }

        return false;
    }
}
