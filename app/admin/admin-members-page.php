<?php

require_once __DIR__ . '/admin-members-helpers.php';
require_once __DIR__ . '/admin-members-context.php';
require_once __DIR__ . '/admin-members-notices.php';
require_once __DIR__ . '/admin-members-actions.php';
require_once __DIR__ . '/admin-members-read.php';

if (!function_exists('spp_admin_members_load_page_state')) {
    function spp_admin_members_load_page_state(array $context): array
    {
        $realmDbMap = (array)($context['realm_db_map'] ?? array());
        $auth = $context['auth'] ?? null;
        $comLinks = (array)($context['com_links'] ?? array());
        $p = (int)($context['page'] ?? 1);
        $templateOptions = function_exists('spp_config_template_names')
            ? spp_config_template_names()
            : array();

        $actionContext = spp_admin_members_build_action_context($context);
        $membersPdo = $actionContext['members_pdo'];
        $membersCharsPdo = $actionContext['members_chars_pdo'];

        spp_admin_members_handle_action($actionContext);

        $state = spp_admin_members_build_base_state();
        $accountId = (int)$state['accountId'];

        if ($accountId > 0) {
            spp_admin_members_emit_detail_notices();
            $detailView = spp_admin_members_build_detail_view($membersPdo, $membersCharsPdo, $auth, $comLinks, $realmDbMap, $accountId, (int)$state['selectedToolRealmId']);
            $state = array_merge($state, $detailView);
            $state['admin_members_csrf_token'] = spp_csrf_token('admin_members');
        } else {
            $listView = spp_admin_members_build_list_view($membersPdo, $p, $realmDbMap);
            $state = array_merge($state, $listView);
            spp_admin_members_emit_list_notices();
        }

        $state['isSuperAdmin'] = (int)($context['user']['gmlevel'] ?? 0) === 3;
        $state['templateOptions'] = $templateOptions;

        return $state;
    }
}
