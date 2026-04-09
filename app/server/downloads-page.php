<?php

require_once __DIR__ . '/realm-capabilities.php';

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

if (!function_exists('spp_server_downloads_load_page_state')) {
    function spp_server_downloads_load_page_state(array $args = array()): array
    {
        $realmMap = (array)($args['realm_map'] ?? ($GLOBALS['realmDbMap'] ?? array()));
        $get = (array)($args['get'] ?? $_GET);
        $siteRoot = dirname(__DIR__, 2);
        $downloadsRoot = $siteRoot . DIRECTORY_SEPARATOR . 'downloads';
        $requestedRealmId = isset($get['realm']) ? (int)$get['realm'] : 0;
        $downloadsRealmId = !empty($realmMap) ? (int)spp_resolve_realm_id($realmMap, $requestedRealmId > 0 ? $requestedRealmId : null) : 1;
        $realmCapabilities = spp_realm_capabilities($realmMap, $downloadsRealmId);
        $realmlistHref = 'index.php?n=server&sub=realmlist&nobody=1&realm=' . $downloadsRealmId;

        $sectionDefinitions = array(
            'addons' => array(
                'title' => 'Addon Packs',
                'description' => 'Local copies of addon zips and folders. Start by copying files here.',
                'web' => '/downloads/addons',
                'path' => $downloadsRoot . DIRECTORY_SEPARATOR . 'addons',
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
            $section['files'] = spp_downloads_collect_files($section['path'], $section['web']);
            $section['show_realmlist_action'] = ($key === 'tools');
            $downloadsSections[$key] = $section;
        }

        return array(
            'downloadsRealmId' => $downloadsRealmId,
            'downloadsRealmlistHref' => $realmlistHref,
            'downloadsSections' => $downloadsSections,
            'realmCapabilities' => $realmCapabilities,
            'pathway_info' => array(
                array('title' => 'Downloads', 'link' => ''),
            ),
        );
    }
}
