<?php

require_once __DIR__ . '/../../components/admin/admin.populationdirector.helpers.php';
require_once __DIR__ . '/../../components/admin/admin.populationdirector.actions.php';
require_once __DIR__ . '/../../components/admin/admin.populationdirector.read.php';

if (!function_exists('spp_admin_populationdirector_format_history')) {
    function spp_admin_populationdirector_format_history(array $auditRows): array
    {
        $history = array();
        foreach ($auditRows as $row) {
            $detail = array();
            if (!empty($row['detail_json'])) {
                $decoded = json_decode((string)$row['detail_json'], true);
                if (is_array($decoded)) {
                    $detail = $decoded;
                }
            }

            $history[] = array(
                'timestamp' => (string)($row['created_at'] ?? ''),
                'action' => (string)($row['event_type'] ?? ''),
                'target' => isset($detail['target_override']) ? (string)$detail['target_override'] : '',
                'band' => (string)($row['band_key'] ?? ''),
                'reason' => (string)($detail['note'] ?? ''),
                'detail' => (string)($row['summary'] ?? ''),
                'actor' => (string)($row['created_by'] ?? ''),
            );
        }

        return $history;
    }
}

if (!function_exists('spp_admin_populationdirector_card_rows')) {
    function spp_admin_populationdirector_card_rows(array $candidates): array
    {
        $rows = array();
        foreach ($candidates as $candidate) {
            $title = trim((string)($candidate['display_name'] ?? ''));
            if ($title === '') {
                $title = 'Unnamed bot';
            }

            $body = '';
            if (!empty($candidate['explanations'][0])) {
                $body = (string)$candidate['explanations'][0];
            }

            $metaParts = array();
            $metaParts[] = 'Score ' . number_format((float)($candidate['final_score'] ?? 0), 2);
            if (!empty($candidate['play_style_key'])) {
                $metaParts[] = 'Style ' . (string)$candidate['play_style_key'];
            }
            if (!empty($candidate['preferred_hours'])) {
                $metaParts[] = 'Hours ' . (string)$candidate['preferred_hours'];
            }

            $rows[] = array(
                'title' => $title,
                'body' => $body,
                'meta' => implode(' | ', $metaParts),
            );
        }

        return $rows;
    }
}

if (!function_exists('spp_admin_populationdirector_load_page_state')) {
    function spp_admin_populationdirector_load_page_state(array $context = array()): array
    {
        $realmDbMap = (array)($context['realm_db_map'] ?? ($GLOBALS['realmDbMap'] ?? array()));
        $masterPdo = $context['master_pdo'] ?? spp_website_settings_pdo();

        $actionState = spp_admin_populationdirector_handle_action($masterPdo, $realmDbMap);
        $view = spp_admin_populationdirector_build_view($masterPdo, $realmDbMap, $actionState);

        $realmContext = (array)($view['realm_context'] ?? array());
        $activeBand = (array)($view['active_band'] ?? array());
        $overrideRow = (array)($view['override_row'] ?? array());
        $candidates = (array)(($view['recommendations']['candidates'] ?? array()));
        $selectedRealmId = (int)($view['selected_realm_id'] ?? 0);

        $targetValue = $overrideRow['target_override'] ?? $activeBand['baseline_target'] ?? null;
        $pressureValue = $overrideRow['pressure_override'] ?? $activeBand['baseline_pressure'] ?? null;
        $observedOnline = (int)($realmContext['online_bots'] ?? 0);
        $targetOnline = $targetValue !== null ? (float)$targetValue : (float)$observedOnline;
        $shortfall = max(0, (int)ceil($targetOnline - $observedOnline));
        $surplus = max(0, $observedOnline - (int)floor($targetOnline));

        $offlineCandidates = array_values(array_filter($candidates, static function (array $row): bool {
            return (int)($row['is_online'] ?? 0) !== 1;
        }));
        $onlineCandidates = array_values(array_filter($candidates, static function (array $row): bool {
            return (int)($row['is_online'] ?? 0) === 1;
        }));
        $onlineCandidatesAsc = $onlineCandidates;
        usort($onlineCandidatesAsc, static function (array $left, array $right): int {
            return ($left['final_score'] ?? 0) <=> ($right['final_score'] ?? 0);
        });

        $recommendIn = $shortfall > 0 ? array_slice($offlineCandidates, 0, min(5, $shortfall)) : array_slice($offlineCandidates, 0, 3);
        $recommendOut = $surplus > 0 ? array_slice($onlineCandidatesAsc, 0, min(5, $surplus)) : array_slice($onlineCandidatesAsc, 0, min(3, count($onlineCandidatesAsc)));

        $explanationSnippets = array();
        foreach (array_slice($candidates, 0, 5) as $candidate) {
            $details = array_slice((array)($candidate['explanations'] ?? array()), 0, 2);
            $explanationSnippets[] = array(
                'title' => (string)($candidate['display_name'] ?? 'Candidate'),
                'meta' => 'Score ' . number_format((float)($candidate['final_score'] ?? 0), 2),
                'body' => implode(' ', $details),
            );
        }

        $targetReason = trim(implode(' ', array_filter(array(
            !empty($activeBand['band_label']) ? ('Band ' . (string)$activeBand['band_label'] . ' is active.') : '',
            isset($activeBand['baseline_target']) ? ('Baseline target is ' . (string)$activeBand['baseline_target'] . '.') : '',
            isset($pressureValue) ? ('Pressure is ' . number_format((float)$pressureValue, 2) . '.') : '',
            !empty($activeBand['notes']) ? (string)$activeBand['notes'] : '',
            !empty($overrideRow['note']) ? ('Override note: ' . (string)$overrideRow['note']) : '',
        ))));

        return array(
            'intro' => array(
                'eyebrow' => 'Planner / Control Room',
                'title' => 'Population Director',
                'body' => 'Inspect the current realm target, compare persona-first recommendations, and apply only temporary target or pressure overrides in v1.',
            ),
            'selected_realm_id' => $selectedRealmId,
            'realm_options' => (array)($view['realm_options'] ?? array()),
            'current_target' => array(
                'label' => 'Current Target',
                'value' => $targetValue !== null ? number_format((float)$targetValue, 1) : 'Unavailable',
                'detail' => 'Observed ' . $observedOnline . ' online bots',
            ),
            'observed_online' => array(
                'label' => 'Observed Online',
                'value' => (string)$observedOnline,
                'detail' => 'Realm ' . (string)($realmContext['realm_name'] ?? ('Realm ' . $selectedRealmId)),
            ),
            'active_band' => array(
                'label' => 'Active Band',
                'value' => (string)($activeBand['band_label'] ?? ($activeBand['band_key'] ?? 'Unavailable')),
                'detail' => sprintf(
                    '%02d:00-%02d:00',
                    (int)($activeBand['start_hour'] ?? 0),
                    (int)($activeBand['end_hour'] ?? 0)
                ),
            ),
            'target_reason' => array(
                'title' => 'Why this target exists',
                'body' => $targetReason !== '' ? $targetReason : 'No active band rationale is available yet.',
            ),
            'override_state' => array(
                'label' => 'Override State',
                'value' => ($overrideRow['target_override'] !== null || $overrideRow['pressure_override'] !== null) ? 'Active temporary override' : 'Idle',
                'detail' => $overrideRow['target_override'] !== null || $overrideRow['pressure_override'] !== null
                    ? ('Target ' . ($overrideRow['target_override'] !== null ? number_format((float)$overrideRow['target_override'], 1) : 'auto')
                        . ' | Pressure ' . ($overrideRow['pressure_override'] !== null ? number_format((float)$overrideRow['pressure_override'], 2) : 'auto'))
                    : 'Following the active band baseline.',
                'active' => $overrideRow['target_override'] !== null || $overrideRow['pressure_override'] !== null,
                'mode' => 'realm-temporary',
                'expires_at' => (string)($overrideRow['expires_at'] ?? ''),
                'updated_at' => (string)($overrideRow['updated_at'] ?? ''),
                'updated_by' => (string)($overrideRow['updated_by'] ?? ''),
            ),
            'recommendations_in' => spp_admin_populationdirector_card_rows($recommendIn),
            'recommendations_out' => spp_admin_populationdirector_card_rows($recommendOut),
            'explanation_snippets' => $explanationSnippets,
            'history' => spp_admin_populationdirector_format_history((array)($view['audit_history'] ?? array())),
            'errors' => array_values(array_filter(array_merge(
                (array)($view['errors'] ?? array()),
                (array)($view['warnings'] ?? array())
            ))),
            'action_endpoints' => array(
                'read' => 'index.php?n=admin&sub=populationdirector&realm=' . $selectedRealmId,
                'refresh' => 'index.php?n=admin&sub=populationdirector&realm=' . $selectedRealmId,
                'override' => 'index.php?n=admin&sub=populationdirector&realm=' . $selectedRealmId,
                'clear_override' => 'index.php?n=admin&sub=populationdirector&realm=' . $selectedRealmId,
            ),
            'csrf_token' => (string)($view['admin_populationdirector_csrf_token'] ?? spp_csrf_token('admin_populationdirector')),
            'override_form' => array(
                'target' => $overrideRow['target_override'] !== null ? (string)$overrideRow['target_override'] : '',
                'pressure' => $overrideRow['pressure_override'] !== null ? (string)$overrideRow['pressure_override'] : '',
                'band' => (string)($activeBand['band_key'] ?? ''),
                'minutes' => 60,
                'reason' => (string)($overrideRow['note'] ?? ''),
            ),
            'notice' => (string)($view['notice'] ?? ''),
            'saved_snapshot_id' => (int)($view['saved_snapshot_id'] ?? 0),
        );
    }
}
