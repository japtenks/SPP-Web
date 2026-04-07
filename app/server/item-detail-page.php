<?php

if (!function_exists('spp_item_detail_comment_redirect_url')) {
    function spp_item_detail_comment_redirect_url(array $overrides = []): string
    {
        $params = $_GET;
        unset($params['comment_posted']);

        foreach ($overrides as $key => $value) {
            if ($value === null || $value === '') {
                unset($params[$key]);
            } else {
                $params[$key] = $value;
            }
        }

        return 'index.php?' . http_build_query($params);
    }
}

if (!function_exists('spp_item_detail_post_redirect')) {
    function spp_item_detail_post_redirect(string $url): void
    {
        if (!headers_sent()) {
            header('Location: ' . $url);
            exit;
        }

        $safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        echo '<script>window.location.replace(' . json_encode($url, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) . ');</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . $safeUrl . '"></noscript>';
        echo '<p><a href="' . $safeUrl . '">Continue</a></p>';
        exit;
    }
}

if (!function_exists('spp_item_detail_build_comment_poster_state')) {
    function spp_item_detail_build_comment_poster_state(array $user, int $realmId): array
    {
        $commentPosterOptions = [];
        $commentPosterSelection = '';

        if ((int)($user['id'] ?? 0) <= 0) {
            return [
                'options' => $commentPosterOptions,
                'selection' => $commentPosterSelection,
            ];
        }

        $ownedCharacters = $GLOBALS['account_characters'] ?? [];
        if (is_array($ownedCharacters)) {
            foreach ($ownedCharacters as $character) {
                $characterGuid = (int)($character['guid'] ?? 0);
                $characterRealmId = (int)($character['realm_id'] ?? 0);
                $characterName = trim((string)($character['name'] ?? ''));
                if ($characterGuid <= 0 || $characterRealmId <= 0 || $characterName === '' || $characterRealmId !== $realmId) {
                    continue;
                }

                $optionKey = 'char:' . $characterRealmId . ':' . $characterGuid;
                $commentPosterOptions[$optionKey] = [
                    'type' => 'character',
                    'label' => $characterName . ' (' . (string)($character['realm_name'] ?? ('Realm ' . $characterRealmId)) . ', lvl ' . (int)($character['level'] ?? 0) . ')',
                    'poster' => $characterName,
                    'character_id' => $characterGuid,
                    'identity_id' => function_exists('spp_ensure_char_identity')
                        ? (int)spp_ensure_char_identity($characterRealmId, $characterGuid, (int)$user['id'], $characterName)
                        : null,
                ];
            }
        }

        $defaultPosterKey = '';
        if (!empty($user['character_id']) && !empty($user['cur_selected_realmd'])) {
            $selectedCharacterKey = 'char:' . (int)$user['cur_selected_realmd'] . ':' . (int)$user['character_id'];
            if (isset($commentPosterOptions[$selectedCharacterKey])) {
                $defaultPosterKey = $selectedCharacterKey;
            }
        }
        if ($defaultPosterKey === '' && !empty($commentPosterOptions)) {
            $commentOptionKeys = array_keys($commentPosterOptions);
            $defaultPosterKey = (string)reset($commentOptionKeys);
        }

        $commentPosterSelection = (string)($_POST['comment_poster'] ?? $defaultPosterKey);
        if ($commentPosterSelection === '' || !isset($commentPosterOptions[$commentPosterSelection])) {
            $commentPosterSelection = $defaultPosterKey;
        }

        return [
            'options' => $commentPosterOptions,
            'selection' => $commentPosterSelection,
        ];
    }
}

if (!function_exists('spp_item_detail_bootstrap_state')) {
    function spp_item_detail_bootstrap_state(array $config, array $user, $realmMap, array $realms = []): array
    {
        $realmId = (is_array($realmMap) && !empty($realmMap)) ? spp_resolve_realm_id($realmMap) : 1;
        $realmLabel = spp_get_armory_realm_name($realmId) ?? '';
        $itemId = isset($_GET['item']) ? (int)$_GET['item'] : 0;
        $requestType = strtolower(trim((string)($_GET['type'] ?? 'items')));
        $requestedSetName = trim((string)($_GET['set'] ?? ''));
        $isSetDetailMode = ($requestType === 'sets' && $requestedSetName !== '');
        $setSection = strtolower(trim((string)($_GET['set_section'] ?? ($_GET['section'] ?? 'misc'))));
        $setClass = trim((string)($_GET['set_class'] ?? ($_GET['class'] ?? '')));

        $legacyRealmName = '';
        if (!empty($realms)) {
            foreach ($realms as $name => $keys) {
                if ((int)($keys[2] ?? 0) === $realmId) {
                    $legacyRealmName = (string)$name;
                    break;
                }
            }
        }

        $posterState = spp_item_detail_build_comment_poster_state($user, $realmId);

        return [
            'realm_id' => $realmId,
            'realm_label' => $realmLabel,
            'item_id' => $itemId,
            'request_type' => $requestType,
            'requested_set_name' => $requestedSetName,
            'is_set_detail_mode' => $isSetDetailMode,
            'set_section' => $setSection,
            'set_class' => $setClass,
            'class_names' => [1 => 'Warrior', 2 => 'Paladin', 3 => 'Hunter', 4 => 'Rogue', 5 => 'Priest', 6 => 'Death Knight', 7 => 'Shaman', 8 => 'Mage', 9 => 'Warlock', 11 => 'Druid'],
            'race_names' => [1 => 'Human', 2 => 'Orc', 3 => 'Dwarf', 4 => 'Night Elf', 5 => 'Undead', 6 => 'Tauren', 7 => 'Gnome', 8 => 'Troll', 10 => 'Blood Elf', 11 => 'Draenei'],
            'alliance_races' => [1, 3, 4, 7, 11, 22, 25, 29],
            'item' => null,
            'item_set' => null,
            'random_properties' => [],
            'owners' => [],
            'upgrades' => [],
            'upgrade_presets' => spp_item_upgrade_presets(),
            'upgrade_mode' => strtolower(trim((string)($_GET['upgrade_mode'] ?? ''))),
            'upgrade_profile_id' => trim((string)($_GET['upgrade_profile'] ?? '')),
            'upgrade_weights_raw' => trim((string)($_GET['upgrade_weights'] ?? '')),
            'upgrade_manual_weights' => [],
            'upgrade_active_weights' => [],
            'upgrade_active_profile' => null,
            'upgrade_available_presets' => [],
            'upgrade_current_stats' => [],
            'upgrade_current_score' => null,
            'upgrade_notice' => '',
            'upgrade_fallback_url' => '',
            'upgrade_clear_url' => '',
            'comment_topic' => null,
            'comment_posts' => [],
            'comment_error' => '',
            'comment_success' => !empty($_GET['comment_posted']) ? 'Comment posted.' : '',
            'comment_poster_options' => $posterState['options'],
            'comment_poster_selection' => $posterState['selection'],
            'item_comment_forum_context' => function_exists('spp_forum_item_discussion_context')
                ? spp_forum_item_discussion_context()
                : ['realm_id' => 1, 'forum_id' => 0, 'forum_name' => '', 'cat_id' => 0, 'hidden' => 0],
            'set_detail' => null,
            'set_comment_forum_context' => function_exists('spp_forum_set_discussion_context')
                ? spp_forum_set_discussion_context()
                : (function_exists('spp_forum_item_discussion_context')
                    ? spp_forum_item_discussion_context()
                    : ['realm_id' => 1, 'forum_id' => 0, 'forum_name' => '', 'cat_id' => 0, 'hidden' => 0]),
            'comment_subject_label' => 'item',
            'comment_empty_copy' => 'No comments yet for this item.',
            'comment_login_copy' => 'Log in to join this item discussion.',
            'comment_no_poster_copy' => 'You need a character on this realm to comment in this item discussion.',
            'page_error' => '',
            'legacy_realm_name' => $legacyRealmName,
            'item_realm_options' => [],
            'item_realm_switch_params' => [],
        ];
    }
}
