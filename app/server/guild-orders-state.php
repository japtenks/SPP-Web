<?php

require_once __DIR__ . '/../support/db-schema.php';
require_once __DIR__ . '/../../components/admin/admin.playerbots.helpers.php';

if (!function_exists('spp_guild_orders_fetch_roster_rows')) {
    function spp_guild_orders_fetch_roster_rows(PDO $charsPdo, string $realmDB, int $guildId): array
    {
        if ($guildId <= 0) {
            return array();
        }

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
            WHERE gm.guildid = ?
            ORDER BY gm.rank ASC, c.level DESC, c.name ASC
        ");
        $stmt->execute(array((int)$guildId));

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: array();
    }
}

if (!function_exists('spp_guild_orders_fetch_guild_info_row')) {
    function spp_guild_orders_fetch_guild_info_row(PDO $charsPdo, string $realmDB, int $guildId): array
    {
        $result = array(
            'info' => '',
            'motd' => '',
        );

        if ($guildId <= 0) {
            return $result;
        }

        try {
            $hasInfoColumn = spp_db_column_exists($charsPdo, 'guild', 'info');
            $selectColumns = $hasInfoColumn ? 'info, motd' : 'motd';
            $stmt = $charsPdo->prepare("SELECT {$selectColumns} FROM {$realmDB}.guild WHERE guildid = ? LIMIT 1");
            $stmt->execute(array((int)$guildId));
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: array();

            if ($hasInfoColumn) {
                $result['info'] = (string)($row['info'] ?? '');
            }
            $result['motd'] = (string)($row['motd'] ?? '');
        } catch (Throwable $e) {
            error_log('[guild-orders] Failed loading guild info row: ' . $e->getMessage());
        }

        return $result;
    }
}

if (!function_exists('spp_guild_orders_share_status_meta')) {
    function spp_guild_orders_share_status_meta(array $sharePreview, string $shareBlock): array
    {
        $shareBlock = trim($shareBlock);
        $hasErrors = !empty($sharePreview['errors']);
        $hasEntries = !empty($sharePreview['entries']);

        if ($shareBlock === '') {
            return array(
                'key' => 'empty',
                'label' => 'No Share Block',
                'description' => 'No guild Share block is set.',
            );
        }

        if ($hasErrors) {
            return array(
                'key' => 'invalid',
                'label' => 'Invalid Share',
                'description' => (string)($sharePreview['errors'][0] ?? 'Share block contains validation errors.'),
            );
        }

        if ($hasEntries) {
            return array(
                'key' => 'valid',
                'label' => 'Share Ready',
                'description' => 'Guild Share demand is parsed and ready.',
            );
        }

        return array(
            'key' => 'empty',
            'label' => 'No Share Block',
            'description' => 'No guild Share block is set.',
        );
    }
}

if (!function_exists('spp_guild_orders_meeting_status_meta')) {
    function spp_guild_orders_meeting_status_meta(array $meetingPreview): array
    {
        if (empty($meetingPreview['found'])) {
            return array(
                'key' => 'empty',
                'label' => 'No Meeting',
                'description' => 'No meeting directive is set.',
            );
        }

        if (empty($meetingPreview['valid'])) {
            return array(
                'key' => 'invalid',
                'label' => 'Invalid Meeting',
                'description' => (string)($meetingPreview['error'] ?? 'Meeting directive is invalid.'),
            );
        }

        return array(
            'key' => 'valid',
            'label' => 'Meeting Ready',
            'description' => (string)($meetingPreview['display'] ?? 'Meeting directive is parsed and ready.'),
        );
    }
}

if (!function_exists('spp_guild_orders_build_member_order_preview')) {
    function spp_guild_orders_build_member_order_preview(array $members, bool $assumeShareFallback = true): array
    {
        $rows = array();
        $validationItems = array();
        $validCount = 0;
        $invalidCount = 0;
        $shareFallbackCount = 0;

        foreach ($members as $member) {
            $guid = (int)($member['guid'] ?? 0);
            $offnote = trim((string)($member['offnote'] ?? ''));
            $parsed = spp_admin_playerbots_validate_order_note($offnote);
            $statusMeta = spp_admin_playerbots_order_status_meta($parsed, $assumeShareFallback);
            $typeLabel = spp_admin_playerbots_order_type_label($parsed, $assumeShareFallback);

            $row = array(
                'guid' => $guid,
                'name' => (string)($member['name'] ?? ''),
                'offnote' => $offnote,
                'parsed' => $parsed,
                'status_meta' => $statusMeta,
                'status_badge_key' => (string)($statusMeta['key'] ?? 'none'),
                'status_badge_label' => (string)($statusMeta['label'] ?? ''),
                'status_badge_description' => (string)($statusMeta['description'] ?? ''),
                'status_badge_class' => 'is-' . (string)($statusMeta['key'] ?? 'none'),
                'type_label' => $typeLabel,
                'manual_share_fallback_label' => $typeLabel,
                'is_share_fallback' => ((string)($statusMeta['key'] ?? '') === 'share_fallback'),
            );

            if (!empty($parsed['valid'])) {
                $validCount++;
                if (!empty($row['is_share_fallback'])) {
                    $shareFallbackCount++;
                }
            } else {
                $invalidCount++;
                $validationItems[] = array(
                    'guid' => $guid,
                    'name' => $row['name'],
                    'note' => $offnote,
                    'error' => (string)($parsed['error'] ?? 'Invalid order note.'),
                );
            }

            $rows[] = $row;
        }

        return array(
            'rows' => $rows,
            'validation_items' => $validationItems,
            'valid_count' => $validCount,
            'invalid_count' => $invalidCount,
            'share_fallback_count' => $shareFallbackCount,
        );
    }
}

if (!function_exists('spp_guild_orders_build_validation_feedback')) {
    function spp_guild_orders_build_validation_feedback(array $orderPreview, array $sharePreview, array $meetingPreview): array
    {
        $groups = array();
        $summaryParts = array();

        $orderValidationItems = array_values($orderPreview['validation_items'] ?? array());
        if (!empty($orderValidationItems)) {
            $groups['officer_notes'] = array(
                'label' => 'Officer Notes',
                'items' => $orderValidationItems,
            );
            $summaryParts[] = count($orderValidationItems) . ' officer note validation issue(s)';
        }

        $shareErrors = array_values($sharePreview['errors'] ?? array());
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

        $summary = empty($summaryParts)
            ? ''
            : 'Guild orders have ' . implode(', ', $summaryParts) . '.';

        return array(
            'has_errors' => !empty($groups),
            'summary' => $summary,
            'groups' => $groups,
        );
    }
}

if (!function_exists('spp_guild_orders_validate_note_submission')) {
    function spp_guild_orders_validate_note_submission(array $members, array $publicNotes, array $officerNotes): array
    {
        $membersByGuid = array();
        foreach ($members as $member) {
            $guid = (int)($member['guid'] ?? 0);
            if ($guid > 0) {
                $membersByGuid[$guid] = $member;
            }
        }

        $noteGuids = array_values(array_unique(array_merge(array_keys($publicNotes), array_keys($officerNotes))));
        if (empty($noteGuids)) {
            return array(
                'valid' => false,
                'summary' => 'No guild notes were submitted.',
                'groups' => array(),
                'valid_members' => array(),
            );
        }

        $validMembers = array();
        $validationItems = array();
        foreach ($noteGuids as $noteGuid) {
            $guid = (int)$noteGuid;
            if ($guid <= 0 || !isset($membersByGuid[$guid])) {
                $validationItems[] = array(
                    'guid' => $guid,
                    'name' => 'Unknown member',
                    'note' => '',
                    'error' => 'That guild member no longer exists in the roster.',
                );
                continue;
            }

            $member = $membersByGuid[$guid];
            $validMembers[$guid] = $member;

            $parsed = spp_admin_playerbots_validate_order_note((string)($officerNotes[$guid] ?? ''));
            if (empty($parsed['valid'])) {
                $validationItems[] = array(
                    'guid' => $guid,
                    'name' => (string)($member['name'] ?? ''),
                    'note' => trim((string)($officerNotes[$guid] ?? '')),
                    'error' => (string)($parsed['error'] ?? 'Invalid officer note.'),
                );
            }
        }

        if (!empty($validationItems)) {
            return array(
                'valid' => false,
                'summary' => count($validationItems) . ' officer note validation issue(s) must be fixed before saving.',
                'groups' => array(
                    'officer_notes' => array(
                        'label' => 'Officer Notes',
                        'items' => $validationItems,
                    ),
                ),
                'valid_members' => array(),
            );
        }

        return array(
            'valid' => true,
            'summary' => '',
            'groups' => array(),
            'valid_members' => array_values($validMembers),
        );
    }
}

if (!function_exists('spp_guild_orders_build_state')) {
    function spp_guild_orders_build_state(array $args): array
    {
        extract($args, EXTR_SKIP);

        $guild = isset($guild) && is_array($guild) ? $guild : array();
        $members = isset($members) && is_array($members) ? $members : array();
        $realmMap = isset($realmMap) && is_array($realmMap) ? $realmMap : array();
        $realmInfo = is_array($realmMap[$realmId] ?? null) ? $realmMap[$realmId] : array();

        $guildInfoRow = array(
            'info' => '',
            'motd' => (string)($guild['motd'] ?? ''),
        );
        if (isset($charsPdo) && $guildId > 0) {
            $guildInfoRow = array_merge($guildInfoRow, spp_guild_orders_fetch_guild_info_row($charsPdo, $realmDB, (int)$guildId));
        }

        $guildInfo = (string)($guildInfoRow['info'] ?? '');
        $guildMotd = trim((string)($guildInfoRow['motd'] ?? (string)($guild['motd'] ?? '')));
        $shareBlock = spp_admin_playerbots_extract_share_block($guildInfo);
        $sharePreview = spp_admin_playerbots_validate_share_block($shareBlock);
        $shareStatusMeta = spp_guild_orders_share_status_meta($sharePreview, $shareBlock);
        $meetingPreview = spp_admin_playerbots_parse_meeting_directive($guildMotd);
        $meetingStatusMeta = spp_guild_orders_meeting_status_meta($meetingPreview);
        $meetingLocationOptions = spp_admin_playerbots_meeting_location_options($realmInfo, (string)($meetingPreview['location'] ?? ''));
        $assumeShareFallback = !empty($sharePreview['entries']) && empty($sharePreview['errors']);
        $orderPreview = spp_guild_orders_build_member_order_preview($members, $assumeShareFallback);
        $validationFeedback = spp_guild_orders_build_validation_feedback($orderPreview, $sharePreview, $meetingPreview);

        return array(
            'guildInfoRow' => $guildInfoRow,
            'shareBlock' => $shareBlock,
            'sharePreview' => $sharePreview,
            'shareStatusMeta' => $shareStatusMeta,
            'shareBadgeLabel' => (string)($shareStatusMeta['label'] ?? ''),
            'shareBadgeClass' => 'is-' . (string)($shareStatusMeta['key'] ?? 'empty'),
            'shareManualFallbackLabel' => !empty($assumeShareFallback) ? 'Auto via Share' : 'No Order',
            'meetingPreview' => $meetingPreview,
            'meetingStatusMeta' => $meetingStatusMeta,
            'meetingBadgeLabel' => (string)($meetingStatusMeta['label'] ?? ''),
            'meetingBadgeClass' => 'is-' . (string)($meetingStatusMeta['key'] ?? 'empty'),
            'meetingLocationOptions' => $meetingLocationOptions,
            'orderPreview' => $orderPreview['rows'],
            'orderPreviewMeta' => array(
                'valid_count' => (int)($orderPreview['valid_count'] ?? 0),
                'invalid_count' => (int)($orderPreview['invalid_count'] ?? 0),
                'share_fallback_count' => (int)($orderPreview['share_fallback_count'] ?? 0),
            ),
            'orderValidationFeedback' => $validationFeedback,
        );
    }
}
