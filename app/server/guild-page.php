<?php

require_once __DIR__ . '/../support/db-schema.php';
require_once __DIR__ . '/guild-orders-state.php';

if (!function_exists('spp_guild_order_examples')) {
    function spp_guild_order_examples(): array
    {
        return array(
            'Craft: Heavy Wool Bandage 10',
            'Farm: Copper Ore 40',
            'Kill: Defias Pillager',
            'Explore: Stranglethorn Vale',
            'skip order',
        );
    }
}

if (!function_exists('spp_guild_build_order_preview_rows')) {
    function spp_guild_build_order_preview_rows(array $members, bool $assumeShareFallback = true): array
    {
        $rows = array();
        foreach ($members as $member) {
            $rawNote = trim((string)($member['offnote'] ?? ''));
            $parsed = spp_admin_playerbots_validate_order_note($rawNote);
            $status = spp_admin_playerbots_order_status_meta($parsed, $assumeShareFallback);
            $typeLabel = spp_admin_playerbots_order_type_label($parsed, $assumeShareFallback);
            $rows[] = array(
                'guid' => (int)($member['guid'] ?? 0),
                'name' => (string)($member['name'] ?? ''),
                'offnote' => $rawNote,
                'parsed' => $parsed,
                'status' => $status,
                'status_badge_key' => (string)($status['key'] ?? 'none'),
                'status_badge_label' => (string)($status['label'] ?? ''),
                'status_badge_description' => (string)($status['description'] ?? ''),
                'status_badge_class' => 'is-' . (string)($status['key'] ?? 'none'),
                'type_label' => $typeLabel,
                'manual_share_fallback_label' => $typeLabel,
                'target_label' => !empty($parsed['valid']) ? trim((string)($parsed['target'] ?? '')) : '',
                'amount_label' => !empty($parsed['valid']) && isset($parsed['amount']) && $parsed['amount'] !== null ? (int)$parsed['amount'] : null,
                'summary_label' => !empty($parsed['valid'])
                    ? (string)($parsed['normalized'] !== '' ? $parsed['normalized'] : $typeLabel)
                    : (string)($parsed['error'] ?? 'Invalid order'),
            );
        }

        return $rows;
    }
}

if (!function_exists('spp_guild_build_order_validation_feedback')) {
    function spp_guild_build_order_validation_feedback(array $guildOrdersPreview, array $guildSharePreview, array $meetingPreview): array
    {
        $groups = array();
        $summaryParts = array();

        $invalidOrderRows = array();
        foreach ($guildOrdersPreview as $orderRow) {
            if (!empty($orderRow['parsed']['valid'])) {
                continue;
            }
            $invalidOrderRows[] = array(
                'guid' => (int)($orderRow['guid'] ?? 0),
                'name' => (string)($orderRow['name'] ?? ''),
                'note' => (string)($orderRow['offnote'] ?? ''),
                'error' => (string)($orderRow['parsed']['error'] ?? 'Invalid order note.'),
            );
        }
        if (!empty($invalidOrderRows)) {
            $groups['officer_notes'] = array(
                'label' => 'Officer Notes',
                'items' => $invalidOrderRows,
            );
            $summaryParts[] = count($invalidOrderRows) . ' officer note validation issue(s)';
        }

        $shareErrors = array_values((array)($guildSharePreview['errors'] ?? array()));
        if (!empty($shareErrors)) {
            $groups['share'] = array(
                'label' => 'Share Block',
                'items' => array_map(static function ($error) {
                    return array('error' => (string)$error);
                }, $shareErrors),
            );
            $summaryParts[] = count($shareErrors) . ' share validation issue(s)';
        }

        if (!empty($meetingPreview['found']) && empty($meetingPreview['valid'])) {
            $groups['meeting'] = array(
                'label' => 'Meeting',
                'items' => array(
                    array(
                        'error' => (string)($meetingPreview['error'] ?? 'Meeting directive is invalid.'),
                    ),
                ),
            );
            $summaryParts[] = 'meeting directive validation issue';
        }

        return array(
            'has_errors' => !empty($groups),
            'summary' => !empty($summaryParts) ? ('Guild orders have ' . implode(', ', $summaryParts) . '.') : '',
            'groups' => $groups,
        );
    }
}

if (!function_exists('spp_guild_share_preview_rows')) {
    function spp_guild_share_preview_rows(array $sharePreview): array
    {
        $rows = array();
        foreach ((array)($sharePreview['entries'] ?? array()) as $entry) {
            $rows[] = array(
                'filter' => (string)($entry['filter'] ?? ''),
                'items_label' => spp_admin_playerbots_format_share_items((array)($entry['items'] ?? array())),
            );
        }

        return $rows;
    }
}

if (!function_exists('spp_guild_load_page_state')) {
    function spp_guild_load_page_state(array $args): array
    {
        extract($args, EXTR_SKIP);

        if (!array_key_exists('info', $guild)) {
            $stmt = $charsPdo->prepare("SELECT info FROM {$realmDB}.guild WHERE guildid=?");
            $stmt->execute([(int)$guildId]);
            $guild['info'] = (string)$stmt->fetchColumn();
        }

        $selectedWebsiteCharacterId = 0;
        if (isset($user['id']) && (int)$user['id'] > 0) {
            $stmt = $realmdPdo->prepare("SELECT character_id FROM website_accounts WHERE account_id=?");
            $stmt->execute([(int)$user['id']]);
            $selectedWebsiteCharacterId = (int)$stmt->fetchColumn();
        }

        $selectedGuildMember = null;
        if ($selectedWebsiteCharacterId > 0) {
            $stmt = $charsPdo->prepare("SELECT gm.guid, gm.rank, COALESCE(gr.rights, 0) AS rank_rights
                 FROM {$realmDB}.guild_member gm
                 LEFT JOIN {$realmDB}.guild_rank gr ON gr.guildid = gm.guildid AND gr.rid = gm.rank
                 WHERE gm.guildid=? AND gm.guid=?");
            $stmt->execute([(int)$guildId, (int)$selectedWebsiteCharacterId]);
            $selectedGuildMember = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        $isSelectedGuildLeader = $selectedWebsiteCharacterId > 0 && $selectedWebsiteCharacterId === (int)$guild['leaderguid'];
        $isGm = (int)($user['gmlevel'] ?? 0) >= 1;
        $selectedGuildRankRights = isset($selectedGuildMember['rank_rights']) ? (int)$selectedGuildMember['rank_rights'] : 0;
        $guildSetMotdRight = 4096;
        $guildViewOfficerNoteRight = 16384;
        $canEditGuildNotes = $isSelectedGuildLeader || $isGm;
        $canViewOfficerNotes = $isSelectedGuildLeader || $isGm || (($selectedGuildRankRights & $guildViewOfficerNoteRight) === $guildViewOfficerNoteRight);
        $canEditGuildMotd = $isSelectedGuildLeader || $isGm || (($selectedGuildRankRights & $guildSetMotdRight) === $guildSetMotdRight);
        $canManageGuildRoster = $isSelectedGuildLeader || $isGm;
        $canAccessGuildLeaderTab = $canEditGuildNotes || $canEditGuildMotd || $canManageGuildRoster || $isSelectedGuildLeader || $isGm;
        $requestedGuildTab = strtolower(trim((string)($_GET['tab'] ?? 'overview')));
        if ($requestedGuildTab !== 'leader' && $requestedGuildTab !== 'overview') {
            $requestedGuildTab = 'overview';
        }
        $guildActiveTab = $requestedGuildTab === 'leader' && $canAccessGuildLeaderTab ? 'leader' : 'overview';
        $isGuildLeaderTabActive = $guildActiveTab === 'leader';
        $guildRosterAllowsNoteEditing = $isGuildLeaderTabActive && $canEditGuildNotes;
        $guildRosterShowsOfficerNotes = $isGuildLeaderTabActive && $canViewOfficerNotes;
        $guildRosterAllowsManagement = $isGuildLeaderTabActive && $canManageGuildRoster;
        $guildLeaderToolsVisible = $isGuildLeaderTabActive && $canAccessGuildLeaderTab;
        $guildNoteFeedback = '';
        $guildNoteError = '';
        $guildMotdFeedback = '';
        $guildMotdError = '';
        $guildNoteWriteMode = '';
        $guildMotdWriteMode = '';
        $guildReturnParams = is_array($_GET ?? null) ? $_GET : array();
        $guildReturnParams['n'] = 'server';
        $guildReturnParams['sub'] = 'guild';
        $guildReturnParams['realm'] = (int)$realmId;
        $guildReturnParams['guildid'] = (int)$guildId;
        unset($guildReturnParams['guild_note_saved'], $guildReturnParams['guild_motd_saved']);
        $guildReturnUrl = 'index.php?' . http_build_query($guildReturnParams);
        $guildCsrfToken = spp_csrf_token('guild_page');
        $guildOrderDraftNotes = array();
        $guildOrderValidationByGuid = array();
        $guildShareBlock = spp_admin_playerbots_extract_share_block((string)($guild['info'] ?? ''));
        $guildSharePreview = spp_admin_playerbots_validate_share_block($guildShareBlock);
        $guildSharePreviewRows = spp_guild_share_preview_rows($guildSharePreview);
        $guildOrdersPanelVisible = $canEditGuildNotes;
        $guildOrdersFallbackSummary = 'Blank note -> auto via Share block -> craft if a guild deficit can be satisfied directly -> otherwise farm the missing reagents or materials.';
        $guildOrdersExamples = spp_guild_order_examples();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guild_roster_action']) && $_POST['guild_roster_action'] === 'manage_member') {
            spp_require_csrf('guild_page');
            if (!$canManageGuildRoster) {
                $guildNoteError = 'Only the selected guild leader can manage guild members from the website.';
            } else {
                $actionType = isset($_POST['guild_roster_action_type']) ? trim((string)$_POST['guild_roster_action_type']) : '';
                $targetGuid = isset($_POST['target_guid']) ? (int)$_POST['target_guid'] : 0;
                $stmt = $charsPdo->prepare("SELECT c.guid, c.name, gm.rank
                     FROM {$realmDB}.guild_member gm
                     INNER JOIN {$realmDB}.characters c ON c.guid = gm.guid
                     WHERE gm.guildid=? AND gm.guid=?");
                $stmt->execute([(int)$guildId, (int)$targetGuid]);
                $targetMember = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$targetMember) {
                    $guildNoteError = 'That guild member could not be found.';
                } elseif ((int)$targetMember['guid'] === (int)$guild['leaderguid']) {
                    $guildNoteError = 'The guild leader cannot be managed from the website.';
                } else {
                    $targetName = (string)$targetMember['name'];
                    $targetRank = (int)$targetMember['rank'];
                    $stmt = $charsPdo->prepare("SELECT COALESCE(MAX(rid), 0) FROM {$realmDB}.guild_rank WHERE guildid=?");
                    $stmt->execute([(int)$guildId]);
                    $maxGuildRankId = (int)$stmt->fetchColumn();
                    $soapCommand = '';
                    $successLabel = '';

                    if ($actionType === 'rank_up') {
                        if ($targetRank <= 1) {
                            $guildNoteError = 'That member is already at the highest rank that can be adjusted from the roster.';
                        } else {
                            $soapCommand = '.guild rank ' . $targetName . ' ' . ($targetRank - 1);
                            $successLabel = 'Promoted ' . $targetName . '.';
                        }
                    } elseif ($actionType === 'rank_down') {
                        if ($targetRank >= $maxGuildRankId) {
                            $guildNoteError = 'That member is already at the lowest guild rank.';
                        } else {
                            $soapCommand = '.guild rank ' . $targetName . ' ' . ($targetRank + 1);
                            $successLabel = 'Demoted ' . $targetName . '.';
                        }
                    } elseif ($actionType === 'kick') {
                        $soapCommand = '.guild uninvite ' . $targetName;
                        $successLabel = 'Removed ' . $targetName . ' from the guild.';
                    } else {
                        $guildNoteError = 'Unknown guild roster action.';
                    }

                    if ($guildNoteError === '' && $soapCommand !== '') {
                        $soapError = '';
                        $soapResult = spp_mangos_soap_execute_command($realmId, $soapCommand, $soapError);
                        if ($soapResult === false) {
                            $guildNoteError = $soapError !== '' ? $soapError : 'The guild action failed.';
                        } else {
                            $guildNoteFeedback = $successLabel;
                            if ($soapResult !== '') {
                                $guildNoteFeedback .= ' ' . $soapResult;
                            }
                        }
                    }
                }
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guild_form_action']) && $_POST['guild_form_action'] === 'save_guild_data') {
            spp_require_csrf('guild_page');
            $submitMode = isset($_POST['guild_submit_mode']) ? (string)$_POST['guild_submit_mode'] : '';
            $shouldSaveMotd = $submitMode === 'motd_only';
            $shouldSaveNotes = $submitMode === 'all_notes';
            $publicNotes = isset($_POST['pnote']) && is_array($_POST['pnote']) ? $_POST['pnote'] : [];
            $officerNotes = isset($_POST['offnote']) && is_array($_POST['offnote']) ? $_POST['offnote'] : [];
            $guildOrderDraftNotes = array();
            foreach ($officerNotes as $noteGuid => $noteValue) {
                $guildOrderDraftNotes[(int)$noteGuid] = substr(trim((string)$noteValue), 0, 31);
            }

            if ($shouldSaveNotes) {
                if (!$canEditGuildNotes) {
                    $guildNoteError = 'Only the selected guild leader can update guild notes from the website.';
                } else {
                    $validationRoster = spp_guild_orders_fetch_roster_rows($charsPdo, $realmDB, (int)$guildId);
                    $noteValidation = spp_guild_orders_validate_note_submission($validationRoster, $publicNotes, $officerNotes);
                    $guildOrderValidationFeedback = array(
                        'has_errors' => !empty($noteValidation['valid']) ? false : true,
                        'summary' => (string)($noteValidation['summary'] ?? ''),
                        'groups' => (array)($noteValidation['groups'] ?? array()),
                    );
                    $guildOrderValidationByGuid = array();
                    if (!empty($guildOrderValidationFeedback['groups']['officer_notes']['items']) && is_array($guildOrderValidationFeedback['groups']['officer_notes']['items'])) {
                        foreach ($guildOrderValidationFeedback['groups']['officer_notes']['items'] as $validationItem) {
                            $validationGuid = (int)($validationItem['guid'] ?? 0);
                            if ($validationGuid > 0) {
                                $guildOrderValidationByGuid[$validationGuid] = (string)($validationItem['error'] ?? 'Invalid order note.');
                            }
                        }
                    }

                    if (empty($noteValidation['valid'])) {
                        $guildNoteError = (string)($noteValidation['summary'] ?? 'Officer note validation failed.');
                    } else {
                        $validMembers = (array)($noteValidation['valid_members'] ?? array());

                        if (count($validMembers) === 0) {
                            $guildNoteError = 'No valid guild members were found for the submitted notes.';
                        } else {
                            foreach ($validMembers as $memberRow) {
                                $validGuid = (int)($memberRow['guid'] ?? 0);
                                $memberName = isset($memberRow['name']) ? (string)$memberRow['name'] : '';
                                if ($validGuid < 1 || $memberName === '') {
                                    continue;
                                }

                                $publicNote = substr(trim((string)($publicNotes[$validGuid] ?? '')), 0, 31);
                                $officerNote = substr(trim((string)($officerNotes[$validGuid] ?? '')), 0, 31);

                                $soapError = '';
                                $publicSoapCommand = '.guild pnote ' . $memberName . ' ' . spp_mangos_soap_format_trailing_argument($publicNote);
                                $publicSoapResult = spp_mangos_soap_execute_command($realmId, $publicSoapCommand, $soapError);
                                $officerSoapResult = false;

                                if ($publicSoapResult !== false) {
                                    $officerSoapCommand = '.guild offnote ' . $memberName . ' ' . spp_mangos_soap_format_trailing_argument($officerNote);
                                    $officerSoapResult = spp_mangos_soap_execute_command($realmId, $officerSoapCommand, $soapError);
                                }

                                if ($publicSoapResult === false || $officerSoapResult === false) {
                                    try {
                                        $stmt = $charsPdo->prepare('UPDATE guild_member SET pnote = ?, offnote = ? WHERE guildid = ? AND guid = ?');
                                        $stmt->execute(array($publicNote, $officerNote, (int)$guildId, (int)$validGuid));
                                        $guildNoteWriteMode = 'sql_fallback';
                                    } catch (Throwable $e) {
                                        $guildNoteError = trim(($soapError !== '' ? $soapError . ' ' : '') . 'SQL fallback failed: ' . $e->getMessage());
                                        break;
                                    }
                                }
                            }

                            if ($guildNoteError === '') {
                                $guildNoteFeedback = 'Guild notes updated.';
                            }
                        }
                    }
                }
            }

            if ($shouldSaveMotd && $guildNoteError === '') {
                if (!$canEditGuildMotd) {
                    $guildMotdError = 'Your selected guild character does not have permission to update the guild MOTD.';
                } else {
                    $newMotd = substr(trim((string)($_POST['guild_motd'] ?? '')), 0, 128);
                    $soapCommand = '.guild motd ' . spp_mangos_soap_quote_argument((string)$guild['name']) . ' ' . spp_mangos_soap_format_trailing_argument($newMotd);
                    $writeResult = spp_mangos_execute_or_sql_fallback(
                        $realmId,
                        $soapCommand,
                        static function () use ($charsPdo, $guildId, $newMotd): void {
                            $stmt = $charsPdo->prepare('UPDATE guild SET motd = ? WHERE guildid = ? LIMIT 1');
                            $stmt->execute(array($newMotd, (int)$guildId));
                        },
                        array(
                            'sql_fallback_message' => 'Guild MOTD was written directly to the characters DB because SOAP was unavailable. This is less safe while the world server is offline.',
                        )
                    );
                    if (empty($writeResult['ok'])) {
                        $guildMotdError = (string)($writeResult['message'] ?? 'The guild MOTD update failed.');
                    } else {
                        $guildMotdFeedback = 'Guild MOTD updated.';
                        if (($writeResult['mode'] ?? '') === 'soap' && (string)($writeResult['soap_result'] ?? '') !== '') {
                            $guildMotdFeedback .= ' ' . (string)$writeResult['soap_result'];
                        } elseif (($writeResult['mode'] ?? '') === 'sql_fallback') {
                            $guildMotdWriteMode = 'sql_fallback';
                            $guildMotdFeedback .= ' ' . (string)($writeResult['message'] ?? '');
                        }
                        $guild['motd'] = $newMotd;
                    }
                }
            }

            if ($guildNoteError === '' && $guildMotdError === '' && ($guildNoteFeedback !== '' || $guildMotdFeedback !== '')) {
                $redirectUrl = preg_replace('/([?&])guild_(note|motd)_saved=1(&|$)/', '$1', $guildReturnUrl);
                $redirectUrl = rtrim((string)$redirectUrl, '?&');
                if ($guildNoteFeedback !== '') {
                    $redirectUrl .= (strpos($redirectUrl, '?') === false ? '?' : '&') . 'guild_note_saved=1';
                    if ($guildNoteWriteMode !== '') {
                        $redirectUrl .= '&guild_note_mode=' . urlencode($guildNoteWriteMode);
                    }
                }
                if ($guildMotdFeedback !== '') {
                    $redirectUrl .= (strpos($redirectUrl, '?') === false ? '?' : '&') . 'guild_motd_saved=1';
                    if ($guildMotdWriteMode !== '') {
                        $redirectUrl .= '&guild_motd_mode=' . urlencode($guildMotdWriteMode);
                    }
                }
                if (!headers_sent()) {
                    header('Location: ' . $redirectUrl);
                    exit;
                }
                $GLOBALS['spp_guild_redirect'] = $redirectUrl;
            }
        }

        $guildFlavorProfiles = [
            'leveling' => [
                'label' => 'Leveling',
                'desc'  => 'Quests, grinds, repairs, trains. Behaves like a real leveling player.',
                'co'    => '+dps,+dps assist,-threat,+custom::say',
                'nc'    => '+rpg,+quest,+grind,+loot,+wander,+custom::say',
                'react' => '',
            ],
            'quest' => [
                'label' => 'Quest',
                'desc'  => 'NPC-focused. Moves purposefully between quest hubs, fishes while traveling.',
                'co'    => '+dps,+dps assist,-threat,+custom::say',
                'nc'    => '+rpg,+rpg quest,+loot,+tfish,+wander,+custom::say',
                'react' => '',
            ],
            'pvp' => [
                'label' => 'PvP',
                'desc'  => 'Aggressive. Queues battlegrounds, roams for enemy players, duels.',
                'co'    => '+dps,+dps assist,+threat,+boost,+pvp,+duel,+custom::say',
                'nc'    => '+rpg,+wander,+bg,+custom::say',
                'react' => '+pvp',
            ],
            'farming' => [
                'label' => 'Farming',
                'desc'  => 'Silent resource gatherers. Mining, herbing, fishing. No questing.',
                'co'    => '+dps,-threat',
                'nc'    => '+gather,+grind,+loot,+tfish,+wander,+rpg maintenance',
                'react' => '',
            ],
            'default' => [
                'label' => 'Default',
                'desc'  => 'Clears all overrides. Bots fall back to server-wide config.',
                'co'    => '',
                'nc'    => '',
                'react' => '',
            ],
        ];

        $flavorFeedback = '';
        $flavorError    = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guild_form_action']) && $_POST['guild_form_action'] === 'save_guild_flavor') {
            spp_require_csrf('guild_page');
            if (!$isSelectedGuildLeader && !$isGm) {
                $flavorError = 'Only the guild leader can change the bot strategy flavor.';
            } else {
                $newFlavor = isset($_POST['guild_flavor']) ? trim((string)$_POST['guild_flavor']) : '';
                if (!array_key_exists($newFlavor, $guildFlavorProfiles)) {
                    $flavorError = 'Invalid flavor selected.';
                } else {
                    $stmt = $charsPdo->prepare("SELECT COUNT(*) FROM {$realmDB}.guild_member WHERE guildid=?");
                    $stmt->execute([(int)$guildId]);
                    $memberCount = (int)$stmt->fetchColumn();

                    if ($memberCount <= 0) {
                        $flavorError = 'Guild has no members.';
                    } else {
                        $sqlFallbackError = '';
                        $sqlFallbackOk = spp_guild_apply_flavor_sql_fallback($charsPdo, (int)$guildId, $guildFlavorProfiles[$newFlavor], $sqlFallbackError);
                        if (!$sqlFallbackOk) {
                            $flavorError = 'The guild flavor update failed.';
                            if ($sqlFallbackError !== '') {
                                $flavorError .= ' ' . $sqlFallbackError;
                            }
                        } else {
                            $flavorFeedback = 'Guild flavor set to <strong>' . htmlspecialchars($guildFlavorProfiles[$newFlavor]['label']) . '</strong> for ' . $memberCount . ' members via DB write. Bots will apply the new strategies on next relog.';
                        }
                    }
                }
            }
        }

        $currentFlavor = 'default';
        $stmt = $charsPdo->prepare(
            "SELECT `key`, value FROM {$realmDB}.ai_playerbot_db_store
             WHERE preset='default'
             AND guid = (SELECT guid FROM {$realmDB}.guild_member WHERE guildid=? LIMIT 1)"
        );
        $stmt->execute([(int)$guildId]);
        $sampleOverrides = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $sampleOverrides[$row['key']] = $row['value'];
        }
        if (!empty($sampleOverrides)) {
            foreach ($guildFlavorProfiles as $flavorKey => $fp) {
                if ($flavorKey === 'default') continue;
                if (($sampleOverrides['co'] ?? '') === $fp['co'] && ($sampleOverrides['nc'] ?? '') === $fp['nc']) {
                    $currentFlavor = $flavorKey;
                    break;
                }
            }
            if ($currentFlavor === 'default') $currentFlavor = 'custom';
        }

        if (isset($_GET['guild_note_saved']) && (int)$_GET['guild_note_saved'] === 1) {
            $guildNoteFeedback = 'Guild notes updated.';
            if (isset($_GET['guild_note_mode']) && (string)$_GET['guild_note_mode'] === 'sql_fallback') {
                $guildNoteFeedback .= ' SOAP was unavailable, so a direct SQL fallback write was used. This is less safe while the world server is offline.';
            }
        }
        if (isset($_GET['guild_motd_saved']) && (int)$_GET['guild_motd_saved'] === 1) {
            $guildMotdFeedback = 'Guild MOTD updated.';
            if (isset($_GET['guild_motd_mode']) && (string)$_GET['guild_motd_mode'] === 'sql_fallback') {
                $guildMotdFeedback .= ' SOAP was unavailable, so a direct SQL fallback write was used. This is less safe while the world server is offline.';
            }
        }

        $guildEstablishedLabel = 'Unknown';
        try {
            $createdColumn = null;
            foreach (array('createdate', 'create_date', 'created_at') as $candidateColumn) {
                $stmt = $charsPdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME='guild' AND COLUMN_NAME=?");
                $stmt->execute([$realmDB, $candidateColumn]);
                $columnExists = $stmt->fetchColumn();
                if ((int)$columnExists > 0) {
                    $createdColumn = $candidateColumn;
                    break;
                }
            }

            if ($createdColumn !== null) {
                $stmt = $charsPdo->prepare("SELECT `{$createdColumn}` FROM {$realmDB}.guild WHERE guildid=?");
                $stmt->execute([(int)$guildId]);
                $createdValue = $stmt->fetchColumn();
                if (!empty($createdValue) && $createdValue !== '0000-00-00 00:00:00') {
                    $createdTs = is_numeric($createdValue) ? (int)$createdValue : strtotime((string)$createdValue);
                    if ($createdTs > 0) {
                        $guildEstablishedLabel = date('M j, Y', $createdTs);
                    }
                }
            }

            if ($guildEstablishedLabel === 'Unknown') {
                $eventTimeColumn = null;
                if (spp_db_table_exists($charsPdo, 'guild_eventlog')) {
                    foreach (array('TimeStamp', 'timestamp', 'time', 'event_time') as $candidateColumn) {
                        if (spp_db_column_exists($charsPdo, 'guild_eventlog', $candidateColumn)) {
                            $eventTimeColumn = $candidateColumn;
                            break;
                        }
                    }

                    if ($eventTimeColumn !== null) {
                        $stmt = $charsPdo->prepare("SELECT MIN(`{$eventTimeColumn}`) FROM {$realmDB}.guild_eventlog WHERE guildid=?");
                        $stmt->execute([(int)$guildId]);
                        $firstEventValue = $stmt->fetchColumn();
                        if (!empty($firstEventValue)) {
                            $firstEventTs = is_numeric($firstEventValue) ? (int)$firstEventValue : strtotime((string)$firstEventValue);
                            if ($firstEventTs > 0) {
                                $guildEstablishedLabel = date('M j, Y', $firstEventTs);
                            }
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('[guild] Failed resolving established date: ' . $e->getMessage());
        }

        $stmt = $charsPdo->prepare("SELECT guid, name, race, class, level, gender FROM {$realmDB}.characters WHERE guid=?");
        $stmt->execute([(int)$guild['leaderguid']]);
        $leader = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $charsPdo->prepare("
            SELECT
              c.guid,
              c.name,
              c.online,
              c.race,
              c.class,
              c.level,
              c.gender,
              gm.rank,
              gm.pnote,
              gm.offnote,
              gr.rname AS rank_name
            FROM {$realmDB}.guild_member gm
            LEFT JOIN {$realmDB}.characters c ON gm.guid = c.guid
            LEFT JOIN {$realmDB}.guild_rank gr ON gr.guildid = gm.guildid AND gr.rid = gm.rank
            WHERE gm.guildid=?
            ORDER BY gm.rank ASC, c.level DESC, c.name ASC
        ");
        $stmt->execute([(int)$guildId]);
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!is_array($members)) {
            $members = [];
        }

        if (!empty($guildOrderDraftNotes)) {
            foreach ($members as &$member) {
                $memberGuid = (int)($member['guid'] ?? 0);
                if (array_key_exists($memberGuid, $guildOrderDraftNotes)) {
                    $member['offnote'] = $guildOrderDraftNotes[$memberGuid];
                }
            }
            unset($member);
        }

        $shareFallbackEnabled = !empty($guildSharePreview['entries']) && empty($guildSharePreview['errors']);
        $guildOrdersPreview = spp_guild_build_order_preview_rows($members, $shareFallbackEnabled);
        if (!empty($guildOrderValidationByGuid)) {
            foreach ($guildOrdersPreview as &$orderPreviewRow) {
                $orderGuid = (int)($orderPreviewRow['guid'] ?? 0);
                if (isset($guildOrderValidationByGuid[$orderGuid])) {
                    $orderPreviewRow['validation_error'] = $guildOrderValidationByGuid[$orderGuid];
                }
            }
            unset($orderPreviewRow);
        }

        $memberAverageItemLevels = [];
        if (!empty($members)) {
            $memberIds = array_values(array_unique(array_map(static function ($member) {
                return (int)($member['guid'] ?? 0);
            }, $members)));
            $memberIds = array_values(array_filter($memberIds));

            if (!empty($memberIds)) {
                $memberIdSql = implode(',', $memberIds);
                try {
                    $stmt = $charsPdo->prepare("
                        SELECT
                          ci.guid,
                          ROUND(AVG(it.ItemLevel), 1) AS avg_item_level
                        FROM {$realmDB}.character_inventory ci
                        INNER JOIN {$realmWorldDB}.item_template it ON it.entry = ci.item_template
                        WHERE ci.guid IN ({$memberIdSql})
                          AND ci.bag = 0
                          AND ci.slot BETWEEN 0 AND 18
                          AND ci.slot NOT IN (3, 18)
                          AND ci.item_template > 0
                        GROUP BY ci.guid
                    ");
                    $stmt->execute([]);
                    $itemLevelRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (is_array($itemLevelRows)) {
                        foreach ($itemLevelRows as $itemLevelRow) {
                            $memberAverageItemLevels[(int)$itemLevelRow['guid']] = round((float)$itemLevelRow['avg_item_level'], 1);
                        }
                    }
                } catch (Throwable $e) {
                    error_log('[guild] Failed loading average item levels: ' . $e->getMessage());
                }
            }
        }

        $guildMembers = count($members);
        $avgLevel = 0;
        $maxLevel = 0;
        $guildAverageItemLevelTotal = 0.0;
        $guildAverageItemLevelCount = 0;
        $classBreakdown = [];
        $classDisplayOrder = [1, 2, 3, 4, 5, 7, 8, 9, 11, 6];
        $rankOptions = [];

        foreach ($members as $member) {
            $level = (int)($member['level'] ?? 0);
            $avgLevel += $level;
            if ($level > $maxLevel) $maxLevel = $level;

            $memberItemLevel = (float)($memberAverageItemLevels[(int)($member['guid'] ?? 0)] ?? 0);
            if ($memberItemLevel > 0) {
                $guildAverageItemLevelTotal += $memberItemLevel;
                $guildAverageItemLevelCount++;
            }

            $classId = (int)($member['class'] ?? 0);
            if (!isset($classBreakdown[$classId])) $classBreakdown[$classId] = 0;
            $classBreakdown[$classId]++;

            $rankId = (int)($member['rank'] ?? 0);
            if (!isset($rankOptions[$rankId])) {
                $rankOptions[$rankId] = !empty($member['rank_name']) ? $member['rank_name'] : ('Rank ' . $rankId);
            }
        }

        $avgLevel = $guildMembers > 0 ? round($avgLevel / $guildMembers, 1) : 0;
        $guildAverageItemLevel = $guildAverageItemLevelCount > 0 ? round($guildAverageItemLevelTotal / $guildAverageItemLevelCount, 1) : 0;
        $maxGuildRankId = !empty($rankOptions) ? max(array_keys($rankOptions)) : 0;
        $orderedClassBreakdown = [];
        foreach ($classDisplayOrder as $classId) {
            if (!empty($classBreakdown[$classId])) {
                $orderedClassBreakdown[$classId] = $classBreakdown[$classId];
            }
        }
        foreach ($classBreakdown as $classId => $classCount) {
            if (!isset($orderedClassBreakdown[$classId])) {
                $orderedClassBreakdown[$classId] = $classCount;
            }
        }

        $factionName = ($leader && in_array((int)$leader['race'], $allianceRaces, true)) ? 'Alliance' : 'Horde';
        $factionSlug = strtolower($factionName);
        $crest = spp_modern_faction_logo_url($factionSlug);
        $heroBg = spp_modern_guild_image_url($factionSlug . '_guild.jpg');
        $pageBg = $heroBg;
        $motd = trim((string)$guild['motd']) !== '' ? $guild['motd'] : 'No message set.';
        $meetingPreview = spp_admin_playerbots_parse_meeting_directive((string)($guild['motd'] ?? ''));
        $meetingLocationOptions = spp_admin_playerbots_meeting_location_options(
            is_array($realmMap[$realmId] ?? null) ? $realmMap[$realmId] : array(),
            (string)($meetingPreview['location'] ?? '')
        );

        $selectedName = trim($_GET['name'] ?? '');
        $selectedClass = isset($_GET['class']) ? (int)$_GET['class'] : -1;
        $selectedRank = isset($_GET['rank']) ? (int)$_GET['rank'] : -1;
        $selectedMax = isset($_GET['maxonly']) ? 1 : 0;
        $p = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
        $itemsPerPage = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : 25;
        $sortBy = strtolower(trim($_GET['sort'] ?? 'rank'));
        $sortDir = strtoupper(trim($_GET['dir'] ?? 'ASC'));
        $allowedSorts = array('status', 'name', 'race', 'class', 'level', 'ilvl', 'rank');
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'rank';
        }
        if ($sortDir !== 'ASC' && $sortDir !== 'DESC') {
            $sortDir = 'ASC';
        }

        $filteredMembers = [];
        foreach ($members as $member) {
            if ($selectedName !== '' && stripos((string)$member['name'], $selectedName) === false) continue;
            if ($selectedClass > 0 && (int)$member['class'] !== $selectedClass) continue;
            if ($selectedRank >= 0 && (int)$member['rank'] !== $selectedRank) continue;
            if ($selectedMax && (int)$member['level'] < $maxLevel) continue;
            $filteredMembers[] = $member;
        }

        if (!empty($filteredMembers)) {
            usort($filteredMembers, function ($left, $right) use ($sortBy, $sortDir, $classNames, $raceNames, $memberAverageItemLevels) {
                return spp_guild_roster_sort_compare($left, $right, $sortBy, $sortDir, $classNames, $raceNames, $memberAverageItemLevels);
            });
        }

        $totalMembers = count($filteredMembers);
        $pageCount = max(1, (int)ceil($totalMembers / $itemsPerPage));
        if ($p > $pageCount) $p = $pageCount;
        $offset = ($p - 1) * $itemsPerPage;
        $membersPage = array_slice($filteredMembers, $offset, $itemsPerPage);
        $resultStart = $totalMembers > 0 ? $offset + 1 : 0;
        $resultEnd = min($offset + $itemsPerPage, $totalMembers);

        $baseUrl = 'index.php?n=server&sub=guild&realm=' . $realmId . '&guildid=' . $guildId . '&per_page=' . $itemsPerPage . '&tab=' . urlencode($guildActiveTab);
        if ($selectedName !== '') $baseUrl .= '&name=' . urlencode($selectedName);
        if ($selectedClass > 0) $baseUrl .= '&class=' . $selectedClass;
        if ($selectedRank >= 0) $baseUrl .= '&rank=' . $selectedRank;
        if ($selectedMax) $baseUrl .= '&maxonly=1';
        $guildOverviewUrl = 'index.php?n=server&sub=guild&realm=' . $realmId . '&guildid=' . $guildId . '&tab=overview';
        $guildLeaderUrl = 'index.php?n=server&sub=guild&realm=' . $realmId . '&guildid=' . $guildId . '&tab=leader';
        if ($selectedName !== '') {
            $guildOverviewUrl .= '&name=' . urlencode($selectedName);
            $guildLeaderUrl .= '&name=' . urlencode($selectedName);
        }
        if ($selectedClass > 0) {
            $guildOverviewUrl .= '&class=' . $selectedClass;
            $guildLeaderUrl .= '&class=' . $selectedClass;
        }
        if ($selectedRank >= 0) {
            $guildOverviewUrl .= '&rank=' . $selectedRank;
            $guildLeaderUrl .= '&rank=' . $selectedRank;
        }
        if ($selectedMax) {
            $guildOverviewUrl .= '&maxonly=1';
            $guildLeaderUrl .= '&maxonly=1';
        }
        if ($sortBy !== 'rank' || $sortDir !== 'ASC') {
            $guildOverviewUrl .= '&sort=' . urlencode($sortBy) . '&dir=' . urlencode($sortDir);
            $guildLeaderUrl .= '&sort=' . urlencode($sortBy) . '&dir=' . urlencode($sortDir);
        }
        if ($p > 1) {
            $guildOverviewUrl .= '&p=' . $p;
            $guildLeaderUrl .= '&p=' . $p;
        }

        $maxBreakdown = $classBreakdown ? max($classBreakdown) : 1;
        $guildClassLevelBuckets = [];
        foreach ($members as $member) {
            $classId = (int)($member['class'] ?? 0);
            $level = (int)($member['level'] ?? 0);
            if ($classId <= 0 || $level <= 0) {
                continue;
            }
            if (!isset($guildClassLevelBuckets[$classId])) {
                $guildClassLevelBuckets[$classId] = [];
            }
            $guildClassLevelBuckets[$classId][] = $level;
        }

        $guildClassLevelCards = [];
        $guildMedianLevelMax = 0;
        foreach ($orderedClassBreakdown as $classId => $classCount) {
            $levels = $guildClassLevelBuckets[$classId] ?? [];
            sort($levels, SORT_NUMERIC);
            $levelCount = count($levels);
            $medianLevel = 0;
            if ($levelCount > 0) {
                $middle = (int)floor(($levelCount - 1) / 2);
                if ($levelCount % 2 === 0) {
                    $medianLevel = (int)round(($levels[$middle] + $levels[$middle + 1]) / 2);
                } else {
                    $medianLevel = (int)$levels[$middle];
                }
            }
            $guildMedianLevelMax = max($guildMedianLevelMax, $medianLevel);
            $guildClassLevelCards[$classId] = $medianLevel;
        }

        $guildOrderValidationFeedback = spp_guild_build_order_validation_feedback($guildOrdersPreview, $guildSharePreview, $meetingPreview);
        $guildShareStatusMeta = spp_guild_orders_share_status_meta($guildSharePreview, $guildShareBlock);
        $guildMeetingStatusMeta = spp_guild_orders_meeting_status_meta($meetingPreview);
        $guildShareFallbackLabel = $shareFallbackEnabled ? 'Auto via Share' : 'No Order';
        $orderPreview = $guildOrdersPreview;
        $sharePreview = $guildSharePreview;
        $guildOrderPreview = $guildOrdersPreview;
        $guildOrdersState = array(
            'examples' => $guildOrdersExamples,
            'fallback_summary' => $guildOrdersFallbackSummary,
            'panel_visible' => $guildOrdersPanelVisible,
            'share_block' => $guildShareBlock,
            'share_preview' => $guildSharePreview,
            'share_preview_rows' => $guildSharePreviewRows,
            'share_status_meta' => $guildShareStatusMeta,
            'share_fallback_label' => $guildShareFallbackLabel,
            'meeting_preview' => $meetingPreview,
            'meeting_status_meta' => $guildMeetingStatusMeta,
            'meeting_location_options' => $meetingLocationOptions,
            'order_preview' => $guildOrdersPreview,
            'validation_feedback' => $guildOrderValidationFeedback,
        );

        $guildOrderShareBlock = trim((string)($guildOrderShareBlock ?? ($guildInfo ?? ($guild['info'] ?? ''))));
        $guildOrderSharePreview = array('errors' => array(), 'entries' => array());
        if ($guildOrderShareBlock !== '') {
            $guildOrderSharePreview = spp_admin_playerbots_validate_share_block($guildOrderShareBlock);
        }

        $guildOrderMeetingPreview = is_array($meetingPreview ?? null) ? $meetingPreview : array();
        $guildOrderRows = array();
        $guildOrderStats = array(
            'manual' => 0,
            'share-fallback' => 0,
            'skip' => 0,
            'invalid' => 0,
        );

        foreach (is_array($members ?? null) ? $members : array() as $member) {
            $officerNote = trim((string)($member['offnote'] ?? ''));
            $parsedOrder = spp_guild_orders_parse_note_row($officerNote);
            $statusKey = (string)($parsedOrder['status_key'] ?? 'invalid');
            if (!isset($guildOrderStats[$statusKey])) {
                $guildOrderStats[$statusKey] = 0;
            }
            $guildOrderStats[$statusKey]++;

            $guildOrderRows[] = array(
                'guid' => (int)($member['guid'] ?? 0),
                'name' => (string)($member['name'] ?? ''),
                'offnote' => $officerNote,
                'parsed_type' => (string)($parsedOrder['parsed_type'] ?? 'Invalid'),
                'target' => (string)($parsedOrder['target'] ?? ''),
                'amount' => (string)($parsedOrder['amount'] ?? ''),
                'status_key' => $statusKey,
                'status_label' => (string)($parsedOrder['status_label'] ?? 'Invalid'),
                'normalized' => (string)($parsedOrder['normalized'] ?? ''),
                'error' => (string)($parsedOrder['error'] ?? ''),
                'is_valid' => !empty($parsedOrder['is_valid']),
                'is_blank' => !empty($parsedOrder['is_blank']),
            );
        }

        $guildOrderSummary = array(
            'manual' => (int)($guildOrderStats['manual'] ?? 0),
            'share_fallback' => (int)($guildOrderStats['share-fallback'] ?? 0),
            'skip' => (int)($guildOrderStats['skip'] ?? 0),
            'invalid' => (int)($guildOrderStats['invalid'] ?? 0),
        );

        $guildOrderPreviewRows = is_array($guildOrdersState['order_preview'] ?? null) ? $guildOrdersState['order_preview'] : array();
        $guildOrderPreviewByGuid = array();
        foreach ($guildOrderPreviewRows as $guildOrderPreviewRow) {
            $guildOrderPreviewByGuid[(int)($guildOrderPreviewRow['guid'] ?? 0)] = $guildOrderPreviewRow;
        }

        $isCompactLeaderRoster = false;

        return get_defined_vars();
    }
}
