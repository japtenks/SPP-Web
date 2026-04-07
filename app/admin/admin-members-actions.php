<?php

require_once __DIR__ . '/admin-members-account-actions.php';
require_once __DIR__ . '/admin-members-maintenance-actions.php';

if (!function_exists('spp_admin_members_handle_action')) {
    function spp_admin_members_handle_action(array $context)
    {
        $membersPdo = $context['members_pdo'];
        $membersCharsPdo = $context['members_chars_pdo'];
        $oldInactiveTime = (int)$context['old_inactive_time'];
        $deleteInactiveAccountsEnabled = !empty($context['delete_inactive_accounts_enabled']);
        $deleteInactiveCharactersEnabled = !empty($context['delete_inactive_characters_enabled']);
        $realmDbMap = $context['realm_db_map'];
        $user = $context['user'];

        if (!empty($_POST['search_member'])) {
            spp_require_csrf('admin_members');
            $searchString = trim((string)$_POST['search_member']);
            $stmt = $membersPdo->prepare("SELECT id FROM account WHERE username=?");
            $stmt->execute(array($searchString));
            $accountId = $stmt->fetchColumn();
            if ($accountId !== false && $accountId !== null && $accountId !== '') {
                redirect('index.php?n=admin&sub=members&id=' . (int)$accountId, 0);
            }
            output_message('alert', 'No results');
            return;
        }

        $action = (string)($_GET['action'] ?? '');
        $accountId = (int)($_GET['id'] ?? 0);
        $selectedRealmId = (int)($_POST['character_realm_id'] ?? ($_GET['character_realm_id'] ?? 0));
        if ($selectedRealmId <= 0 || empty($realmDbMap[$selectedRealmId])) {
            $selectedRealmId = (int)($GLOBALS['activeRealmId'] ?? spp_resolve_realm_id($realmDbMap));
        }

        if ($accountId > 0) {
            if ($action === '' || $action === '0') {
                return;
            }

            spp_admin_members_handle_account_action(array(
                'action' => $action,
                'account_id' => $accountId,
                'selected_realm_id' => $selectedRealmId,
                'members_pdo' => $membersPdo,
                'realm_db_map' => $realmDbMap,
                'user' => $user,
            ));

            return;
        }

        spp_admin_members_handle_maintenance_action(array(
            'action' => $action,
            'members_pdo' => $membersPdo,
            'members_chars_pdo' => $membersCharsPdo,
            'old_inactive_time' => $oldInactiveTime,
            'delete_inactive_accounts_enabled' => $deleteInactiveAccountsEnabled,
            'delete_inactive_characters_enabled' => $deleteInactiveCharactersEnabled,
            'realm_db_map' => $realmDbMap,
        ));
    }
}
