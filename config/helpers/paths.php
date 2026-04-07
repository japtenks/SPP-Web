<?php
// Path and filesystem helpers.

if (!function_exists('spp_project_root')) {
    function spp_project_root(): string
    {
        static $root = null;

        if ($root === null) {
            $root = str_replace('\\', '/', dirname(__DIR__, 2));
        }

        return $root;
    }
}

if (!function_exists('spp_join_path')) {
    function spp_join_path(string ...$segments): string
    {
        $clean = array();

        foreach ($segments as $index => $segment) {
            $segment = str_replace('\\', '/', (string)$segment);
            $segment = $index === 0 ? rtrim($segment, '/') : trim($segment, '/');
            if ($segment !== '') {
                $clean[] = $segment;
            }
        }

        return implode('/', $clean);
    }
}

if (!function_exists('spp_ensure_directory')) {
    function spp_ensure_directory(string $path): string
    {
        if ($path !== '' && !is_dir($path)) {
            @mkdir($path, 0775, true);
        }

        return $path;
    }
}

if (!function_exists('spp_storage_path')) {
    function spp_storage_path(string $relativePath = ''): string
    {
        $base = spp_project_root() . '/storage';
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');
        $target = $relativePath === '' ? $base : spp_join_path($base, $relativePath);

        $dir = pathinfo($target, PATHINFO_EXTENSION) !== '' ? dirname($target) : $target;
        spp_ensure_directory($dir);

        return $target;
    }
}

if (!function_exists('spp_public_asset_path')) {
    function spp_public_asset_path(string $relativePath = ''): string
    {
        $relativePath = trim(str_replace('\\', '/', $relativePath), '/');
        if ($relativePath === '') {
            return spp_project_root();
        }

        return spp_join_path(spp_project_root(), $relativePath);
    }
}

if (!function_exists('spp_component_root')) {
    function spp_component_root(string $section = ''): string {
        $section = trim(str_replace('\\', '/', $section), '/');
        return $section === '' ? 'components' : 'components/' . $section;
    }
}

if (!function_exists('spp_component_path')) {
    function spp_component_path(string $relativePath = ''): string {
        $base = spp_component_root();
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        return $relativePath === '' ? $base : $base . '/' . $relativePath;
    }
}

if (!function_exists('spp_site_url')) {
    function spp_site_url(string $relativePath = ''): string {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        return $relativePath === '' ? '/' : '/' . $relativePath;
    }
}
