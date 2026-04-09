<?php

if (!function_exists('spp_character_load_profile_state')) {
    function spp_character_load_profile_state(array $args): array
    {
        $realmId = (int)($args['realm_id'] ?? 1);
        $characterName = trim((string)($args['character_name'] ?? ''));
        $characterGuid = (int)($args['character_guid'] ?? 0);
        $tabs = (array)($args['tabs'] ?? []);
        $tab = trim((string)($args['tab'] ?? 'overview'));
        $user = (array)($args['user'] ?? []);

        $pageError = '';
        $character = null;
        $gearProgression = (array)($args['gear_progression'] ?? []);
        $forumSocial = (array)($args['forum_social'] ?? []);
        $botSignatureText = (string)($args['bot_signature_text'] ?? '');
        $botPlayHabitTraits = (array)($args['bot_play_habit_traits'] ?? array());
        $characterIsBot = (bool)($args['character_is_bot'] ?? false);
        $canManageBotPersonality = (bool)($args['can_manage_bot_personality'] ?? false);
        $characterPersonalityCsrfToken = (string)($args['character_personality_csrf_token'] ?? '');

        try {
            $charsPdo = spp_get_pdo('chars', $realmId);
            $worldPdo = spp_get_pdo('world', $realmId);
            $realmdPdo = spp_get_pdo('realmd', $realmId);
            $armoryPdo = spp_get_pdo('armory', $realmId);

            $characterColumns = spp_character_columns($charsPdo, 'characters');
            $guildMemberColumns = spp_character_columns($charsPdo, 'guild_member');
            $guildColumns = spp_character_columns($charsPdo, 'guild');
            $playtimeColumn = spp_character_resolve_column($charsPdo, 'characters', array('totaltime', 'played_time_total'));
            $leveltimeColumn = spp_character_resolve_column($charsPdo, 'characters', array('leveltime', 'played_time_level'));
            $guildMemberGuildIdColumn = spp_character_resolve_column($charsPdo, 'guild_member', array('guildid', 'guild_id'));
            $guildGuildIdColumn = spp_character_resolve_column($charsPdo, 'guild', array('guildid', 'guild_id'));

            $selectParts = array(
                'c.`guid`',
                'c.`name`',
                'c.`account`',
                'c.`race`',
                'c.`class`',
                'c.`gender`',
                'c.`level`',
                'c.`zone`',
                'c.`map`',
                'c.`online`',
                ($playtimeColumn !== null ? 'c.`' . $playtimeColumn . '`' : '0') . ' AS `totaltime`',
                ($leveltimeColumn !== null ? 'c.`' . $leveltimeColumn . '`' : '0') . ' AS `leveltime`',
            );
            foreach (array('health', 'power1', 'power2', 'stored_honorable_kills', 'stored_honor_rating', 'honor_highest_rank', 'totalKills', 'totalHonorPoints') as $columnName) {
                if (isset($characterColumns[$columnName])) {
                    $selectParts[] = 'c.`' . $columnName . '`';
                }
            }
            if ($guildMemberGuildIdColumn !== null && isset($guildMemberColumns[$guildMemberGuildIdColumn])) {
                $selectParts[] = 'gm.`' . $guildMemberGuildIdColumn . '` AS `guildid`';
            } else {
                $selectParts[] = '0 AS `guildid`';
            }
            $selectParts[] = 'g.`name` AS `guild_name`';

            $sql = 'SELECT ' . implode(', ', $selectParts) . ' FROM `characters` c';
            $sql .= ' LEFT JOIN `guild_member` gm ON gm.`guid` = c.`guid`';
            if ($guildMemberGuildIdColumn !== null && $guildGuildIdColumn !== null && isset($guildColumns[$guildGuildIdColumn])) {
                $sql .= ' LEFT JOIN `guild` g ON g.`' . $guildGuildIdColumn . '` = gm.`' . $guildMemberGuildIdColumn . '`';
            } else {
                $sql .= ' LEFT JOIN `guild` g ON 1 = 0';
            }
            $stmt = $charsPdo->prepare($sql . ($characterGuid > 0 ? ' WHERE c.`guid` = ?' : ' WHERE c.`name` = ?') . ' LIMIT 1');
            $stmt->execute(array($characterGuid > 0 ? $characterGuid : $characterName));
            $character = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$character) {
                throw new RuntimeException('Character not found.');
            }

            $characterGuid = (int)$character['guid'];
            $characterName = (string)$character['name'];

            try {
                if ($realmdPdo instanceof PDO && spp_character_table_exists($realmdPdo, 'bot_rotation_ilvl_log')) {
                    $gearProgression['supported'] = true;
                    $stmt = $realmdPdo->prepare(
                        'SELECT `snapshot_time`, `level`, `online`, `avg_equipped_ilvl`, `equipped_item_count`
                         FROM `bot_rotation_ilvl_log`
                         WHERE `realm` = ? AND `bot_guid` = ?
                         ORDER BY `snapshot_time` DESC
                         LIMIT 240'
                    );
                    $stmt->execute(array($realmId, $characterGuid));
                    $gearRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (!empty($gearRows)) {
                        $gearRows = array_reverse($gearRows);
                        $gearProgression['rows'] = $gearRows;
                        $gearProgression['has_history'] = true;
                        $gearProgression['snapshot_count'] = count($gearRows);
                        $firstRow = $gearRows[0];
                        $latestRow = $gearRows[count($gearRows) - 1];
                        $values = array();
                        foreach ($gearRows as $gearRow) {
                            $value = spp_character_float_or_null($gearRow['avg_equipped_ilvl'] ?? null);
                            if ($value !== null) {
                                $values[] = $value;
                            }
                        }
                        $gearProgression['first_ilvl'] = spp_character_float_or_null($firstRow['avg_equipped_ilvl'] ?? null);
                        $gearProgression['latest_ilvl'] = spp_character_float_or_null($latestRow['avg_equipped_ilvl'] ?? null);
                        $gearProgression['peak_ilvl'] = !empty($values) ? max($values) : null;
                        $gearProgression['delta_ilvl'] = (
                            $gearProgression['first_ilvl'] !== null &&
                            $gearProgression['latest_ilvl'] !== null
                        ) ? ($gearProgression['latest_ilvl'] - $gearProgression['first_ilvl']) : null;
                        $gearProgression['latest_equipped_count'] = isset($latestRow['equipped_item_count']) ? (int)$latestRow['equipped_item_count'] : null;
                        $gearProgression['latest_level'] = isset($latestRow['level']) ? (int)$latestRow['level'] : null;
                        $gearProgression['latest_online'] = !empty($latestRow['online']);
                        $firstTime = strtotime((string)($firstRow['snapshot_time'] ?? ''));
                        $latestTime = strtotime((string)($latestRow['snapshot_time'] ?? ''));
                        $gearProgression['first_snapshot_label'] = $firstTime ? date('M j, Y', $firstTime) : '';
                        $gearProgression['latest_snapshot_label'] = $latestTime ? date('M j, Y g:i A', $latestTime) : '';
                        $gearProgression['chart'] = spp_character_build_ilvl_chart($gearRows);
                    }
                }
            } catch (Exception $e) {
                error_log('[character-gear-progress] ' . $e->getMessage());
            }

            try {
                if (
                    $realmdPdo instanceof PDO &&
                    spp_character_table_exists($realmdPdo, 'f_posts') &&
                    spp_character_table_exists($realmdPdo, 'f_topics') &&
                    spp_character_table_exists($realmdPdo, 'f_forums')
                ) {
                    $characterAccountId = (int)($character['account'] ?? 0);
                    $forumSocial['account_id'] = $characterAccountId;
                    if ($characterAccountId > 0) {
                        $forumSocial['account_link'] = 'index.php?n=admin&sub=members&id=' . $characterAccountId;
                        if (spp_character_table_exists($realmdPdo, 'account')) {
                            $stmt = $realmdPdo->prepare('SELECT `username` FROM `account` WHERE `id` = ? LIMIT 1');
                            $stmt->execute(array($characterAccountId));
                            $forumSocial['account_username'] = (string)$stmt->fetchColumn();
                        }
                    }
                    $identity = spp_get_char_identity($realmId, $characterGuid);
                    $identityId = (int)($identity['identity_id'] ?? 0);
                    if ($identityId <= 0 && $characterAccountId > 0) {
                        $identityId = spp_ensure_char_identity($realmId, $characterGuid, $characterAccountId, $characterName);
                    }

                    $forumSocial['identity_id'] = $identityId;
                    if ($identityId > 0) {
                        $forumSocial['signature'] = (string)spp_get_identity_signature($identityId);
                        $botPlayHabitTraits = spp_get_identity_play_habit_traits($identityId, $identity ?: array());
                        if ($forumSocial['signature'] !== '') {
                            $normalizedSignature = str_replace(
                                array('<br />', '<br/>', '<br>'),
                                "\n",
                                html_entity_decode($forumSocial['signature'], ENT_QUOTES, 'UTF-8')
                            );
                            $normalizedSignature = spp_forum_normalize_legacy_markup($normalizedSignature);
                            $forumSocial['rendered_signature'] = bbcode($normalizedSignature, true, true, true, false);
                        }

                        $stmt = $realmdPdo->prepare('SELECT COUNT(*) FROM `f_posts` WHERE `poster_identity_id` = ?');
                        $stmt->execute(array($identityId));
                        $forumSocial['posts'] = (int)$stmt->fetchColumn();

                        $stmt = $realmdPdo->prepare('SELECT COUNT(*) FROM `f_topics` WHERE `topic_poster_identity_id` = ?');
                        $stmt->execute(array($identityId));
                        $forumSocial['topics'] = (int)$stmt->fetchColumn();

                        $stmt = $realmdPdo->prepare('SELECT MAX(`posted`) FROM `f_posts` WHERE `poster_identity_id` = ?');
                        $stmt->execute(array($identityId));
                        $forumSocial['last_post'] = (int)$stmt->fetchColumn();

                        $stmt = $realmdPdo->prepare('SELECT MAX(`topic_posted`) FROM `f_topics` WHERE `topic_poster_identity_id` = ?');
                        $stmt->execute(array($identityId));
                        $forumSocial['last_topic'] = (int)$stmt->fetchColumn();

                        $stmt = $realmdPdo->prepare("
                            SELECT
                                p.`post_id`,
                                p.`posted`,
                                LEFT(TRIM(REPLACE(REPLACE(REPLACE(COALESCE(p.`message`, ''), '<br />', ' '), '<br/>', ' '), '<br>', ' ')), 180) AS `excerpt`,
                                t.`topic_id`,
                                t.`topic_name`,
                                f.`forum_id`,
                                f.`forum_name`
                            FROM `f_posts` p
                            LEFT JOIN `f_topics` t ON t.`topic_id` = p.`topic_id`
                            LEFT JOIN `f_forums` f ON f.`forum_id` = t.`forum_id`
                            WHERE p.`poster_identity_id` = ?
                            ORDER BY p.`posted` DESC
                            LIMIT 5
                        ");
                        $stmt->execute(array($identityId));
                        $forumSocial['recent_posts'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: array();

                        $stmt = $realmdPdo->prepare("
                            SELECT
                                t.`topic_id`,
                                t.`topic_name`,
                                t.`topic_posted`,
                                t.`last_post`,
                                t.`num_replies`,
                                f.`forum_id`,
                                f.`forum_name`
                            FROM `f_topics` t
                            LEFT JOIN `f_forums` f ON f.`forum_id` = t.`forum_id`
                            WHERE t.`topic_poster_identity_id` = ?
                            ORDER BY t.`topic_posted` DESC
                            LIMIT 5
                        ");
                        $stmt->execute(array($identityId));
                        $forumSocial['recent_topics'] = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: array();
                    }
                }
            } catch (Exception $e) {
                error_log('[character-social] ' . $e->getMessage());
            }

            try {
                if ($realmdPdo instanceof PDO && spp_character_table_exists($realmdPdo, 'bot_rotation_state')) {
                    $stmt = $realmdPdo->prepare('
                        SELECT `last_online`, `online_seconds`, `offline_seconds`,
                               `online_sessions`, `offline_sessions`,
                               `last_seen_time`, `last_change_time`,
                               `last_online_start`, `last_offline_start`,
                               TIMESTAMPDIFF(SECOND, `last_online_start`, NOW()) AS `online_span_seconds`,
                               TIMESTAMPDIFF(SECOND, `last_offline_start`, NOW()) AS `offline_span_seconds`
                        FROM `bot_rotation_state`
                        WHERE `realm` = ? AND `bot_guid` = ?
                        LIMIT 1
                    ');
                    $stmt->execute(array($realmId, $characterGuid));
                    $rotationState = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
                    if ($rotationState) {
                        $forumSocial['rotation_last_online'] = !empty($rotationState['last_online']) ? 1 : 0;
                        $forumSocial['rotation_online_seconds'] = (int)($rotationState['online_seconds'] ?? 0);
                        $forumSocial['rotation_offline_seconds'] = (int)($rotationState['offline_seconds'] ?? 0);
                        $forumSocial['rotation_online_sessions'] = (int)($rotationState['online_sessions'] ?? 0);
                        $forumSocial['rotation_offline_sessions'] = (int)($rotationState['offline_sessions'] ?? 0);
                        $forumSocial['rotation_full_cycles'] = min(
                            $forumSocial['rotation_online_sessions'],
                            $forumSocial['rotation_offline_sessions']
                        );
                        $forumSocial['rotation_online_avg_seconds'] = $forumSocial['rotation_online_sessions'] > 0
                            ? ($forumSocial['rotation_online_seconds'] / $forumSocial['rotation_online_sessions'])
                            : null;
                        $forumSocial['rotation_offline_avg_seconds'] = $forumSocial['rotation_offline_sessions'] > 0
                            ? ($forumSocial['rotation_offline_seconds'] / $forumSocial['rotation_offline_sessions'])
                            : null;
                        $forumSocial['rotation_last_seen'] = (string)($rotationState['last_seen_time'] ?? '');
                        $forumSocial['rotation_last_change'] = (string)($rotationState['last_change_time'] ?? '');
                        $forumSocial['rotation_tracked'] = true;
                        $forumSocial['rotation_current_span_seconds'] = !empty($forumSocial['rotation_last_online'])
                            ? max(0, (int)($rotationState['online_span_seconds'] ?? 0))
                            : max(0, (int)($rotationState['offline_span_seconds'] ?? 0));
                    }
                }
            } catch (Exception $e) {
                error_log('[character-rotation-social] ' . $e->getMessage());
            }

            $botSignatureText = (string)($forumSocial['signature'] ?? '');
            $characterIsBot = spp_character_is_playerbot_account_name((string)($forumSocial['account_username'] ?? ''));
            $canManageBotPersonality = $characterIsBot && (int)($user['gmlevel'] ?? 0) >= 1;
            if ($canManageBotPersonality && !in_array('personality', $tabs, true)) {
                $tabs[] = 'personality';
                $characterPersonalityCsrfToken = spp_csrf_token('character_personality');
            }
            if (!in_array($tab, $tabs, true)) {
                $tab = 'overview';
            }

            return array(
                'page_error' => $pageError,
                'character' => $character,
                'character_guid' => $characterGuid,
                'character_name' => $characterName,
                'gear_progression' => $gearProgression,
                'forum_social' => $forumSocial,
                'bot_signature_text' => $botSignatureText,
                'bot_play_habit_traits' => $botPlayHabitTraits,
                'character_is_bot' => $characterIsBot,
                'can_manage_bot_personality' => $canManageBotPersonality,
                'character_personality_csrf_token' => $characterPersonalityCsrfToken,
                'tabs' => $tabs,
                'tab' => $tab,
                'chars_pdo' => $charsPdo,
                'world_pdo' => $worldPdo,
                'realmd_pdo' => $realmdPdo,
                'armory_pdo' => $armoryPdo,
            );
        } catch (Throwable $e) {
            return array(
                'page_error' => $e instanceof RuntimeException ? $e->getMessage() : 'Character profile could not be loaded.',
                'character' => null,
                'character_guid' => $characterGuid,
                'character_name' => $characterName,
                'gear_progression' => $gearProgression,
                'forum_social' => $forumSocial,
                'bot_signature_text' => $botSignatureText,
                'bot_play_habit_traits' => $botPlayHabitTraits,
                'character_is_bot' => $characterIsBot,
                'can_manage_bot_personality' => $canManageBotPersonality,
                'character_personality_csrf_token' => $characterPersonalityCsrfToken,
                'tabs' => $tabs,
                'tab' => $tab,
                'chars_pdo' => null,
                'world_pdo' => null,
                'realmd_pdo' => null,
                'armory_pdo' => null,
            );
        }
    }
}
