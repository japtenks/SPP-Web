<?php
if (INCLUDED !== true) {
    exit;
}

require_once __DIR__ . '/../../app/support/db-schema.php';

if (!function_exists('spp_admin_populationdirector_band_table_name')) {
    function spp_admin_populationdirector_band_table_name(): string
    {
        return 'website_populationdirector_bands';
    }
}

if (!function_exists('spp_admin_populationdirector_state_table_name')) {
    function spp_admin_populationdirector_state_table_name(): string
    {
        return 'website_populationdirector_state';
    }
}

if (!function_exists('spp_admin_populationdirector_override_table_name')) {
    function spp_admin_populationdirector_override_table_name(): string
    {
        return 'website_populationdirector_realm_overrides';
    }
}

if (!function_exists('spp_admin_populationdirector_snapshot_table_name')) {
    function spp_admin_populationdirector_snapshot_table_name(): string
    {
        return 'website_populationdirector_recommendation_snapshots';
    }
}

if (!function_exists('spp_admin_populationdirector_audit_table_name')) {
    function spp_admin_populationdirector_audit_table_name(): string
    {
        return 'website_populationdirector_audit_history';
    }
}

if (!function_exists('spp_admin_populationdirector_default_bands')) {
    function spp_admin_populationdirector_default_bands(): array
    {
        return array(
            array('band_key' => 'overnight', 'band_label' => 'Overnight', 'start_hour' => 0, 'end_hour' => 5, 'focus_terms' => 'stability,guard,watch,sustain,quiet', 'baseline_target' => 4, 'baseline_pressure' => 0.30, 'persona_weight' => 0.58, 'continuity_weight' => 0.27, 'pressure_weight' => 0.15, 'is_active' => 0, 'notes' => 'Low-traffic control window that prefers steady, watchful personas.'),
            array('band_key' => 'morning', 'band_label' => 'Morning', 'start_hour' => 6, 'end_hour' => 11, 'focus_terms' => 'quest,craft,gather,assist,travel', 'baseline_target' => 6, 'baseline_pressure' => 0.42, 'persona_weight' => 0.60, 'continuity_weight' => 0.25, 'pressure_weight' => 0.15, 'is_active' => 0, 'notes' => 'Player pickup hours with a bias toward helpful utility profiles.'),
            array('band_key' => 'afternoon', 'band_label' => 'Afternoon', 'start_hour' => 12, 'end_hour' => 16, 'focus_terms' => 'group,dungeon,assist,adapt,utility', 'baseline_target' => 8, 'baseline_pressure' => 0.55, 'persona_weight' => 0.62, 'continuity_weight' => 0.23, 'pressure_weight' => 0.15, 'is_active' => 0, 'notes' => 'Balanced traffic band for flexible support and group-ready bots.'),
            array('band_key' => 'prime', 'band_label' => 'Prime Time', 'start_hour' => 17, 'end_hour' => 21, 'focus_terms' => 'raid,dungeon,tactics,team,role', 'baseline_target' => 12, 'baseline_pressure' => 0.82, 'persona_weight' => 0.66, 'continuity_weight' => 0.22, 'pressure_weight' => 0.12, 'is_active' => 0, 'notes' => 'Highest activity band with a stronger preference for role-aligned personas.'),
            array('band_key' => 'late', 'band_label' => 'Late Night', 'start_hour' => 22, 'end_hour' => 23, 'focus_terms' => 'cleanup,patrol,social,steady,fallback', 'baseline_target' => 7, 'baseline_pressure' => 0.48, 'persona_weight' => 0.57, 'continuity_weight' => 0.28, 'pressure_weight' => 0.15, 'is_active' => 0, 'notes' => 'Wind-down band that keeps the realm covered without chasing churn.'),
        );
    }
}

if (!function_exists('spp_admin_populationdirector_normalize_terms')) {
    function spp_admin_populationdirector_normalize_terms(string $text): array
    {
        $text = strtolower(trim($text));
        if ($text === '') {
            return array();
        }

        $tokens = preg_split('/[^a-z0-9]+/', $text) ?: array();
        $terms = array();
        foreach ($tokens as $token) {
            $token = trim((string)$token);
            if ($token === '' || strlen($token) < 3) {
                continue;
            }
            $terms[$token] = $token;
        }

        return array_values($terms);
    }
}

if (!function_exists('spp_admin_populationdirector_keywords_from_row')) {
    function spp_admin_populationdirector_keywords_from_row(array $row): array
    {
        $keywords = spp_admin_populationdirector_normalize_terms((string)($row['focus_terms'] ?? ''));
        if (!empty($keywords)) {
            return $keywords;
        }

        return spp_admin_populationdirector_normalize_terms(trim((string)($row['band_label'] ?? '')) . ' ' . trim((string)($row['band_key'] ?? '')));
    }
}

if (!function_exists('spp_admin_populationdirector_default_band_for_hour')) {
    function spp_admin_populationdirector_default_band_for_hour(int $hour): array
    {
        $hour = max(0, min(23, $hour));
        foreach (spp_admin_populationdirector_default_bands() as $bandRow) {
            $startHour = (int)($bandRow['start_hour'] ?? 0);
            $endHour = (int)($bandRow['end_hour'] ?? 0);
            if ($hour >= $startHour && $hour <= $endHour) {
                return $bandRow;
            }
        }

        return reset(spp_admin_populationdirector_default_bands()) ?: array(
            'band_key' => 'fallback',
            'band_label' => 'Fallback',
            'start_hour' => 0,
            'end_hour' => 23,
            'focus_terms' => '',
            'baseline_target' => 0,
            'baseline_pressure' => 0.0,
            'persona_weight' => 0.60,
            'continuity_weight' => 0.25,
            'pressure_weight' => 0.15,
            'is_active' => 1,
            'notes' => '',
        );
    }
}

if (!function_exists('spp_admin_populationdirector_ensure_runtime_tables')) {
    function spp_admin_populationdirector_ensure_runtime_tables(PDO $pdo): void
    {
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `" . spp_admin_populationdirector_band_table_name() . "` (
                `band_key` VARCHAR(64) NOT NULL,
                `band_label` VARCHAR(128) NOT NULL,
                `start_hour` TINYINT UNSIGNED NOT NULL DEFAULT 0,
                `end_hour` TINYINT UNSIGNED NOT NULL DEFAULT 0,
                `focus_terms` VARCHAR(255) NOT NULL DEFAULT '',
                `baseline_target` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `baseline_pressure` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
                `persona_weight` DECIMAL(5,2) NOT NULL DEFAULT 0.60,
                `continuity_weight` DECIMAL(5,2) NOT NULL DEFAULT 0.25,
                `pressure_weight` DECIMAL(5,2) NOT NULL DEFAULT 0.15,
                `is_active` TINYINT(1) NOT NULL DEFAULT 0,
                `notes` TEXT DEFAULT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`band_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `" . spp_admin_populationdirector_state_table_name() . "` (
                `state_id` TINYINT UNSIGNED NOT NULL,
                `active_band_key` VARCHAR(64) NOT NULL DEFAULT '',
                `active_band_source` VARCHAR(32) NOT NULL DEFAULT 'derived',
                `active_since` DATETIME DEFAULT NULL,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                `updated_by` VARCHAR(64) DEFAULT NULL,
                PRIMARY KEY (`state_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `" . spp_admin_populationdirector_override_table_name() . "` (
                `realm_id` INT UNSIGNED NOT NULL,
                `band_key` VARCHAR(64) NOT NULL DEFAULT '',
                `target_override` DECIMAL(10,2) DEFAULT NULL,
                `pressure_override` DECIMAL(5,2) DEFAULT NULL,
                `expires_at` DATETIME DEFAULT NULL,
                `note` VARCHAR(255) DEFAULT NULL,
                `updated_by` VARCHAR(64) DEFAULT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`realm_id`, `band_key`),
                KEY `idx_populationdirector_override_expiry` (`expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `" . spp_admin_populationdirector_snapshot_table_name() . "` (
                `snapshot_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `realm_id` INT UNSIGNED NOT NULL,
                `band_key` VARCHAR(64) NOT NULL DEFAULT '',
                `snapshot_label` VARCHAR(128) NOT NULL DEFAULT '',
                `candidate_count` INT UNSIGNED NOT NULL DEFAULT 0,
                `target_count` DECIMAL(10,2) DEFAULT NULL,
                `pressure_value` DECIMAL(5,2) DEFAULT NULL,
                `context_json` LONGTEXT DEFAULT NULL,
                `recommendations_json` LONGTEXT DEFAULT NULL,
                `created_by` VARCHAR(64) DEFAULT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`snapshot_id`),
                KEY `idx_populationdirector_snapshot_realm` (`realm_id`, `band_key`, `created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $pdo->exec("CREATE TABLE IF NOT EXISTS `" . spp_admin_populationdirector_audit_table_name() . "` (
                `audit_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `realm_id` INT UNSIGNED NOT NULL DEFAULT 0,
                `band_key` VARCHAR(64) NOT NULL DEFAULT '',
                `event_type` VARCHAR(64) NOT NULL,
                `summary` VARCHAR(255) NOT NULL DEFAULT '',
                `detail_json` LONGTEXT DEFAULT NULL,
                `created_by` VARCHAR(64) DEFAULT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`audit_id`),
                KEY `idx_populationdirector_audit_realm` (`realm_id`, `band_key`, `created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (Throwable $e) {
            error_log('[admin.populationdirector] Failed ensuring runtime tables: ' . $e->getMessage());
        }
    }
}

if (!function_exists('spp_admin_populationdirector_load_band_catalog')) {
    function spp_admin_populationdirector_load_band_catalog(PDO $pdo): array
    {
        $bands = array();
        if (spp_db_table_exists($pdo, spp_admin_populationdirector_band_table_name())) {
            try {
                $stmt = $pdo->query('SELECT * FROM `' . spp_admin_populationdirector_band_table_name() . '` ORDER BY `start_hour` ASC, `band_key` ASC');
                foreach ((array)($stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array()) as $row) {
                    $bandKey = trim((string)($row['band_key'] ?? ''));
                    if ($bandKey === '') {
                        continue;
                    }
                    $bands[$bandKey] = array(
                        'band_key' => $bandKey,
                        'band_label' => trim((string)($row['band_label'] ?? $bandKey)),
                        'start_hour' => (int)($row['start_hour'] ?? 0),
                        'end_hour' => (int)($row['end_hour'] ?? 0),
                        'focus_terms' => trim((string)($row['focus_terms'] ?? '')),
                        'baseline_target' => (float)($row['baseline_target'] ?? 0),
                        'baseline_pressure' => (float)($row['baseline_pressure'] ?? 0),
                        'persona_weight' => (float)($row['persona_weight'] ?? 0.60),
                        'continuity_weight' => (float)($row['continuity_weight'] ?? 0.25),
                        'pressure_weight' => (float)($row['pressure_weight'] ?? 0.15),
                        'is_active' => (int)($row['is_active'] ?? 0) === 1 ? 1 : 0,
                        'notes' => (string)($row['notes'] ?? ''),
                    );
                }
            } catch (Throwable $e) {
                $bands = array();
            }
        }

        if (empty($bands)) {
            foreach (spp_admin_populationdirector_default_bands() as $bandRow) {
                $bands[(string)$bandRow['band_key']] = $bandRow;
            }
        }

        return array_values($bands);
    }
}

if (!function_exists('spp_admin_populationdirector_resolve_active_band')) {
    function spp_admin_populationdirector_resolve_active_band(PDO $pdo, array $bandCatalog): array
    {
        $stateRow = array();
        if (spp_db_table_exists($pdo, spp_admin_populationdirector_state_table_name())) {
            try {
                $stmt = $pdo->query('SELECT * FROM `' . spp_admin_populationdirector_state_table_name() . '` WHERE `state_id` = 1 LIMIT 1');
                $stateRow = $stmt ? (array)($stmt->fetch(PDO::FETCH_ASSOC) ?: array()) : array();
            } catch (Throwable $e) {
                $stateRow = array();
            }
        }

        $activeKey = trim((string)($stateRow['active_band_key'] ?? ''));
        if ($activeKey !== '') {
            foreach ($bandCatalog as $bandRow) {
                if ((string)($bandRow['band_key'] ?? '') === $activeKey) {
                    $bandRow['active_band_source'] = 'state';
                    $bandRow['active_since'] = (string)($stateRow['active_since'] ?? '');
                    return $bandRow;
                }
            }
        }

        $hour = (int)date('G');
        foreach ($bandCatalog as $bandRow) {
            $startHour = (int)($bandRow['start_hour'] ?? 0);
            $endHour = (int)($bandRow['end_hour'] ?? 0);
            if ($startHour <= $endHour && $hour >= $startHour && $hour <= $endHour) {
                $bandRow['active_band_source'] = 'derived';
                return $bandRow;
            }
            if ($startHour > $endHour && ($hour >= $startHour || $hour <= $endHour)) {
                $bandRow['active_band_source'] = 'derived';
                return $bandRow;
            }
        }

        $fallback = !empty($bandCatalog) ? (array)$bandCatalog[0] : spp_admin_populationdirector_default_band_for_hour($hour);
        $fallback['active_band_source'] = 'fallback';
        return $fallback;
    }
}

if (!function_exists('spp_admin_populationdirector_load_override_row')) {
    function spp_admin_populationdirector_load_override_row(PDO $pdo, int $realmId, string $bandKey): array
    {
        $bandKey = trim($bandKey);
        if ($realmId <= 0 || !spp_db_table_exists($pdo, spp_admin_populationdirector_override_table_name())) {
            return array('realm_id' => $realmId, 'band_key' => $bandKey, 'target_override' => null, 'pressure_override' => null, 'expires_at' => null, 'note' => '', 'updated_by' => '');
        }

        try {
            $stmt = $pdo->prepare('SELECT * FROM `' . spp_admin_populationdirector_override_table_name() . '` WHERE `realm_id` = ? AND (`band_key` = ? OR `band_key` = \'\') AND (`expires_at` IS NULL OR `expires_at` >= NOW()) ORDER BY (`band_key` = ?) DESC, `updated_at` DESC LIMIT 1');
            $stmt->execute(array($realmId, $bandKey, $bandKey));
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return array('realm_id' => $realmId, 'band_key' => (string)($row['band_key'] ?? ''), 'target_override' => isset($row['target_override']) ? (float)$row['target_override'] : null, 'pressure_override' => isset($row['pressure_override']) ? (float)$row['pressure_override'] : null, 'expires_at' => (string)($row['expires_at'] ?? ''), 'note' => (string)($row['note'] ?? ''), 'updated_by' => (string)($row['updated_by'] ?? ''));
            }
        } catch (Throwable $e) {
            return array('realm_id' => $realmId, 'band_key' => $bandKey, 'target_override' => null, 'pressure_override' => null, 'expires_at' => null, 'note' => '', 'updated_by' => '');
        }

        return array('realm_id' => $realmId, 'band_key' => $bandKey, 'target_override' => null, 'pressure_override' => null, 'expires_at' => null, 'note' => '', 'updated_by' => '');
    }
}

if (!function_exists('spp_admin_populationdirector_realmd_name')) {
    function spp_admin_populationdirector_realmd_name(array $realmInfo, int $realmId): string
    {
        $name = trim((string)($realmInfo['name'] ?? ''));
        return $name !== '' ? $name : ('Realm ' . $realmId);
    }
}

if (!function_exists('spp_admin_populationdirector_fetch_character_map')) {
    function spp_admin_populationdirector_fetch_character_map(?PDO $charsPdo, array $characterGuids): array
    {
        if (!$charsPdo instanceof PDO || empty($characterGuids) || !spp_db_table_exists($charsPdo, 'characters')) {
            return array();
        }

        $characterGuids = array_values(array_unique(array_filter(array_map('intval', $characterGuids))));
        if (empty($characterGuids)) {
            return array();
        }

        $map = array();
        try {
            foreach (array_chunk($characterGuids, 250) as $chunk) {
                $placeholders = implode(',', array_fill(0, count($chunk), '?'));
                $stmt = $charsPdo->prepare(
                    'SELECT `guid`, `name`, `class`, `level`, `online`, `logout_time`
                     FROM `characters`
                     WHERE `guid` IN (' . $placeholders . ')'
                );
                $stmt->execute($chunk);
                foreach ((array)($stmt->fetchAll(PDO::FETCH_ASSOC) ?: array()) as $row) {
                    $guid = (int)($row['guid'] ?? 0);
                    if ($guid <= 0) {
                        continue;
                    }
                    $map[$guid] = array(
                        'guid' => $guid,
                        'name' => trim((string)($row['name'] ?? '')),
                        'class' => (int)($row['class'] ?? 0),
                        'level' => (int)($row['level'] ?? 0),
                        'online' => (int)($row['online'] ?? 0) === 1 ? 1 : 0,
                        'logout_time' => trim((string)($row['logout_time'] ?? '')),
                    );
                }
            }
        } catch (Throwable $e) {
            return array();
        }

        return $map;
    }
}

if (!function_exists('spp_admin_populationdirector_load_realm_context')) {
    function spp_admin_populationdirector_load_realm_context(PDO $masterPdo, array $realmDbMap, int $realmId): array
    {
        $realmInfo = (array)($realmDbMap[$realmId] ?? array());
        $realmdRow = array();
        if (spp_db_table_exists($masterPdo, 'realmlist')) {
            try {
                $stmt = $masterPdo->prepare('SELECT * FROM `realmlist` WHERE `id` = ? LIMIT 1');
                $stmt->execute(array($realmId));
                $realmdRow = (array)($stmt->fetch(PDO::FETCH_ASSOC) ?: array());
            } catch (Throwable $e) {
                $realmdRow = array();
            }
        }

        $charsPdo = null;
        try {
            $charsPdo = spp_get_pdo('chars', $realmId);
        } catch (Throwable $e) {
            $charsPdo = null;
        }

        $onlineCharacters = 0;
        $onlineBots = 0;
        $botCandidates = 0;
        if ($charsPdo instanceof PDO && spp_db_table_exists($charsPdo, 'characters')) {
            try {
                $onlineCharacters = (int)$charsPdo->query("SELECT COUNT(*) FROM `characters` WHERE `online` = 1")->fetchColumn();
            } catch (Throwable $e) {
                $onlineCharacters = 0;
            }
        }

        if ($charsPdo instanceof PDO
            && spp_db_table_exists($masterPdo, 'website_identities')
            && spp_db_column_exists($masterPdo, 'website_identities', 'character_guid')) {
            try {
                $stmt = $masterPdo->prepare("SELECT `character_guid` FROM `website_identities` WHERE `realm_id` = ? AND (`identity_type` = 'bot_character' OR `is_bot` = 1) AND `character_guid` IS NOT NULL AND `character_guid` > 0");
                $stmt->execute(array($realmId));
                $characterGuids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN, 0) ?: array());
                $characterMap = spp_admin_populationdirector_fetch_character_map($charsPdo, $characterGuids);
                foreach ($characterMap as $characterRow) {
                    if ((int)($characterRow['online'] ?? 0) === 1) {
                        $onlineBots++;
                    }
                }
            } catch (Throwable $e) {
                $onlineBots = 0;
            }
        }

        if (spp_db_table_exists($masterPdo, 'website_identities')) {
            try {
                $stmt = $masterPdo->prepare("SELECT COUNT(*) FROM `website_identities` WHERE `realm_id` = ? AND (`identity_type` = 'bot_character' OR `is_bot` = 1)");
                $stmt->execute(array($realmId));
                $botCandidates = (int)$stmt->fetchColumn();
            } catch (Throwable $e) {
                $botCandidates = 0;
            }
        }

        return array(
            'realm_id' => $realmId,
            'realm_name' => spp_admin_populationdirector_realmd_name($realmdRow ?: $realmInfo, $realmId),
            'realm_address' => trim((string)($realmdRow['address'] ?? ($realmInfo['address'] ?? ''))),
            'realm_port' => (int)($realmdRow['port'] ?? ($realmInfo['port'] ?? 0)),
            'realmd_db' => trim((string)($realmInfo['realmd'] ?? '')),
            'chars_db' => trim((string)($realmInfo['chars'] ?? '')),
            'population' => trim((string)($realmdRow['population'] ?? '')),
            'online_characters' => $onlineCharacters,
            'online_bots' => $onlineBots,
            'bot_candidates' => $botCandidates,
            'updated_at' => trim((string)($realmdRow['updated_at'] ?? '')),
        );
    }
}

if (!function_exists('spp_admin_populationdirector_fetch_candidate_signature')) {
    function spp_admin_populationdirector_fetch_candidate_signature(PDO $masterPdo, array $candidateRow): string
    {
        $profileTable = function_exists('spp_identity_profile_table_name')
            ? spp_identity_profile_table_name()
            : 'website_identity_profiles';
        if (!spp_db_table_exists($masterPdo, $profileTable)) {
            return '';
        }
        if (!spp_db_column_exists($masterPdo, $profileTable, 'identity_id')
            || !spp_db_column_exists($masterPdo, $profileTable, 'signature')) {
            return '';
        }

        $identityId = (int)($candidateRow['identity_id'] ?? 0);
        if ($identityId <= 0) {
            return '';
        }

        try {
            $stmt = $masterPdo->prepare('SELECT `signature` FROM `' . $profileTable . '` WHERE `identity_id` = ? LIMIT 1');
            $stmt->execute(array($identityId));
            $signature = $stmt->fetchColumn();
            return $signature !== false ? trim((string)$signature) : '';
        } catch (Throwable $e) {
            return '';
        }
    }
}

if (!function_exists('spp_admin_populationdirector_current_day_tokens')) {
    function spp_admin_populationdirector_current_day_tokens(): array
    {
        $full = strtolower((string)date('D'));
        $map = array(
            'mon' => array('mon', 'monday'),
            'tue' => array('tue', 'tues', 'tuesday'),
            'wed' => array('wed', 'wednesday'),
            'thu' => array('thu', 'thur', 'thurs', 'thursday'),
            'fri' => array('fri', 'friday'),
            'sat' => array('sat', 'saturday'),
            'sun' => array('sun', 'sunday'),
        );
        return $map[$full] ?? array($full);
    }
}

if (!function_exists('spp_admin_populationdirector_preferred_day_score')) {
    function spp_admin_populationdirector_preferred_day_score(string $preferredDays): float
    {
        $preferredDays = strtolower(trim($preferredDays));
        if ($preferredDays === '' || $preferredDays === 'daily' || $preferredDays === 'flexible') {
            return 0.60;
        }

        $currentTokens = spp_admin_populationdirector_current_day_tokens();
        foreach ($currentTokens as $token) {
            if (strpos($preferredDays, $token) !== false) {
                return 1.0;
            }
        }

        if (strpos($preferredDays, 'weekend') !== false) {
            return in_array(date('N'), array('6', '7'), true) ? 1.0 : 0.20;
        }
        if (strpos($preferredDays, 'weekdays') !== false) {
            return (int)date('N') <= 5 ? 1.0 : 0.20;
        }

        return 0.25;
    }
}

if (!function_exists('spp_admin_populationdirector_preferred_hour_score')) {
    function spp_admin_populationdirector_preferred_hour_score(string $preferredHours): float
    {
        $preferredHours = trim($preferredHours);
        if ($preferredHours === '' || strcasecmp($preferredHours, 'flexible') === 0) {
            return 0.60;
        }

        if (preg_match('/(\d{1,2})\s*:\s*(\d{2})\s*-\s*(\d{1,2})\s*:\s*(\d{2})/', $preferredHours, $matches)) {
            $startHour = max(0, min(23, (int)$matches[1]));
            $endHour = max(0, min(23, (int)$matches[3]));
            $currentHour = (int)date('G');
            $inWindow = $startHour <= $endHour
                ? ($currentHour >= $startHour && $currentHour <= $endHour)
                : ($currentHour >= $startHour || $currentHour <= $endHour);
            return $inWindow ? 1.0 : 0.15;
        }

        return 0.35;
    }
}

if (!function_exists('spp_admin_populationdirector_play_style_score')) {
    function spp_admin_populationdirector_play_style_score(string $playStyleKey, array $bandRow): float
    {
        $playStyleKey = strtolower(trim($playStyleKey));
        $bandKey = strtolower(trim((string)($bandRow['band_key'] ?? '')));
        if ($playStyleKey === '') {
            return 0.45;
        }

        $preferredMap = array(
            'overnight' => array('night_owl', 'balanced', 'support'),
            'morning' => array('social', 'crafting', 'support'),
            'afternoon' => array('balanced', 'support', 'grinder'),
            'prime' => array('pvp', 'grinder', 'balanced'),
            'late' => array('night_owl', 'social', 'weekend'),
        );

        $choices = $preferredMap[$bandKey] ?? array('balanced');
        if (in_array($playStyleKey, $choices, true)) {
            return 1.0;
        }

        return in_array($playStyleKey, array('balanced', 'support', 'social'), true) ? 0.60 : 0.30;
    }
}

if (!function_exists('spp_admin_populationdirector_extract_identity_traits')) {
    function spp_admin_populationdirector_extract_identity_traits(array $candidateRow, string $signature): array
    {
        $traits = spp_admin_populationdirector_normalize_terms($signature);
        if (empty($traits)) {
            $traits = spp_admin_populationdirector_normalize_terms(trim((string)($candidateRow['display_name'] ?? '')) . ' ' . trim((string)($candidateRow['identity_type'] ?? '')));
        }

        return array_slice($traits, 0, 12);
    }
}

if (!function_exists('spp_admin_populationdirector_score_candidate')) {
    function spp_admin_populationdirector_score_candidate(array $candidateRow, array $bandRow, array $overrideRow, array $realmContext): array
    {
        $signature = trim((string)($candidateRow['signature'] ?? ''));
        $playStyleKey = (string)($candidateRow['play_style_key'] ?? '');
        $preferredDays = (string)($candidateRow['preferred_days'] ?? '');
        $preferredHours = (string)($candidateRow['preferred_hours'] ?? '');
        $focusTerms = spp_admin_populationdirector_keywords_from_row($bandRow);
        $traits = spp_admin_populationdirector_extract_identity_traits($candidateRow, $signature);
        $matched = array_values(array_intersect($traits, $focusTerms));
        $personaWeight = (float)($bandRow['persona_weight'] ?? 0.60);
        $continuityWeight = (float)($bandRow['continuity_weight'] ?? 0.25);
        $pressureWeight = (float)($bandRow['pressure_weight'] ?? 0.15);
        $target = $overrideRow['target_override'] !== null
            ? (float)$overrideRow['target_override']
            : (float)($bandRow['baseline_target'] ?? 0.0);
        $pressure = $overrideRow['pressure_override'] !== null
            ? (float)$overrideRow['pressure_override']
            : (float)($bandRow['baseline_pressure'] ?? 0.0);
        $online = (int)($candidateRow['is_online'] ?? 0) === 1;
        $termScore = empty($focusTerms) ? (empty($traits) ? 0.0 : 0.4) : (count($matched) / max(1, count($focusTerms)));
        $styleScore = spp_admin_populationdirector_play_style_score($playStyleKey, $bandRow);
        $dayScore = spp_admin_populationdirector_preferred_day_score($preferredDays);
        $hourScore = spp_admin_populationdirector_preferred_hour_score($preferredHours);
        $personaScore = (($termScore * 0.20) + ($styleScore * 0.35) + ($dayScore * 0.20) + ($hourScore * 0.25));
        if ($signature !== '' && $personaScore > 0) {
            $personaScore = min(1.0, $personaScore + 0.1);
        }
        $score = ($personaWeight * $personaScore) + ($continuityWeight * ($online ? 1.0 : 0.0)) + ($pressureWeight * max(0.0, min(1.0, $pressure)));
        if ($online) {
            $score += 0.05;
        }

        $onlineBots = (int)($realmContext['online_bots'] ?? 0);
        $targetShortfall = max(0.0, $target - $onlineBots);
        if ($targetShortfall > 0) {
            $score += min(0.10, $targetShortfall / max(1.0, $target * 10));
        }

        $explanations = array();
        if ($playStyleKey !== '') {
            $explanations[] = 'Play style ' . $playStyleKey . ' is being weighted against the active ' . (string)($bandRow['band_label'] ?? 'band') . ' window.';
        } elseif (!empty($matched)) {
            $explanations[] = 'Persona-first match: ' . implode(', ', array_slice($matched, 0, 4)) . ' appears in the profile signature.';
        } else {
            $explanations[] = 'No explicit play-style traits were available, so the score falls back to seeded defaults and identity metadata.';
        }

        if ($preferredDays !== '' || $preferredHours !== '') {
            $dayText = $preferredDays !== '' ? $preferredDays : 'fallback days';
            $hourText = $preferredHours !== '' ? $preferredHours : 'fallback hours';
            $explanations[] = 'Preferred window ' . $dayText . ' / ' . $hourText . ' contributes directly to the recommendation.';
        }

        if ($online) {
            $explanations[] = 'Already online, so continuity bias keeps this bot ahead of cold-start candidates.';
        } else {
            $explanations[] = 'Currently offline, so it only wins if its persona fit is strong enough to justify a bring-up.';
        }

        $explanations[] = sprintf(
            'Band target %.2f with pressure %.2f leaves %d online bots against a target shortfall of %.2f.',
            $target,
            max(0.0, min(1.0, $pressure)),
            $onlineBots,
            $targetShortfall
        );

        return array(
            'identity_id' => (int)($candidateRow['identity_id'] ?? 0),
            'display_name' => trim((string)($candidateRow['display_name'] ?? '')),
            'character_guid' => (int)($candidateRow['character_guid'] ?? 0),
            'is_online' => $online ? 1 : 0,
            'signature' => $signature,
            'play_style_key' => $playStyleKey,
            'preferred_days' => $preferredDays,
            'preferred_hours' => $preferredHours,
            'traits' => $traits,
            'matched_terms' => $matched,
            'persona_score' => round($personaScore, 4),
            'continuity_score' => $online ? 1.0 : 0.0,
            'pressure_score' => round(max(0.0, min(1.0, $pressure)), 4),
            'final_score' => round($score, 4),
            'target_count' => round($target, 2),
            'pressure_value' => round(max(0.0, min(1.0, $pressure)), 2),
            'explanations' => $explanations,
        );
    }
}

if (!function_exists('spp_admin_populationdirector_build_recommendations')) {
    function spp_admin_populationdirector_build_recommendations(PDO $masterPdo, ?PDO $charsPdo, int $realmId, array $bandRow, array $overrideRow, array $realmContext): array
    {
        if ($realmId <= 0) {
            return array('candidates' => array(), 'warnings' => array('No realm was selected.'));
        }

        if (!$masterPdo instanceof PDO || !spp_db_table_exists($masterPdo, 'website_identities')) {
            return array('candidates' => array(), 'warnings' => array('The website_identities table is missing, so no bot candidates can be scored.'));
        }

        try {
            $stmt = $masterPdo->prepare(
                "SELECT i.`identity_id`, i.`identity_type`, i.`owner_account_id`, i.`realm_id`, i.`character_guid`,
                        i.`display_name`, i.`identity_key`, i.`guild_id`, i.`is_bot`, i.`is_active`
                 FROM `website_identities` i
                 WHERE i.`realm_id` = ?
                   AND (i.`identity_type` = 'bot_character' OR i.`is_bot` = 1)
                 ORDER BY i.`display_name` ASC, i.`identity_id` ASC"
            );
            $stmt->execute(array($realmId));
            $candidateRows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: array();
        } catch (Throwable $e) {
            return array('candidates' => array(), 'warnings' => array('The bot identity query failed: ' . $e->getMessage()));
        }

        $characterMap = spp_admin_populationdirector_fetch_character_map(
            $charsPdo,
            array_map(static function (array $row): int {
                return (int)($row['character_guid'] ?? 0);
            }, $candidateRows)
        );

        $candidates = array();
        foreach ($candidateRows as $candidateRow) {
            $guid = (int)($candidateRow['character_guid'] ?? 0);
            if ($guid > 0 && isset($characterMap[$guid])) {
                $candidateRow = array_merge($candidateRow, array(
                    'character_name' => (string)($characterMap[$guid]['name'] ?? ''),
                    'character_class' => (int)($characterMap[$guid]['class'] ?? 0),
                    'character_level' => (int)($characterMap[$guid]['level'] ?? 0),
                    'is_online' => (int)($characterMap[$guid]['online'] ?? 0),
                    'logout_time' => (string)($characterMap[$guid]['logout_time'] ?? ''),
                ));
            } else {
                $candidateRow = array_merge($candidateRow, array(
                    'character_name' => '',
                    'character_class' => 0,
                    'character_level' => 0,
                    'is_online' => 0,
                    'logout_time' => '',
                ));
            }
            $candidateRow['signature'] = spp_admin_populationdirector_fetch_candidate_signature($masterPdo, $candidateRow);
            if (function_exists('spp_get_identity_play_habit_traits')) {
                $traitRow = spp_get_identity_play_habit_traits((int)($candidateRow['identity_id'] ?? 0), $candidateRow);
                $candidateRow = array_merge($candidateRow, $traitRow);
            }
            $candidates[] = spp_admin_populationdirector_score_candidate($candidateRow, $bandRow, $overrideRow, $realmContext);
        }

        usort($candidates, static function (array $left, array $right): int {
            $scoreCompare = ($right['final_score'] ?? 0) <=> ($left['final_score'] ?? 0);
            if ($scoreCompare !== 0) {
                return $scoreCompare;
            }
            $onlineCompare = (int)($right['is_online'] ?? 0) <=> (int)($left['is_online'] ?? 0);
            if ($onlineCompare !== 0) {
                return $onlineCompare;
            }
            return strcmp((string)($left['display_name'] ?? ''), (string)($right['display_name'] ?? ''));
        });

        return array('candidates' => $candidates, 'warnings' => array());
    }
}

if (!function_exists('spp_admin_populationdirector_write_audit')) {
    function spp_admin_populationdirector_write_audit(PDO $pdo, array $row): bool
    {
        if (!spp_db_table_exists($pdo, spp_admin_populationdirector_audit_table_name())) {
            return false;
        }

        try {
            $stmt = $pdo->prepare('INSERT INTO `' . spp_admin_populationdirector_audit_table_name() . '` (`realm_id`, `band_key`, `event_type`, `summary`, `detail_json`, `created_by`) VALUES (?, ?, ?, ?, ?, ?)');
            return $stmt->execute(array(
                (int)($row['realm_id'] ?? 0),
                (string)($row['band_key'] ?? ''),
                (string)($row['event_type'] ?? ''),
                (string)($row['summary'] ?? ''),
                (string)($row['detail_json'] ?? ''),
                (string)($row['created_by'] ?? ''),
            ));
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('spp_admin_populationdirector_save_snapshot')) {
    function spp_admin_populationdirector_save_snapshot(PDO $pdo, array $snapshotRow): int
    {
        if (!spp_db_table_exists($pdo, spp_admin_populationdirector_snapshot_table_name())) {
            return 0;
        }

        try {
            $stmt = $pdo->prepare('INSERT INTO `' . spp_admin_populationdirector_snapshot_table_name() . '` (`realm_id`, `band_key`, `snapshot_label`, `candidate_count`, `target_count`, `pressure_value`, `context_json`, `recommendations_json`, `created_by`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute(array(
                (int)($snapshotRow['realm_id'] ?? 0),
                (string)($snapshotRow['band_key'] ?? ''),
                (string)($snapshotRow['snapshot_label'] ?? ''),
                (int)($snapshotRow['candidate_count'] ?? 0),
                $snapshotRow['target_count'] !== null ? (float)$snapshotRow['target_count'] : null,
                $snapshotRow['pressure_value'] !== null ? (float)$snapshotRow['pressure_value'] : null,
                (string)($snapshotRow['context_json'] ?? ''),
                (string)($snapshotRow['recommendations_json'] ?? ''),
                (string)($snapshotRow['created_by'] ?? ''),
            ));
            return (int)$pdo->lastInsertId();
        } catch (Throwable $e) {
            return 0;
        }
    }
}

if (!function_exists('spp_admin_populationdirector_save_override')) {
    function spp_admin_populationdirector_save_override(PDO $pdo, array $overrideRow): bool
    {
        if (!spp_db_table_exists($pdo, spp_admin_populationdirector_override_table_name())) {
            return false;
        }

        try {
            $stmt = $pdo->prepare('INSERT INTO `' . spp_admin_populationdirector_override_table_name() . '` (`realm_id`, `band_key`, `target_override`, `pressure_override`, `expires_at`, `note`, `updated_by`) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `target_override` = VALUES(`target_override`), `pressure_override` = VALUES(`pressure_override`), `expires_at` = VALUES(`expires_at`), `note` = VALUES(`note`), `updated_by` = VALUES(`updated_by`)');
            return $stmt->execute(array(
                (int)($overrideRow['realm_id'] ?? 0),
                (string)($overrideRow['band_key'] ?? ''),
                $overrideRow['target_override'] !== null ? (float)$overrideRow['target_override'] : null,
                $overrideRow['pressure_override'] !== null ? (float)$overrideRow['pressure_override'] : null,
                $overrideRow['expires_at'] !== null ? (string)$overrideRow['expires_at'] : null,
                (string)($overrideRow['note'] ?? ''),
                (string)($overrideRow['updated_by'] ?? ''),
            ));
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('spp_admin_populationdirector_clear_override')) {
    function spp_admin_populationdirector_clear_override(PDO $pdo, int $realmId, string $bandKey): bool
    {
        if ($realmId <= 0 || !spp_db_table_exists($pdo, spp_admin_populationdirector_override_table_name())) {
            return false;
        }

        try {
            $stmt = $pdo->prepare('DELETE FROM `' . spp_admin_populationdirector_override_table_name() . '` WHERE `realm_id` = ? AND `band_key` = ? LIMIT 1');
            return $stmt->execute(array($realmId, trim($bandKey)));
        } catch (Throwable $e) {
            return false;
        }
    }
}
