<?php

if (!function_exists('spp_route_subpage')) {
    function spp_route_subpage($requestedSub = null): string
    {
        $sub = trim((string)($requestedSub ?? ''));
        if ($sub === '') {
            return 'index';
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $sub) || is_numeric($sub)) {
            return 'index';
        }

        return $sub;
    }
}

if (!function_exists('spp_route_component')) {
    function spp_route_component($requestedComponent = null, $defaultComponent = 'frontpage'): string
    {
        $component = trim((string)($requestedComponent ?? ''));
        if ($component === '') {
            $component = trim((string)$defaultComponent);
        }

        return $component !== '' ? $component : 'frontpage';
    }
}

if (!function_exists('spp_route_execution_paths')) {
    function spp_route_execution_paths(string $component, string $subpage, $config = null): array
    {
        $component = spp_route_component($component);
        $subpage = spp_route_subpage($subpage);

        return array(
            'component' => $component,
            'subpage' => $subpage,
            'script_file' => spp_component_path($component . '/' . $component . '.' . $subpage . '.php'),
            'template_file' => spp_template_path($component . '/' . $component . '.' . $subpage . '.php', $config),
        );
    }
}

if (!function_exists('spp_page_contract_bootstrap')) {
    function spp_page_contract_bootstrap(string $family, string $page, array $assets = array(), $config = null): array
    {
        $family = trim($family);
        $page = trim($page);

        $GLOBALS['spp_page_contract'] = array(
            'family' => $family,
            'page' => $page,
            'assets' => $assets,
        );

        foreach ((array)($assets['styles'] ?? array()) as $styleFile) {
            if (is_string($styleFile) && trim($styleFile) !== '') {
                spp_register_family_style($family, $styleFile, $config);
            }
        }

        foreach ((array)($assets['scripts'] ?? array()) as $scriptFile) {
            if (is_string($scriptFile) && trim($scriptFile) !== '') {
                spp_register_family_script($family, $scriptFile, $config, array('defer' => true));
            }
        }

        foreach ((array)($assets['style_paths'] ?? array()) as $stylePath) {
            if (is_string($stylePath) && trim($stylePath) !== '') {
                spp_register_style_asset(
                    'page:' . $family . ':' . $page . ':style-path:' . $stylePath,
                    spp_template_asset_url(ltrim($stylePath, '/'), $config)
                );
            }
        }

        foreach ((array)($assets['script_paths'] ?? array()) as $scriptPath) {
            if (is_string($scriptPath) && trim($scriptPath) !== '') {
                spp_register_script_asset(
                    'page:' . $family . ':' . $page . ':script-path:' . $scriptPath,
                    spp_template_asset_url(ltrim($scriptPath, '/'), $config),
                    array('defer' => true)
                );
            }
        }

        return $GLOBALS['spp_page_contract'];
    }
}

if (!function_exists('spp_utility_contract_bootstrap')) {
    function spp_utility_contract_bootstrap(string $family, string $endpoint, array $contract = array()): array
    {
        $family = trim($family);
        $endpoint = trim($endpoint);

        $GLOBALS['spp_route_contract'] = array(
            'contract_type' => 'utility',
            'family' => $family,
            'page' => $endpoint,
            'kind' => (string)($contract['kind'] ?? ($contract['utility_kind'] ?? '')),
            'utility_kind' => (string)($contract['utility_kind'] ?? ($contract['kind'] ?? '')),
            'loader' => (string)($contract['loader'] ?? ''),
            'component_role' => (string)($contract['component_role'] ?? 'adapter'),
            'response' => $contract['response'] ?? null,
            'nobody' => (bool)($contract['nobody'] ?? false),
        );

        return $GLOBALS['spp_route_contract'];
    }
}
