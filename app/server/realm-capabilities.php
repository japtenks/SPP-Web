<?php

require_once dirname(__DIR__) . '/support/db-schema.php';

if (!function_exists('spp_realm_capability_service_configured')) {
    function spp_realm_capability_service_configured(array $realmMap, int $realmId, string $service): bool
    {
        return !empty($realmMap[$realmId][$service]) && is_string($realmMap[$realmId][$service]);
    }
}

if (!function_exists('spp_realm_capability_service_pdo')) {
    function spp_realm_capability_service_pdo(array $realmMap, int $realmId, string $service): ?PDO
    {
        if (!spp_realm_capability_service_configured($realmMap, $realmId, $service) || !function_exists('spp_get_pdo')) {
            return null;
        }

        try {
            $pdo = spp_get_pdo($service, $realmId);
            return $pdo instanceof PDO ? $pdo : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('spp_realm_capability_table_exists')) {
    function spp_realm_capability_table_exists(?PDO $pdo, string $table): bool
    {
        return $pdo instanceof PDO && spp_db_table_exists($pdo, $table);
    }
}

if (!function_exists('spp_realm_capability_any_table_exists')) {
    function spp_realm_capability_any_table_exists(?PDO $pdo, array $tables): bool
    {
        foreach ($tables as $table) {
            if (spp_realm_capability_table_exists($pdo, (string)$table)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('spp_realm_capability_table_columns')) {
    function spp_realm_capability_table_columns(?PDO $pdo, string $table): array
    {
        static $cache = array();

        if (!$pdo instanceof PDO || !spp_realm_capability_table_exists($pdo, $table)) {
            return array();
        }

        $key = spl_object_hash($pdo) . ':' . $table;
        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $columns = array();
        foreach ($pdo->query('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`')->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $columns[(string)($row['Field'] ?? '')] = true;
        }

        return $cache[$key] = $columns;
    }
}

if (!function_exists('spp_realm_capability_pick_column')) {
    function spp_realm_capability_pick_column(?PDO $pdo, string $table, array $candidates, ?string $default = null): ?string
    {
        $columns = spp_realm_capability_table_columns($pdo, $table);
        foreach ($candidates as $candidate) {
            $candidate = (string)$candidate;
            if ($candidate !== '' && isset($columns[$candidate])) {
                return $candidate;
            }
        }

        return $default;
    }
}

if (!function_exists('spp_realm_capability_guess_expansion_key')) {
    function spp_realm_capability_guess_expansion_key(array $realmMap, int $realmId): string
    {
        $explicit = function_exists('spp_realm_to_expansion_key')
            ? trim((string)spp_realm_to_expansion_key($realmId))
            : '';
        if ($explicit !== '') {
            return $explicit;
        }

        $realm = (array)($realmMap[$realmId] ?? array());
        $worldName = strtolower(trim((string)($realm['world'] ?? '')));
        $armoryName = strtolower(trim((string)($realm['armory'] ?? '')));
        $haystack = $worldName . ' ' . $armoryName;

        if (strpos($haystack, 'vmangos') !== false) {
            return 'vmangos';
        }
        if (strpos($haystack, 'wotlk') !== false) {
            return 'wotlk';
        }
        if (strpos($haystack, 'tbc') !== false) {
            return 'tbc';
        }

        return 'classic';
    }
}

if (!function_exists('spp_realm_capability_icon_assets_available')) {
    function spp_realm_capability_icon_assets_available(): bool
    {
        $defaultIcon = function_exists('spp_modern_icon_path')
            ? spp_modern_icon_path('404.png')
            : dirname(__DIR__, 2) . '/images/modern/icons/64x64/404.png';

        return is_string($defaultIcon) && $defaultIcon !== '' && is_file($defaultIcon);
    }
}

if (!function_exists('spp_realm_capabilities')) {
    function spp_realm_capabilities(array $realmMap, int $realmId): array
    {
        static $cache = array();

        $cacheKey = md5(json_encode(array(
            'realm_id' => $realmId,
            'realm' => $realmMap[$realmId] ?? null,
        )));
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $expansionKey = spp_realm_capability_guess_expansion_key($realmMap, $realmId);
        $isVmangos = $expansionKey === 'vmangos';

        $realmdPdo = spp_realm_capability_service_pdo($realmMap, $realmId, 'realmd');
        $charsPdo = spp_realm_capability_service_pdo($realmMap, $realmId, 'chars');
        $worldPdo = spp_realm_capability_service_pdo($realmMap, $realmId, 'world');
        $armoryPdo = spp_realm_capability_service_pdo($realmMap, $realmId, 'armory');
        $botsPdo = spp_realm_capability_service_pdo($realmMap, $realmId, 'bots');

        $hasChars = $charsPdo instanceof PDO;
        $hasWorld = $worldPdo instanceof PDO;
        $hasArmory = $armoryPdo instanceof PDO;
        $hasBots = $botsPdo instanceof PDO;
        $hasItemTemplate = spp_realm_capability_table_exists($worldPdo, 'item_template');
        $hasArmoryDbc = spp_realm_capability_any_table_exists($armoryPdo, array(
            'dbc_itemdisplayinfo',
            'dbc_spellicon',
            'dbc_spell',
            'dbc_talent',
        ));
        $hasWorldDbc = spp_realm_capability_any_table_exists($worldPdo, array(
            'achievement_dbc',
            'dbc_itemset',
            'dbc_spell',
        ));
        $hasDbc = $hasArmoryDbc || $hasWorldDbc;
        $hasIconAssets = spp_realm_capability_icon_assets_available();
        $supportsGuilds = $hasChars
            && spp_realm_capability_table_exists($charsPdo, 'guild')
            && spp_realm_capability_table_exists($charsPdo, 'guild_member');
        $supportsHonor = $hasChars && spp_realm_capability_table_exists($charsPdo, 'characters');
        $supportsAuction = $hasChars
            && $hasWorld
            && spp_realm_capability_table_exists($charsPdo, 'auction')
            && $hasItemTemplate;
        $supportsProgression = !$isVmangos
            && $hasChars
            && spp_realm_capability_table_exists($charsPdo, 'item_instance');
        $supportsCharacterDetail = !$isVmangos && $hasChars && $hasWorld && $hasArmory && $hasDbc && $hasIconAssets;
        $supportsItemDetail = !$isVmangos && $hasChars && $hasWorld && $hasArmory && $hasDbc && $hasItemTemplate && $hasIconAssets;
        $supportsMarketplace = !$isVmangos && $hasArmory && $hasDbc;
        $supportsSocial = $supportsGuilds || $hasBots;

        return $cache[$cacheKey] = array(
            'realm_id' => $realmId,
            'expansion_key' => $expansionKey,
            'is_vmangos' => $isVmangos,
            'has_realmd' => $realmdPdo instanceof PDO,
            'has_chars' => $hasChars,
            'has_world' => $hasWorld,
            'has_armory' => $hasArmory,
            'has_bots' => $hasBots,
            'has_icon_assets' => $hasIconAssets,
            'has_dbc' => $hasDbc,
            'has_item_template' => $hasItemTemplate,
            'supports_progression' => $supportsProgression,
            'supports_item_template' => $hasItemTemplate,
            'supports_guilds' => $supportsGuilds,
            'supports_honor' => $supportsHonor,
            'supports_auction' => $supportsAuction,
            'supports_social' => $supportsSocial,
            'supports_marketplace' => $supportsMarketplace,
            'supports_character_detail' => $supportsCharacterDetail,
            'supports_item_detail' => $supportsItemDetail,
            'services' => array(
                'realmd' => array(
                    'configured' => spp_realm_capability_service_configured($realmMap, $realmId, 'realmd'),
                    'available' => $realmdPdo instanceof PDO,
                ),
                'chars' => array(
                    'configured' => spp_realm_capability_service_configured($realmMap, $realmId, 'chars'),
                    'available' => $hasChars,
                ),
                'world' => array(
                    'configured' => spp_realm_capability_service_configured($realmMap, $realmId, 'world'),
                    'available' => $hasWorld,
                ),
                'armory' => array(
                    'configured' => spp_realm_capability_service_configured($realmMap, $realmId, 'armory'),
                    'available' => $hasArmory,
                ),
                'bots' => array(
                    'configured' => spp_realm_capability_service_configured($realmMap, $realmId, 'bots'),
                    'available' => $hasBots,
                ),
            ),
        );
    }
}
