<?php

require_once __DIR__ . '/../support/db-schema.php';
require_once __DIR__ . '/../../components/admin/admin.realms.helpers.php';
require_once __DIR__ . '/../../components/admin/admin.operations.helpers.php';
require_once __DIR__ . '/admin-realms-actions.php';
require_once __DIR__ . '/admin-realms-read.php';

if (!function_exists('spp_admin_realms_load_page_state')) {
    function spp_admin_realms_load_page_state(array $args = array()): array
    {
        $realmDbMap = (array)($args['realm_db_map'] ?? ($GLOBALS['realmDbMap'] ?? array()));
        $configuredRealmDbMap = (array)($GLOBALS['allConfiguredRealmDbMap'] ?? $realmDbMap);
        $realmsPdo = spp_website_settings_pdo();

        $actionState = spp_admin_realms_handle_action($realmsPdo, $configuredRealmDbMap);
        $runtimeView = spp_admin_realms_runtime_state($realmsPdo, $configuredRealmDbMap);
        $runtimeSettings = (array)($runtimeView['runtime_settings'] ?? array());
        $runtimeRealmOptions = (array)($runtimeView['runtime_realm_options'] ?? array());
        $scanRealmMap = $runtimeRealmOptions;
        $enabledRealmIds = array_values(array_map('intval', (array)($runtimeSettings['enabled_realm_ids'] ?? array())));
        if (!empty($enabledRealmIds)) {
            $enabledLookup = array_fill_keys($enabledRealmIds, true);
            $scanRealmMap = array_intersect_key($runtimeRealmOptions, $enabledLookup);
            if (empty($scanRealmMap)) {
                $scanRealmMap = $runtimeRealmOptions;
            }
        }
        $previousRealmDbMap = $GLOBALS['realmDbMap'] ?? array();
        $GLOBALS['realmDbMap'] = $scanRealmMap;
        $view = spp_admin_realms_build_view($realmsPdo, $scanRealmMap);
        $GLOBALS['realmDbMap'] = $previousRealmDbMap;
        $runtimeForm = !empty($actionState['runtime_form'])
            ? $actionState['runtime_form']
            : array(
                'multirealm' => (int)($runtimeSettings['multirealm'] ?? 0),
                'selection_mode' => (string)($runtimeSettings['selection_mode'] ?? 'manual'),
                'default_realm_id' => (int)($runtimeSettings['default_realm_id'] ?? 0),
                'enabled_realm_ids' => array_values((array)($runtimeSettings['enabled_realm_ids'] ?? array())),
            );

        return array_merge($view, array(
            'realm_type_def' => spp_admin_realms_type_definitions(),
            'realm_timezone_def' => spp_admin_realms_timezone_definitions(),
            'realm_column_labels' => spp_admin_realms_column_labels(),
            'realm_operations_href' => 'index.php?n=admin&sub=operations',
            'admin_realms_csrf_token' => spp_csrf_token('admin_realms'),
            'runtime_settings' => $runtimeSettings,
            'runtime_realm_options' => $runtimeRealmOptions,
            'runtime_selection_modes' => (array)($runtimeView['runtime_selection_modes'] ?? array()),
            'runtime_form' => $runtimeForm,
            'runtime_errors' => (array)($actionState['runtime_errors'] ?? array()),
            'runtime_catalog' => (array)($runtimeView['runtime_catalog'] ?? array()),
            'runtime_warnings' => (array)($runtimeView['runtime_warnings'] ?? array()),
            'slot_form' => (array)(
                $actionState['slot_form']
                ?? spp_admin_realms_preferred_slot_form($runtimeRealmOptions, (array)($runtimeView['runtime_catalog'] ?? array()))
            ),
            'slot_errors' => (array)($actionState['slot_errors'] ?? array()),
        ));
    }
}
