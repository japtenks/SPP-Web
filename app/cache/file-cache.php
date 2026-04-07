<?php

if (!function_exists('spp_cache_directory')) {
    function spp_cache_directory(string $namespace = 'data'): string
    {
        $namespace = trim(preg_replace('/[^a-z0-9_\-\/]+/i', '-', $namespace), '/');
        if ($namespace === '') {
            $namespace = 'data';
        }

        $baseDirectory = function_exists('spp_storage_path')
            ? spp_storage_path('cache')
            : (dirname(__DIR__, 2) . '/storage/cache');

        $directory = rtrim(str_replace('\\', '/', $baseDirectory), '/') . '/' . $namespace;
        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        return $directory;
    }
}

if (!function_exists('spp_cache_file_path')) {
    function spp_cache_file_path(string $namespace, string $key): string
    {
        return spp_cache_directory($namespace) . '/' . md5($namespace . '|' . $key) . '.phpcache';
    }
}

if (!function_exists('spp_cache_get')) {
    function spp_cache_get(string $namespace, string $key, int $ttlSeconds, &$found = false)
    {
        $found = false;
        $cacheFile = spp_cache_file_path($namespace, $key);
        if (!is_file($cacheFile)) {
            return null;
        }

        $payload = @unserialize((string)@file_get_contents($cacheFile));
        if (!is_array($payload) || !array_key_exists('value', $payload)) {
            return null;
        }

        $storedAt = isset($payload['stored_at']) ? (int)$payload['stored_at'] : (int)@filemtime($cacheFile);
        if ($ttlSeconds > 0 && $storedAt > 0 && (time() - $storedAt) >= $ttlSeconds) {
            return null;
        }

        $found = true;
        return $payload['value'];
    }
}

if (!function_exists('spp_cache_put')) {
    function spp_cache_put(string $namespace, string $key, $value): bool
    {
        $cacheFile = spp_cache_file_path($namespace, $key);
        $payload = serialize([
            'stored_at' => time(),
            'value' => $value,
        ]);

        return @file_put_contents($cacheFile, $payload, LOCK_EX) !== false;
    }
}

if (!function_exists('spp_cache_forget')) {
    function spp_cache_forget(string $namespace, string $key): bool
    {
        $cacheFile = spp_cache_file_path($namespace, $key);
        return !is_file($cacheFile) || @unlink($cacheFile);
    }
}
