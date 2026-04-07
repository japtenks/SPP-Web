<?php

require_once __DIR__ . '/marketplace-helpers.php';

if (!function_exists('spp_marketplace_cache_fresh_ttl')) {
    function spp_marketplace_cache_fresh_ttl(): int
    {
        return 1800;
    }
}

if (!function_exists('spp_marketplace_cache_stale_ttl')) {
    function spp_marketplace_cache_stale_ttl(): int
    {
        return 7200;
    }
}

if (!function_exists('spp_marketplace_cache_folder')) {
    function spp_marketplace_cache_folder(): string
    {
        return function_exists('spp_storage_path')
            ? spp_storage_path('cache/sites')
            : dirname(__DIR__, 2) . '/storage/cache/sites';
    }
}

if (!function_exists('spp_marketplace_cache_id')) {
    function spp_marketplace_cache_id(string $key): string
    {
        return 'mp_' . md5('MARKETPLACE|' . $key);
    }
}

if (!function_exists('spp_marketplace_lock_path')) {
    function spp_marketplace_lock_path(string $key): string
    {
        return rtrim(str_replace('\\', '/', spp_marketplace_cache_folder()), '/') . '/' . spp_marketplace_cache_id($key) . '.refresh';
    }
}

if (!function_exists('spp_marketplace_context')) {
    function spp_marketplace_context($realmDbMap = null): array
    {
        $realmMap = $realmDbMap ?? ($GLOBALS['realmDbMap'] ?? null);
        $realmId = (is_array($realmMap) && !empty($realmMap)) ? spp_resolve_realm_id($realmMap) : 1;
        $realmLabel = spp_get_armory_realm_name($realmId) ?? '';
        $expansion = spp_marketplace_detect_expansion($realmId, is_array($realmMap) ? $realmMap : []);
        $craftProfessionIds = [164, 165, 171, 197, 202, 333];

        if ($expansion >= 1) {
            $craftProfessionIds[] = 755;
        }
        if ($expansion >= 2) {
            $craftProfessionIds[] = 773;
        }

        return [
            'realmMap' => $realmMap,
            'realmId' => (int)$realmId,
            'realmLabel' => (string)$realmLabel,
            'expansion' => (int)$expansion,
            'craftProfessionIds' => array_values($craftProfessionIds),
            'tierOrder' => ['Grand Master', 'Master', 'Artisan', 'Expert', 'Journeyman', 'Apprentice', 'Training'],
        ];
    }
}

if (!function_exists('spp_marketplace_realmd_db_name')) {
    function spp_marketplace_realmd_db_name(array $context): string
    {
        $realmMap = is_array($context['realmMap'] ?? null) ? $context['realmMap'] : [];
        $realmId = (int)($context['realmId'] ?? 1);
        return (string)($realmMap[$realmId]['realmd'] ?? 'classicrealmd');
    }
}

if (!function_exists('spp_marketplace_summary_cache_key')) {
    function spp_marketplace_summary_cache_key(array $context): string
    {
        return 'summary_v6_' . (int)$context['realmId'] . '_x' . (int)$context['expansion'];
    }
}

if (!function_exists('spp_marketplace_profession_cache_key')) {
    function spp_marketplace_profession_cache_key(array $context, int $skillId): string
    {
        return 'profession_v2_' . (int)$context['realmId'] . '_x' . (int)$context['expansion'] . '_s' . $skillId;
    }
}

if (!function_exists('spp_marketplace_search_cache_key')) {
    function spp_marketplace_search_cache_key(array $context): string
    {
        return 'search_v2_' . (int)$context['realmId'] . '_x' . (int)$context['expansion'];
    }
}

if (!function_exists('spp_marketplace_cache_read')) {
    function spp_marketplace_cache_read(string $key): ?array
    {
        $cache = new gCache();
        $cache->folder = spp_marketplace_cache_folder();
        $cache->contentId = spp_marketplace_cache_id($key);
        $cache->timeout = (int)ceil(spp_marketplace_cache_stale_ttl() / 60);

        if (!$cache->Valid()) {
            return null;
        }

        $payload = @unserialize((string)$cache->content);
        return is_array($payload) ? $payload : null;
    }
}

if (!function_exists('spp_marketplace_cache_write')) {
    function spp_marketplace_cache_write(string $key, array $payload): bool
    {
        $cache = new gCache();
        $cache->folder = spp_marketplace_cache_folder();
        $cache->contentId = spp_marketplace_cache_id($key);
        return $cache->cacheWrite(serialize($payload)) !== '';
    }
}

if (!function_exists('spp_marketplace_try_refresh_lock')) {
    function spp_marketplace_try_refresh_lock(string $key, int $maxAgeSeconds = 900): bool
    {
        $lockPath = spp_marketplace_lock_path($key);
        $handle = @fopen($lockPath, 'x');
        if ($handle !== false) {
            @fwrite($handle, (string)time());
            @fclose($handle);
            return true;
        }

        if (is_file($lockPath)) {
            $mtime = (int)@filemtime($lockPath);
            if ($mtime > 0 && (time() - $mtime) > $maxAgeSeconds) {
                @unlink($lockPath);
                $handle = @fopen($lockPath, 'x');
                if ($handle !== false) {
                    @fwrite($handle, (string)time());
                    @fclose($handle);
                    return true;
                }
            }
        }

        return false;
    }
}

if (!function_exists('spp_marketplace_release_refresh_lock')) {
    function spp_marketplace_release_refresh_lock(string $key): void
    {
        $lockPath = spp_marketplace_lock_path($key);
        if (is_file($lockPath)) {
            @unlink($lockPath);
        }
    }
}

if (!function_exists('spp_marketplace_cache_payload')) {
    function spp_marketplace_cache_payload($value, int $buildMs): array
    {
        $storedAt = time();
        return [
            'stored_at' => $storedAt,
            'fresh_until' => $storedAt + spp_marketplace_cache_fresh_ttl(),
            'stale_until' => $storedAt + spp_marketplace_cache_stale_ttl(),
            'build_ms' => $buildMs,
            'value' => $value,
        ];
    }
}

if (!function_exists('spp_marketplace_build_and_store')) {
    function spp_marketplace_build_and_store(string $key, callable $builder): array
    {
        $startedAt = microtime(true);
        $value = $builder();
        $payload = spp_marketplace_cache_payload($value, (int)round((microtime(true) - $startedAt) * 1000));
        spp_marketplace_cache_write($key, $payload);
        return $payload;
    }
}

if (!function_exists('spp_marketplace_schedule_refresh')) {
    function spp_marketplace_schedule_refresh(string $key, callable $builder): void
    {
        static $scheduled = [];
        if (isset($scheduled[$key]) || !spp_marketplace_try_refresh_lock($key)) {
            return;
        }

        $scheduled[$key] = true;
        register_shutdown_function(function () use ($key, $builder) {
            if (function_exists('fastcgi_finish_request')) {
                @fastcgi_finish_request();
            }

            try {
                spp_marketplace_build_and_store($key, $builder);
            } catch (Throwable $e) {
            }

            spp_marketplace_release_refresh_lock($key);
        });
    }
}

if (!function_exists('spp_marketplace_cached_snapshot')) {
    function spp_marketplace_cached_snapshot(string $key, callable $builder): array
    {
        $payload = spp_marketplace_cache_read($key);
        $now = time();

        if (is_array($payload) && isset($payload['value'])) {
            if ((int)($payload['fresh_until'] ?? 0) >= $now) {
                return ['value' => $payload['value'], 'meta' => $payload, 'pageError' => ''];
            }

            if ((int)($payload['stale_until'] ?? 0) >= $now) {
                spp_marketplace_schedule_refresh($key, $builder);
                return ['value' => $payload['value'], 'meta' => $payload, 'pageError' => ''];
            }
        }

        $lockAcquired = spp_marketplace_try_refresh_lock($key);
        try {
            $freshPayload = spp_marketplace_build_and_store($key, $builder);
            return ['value' => $freshPayload['value'], 'meta' => $freshPayload, 'pageError' => ''];
        } catch (Throwable $e) {
            if (is_array($payload) && isset($payload['value'])) {
                return ['value' => $payload['value'], 'meta' => $payload, 'pageError' => 'Marketplace data is temporarily stale.'];
            }

            return ['value' => null, 'meta' => null, 'pageError' => 'The marketplace could not be loaded right now.'];
        } finally {
            if ($lockAcquired) {
                spp_marketplace_release_refresh_lock($key);
            }
        }
    }
}

if (!function_exists('spp_marketplace_normalize_search')) {
    function spp_marketplace_normalize_search(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/i', ' ', $value);
        return trim((string)$value);
    }
}

if (!function_exists('spp_marketplace_projection_snapshot_version')) {
    function spp_marketplace_projection_snapshot_version(): string
    {
        return 'v1';
    }
}

if (!function_exists('spp_marketplace_projection_table_columns')) {
    function spp_marketplace_projection_table_columns(): array
    {
        return [
            'marketplace_bot_professions' => [
                'realm_id', 'guid', 'skill_id', 'profession_name', 'profession_icon', 'profession_description',
                'race', 'class', 'gender', 'level', 'online', 'faction', 'name', 'value', 'max',
                'profession_cap', 'tier_label', 'is_capped', 'craft_count', 'special_craft_count',
            ],
            'marketplace_bot_spells' => [
                'realm_id', 'guid', 'skill_id', 'spell_id', 'spell_name', 'item_entry', 'item_name',
                'quality', 'icon', 'required_rank', 'is_special', 'recipe_signature',
            ],
            'marketplace_profession_summary' => [
                'realm_id', 'skill_id', 'profession_name', 'profession_icon', 'profession_description',
                'profession_cap', 'total_crafters', 'has_coverage', 'summary_type', 'faction',
                'rank_position', 'guid', 'name', 'race', 'class', 'gender', 'level', 'online',
                'value', 'max', 'tier_label', 'is_capped',
            ],
            'marketplace_search_index' => [
                'realm_id', 'skill_id', 'profession_name', 'profession_icon', 'guid', 'bot_name',
                'bot_race', 'bot_class', 'bot_gender', 'bot_faction', 'bot_level', 'tier_label',
                'bot_value', 'bot_max', 'craft_count', 'special_craft_count', 'spell_id', 'spell_name',
                'item_entry', 'item_name', 'quality', 'icon', 'required_rank', 'is_special', 'search_text',
            ],
        ];
    }
}

if (!function_exists('spp_marketplace_projection_tables')) {
    function spp_marketplace_projection_tables(): array
    {
        return [
            'marketplace_bot_professions' => "CREATE TABLE IF NOT EXISTS `marketplace_bot_professions` (\n"
                . "  `realm_id` INT NOT NULL,\n  `guid` INT NOT NULL,\n  `skill_id` INT NOT NULL,\n  `profession_name` VARCHAR(128) NOT NULL,\n"
                . "  `profession_icon` VARCHAR(255) NOT NULL DEFAULT '',\n  `profession_description` TEXT NULL,\n  `race` TINYINT UNSIGNED NOT NULL DEFAULT 0,\n"
                . "  `class` TINYINT UNSIGNED NOT NULL DEFAULT 0,\n  `gender` TINYINT UNSIGNED NOT NULL DEFAULT 0,\n  `level` SMALLINT UNSIGNED NOT NULL DEFAULT 0,\n"
                . "  `online` TINYINT(1) NOT NULL DEFAULT 0,\n  `faction` VARCHAR(16) NOT NULL DEFAULT '',\n  `name` VARCHAR(64) NOT NULL,\n"
                . "  `value` INT NOT NULL DEFAULT 0,\n  `max` INT NOT NULL DEFAULT 0,\n  `profession_cap` INT NOT NULL DEFAULT 0,\n"
                . "  `tier_label` VARCHAR(32) NOT NULL DEFAULT '',\n  `is_capped` TINYINT(1) NOT NULL DEFAULT 0,\n  `craft_count` INT NOT NULL DEFAULT 0,\n"
                . "  `special_craft_count` INT NOT NULL DEFAULT 0,\n  PRIMARY KEY (`realm_id`, `skill_id`, `guid`),\n"
                . "  KEY `idx_realm_guid` (`realm_id`, `guid`),\n  KEY `idx_skill_faction_rank` (`realm_id`, `skill_id`, `faction`, `is_capped`, `value`, `max`),\n"
                . "  KEY `idx_skill_tier_rank` (`realm_id`, `skill_id`, `tier_label`, `value`, `max`),\n  KEY `idx_realm_name` (`realm_id`, `name`)\n"
                . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'marketplace_bot_professions_stage' => 'CREATE TABLE IF NOT EXISTS `marketplace_bot_professions_stage` LIKE `marketplace_bot_professions`',
            'marketplace_bot_spells' => "CREATE TABLE IF NOT EXISTS `marketplace_bot_spells` (\n"
                . "  `realm_id` INT NOT NULL,\n  `guid` INT NOT NULL,\n  `skill_id` INT NOT NULL,\n  `spell_id` INT NOT NULL,\n"
                . "  `spell_name` VARCHAR(255) NOT NULL DEFAULT '',\n  `item_entry` INT NOT NULL DEFAULT 0,\n  `item_name` VARCHAR(255) NOT NULL DEFAULT '',\n"
                . "  `quality` TINYINT UNSIGNED NOT NULL DEFAULT 1,\n  `icon` VARCHAR(255) NOT NULL DEFAULT '',\n  `required_rank` INT NOT NULL DEFAULT 0,\n"
                . "  `is_special` TINYINT(1) NOT NULL DEFAULT 0,\n  `recipe_signature` VARCHAR(255) NOT NULL DEFAULT '',\n"
                . "  PRIMARY KEY (`realm_id`, `skill_id`, `guid`, `spell_id`, `item_entry`),\n  KEY `idx_skill_guid_special` (`realm_id`, `skill_id`, `guid`, `is_special`),\n"
                . "  KEY `idx_skill_spell_name` (`realm_id`, `skill_id`, `spell_name`),\n  KEY `idx_realm_item_entry` (`realm_id`, `item_entry`),\n"
                . "  KEY `idx_realm_item_name` (`realm_id`, `item_name`(191))\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'marketplace_bot_spells_stage' => 'CREATE TABLE IF NOT EXISTS `marketplace_bot_spells_stage` LIKE `marketplace_bot_spells`',
            'marketplace_profession_summary' => "CREATE TABLE IF NOT EXISTS `marketplace_profession_summary` (\n"
                . "  `realm_id` INT NOT NULL,\n  `skill_id` INT NOT NULL,\n  `profession_name` VARCHAR(128) NOT NULL,\n  `profession_icon` VARCHAR(255) NOT NULL DEFAULT '',\n"
                . "  `profession_description` TEXT NULL,\n  `profession_cap` INT NOT NULL DEFAULT 0,\n  `total_crafters` INT NOT NULL DEFAULT 0,\n"
                . "  `has_coverage` TINYINT(1) NOT NULL DEFAULT 0,\n  `summary_type` VARCHAR(24) NOT NULL,\n  `faction` VARCHAR(16) NOT NULL DEFAULT '',\n"
                . "  `rank_position` TINYINT UNSIGNED NOT NULL DEFAULT 0,\n  `guid` INT NOT NULL DEFAULT 0,\n  `name` VARCHAR(64) NOT NULL DEFAULT '',\n"
                . "  `race` TINYINT UNSIGNED NOT NULL DEFAULT 0,\n  `class` TINYINT UNSIGNED NOT NULL DEFAULT 0,\n  `gender` TINYINT UNSIGNED NOT NULL DEFAULT 0,\n"
                . "  `level` SMALLINT UNSIGNED NOT NULL DEFAULT 0,\n  `online` TINYINT(1) NOT NULL DEFAULT 0,\n  `value` INT NOT NULL DEFAULT 0,\n"
                . "  `max` INT NOT NULL DEFAULT 0,\n  `tier_label` VARCHAR(32) NOT NULL DEFAULT '',\n  `is_capped` TINYINT(1) NOT NULL DEFAULT 0,\n"
                . "  PRIMARY KEY (`realm_id`, `skill_id`, `summary_type`, `faction`, `rank_position`),\n  KEY `idx_realm_skill` (`realm_id`, `skill_id`),\n"
                . "  KEY `idx_realm_profession_name` (`realm_id`, `profession_name`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'marketplace_profession_summary_stage' => 'CREATE TABLE IF NOT EXISTS `marketplace_profession_summary_stage` LIKE `marketplace_profession_summary`',
            'marketplace_search_index' => "CREATE TABLE IF NOT EXISTS `marketplace_search_index` (\n"
                . "  `realm_id` INT NOT NULL,\n  `skill_id` INT NOT NULL,\n  `profession_name` VARCHAR(128) NOT NULL,\n  `profession_icon` VARCHAR(255) NOT NULL DEFAULT '',\n"
                . "  `guid` INT NOT NULL,\n  `bot_name` VARCHAR(64) NOT NULL,\n  `bot_race` TINYINT UNSIGNED NOT NULL DEFAULT 0,\n  `bot_class` TINYINT UNSIGNED NOT NULL DEFAULT 0,\n"
                . "  `bot_gender` TINYINT UNSIGNED NOT NULL DEFAULT 0,\n  `bot_faction` VARCHAR(16) NOT NULL DEFAULT '',\n  `bot_level` SMALLINT UNSIGNED NOT NULL DEFAULT 0,\n"
                . "  `tier_label` VARCHAR(32) NOT NULL DEFAULT '',\n  `bot_value` INT NOT NULL DEFAULT 0,\n  `bot_max` INT NOT NULL DEFAULT 0,\n"
                . "  `craft_count` INT NOT NULL DEFAULT 0,\n  `special_craft_count` INT NOT NULL DEFAULT 0,\n  `spell_id` INT NOT NULL,\n  `spell_name` VARCHAR(255) NOT NULL DEFAULT '',\n"
                . "  `item_entry` INT NOT NULL DEFAULT 0,\n  `item_name` VARCHAR(255) NOT NULL DEFAULT '',\n  `quality` TINYINT UNSIGNED NOT NULL DEFAULT 1,\n"
                . "  `icon` VARCHAR(255) NOT NULL DEFAULT '',\n  `required_rank` INT NOT NULL DEFAULT 0,\n  `is_special` TINYINT(1) NOT NULL DEFAULT 0,\n"
                . "  `search_text` VARCHAR(1024) NOT NULL DEFAULT '',\n  PRIMARY KEY (`realm_id`, `skill_id`, `guid`, `spell_id`, `item_entry`),\n"
                . "  KEY `idx_realm_skill` (`realm_id`, `skill_id`),\n  KEY `idx_realm_bot_name` (`realm_id`, `bot_name`),\n  KEY `idx_realm_item_entry` (`realm_id`, `item_entry`),\n"
                . "  KEY `idx_realm_search_text` (`realm_id`, `search_text`(191)),\n  KEY `idx_realm_item_name` (`realm_id`, `item_name`(191)),\n  KEY `idx_realm_spell_name` (`realm_id`, `spell_name`(191))\n"
                . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            'marketplace_search_index_stage' => 'CREATE TABLE IF NOT EXISTS `marketplace_search_index_stage` LIKE `marketplace_search_index`',
            'marketplace_build_state' => "CREATE TABLE IF NOT EXISTS `marketplace_build_state` (\n"
                . "  `realm_id` INT NOT NULL,\n  `snapshot_version` VARCHAR(32) NOT NULL,\n  `build_status` VARCHAR(16) NOT NULL,\n  `started_at` DATETIME NULL,\n"
                . "  `completed_at` DATETIME NULL,\n  `duration_ms` INT NOT NULL DEFAULT 0,\n  `profession_count` INT NOT NULL DEFAULT 0,\n"
                . "  `bot_profession_rows` INT NOT NULL DEFAULT 0,\n  `bot_spell_rows` INT NOT NULL DEFAULT 0,\n  `search_rows` INT NOT NULL DEFAULT 0,\n"
                . "  `error_text` TEXT NULL,\n  PRIMARY KEY (`realm_id`)\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ];
    }
}

if (!function_exists('spp_marketplace_projection_refresh_key')) {
    function spp_marketplace_projection_refresh_key(array $context): string
    {
        return 'projection_v1_' . (int)$context['realmId'];
    }
}

if (!function_exists('spp_marketplace_ensure_projection_tables')) {
    function spp_marketplace_ensure_projection_tables(PDO $armoryPdo): void
    {
        foreach (spp_marketplace_projection_tables() as $sql) {
            $armoryPdo->exec($sql);
        }
    }
}

if (!function_exists('spp_marketplace_projection_state')) {
    function spp_marketplace_projection_state(PDO $armoryPdo, int $realmId): array
    {
        $statement = $armoryPdo->prepare(
            'SELECT `realm_id`, `snapshot_version`, `build_status`, `started_at`, `completed_at`, `duration_ms`,
                    `profession_count`, `bot_profession_rows`, `bot_spell_rows`, `search_rows`, `error_text`
             FROM `marketplace_build_state`
             WHERE `realm_id` = ?'
        );
        $statement->execute([$realmId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $row : [];
    }
}

if (!function_exists('spp_marketplace_projection_has_live_rows')) {
    function spp_marketplace_projection_has_live_rows(PDO $armoryPdo, int $realmId): bool
    {
        $statement = $armoryPdo->prepare(
            'SELECT 1
             FROM `marketplace_profession_summary`
             WHERE `realm_id` = ?
             LIMIT 1'
        );
        $statement->execute([$realmId]);
        return (bool)$statement->fetchColumn();
    }
}

if (!function_exists('spp_marketplace_projection_ready_state')) {
    function spp_marketplace_projection_ready_state(array $state, bool $hasLiveRows): bool
    {
        return $hasLiveRows
            && (string)($state['snapshot_version'] ?? '') === spp_marketplace_projection_snapshot_version()
            && (string)($state['build_status'] ?? '') === 'ready';
    }
}

if (!function_exists('spp_marketplace_projection_completed_age')) {
    function spp_marketplace_projection_completed_age(array $state): ?int
    {
        $completedAt = trim((string)($state['completed_at'] ?? ''));
        if ($completedAt === '') {
            return null;
        }

        $timestamp = strtotime($completedAt);
        if ($timestamp === false) {
            return null;
        }

        return max(0, time() - $timestamp);
    }
}

if (!function_exists('spp_marketplace_projection_log')) {
    function spp_marketplace_projection_log(array $context, string $message, array $meta = []): void
    {
        $parts = ['marketplace projection', 'realm=' . (int)$context['realmId'], $message];
        foreach ($meta as $key => $value) {
            $parts[] = $key . '=' . $value;
        }
        error_log(implode(' ', $parts));
    }
}

if (!function_exists('spp_marketplace_bulk_insert')) {
    function spp_marketplace_bulk_insert(PDO $pdo, string $table, array $columns, array $rows, int $chunkSize = 250): void
    {
        if (empty($rows)) {
            return;
        }

        $columnSql = '`' . implode('`, `', $columns) . '`';
        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            $valueSql = [];
            $params = [];
            foreach ($chunk as $row) {
                $valueSql[] = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
                foreach ($columns as $column) {
                    $params[] = $row[$column] ?? null;
                }
            }

            $statement = $pdo->prepare(
                'INSERT INTO `' . $table . '` (' . $columnSql . ') VALUES ' . implode(', ', $valueSql)
            );
            $statement->execute($params);
        }
    }
}

if (!function_exists('spp_marketplace_projection_upsert_state')) {
    function spp_marketplace_projection_upsert_state(PDO $armoryPdo, int $realmId, array $values): void
    {
        $defaults = [
            'snapshot_version' => spp_marketplace_projection_snapshot_version(),
            'build_status' => 'idle',
            'started_at' => null,
            'completed_at' => null,
            'duration_ms' => 0,
            'profession_count' => 0,
            'bot_profession_rows' => 0,
            'bot_spell_rows' => 0,
            'search_rows' => 0,
            'error_text' => null,
        ];
        $payload = $values + $defaults;

        $statement = $armoryPdo->prepare(
            'INSERT INTO `marketplace_build_state`
                (`realm_id`, `snapshot_version`, `build_status`, `started_at`, `completed_at`, `duration_ms`,
                 `profession_count`, `bot_profession_rows`, `bot_spell_rows`, `search_rows`, `error_text`)
             VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                `snapshot_version` = VALUES(`snapshot_version`),
                `build_status` = VALUES(`build_status`),
                `started_at` = VALUES(`started_at`),
                `completed_at` = VALUES(`completed_at`),
                `duration_ms` = VALUES(`duration_ms`),
                `profession_count` = VALUES(`profession_count`),
                `bot_profession_rows` = VALUES(`bot_profession_rows`),
                `bot_spell_rows` = VALUES(`bot_spell_rows`),
                `search_rows` = VALUES(`search_rows`),
                `error_text` = VALUES(`error_text`)'
        );
        $statement->execute([
            $realmId,
            (string)$payload['snapshot_version'],
            (string)$payload['build_status'],
            $payload['started_at'],
            $payload['completed_at'],
            (int)$payload['duration_ms'],
            (int)$payload['profession_count'],
            (int)$payload['bot_profession_rows'],
            (int)$payload['bot_spell_rows'],
            (int)$payload['search_rows'],
            $payload['error_text'],
        ]);
    }
}

if (!function_exists('spp_marketplace_projection_load_spell_icons')) {
    function spp_marketplace_projection_load_spell_icons(PDO $armoryPdo, array $spellMetaMap): array
    {
        $spellIconIds = [];
        foreach ($spellMetaMap as $spellRow) {
            $spellIconId = (int)($spellRow['SpellIconID'] ?? 0);
            if ($spellIconId > 0) {
                $spellIconIds[$spellIconId] = true;
            }
        }

        if (empty($spellIconIds)) {
            return [];
        }

        $iconIds = array_keys($spellIconIds);
        $placeholders = implode(',', array_fill(0, count($iconIds), '?'));
        $statement = $armoryPdo->prepare(
            'SELECT `id`, `name`
             FROM `dbc_spellicon`
             WHERE `id` IN (' . $placeholders . ')'
        );
        $statement->execute($iconIds);

        $iconMap = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $iconMap[(int)$row['id']] = (string)$row['name'];
        }

        return $iconMap;
    }
}

if (!function_exists('spp_marketplace_projection_load_item_icons')) {
    function spp_marketplace_projection_load_item_icons(PDO $armoryPdo, array $craftItemMap): array
    {
        $displayIds = [];
        foreach ($craftItemMap as $itemRow) {
            $displayId = (int)($itemRow['displayid'] ?? 0);
            if ($displayId > 0) {
                $displayIds[$displayId] = true;
            }
        }

        if (empty($displayIds)) {
            return [];
        }

        $displayIdList = array_keys($displayIds);
        $placeholders = implode(',', array_fill(0, count($displayIdList), '?'));
        $statement = $armoryPdo->prepare(
            'SELECT `id`, `name`
             FROM `dbc_itemdisplayinfo`
             WHERE `id` IN (' . $placeholders . ')'
        );
        $statement->execute($displayIdList);

        $iconMap = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $iconMap[(int)$row['id']] = (string)$row['name'];
        }

        return $iconMap;
    }
}

if (!function_exists('spp_marketplace_load_skill_meta')) {
    function spp_marketplace_load_skill_meta(PDO $armoryPdo, array $skillIds): array
    {
        if (empty($skillIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($skillIds), '?'));
        $statement = $armoryPdo->prepare(
            'SELECT sl.`id`, sl.`name`, sl.`description`, si.`name` AS `icon_name`
             FROM `dbc_skillline` sl
             LEFT JOIN `dbc_spellicon` si ON si.`id` = sl.`ref_spellicon`
             WHERE sl.`id` IN (' . $placeholders . ')
             ORDER BY sl.`name`'
        );
        $statement->execute(array_values($skillIds));

        $meta = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $meta[(int)$row['id']] = $row;
        }

        return $meta;
    }
}

if (!function_exists('spp_marketplace_load_profession_rows')) {
    function spp_marketplace_load_profession_rows(PDO $charsPdo, string $realmdDb, array $skillIds): array
    {
        if (empty($skillIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($skillIds), '?'));
        $statement = $charsPdo->prepare(
            'SELECT c.`guid`, c.`name`, c.`race`, c.`class`, c.`level`, c.`gender`, c.`online`, cs.`skill`, cs.`value`, cs.`max`
             FROM `characters` c
             INNER JOIN `character_skills` cs ON cs.`guid` = c.`guid`
             INNER JOIN `' . $realmdDb . '`.`account` a ON a.`id` = c.`account`
             LEFT JOIN `ai_playerbot_names` apn ON apn.`name` = c.`name`
             WHERE cs.`skill` IN (' . $placeholders . ')
               AND (a.`username` LIKE \'rndbot%\' OR apn.`name_id` IS NOT NULL)
             ORDER BY cs.`skill`, cs.`max` DESC, cs.`value` DESC, c.`name` ASC'
        );
        $statement->execute(array_values($skillIds));
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('spp_marketplace_specialist_compare')) {
    function spp_marketplace_specialist_compare(array $left, array $right): int
    {
        if ((int)($left['is_capped'] ?? 0) !== (int)($right['is_capped'] ?? 0)) {
            return (int)$right['is_capped'] <=> (int)$left['is_capped'];
        }
        if ((int)($left['special_recipe_count'] ?? 0) !== (int)($right['special_recipe_count'] ?? 0)) {
            return (int)$right['special_recipe_count'] <=> (int)$left['special_recipe_count'];
        }
        if ((int)($left['value'] ?? 0) !== (int)($right['value'] ?? 0)) {
            return (int)$right['value'] <=> (int)$left['value'];
        }
        if ((int)($left['max'] ?? 0) !== (int)($right['max'] ?? 0)) {
            return (int)$right['max'] <=> (int)$left['max'];
        }
        if ((int)($left['level'] ?? 0) !== (int)($right['level'] ?? 0)) {
            return (int)$right['level'] <=> (int)$left['level'];
        }

        return strnatcasecmp((string)($left['name'] ?? ''), (string)($right['name'] ?? ''));
    }
}

if (!function_exists('spp_marketplace_compact_crafter_card')) {
    function spp_marketplace_compact_crafter_card(array $crafter, int $realmId, array $extra = []): array
    {
        return [
            'guid' => (int)($crafter['guid'] ?? 0),
            'name' => (string)($crafter['name'] ?? ''),
            'race' => (int)($crafter['race'] ?? 0),
            'class' => (int)($crafter['class'] ?? 0),
            'gender' => (int)($crafter['gender'] ?? 0),
            'level' => (int)($crafter['level'] ?? 0),
            'value' => (int)($crafter['value'] ?? 0),
            'max' => (int)($crafter['max'] ?? 0),
            'is_capped' => !empty($crafter['is_capped']),
            'special_recipe_count' => (int)($crafter['special_recipe_count'] ?? 0),
            'specialties' => array_values(array_map('strval', (array)($crafter['specialties'] ?? []))),
            'online' => !empty($crafter['online']),
            'href' => spp_marketplace_character_href($realmId, (string)($crafter['name'] ?? ''), 'professions'),
            'race_icon' => spp_marketplace_race_icon_url((int)($crafter['race'] ?? 0), (int)($crafter['gender'] ?? 0)),
            'class_icon' => spp_marketplace_class_icon_url((int)($crafter['class'] ?? 0)),
            'online_label' => !empty($crafter['online']) ? 'Online' : 'Offline',
        ] + $extra;
    }
}

if (!function_exists('spp_marketplace_specialty_label_map')) {
    function spp_marketplace_specialty_label_map(array $definitions): array
    {
        $labels = [];
        foreach ($definitions as $skillId => $rows) {
            $skillId = (int)$skillId;
            foreach ((array)$rows as $row) {
                $slug = (string)($row['slug'] ?? '');
                $label = (string)($row['label'] ?? '');
                if ($slug !== '' && $label !== '') {
                    $labels[$skillId][$slug] = $label;
                }
            }
        }

        return $labels;
    }
}

if (!function_exists('spp_marketplace_collect_learned_spells_by_guid')) {
    function spp_marketplace_collect_learned_spells_by_guid(PDO $charsPdo, array $guids): array
    {
        if (empty($guids)) {
            return [[], []];
        }

        $placeholders = implode(',', array_fill(0, count($guids), '?'));
        $spellStmt = $charsPdo->prepare(
            'SELECT `guid`, `spell`
             FROM `character_spell`
             WHERE `disabled` = 0 AND `guid` IN (' . $placeholders . ')'
        );
        $spellStmt->execute(array_values($guids));

        $learnedSpellsByGuid = [];
        $learnedSpellIds = [];
        foreach ($spellStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $guid = (int)$row['guid'];
            $spellId = (int)$row['spell'];
            $learnedSpellsByGuid[$guid][$spellId] = true;
            $learnedSpellIds[$spellId] = true;
        }

        return [$learnedSpellsByGuid, array_keys($learnedSpellIds)];
    }
}

if (!function_exists('spp_marketplace_load_trainer_spell_map')) {
    function spp_marketplace_load_trainer_spell_map(PDO $worldPdo, array $skillIds): array
    {
        if (empty($skillIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($skillIds), '?'));
        $trainerMap = [];

        $trainerStmt = $worldPdo->prepare(
            'SELECT `reqskill`, `spell`, `reqskillvalue`
             FROM `npc_trainer`
             WHERE `reqskill` IN (' . $placeholders . ')'
        );
        $trainerStmt->execute(array_values($skillIds));
        foreach ($trainerStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $trainerMap[(int)$row['reqskill']][(int)$row['spell']] = (int)$row['reqskillvalue'];
        }

        $templateStmt = $worldPdo->prepare(
            'SELECT `reqskill`, `spell`, `reqskillvalue`
             FROM `npc_trainer_template`
             WHERE `reqskill` IN (' . $placeholders . ')'
        );
        $templateStmt->execute(array_values($skillIds));
        foreach ($templateStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $trainerMap[(int)$row['reqskill']][(int)$row['spell']] = (int)$row['reqskillvalue'];
        }

        return $trainerMap;
    }
}

if (!function_exists('spp_marketplace_load_bulk_spell_meta')) {
    function spp_marketplace_load_bulk_spell_meta(PDO $worldPdo, array $spellIds): array
    {
        if (empty($spellIds)) {
            return [[], [], []];
        }

        $placeholders = implode(',', array_fill(0, count($spellIds), '?'));
        $spellStmt = $worldPdo->prepare(
            'SELECT `Id`, `SpellName`, `SpellIconID`, `Effect1`, `Effect2`, `Effect3`, `EffectTriggerSpell1`, `EffectTriggerSpell2`, `EffectTriggerSpell3`, `EffectItemType1`, `EffectItemType2`, `EffectItemType3`
             FROM `spell_template`
             WHERE `Id` IN (' . $placeholders . ')'
        );
        $spellStmt->execute(array_values($spellIds));

        $spellMetaMap = [];
        $spellOutputMap = [];
        $craftedItemIds = [];
        foreach ($spellStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $spellId = (int)$row['Id'];
            $spellMetaMap[$spellId] = $row;
            foreach (['EffectItemType1', 'EffectItemType2', 'EffectItemType3'] as $field) {
                $itemId = (int)($row[$field] ?? 0);
                if ($itemId > 0) {
                    $spellOutputMap[$spellId][$itemId] = true;
                    $craftedItemIds[$itemId] = true;
                }
            }
        }

        spp_marketplace_hydrate_trigger_outputs($worldPdo, $spellMetaMap, $spellOutputMap, $craftedItemIds);

        return [$spellMetaMap, $spellOutputMap, array_keys($craftedItemIds)];
    }
}

if (!function_exists('spp_marketplace_hydrate_trigger_outputs')) {
    function spp_marketplace_hydrate_trigger_outputs(PDO $worldPdo, array &$spellMetaMap, array &$spellOutputMap, array &$craftedItemIds): void
    {
        $triggerSpellIds = [];
        foreach ($spellMetaMap as $spellRow) {
            foreach ([1, 2, 3] as $index) {
                $triggerSpellId = (int)($spellRow['EffectTriggerSpell' . $index] ?? 0);
                if ($triggerSpellId > 0) {
                    $triggerSpellIds[$triggerSpellId] = true;
                }
            }
        }
        if (empty($triggerSpellIds)) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($triggerSpellIds), '?'));
        $triggerStmt = $worldPdo->prepare(
            'SELECT `Id`, `SpellName`, `SpellIconID`, `EffectItemType1`, `EffectItemType2`, `EffectItemType3`
             FROM `spell_template`
             WHERE `Id` IN (' . $placeholders . ')'
        );
        $triggerStmt->execute(array_keys($triggerSpellIds));
        $triggerRowsById = [];
        foreach ($triggerStmt->fetchAll(PDO::FETCH_ASSOC) as $triggerRow) {
            $triggerRowsById[(int)($triggerRow['Id'] ?? 0)] = $triggerRow;
        }

        foreach ($spellMetaMap as $spellId => $spellRow) {
            if (!empty($spellOutputMap[$spellId])) {
                continue;
            }
            foreach ([1, 2, 3] as $index) {
                $triggerSpellId = (int)($spellRow['EffectTriggerSpell' . $index] ?? 0);
                if ($triggerSpellId <= 0 || empty($triggerRowsById[$triggerSpellId])) {
                    continue;
                }
                $triggerRow = $triggerRowsById[$triggerSpellId];
                if ((int)($spellMetaMap[$spellId]['SpellIconID'] ?? 0) <= 1 && (int)($triggerRow['SpellIconID'] ?? 0) > 0) {
                    $spellMetaMap[$spellId]['SpellIconID'] = (int)$triggerRow['SpellIconID'];
                }
                foreach (['EffectItemType1', 'EffectItemType2', 'EffectItemType3'] as $field) {
                    $itemId = (int)($triggerRow[$field] ?? 0);
                    if ($itemId <= 0) {
                        continue;
                    }
                    $spellOutputMap[$spellId][$itemId] = true;
                    $craftedItemIds[$itemId] = true;
                }
            }
        }
    }
}

if (!function_exists('spp_marketplace_trainer_recipe_rank_map')) {
    function spp_marketplace_trainer_recipe_rank_map(array $trainerSpellMap, array $spellMetaMap, array $spellOutputMap, array $craftItemMap): array
    {
        $rankMap = [];
        foreach ($trainerSpellMap as $skillId => $trainerSpells) {
            foreach ($trainerSpells as $spellId => $requiredRank) {
                if (!isset($spellMetaMap[$spellId])) {
                    continue;
                }
                $itemNames = [];
                foreach (array_keys($spellOutputMap[$spellId] ?? []) as $itemId) {
                    $itemName = trim((string)($craftItemMap[$itemId]['name'] ?? ''));
                    if ($itemName !== '') {
                        $itemNames[] = $itemName;
                    }
                }
                $signature = spp_marketplace_profession_recipe_signature((string)($spellMetaMap[$spellId]['SpellName'] ?? ''), $itemNames);
                if ($signature === '|') {
                    continue;
                }
                $existing = $rankMap[$skillId][$signature] ?? null;
                if ($existing === null || (int)$requiredRank < (int)$existing) {
                    $rankMap[$skillId][$signature] = (int)$requiredRank;
                }
            }
        }

        return $rankMap;
    }
}

if (!function_exists('spp_marketplace_load_bulk_item_map')) {
    function spp_marketplace_load_bulk_item_map(PDO $worldPdo, array $itemIds): array
    {
        if (empty($itemIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
        $itemStmt = $worldPdo->prepare(
            'SELECT `entry`, `name`, `Quality`, `RequiredSkill`, `RequiredSkillRank`, `displayid`
             FROM `item_template`
             WHERE `entry` IN (' . $placeholders . ')'
        );
        $itemStmt->execute(array_values($itemIds));

        $itemMap = [];
        foreach ($itemStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $itemMap[(int)$row['entry']] = $row;
        }

        return $itemMap;
    }
}

if (!function_exists('spp_marketplace_merge_spell_id_sets')) {
    function spp_marketplace_merge_spell_id_sets(array $learnedSpellIds, array $trainerSpellIds): array
    {
        $spellIds = [];
        foreach ($learnedSpellIds as $spellId) {
            $spellId = (int)$spellId;
            if ($spellId > 0) {
                $spellIds[$spellId] = true;
            }
        }
        foreach ($trainerSpellIds as $spellId => $enabled) {
            $spellId = (int)$spellId;
            if ($spellId > 0 && $enabled) {
                $spellIds[$spellId] = true;
            }
        }

        return array_keys($spellIds);
    }
}

if (!function_exists('spp_marketplace_match_specialties')) {
    function spp_marketplace_match_specialties(array $definitions, array $learnedSpellIds, array $spellMetaMap): array
    {
        if (empty($definitions)) {
            return [];
        }

        $matched = [];
        $spellNameHaystack = [];
        foreach (array_keys($learnedSpellIds) as $spellId) {
            $spellName = strtolower(trim((string)($spellMetaMap[$spellId]['SpellName'] ?? '')));
            if ($spellName !== '') {
                $spellNameHaystack[] = $spellName;
            }
        }
        $joinedNames = implode(' | ', $spellNameHaystack);

        foreach ($definitions as $definition) {
            $isMatched = false;
            foreach ((array)($definition['spell_ids'] ?? []) as $spellId) {
                if (!empty($learnedSpellIds[(int)$spellId])) {
                    $isMatched = true;
                    break;
                }
            }

            if (!$isMatched) {
                foreach ((array)($definition['keywords'] ?? []) as $keyword) {
                    if ($keyword !== '' && strpos($joinedNames, strtolower((string)$keyword)) !== false) {
                        $isMatched = true;
                        break;
                    }
                }
            }

            if ($isMatched) {
                $matched[] = (string)$definition['slug'];
            }
        }

        return $matched;
    }
}

if (!function_exists('spp_marketplace_collect_specialist_candidates')) {
    function spp_marketplace_collect_specialist_candidates(array $context, array $professionRows): array
    {
        if (empty($professionRows)) {
            return [[], []];
        }

        $realmId = (int)$context['realmId'];
        $skillIds = array_values(array_unique(array_map(static function (array $row): int {
            return (int)$row['skill'];
        }, $professionRows)));
        $guids = array_values(array_unique(array_map(static function (array $row): int {
            return (int)$row['guid'];
        }, $professionRows)));

        $charsPdo = spp_get_pdo('chars', $realmId);
        $worldPdo = spp_get_pdo('world', $realmId);

        [$learnedSpellsByGuid, $learnedSpellIds] = spp_marketplace_collect_learned_spells_by_guid($charsPdo, $guids);
        $trainerSpellMap = spp_marketplace_load_trainer_spell_map($worldPdo, $skillIds);
        $trainerSpellIds = [];
        foreach ($trainerSpellMap as $trainerSpells) {
            foreach (array_keys($trainerSpells) as $spellId) {
                $trainerSpellIds[(int)$spellId] = true;
            }
        }
        [$spellMetaMap, $spellOutputMap, $craftedItemIds] = spp_marketplace_load_bulk_spell_meta(
            $worldPdo,
            spp_marketplace_merge_spell_id_sets($learnedSpellIds, $trainerSpellIds)
        );
        $craftItemMap = spp_marketplace_load_bulk_item_map($worldPdo, $craftedItemIds);
        $trainerRecipeRankMap = spp_marketplace_trainer_recipe_rank_map($trainerSpellMap, $spellMetaMap, $spellOutputMap, $craftItemMap);
        $specialtyDefinitions = spp_marketplace_specialty_definitions((int)$context['expansion']);
        $cap = spp_marketplace_profession_cap((int)$context['expansion']);

        $candidatesBySkill = [];
        foreach ($professionRows as $row) {
            $guid = (int)$row['guid'];
            $skillId = (int)$row['skill'];
            $knownSpellIds = $learnedSpellsByGuid[$guid] ?? [];
            $specialCount = 0;

            foreach (array_keys($knownSpellIds) as $spellId) {
                if (!isset($spellMetaMap[$spellId])) {
                    continue;
                }

                $spellRow = $spellMetaMap[$spellId];
                $spellName = trim((string)($spellRow['SpellName'] ?? ''));
                $primaryItemName = '';
                $recipeItemNames = [];
                foreach (array_keys($spellOutputMap[$spellId] ?? []) as $itemId) {
                    if (!empty($craftItemMap[$itemId]['name'])) {
                        $recipeItemNames[] = (string)$craftItemMap[$itemId]['name'];
                        if ($primaryItemName === '') {
                            $primaryItemName = (string)$craftItemMap[$itemId]['name'];
                        }
                    }
                }
                $recipeSignature = spp_marketplace_profession_recipe_signature($spellName, $recipeItemNames);

                $itemRequiredSkills = [];
                foreach (array_keys($spellOutputMap[$spellId] ?? []) as $craftedItemId) {
                    $requiredSkill = (int)($craftItemMap[$craftedItemId]['RequiredSkill'] ?? 0);
                    if ($requiredSkill > 0) {
                        $itemRequiredSkills[$requiredSkill] = true;
                    }
                }
                $isAssigned = isset($trainerSpellMap[$skillId][$spellId])
                    || !empty($trainerRecipeRankMap[$skillId][$recipeSignature])
                    || (count($itemRequiredSkills) === 1 && !empty($itemRequiredSkills[$skillId]));
                if (!$isAssigned) {
                    continue;
                }

                $requiredRank = isset($trainerSpellMap[$skillId][$spellId])
                    ? (int)$trainerSpellMap[$skillId][$spellId]
                    : (int)($trainerRecipeRankMap[$skillId][$recipeSignature] ?? 0);
                if ($requiredRank <= 0 && !empty($spellOutputMap[$spellId])) {
                    foreach (array_keys($spellOutputMap[$spellId]) as $craftedItemId) {
                        if (!empty($craftItemMap[$craftedItemId]['RequiredSkillRank'])) {
                            $requiredRank = (int)$craftItemMap[$craftedItemId]['RequiredSkillRank'];
                            break;
                        }
                    }
                }
                $isTrainerRecipe = isset($trainerSpellMap[$skillId][$spellId]) || !empty($trainerRecipeRankMap[$skillId][$recipeSignature]);
                $isSpecialRecipe = spp_marketplace_is_specialization_recipe(
                    (int)$context['expansion'],
                    $skillId,
                    $spellId,
                    $spellName,
                    $primaryItemName
                ) || (!$isTrainerRecipe && $requiredRank > 0);

                if ($isSpecialRecipe) {
                    $specialCount++;
                }
            }

            $specialties = spp_marketplace_match_specialties(
                (array)($specialtyDefinitions[$skillId] ?? []),
                $knownSpellIds,
                $spellMetaMap
            );

            $candidate = [
                'guid' => $guid,
                'skill' => $skillId,
                'name' => (string)$row['name'],
                'race' => (int)$row['race'],
                'class' => (int)$row['class'],
                'gender' => (int)$row['gender'],
                'level' => (int)$row['level'],
                'online' => !empty($row['online']),
                'value' => (int)$row['value'],
                'max' => (int)$row['max'],
                'is_capped' => (int)$row['value'] >= $cap,
                'special_recipe_count' => $specialCount,
                'specialties' => $specialties,
            ];

            $candidatesBySkill[$skillId][] = $candidate;
        }

        return [$candidatesBySkill, $specialtyDefinitions];
    }
}

if (!function_exists('spp_marketplace_pick_top_specialist')) {
    function spp_marketplace_pick_top_specialist(array $candidates): ?array
    {
        if (empty($candidates)) {
            return null;
        }

        usort($candidates, 'spp_marketplace_specialist_compare');
        return $candidates[0] ?? null;
    }
}

if (!function_exists('spp_marketplace_summary_bot_compare')) {
    function spp_marketplace_summary_bot_compare(array $left, array $right): int
    {
        if ((int)$left['value'] !== (int)$right['value']) {
            return (int)$right['value'] <=> (int)$left['value'];
        }
        if ((int)$left['max'] !== (int)$right['max']) {
            return (int)$right['max'] <=> (int)$left['max'];
        }
        if ((int)$left['level'] !== (int)$right['level']) {
            return (int)$right['level'] <=> (int)$left['level'];
        }
        return strnatcasecmp((string)$left['name'], (string)$right['name']);
    }
}

if (!function_exists('spp_marketplace_detail_bot_compare')) {
    function spp_marketplace_detail_bot_compare(array $left, array $right): int
    {
        if ((int)$left['value'] !== (int)$right['value']) {
            return (int)$right['value'] <=> (int)$left['value'];
        }
        if ((int)$left['special_craft_count'] !== (int)$right['special_craft_count']) {
            return (int)$right['special_craft_count'] <=> (int)$left['special_craft_count'];
        }
        if ((int)$left['craft_count'] !== (int)$right['craft_count']) {
            return (int)$right['craft_count'] <=> (int)$left['craft_count'];
        }
        return strnatcasecmp((string)$left['name'], (string)$right['name']);
    }
}

if (!function_exists('spp_marketplace_recipe_compare')) {
    function spp_marketplace_recipe_compare(array $left, array $right): int
    {
        $rankCompare = ((int)$right['required_rank']) <=> ((int)$left['required_rank']);
        if ($rankCompare !== 0) {
            return $rankCompare;
        }
        return strnatcasecmp((string)$left['spell_name'], (string)$right['spell_name']);
    }
}

if (!function_exists('spp_marketplace_projection_profession_compare')) {
    function spp_marketplace_projection_profession_compare(array $left, array $right): int
    {
        if ((int)$left['has_coverage'] !== (int)$right['has_coverage']) {
            return (int)$right['has_coverage'] <=> (int)$left['has_coverage'];
        }
        if ((int)$left['total_crafters'] !== (int)$right['total_crafters']) {
            return (int)$right['total_crafters'] <=> (int)$left['total_crafters'];
        }
        $leftTop = (int)(($left['top_crafter']['value'] ?? 0));
        $rightTop = (int)(($right['top_crafter']['value'] ?? 0));
        if ($leftTop !== $rightTop) {
            return $rightTop <=> $leftTop;
        }

        return ((int)$left['skill_id']) <=> ((int)$right['skill_id']);
    }
}

if (!function_exists('spp_marketplace_build_projection_snapshot')) {
    function spp_marketplace_build_projection_snapshot(array $context): array
    {
        $realmId = (int)$context['realmId'];
        $startedAt = microtime(true);
        $startedAtSql = date('Y-m-d H:i:s');
        $timings = [];
        $measure = static function (string $label, callable $callback) use (&$timings) {
            $phaseStartedAt = microtime(true);
            $result = $callback();
            $timings[$label] = (int)round((microtime(true) - $phaseStartedAt) * 1000);
            return $result;
        };

        $armoryPdo = spp_get_pdo('armory', $realmId);
        spp_marketplace_ensure_projection_tables($armoryPdo);
        spp_marketplace_projection_upsert_state($armoryPdo, $realmId, [
            'build_status' => 'building',
            'started_at' => $startedAtSql,
            'completed_at' => null,
            'duration_ms' => 0,
            'profession_count' => 0,
            'bot_profession_rows' => 0,
            'bot_spell_rows' => 0,
            'search_rows' => 0,
            'error_text' => null,
        ]);

        try {
            $charsPdo = spp_get_pdo('chars', $realmId);
            $worldPdo = spp_get_pdo('world', $realmId);
            $skillMeta = spp_marketplace_load_skill_meta($armoryPdo, $context['craftProfessionIds']);
            $rows = $measure('source profession roster query', function () use ($charsPdo, $context) {
                return spp_marketplace_load_profession_rows(
                    $charsPdo,
                    spp_marketplace_realmd_db_name($context),
                    $context['craftProfessionIds']
                );
            });

            $cap = spp_marketplace_profession_cap((int)$context['expansion']);
            $botRowsBySkill = [];
            $guidSet = [];
            foreach ($rows as $row) {
                $skillId = (int)($row['skill'] ?? 0);
                $guid = (int)($row['guid'] ?? 0);
                if ($skillId <= 0 || $guid <= 0 || !isset($skillMeta[$skillId])) {
                    continue;
                }

                $guidSet[$guid] = true;
                $botRowsBySkill[$skillId][$guid] = [
                    'guid' => $guid,
                    'skill_id' => $skillId,
                    'profession_name' => (string)$skillMeta[$skillId]['name'],
                    'profession_icon' => spp_marketplace_profession_icon_url($skillId, $skillMeta[$skillId]['icon_name'] ?? ''),
                    'profession_description' => trim((string)($skillMeta[$skillId]['description'] ?? '')),
                    'race' => (int)($row['race'] ?? 0),
                    'class' => (int)($row['class'] ?? 0),
                    'gender' => (int)($row['gender'] ?? 0),
                    'level' => (int)($row['level'] ?? 0),
                    'online' => !empty($row['online']) ? 1 : 0,
                    'faction' => spp_marketplace_faction_name((int)($row['race'] ?? 0)),
                    'name' => (string)($row['name'] ?? ''),
                    'value' => (int)($row['value'] ?? 0),
                    'max' => (int)($row['max'] ?? 0),
                    'profession_cap' => $cap,
                    'tier_label' => spp_marketplace_profession_tier_label((int)($row['max'] ?? 0)),
                    'is_capped' => (int)($row['value'] ?? 0) >= $cap ? 1 : 0,
                    'craft_count' => 0,
                    'special_craft_count' => 0,
                ];
            }

            $guids = array_keys($guidSet);
            [$learnedSpellsByGuid, $learnedSpellIds] = $measure('learned spell collection', function () use ($charsPdo, $guids) {
                return spp_marketplace_collect_learned_spells_by_guid($charsPdo, $guids);
            });
            $trainerSpellMap = $measure('trainer spell loading', function () use ($worldPdo, $context) {
                return spp_marketplace_load_trainer_spell_map($worldPdo, $context['craftProfessionIds']);
            });

            $trainerSpellIds = [];
            foreach ($trainerSpellMap as $trainerSpells) {
                foreach (array_keys($trainerSpells) as $spellId) {
                    $trainerSpellIds[(int)$spellId] = true;
                }
            }

            [$spellMetaMap, $spellOutputMap, $craftedItemIds] = $measure('spell metadata hydration', function () use ($worldPdo, $learnedSpellIds, $trainerSpellIds) {
                return spp_marketplace_load_bulk_spell_meta(
                    $worldPdo,
                    spp_marketplace_merge_spell_id_sets($learnedSpellIds, $trainerSpellIds)
                );
            });
            $craftItemMap = $measure('item metadata hydration', function () use ($worldPdo, $craftedItemIds) {
                return spp_marketplace_load_bulk_item_map($worldPdo, $craftedItemIds);
            });
            $spellIconMap = spp_marketplace_projection_load_spell_icons($armoryPdo, $spellMetaMap);
            $craftIcons = spp_marketplace_projection_load_item_icons($armoryPdo, $craftItemMap);
            $trainerRecipeRankMap = spp_marketplace_trainer_recipe_rank_map($trainerSpellMap, $spellMetaMap, $spellOutputMap, $craftItemMap);

            $botSpellRows = [];
            $searchRows = [];
            $measure('profession detail assembly', function () use (&$botRowsBySkill, &$botSpellRows, &$searchRows, $context, $learnedSpellsByGuid, $spellMetaMap, $spellOutputMap, $craftItemMap, $craftIcons, $spellIconMap, $trainerSpellMap, $trainerRecipeRankMap) {
                foreach ($botRowsBySkill as $skillId => &$botsByGuid) {
                    foreach ($botsByGuid as $guid => &$botRow) {
                        $knownCrafts = [];
                        foreach (array_keys($learnedSpellsByGuid[$guid] ?? []) as $spellId) {
                            if (!isset($spellMetaMap[$spellId])) {
                                continue;
                            }

                            $spellRow = $spellMetaMap[$spellId];
                            $spellName = trim((string)($spellRow['SpellName'] ?? ''));
                            $primaryItemName = '';
                            $recipeItemNames = [];
                            foreach (array_keys($spellOutputMap[$spellId] ?? []) as $itemId) {
                                if (!empty($craftItemMap[$itemId]['name'])) {
                                    $recipeItemNames[] = (string)$craftItemMap[$itemId]['name'];
                                    if ($primaryItemName === '') {
                                        $primaryItemName = (string)$craftItemMap[$itemId]['name'];
                                    }
                                }
                            }
                            $recipeSignature = spp_marketplace_profession_recipe_signature($spellName, $recipeItemNames);

                            $itemRequiredSkills = [];
                            foreach (array_keys($spellOutputMap[$spellId] ?? []) as $itemId) {
                                $requiredSkill = (int)($craftItemMap[$itemId]['RequiredSkill'] ?? 0);
                                if ($requiredSkill > 0) {
                                    $itemRequiredSkills[$requiredSkill] = true;
                                }
                            }
                            $isAssigned = isset($trainerSpellMap[$skillId][$spellId])
                                || !empty($trainerRecipeRankMap[$skillId][$recipeSignature])
                                || (count($itemRequiredSkills) === 1 && !empty($itemRequiredSkills[$skillId]));
                            if (!$isAssigned) {
                                continue;
                            }

                            $primaryItemEntry = 0;
                            $primaryQuality = 1;
                            $primaryRequiredRank = 0;
                            $primaryIcon = '';
                            foreach (array_keys($spellOutputMap[$spellId] ?? []) as $itemId) {
                                if (!isset($craftItemMap[$itemId])) {
                                    continue;
                                }

                                $itemRow = $craftItemMap[$itemId];
                                if ($primaryItemEntry === 0) {
                                    $primaryItemEntry = $itemId;
                                    $primaryQuality = (int)($itemRow['Quality'] ?? 1);
                                    $primaryRequiredRank = (int)($itemRow['RequiredSkillRank'] ?? 0);
                                    $primaryIcon = spp_marketplace_icon_url($craftIcons[(int)($itemRow['displayid'] ?? 0)] ?? '');
                                }
                            }

                            $requiredRank = isset($trainerSpellMap[$skillId][$spellId])
                                ? (int)$trainerSpellMap[$skillId][$spellId]
                                : (!empty($trainerRecipeRankMap[$skillId][$recipeSignature]) ? (int)$trainerRecipeRankMap[$skillId][$recipeSignature] : $primaryRequiredRank);
                            $isTrainerRecipe = isset($trainerSpellMap[$skillId][$spellId]) || !empty($trainerRecipeRankMap[$skillId][$recipeSignature]);
                            $isSpecialRecipe = spp_marketplace_is_specialization_recipe(
                                (int)$context['expansion'],
                                (int)$skillId,
                                $spellId,
                                $spellName,
                                $primaryItemName
                            ) || (!$isTrainerRecipe && $requiredRank > 0);

                            $craftRow = [
                                'realm_id' => (int)$context['realmId'],
                                'guid' => (int)$guid,
                                'skill_id' => (int)$skillId,
                                'spell_id' => (int)$spellId,
                                'spell_name' => $spellName !== '' ? $spellName : ('Spell #' . $spellId),
                                'item_entry' => $primaryItemEntry,
                                'item_name' => $primaryItemName,
                                'quality' => $primaryQuality,
                                'icon' => $primaryIcon !== '' ? $primaryIcon : spp_marketplace_icon_url($spellIconMap[(int)($spellRow['SpellIconID'] ?? 0)] ?? ''),
                                'required_rank' => $requiredRank,
                                'is_special' => $isSpecialRecipe ? 1 : 0,
                                'recipe_signature' => $recipeSignature,
                            ];

                            $knownCrafts[] = $craftRow;
                        }

                        usort($knownCrafts, 'spp_marketplace_recipe_compare');
                        $botRow['craft_count'] = count($knownCrafts);
                        $botRow['special_craft_count'] = count(array_filter($knownCrafts, static function (array $craft): bool {
                            return !empty($craft['is_special']);
                        }));

                        foreach ($knownCrafts as $craftRow) {
                            $botSpellRows[] = $craftRow;
                            $searchRows[] = [
                                'realm_id' => (int)$context['realmId'],
                                'skill_id' => (int)$skillId,
                                'profession_name' => (string)$botRow['profession_name'],
                                'profession_icon' => (string)$botRow['profession_icon'],
                                'guid' => (int)$guid,
                                'bot_name' => (string)$botRow['name'],
                                'bot_race' => (int)$botRow['race'],
                                'bot_class' => (int)$botRow['class'],
                                'bot_gender' => (int)$botRow['gender'],
                                'bot_faction' => (string)$botRow['faction'],
                                'bot_level' => (int)$botRow['level'],
                                'tier_label' => (string)$botRow['tier_label'],
                                'bot_value' => (int)$botRow['value'],
                                'bot_max' => (int)$botRow['max'],
                                'craft_count' => (int)$botRow['craft_count'],
                                'special_craft_count' => (int)$botRow['special_craft_count'],
                                'spell_id' => (int)$craftRow['spell_id'],
                                'spell_name' => (string)$craftRow['spell_name'],
                                'item_entry' => (int)$craftRow['item_entry'],
                                'item_name' => (string)$craftRow['item_name'],
                                'quality' => (int)$craftRow['quality'],
                                'icon' => (string)$craftRow['icon'],
                                'required_rank' => (int)$craftRow['required_rank'],
                                'is_special' => (int)$craftRow['is_special'],
                                'search_text' => spp_marketplace_normalize_search(
                                    $botRow['profession_name'] . ' ' . $botRow['name'] . ' ' . $craftRow['spell_name'] . ' ' . $craftRow['item_name']
                                ),
                            ];
                        }
                    }
                    unset($botRow);
                }
                unset($botsByGuid);
                return true;
            });

            $botProfessionRows = [];
            foreach ($botRowsBySkill as $botsByGuid) {
                foreach ($botsByGuid as $botRow) {
                    $botProfessionRows[] = [
                        'realm_id' => $realmId,
                        'guid' => (int)$botRow['guid'],
                        'skill_id' => (int)$botRow['skill_id'],
                        'profession_name' => (string)$botRow['profession_name'],
                        'profession_icon' => (string)$botRow['profession_icon'],
                        'profession_description' => (string)$botRow['profession_description'],
                        'race' => (int)$botRow['race'],
                        'class' => (int)$botRow['class'],
                        'gender' => (int)$botRow['gender'],
                        'level' => (int)$botRow['level'],
                        'online' => (int)$botRow['online'],
                        'faction' => (string)$botRow['faction'],
                        'name' => (string)$botRow['name'],
                        'value' => (int)$botRow['value'],
                        'max' => (int)$botRow['max'],
                        'profession_cap' => (int)$botRow['profession_cap'],
                        'tier_label' => (string)$botRow['tier_label'],
                        'is_capped' => (int)$botRow['is_capped'],
                        'craft_count' => (int)$botRow['craft_count'],
                        'special_craft_count' => (int)$botRow['special_craft_count'],
                    ];
                }
            }

            $summaryRows = $measure('summary assembly', function () use ($context, $skillMeta, $botRowsBySkill, $cap, $realmId) {
                $rows = [];
                foreach ($context['craftProfessionIds'] as $skillId) {
                    $skillId = (int)$skillId;
                    if (!isset($skillMeta[$skillId])) {
                        continue;
                    }

                    $professionName = (string)$skillMeta[$skillId]['name'];
                    $allCandidates = array_values($botRowsBySkill[$skillId] ?? []);
                    usort($allCandidates, 'spp_marketplace_summary_bot_compare');
                    $topCrafter = $allCandidates[0] ?? null;

                    $rows[] = [
                        'realm_id' => $realmId,
                        'skill_id' => $skillId,
                        'profession_name' => $professionName,
                        'profession_icon' => spp_marketplace_profession_icon_url($skillId, $skillMeta[$skillId]['icon_name'] ?? ''),
                        'profession_description' => trim((string)($skillMeta[$skillId]['description'] ?? '')),
                        'profession_cap' => $cap,
                        'total_crafters' => count($allCandidates),
                        'has_coverage' => $topCrafter !== null ? 1 : 0,
                        'summary_type' => 'top_overall',
                        'faction' => '',
                        'rank_position' => 1,
                        'guid' => (int)($topCrafter['guid'] ?? 0),
                        'name' => (string)($topCrafter['name'] ?? ''),
                        'race' => (int)($topCrafter['race'] ?? 0),
                        'class' => (int)($topCrafter['class'] ?? 0),
                        'gender' => (int)($topCrafter['gender'] ?? 0),
                        'level' => (int)($topCrafter['level'] ?? 0),
                        'online' => !empty($topCrafter['online']) ? 1 : 0,
                        'value' => (int)($topCrafter['value'] ?? 0),
                        'max' => (int)($topCrafter['max'] ?? 0),
                        'tier_label' => (string)($topCrafter['tier_label'] ?? ''),
                        'is_capped' => !empty($topCrafter['is_capped']) ? 1 : 0,
                    ];

                    foreach (['Alliance', 'Horde'] as $factionName) {
                        $factionCandidates = array_values(array_filter(
                            $allCandidates,
                            static function (array $candidate) use ($factionName): bool {
                                return (string)($candidate['faction'] ?? '') === $factionName;
                            }
                        ));
                        usort($factionCandidates, 'spp_marketplace_summary_bot_compare');
                        foreach (array_slice($factionCandidates, 0, 3) as $index => $candidate) {
                            $rows[] = [
                                'realm_id' => $realmId,
                                'skill_id' => $skillId,
                                'profession_name' => $professionName,
                                'profession_icon' => spp_marketplace_profession_icon_url($skillId, $skillMeta[$skillId]['icon_name'] ?? ''),
                                'profession_description' => trim((string)($skillMeta[$skillId]['description'] ?? '')),
                                'profession_cap' => $cap,
                                'total_crafters' => count($allCandidates),
                                'has_coverage' => !empty($candidate) ? 1 : 0,
                                'summary_type' => 'top_faction',
                                'faction' => $factionName,
                                'rank_position' => $index + 1,
                                'guid' => (int)($candidate['guid'] ?? 0),
                                'name' => (string)($candidate['name'] ?? ''),
                                'race' => (int)($candidate['race'] ?? 0),
                                'class' => (int)($candidate['class'] ?? 0),
                                'gender' => (int)($candidate['gender'] ?? 0),
                                'level' => (int)($candidate['level'] ?? 0),
                                'online' => !empty($candidate['online']) ? 1 : 0,
                                'value' => (int)($candidate['value'] ?? 0),
                                'max' => (int)($candidate['max'] ?? 0),
                                'tier_label' => (string)($candidate['tier_label'] ?? ''),
                                'is_capped' => !empty($candidate['is_capped']) ? 1 : 0,
                            ];
                        }
                    }
                }

                return $rows;
            });

            $measure('search index assembly', function () {
                return true;
            });

            foreach ([
                'marketplace_bot_professions_stage',
                'marketplace_bot_spells_stage',
                'marketplace_profession_summary_stage',
                'marketplace_search_index_stage',
            ] as $stageTable) {
                $armoryPdo->prepare('DELETE FROM `' . $stageTable . '` WHERE `realm_id` = ?')->execute([$realmId]);
            }

            $measure('stage write', function () use ($armoryPdo, $botProfessionRows, $botSpellRows, $summaryRows, $searchRows) {
                $columns = spp_marketplace_projection_table_columns();
                spp_marketplace_bulk_insert($armoryPdo, 'marketplace_bot_professions_stage', $columns['marketplace_bot_professions'], $botProfessionRows);
                spp_marketplace_bulk_insert($armoryPdo, 'marketplace_bot_spells_stage', $columns['marketplace_bot_spells'], $botSpellRows);
                spp_marketplace_bulk_insert($armoryPdo, 'marketplace_profession_summary_stage', $columns['marketplace_profession_summary'], $summaryRows);
                spp_marketplace_bulk_insert($armoryPdo, 'marketplace_search_index_stage', $columns['marketplace_search_index'], $searchRows);
                return true;
            });

            $measure('live publish', function () use ($armoryPdo, $realmId) {
                foreach ([
                    'marketplace_bot_professions',
                    'marketplace_bot_spells',
                    'marketplace_profession_summary',
                    'marketplace_search_index',
                ] as $liveTable) {
                    $armoryPdo->prepare('DELETE FROM `' . $liveTable . '` WHERE `realm_id` = ?')->execute([$realmId]);
                    $armoryPdo->exec(
                        'INSERT INTO `' . $liveTable . '`
                         SELECT *
                         FROM `' . $liveTable . '_stage`
                         WHERE `realm_id` = ' . (int)$realmId
                    );
                }
                return true;
            });

            $durationMs = (int)round((microtime(true) - $startedAt) * 1000);
            spp_marketplace_projection_upsert_state($armoryPdo, $realmId, [
                'build_status' => 'ready',
                'started_at' => $startedAtSql,
                'completed_at' => date('Y-m-d H:i:s'),
                'duration_ms' => $durationMs,
                'profession_count' => count($context['craftProfessionIds']),
                'bot_profession_rows' => count($botProfessionRows),
                'bot_spell_rows' => count($botSpellRows),
                'search_rows' => count($searchRows),
                'error_text' => null,
            ]);

            spp_marketplace_projection_log($context, 'build-complete', [
                'duration_ms' => $durationMs,
                'summary_rows' => count($summaryRows),
                'bot_professions' => count($botProfessionRows),
                'bot_spells' => count($botSpellRows),
                'search_rows' => count($searchRows),
                'timings' => json_encode($timings),
            ]);

            return [
                'duration_ms' => $durationMs,
                'summary_rows' => count($summaryRows),
                'bot_profession_rows' => count($botProfessionRows),
                'bot_spell_rows' => count($botSpellRows),
                'search_rows' => count($searchRows),
            ];
        } catch (Throwable $e) {
            spp_marketplace_projection_upsert_state($armoryPdo, $realmId, [
                'build_status' => 'failed',
                'started_at' => $startedAtSql,
                'completed_at' => date('Y-m-d H:i:s'),
                'duration_ms' => (int)round((microtime(true) - $startedAt) * 1000),
                'error_text' => function_exists('mb_substr') ? mb_substr($e->getMessage(), 0, 65535) : substr($e->getMessage(), 0, 65535),
            ]);
            spp_marketplace_projection_log($context, 'build-failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}

if (!function_exists('spp_marketplace_schedule_projection_refresh')) {
    function spp_marketplace_schedule_projection_refresh(array $context): void
    {
        $key = spp_marketplace_projection_refresh_key($context);
        static $scheduled = [];
        if (isset($scheduled[$key]) || !spp_marketplace_try_refresh_lock($key)) {
            return;
        }

        $scheduled[$key] = true;
        register_shutdown_function(function () use ($context, $key) {
            if (function_exists('fastcgi_finish_request')) {
                @fastcgi_finish_request();
            }

            try {
                spp_marketplace_build_projection_snapshot($context);
            } catch (Throwable $e) {
            }

            spp_marketplace_release_refresh_lock($key);
        });
    }
}

if (!function_exists('spp_marketplace_prepare_projection_data')) {
    function spp_marketplace_prepare_projection_data(array $context): array
    {
        $realmId = (int)$context['realmId'];
        $armoryPdo = spp_get_pdo('armory', $realmId);
        spp_marketplace_ensure_projection_tables($armoryPdo);

        $state = spp_marketplace_projection_state($armoryPdo, $realmId);
        $hasLiveRows = spp_marketplace_projection_has_live_rows($armoryPdo, $realmId);
        $ready = spp_marketplace_projection_ready_state($state, $hasLiveRows);
        $age = spp_marketplace_projection_completed_age($state);

        if ($ready) {
            if ($age !== null && $age > spp_marketplace_cache_fresh_ttl()) {
                if ($age <= spp_marketplace_cache_stale_ttl()) {
                    spp_marketplace_schedule_projection_refresh($context);
                } else {
                    try {
                        spp_marketplace_build_projection_snapshot($context);
                    } catch (Throwable $e) {
                    }
                }
            }

            return ['has_snapshot' => true, 'pageError' => '', 'state' => spp_marketplace_projection_state($armoryPdo, $realmId)];
        }

        try {
            spp_marketplace_build_projection_snapshot($context);
        } catch (Throwable $e) {
            $state = spp_marketplace_projection_state($armoryPdo, $realmId);
            $hasLiveRows = spp_marketplace_projection_has_live_rows($armoryPdo, $realmId);
            if (spp_marketplace_projection_ready_state($state, $hasLiveRows)) {
                return ['has_snapshot' => true, 'pageError' => '', 'state' => $state];
            }

            return ['has_snapshot' => false, 'pageError' => 'The marketplace could not be loaded right now.', 'state' => $state];
        }

        return ['has_snapshot' => true, 'pageError' => '', 'state' => spp_marketplace_projection_state($armoryPdo, $realmId)];
    }
}

if (!function_exists('spp_marketplace_projection_summary_rows')) {
    function spp_marketplace_projection_summary_rows(PDO $armoryPdo, int $realmId): array
    {
        $statement = $armoryPdo->prepare(
            'SELECT *
             FROM `marketplace_profession_summary`
             WHERE `realm_id` = ?
             ORDER BY `skill_id` ASC,
                      FIELD(`summary_type`, \'top_overall\', \'top_faction\') ASC,
                      `faction` ASC,
                      `rank_position` ASC'
        );
        $statement->execute([$realmId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}

if (!function_exists('spp_marketplace_compact_card_from_projection')) {
    function spp_marketplace_compact_card_from_projection(array $row, int $realmId): ?array
    {
        if ((int)($row['guid'] ?? 0) <= 0) {
            return null;
        }

        return spp_marketplace_compact_crafter_card($row, $realmId, [
            'tier' => (string)($row['tier_label'] ?? ''),
        ]);
    }
}

if (!function_exists('spp_marketplace_build_summary_snapshot')) {
    function spp_marketplace_build_summary_snapshot(array $context): array
    {
        $armoryPdo = spp_get_pdo('armory', (int)$context['realmId']);
        $rows = spp_marketplace_projection_summary_rows($armoryPdo, (int)$context['realmId']);
        $summary = [];
        $coveredProfessionCount = 0;

        foreach ($rows as $row) {
            $skillId = (int)($row['skill_id'] ?? 0);
            if ($skillId <= 0) {
                continue;
            }

            $professionName = (string)($row['profession_name'] ?? ('Profession #' . $skillId));
            if (!isset($summary[$professionName])) {
                $summary[$professionName] = [
                    'skill_id' => $skillId,
                    'name' => $professionName,
                    'description' => trim((string)($row['profession_description'] ?? '')),
                    'icon' => (string)($row['profession_icon'] ?? ''),
                    'profession_cap' => (int)($row['profession_cap'] ?? 0),
                    'has_coverage' => !empty($row['has_coverage']),
                    'total_crafters' => (int)($row['total_crafters'] ?? 0),
                    'top_crafter' => null,
                    'faction_top_crafters' => ['Alliance' => [], 'Horde' => []],
                    'has_specialties' => true,
                    'specialty_count' => 0,
                    'specialty_coverage_count' => 0,
                    'specialties' => [],
                ];
                if (!empty($row['has_coverage'])) {
                    $coveredProfessionCount++;
                }
            }

            if ((string)($row['summary_type'] ?? '') === 'top_overall') {
                $summary[$professionName]['top_crafter'] = spp_marketplace_compact_card_from_projection(
                    $row,
                    (int)$context['realmId']
                );
                continue;
            }

            if ((string)($row['summary_type'] ?? '') === 'top_faction') {
                $faction = (string)($row['faction'] ?? '');
                if ($faction === 'Alliance' || $faction === 'Horde') {
                    $card = spp_marketplace_compact_card_from_projection($row, (int)$context['realmId']);
                    if ($card !== null) {
                        $summary[$professionName]['faction_top_crafters'][$faction][] = $card;
                    }
                }
            }
        }

        uasort($summary, 'spp_marketplace_projection_profession_compare');
        return ['professions' => $summary, 'botCount' => $coveredProfessionCount, 'craftCount' => 0];
    }
}

if (!function_exists('spp_marketplace_fetch_summary')) {
    function spp_marketplace_fetch_summary(array $context): array
    {
        $projection = spp_marketplace_prepare_projection_data($context);
        if (empty($projection['has_snapshot'])) {
            return ['value' => null, 'meta' => null, 'pageError' => (string)($projection['pageError'] ?? 'The marketplace could not be loaded right now.')];
        }

        return spp_marketplace_cached_snapshot(
            spp_marketplace_summary_cache_key($context),
            function () use ($context) {
                return spp_marketplace_build_summary_snapshot($context);
            }
        );
    }
}

if (!function_exists('spp_marketplace_fetch_spell_maps')) {
    function spp_marketplace_fetch_spell_maps(PDO $charsPdo, PDO $worldPdo, PDO $armoryPdo, array $guids, int $skillId): array
    {
        $learnedSpellsByGuid = [];
        $learnedSpellIds = [];
        if (!empty($guids)) {
            $placeholders = implode(',', array_fill(0, count($guids), '?'));
            $spellStmt = $charsPdo->prepare(
                'SELECT `guid`, `spell`
                 FROM `character_spell`
                 WHERE `disabled` = 0 AND `guid` IN (' . $placeholders . ')'
            );
            $spellStmt->execute(array_values($guids));
            foreach ($spellStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $guid = (int)$row['guid'];
                $spellId = (int)$row['spell'];
                $learnedSpellsByGuid[$guid][$spellId] = true;
                $learnedSpellIds[$spellId] = true;
            }
        }

        $trainerSpellIdsBySkill = [];
        $trainerStmt = $worldPdo->prepare(
            'SELECT `spell`, `reqskillvalue`
             FROM `npc_trainer`
             WHERE `reqskill` = ?
             UNION
             SELECT `spell`, `reqskillvalue`
             FROM `npc_trainer_template`
             WHERE `reqskill` = ?'
        );
        $trainerStmt->execute([$skillId, $skillId]);
        foreach ($trainerStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $spellId = (int)$row['spell'];
            if ($spellId <= 0) {
                continue;
            }
            $trainerSpellIdsBySkill[$skillId][$spellId] = (int)$row['reqskillvalue'];
            $learnedSpellIds[$spellId] = true;
        }

        $spellMetaMap = [];
        $spellOutputMap = [];
        $spellIconIds = [];
        $craftedItemIds = [];
        if (!empty($learnedSpellIds)) {
            $spellIds = array_keys($learnedSpellIds);
            $placeholders = implode(',', array_fill(0, count($spellIds), '?'));
            $spellStmt = $worldPdo->prepare(
                'SELECT `Id`, `SpellName`, `SpellIconID`, `Effect1`, `Effect2`, `Effect3`, `EffectTriggerSpell1`, `EffectTriggerSpell2`, `EffectTriggerSpell3`, `EffectItemType1`, `EffectItemType2`, `EffectItemType3`
                 FROM `spell_template`
                 WHERE `Id` IN (' . $placeholders . ')'
            );
            $spellStmt->execute($spellIds);
            foreach ($spellStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $spellId = (int)$row['Id'];
                $spellMetaMap[$spellId] = $row;
                $spellIconId = (int)($row['SpellIconID'] ?? 0);
                if ($spellIconId > 0) {
                    $spellIconIds[$spellIconId] = true;
                }
                foreach (['EffectItemType1', 'EffectItemType2', 'EffectItemType3'] as $field) {
                    $itemId = (int)($row[$field] ?? 0);
                    if ($itemId > 0) {
                        $spellOutputMap[$spellId][$itemId] = true;
                        $craftedItemIds[$itemId] = true;
                    }
                }
            }
            spp_marketplace_hydrate_trigger_outputs($worldPdo, $spellMetaMap, $spellOutputMap, $craftedItemIds);
        }

        $spellIconMap = [];
        if (!empty($spellIconIds)) {
            $iconIds = array_keys($spellIconIds);
            $placeholders = implode(',', array_fill(0, count($iconIds), '?'));
            $iconStmt = $armoryPdo->prepare('SELECT `id`, `name` FROM `dbc_spellicon` WHERE `id` IN (' . $placeholders . ')');
            $iconStmt->execute($iconIds);
            foreach ($iconStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $spellIconMap[(int)$row['id']] = (string)$row['name'];
            }
        }

        $craftItemMap = [];
        $craftIcons = [];
        if (!empty($craftedItemIds)) {
            $itemIds = array_keys($craftedItemIds);
            $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
            $itemStmt = $worldPdo->prepare(
                'SELECT `entry`, `name`, `Quality`, `RequiredSkill`, `RequiredSkillRank`, `displayid`
                 FROM `item_template`
                 WHERE `entry` IN (' . $placeholders . ')'
            );
            $itemStmt->execute($itemIds);
            $displayIds = [];
            foreach ($itemStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $craftItemMap[(int)$row['entry']] = $row;
                $displayId = (int)($row['displayid'] ?? 0);
                if ($displayId > 0) {
                    $displayIds[$displayId] = true;
                }
            }

            if (!empty($displayIds)) {
                $displayIdList = array_keys($displayIds);
                $placeholders = implode(',', array_fill(0, count($displayIdList), '?'));
                $iconStmt = $armoryPdo->prepare('SELECT `id`, `name` FROM `dbc_itemdisplayinfo` WHERE `id` IN (' . $placeholders . ')');
                $iconStmt->execute($displayIdList);
                foreach ($iconStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $craftIcons[(int)$row['id']] = (string)$row['name'];
                }
            }
        }

        return [$learnedSpellsByGuid, $trainerSpellIdsBySkill, spp_marketplace_trainer_recipe_rank_map($trainerSpellIdsBySkill, $spellMetaMap, $spellOutputMap, $craftItemMap), $spellMetaMap, $spellOutputMap, $craftItemMap, $craftIcons, $spellIconMap];
    }
}

if (!function_exists('spp_marketplace_build_profession_snapshot')) {
    function spp_marketplace_build_profession_snapshot(array $context, int $skillId): array
    {
        $realmId = (int)$context['realmId'];
        $armoryPdo = spp_get_pdo('armory', $realmId);

        $summaryStmt = $armoryPdo->prepare(
            'SELECT *
             FROM `marketplace_profession_summary`
             WHERE `realm_id` = ? AND `skill_id` = ?
             ORDER BY FIELD(`summary_type`, \'top_overall\', \'top_faction\'), `rank_position`
             LIMIT 1'
        );
        $summaryStmt->execute([$realmId, $skillId]);
        $summaryRow = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $profession = [
            'skill_id' => $skillId,
            'name' => (string)($summaryRow['profession_name'] ?? ('Profession #' . $skillId)),
            'description' => trim((string)($summaryRow['profession_description'] ?? '')),
            'icon' => (string)($summaryRow['profession_icon'] ?? ''),
            'tiers' => [],
            'total_bots' => 0,
            'total_recipes' => 0,
            'total_special_recipes' => 0,
            'special_holders' => [],
        ];

        $botStmt = $armoryPdo->prepare(
            'SELECT *
             FROM `marketplace_bot_professions`
             WHERE `realm_id` = ? AND `skill_id` = ?
             ORDER BY `max` DESC, `value` DESC, `name` ASC'
        );
        $botStmt->execute([$realmId, $skillId]);
        $botRows = $botStmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($botRows)) {
            return $profession;
        }

        $spellStmt = $armoryPdo->prepare(
            'SELECT *
             FROM `marketplace_bot_spells`
             WHERE `realm_id` = ? AND `skill_id` = ?
             ORDER BY `guid` ASC, `required_rank` DESC, `spell_name` ASC'
        );
        $spellStmt->execute([$realmId, $skillId]);
        $spellRows = $spellStmt->fetchAll(PDO::FETCH_ASSOC);

        $spellsByGuid = [];
        foreach ($spellRows as $row) {
            $guid = (int)($row['guid'] ?? 0);
            if ($guid <= 0) {
                continue;
            }

            $spellsByGuid[$guid][] = [
                'spell_id' => (int)($row['spell_id'] ?? 0),
                'spell_name' => (string)($row['spell_name'] ?? ''),
                'item_entry' => (int)($row['item_entry'] ?? 0),
                'item_name' => (string)($row['item_name'] ?? ''),
                'quality' => (int)($row['quality'] ?? 1),
                'icon' => (string)($row['icon'] ?? ''),
                'required_rank' => (int)($row['required_rank'] ?? 0),
                'is_special' => !empty($row['is_special']),
            ];
        }

        foreach ($botRows as $row) {
            $guid = (int)$row['guid'];
            $knownCrafts = $spellsByGuid[$guid] ?? [];
            usort($knownCrafts, 'spp_marketplace_recipe_compare');
            $specialCrafts = array_values(array_filter($knownCrafts, static function (array $craft): bool {
                return !empty($craft['is_special']);
            }));

            $bot = [
                'guid' => $guid,
                'name' => (string)$row['name'],
                'race' => (int)$row['race'],
                'class' => (int)$row['class'],
                'gender' => (int)$row['gender'],
                'online' => !empty($row['online']),
                'faction' => (string)($row['faction'] ?? ''),
                'level' => (int)$row['level'],
                'value' => (int)$row['value'],
                'max' => (int)$row['max'],
                'tier' => (string)($row['tier_label'] ?? spp_marketplace_profession_tier_label((int)($row['max'] ?? 0))),
                'craft_count' => (int)($row['craft_count'] ?? count($knownCrafts)),
                'special_craft_count' => (int)($row['special_craft_count'] ?? count($specialCrafts)),
                'crafts' => array_values($knownCrafts),
                'special_crafts' => $specialCrafts,
            ];

            $profession['tiers'][$bot['tier']][] = $bot;
            $profession['total_bots']++;
            $profession['total_recipes'] += $bot['craft_count'];
            $profession['total_special_recipes'] += $bot['special_craft_count'];
            if ($bot['special_craft_count'] > 0) {
                $profession['special_holders'][] = ['name' => $bot['name'], 'count' => $bot['special_craft_count'], 'faction' => $bot['faction']];
            }
        }

        foreach ($profession['tiers'] as &$tierBots) {
            usort($tierBots, 'spp_marketplace_detail_bot_compare');
        }
        unset($tierBots);

        return $profession;
    }
}

if (!function_exists('spp_marketplace_fetch_profession_detail')) {
    function spp_marketplace_fetch_profession_detail(array $context, int $skillId): array
    {
        $projection = spp_marketplace_prepare_projection_data($context);
        if (empty($projection['has_snapshot'])) {
            return ['value' => [], 'meta' => null, 'pageError' => (string)($projection['pageError'] ?? 'The marketplace could not be loaded right now.')];
        }

        return spp_marketplace_cached_snapshot(
            spp_marketplace_profession_cache_key($context, $skillId),
            function () use ($context, $skillId) {
                return spp_marketplace_build_profession_snapshot($context, $skillId);
            }
        );
    }
}

if (!function_exists('spp_marketplace_build_search_index')) {
    function spp_marketplace_build_search_index(array $context): array
    {
        $armoryPdo = spp_get_pdo('armory', (int)$context['realmId']);
        $statement = $armoryPdo->prepare(
            'SELECT `skill_id` AS `profession_skill_id`, `profession_name`, `profession_icon`,
                    `guid` AS `bot_guid`, `bot_name`, `bot_race`, `bot_class`, `bot_gender`,
                    `bot_faction`, `bot_level`, `tier_label` AS `tier`, `bot_value`, `bot_max`,
                    `craft_count`, `special_craft_count`, `spell_id`, `spell_name`, `item_entry`,
                    `item_name`, `quality`, `icon`, `required_rank`, `is_special`, `search_text`
             FROM `marketplace_search_index`
             WHERE `realm_id` = ?
             ORDER BY `profession_name` ASC, `bot_name` ASC, `spell_name` ASC'
        );
        $statement->execute([(int)$context['realmId']]);
        return ['entries' => $statement->fetchAll(PDO::FETCH_ASSOC)];
    }
}

if (!function_exists('spp_marketplace_fetch_search_index')) {
    function spp_marketplace_fetch_search_index(array $context): array
    {
        $projection = spp_marketplace_prepare_projection_data($context);
        if (empty($projection['has_snapshot'])) {
            return ['value' => ['entries' => []], 'meta' => null, 'pageError' => (string)($projection['pageError'] ?? 'The marketplace could not be loaded right now.')];
        }

        return spp_marketplace_cached_snapshot(
            spp_marketplace_search_cache_key($context),
            function () use ($context) {
                return spp_marketplace_build_search_index($context);
            }
        );
    }
}

if (!function_exists('spp_marketplace_search_results')) {
    function spp_marketplace_search_results(array $context, string $query, int $limit = 80): array
    {
        $normalizedQuery = spp_marketplace_normalize_search($query);
        if ($normalizedQuery === '') {
            return ['query' => '', 'normalized_query' => '', 'matches' => [], 'total_matches' => 0];
        }

        $projection = spp_marketplace_prepare_projection_data($context);
        if (empty($projection['has_snapshot'])) {
            return [
                'query' => $query,
                'normalized_query' => $normalizedQuery,
                'matches' => [],
                'total_matches' => 0,
            ];
        }

        $armoryPdo = spp_get_pdo('armory', (int)$context['realmId']);
        $like = '%' . $normalizedQuery . '%';

        $countStmt = $armoryPdo->prepare(
            'SELECT COUNT(*)
             FROM `marketplace_search_index`
             WHERE `realm_id` = ? AND `search_text` LIKE ?'
        );
        $countStmt->execute([(int)$context['realmId'], $like]);
        $totalMatches = (int)$countStmt->fetchColumn();

        $matchStmt = $armoryPdo->prepare(
            'SELECT `skill_id` AS `profession_skill_id`, `profession_name`, `profession_icon`,
                    `guid` AS `bot_guid`, `bot_name`, `bot_race`, `bot_class`, `bot_gender`,
                    `bot_faction`, `bot_level`, `tier_label` AS `tier`, `bot_value`, `bot_max`,
                    `craft_count`, `special_craft_count`, `spell_id`, `spell_name`, `item_entry`,
                    `item_name`, `quality`, `icon`, `required_rank`, `is_special`, `search_text`
             FROM `marketplace_search_index`
             WHERE `realm_id` = ? AND `search_text` LIKE ?
             ORDER BY `profession_name` ASC, `bot_name` ASC, `spell_name` ASC
             LIMIT ' . (int)$limit
        );
        $matchStmt->execute([(int)$context['realmId'], $like]);

        return [
            'query' => $query,
            'normalized_query' => $normalizedQuery,
            'matches' => $matchStmt->fetchAll(PDO::FETCH_ASSOC),
            'total_matches' => $totalMatches,
        ];
    }
}

if (!function_exists('spp_marketplace_character_href')) {
    function spp_marketplace_character_href(int $realmId, string $name, string $tab = ''): string
    {
        $url = 'index.php?n=server&sub=character&realm=' . $realmId . '&character=' . urlencode($name);
        if ($tab !== '') {
            $url .= '&tab=' . urlencode($tab);
        }
        return $url;
    }
}

if (!function_exists('spp_marketplace_item_href')) {
    function spp_marketplace_item_href(int $realmId, int $itemEntry): string
    {
        return 'index.php?n=server&sub=item&realm=' . $realmId . '&item=' . $itemEntry;
    }
}

if (!function_exists('spp_marketplace_build_page_state')) {
    function spp_marketplace_build_page_state($realmDbMap = null): array
    {
        $context = spp_marketplace_context($realmDbMap);
        $summarySnapshot = spp_marketplace_fetch_summary($context);
        $summary = is_array($summarySnapshot['value']) ? $summarySnapshot['value'] : ['professions' => [], 'botCount' => 0, 'craftCount' => 0];

        return [
            'realmMap' => $context['realmMap'],
            'realmId' => $context['realmId'],
            'realmLabel' => $context['realmLabel'],
            'expansion' => $context['expansion'],
            'craftProfessionIds' => $context['craftProfessionIds'],
            'tierOrder' => $context['tierOrder'],
            'professionSummaries' => $summary['professions'] ?? [],
            'marketplace' => $summary['professions'] ?? [],
            'botCount' => (int)($summary['botCount'] ?? 0),
            'craftCount' => (int)($summary['craftCount'] ?? 0),
            'pageError' => (string)($summarySnapshot['pageError'] ?? ''),
            'cacheMeta' => $summarySnapshot['meta'] ?? null,
        ];
    }
}
