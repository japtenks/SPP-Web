<?php

require_once __DIR__ . '/../../components/admin/chartools/charconfig.php';
require_once __DIR__ . '/../../components/admin/chartools/add.php';
require_once __DIR__ . '/../../components/admin/chartools/functionstransfer.php';
require_once __DIR__ . '/../../components/admin/chartools/functionsrename.php';
require_once __DIR__ . '/../../components/admin/chartools/functionsrace.php';
require_once __DIR__ . '/../../components/admin/chartools/functionsgear.php';
require_once __DIR__ . '/../../components/admin/chartools/tabs.php';
require_once __DIR__ . '/admin-chartools-read.php';
require_once __DIR__ . '/admin-chartools-actions.php';

if (!function_exists('spp_admin_chartools_realm_options')) {
    function spp_admin_chartools_realm_options(array $dbs): array
    {
        $options = array();

        foreach ($dbs as $realmId => $realm) {
            $options[] = array(
                'id' => (int)$realmId,
                'name' => (string)($realm['name'] ?? ('Realm ' . (int)$realmId)),
            );
        }

        return $options;
    }
}

if (!function_exists('spp_admin_chartools_load_page_state')) {
    function spp_admin_chartools_load_page_state(array $args): array
    {
        $dbs = (array)($args['dbs'] ?? array());
        $charcfgPdo = $args['charcfg_pdo'] ?? null;
        if (!$charcfgPdo instanceof PDO) {
            throw new InvalidArgumentException('A valid chartools PDO is required.');
        }

        $state = spp_admin_chartools_build_state($dbs, $charcfgPdo);
        $rawDonationPackOptions = (array)($state['donationPackOptions'] ?? array());
        $selectedCharacterProfile = $state['selectedCharacterProfile'] ?? null;
        $selectedRealmId = (int)($state['selectedRealmId'] ?? 0);
        $donationPackOptions = chartools_build_delivery_options($rawDonationPackOptions, $selectedRealmId, $selectedCharacterProfile);
        $state['donationPackOptions'] = $donationPackOptions;

        $chartoolsMessages = array(
            'empty_field' => $args['messages']['empty_field'] ?? '',
            'character_1' => $args['messages']['character_1'] ?? '',
            'doesntexist' => $args['messages']['doesntexist'] ?? '',
            'alreadyexist' => $args['messages']['alreadyexist'] ?? '',
            'isonline' => $args['messages']['isonline'] ?? '',
            'renamesuccess' => $args['messages']['renamesuccess'] ?? '',
        );

        $actionState = spp_admin_chartools_handle_actions($state, $dbs, $chartoolsMessages);
        $selectedCharacterProfile = $actionState['selectedCharacterProfile'];
        $selectedCharacterClassId = (int)($selectedCharacterProfile['class'] ?? 0);
        $selectedCharacterRaceId = (int)($selectedCharacterProfile['race'] ?? 0);
        $selectedFullPackagePhaseId = trim((string)($_POST['full_package_phase'] ?? ''));

        $availableRaceOptions = !empty($selectedCharacterProfile)
            ? chartools_available_race_options($selectedCharacterClassId, $selectedCharacterRaceId)
            : array();
        $availableFullPackagePhases = chartools_build_full_package_phases();
        if ($selectedFullPackagePhaseId === '' && !empty($availableFullPackagePhases[0]['id'])) {
            $selectedFullPackagePhaseId = (string)$availableFullPackagePhases[0]['id'];
        }
        $selectedFullPackageRoleId = trim((string)($_POST['full_package_role'] ?? ''));
        $availableFullPackageRoles = chartools_build_full_package_roles($selectedRealmId, $selectedCharacterProfile, $selectedFullPackagePhaseId);
        if ($selectedFullPackageRoleId === '' && !empty($availableFullPackageRoles[0]['id'])) {
            $selectedFullPackageRoleId = (string)$availableFullPackageRoles[0]['id'];
        }
        if ($selectedFullPackageRoleId !== '') {
            $roleStillAvailable = false;
            foreach ($availableFullPackageRoles as $roleOption) {
                if ((string)$roleOption['id'] === $selectedFullPackageRoleId) {
                    $roleStillAvailable = true;
                    break;
                }
            }
            if (!$roleStillAvailable) {
                $selectedFullPackageRoleId = !empty($availableFullPackageRoles[0]['id']) ? (string)$availableFullPackageRoles[0]['id'] : '';
            }
        }
        $donationPackOptions = chartools_build_delivery_options($rawDonationPackOptions, $selectedRealmId, $selectedCharacterProfile);

        if (!isset($GLOBALS['pathway_info']) || !is_array($GLOBALS['pathway_info'])) {
            $GLOBALS['pathway_info'] = array();
        }
        $GLOBALS['pathway_info'][] = array('title' => 'Character Tools', 'link' => 'index.php?n=admin&sub=chartools');

        return array_merge($state, array(
            'chartoolsActionUrl' => 'index.php?n=admin&sub=chartools',
            'adminChartoolsCsrfToken' => spp_csrf_token('admin_chartools'),
            'realmOptions' => spp_admin_chartools_realm_options($dbs),
            'renameNewName' => trim((string)($_POST['newname'] ?? '')),
            'selectedRaceId' => (int)($_POST['newrace'] ?? 0),
            'selectedDonationPackId' => trim((string)($_POST['donation_pack_id'] ?? '')),
            'selectedCharacterProfile' => $selectedCharacterProfile,
            'selectedCharacterRaceLabel' => $selectedCharacterRaceId > 0 ? chartools_race_label($selectedCharacterRaceId) : '',
            'selectedCharacterClassLabel' => $selectedCharacterClassId > 0 ? chartools_playerbot_class_name($selectedCharacterClassId) : '',
            'selectedCharacterLevel' => (int)($selectedCharacterProfile['level'] ?? 0),
            'selectedRealmTargetLevelCap' => $selectedRealmId > 0 ? (int)chartools_playerbot_level_cap($selectedRealmId) : 0,
            'availableRaceOptions' => $availableRaceOptions,
            'availableFullPackagePhases' => $availableFullPackagePhases,
            'selectedFullPackagePhaseId' => $selectedFullPackagePhaseId,
            'selectedFullPackageRoleId' => $selectedFullPackageRoleId,
            'availableFullPackageRoles' => $availableFullPackageRoles,
            'donationPackOptions' => $donationPackOptions,
            'renameMessageHtml' => $actionState['renameMessageHtml'],
            'raceMessageHtml' => $actionState['raceMessageHtml'],
            'deliveryMessageHtml' => $actionState['deliveryMessageHtml'],
            'fullPackageMessageHtml' => $actionState['fullPackageMessageHtml'],
        ));
    }
}
