<?php

require_once __DIR__ . '/../../components/account/account.helpers.php';
require_once __DIR__ . '/account-manage-actions.php';

if (!function_exists('spp_account_manage_load_page_state')) {
    function spp_account_manage_load_page_state(array $args = array()): array
    {
        $user = $args['user'] ?? ($GLOBALS['user'] ?? array());
        $auth = $args['auth'] ?? ($GLOBALS['auth'] ?? null);
        $realmDbMap = $args['realm_map'] ?? ($GLOBALS['realmDbMap'] ?? array());
        $templateOptions = function_exists('spp_config_template_names')
            ? spp_config_template_names()
            : array();

        $state = array(
            '__stop' => false,
            'profile' => null,
            'accountCharacters' => array(),
            'renameCharacters' => array(),
            'manageRealmName' => '',
            'manage_csrf_token' => '',
            'backgroundPreferencesAvailable' => false,
            'backgroundModeOptions' => array(),
            'availableBackgroundImages' => array(),
            'hiddenForumPreferenceAvailable' => false,
            'canManageHiddenForums' => false,
        );

        $pathway_info = $GLOBALS['pathway_info'] ?? array();
        $pathway_info[] = array('title' => 'Edit Profile', 'link' => '');
        $GLOBALS['pathway_info'] = $pathway_info;

        if ((int)($user['id'] ?? 0) <= 0) {
            redirect('index.php?n=account&sub=login', 1);
            $state['__stop'] = true;
            return $state;
        }

        $currentRealmId = function_exists('spp_current_realm_id')
            ? spp_current_realm_id(is_array($realmDbMap) ? $realmDbMap : array())
            : (int)($_COOKIE['cur_selected_realmd'] ?? $_COOKIE['cur_selected_realm'] ?? spp_resolve_realm_id($realmDbMap));
        if (!isset($realmDbMap[$currentRealmId])) {
            $currentRealmId = function_exists('spp_current_realm_id')
                ? spp_current_realm_id(is_array($realmDbMap) ? $realmDbMap : array())
                : spp_resolve_realm_id($realmDbMap);
        }
        $managePdo = function_exists('spp_canonical_auth_pdo') ? spp_canonical_auth_pdo() : spp_get_pdo('realmd', 1);
        spp_ensure_website_account_row($managePdo, $user['id']);
        $manageCharPdo = spp_get_pdo('chars', $currentRealmId);

        $manageRealmName = spp_realm_display_name($currentRealmId, is_array($realmDbMap) ? $realmDbMap : null);

        if (isset($_GET['pwchange'])) {
            if ($_GET['pwchange'] === '1') {
                output_message('notice', '<b>Password changed successfully.</b>');
            } elseif ($_GET['pwchange'] === 'mismatch') {
                output_message('alert', '<b>New password confirmation does not match.</b>');
            } elseif ($_GET['pwchange'] === 'failed') {
                output_message('alert', '<b>Password change failed: SRP values were not saved.</b>');
            } elseif ($_GET['pwchange'] === 'short') {
                output_message('alert', '<b>Your new password must be at least 4 characters long.</b>');
            }
        }

        $action = (string)($_GET['action'] ?? '');
        if ($action !== '') {
            spp_account_manage_handle_action($action, array(
                'managePdo' => $managePdo,
                'manageCharPdo' => $manageCharPdo,
                'auth' => $auth,
                'user' => $user,
                'currentRealmId' => $currentRealmId,
            ));
            $state['__stop'] = true;
            return $state;
        }

        $profile = $auth->getprofile($user['id']);
        $profile['signature'] = str_replace('<br />', '', (string)($profile['signature'] ?? ''));
        $backgroundPreferencesAvailable = spp_website_accounts_has_columns(array('background_mode', 'background_image'));
        $hiddenForumPreferenceAvailable = spp_website_accounts_has_columns(array('show_hidden_forums'));
        $canManageHiddenForums = function_exists('spp_account_can_manage_hidden_forums')
            ? spp_account_can_manage_hidden_forums((array)$user, (array)$profile)
            : false;
        $backgroundModeOptions = spp_background_mode_options();
        $availableBackgroundImages = spp_background_image_catalog();
        $profile['background_mode'] = spp_normalize_background_mode($profile['background_mode'] ?? '', 'daily');
        $defaultBackgroundImage = (string)spp_array_first_key($availableBackgroundImages);
        $profile['background_image'] = !empty($profile['background_image']) && isset($availableBackgroundImages[$profile['background_image']])
            ? (string)$profile['background_image']
            : $defaultBackgroundImage;
        $profile['show_hidden_forums'] = !empty($profile['show_hidden_forums']) ? 1 : 0;

        $stmtChars = $manageCharPdo->prepare("SELECT guid, name, level, online FROM characters WHERE account=? ORDER BY name ASC");
        $stmtChars->execute([(int)$user['id']]);
        $renameCharacters = $stmtChars->fetchAll(PDO::FETCH_ASSOC);

        $accountCharacters = array();
        $ownedCharacters = $GLOBALS['account_characters'] ?? array();
        if (!empty($ownedCharacters) && is_array($ownedCharacters)) {
            foreach ($ownedCharacters as $character) {
                $websiteAccountId = (int)($character['website_account_id'] ?? $character['account'] ?? $user['id']);
                if ($websiteAccountId !== (int)$user['id']) {
                    continue;
                }
                $accountCharacters[] = array(
                    'guid' => (int)($character['guid'] ?? 0),
                    'name' => (string)($character['name'] ?? ''),
                    'level' => (int)($character['level'] ?? 0),
                    'online' => 0,
                    'realm_id' => (int)($character['realm_id'] ?? 0),
                    'realm_name' => (string)($character['realm_name'] ?? spp_realm_display_name((int)($character['realm_id'] ?? 0), is_array($realmDbMap) ? $realmDbMap : null)),
                );
            }
        }
        if (empty($accountCharacters)) {
            foreach ($renameCharacters as $character) {
                $accountCharacters[] = array(
                    'guid' => (int)($character['guid'] ?? 0),
                    'name' => (string)($character['name'] ?? ''),
                    'level' => (int)($character['level'] ?? 0),
                    'online' => (int)($character['online'] ?? 0),
                    'realm_id' => $currentRealmId,
                    'realm_name' => $manageRealmName,
                );
            }
        }

        $profile['character_signatures'] = array();
        $profile['signature_character_key'] = '';
        $profile['signature_character_name'] = '';
        $profile['selected_character_avatar_url'] = '';

        $availableCharacterKeys = array();
        foreach ($accountCharacters as $character) {
            $characterRealmId = (int)($character['realm_id'] ?? $currentRealmId);
            $characterKey = $characterRealmId . ':' . (int)$character['guid'];
            $availableCharacterKeys[$characterKey] = array(
                'guid' => (int)$character['guid'],
                'name' => (string)$character['name'],
                'realm_id' => $characterRealmId,
                'realm_name' => (string)($character['realm_name'] ?? spp_realm_display_name($characterRealmId, is_array($realmDbMap) ? $realmDbMap : null)),
            );
        }

        $requestedSignatureKey = trim((string)($_GET['sigchar'] ?? ''));
        if ($requestedSignatureKey === '' && !empty($profile['character_id'])) {
            $requestedSignatureRealmId = (int)($profile['character_realm_id'] ?? $currentRealmId);
            $requestedSignatureKey = $requestedSignatureRealmId . ':' . (int)$profile['character_id'];
        }
        if ($requestedSignatureKey === '' && !empty($accountCharacters[0]['guid'])) {
            $requestedSignatureKey = (int)($accountCharacters[0]['realm_id'] ?? $currentRealmId) . ':' . (int)$accountCharacters[0]['guid'];
        }
        if (!isset($availableCharacterKeys[$requestedSignatureKey])) {
            $requestedSignatureKey = '';
        }

        foreach ($accountCharacters as $character) {
            $characterGuid = (int)$character['guid'];
            $characterRealmId = (int)($character['realm_id'] ?? $currentRealmId);
            $characterKey = $characterRealmId . ':' . $characterGuid;
            $identityId = spp_ensure_char_identity($characterRealmId, $characterGuid, $user['id'], (string)$character['name']);
            $profile['character_signatures'][$characterKey] = array(
                'name' => (string)$character['name'],
                'realm_name' => (string)($character['realm_name'] ?? spp_realm_display_name($characterRealmId, is_array($realmDbMap) ? $realmDbMap : null)),
                'avatar_url' => spp_character_portrait_url($characterRealmId, $characterGuid, (int)$user['id']),
                'signature' => $identityId > 0 ? str_replace('<br />', '', spp_get_identity_signature($identityId)) : '',
            );
        }

        if ($requestedSignatureKey !== '') {
            $profile['signature_character_key'] = $requestedSignatureKey;
            $profile['signature_character_name'] = (string)($availableCharacterKeys[$requestedSignatureKey]['name'] ?? '');
            $profile['selected_character_avatar_url'] = (string)($profile['character_signatures'][$requestedSignatureKey]['avatar_url'] ?? '');
            $profile['signature'] = (string)($profile['character_signatures'][$requestedSignatureKey]['signature'] ?? $profile['signature']);
        } elseif (!empty($profile['character_signatures'])) {
            $firstSignatureKey = (string)spp_array_first_key($profile['character_signatures']);
            $profile['signature_character_key'] = $firstSignatureKey;
            $profile['selected_character_avatar_url'] = (string)($profile['character_signatures'][$firstSignatureKey]['avatar_url'] ?? '');
            $profile['signature'] = (string)($profile['character_signatures'][$firstSignatureKey]['signature'] ?? $profile['signature']);
        }

        $profile['avatar_fallback_url'] = '';
        if (empty($profile['avatar'])) {
            $profile['avatar_fallback_url'] = spp_account_avatar_fallback_url($manageCharPdo, $profile, $accountCharacters);
        }

        return array(
            '__stop' => false,
            'profile' => $profile,
            'accountCharacters' => $accountCharacters,
            'renameCharacters' => $renameCharacters,
            'manageRealmName' => $manageRealmName,
            'manage_csrf_token' => spp_csrf_token('account_manage'),
            'backgroundPreferencesAvailable' => $backgroundPreferencesAvailable,
            'backgroundModeOptions' => $backgroundModeOptions,
            'availableBackgroundImages' => $availableBackgroundImages,
            'hiddenForumPreferenceAvailable' => $hiddenForumPreferenceAvailable,
            'canManageHiddenForums' => $canManageHiddenForums,
            'templateOptions' => $templateOptions,
        );
    }
}
