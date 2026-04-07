<?php

require_once dirname(__DIR__, 2) . '/components/forum/forum.avatar.php';

if (!function_exists('spp_server_chars_class_icon_url')) {
    function spp_server_chars_class_icon_url($classId)
    {
        $classId = (int)$classId;
        $icons = array(
            1 => 'class-1',
            2 => 'class-2',
            3 => 'class-3',
            4 => 'class-4',
            5 => 'class-5',
            6 => 'class-6',
            7 => 'class-7',
            8 => 'class-8',
            9 => 'class-9',
            11 => 'class-11',
        );

        if (!isset($icons[$classId])) {
            return spp_armory_icon_url('404.png');
        }

        return spp_resolve_armory_icon_url($icons[$classId]);
    }
}

if (!function_exists('spp_server_chars_race_icon_url')) {
    function spp_server_chars_race_icon_url($raceId, $gender)
    {
        $raceId = (int)$raceId;
        $gender = ((int)$gender === 1) ? 'female' : 'male';
        $icons = array(
            1 => 'achievement_character_human_' . $gender,
            2 => 'achievement_character_orc_' . $gender,
            3 => 'achievement_character_dwarf_' . $gender,
            4 => 'achievement_character_nightelf_' . $gender,
            5 => 'achievement_character_undead_' . $gender,
            6 => 'achievement_character_tauren_' . $gender,
            7 => 'achievement_character_gnome_' . $gender,
            8 => 'achievement_character_troll_' . $gender,
            10 => 'achievement_character_bloodelf_' . $gender,
            11 => 'achievement_character_draenei_' . $gender,
        );

        if (!isset($icons[$raceId])) {
            return spp_armory_icon_url('404.png');
        }

        return spp_resolve_armory_icon_url($icons[$raceId]);
    }
}

if (!function_exists('spp_server_chars_faction_icon_url')) {
    function spp_server_chars_faction_icon_url(string $factionName): string
    {
        $slug = strtolower(trim($factionName));
        if ($slug !== 'alliance' && $slug !== 'horde') {
            return spp_modern_faction_logo_url('alliance');
        }

        return spp_modern_faction_logo_url($slug);
    }
}

if (!function_exists('spp_server_chars_parse_search')) {
    function spp_server_chars_parse_search($search): array
    {
        $parsed = array(
            'name' => array(),
            'guild' => array(),
            'zone' => array(),
            'class' => array(),
            'race' => array(),
            'faction' => array(),
            'level' => array(),
            'generic' => array(),
        );

        $tokens = str_getcsv((string)$search, ' ', '"');
        $currentFlag = null;
        $flagMap = array(
            '-n' => 'name',
            '-g' => 'guild',
            '-z' => 'zone',
            '-c' => 'class',
            '-r' => 'race',
            '-f' => 'faction',
            '-l' => 'level',
        );

        foreach ($tokens as $token) {
            $token = trim((string)$token);
            if ($token === '') {
                continue;
            }

            $lowerToken = strtolower($token);
            if (isset($flagMap[$lowerToken])) {
                $currentFlag = $flagMap[$lowerToken];
                continue;
            }

            if ($currentFlag !== null) {
                $parsed[$currentFlag][] = $token;
                $currentFlag = null;
            } else {
                $parsed['generic'][] = $token;
            }
        }

        return $parsed;
    }
}

if (!function_exists('spp_server_chars_search_terms_match')) {
    function spp_server_chars_search_terms_match(array $terms, $value): bool
    {
        if (empty($terms)) {
            return true;
        }

        $value = strtolower((string)$value);
        foreach ($terms as $term) {
            if (stripos($value, strtolower((string)$term)) === false) {
                return false;
            }
        }

        return true;
    }
}

if (!function_exists('spp_server_chars_page_url')) {
    function spp_server_chars_page_url(array $params = array()): string
    {
        $query = array(
            'n' => 'server',
            'sub' => 'chars',
        );

        $allowedKeys = array(
            'realm',
            'search',
            'show_bots',
            'online',
            'faction',
            'p',
            'per_page',
        );

        foreach ($allowedKeys as $key) {
            if (!array_key_exists($key, $params)) {
                continue;
            }

            $value = $params[$key];
            if ($value === null || $value === '') {
                continue;
            }

            $query[$key] = $value;
        }

        return 'index.php?' . http_build_query($query);
    }
}
