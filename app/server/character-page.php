<?php

require_once dirname(__DIR__, 2) . '/config/config-protected.php';
require_once dirname(__DIR__, 2) . '/app/support/db-schema.php';
require_once dirname(__DIR__, 2) . '/components/admin/admin.playerbots.helpers.php';
require_once dirname(__DIR__, 2) . '/app/server/character-helpers.php';
require_once dirname(__DIR__, 2) . '/app/server/character-profile-data.php';
require_once dirname(__DIR__, 2) . '/app/server/character-content-data.php';
require_once dirname(__DIR__, 2) . '/app/server/character-advancement-data.php';
require_once dirname(__DIR__, 2) . '/app/server/character-achievements-data.php';
require_once dirname(__DIR__, 2) . '/app/server/character-activity-admin-data.php';

if (!function_exists('spp_character_bootstrap_state')) {
    function spp_character_bootstrap_state(array $args = array()): array
    {
        $realmMap = $args['realm_map'] ?? null;
        $get = (array)($args['get'] ?? $_GET);
        $realmId = (is_array($realmMap) && !empty($realmMap)) ? spp_resolve_realm_id($realmMap) : 1;
        $tab = strtolower(trim((string)($get['tab'] ?? 'overview')));

        return array(
            'realm_id' => $realmId,
            'tab' => $tab,
            'tabs' => array('overview', 'talents', 'reputation', 'skills', 'professions', 'quest log', 'achievements', 'social'),
            'can_manage_bot_personality' => false,
            'character_is_bot' => false,
            'character_personality_csrf_token' => '',
            'class_names' => array(1 => 'Warrior', 2 => 'Paladin', 3 => 'Hunter', 4 => 'Rogue', 5 => 'Priest', 6 => 'Death Knight', 7 => 'Shaman', 8 => 'Mage', 9 => 'Warlock', 11 => 'Druid'),
            'race_names' => array(1 => 'Human', 2 => 'Orc', 3 => 'Dwarf', 4 => 'Night Elf', 5 => 'Undead', 6 => 'Tauren', 7 => 'Gnome', 8 => 'Troll', 10 => 'Blood Elf', 11 => 'Draenei'),
            'slot_names' => array(0 => 'Head', 1 => 'Neck', 2 => 'Shoulder', 3 => 'Shirt', 4 => 'Chest', 5 => 'Waist', 6 => 'Legs', 7 => 'Feet', 8 => 'Wrist', 9 => 'Hands', 10 => 'Finger 1', 11 => 'Finger 2', 12 => 'Trinket 1', 13 => 'Trinket 2', 14 => 'Back', 15 => 'Main Hand', 16 => 'Off Hand', 17 => 'Ranged', 18 => 'Tabard'),
            'character_name' => trim((string)($get['character'] ?? '')),
            'character_guid' => isset($get['guid']) ? (int)$get['guid'] : 0,
            'page_error' => '',
            'character' => null,
            'stats' => array(),
            'equipment' => array(),
            'talent_tabs' => array(),
            'reputations' => array(),
            'reputation_sections' => array(),
            'skills_by_category' => array(),
            'professions_by_category' => array(),
            'profession_recipes_by_skill_id' => array(),
            'known_character_spells' => array(),
            'achievement_summary' => array('supported' => false, 'count' => 0, 'points' => 0, 'recent' => array(), 'groups' => array()),
            'recent_gear' => array(),
            'active_quest_log' => array(),
            'completed_quest_history' => array(),
            'completed_quest_total' => 0,
            'last_instance' => '',
            'last_instance_date' => 0,
            'current_map_name' => 'Unknown zone',
            'display_location' => 'Unknown zone',
            'combat_highlights' => array(),
            'faction_icon' => '',
            'gear_progression' => array(
                'supported' => false,
                'has_history' => false,
                'rows' => array(),
                'chart' => null,
                'latest_ilvl' => null,
                'first_ilvl' => null,
                'peak_ilvl' => null,
                'delta_ilvl' => null,
                'latest_equipped_count' => null,
                'latest_level' => null,
                'latest_online' => null,
                'snapshot_count' => 0,
                'first_snapshot_label' => '',
                'latest_snapshot_label' => '',
            ),
            'forum_social' => array(
                'identity_id' => 0,
                'account_id' => 0,
                'account_username' => '',
                'account_link' => '',
                'signature' => '',
                'rendered_signature' => '',
                'posts' => 0,
                'topics' => 0,
                'last_post' => 0,
                'last_topic' => 0,
                'recent_posts' => array(),
                'recent_topics' => array(),
                'rotation_online_sessions' => 0,
                'rotation_offline_sessions' => 0,
                'rotation_full_cycles' => 0,
                'rotation_last_online' => 0,
                'rotation_online_seconds' => 0,
                'rotation_offline_seconds' => 0,
                'rotation_online_avg_seconds' => null,
                'rotation_offline_avg_seconds' => null,
                'rotation_current_span_seconds' => null,
                'rotation_last_seen' => '',
                'rotation_last_change' => '',
                'rotation_tracked' => false,
            ),
            'bot_personality_text' => '',
            'bot_signature_text' => '',
            'character_admin_feedback' => '',
            'character_admin_error' => '',
            'bot_strategy_profiles' => spp_admin_playerbots_bot_strategy_profiles(),
            'strategy_builder_options' => array(
                'co' => array('dps', 'dps assist', 'dps aoe', 'tank', 'tank assist', 'threat', 'boost', 'offheal', 'cast time', 'custom::say', 'attack tagged', 'duel', 'pvp', 'avoid mobs'),
                'nc' => array('follow', 'loot', 'delayed roll', 'food', 'conserve mana', 'quest', 'grind', 'wander', 'gather', 'consumables', 'rpg', 'rpg guild', 'rpg vendor', 'rpg maintenance', 'rpg craft', 'rpg explore', 'rpg bg', 'tfish', 'duel', 'free', 'roll', 'custom::say'),
                'dead' => array('auto release', 'flee', 'corpse', 'return', 'delay'),
                'react' => array('preheal', 'flee', 'avoid aoe', 'pvp'),
            ),
            'character_strategy_state' => array(
                'values' => array_fill_keys(spp_admin_playerbots_strategy_keys(), ''),
                'consistent' => true,
                'member_count' => 0,
                'profile_key' => 'custom',
                'mixed_count' => 0,
            ),
            'personality_saved' => (int)($get['personality_saved'] ?? 0) === 1,
            'personality_mode' => trim((string)($get['personality_mode'] ?? '')),
            'signature_saved' => (int)($get['signature_saved'] ?? 0) === 1,
            'bot_strategy_saved' => (int)($get['bot_strategy_saved'] ?? 0) === 1,
            'bot_strategy_mode' => trim((string)($get['bot_strategy_mode'] ?? '')),
        );
    }
}

if (!function_exists('spp_character_talent_embed_markup')) {
    function spp_character_talent_embed_markup(int $realmId, string $characterName): string
    {
        if ($realmId <= 0 || trim($characterName) === '') {
            return '';
        }

        $siteRoot = dirname(__DIR__, 2);
        ob_start();
        $__savedGet = $_GET;
        $_GET['realm'] = (string)$realmId;
        $_GET['character'] = $characterName;
        $_GET['mode'] = 'profile';
        $_GET['embed'] = '1';
        unset($_GET['class']);
        include($siteRoot . '/templates/offlike/server/server.talents.php');
        $_GET = $__savedGet;
        return (string)ob_get_clean();
    }
}

if (!function_exists('spp_character_load_page_state')) {
    function spp_character_load_page_state(array $args): array
    {
        $realmMap = $args['realm_map'] ?? ($GLOBALS['realmDbMap'] ?? array());
        $user = (array)($args['user'] ?? ($GLOBALS['user'] ?? array()));
        $get = (array)($args['get'] ?? $_GET);
        $post = (array)($args['post'] ?? $_POST);
        $serverMethod = strtoupper((string)($args['server_method'] ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET')));

        $pageState = spp_character_bootstrap_state(array(
            'realm_map' => is_array($realmMap) ? $realmMap : array(),
            'get' => $get,
        ));

        $realmId = (int)($pageState['realm_id'] ?? 1);
        $characterName = (string)($pageState['character_name'] ?? '');
        $characterGuid = (int)($pageState['character_guid'] ?? 0);
        $tabs = (array)($pageState['tabs'] ?? array());
        $tab = (string)($pageState['tab'] ?? 'overview');

        if (!is_array($realmMap) || !isset($realmMap[$realmId])) {
            $pageState['page_error'] = 'The requested realm is unavailable.';
        } elseif ($characterName === '' && $characterGuid <= 0) {
            $pageState['page_error'] = 'No character was selected.';
        } else {
            $profileState = spp_character_load_profile_state(array(
                'realm_id' => $realmId,
                'character_name' => $characterName,
                'character_guid' => $characterGuid,
                'tabs' => $tabs,
                'tab' => $tab,
                'user' => $user,
                'gear_progression' => $pageState['gear_progression'] ?? array(),
                'forum_social' => $pageState['forum_social'] ?? array(),
                'bot_signature_text' => $pageState['bot_signature_text'] ?? '',
                'character_is_bot' => $pageState['character_is_bot'] ?? false,
                'can_manage_bot_personality' => $pageState['can_manage_bot_personality'] ?? false,
                'character_personality_csrf_token' => $pageState['character_personality_csrf_token'] ?? '',
            ));

            $pageState['page_error'] = (string)($profileState['page_error'] ?? '');
            $pageState['character'] = $profileState['character'] ?? null;
            $pageState['character_guid'] = (int)($profileState['character_guid'] ?? $characterGuid);
            $pageState['character_name'] = (string)($profileState['character_name'] ?? $characterName);
            $pageState['gear_progression'] = $profileState['gear_progression'] ?? ($pageState['gear_progression'] ?? array());
            $pageState['forum_social'] = $profileState['forum_social'] ?? ($pageState['forum_social'] ?? array());
            $pageState['bot_signature_text'] = (string)($profileState['bot_signature_text'] ?? ($pageState['bot_signature_text'] ?? ''));
            $pageState['character_is_bot'] = !empty($profileState['character_is_bot']);
            $pageState['can_manage_bot_personality'] = !empty($profileState['can_manage_bot_personality']);
            $pageState['character_personality_csrf_token'] = (string)($profileState['character_personality_csrf_token'] ?? ($pageState['character_personality_csrf_token'] ?? ''));
            $pageState['tabs'] = $profileState['tabs'] ?? $tabs;
            $pageState['tab'] = (string)($profileState['tab'] ?? $tab);

            $charsPdo = $profileState['chars_pdo'] ?? null;
            $worldPdo = $profileState['world_pdo'] ?? null;
            $realmdPdo = $profileState['realmd_pdo'] ?? null;
            $armoryPdo = $profileState['armory_pdo'] ?? null;
            $pageState['chars_pdo'] = $charsPdo;
            $pageState['world_pdo'] = $worldPdo;
            $pageState['realmd_pdo'] = $realmdPdo;
            $pageState['armory_pdo'] = $armoryPdo;

            if (
                $pageState['character']
                && $pageState['page_error'] === ''
                && $charsPdo instanceof PDO
                && $worldPdo instanceof PDO
                && $armoryPdo instanceof PDO
            ) {
                $coreContentState = spp_character_load_core_content_state(array(
                    'character_guid' => $pageState['character_guid'],
                    'character' => $pageState['character'],
                    'chars_pdo' => $charsPdo,
                    'world_pdo' => $worldPdo,
                    'armory_pdo' => $armoryPdo,
                    'slot_names' => $pageState['slot_names'] ?? array(),
                ));
                $pageState['stats'] = $coreContentState['stats'] ?? ($pageState['stats'] ?? array());
                $pageState['equipment'] = $coreContentState['equipment'] ?? ($pageState['equipment'] ?? array());
                $pageState['active_quest_log'] = $coreContentState['active_quest_log'] ?? ($pageState['active_quest_log'] ?? array());
                $pageState['completed_quest_history'] = $coreContentState['completed_quest_history'] ?? ($pageState['completed_quest_history'] ?? array());
                $pageState['completed_quest_total'] = (int)($coreContentState['completed_quest_total'] ?? ($pageState['completed_quest_total'] ?? 0));

                $advancementState = spp_character_load_advancement_state(array(
                    'character_guid' => $pageState['character_guid'],
                    'character' => $pageState['character'],
                    'chars_pdo' => $charsPdo,
                    'world_pdo' => $worldPdo,
                    'armory_pdo' => $armoryPdo,
                    'talent_tabs' => $pageState['talent_tabs'] ?? array(),
                    'reputations' => $pageState['reputations'] ?? array(),
                    'reputation_sections' => $pageState['reputation_sections'] ?? array(),
                    'skills_by_category' => $pageState['skills_by_category'] ?? array(),
                    'professions_by_category' => $pageState['professions_by_category'] ?? array(),
                    'profession_recipes_by_skill_id' => $pageState['profession_recipes_by_skill_id'] ?? array(),
                    'known_character_spells' => $pageState['known_character_spells'] ?? array(),
                ));
                $pageState['talent_tabs'] = $advancementState['talent_tabs'] ?? ($pageState['talent_tabs'] ?? array());
                $pageState['reputations'] = $advancementState['reputations'] ?? ($pageState['reputations'] ?? array());
                $pageState['reputation_sections'] = $advancementState['reputation_sections'] ?? ($pageState['reputation_sections'] ?? array());
                $pageState['skills_by_category'] = $advancementState['skills_by_category'] ?? ($pageState['skills_by_category'] ?? array());
                $pageState['professions_by_category'] = $advancementState['professions_by_category'] ?? ($pageState['professions_by_category'] ?? array());
                $pageState['profession_recipes_by_skill_id'] = $advancementState['profession_recipes_by_skill_id'] ?? ($pageState['profession_recipes_by_skill_id'] ?? array());
                $pageState['known_character_spells'] = $advancementState['known_character_spells'] ?? ($pageState['known_character_spells'] ?? array());

                $achievementState = spp_character_load_achievement_state(array(
                    'character_guid' => $pageState['character_guid'],
                    'chars_pdo' => $charsPdo,
                    'world_pdo' => $worldPdo,
                    'armory_pdo' => $armoryPdo,
                    'achievement_summary' => $pageState['achievement_summary'] ?? array(),
                ));
                $pageState['achievement_summary'] = $achievementState['achievement_summary'] ?? ($pageState['achievement_summary'] ?? array());

                $activityAdminState = spp_character_load_activity_admin_state(array(
                    'realm_id' => $realmId,
                    'character_guid' => $pageState['character_guid'],
                    'character_name' => $pageState['character_name'],
                    'character' => $pageState['character'],
                    'chars_pdo' => $charsPdo,
                    'world_pdo' => $worldPdo,
                    'armory_pdo' => $armoryPdo,
                    'forum_social' => $pageState['forum_social'] ?? array(),
                    'recent_gear' => $pageState['recent_gear'] ?? array(),
                    'last_instance' => $pageState['last_instance'] ?? '',
                    'last_instance_date' => $pageState['last_instance_date'] ?? 0,
                    'can_manage_bot_personality' => $pageState['can_manage_bot_personality'] ?? false,
                    'bot_personality_text' => $pageState['bot_personality_text'] ?? '',
                    'bot_signature_text' => $pageState['bot_signature_text'] ?? '',
                    'character_admin_feedback' => $pageState['character_admin_feedback'] ?? '',
                    'character_admin_error' => $pageState['character_admin_error'] ?? '',
                    'character_strategy_state' => $pageState['character_strategy_state'] ?? array(),
                    'post' => $post,
                    'server_method' => $serverMethod,
                ));
                $pageState['recent_gear'] = $activityAdminState['recent_gear'] ?? ($pageState['recent_gear'] ?? array());
                $pageState['last_instance'] = (string)($activityAdminState['last_instance'] ?? ($pageState['last_instance'] ?? ''));
                $pageState['last_instance_date'] = (int)($activityAdminState['last_instance_date'] ?? ($pageState['last_instance_date'] ?? 0));
                $pageState['bot_personality_text'] = (string)($activityAdminState['bot_personality_text'] ?? ($pageState['bot_personality_text'] ?? ''));
                $pageState['bot_signature_text'] = (string)($activityAdminState['bot_signature_text'] ?? ($pageState['bot_signature_text'] ?? ''));
                $pageState['forum_social'] = $activityAdminState['forum_social'] ?? ($pageState['forum_social'] ?? array());
                $pageState['character_admin_feedback'] = (string)($activityAdminState['character_admin_feedback'] ?? ($pageState['character_admin_feedback'] ?? ''));
                $pageState['character_admin_error'] = (string)($activityAdminState['character_admin_error'] ?? ($pageState['character_admin_error'] ?? ''));
                $pageState['character_strategy_state'] = $activityAdminState['character_strategy_state'] ?? ($pageState['character_strategy_state'] ?? array());
            }

            $character = $pageState['character'];
            $currentMapName = 'Unknown zone';
            $displayLocation = 'Unknown location';
            if (is_array($character)) {
                $zoneName = isset($character['zone']) && isset($GLOBALS['MANG']) && $GLOBALS['MANG'] instanceof Mangos ? $GLOBALS['MANG']->get_zone_name((int)$character['zone']) : 'Unknown zone';
                $currentMapName = isset($character['map']) && isset($GLOBALS['MANG']) && $GLOBALS['MANG'] instanceof Mangos ? $GLOBALS['MANG']->get_zone_name((int)$character['map']) : 'Unknown zone';
                $normalizedZoneName = $zoneName !== 'Unknown zone' ? trim((string)$zoneName) : '';
                $normalizedMapName = $currentMapName !== 'Unknown zone' ? trim((string)$currentMapName) : '';
                $continentNames = array('Azeroth', 'Eastern Kingdoms', 'Kalimdor', 'Outland', 'Northrend');
                if ($normalizedZoneName !== '' && $normalizedMapName !== '' && strcasecmp($normalizedZoneName, $normalizedMapName) !== 0 && !in_array($normalizedMapName, $continentNames, true)) {
                    $displayLocation = $normalizedZoneName . ', ' . $normalizedMapName;
                } elseif ($normalizedZoneName !== '') {
                    $displayLocation = $normalizedZoneName;
                } elseif ($normalizedMapName !== '') {
                    $displayLocation = $normalizedMapName;
                }
            }

            $pageState['realm_label'] = spp_get_armory_realm_name($realmId) ?? '';
            $pageState['current_map_name'] = $currentMapName;
            $pageState['display_location'] = $displayLocation;
            if (($pageState['last_instance'] ?? '') === '' && $currentMapName !== 'Unknown zone' && strpos($currentMapName, ':') !== false) {
                $pageState['last_instance'] = $currentMapName;
            }

            if (empty($pageState['recent_gear']) && !empty($pageState['equipment'])) {
                foreach (array_slice(array_values((array)$pageState['equipment']), 0, 5) as $fallbackItem) {
                    $pageState['recent_gear'][] = array(
                        'entry' => (int)($fallbackItem['entry'] ?? 0),
                        'name' => (string)($fallbackItem['name'] ?? ''),
                        'quality' => (int)($fallbackItem['quality'] ?? 0),
                        'item_level' => (int)($fallbackItem['item_level'] ?? 0),
                        'icon' => (string)($fallbackItem['icon'] ?? ''),
                        'date' => 0,
                    );
                }
            }
        }

        $pageState['character_url'] = 'index.php?n=server&sub=character&realm=' . (int)$realmId . '&character=' . urlencode((string)($pageState['character_name'] ?? ''));
        $pageState['talent_calculator_url'] = 'index.php?n=server&sub=talents&realm=' . (int)$realmId . '&character=' . urlencode((string)($pageState['character_name'] ?? ''));
        $pageState['talent_embed_markup'] = ($pageState['tab'] ?? '') === 'talents'
            ? spp_character_talent_embed_markup((int)$realmId, (string)($pageState['character_name'] ?? ''))
            : '';

        return array_merge($pageState, array(
            'realmId' => (int)($pageState['realm_id'] ?? 1),
            'tab' => (string)($pageState['tab'] ?? 'overview'),
            'tabs' => (array)($pageState['tabs'] ?? array()),
            'canManageBotPersonality' => !empty($pageState['can_manage_bot_personality']),
            'characterIsBot' => !empty($pageState['character_is_bot']),
            'characterPersonalityCsrfToken' => (string)($pageState['character_personality_csrf_token'] ?? ''),
            'classNames' => (array)($pageState['class_names'] ?? array()),
            'raceNames' => (array)($pageState['race_names'] ?? array()),
            'slotNames' => (array)($pageState['slot_names'] ?? array()),
            'characterName' => (string)($pageState['character_name'] ?? ''),
            'characterGuid' => (int)($pageState['character_guid'] ?? 0),
            'pageError' => (string)($pageState['page_error'] ?? ''),
            'character' => $pageState['character'] ?? null,
            'stats' => (array)($pageState['stats'] ?? array()),
            'equipment' => (array)($pageState['equipment'] ?? array()),
            'talentTabs' => (array)($pageState['talent_tabs'] ?? array()),
            'reputations' => (array)($pageState['reputations'] ?? array()),
            'reputationSections' => (array)($pageState['reputation_sections'] ?? array()),
            'skillsByCategory' => (array)($pageState['skills_by_category'] ?? array()),
            'professionsByCategory' => (array)($pageState['professions_by_category'] ?? array()),
            'professionRecipesBySkillId' => (array)($pageState['profession_recipes_by_skill_id'] ?? array()),
            'knownCharacterSpells' => (array)($pageState['known_character_spells'] ?? array()),
            'achievementSummary' => (array)($pageState['achievement_summary'] ?? array()),
            'recentGear' => (array)($pageState['recent_gear'] ?? array()),
            'activeQuestLog' => (array)($pageState['active_quest_log'] ?? array()),
            'completedQuestHistory' => (array)($pageState['completed_quest_history'] ?? array()),
            'completedQuestTotal' => (int)($pageState['completed_quest_total'] ?? 0),
            'lastInstance' => (string)($pageState['last_instance'] ?? ''),
            'lastInstanceDate' => (int)($pageState['last_instance_date'] ?? 0),
            'currentMapName' => (string)($pageState['current_map_name'] ?? 'Unknown zone'),
            'displayLocation' => (string)($pageState['display_location'] ?? 'Unknown location'),
            'combatHighlights' => (array)($pageState['combat_highlights'] ?? array()),
            'factionIcon' => (string)($pageState['faction_icon'] ?? ''),
            'gearProgression' => (array)($pageState['gear_progression'] ?? array()),
            'forumSocial' => (array)($pageState['forum_social'] ?? array()),
            'botPersonalityText' => (string)($pageState['bot_personality_text'] ?? ''),
            'botSignatureText' => (string)($pageState['bot_signature_text'] ?? ''),
            'characterAdminFeedback' => (string)($pageState['character_admin_feedback'] ?? ''),
            'characterAdminError' => (string)($pageState['character_admin_error'] ?? ''),
            'botStrategyProfiles' => (array)($pageState['bot_strategy_profiles'] ?? array()),
            'strategyBuilderOptions' => (array)($pageState['strategy_builder_options'] ?? array()),
            'characterStrategyState' => (array)($pageState['character_strategy_state'] ?? array()),
            'charsPdo' => $pageState['chars_pdo'] ?? null,
            'worldPdo' => $pageState['world_pdo'] ?? null,
            'realmdPdo' => $pageState['realmd_pdo'] ?? null,
            'armoryPdo' => $pageState['armory_pdo'] ?? null,
            'realmLabel' => (string)($pageState['realm_label'] ?? ''),
            'characterUrl' => (string)($pageState['character_url'] ?? ''),
            'talentCalculatorUrl' => (string)($pageState['talent_calculator_url'] ?? ''),
            'talent_embed_markup' => (string)($pageState['talent_embed_markup'] ?? ''),
            'personality_saved' => !empty($pageState['personality_saved']),
            'personality_mode' => (string)($pageState['personality_mode'] ?? ''),
            'signature_saved' => !empty($pageState['signature_saved']),
            'bot_strategy_saved' => !empty($pageState['bot_strategy_saved']),
            'bot_strategy_mode' => (string)($pageState['bot_strategy_mode'] ?? ''),
        ));
    }
}
