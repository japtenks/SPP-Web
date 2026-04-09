<?php

if (!function_exists('spp_admin_members_build_action_context')) {
    function spp_admin_members_build_action_context(array $context): array
    {
        $realmDbMap = (array)($context['realm_db_map'] ?? array());
        $selectedRealmId = spp_admin_members_resolve_realm_id($realmDbMap);

        return array(
            'members_pdo' => spp_get_pdo('realmd', $selectedRealmId),
            'members_chars_pdo' => spp_get_pdo('chars', $selectedRealmId),
            'old_inactive_time' => 3600 * 24 * 7,
            'delete_inactive_accounts_enabled' => false,
            'delete_inactive_characters_enabled' => false,
            'realm_db_map' => $realmDbMap,
            'user' => (array)($context['user'] ?? array()),
            'selected_realm_id' => $selectedRealmId,
        );
    }
}

if (!function_exists('spp_admin_members_build_base_state')) {
    function spp_admin_members_build_base_state(): array
    {
        return array(
            'accountId' => (int)($_GET['id'] ?? 0),
            'selectedToolRealmId' => (int)($_POST['character_realm_id'] ?? ($_GET['character_realm_id'] ?? 0)),
            'currentCharFilter' => (string)($_GET['char'] ?? ''),
            'admin_members_csrf_token' => '',
        );
    }
}
