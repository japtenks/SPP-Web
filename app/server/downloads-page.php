<?php

require_once __DIR__ . '/realm-capabilities.php';
require_once __DIR__ . '/realmlist-endpoint.php';

if (!function_exists('spp_downloads_format_size')) {
    function spp_downloads_format_size($bytes): string
    {
        $bytes = (float)$bytes;
        $units = array('B', 'KB', 'MB', 'GB');
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return ($unitIndex === 0 ? (string)(int)$bytes : number_format($bytes, 1)) . ' ' . $units[$unitIndex];
    }
}

if (!function_exists('spp_downloads_collect_files')) {
    function spp_downloads_collect_files(string $diskPath, string $webBase): array
    {
        $items = array();
        if (!is_dir($diskPath)) {
            return $items;
        }

        $allowedExtensions = array('zip', 'rar', '7z', 'exe', 'msi', 'txt', 'pdf', 'mpq');
        $entries = scandir($diskPath);
        if (!is_array($entries)) {
            return $items;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $fullPath = $diskPath . DIRECTORY_SEPARATOR . $entry;
            if (!is_file($fullPath)) {
                continue;
            }

            $extension = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if ($extension !== '' && !in_array($extension, $allowedExtensions, true)) {
                continue;
            }

            $items[] = array(
                'name' => $entry,
                'href' => rtrim($webBase, '/') . '/' . rawurlencode($entry),
                'size' => spp_downloads_format_size((int)filesize($fullPath)),
                'modified' => @date('Y-m-d H:i', (int)filemtime($fullPath)),
                'ext' => $extension !== '' ? strtoupper($extension) : 'FILE',
            );
        }

        usort($items, static function (array $left, array $right): int {
            return strcasecmp($left['name'], $right['name']);
        });

        return $items;
    }
}

if (!function_exists('spp_downloads_addon_release_manifest')) {
    function spp_downloads_addon_release_manifest(): array
    {
        return array(
            'classic' => array(
                'name' => 'addons-vanilla-1.12.zip',
                'href' => 'https://github.com/japtenks/spp-cmangos-prox/releases/download/assets/addons-vanilla-1.12.zip',
                'size' => 'GitHub Release Asset',
                'modified' => '',
                'ext' => 'ZIP',
                'expansion_key' => 'classic',
                'expansion_label' => 'Classic',
            ),
            'tbc' => array(
                'name' => 'addons-tbc-2.4.3.zip',
                'href' => 'https://github.com/japtenks/spp-cmangos-prox/releases/download/assets/addons-tbc-2.4.3.zip',
                'size' => 'GitHub Release Asset',
                'modified' => '',
                'ext' => 'ZIP',
                'expansion_key' => 'tbc',
                'expansion_label' => 'TBC',
            ),
            'wotlk' => array(
                'name' => 'addon-3.3.5a.zip',
                'href' => 'https://github.com/japtenks/spp-cmangos-prox/releases/download/assets/addon-3.3.5a.zip',
                'size' => 'GitHub Release Asset',
                'modified' => '',
                'ext' => 'ZIP',
                'expansion_key' => 'wotlk',
                'expansion_label' => 'WotLK / 3.3.5a',
            ),
        );
    }
}

if (!function_exists('spp_downloads_addon_files_for_expansion')) {
    function spp_downloads_addon_files_for_expansion(string $selectedExpansionKey = ''): array
    {
        $manifest = spp_downloads_addon_release_manifest();
        $normalizedExpansionKey = strtolower(trim($selectedExpansionKey));
        if ($normalizedExpansionKey === 'vanilla') {
            $normalizedExpansionKey = 'classic';
        } elseif ($normalizedExpansionKey === '3.3.5a') {
            $normalizedExpansionKey = 'wotlk';
        }

        $order = array('classic', 'tbc', 'wotlk');
        if ($normalizedExpansionKey !== '' && in_array($normalizedExpansionKey, $order, true)) {
            $order = array_values(array_unique(array_merge(array($normalizedExpansionKey), $order)));
        }

        $items = array();
        foreach ($order as $expansionKey) {
            if (empty($manifest[$expansionKey])) {
                continue;
            }

            $item = $manifest[$expansionKey];
            $items[] = $item;
        }

        return $items;
    }
}

if (!function_exists('spp_server_downloads_load_page_state')) {
    function spp_server_downloads_load_page_state(array $args = array()): array
    {
        $realmMap = (array)($args['realm_map'] ?? ($GLOBALS['realmDbMap'] ?? array()));
        $get = (array)($args['get'] ?? $_GET);
        $siteRoot = dirname(__DIR__, 2);
        $downloadsRoot = $siteRoot . DIRECTORY_SEPARATOR . 'downloads';
        $requestedChoiceId = isset($get['realm']) ? (int)$get['realm'] : 0;
        $choice = spp_server_realmlist_choice($realmMap, $requestedChoiceId);
        $downloadsRealmId = (int)($choice['public_choice_id'] ?? 0);
        $sourceSlotId = (int)($choice['source_slot_id'] ?? $downloadsRealmId);
        $realmCapabilities = $sourceSlotId > 0 ? spp_realm_capabilities($realmMap, $sourceSlotId) : array();
        $selectedExpansionKey = (string)($realmCapabilities['expansion_key'] ?? '');
        $realmlistHref = 'index.php?n=server&sub=realmlist&nobody=1&realm=' . max(1, $downloadsRealmId);
        $realmlistOptions = spp_server_realmlist_download_options($realmMap, $downloadsRealmId);

        $sectionDefinitions = array(
            'addons' => array(
                'title' => 'Addon Bundles',
                'description' => 'Expansion-matched bundled addon archives hosted on the GitHub assets release.',
                'web' => 'https://github.com/japtenks/spp-cmangos-prox/releases/tag/assets',
                'path' => '',
                'files' => spp_downloads_addon_files_for_expansion($selectedExpansionKey),
            ),
            'tools' => array(
                'title' => 'Tools & Utilities',
                'description' => 'Optional helper tools, launchers, docs, or patches for players on the Realms.',
                'web' => '/downloads/tools',
                'path' => $downloadsRoot . DIRECTORY_SEPARATOR . 'tools',
            ),
        );

        $downloadsSections = array();
        foreach ($sectionDefinitions as $key => $section) {
            if (!isset($section['files'])) {
                $section['files'] = spp_downloads_collect_files($section['path'], $section['web']);
            }
            $section['show_realmlist_action'] = ($key === 'tools');
            $downloadsSections[$key] = $section;
        }

        return array(
            'downloadsRealmId' => $downloadsRealmId,
            'downloadsRealmlistHref' => $realmlistHref,
            'downloadsRealmlistOptions' => $realmlistOptions,
            'downloadsRealmlistDownloadAvailable' => !empty($choice['is_download_available']),
            'downloadsRealmlistMetadataState' => (string)($choice['metadata_state'] ?? 'incomplete'),
            'downloadsRealmlistMissingReasons' => (array)($choice['missing_reasons'] ?? array()),
            'downloadsSections' => $downloadsSections,
            'realmCapabilities' => $realmCapabilities,
            'pathway_info' => array(
                array('title' => 'Downloads', 'link' => ''),
            ),
        );
    }
}
