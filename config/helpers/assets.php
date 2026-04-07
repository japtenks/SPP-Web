<?php
// Template, asset, and background helpers.

if (!function_exists('spp_asset_url_for_path')) {
    function spp_asset_url_for_path(string $relativePath, bool $withVersion = true): string
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        $url = '/' . $relativePath;

        if (!$withVersion) {
            return $url;
        }

        $assetPath = spp_public_asset_path($relativePath);
        if (!is_file($assetPath)) {
            return $url;
        }

        $version = @filemtime($assetPath);
        if (!$version) {
            return $url;
        }

        return $url . '?v=' . $version;
    }
}

if (!function_exists('spp_template_path')) {
    function spp_template_path(string $relativePath = '', $config = null): string {
        $base = spp_template_root($config);
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        return $relativePath === '' ? $base : $base . '/' . $relativePath;
    }
}

if (!function_exists('spp_template_url')) {
    function spp_template_url(string $relativePath = '', $config = null): string {
        return '/' . ltrim(spp_template_path($relativePath, $config), '/');
    }
}

if (!function_exists('spp_template_asset_url')) {
    function spp_template_asset_url(string $relativePath = '', $config = null, bool $withVersion = true): string
    {
        return spp_asset_url_for_path(spp_template_path($relativePath, $config), $withVersion);
    }
}

if (!function_exists('spp_template_image_url')) {
    function spp_template_image_url(string $relativePath = '', $config = null): string
    {
        return spp_template_asset_url($relativePath, $config, false);
    }
}

if (!function_exists('spp_template_image_path')) {
    function spp_template_image_path(string $relativePath = '', $config = null): string
    {
        return spp_public_asset_path(spp_template_path($relativePath, $config));
    }
}

if (!function_exists('spp_modern_asset_root')) {
    function spp_modern_asset_root($config = null): string
    {
        $configuredRoot = trim((string)spp_config_generic('modern_asset_root', '', $config));
        if ($configuredRoot !== '') {
            return trim(str_replace('\\', '/', $configuredRoot), '/');
        }

        $preferredRoot = 'images/modern';
        $legacyRoot = trim(str_replace('\\', '/', spp_template_path('images/modern', $config)), '/');

        if (is_dir(spp_public_asset_path($preferredRoot)) || !is_dir(spp_public_asset_path($legacyRoot))) {
            return $preferredRoot;
        }

        return $legacyRoot;
    }
}

if (!function_exists('spp_modern_asset_relative_path')) {
    function spp_modern_asset_relative_path(string $relativePath = '', $config = null): string
    {
        $base = spp_modern_asset_root($config);
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');

        return $relativePath === '' ? $base : $base . '/' . $relativePath;
    }
}

if (!function_exists('spp_portrait_image_url')) {
    function spp_portrait_image_url(string $relativePath = '', $config = null): string
    {
        return spp_modern_image_url('portraits/' . ltrim($relativePath, '/'), $config);
    }
}

if (!function_exists('spp_portrait_image_path')) {
    function spp_portrait_image_path(string $relativePath = '', $config = null): string
    {
        return spp_modern_image_path('portraits/' . ltrim($relativePath, '/'), $config);
    }
}

if (!function_exists('spp_portrait_bucket_chain_for_level')) {
    function spp_portrait_bucket_chain_for_level(int $level): array
    {
        if ($level <= 59) {
            return array('wow-default');
        }

        if ($level <= 69) {
            return array('wow', 'wow-default');
        }

        if ($level <= 79) {
            return array('wow-70', 'wow', 'wow-default');
        }

        return array('wow-80', 'wow-70', 'wow', 'wow-default');
    }
}

if (!function_exists('spp_portrait_bucket_chain_for_expansion')) {
    function spp_portrait_bucket_chain_for_expansion(int $expansion): array
    {
        if ($expansion >= 2) {
            return array('wow-80', 'wow-70', 'wow', 'wow-default');
        }

        if ($expansion >= 1) {
            return array('wow-70', 'wow', 'wow-default');
        }

        return array('wow', 'wow-default');
    }
}

if (!function_exists('spp_find_portrait_relative_path')) {
    function spp_find_portrait_relative_path(array $portraitBuckets, int $gender, int $race, int $class): ?string
    {
        foreach ($portraitBuckets as $portraitBucket) {
            $portraitBucket = trim((string)$portraitBucket);
            if ($portraitBucket === '') {
                continue;
            }

            $pattern = spp_portrait_image_path($portraitBucket . '/' . $gender . '-' . $race . '-' . $class . '*.gif');
            $matches = glob($pattern);
            if (empty($matches)) {
                continue;
            }

            sort($matches, SORT_NATURAL);
            $relative = str_replace('\\', '/', basename(dirname($matches[0])) . '/' . basename($matches[0]));
            return $relative;
        }

        return null;
    }
}

if (!function_exists('spp_armory_image_url')) {
    function spp_armory_image_url(string $relativePath = '', $config = null): string
    {
        return spp_template_image_url('images/armory/' . ltrim($relativePath, '/'), $config);
    }
}

if (!function_exists('spp_armory_icon_url')) {
    function spp_armory_icon_url(string $filename = '404.png', $config = null): string
    {
        return spp_modern_image_url('icons/64x64/' . ltrim($filename, '/'), $config);
    }
}

if (!function_exists('spp_armory_icon_candidates')) {
    function spp_armory_icon_candidates(string $iconName): array
    {
        $iconName = trim(str_replace('\\', '/', $iconName));
        if ($iconName === '') {
            return array('404.png');
        }

        $basename = strtolower((string)pathinfo($iconName, PATHINFO_FILENAME));
        $extension = strtolower((string)pathinfo($iconName, PATHINFO_EXTENSION));
        $candidates = array();

        if ($basename === '') {
            $basename = strtolower($iconName);
        }

        if ($extension !== '') {
            $candidates[] = $basename . '.' . $extension;
        }

        foreach (array('png', 'jpg', 'jpeg', 'gif') as $candidateExtension) {
            $candidate = $basename . '.' . $candidateExtension;
            if (!in_array($candidate, $candidates, true)) {
                $candidates[] = $candidate;
            }
        }

        return $candidates;
    }
}

if (!function_exists('spp_resolve_armory_icon_filename')) {
    function spp_resolve_armory_icon_filename(string $iconName, string $default = '404.png', $config = null): string
    {
        foreach (spp_armory_icon_candidates($iconName) as $candidate) {
            if (is_file(spp_modern_image_path('icons/64x64/' . $candidate, $config))) {
                return $candidate;
            }
        }

        return ltrim($default, '/');
    }
}

if (!function_exists('spp_resolve_armory_icon_url')) {
    function spp_resolve_armory_icon_url(string $iconName, string $default = '404.png', $config = null): string
    {
        return spp_armory_icon_url(spp_resolve_armory_icon_filename($iconName, $default, $config), $config);
    }
}

if (!function_exists('spp_theme_icons_url')) {
    function spp_theme_icons_url(string $relativePath = '', $config = null): string
    {
        return spp_modern_meta_icon_url($relativePath, $config);
    }
}

if (!function_exists('spp_modern_image_url')) {
    function spp_modern_image_url(string $relativePath = '', $config = null): string
    {
        return spp_asset_url_for_path(spp_modern_asset_relative_path($relativePath, $config), false);
    }
}

if (!function_exists('spp_modern_image_path')) {
    function spp_modern_image_path(string $relativePath = '', $config = null): string
    {
        return spp_public_asset_path(spp_modern_asset_relative_path($relativePath, $config));
    }
}

if (!function_exists('spp_modern_branding_url')) {
    function spp_modern_branding_url(string $filename = '', $config = null): string
    {
        return spp_modern_image_url('branding/' . ltrim($filename, '/'), $config);
    }
}

if (!function_exists('spp_modern_branding_path')) {
    function spp_modern_branding_path(string $filename = '', $config = null): string
    {
        return spp_modern_image_path('branding/' . ltrim($filename, '/'), $config);
    }
}

if (!function_exists('spp_modern_faction_logo_url')) {
    function spp_modern_faction_logo_url(string $factionSlug = 'alliance', $config = null): string
    {
        $factionSlug = strtolower(trim($factionSlug));
        if ($factionSlug !== 'horde') {
            $factionSlug = 'alliance';
        }

        return spp_modern_image_url('factions/logo-' . $factionSlug . '.png', $config);
    }
}

if (!function_exists('spp_modern_faction_logo_path')) {
    function spp_modern_faction_logo_path(string $factionSlug = 'alliance', $config = null): string
    {
        $factionSlug = strtolower(trim($factionSlug));
        if ($factionSlug !== 'horde') {
            $factionSlug = 'alliance';
        }

        return spp_modern_image_path('factions/logo-' . $factionSlug . '.png', $config);
    }
}

if (!function_exists('spp_modern_guild_image_url')) {
    function spp_modern_guild_image_url(string $filename = '', $config = null): string
    {
        return spp_modern_image_url('guilds/' . ltrim($filename, '/'), $config);
    }
}

if (!function_exists('spp_modern_guild_image_path')) {
    function spp_modern_guild_image_path(string $filename = '', $config = null): string
    {
        return spp_modern_image_path('guilds/' . ltrim($filename, '/'), $config);
    }
}

if (!function_exists('spp_modern_forum_image_url')) {
    function spp_modern_forum_image_url(string $filename = '', $config = null): string
    {
        return spp_modern_image_url('forum/' . ltrim($filename, '/'), $config);
    }
}

if (!function_exists('spp_modern_forum_image_path')) {
    function spp_modern_forum_image_path(string $filename = '', $config = null): string
    {
        return spp_modern_image_path('forum/' . ltrim($filename, '/'), $config);
    }
}

if (!function_exists('spp_modern_header_image_url')) {
    function spp_modern_header_image_url(string $filename = '', $config = null): string
    {
        return spp_modern_image_url('headers/' . ltrim($filename, '/'), $config);
    }
}

if (!function_exists('spp_modern_header_image_path')) {
    function spp_modern_header_image_path(string $filename = '', $config = null): string
    {
        return spp_modern_image_path('headers/' . ltrim($filename, '/'), $config);
    }
}

if (!function_exists('spp_modern_icon_url')) {
    function spp_modern_icon_url(string $filename = '', $config = null): string
    {
        return spp_modern_image_url('icons/64x64/' . ltrim($filename, '/'), $config);
    }
}

if (!function_exists('spp_modern_icon_path')) {
    function spp_modern_icon_path(string $filename = '', $config = null): string
    {
        return spp_modern_image_path('icons/64x64/' . ltrim($filename, '/'), $config);
    }
}

if (!function_exists('spp_modern_meta_icon_url')) {
    function spp_modern_meta_icon_url(string $relativePath = '', $config = null): string
    {
        return spp_modern_image_url('meta/' . ltrim($relativePath, '/'), $config);
    }
}

if (!function_exists('spp_modern_meta_icon_path')) {
    function spp_modern_meta_icon_path(string $relativePath = '', $config = null): string
    {
        return spp_modern_image_path('meta/' . ltrim($relativePath, '/'), $config);
    }
}

if (!function_exists('spp_modern_talent_tab_url')) {
    function spp_modern_talent_tab_url(string $filename = '', $config = null): string
    {
        return spp_modern_image_url('talent-tabs/' . ltrim($filename, '/'), $config);
    }
}

if (!function_exists('spp_modern_talent_tab_path')) {
    function spp_modern_talent_tab_path(string $filename = '', $config = null): string
    {
        return spp_modern_image_path('talent-tabs/' . ltrim($filename, '/'), $config);
    }
}

if (!function_exists('spp_modern_status_image_url')) {
    function spp_modern_status_image_url(string $filename = '', $config = null): string
    {
        return spp_modern_image_url('status/' . ltrim($filename, '/'), $config);
    }
}

if (!function_exists('spp_modern_status_image_path')) {
    function spp_modern_status_image_path(string $filename = '', $config = null): string
    {
        return spp_modern_image_path('status/' . ltrim($filename, '/'), $config);
    }
}

if (!function_exists('spp_xfer_image_url')) {
    function spp_xfer_image_url(string $relativePath = '', $config = null): string
    {
        return spp_template_image_url('images/xfer/' . ltrim($relativePath, '/'), $config);
    }
}

if (!function_exists('spp_js_asset_url')) {
    function spp_js_asset_url(string $filename, bool $withVersion = false): string {
        return spp_asset_url_for_path('js/' . ltrim($filename, '/'), $withVersion);
    }
}

if (!function_exists('spp_template_js_asset_url')) {
    function spp_template_js_asset_url(string $filename, $config = null, bool $withVersion = true): string
    {
        return spp_template_asset_url('js/' . ltrim($filename, '/'), $config, $withVersion);
    }
}

if (!function_exists('spp_asset_registry_bootstrap')) {
    function spp_asset_registry_bootstrap(): void
    {
        if (!isset($GLOBALS['spp_asset_registry']) || !is_array($GLOBALS['spp_asset_registry'])) {
            $GLOBALS['spp_asset_registry'] = array(
                'styles' => array(),
                'scripts' => array(),
            );
        }
    }
}

if (!function_exists('spp_asset_registry_bucket')) {
    function spp_asset_registry_bucket(string $type): array
    {
        spp_asset_registry_bootstrap();

        if (!isset($GLOBALS['spp_asset_registry'][$type]) || !is_array($GLOBALS['spp_asset_registry'][$type])) {
            $GLOBALS['spp_asset_registry'][$type] = array();
        }

        return $GLOBALS['spp_asset_registry'][$type];
    }
}

if (!function_exists('spp_register_style_asset')) {
    function spp_register_style_asset(string $handle, string $url, array $options = array()): void
    {
        $handle = trim($handle);
        $url = trim($url);
        if ($handle === '' || $url === '') {
            return;
        }

        spp_asset_registry_bootstrap();
        $bucket = spp_asset_registry_bucket('styles');
        $bucket[$handle] = array(
            'handle' => $handle,
            'url' => $url,
            'media' => trim((string)($options['media'] ?? 'all')) ?: 'all',
        );
        $GLOBALS['spp_asset_registry']['styles'] = $bucket;
    }
}

if (!function_exists('spp_register_script_asset')) {
    function spp_register_script_asset(string $handle, string $url, array $options = array()): void
    {
        $handle = trim($handle);
        $url = trim($url);
        if ($handle === '' || $url === '') {
            return;
        }

        spp_asset_registry_bootstrap();
        $bucket = spp_asset_registry_bucket('scripts');
        $bucket[$handle] = array(
            'handle' => $handle,
            'url' => $url,
            'defer' => !empty($options['defer']),
            'async' => !empty($options['async']),
        );
        $GLOBALS['spp_asset_registry']['scripts'] = $bucket;
    }
}

if (!function_exists('spp_register_family_style')) {
    function spp_register_family_style(string $family, string $filename, $config = null, array $options = array()): void
    {
        spp_register_style_asset(
            'family:' . trim($family) . ':' . trim($filename),
            spp_template_asset_url('css/' . ltrim($filename, '/'), $config, $options['with_version'] ?? true),
            $options
        );
    }
}

if (!function_exists('spp_register_family_script')) {
    function spp_register_family_script(string $family, string $filename, $config = null, array $options = array()): void
    {
        spp_register_script_asset(
            'family:' . trim($family) . ':' . trim($filename),
            spp_template_js_asset_url($filename, $config, $options['with_version'] ?? true),
            $options
        );
    }
}

if (!function_exists('spp_render_registered_styles')) {
    function spp_render_registered_styles(): string
    {
        $styles = spp_asset_registry_bucket('styles');
        $html = array();

        foreach ($styles as $asset) {
            $html[] = '<link rel="stylesheet" href="' . htmlspecialchars((string)$asset['url'], ENT_QUOTES) . '" media="' . htmlspecialchars((string)($asset['media'] ?? 'all'), ENT_QUOTES) . '"/>';
        }

        return implode("\n  ", $html);
    }
}

if (!function_exists('spp_render_registered_scripts')) {
    function spp_render_registered_scripts(): string
    {
        $scripts = spp_asset_registry_bucket('scripts');
        $html = array();

        foreach ($scripts as $asset) {
            $attrs = '';
            if (!empty($asset['defer'])) {
                $attrs .= ' defer';
            }
            if (!empty($asset['async'])) {
                $attrs .= ' async';
            }
            $html[] = '<script src="' . htmlspecialchars((string)$asset['url'], ENT_QUOTES) . '"' . $attrs . '></script>';
        }

        return implode("\n", $html);
    }
}

if (!function_exists('spp_background_image_directory')) {
    function spp_background_image_directory() {
        return rtrim(spp_modern_image_path('bkgd'), '/') . '/';
    }
}

if (!function_exists('spp_background_image_base_url')) {
    function spp_background_image_base_url() {
        return rtrim(spp_modern_image_url('bkgd'), '/') . '/';
    }
}

if (!function_exists('spp_background_image_url')) {
    function spp_background_image_url($filename) {
        $filename = basename(trim((string)$filename));
        if ($filename === '') {
            $filename = '19.jpg';
        }

        return spp_background_image_base_url() . rawurlencode($filename);
    }
}

if (!function_exists('spp_background_image_catalog')) {
    function spp_background_image_catalog() {
        static $catalog = null;

        if ($catalog !== null) {
            return $catalog;
        }

        $catalog = [];
        $directory = spp_background_image_directory();

        // Prefer broadly supported formats for the shell background.
        // Some embedded/local browser shells fail to paint WebP reliably.
        $files = glob($directory . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);
        if (!is_array($files) || empty($files)) {
            $files = glob($directory . '*.{webp,jpg,jpeg,png,gif}', GLOB_BRACE);
        }

        if (!is_array($files)) {
            $files = [];
        }

        natsort($files);
        foreach ($files as $path) {
            $filename = basename((string)$path);
            if ($filename === '') {
                continue;
            }
            $catalog[$filename] = spp_background_image_url($filename);
        }

        if (empty($catalog)) {
            $catalog['19.jpg'] = spp_background_image_url('19.jpg');
        }

        return $catalog;
    }
}

if (!function_exists('spp_normalize_background_mode')) {
    function spp_normalize_background_mode($mode, $fallback = 'daily') {
        $fallback = strtolower(trim((string)$fallback));
        $validModes = ['random', 'daily', 'section', 'fixed'];
        if (!in_array($fallback, $validModes, true)) {
            $fallback = 'daily';
        }

        $mode = strtolower(trim((string)$mode));
        if ($mode === 'as_is') {
            return 'daily';
        }

        return in_array($mode, $validModes, true) ? $mode : $fallback;
    }
}

if (!function_exists('spp_background_mode_options')) {
    function spp_background_mode_options() {
        return [
            'random' => 'Random',
            'daily' => 'Once a Day',
            'section' => 'By Main Section',
            'fixed' => 'Fixed Background',
        ];
    }
}

if (!function_exists('spp_background_image_label')) {
    function spp_background_image_label($filename) {
        $base = pathinfo((string)$filename, PATHINFO_FILENAME);
        $base = preg_replace('/[^a-zA-Z0-9]+/', ' ', (string)$base);
        $base = trim((string)$base);
        if ($base === '') {
            return 'Background';
        }

        if (ctype_digit($base)) {
            return 'Background ' . $base;
        }

        return ucwords($base);
    }
}

if (!function_exists('spp_array_first_key')) {
    function spp_array_first_key(array $items) {
        foreach ($items as $key => $value) {
            return $key;
        }

        return null;
    }
}

if (!function_exists('spp_background_section_key')) {
    function spp_background_section_key($mainRoute = null, $subRoute = null) {
        $mainRoute = strtolower(trim((string)($mainRoute ?? ($_GET['n'] ?? 'frontpage'))));
        $subRoute = strtolower(trim((string)($subRoute ?? ($_GET['sub'] ?? ''))));

        if ($mainRoute === '' || $mainRoute === 'index') {
            $mainRoute = 'frontpage';
        }

        if ($mainRoute === 'server') {
            $armorySubs = ['chars', 'guilds', 'honor', 'talents', 'items', 'marketplace'];
            $gameGuideSubs = ['commands', 'botcommands', 'wbuffbuilder'];

            if (in_array($subRoute, $armorySubs, true)) {
                return 'armory';
            }
            if (in_array($subRoute, $gameGuideSubs, true)) {
                return 'gameguide';
            }

            return 'workshop';
        }

        if ($mainRoute === 'gameguide') {
            return 'gameguide';
        }
        if ($mainRoute === 'forum') {
            return 'forums';
        }
        if ($mainRoute === 'armory') {
            return 'armory';
        }
        if ($mainRoute === 'account' || $mainRoute === 'admin') {
            return 'account';
        }
        if ($mainRoute === 'media') {
            return 'media';
        }
        if ($mainRoute === 'community' || $mainRoute === 'whoisonline') {
            return 'community';
        }
        if ($mainRoute === 'statistic') {
            return 'workshop';
        }

        return 'frontpage';
    }
}

if (!function_exists('spp_pick_background_path')) {
    function spp_pick_background_path($mode = 'daily', $selectedImage = '', array $catalog = null, $sectionKey = null) {
        $catalog = $catalog ?: spp_background_image_catalog();
        if (empty($catalog)) {
            return spp_background_image_url('19.jpg');
        }

        $mode = spp_normalize_background_mode($mode, 'daily');
        $selectedImage = basename(trim((string)$selectedImage));
        $sectionKey = (string)($sectionKey ?: spp_background_section_key());
        $orderedPaths = array_values($catalog);

        if ($mode === 'fixed' && $selectedImage !== '' && isset($catalog[$selectedImage])) {
            return $catalog[$selectedImage];
        }

        if ($mode === 'fixed') {
            return $orderedPaths[0];
        }

        if ($mode === 'daily') {
            $index = abs(crc32('daily|' . date('Y-m-d'))) % count($orderedPaths);
            return $orderedPaths[$index];
        }

        if ($mode === 'section') {
            $index = abs(crc32('section|' . $sectionKey)) % count($orderedPaths);
            return $orderedPaths[$index];
        }

        return $orderedPaths[array_rand($orderedPaths)];
    }
}
