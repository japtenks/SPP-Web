<?php
if (INCLUDED !== true) {
    exit;
}

require_once __DIR__ . '/admin.populationdirector.helpers.php';

if (!function_exists('spp_admin_populationdirector_parse_nullable_float')) {
    function spp_admin_populationdirector_parse_nullable_float($value): ?float
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        if (!is_numeric($value)) {
            return null;
        }
        return (float)$value;
    }
}

if (!function_exists('spp_admin_populationdirector_parse_nullable_datetime')) {
    function spp_admin_populationdirector_parse_nullable_datetime($value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        try {
            $dt = new DateTimeImmutable($value);
            return $dt->format('Y-m-d H:i:s');
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('spp_admin_populationdirector_handle_action')) {
    function spp_admin_populationdirector_handle_action(PDO $masterPdo, array $realmDbMap): array
    {
        $state = array(
            'notice' => '',
            'warnings' => array(),
            'errors' => array(),
            'saved_snapshot_id' => 0,
        );

        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            return $state;
        }

        $action = trim((string)($_POST['populationdirector_action'] ?? ''));
        if ($action === '') {
            return $state;
        }

        spp_require_csrf('admin_populationdirector');
        spp_admin_populationdirector_ensure_runtime_tables($masterPdo);

        $realmId = (int)($_POST['realm_id'] ?? ($_POST['realm'] ?? 0));
        if ($realmId <= 0 && !empty($realmDbMap)) {
            $realmId = (int)array_keys($realmDbMap)[0];
        }
        $bandKey = trim((string)($_POST['band_key'] ?? ($_POST['override_band'] ?? '')));
        $updatedBy = trim((string)($GLOBALS['user']['username'] ?? $GLOBALS['user']['name'] ?? 'admin'));

        if ($action === 'save_override' || $action === 'apply_temporary_override') {
            if ($realmId <= 0) {
                $state['errors'][] = 'Select a realm before saving an override.';
                return $state;
            }

            $overrideMinutes = max(0, (int)($_POST['override_minutes'] ?? 0));
            $expiresAt = spp_admin_populationdirector_parse_nullable_datetime($_POST['expires_at'] ?? null);
            if ($expiresAt === null && $overrideMinutes > 0) {
                try {
                    $expiresAt = (new DateTimeImmutable('now'))->modify('+' . $overrideMinutes . ' minutes')->format('Y-m-d H:i:s');
                } catch (Throwable $e) {
                    $expiresAt = null;
                }
            }

            $overrideRow = array(
                'realm_id' => $realmId,
                'band_key' => $bandKey,
                'target_override' => spp_admin_populationdirector_parse_nullable_float($_POST['target_override'] ?? ($_POST['override_target'] ?? null)),
                'pressure_override' => spp_admin_populationdirector_parse_nullable_float($_POST['pressure_override'] ?? null),
                'expires_at' => $expiresAt,
                'note' => trim((string)($_POST['note'] ?? ($_POST['override_reason'] ?? ''))),
                'updated_by' => $updatedBy,
            );

            if ($overrideRow['target_override'] === null && $overrideRow['pressure_override'] === null && $overrideRow['expires_at'] === null && trim((string)$overrideRow['note']) === '') {
                $state['errors'][] = 'Provide at least one override value, expiration, or note.';
                return $state;
            }

            if (!spp_admin_populationdirector_save_override($masterPdo, $overrideRow)) {
                $state['errors'][] = 'The population override could not be saved.';
                return $state;
            }

            spp_admin_populationdirector_write_audit($masterPdo, array(
                'realm_id' => $realmId,
                'band_key' => $bandKey,
                'event_type' => 'override_saved',
                'summary' => 'Temporary population override saved.',
                'detail_json' => json_encode($overrideRow),
                'created_by' => $updatedBy,
            ));

            $state['notice'] = 'Population override saved.';
            return $state;
        }

        if ($action === 'clear_override' || $action === 'clear_temporary_override') {
            if ($realmId <= 0) {
                $state['errors'][] = 'Select a realm before clearing an override.';
                return $state;
            }

            if (!spp_admin_populationdirector_clear_override($masterPdo, $realmId, $bandKey)) {
                $state['errors'][] = 'The population override could not be cleared.';
                return $state;
            }

            spp_admin_populationdirector_write_audit($masterPdo, array(
                'realm_id' => $realmId,
                'band_key' => $bandKey,
                'event_type' => 'override_cleared',
                'summary' => 'Temporary population override cleared.',
                'detail_json' => json_encode(array('realm_id' => $realmId, 'band_key' => $bandKey)),
                'created_by' => $updatedBy,
            ));

            $state['notice'] = 'Population override cleared.';
            return $state;
        }

        if ($action === 'refresh_snapshot') {
            if ($realmId <= 0) {
                $state['errors'][] = 'Select a realm before refreshing a snapshot.';
                return $state;
            }

            $bandCatalog = spp_admin_populationdirector_load_band_catalog($masterPdo);
            $activeBand = spp_admin_populationdirector_resolve_active_band($masterPdo, $bandCatalog);
            $realmContext = spp_admin_populationdirector_load_realm_context($masterPdo, $realmDbMap, $realmId);
            $overrideRow = spp_admin_populationdirector_load_override_row($masterPdo, $realmId, (string)($activeBand['band_key'] ?? ''));
            $charsPdo = null;
            try {
                $charsPdo = spp_get_pdo('chars', $realmId);
            } catch (Throwable $e) {
                $charsPdo = null;
            }
            $recommendations = spp_admin_populationdirector_build_recommendations($masterPdo, $charsPdo, $realmId, $activeBand, $overrideRow, $realmContext);
            $snapshotId = spp_admin_populationdirector_save_snapshot($masterPdo, array(
                'realm_id' => $realmId,
                'band_key' => (string)($activeBand['band_key'] ?? ''),
                'snapshot_label' => (string)($activeBand['band_label'] ?? ($activeBand['band_key'] ?? 'Current Band')),
                'candidate_count' => count((array)($recommendations['candidates'] ?? array())),
                'target_count' => (float)($overrideRow['target_override'] ?? $activeBand['baseline_target'] ?? 0),
                'pressure_value' => (float)($overrideRow['pressure_override'] ?? $activeBand['baseline_pressure'] ?? 0),
                'context_json' => json_encode(array(
                    'realm_context' => $realmContext,
                    'active_band' => $activeBand,
                    'override_row' => $overrideRow,
                    'warnings' => (array)($recommendations['warnings'] ?? array()),
                )),
                'recommendations_json' => json_encode($recommendations['candidates'] ?? array()),
                'created_by' => $updatedBy,
            ));

            spp_admin_populationdirector_write_audit($masterPdo, array(
                'realm_id' => $realmId,
                'band_key' => (string)($activeBand['band_key'] ?? ''),
                'event_type' => 'snapshot_saved',
                'summary' => 'Recommendation snapshot captured.',
                'detail_json' => json_encode(array('snapshot_id' => $snapshotId, 'candidate_count' => count((array)($recommendations['candidates'] ?? array())))),
                'created_by' => $updatedBy,
            ));

            $state['saved_snapshot_id'] = $snapshotId;
            $state['notice'] = $snapshotId > 0 ? 'Recommendation snapshot saved.' : 'Recommendation snapshot could not be saved, but the live plan still refreshed.';
            return $state;
        }

        $state['warnings'][] = 'Unknown Population Director action requested.';
        return $state;
    }
}
