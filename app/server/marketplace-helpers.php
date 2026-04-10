<?php

if (!function_exists('spp_marketplace_icon_url')) {
    function spp_marketplace_icon_url($iconName)
    {
        $iconName = trim((string)$iconName);
        if ($iconName === '') {
            return spp_armory_icon_url('404.png');
        }

        $basename = preg_replace('/\.(png|jpg|jpeg|gif)$/i', '', $iconName);
        return spp_resolve_armory_icon_url(strtolower((string)$basename));
    }
}

if (!function_exists('spp_marketplace_profession_icon_url')) {
    function spp_marketplace_profession_icon_url($skillId, $iconName = '')
    {
        $skillId = (int)$skillId;
        $overrides = [
            129 => 'inv_misc_bandage_15',
            171 => 'trade_alchemy',
            185 => 'inv_misc_food_15',
            393 => 'inv_misc_pelt_wolf_01',
            333 => 'trade_engraving',
        ];

        if (isset($overrides[$skillId])) {
            return spp_marketplace_icon_url($overrides[$skillId]);
        }

        return spp_marketplace_icon_url($iconName);
    }
}

if (!function_exists('spp_marketplace_class_icon_url')) {
    function spp_marketplace_class_icon_url($classId)
    {
        $classId = (int)$classId;
        $icons = [
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
        ];

        if (!isset($icons[$classId])) {
            return spp_armory_icon_url('404.png');
        }

        return spp_resolve_armory_icon_url($icons[$classId]);
    }
}

if (!function_exists('spp_marketplace_race_icon_url')) {
    function spp_marketplace_race_icon_url($raceId, $gender)
    {
        $raceId = (int)$raceId;
        $gender = ((int)$gender === 1) ? 'female' : 'male';
        $icons = [
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
        ];

        if (!isset($icons[$raceId])) {
            return spp_armory_icon_url('404.png');
        }

        return spp_resolve_armory_icon_url($icons[$raceId]);
    }
}

if (!function_exists('spp_marketplace_profession_tier_label')) {
    function spp_marketplace_profession_tier_label($maxRank)
    {
        $maxRank = (int)$maxRank;
        if ($maxRank >= 450) return 'Grand Master';
        if ($maxRank >= 375) return 'Master';
        if ($maxRank >= 300) return 'Artisan';
        if ($maxRank >= 225) return 'Expert';
        if ($maxRank >= 150) return 'Journeyman';
        if ($maxRank >= 75) return 'Apprentice';
        return 'Training';
    }
}

if (!function_exists('spp_marketplace_quality_color')) {
    function spp_marketplace_quality_color($quality)
    {
        switch ((int)$quality) {
            case 0: return '#9d9d9d';
            case 1: return '#ffffff';
            case 2: return '#1eff00';
            case 3: return '#0070dd';
            case 4: return '#a335ee';
            case 5: return '#ff8000';
            default: return '#e6cc80';
        }
    }
}

if (!function_exists('spp_marketplace_recipe_display_name')) {
    function spp_marketplace_recipe_display_name($name)
    {
        $display = preg_replace('/^(recipe|pattern|plans|formula|manual|schematic|book|design|tome|technique)\s*:\s*/i', '', (string)$name);
        return trim((string)$display);
    }
}

if (!function_exists('spp_marketplace_profession_recipe_signature')) {
    function spp_marketplace_profession_recipe_signature($spellName, $itemNames = [])
    {
        $spellName = strtolower(trim((string)$spellName));
        $spellName = preg_replace('/\s+/', ' ', $spellName);
        $items = [];
        foreach ((array)$itemNames as $itemName) {
            $itemName = strtolower(trim((string)$itemName));
            $itemName = preg_replace('/\s+/', ' ', $itemName);
            if ($itemName !== '') {
                $items[] = $itemName;
            }
        }
        sort($items);

        return $spellName . '|' . implode('|', $items);
    }
}

if (!function_exists('spp_marketplace_faction_name')) {
    function spp_marketplace_faction_name($raceId)
    {
        return in_array((int)$raceId, [1, 3, 4, 7, 11, 22, 25, 29], true) ? 'Alliance' : 'Horde';
    }
}

if (!function_exists('spp_marketplace_is_specialization_recipe')) {
    function spp_marketplace_is_specialization_recipe($expansion, $skillId, $spellId, $spellName = '', $itemName = '')
    {
        $expansion = (int)$expansion;
        $skillId = (int)$skillId;
        $spellId = (int)$spellId;
        $haystack = strtolower(trim((string)$spellName . ' ' . $itemName));

        $exactSpellIds = [
            0 => [
                164 => [9787, 9788],
                165 => [10656, 10658, 10660],
                202 => [20219, 20222],
            ],
            1 => [
                164 => [9787, 9788, 17039, 17040, 17041],
                165 => [10656, 10658, 10660],
                202 => [20219, 20222],
            ],
            2 => [
                164 => [9787, 9788, 17039, 17040, 17041],
                165 => [10656, 10658, 10660],
                202 => [20219, 20222],
            ],
        ];

        if (!empty($exactSpellIds[$expansion][$skillId]) && in_array($spellId, $exactSpellIds[$expansion][$skillId], true)) {
            return true;
        }

        $containsAny = function ($needles) use ($haystack) {
            foreach ($needles as $needle) {
                if ($needle !== '' && strpos($haystack, strtolower($needle)) !== false) {
                    return true;
                }
            }
            return false;
        };

        $keywordFamilies = [
            0 => [
                165 => ['dragonscale', 'tribal', 'elemental'],
                202 => ['gnomish', 'goblin'],
            ],
            1 => [
                164 => ['armorsmith', 'weaponsmith', 'swordsmith', 'hammersmith', 'axesmith'],
                165 => ['dragonscale', 'tribal', 'elemental'],
                202 => ['gnomish', 'goblin'],
            ],
            2 => [
                164 => ['armorsmith', 'weaponsmith', 'swordsmith', 'hammersmith', 'axesmith'],
                165 => ['dragonscale', 'tribal', 'elemental'],
                202 => ['gnomish', 'goblin'],
            ],
        ];

        return !empty($keywordFamilies[$expansion][$skillId]) && $containsAny($keywordFamilies[$expansion][$skillId]);
    }
}

if (!function_exists('spp_marketplace_detect_expansion')) {
    function spp_marketplace_detect_expansion($realmId, array $realmMap = [])
    {
        $realmId = (int)$realmId;

        if (function_exists('spp_realm_to_expansion')) {
            $expansionName = strtolower(trim((string)spp_realm_to_expansion($realmId)));
            if ($expansionName === 'tbc') {
                return 1;
            }
            if ($expansionName === 'wotlk') {
                return 2;
            }
            if ($expansionName === 'classic') {
                return 0;
            }
        }

        $realmInfo = $realmMap[$realmId] ?? [];
        $haystack = strtolower(trim(implode(' ', array_filter([
            (string)($realmInfo['world'] ?? ''),
            (string)($realmInfo['chars'] ?? ''),
            (string)($realmInfo['armory'] ?? ''),
        ]))));

        if (strpos($haystack, 'wotlk') !== false) {
            return 2;
        }
        if (strpos($haystack, 'tbc') !== false) {
            return 1;
        }

        return 0;
    }
}

if (!function_exists('spp_marketplace_profession_cap')) {
    function spp_marketplace_profession_cap($expansion)
    {
        $expansion = (int)$expansion;
        if ($expansion >= 2) {
            return 450;
        }
        if ($expansion >= 1) {
            return 375;
        }

        return 300;
    }
}

if (!function_exists('spp_marketplace_specialty_definitions')) {
    function spp_marketplace_specialty_definitions($expansion)
    {
        $expansion = (int)$expansion;

        $definitions = [
            164 => [
                [
                    'slug' => 'armorsmith',
                    'label' => 'Armorsmith',
                    'spell_ids' => [9788],
                    'keywords' => ['armorsmith'],
                ],
                [
                    'slug' => 'weaponsmith',
                    'label' => 'Weaponsmith',
                    'spell_ids' => [9787],
                    'keywords' => ['weaponsmith'],
                ],
            ],
            165 => [
                [
                    'slug' => 'elemental',
                    'label' => 'Elemental',
                    'spell_ids' => [10658],
                    'keywords' => ['elemental leatherworking', 'elemental'],
                ],
                [
                    'slug' => 'dragon',
                    'label' => 'Dragon',
                    'spell_ids' => [10656],
                    'keywords' => ['dragonscale leatherworking', 'dragonscale', 'dragon'],
                ],
                [
                    'slug' => 'tribal',
                    'label' => 'Tribal',
                    'spell_ids' => [10660],
                    'keywords' => ['tribal leatherworking', 'tribal'],
                ],
            ],
        ];

        if ($expansion >= 1) {
            $definitions[164][] = [
                'slug' => 'axe',
                'label' => 'Axe',
                'spell_ids' => [17041],
                'keywords' => ['master axesmith', 'axesmith'],
            ];
            $definitions[164][] = [
                'slug' => 'mace',
                'label' => 'Mace',
                'spell_ids' => [17040],
                'keywords' => ['master hammersmith', 'hammersmith', 'mace'],
            ];
            $definitions[164][] = [
                'slug' => 'sword',
                'label' => 'Sword',
                'spell_ids' => [17039],
                'keywords' => ['master swordsmith', 'swordsmith', 'sword'],
            ];
        }

        return $definitions;
    }
}
