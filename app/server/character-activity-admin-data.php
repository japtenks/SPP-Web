<?php

if (!function_exists('spp_character_build_strategy_write_commands')) {
    function spp_character_build_strategy_write_commands(string $characterName, array $deltaValues): array
    {
        $commands = array();
        $characterName = trim($characterName);
        if ($characterName === '') {
            return $commands;
        }

        foreach (spp_admin_playerbots_strategy_keys() as $strategyKey) {
            $deltaValue = trim((string)($deltaValues[$strategyKey] ?? ''));
            if ($deltaValue === '') {
                continue;
            }

            $commands[] = '.bot cmd ' . spp_mangos_soap_quote_argument($characterName) . ' ' . $strategyKey . ' ' . $deltaValue;
        }

        return $commands;
    }
}

if (!function_exists('spp_character_load_activity_admin_state')) {
    function spp_character_load_activity_admin_state(array $args): array
    {
        $realmId = (int)($args['realm_id'] ?? 1);
        $characterGuid = (int)($args['character_guid'] ?? 0);
        $characterName = (string)($args['character_name'] ?? '');
        $character = (array)($args['character'] ?? []);
        $charsPdo = $args['chars_pdo'] ?? null;
        $worldPdo = $args['world_pdo'] ?? null;
        $armoryPdo = $args['armory_pdo'] ?? null;
        $forumSocial = (array)($args['forum_social'] ?? []);
        $recentGear = (array)($args['recent_gear'] ?? []);
        $lastInstance = (string)($args['last_instance'] ?? '');
        $lastInstanceDate = (int)($args['last_instance_date'] ?? 0);
        $canManageBotPersonality = !empty($args['can_manage_bot_personality']);
        $botPersonalityText = (string)($args['bot_personality_text'] ?? '');
        $botSignatureText = (string)($args['bot_signature_text'] ?? '');
        $botPlayHabitTraits = (array)($args['bot_play_habit_traits'] ?? array());
        $characterAdminFeedback = (string)($args['character_admin_feedback'] ?? '');
        $characterAdminError = (string)($args['character_admin_error'] ?? '');
        $characterStrategyState = (array)($args['character_strategy_state'] ?? []);
        $post = (array)($args['post'] ?? $_POST);
        $serverMethod = strtoupper((string)($args['server_method'] ?? ($_SERVER['REQUEST_METHOD'] ?? 'GET')));

        if (!$charsPdo instanceof PDO || !$worldPdo instanceof PDO || !$armoryPdo instanceof PDO || $characterGuid <= 0) {
            return array(
                'recent_gear' => $recentGear,
                'last_instance' => $lastInstance,
                'last_instance_date' => $lastInstanceDate,
                'bot_personality_text' => $botPersonalityText,
                'bot_signature_text' => $botSignatureText,
                'bot_play_habit_traits' => $botPlayHabitTraits,
                'forum_social' => $forumSocial,
                'character_admin_feedback' => $characterAdminFeedback,
                'character_admin_error' => $characterAdminError,
                'character_strategy_state' => $characterStrategyState,
            );
        }

        if (spp_character_table_exists($charsPdo, 'character_armory_feed')) {
            $stmt = $charsPdo->prepare('SELECT `type`, `data`, `date` FROM `character_armory_feed` WHERE `guid` = ? AND `type` IN (2, 3) ORDER BY `date` DESC LIMIT 25');
            $stmt->execute(array($characterGuid));
            $feedRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $recentGearFeed = array();
            foreach ($feedRows as $feedRow) {
                $feedType = (int)$feedRow['type'];
                $feedData = (int)$feedRow['data'];
                if ($feedType === 2 && $feedData > 0 && !isset($recentGearFeed[$feedData]) && count($recentGearFeed) < 5) {
                    $recentGearFeed[$feedData] = (int)$feedRow['date'];
                }
                if ($lastInstance === '' && $feedType === 3 && $feedData > 0) {
                    $instanceStmt = $armoryPdo->prepare("SELECT * FROM `armory_instance_data` WHERE (`id` = ? OR `lootid_1` = ? OR `lootid_2` = ? OR `lootid_3` = ? OR `lootid_4` = ? OR `name_id` = ?) AND `type` = 'npc' LIMIT 1");
                    $instanceStmt->execute(array($feedData, $feedData, $feedData, $feedData, $feedData, $feedData));
                    $instanceLoot = $instanceStmt->fetch(PDO::FETCH_ASSOC);
                    if ($instanceLoot) {
                        $templateStmt = $armoryPdo->prepare('SELECT * FROM `armory_instance_template` WHERE `id` = ? LIMIT 1');
                        $templateStmt->execute(array((int)$instanceLoot['instance_id']));
                        $instanceInfo = $templateStmt->fetch(PDO::FETCH_ASSOC);
                        if ($instanceInfo) {
                            $suffix = (((int)$instanceInfo['expansion'] < 2) || !(int)$instanceInfo['raid']) ? '' : ((int)$instanceInfo['is_heroic'] ? ' (H)' : '');
                            $bossName = trim((string)($instanceLoot['name_en_gb'] ?? ''));
                            $instanceName = trim((string)($instanceInfo['name_en_gb'] ?? '')) . $suffix;
                            $lastInstance = trim($bossName . ' - ' . $instanceName, ' -');
                            $lastInstanceDate = (int)$feedRow['date'];
                        }
                    }
                }
            }
            if (!empty($recentGearFeed)) {
                $recentGearIds = array_keys($recentGearFeed);
                $placeholders = implode(',', array_fill(0, count($recentGearIds), '?'));
                $recentItemMap = array();
                $recentIconMap = array();
                $stmt = $worldPdo->prepare('SELECT `entry`, `name`, `Quality`, `ItemLevel`, `RequiredLevel`, `displayid` FROM `item_template` WHERE `entry` IN (' . $placeholders . ')');
                $stmt->execute($recentGearIds);
                $displayIds = array();
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $itemRow) {
                    $recentItemMap[(int)$itemRow['entry']] = $itemRow;
                    if (!empty($itemRow['displayid'])) {
                        $displayIds[(int)$itemRow['displayid']] = true;
                    }
                }
                if (!empty($displayIds)) {
                    $placeholders = implode(',', array_fill(0, count($displayIds), '?'));
                    $stmt = $armoryPdo->prepare('SELECT `id`, `name` FROM `dbc_itemdisplayinfo` WHERE `id` IN (' . $placeholders . ')');
                    $stmt->execute(array_keys($displayIds));
                    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $iconRow) {
                        $recentIconMap[(int)$iconRow['id']] = (string)$iconRow['name'];
                    }
                }
                foreach ($recentGearFeed as $entry => $feedDate) {
                    if (!isset($recentItemMap[$entry])) {
                        continue;
                    }
                    $itemRow = $recentItemMap[$entry];
                    $recentGear[] = array(
                        'entry' => (int)$entry,
                        'name' => (string)$itemRow['name'],
                        'quality' => (int)$itemRow['Quality'],
                        'item_level' => (int)$itemRow['ItemLevel'],
                        'icon' => spp_character_icon_url($recentIconMap[(int)$itemRow['displayid']] ?? ''),
                        'date' => (int)$feedDate,
                    );
                }
            }
        }

        if ($canManageBotPersonality) {
            try {
                $stmt = $charsPdo->prepare("
                    SELECT value
                    FROM ai_playerbot_db_store
                    WHERE guid = ?
                      AND preset = ''
                      AND `key` = 'value'
                      AND value LIKE 'manual saved string::llmdefaultprompt>%'
                    ORDER BY value ASC
                    LIMIT 1
                ");
                $stmt->execute(array($characterGuid));
                $storedPersonality = $stmt->fetchColumn();
                $botPersonalityText = spp_character_decode_personality_value($storedPersonality !== false ? (string)$storedPersonality : '');
            } catch (Throwable $e) {
                error_log('[character-personality-read] ' . $e->getMessage());
            }

            try {
                $identityRow = array(
                    'realm_id' => (int)$realmId,
                    'character_guid' => (int)$characterGuid,
                    'owner_account_id' => (int)($character['account'] ?? 0),
                    'display_name' => (string)$characterName,
                    'identity_type' => 'bot_character',
                    'guild_id' => isset($character['guildid']) ? (int)$character['guildid'] : null,
                    'is_bot' => 1,
                );
                $identityId = (int)($forumSocial['identity_id'] ?? 0);
                if ($identityId > 0) {
                    $botPlayHabitTraits = spp_get_identity_play_habit_traits($identityId, $identityRow);
                }
            } catch (Throwable $e) {
                error_log('[character-play-habits-read] ' . $e->getMessage());
            }

            $characterStrategyState = spp_admin_playerbots_fetch_character_strategy_state($charsPdo, $characterGuid);
        }

        if ($canManageBotPersonality && $serverMethod === 'POST') {
            $characterAdminAction = trim((string)($post['character_admin_action'] ?? ''));
            if ($characterAdminAction !== '') {
                spp_require_csrf('character_personality');
                $postedGuid = (int)($post['character_guid'] ?? 0);
                $personalityTabUrl = 'index.php?n=server&sub=character&realm=' . (int)$realmId . '&character=' . urlencode((string)$characterName) . '&tab=personality';

                if ($postedGuid !== $characterGuid) {
                    $characterAdminError = 'That personality update did not match the selected character.';
                } elseif ($characterAdminAction === 'save_personality') {
                    $botPersonalityText = trim((string)($post['personality_text'] ?? ''));
                    try {
                        $deleteStmt = $charsPdo->prepare("
                            DELETE FROM ai_playerbot_db_store
                            WHERE guid = ?
                              AND preset = ''
                              AND `key` = 'value'
                              AND value LIKE 'manual saved string::llmdefaultprompt>%'
                        ");
                        $deleteStmt->execute(array($characterGuid));

                        if ($botPersonalityText !== '') {
                            $insertStmt = $charsPdo->prepare("
                                INSERT INTO ai_playerbot_db_store (`guid`, `preset`, `key`, `value`)
                                VALUES (?, '', 'value', ?)
                            ");
                            $insertStmt->execute(array($characterGuid, 'manual saved string::llmdefaultprompt>' . $botPersonalityText));
                        }

                        if (!headers_sent()) {
                            header('Location: ' . $personalityTabUrl . '&personality_saved=1&personality_mode=sql');
                            exit;
                        }
                        $characterAdminFeedback = 'LLM personality saved directly to SQL. No safe remote command exists for this setting, so the bot will pick it up on next login/load.';
                    } catch (Throwable $e) {
                        error_log('[character-personality-save] ' . $e->getMessage());
                        $characterAdminError = 'Saving the bot personality failed.';
                    }
                } elseif ($characterAdminAction === 'save_signature') {
                    $botSignatureText = trim((string)($post['llm_signature'] ?? ''));
                    $identityId = (int)($forumSocial['identity_id'] ?? 0);
                    if ($identityId <= 0) {
                        $identityId = spp_ensure_char_identity($realmId, $characterGuid, (int)($character['account'] ?? 0), $characterName, 1, isset($character['guildid']) ? (int)$character['guildid'] : null);
                        $forumSocial['identity_id'] = $identityId;
                    }

                    if ($identityId <= 0) {
                        $characterAdminError = 'No website identity exists for this bot yet, so the signature could not be saved.';
                    } elseif (!spp_update_identity_signature($identityId, $botSignatureText)) {
                        $characterAdminError = 'Saving the bot signature failed.';
                    } else {
                        $forumSocial['signature'] = $botSignatureText;
                        if (!headers_sent()) {
                            header('Location: ' . $personalityTabUrl . '&signature_saved=1');
                            exit;
                        }
                        $characterAdminFeedback = 'Bot signature saved.';
                    }
                } elseif ($characterAdminAction === 'save_bot_habits') {
                    $identityId = (int)($forumSocial['identity_id'] ?? 0);
                    if ($identityId <= 0) {
                        $identityId = spp_ensure_char_identity($realmId, $characterGuid, (int)($character['account'] ?? 0), $characterName, 1, isset($character['guildid']) ? (int)$character['guildid'] : null);
                        $forumSocial['identity_id'] = $identityId;
                    }

                    $botPlayHabitTraits = array(
                        'play_style_key' => (string)($post['play_style_key'] ?? ''),
                        'weekly_frequency_hint' => (string)($post['weekly_frequency_hint'] ?? ''),
                        'session_duration_hint_min' => (string)($post['session_duration_hint_min'] ?? ''),
                        'session_duration_hint_max' => (string)($post['session_duration_hint_max'] ?? ''),
                        'preferred_days' => (string)($post['preferred_days'] ?? ''),
                        'preferred_hours' => (string)($post['preferred_hours'] ?? ''),
                        'cohort_key' => (string)($post['cohort_key'] ?? ''),
                        'life_stage_hint' => (string)($post['life_stage_hint'] ?? ''),
                    );

                    if ($identityId <= 0) {
                        $characterAdminError = 'No website identity exists for this bot yet, so the play habit traits could not be saved.';
                    } elseif (!spp_update_identity_play_habit_traits($identityId, $botPlayHabitTraits)) {
                        $characterAdminError = 'Saving the bot play habit traits failed.';
                    } else {
                        $botPlayHabitTraits = spp_get_identity_play_habit_traits($identityId, array(
                            'realm_id' => (int)$realmId,
                            'character_guid' => (int)$characterGuid,
                            'owner_account_id' => (int)($character['account'] ?? 0),
                            'display_name' => (string)$characterName,
                            'identity_type' => 'bot_character',
                            'guild_id' => isset($character['guildid']) ? (int)$character['guildid'] : null,
                            'is_bot' => 1,
                        ));
                        if (!headers_sent()) {
                            header('Location: ' . $personalityTabUrl . '&bot_habits_saved=1');
                            exit;
                        }
                        $characterAdminFeedback = 'Bot play habit traits saved.';
                    }
                } elseif ($characterAdminAction === 'save_bot_strategy') {
                    $effectiveStrategyValues = array();
                    foreach (spp_admin_playerbots_strategy_keys() as $strategyKey) {
                        $effectiveStrategyValues[$strategyKey] = spp_admin_playerbots_normalize_strategy_value((string)($post['strategy_' . $strategyKey] ?? ''));
                    }

                    $baseRows = spp_admin_playerbots_fetch_strategy_rows_for_guids($charsPdo, array($characterGuid), 'default');
                    $baseValues = $baseRows[$characterGuid] ?? array_fill_keys(spp_admin_playerbots_strategy_keys(), '');
                    $strategyValues = spp_admin_playerbots_build_strategy_override_set($baseValues, $effectiveStrategyValues);
                    $currentEffectiveValues = (array)($characterStrategyState['values'] ?? array_fill_keys(spp_admin_playerbots_strategy_keys(), ''));
                    $soapDeltaValues = spp_admin_playerbots_build_strategy_override_set($currentEffectiveValues, $effectiveStrategyValues);
                    $soapCommands = spp_character_build_strategy_write_commands($characterName, $soapDeltaValues);
                    $strategyWriteMode = 'soap';
                    $soapError = '';

                    try {
                        $writeSqlOverrides = static function () use ($charsPdo, $characterGuid, $strategyValues): void {
                            $charsPdo->beginTransaction();
                            try {
                                $strategyKeys = spp_admin_playerbots_strategy_keys();
                                $keyPlaceholders = implode(',', array_fill(0, count($strategyKeys), '?'));
                                $deleteStmt = $charsPdo->prepare("
                                    DELETE FROM ai_playerbot_db_store
                                    WHERE guid = ?
                                      AND preset = ''
                                      AND `key` IN ($keyPlaceholders)
                                ");
                                $deleteStmt->execute(array_merge(array($characterGuid), $strategyKeys));

                                $insertStmt = $charsPdo->prepare("
                                    INSERT INTO ai_playerbot_db_store (`guid`, `preset`, `key`, `value`)
                                    VALUES (?, '', ?, ?)
                                ");
                                foreach ($strategyValues as $strategyKey => $strategyValue) {
                                    if ($strategyValue === '') {
                                        continue;
                                    }
                                    $insertStmt->execute(array($characterGuid, $strategyKey, $strategyValue));
                                }

                                $charsPdo->commit();
                            } catch (Throwable $e) {
                                if ($charsPdo->inTransaction()) {
                                    $charsPdo->rollBack();
                                }
                                throw $e;
                            }
                        };

                        foreach ($soapCommands as $soapCommand) {
                            $soapResult = spp_mangos_soap_execute_command($realmId, $soapCommand, $soapError);
                            if ($soapResult === false) {
                                $strategyWriteMode = 'sql_fallback';
                                break;
                            }
                        }

                        if ($strategyWriteMode === 'sql_fallback') {
                            $writeSqlOverrides();
                        }

                        if (!headers_sent()) {
                            $redirectUrl = $personalityTabUrl . '&bot_strategy_saved=1&bot_strategy_mode=' . urlencode($strategyWriteMode);
                            header('Location: ' . $redirectUrl);
                            exit;
                        }
                        $characterAdminFeedback = 'Bot strategy overrides saved.';
                        if ($strategyWriteMode === 'sql_fallback') {
                            $characterAdminFeedback .= ' SOAP was unavailable, so a direct SQL fallback write was used. This is less safe while the world server is offline.';
                        }
                        $characterStrategyState = spp_admin_playerbots_fetch_character_strategy_state($charsPdo, $characterGuid);
                    } catch (Throwable $e) {
                        error_log('[character-bot-strategy-save] ' . $e->getMessage());
                        $characterAdminError = 'Saving the bot strategy overrides failed.';
                        if ($soapError !== '') {
                            $characterAdminError .= ' ' . $soapError;
                        }
                    }
                }
            }
        }

        return array(
            'recent_gear' => $recentGear,
            'last_instance' => $lastInstance,
            'last_instance_date' => $lastInstanceDate,
            'bot_personality_text' => $botPersonalityText,
            'bot_signature_text' => $botSignatureText,
            'bot_play_habit_traits' => $botPlayHabitTraits,
            'forum_social' => $forumSocial,
            'character_admin_feedback' => $characterAdminFeedback,
            'character_admin_error' => $characterAdminError,
            'character_strategy_state' => $characterStrategyState,
        );
    }
}
